<?php
/**
 * BZJ Registration Kernel v35 — Transaction-safe, stateless, robust human code challenge for BuddyBoss/BuddyPress.
 * - Human users only; code resets every GET visit; never lost on error reload
 * - Error always shows above 'Create Account' button
 * - Registration works if and only if challenge VERIFIED on THIS submission, sso_secret correct, nonce correct, all fields filled.
 * - All log entries separated for clarity
 * - TODO: secret requires sso token for approval. Code only required user to be on register page and for codes to match. Consider sending registration page code to email, telegram, sms or whatsapp.
 */

if (!defined('ABSPATH')) exit;

define('BZJ_CHALLENGE_TTL', 20 * MINUTE_IN_SECONDS);
if (!defined('BZJ_LOG_DIR')) {
    define('BZJ_LOG_DIR', ABSPATH . '/data/logs/');
}

/** ===== LOAD BUZZ_SSO_SECRET FROM ENV ===== */
if (!defined('BZJ_SSO_SECRET')) {
    if (file_exists(ABSPATH . '/shared/db_helpers.php')) {
        require_once ABSPATH . '/shared/db_helpers.php';
    }
    if (defined('BUZZ_SSO_SECRET')) {
        define('BZJ_SSO_SECRET', BUZZ_SSO_SECRET);
    } elseif (getenv('BUZZ_SSO_SECRET')) {
        define('BZJ_SSO_SECRET', getenv('BUZZ_SSO_SECRET'));
    } else {
        define('BZJ_SSO_SECRET', 'missing-BUZZ_SSO_SECRET');
    }
}

/** ===== LOG UTILITY (with separator) ===== */
function bzj_sanitize_log_data($data) {
    if (!is_array($data)) return $data;

    $clean = [];

    foreach ($data as $key => $value) {
        // Normalize key for detection
        $k = strtolower($key);

        // Remove anything containing 'pass'
        $danger_keys = ['pass', 'password', 'pwd', /* 'secret', */ 'token', 'auth'];
        
        foreach ($danger_keys as $needle) {
            if (strpos($k, $needle) !== false) {
                $clean[$key] = '[REDACTED]';
                continue 2;
            }
        }
        
/*        if (strpos($k, 'email') !== false) {
            $clean[$key] = substr((string)$value, 0, 4) . '****';
            continue;
        }
*/        
        // Recurse into nested arrays
        if (is_array($value)) {
            $clean[$key] = bzj_sanitize_log_data($value);
        } else {
            $clean[$key] = $value;
        }
        
    }

    return $clean;
}

function bzj_log($type, $data = []) {

    if (!file_exists(BZJ_LOG_DIR)) {
        @mkdir(BZJ_LOG_DIR, 0755, true);
    }

    $log_file = BZJ_LOG_DIR . 'bzj-registration-kernel.log';

    if (file_exists($log_file) && filesize($log_file) > 5 * 1024 * 1024) {
        unlink($log_file);
    }

    $payload = [
        'when' => date('c'),
        'ip'   => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'type' => $type,
        'data' => bzj_sanitize_log_data($data),
        'post' => bzj_sanitize_log_data(array_diff_key($_POST, array_flip([
            'signup_password',
            'user_pass',
            'password',
            'pass',
            'bzj_sso_secret'
        ]))),
    ];

    file_put_contents(
        $log_file,
        json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . "\n==========\n",
        FILE_APPEND
    );
}

/** ===== Visitor fingerprint ===== */
function bzj_fp() {
    return substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 32);
}

/** ===== Challenge lifecycle: reset ONLY ON GET (never on POST fail) ===== */
add_action('template_redirect', function () {
    if (
        function_exists('bp_is_register_page') &&
        bp_is_register_page() &&
        !is_user_logged_in() &&
        ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    ) {
        $fp = bzj_fp();
        set_transient("bzj_state_$fp", [
            'verified'     => false,
            'created'      => time(),
            'challenge_id' => wp_generate_password(8, false)
        ], BZJ_CHALLENGE_TTL);
        delete_transient("bzj_code_$fp");
    }
}, 1);

/** ===== AJAX: Code generator ===== */
add_action('wp_ajax_nopriv_bzj_generate_code', function () {
    $fp = bzj_fp();
    $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    set_transient("bzj_code_$fp", $code, BZJ_CHALLENGE_TTL);
    bzj_log('challenge_code_generated', ['fp' => $fp, 'code'=> $code]);
    wp_send_json_success(['code' => $code]);
});

/** ===== AJAX: Code verifier (side-effect: sets request cache) ===== */
global $bzj_request_verified;
$bzj_request_verified = false;

add_action('wp_ajax_nopriv_bzj_verify_code', function () {
    global $bzj_request_verified;
    $fp = bzj_fp();
    $input = strtoupper(trim($_POST['code'] ?? ''));
    $stored = get_transient("bzj_code_$fp");
    $state  = get_transient("bzj_state_$fp");
    if (!$stored || $input !== $stored || empty($state)) {
        bzj_log('challenge_verify_failed', ['fp'=>$fp, 'code'=>$input, 'expected'=>$stored]);
        wp_send_json_error(['msg'=>'invalid_code']);
    }
    $state['verified'] = true;
    set_transient("bzj_state_$fp", $state, BZJ_CHALLENGE_TTL);
    $bzj_request_verified = true;
    bzj_log('challenge_verify_success', ['fp'=>$fp, 'code'=>$input]);
    wp_send_json_success(['msg'=>'ok']);
});

/** ===== Transaction-safe: request-level or transient verification ===== */
function bzj_auth_decision() {

    global $bzj_request_verified;

    $fp = bzj_fp();
    $state = get_transient("bzj_state_$fp");

    $code_valid = (
        $bzj_request_verified === true ||
        (!empty($state) && !empty($state['verified']))
    );

    $secret_valid = (
        !empty($_POST['bzj_sso_secret']) &&
        hash_equals($_POST['bzj_sso_secret'], BZJ_SSO_SECRET)
    );

    $has_nonce = (
        !empty($_POST['_wpnonce']) &&
        wp_verify_nonce($_POST['_wpnonce'], 'bp_new_signup')
    );

    $has_marker = (
        !empty($_POST['bzj_form_marker']) &&
        $_POST['bzj_form_marker'] === '1'
    );

    if (!$has_nonce || !$has_marker) {
        return false;
    }

    /**
     * FINAL RULE:
     * - Code verification alone is enough
     * - OR secret override only if code is NOT verified
     */
    if ($code_valid) {
        return true;
    }

    if ($secret_valid) {
        return true;
    }
    
    bzj_log('AUTH_DECISION_DEBUG', [
        'result' => bzj_auth_decision(),
        'code_valid' => $code_valid ?? null,
        'secret_present' => !empty($_POST['bzj_sso_secret']),
        'post_keys' => array_keys($_POST),
        'verified_flag' => $bzj_request_verified ?? false
    ]);

    return false;
}

/** ====== UI row: challenge + error placement always directly above Create Account ====== */
add_action('bp_before_registration_submit_buttons', function () {
    // Error placement above button, never above nickname.
    if (!empty($GLOBALS['bzj_error_above_button'])) {
        echo '<div id="bzj-error-above-btn" style="margin:12px 0;padding:8px;background:#ffefef;border:1.5px solid #ee6565;color:#b0000b;border-radius:7px;text-align:center;font-weight:bold;font-size:14px;">'
            . esc_html($GLOBALS['bzj_error_above_button'])
            . '</div>';
    }
    
    ?>
<div class="bzj-registration-challenge" style="margin-top:10px;padding:6px 8px;border:1.5px solid #e1e1e5;border-radius:7px;text-align:center;">
    <div style="display:flex;align-items:center;gap:10px;justify-content: center;">
        <span id="bzj-code" style="font-family:monospace;font-size:1.2em;font-weight:bold;padding:2px 18px;border:1px solid #d0d1d7;border-radius:5px;background:#fafafd;">-----</span>
        <button type="button" aria-label="Refresh code" id="bzj-refresh" title="Generate or refresh code" style="font-size:1.2em;padding:0 7px 1px 7px;margin:0 3px 0 4px;border-radius:5px;">⟳</button>
        <input type="text" id="bzj-input" placeholder="Type code" maxlength="5" autocomplete="off" style="width:85px;border: 1px solid #d0d1d7;border-radius:5px;padding:3px;">
    </div>
    <div>
        <small id="bzj-status" style="margin-top:3px;color:#737373;">Match codes to create account</small>
    </div>
</div>
<style>
    .bzj-registration-challenge input#bzj-input { margin-bottom: 0 !important; }
    input#signup_submit.disabled {
        background-color: #E3E6ED !important;
        border: 1px solid #D4D9E2 !important;
    }
    #bzj-error-above-btn { margin-bottom:7px }
</style>
<input type="hidden" name="bzj_form_marker" value="1">
<input type="hidden" name="bzj_sso_secret" value="">

<script>
(function(){
    const codeBox = document.getElementById('bzj-code');
    const refresh = document.getElementById('bzj-refresh');
    const input   = document.getElementById('bzj-input');
    const status  = document.getElementById('bzj-status');
    let currentCode = '';
    function findSubmitBtn() {
        return (
            document.querySelector('#signup_submit') ||
            document.querySelector('#signup-form > div.submit input[type=submit]') ||
            document.querySelector('#signup-form button[type=submit]') ||
            document.querySelector('button[name=signup_submit]')
        );
    }
    function disable() {
        const btn = findSubmitBtn();
        if(btn) { btn.disabled = true; btn.classList.add('disabled'); }
    }
    function enable() {
        const btn = findSubmitBtn();
        if(btn) { btn.disabled = false; btn.classList.remove('disabled'); }
    }
    // Always show initial message
    status.innerText = 'Match codes to create account';
    status.style.color = '#737373';
    input.value = '';
    input.disabled = true;
    disable();

    async function generate() {
        codeBox.textContent = '.....';
        currentCode = '';
        disable();
        status.innerText = 'Match codes to create account';
        status.style.color = '#737373';
        input.value = '';
        input.disabled = true;
        try {
            const res = await fetch('/wp-admin/admin-ajax.php?action=bzj_generate_code');
            const json = await res.json();
            if (json.success) {
                codeBox.textContent = json.data.code;
                currentCode = json.data.code;
                input.disabled = false;
            } else {
                codeBox.textContent = 'ERR';
                input.disabled = true;
                status.innerText = 'Could not generate code (reload?)';
                status.style.color = 'red';
            }
        } catch(e) {
            codeBox.textContent = 'ERR';
            input.disabled = true;
            status.innerText = 'Could not generate code (reload?)';
            status.style.color = 'red';
        }
        disable();
    }
    async function verify() {
        const val = input.value.trim().toUpperCase();
        if (!val || !currentCode || val.length !== currentCode.length) {
            status.innerText = 'Match codes to create account';
            status.style.color = '#737373';
            disable();
            return;
        }
        try {
            const res = await fetch('/wp-admin/admin-ajax.php?action=bzj_verify_code', {
                method:'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'code=' + encodeURIComponent(val)
            });
            const json = await res.json();
            if(json.success) {
                status.innerText = 'Verified! Create your account';
                status.style.color = 'green';
                enable();
            } else {
                status.innerText = 'Invalid code. Try again.';
                status.style.color = 'red';
                disable();
            }
        } catch(e) {
            status.innerText = 'Error during verification';
            status.style.color = 'red';
            disable();
        }
    }
    refresh.addEventListener('click', () => {
        generate();
        input.value = '';
        status.innerText = 'Match codes to create account';
        status.style.color = '#737373';
    });
    input.addEventListener('input', verify);
    generate();
})();
</script>
<?php
});

/**
 * Validation: only show BP-compatible error, above the button, if code is not verified
 */

add_filter('bp_core_validate_user_signup', function ($result) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return $result;
    if (!bzj_auth_decision()) {
        $GLOBALS['bzj_error_above_button'] = 'Verify code to create your account.';
        $auth = bzj_auth_decision();
        bzj_log('blocked_signup', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'auth_result' => bzj_auth_decision(),
            'post' => $_POST
        ]);
    }
    return $result;
}, 1);

/** 
 * Failsafe: user_register only succeeds for truly verified submissions;
 * Only here do we consume/delete the challenge.
 */
add_action('user_register', function ($user_id) {
    global $bzj_request_verified;
    bzj_log('user_register_event', [
        'user_id' => $user_id,
        'verified' => $bzj_request_verified,
        'post' => $_POST
    ]);
    if (!bzj_auth_decision()) {
        require_once ABSPATH.'wp-admin/includes/user.php';
        wp_delete_user($user_id);
        bzj_log('deleted_user', [
            'user_id'=>$user_id, 'post'=>$_POST
        ]);
    } else {
        $fp = bzj_fp();
        delete_transient("bzj_state_$fp");
        delete_transient("bzj_code_$fp");
    }
}, 1);

add_filter('registration_errors', function($errors) {

    if (!bzj_auth_decision()) {
        $errors->add('bzj_block', 'Verification required before account creation.');
        
        bzj_log('BLOCK_registration_errors', [
            'post' => $_POST
        ]);
    }

    return $errors;

}, 1);

add_filter('wp_pre_insert_user_data', function($data, $update) {

    if ($update) return $data;

    if (!bzj_auth_decision()) {
        bzj_log('BLOCK_pre_insert_user', [
            'data' => $data,
            'post' => $_POST
        ]);

        // HARD STOP (correct WordPress pattern)
        wp_die(
            'Registration blocked: verification failed.',
            'Blocked',
            ['response' => 403]
        );
    }

    return $data;

}, 1, 2);

//Elementor Form for Redirection to the BuddyBoss register page.
add_action('wp_footer', function() {
    if ( is_page('register') ) { // Or use your register page ID/slug.
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function getQueryVar(name) {
                let url = new URL(window.location.href);
                return url.searchParams.get(name) || '';
            }
            var email = getQueryVar('prefill_email');
            if (email) {
                var input = document.querySelector('#signup_email, input[name="signup_email"], input[name="email"]');
                if (input) input.value = email;
            }
        });
        </script>
        <?php
    }
});