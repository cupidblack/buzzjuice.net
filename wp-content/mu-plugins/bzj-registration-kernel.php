<?php
/**
 * BZJ Registration Kernel v50 — Unified Registration Event Bus Firewall (UREB-FW)
 * - Human code challenge for all registration flows: BuddyBoss, GiveWP, WooCommerce, AffiliateWP, direct, API/REST/etc.
 * - Pre-insert firewall (blocks before user exists)
 * - Async/REST/AJAX-safe errors (prevents JSON parse frontend error)
 * - Forensic rolling logs, trust logic, output buffer safety
 * - Stable Woo selectors, mutation observer
 */

if (!defined('ABSPATH')) exit;

/* ============================================================================
   0: CONFIG
============================================================================= */

define('BZJ_TTL', 20 * MINUTE_IN_SECONDS);
define('BZJ_REG_KNL_LOG_DIR', ABSPATH . '/data/logs/bzj-registration-kernel/');

/* ============================================================================
   1: SSO SECRET LOAD
============================================================================= */

if (!defined('BZJ_SSO_SECRET')) {
    if (file_exists(ABSPATH . '/shared/db_helpers.php')) require_once ABSPATH . '/shared/db_helpers.php';
    define('BZJ_SSO_SECRET', defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: 'missing-secret'));
}

/* ============================================================================
   2: LOGGING (ROTATING, REDACTED)
============================================================================= */
function bzj_log_file() {
    if (!file_exists(BZJ_REG_KNL_LOG_DIR)) wp_mkdir_p(BZJ_REG_KNL_LOG_DIR);
    $files = glob(BZJ_REG_KNL_LOG_DIR . '*.log') ?: [];
    usort($files, function($a, $b) { return filemtime($b) <=> filemtime($a); });
    $latest = $files[0] ?? null;
    if (!$latest || filesize($latest) > 512 * 1024) {
        $latest = BZJ_REG_KNL_LOG_DIR . 'bzj-' . date('Y-m-d-H-i-s') . '.log';
    }
    if (count($files) > 20) {
        usort($files, function($a, $b) { return filemtime($a) <=> filemtime($b); });
        while (count($files) > 20) @unlink(array_shift($files));
    }
    return $latest;
}
function bzj_sanitize($data) {
    if (!is_array($data)) return $data;
    $out = [];
    foreach ($data as $k => $v) {
        if (preg_match('/pass|pwd|token|auth|secret/i', $k)) {
            $out[$k] = '[REDACTED]';
            continue;
        }
        $out[$k] = is_array($v) ? bzj_sanitize($v) : $v;
    }
    return $out;
}
function bzj_log($type, $data = []) {
    $payload = [
        'ts' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'type' => $type,
        'context' => bzj_context(),
        'data' => bzj_sanitize($data),
        'post' => bzj_sanitize($_POST),
    ];
    file_put_contents(
        bzj_log_file(),
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n---\n",
        FILE_APPEND
    );
}

/* ============================================================================
   3: PLATFORM/CONTEXT DETECTION
============================================================================= */
function bzj_context() {
    if (function_exists('bp_is_register_page') && bp_is_register_page()) return 'buddyboss';
    if (!empty($_POST['give-form-id']) || (strpos($_SERVER['REQUEST_URI'] ?? '', 'give') !== false)) return 'givewp';
    if (!empty($_POST['affwp_register_nonce'])) return 'affiliatewp';
    if (!empty($_POST['woocommerce-register-nonce']) || !empty($_POST['createaccount']) || (function_exists('is_checkout') && is_checkout())) return 'woocommerce';
    return 'unknown';
}

/* ============================================================================
   4: REGISTRATION SCOPE/ASYNC/REST DETECTION
============================================================================= */
function bzj_is_registration_request() {
    if ( function_exists('bp_is_register_page') && bp_is_register_page()) return true;
    if ( strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-login.php?action=register') !== false ) return true;
    if (!empty($_POST['give-form-id']) || !empty($_POST['give_action']) || !empty($_POST['give_email'])) return true;
    if (!empty($_POST['woocommerce-register-nonce']) || !empty($_POST['createaccount']) || !empty($_POST['account_email']) || !empty($_POST['account_username'])) return true;
    if (!empty($_POST['affwp_register_nonce']) || !empty($_POST['affwp_user_login'])) return true;
    if (!empty($_POST['user_login']) && !empty($_POST['user_email'])) return true;
    return false;
}
function bzj_is_async_request() {
    if (wp_doing_ajax()) return true;
    if (defined('REST_REQUEST') && REST_REQUEST) return true;
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) return true;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return true;
    return false;
}
function bzj_is_trusted_system_request() {
    if (is_user_logged_in()) return true;
    if (!empty($_POST['bzj_sso_secret']) && hash_equals($_POST['bzj_sso_secret'], BZJ_SSO_SECRET)) return true;
    if (defined('WP_CLI') && WP_CLI) return true;
    if (defined('DOING_CRON') && DOING_CRON) return true;
    return false;
}

/* ============================================================================
   5: FINGERPRINT AND CHALLENGE STATE
============================================================================= */
function bzj_fp() {
    return substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 32);
}
function bzj_state() {
    return get_transient("bzj_state_" . bzj_fp());
}
function bzj_set_state($val) {
    set_transient("bzj_state_" . bzj_fp(), $val, BZJ_TTL);
}
function bzj_del_state() {
    delete_transient("bzj_state_" . bzj_fp());
    delete_transient("bzj_code_" . bzj_fp());
}

/* ============================================================================
   6: LIFECYCLE: CHALLENGE RESET ON REGISTRATION PAGE LOAD
============================================================================= */
add_action('template_redirect', function () {
    if (!is_user_logged_in() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && bzj_should_inject_ui()) {
        bzj_set_state(['verified' => false, 'context' => bzj_context()]);
        delete_transient("bzj_code_" . bzj_fp());
    }
}, 1);
function bzj_should_inject_ui() {
    return (
        (function_exists('bp_is_register_page') && bp_is_register_page()) ||
        (function_exists('is_checkout') && is_checkout()) ||
        (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], 'give') !== false || strpos($_SERVER['REQUEST_URI'], 'affiliate-area') !== false))
    );
}

/* ============================================================================
   7: AJAX HANDLERS: JSON **ONLY**, BUFFER-SAFE
============================================================================= */
add_action('wp_ajax_nopriv_bzj_generate_code', function () {
    while (ob_get_level()) { ob_end_clean(); }
    nocache_headers();
    try {
        $fp = bzj_fp();
        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        set_transient("bzj_code_$fp", $code, BZJ_TTL);
        bzj_set_state(['verified' => false, 'context' => bzj_context()]);
        bzj_log('code_generated', []);
        wp_send_json_success(['code' => $code]);
    } catch (Throwable $e) {
        bzj_log('generate_code_error', ['message' => $e->getMessage()]);
        wp_send_json_error(['msg' => 'generation_failed'], 500);
    }
});
add_action('wp_ajax_nopriv_bzj_verify_code', function () {
    while (ob_get_level()) { ob_end_clean(); }
    nocache_headers();
    try {
        $fp = bzj_fp();
        $input = strtoupper(trim($_POST['code'] ?? ''));
        $stored = get_transient("bzj_code_$fp");
        if (!$stored || $input !== $stored) {
            bzj_log('verify_failed', ['input' => $input, 'stored' => $stored]);
            wp_send_json_error(['msg' => 'invalid'], 403);
        }
        bzj_set_state(['verified' => true, 'context' => bzj_context()]);
        bzj_log('verify_success', []);
        wp_send_json_success(['msg' => 'ok']);
    } catch (Throwable $e) {
        bzj_log('verify_code_error', ['message' => $e->getMessage()]);
        wp_send_json_error(['msg' => 'verification_failed'], 500);
    }
});

/* ============================================================================
   8: PRIMARY FIREWALL — NO CHALLENGE → NO REGISTER (with EXCEPTIONS)
============================================================================= */
function bzj_can_register() {
    // Allow trusted system/admin
    if (bzj_is_trusted_system_request()) return true;
    // Ignore non-registration requests
    if (!bzj_is_registration_request()) return true;
    $state = bzj_state();
    $verified = (!empty($state) && !empty($state['verified']));
    if ($verified) return true;
    // Otherwise: block ALL
    bzj_log('registration_denied', [
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'context' => bzj_context(),
        'post_keys' => array_keys($_POST),
    ]);
    return false;
}

/* ============================================================================
   9: FORENSIC LOGGING OF ALL REG ATTEMPTS, INCLUDING API
============================================================================= */
add_action('init', function () {
    if (!bzj_is_registration_request()) return;
    bzj_log('registration_request_detected', [
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'ajax' => wp_doing_ajax(),
        'rest' => (defined('REST_REQUEST') && REST_REQUEST),
        'context' => bzj_context(),
        'post_keys' => array_keys($_POST),
    ]);
}, 1);

/* ============================================================================
   10: FRONTEND VALIDATION ERROR (WP, BP, ETC.)
============================================================================= */
add_filter('registration_errors', function($errors){
    if (!bzj_can_register()) $errors->add('bzj_block', 'Verification required before account creation.');
    return $errors;
}, 1);

/* ============================================================================
   11: PRE-INSERT FIREWALL — HARD BLOCK BEFORE USER EXISTS (Give, Woo, AffWP, etc.)
============================================================================= */
add_filter('wp_pre_insert_user_data', function($data, $update) {
    if ($update) return $data;
    if (!bzj_is_registration_request()) return $data;
    if (bzj_can_register()) return $data;
    bzj_log('pre_insert_blocked', [
        'user_login' => $data['user_login'] ?? '',
        'user_email' => $data['user_email'] ?? '',
    ]);
    if (bzj_is_async_request()) {
        while (ob_get_level()) ob_end_clean();
        nocache_headers();
        wp_send_json_error(['message' => 'Verification required.'], 403);
    }
    wp_die('Verification required.', 'Registration Blocked', ['response' => 403]);
}, 1, 2);

/* ============================================================================
   12: FAILSAFE POST-INSERT ENFORCER (PLUGINS/WEIRD FLOWS)
============================================================================= */
function bzj_enforce($user_id = null, $context = '') {
    if (bzj_can_register()) return true;
    bzj_log('blocked_registration', [ 'context' => $context, 'user_id' => $user_id ]);
    if ($user_id) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
        bzj_log('deleted_unverified_user', [ 'user_id' => $user_id ]);
    }
    if (bzj_is_async_request()) {
        while (ob_get_level()) ob_end_clean();
        nocache_headers();
        wp_send_json_error([ 'message' => 'Verification required.' ], 403);
    }
    wp_die('Verification required.', 'Registration Blocked', ['response' => 403]);
}
add_action('user_register', function($id){ bzj_enforce($id, bzj_context()); }, 1);
add_action('woocommerce_created_customer', function($id){ bzj_enforce($id, 'woocommerce'); }, 10);
add_action('give_insert_user', function($id){ bzj_enforce($id, 'givewp'); }, 10);
add_action('affwp_register_user', function($id){ bzj_enforce($id, 'affiliatewp'); }, 10);

/* ============================================================================
   13: CHALLENGE UI INJECTION — PLATFORMS, BUTTONS
============================================================================= */
function bzj_render_ui($targetSelector, $statusText = 'Match codes then wait to continue') { ?>
<div class="bzj-registration-challenge" data-target="<?php echo esc_attr($targetSelector); ?>"
     style="margin-top:10px;padding:6px 8px;border:1.5px solid #e1e1e5;border-radius:7px;text-align:center;">
    <div style="display:flex;align-items:center;gap:10px;justify-content:center;">
        <span class="bzj-code" style="font-family:monospace;font-size:1.2em;font-weight:bold;padding:2px 18px;border:1px solid #d0d1d7;border-radius:5px;background:#fafafd;">-----</span>
        <button type="button" class="bzj-refresh" style="font-size:1.2em;padding:0 7px 1px 7px;margin:0 3px;border-radius:5px;">⟳</button>
        <input type="text" class="bzj-input" placeholder="Type code" maxlength="5" autocomplete="off" style="width:85px;border: 1px solid #d0d1d7;border-radius:5px;padding:3px;">
    </div>
    <div>
        <small class="bzj-status" style="margin-top:3px;color:#737373;"><?php echo esc_html($statusText); ?></small>
    </div>
    <input type="hidden" name="bzj_form_marker" value="1">
    <input type="hidden" name="bzj_sso_secret" value="">
    <input type="hidden" name="bzj_context" value="<?php echo esc_attr(bzj_context()); ?>">
</div>
<?php }
// BuddyBoss
add_action('bp_before_registration_submit_buttons', function() {
    bzj_render_ui('#signup-form > div.submit input[type=submit], #signup_submit, #signup-form button[type=submit], button[name=signup_submit]', 'Match codes then wait to create account');
}, 20);
// Give Donation Form
add_action('give_donation_form_before_submit', function() {
    bzj_render_ui('#givewp-donation-form-step-0 > div > button', 'Match codes then wait to submit donation');
}, 20);
// AffiliateWP Register
/*add_action('affwp_register_fields_before_submit', function() {
    bzj_render_ui('#affwp-register-form > fieldset > input.button', 'Match codes then wait to register');
}, 20); */
// WooCommerce checkout/register
add_action('woocommerce_after_checkout_registration_form', function() {
    bzj_render_ui('button.wc-block-components-checkout-place-order-button', 'Match codes then wait to place order');
}, 20);

/* ============================================================================
   14: ENFORCED "DISABLED" STYLES FOR ALL BUTTONS
============================================================================= */
add_action('wp_head', function() {
?>
<style>
.bzj-registration-challenge input.bzj-input { margin-bottom: 0 !important; }
button.disabled,
input.disabled,
.wc-block-components-button[disabled],
.bzj-registration-challenge .btn[disabled] {
    background-color: #E3E6ED !important;
    border: 1px solid #D4D9E2 !important;
    opacity: 0.85;
    cursor: not-allowed;
}
</style>
<?php
});

/* ============================================================================
   15: JS — ROBUST BUTTON GATE + EFFICIENT MUTATION OBSERVER
============================================================================= */
add_action('wp_footer', function() { ?>
<script>
(function(){
const STYLES = { backgroundColor: "#E3E6ED", border: "1px solid #D4D9E2" };
function applyDisabledStyles(btn) {
    if (!btn) return;
    btn.disabled = true;
    btn.classList.add('disabled');
    btn.style.backgroundColor = STYLES.backgroundColor;
    btn.style.border = STYLES.border;
    btn.style.opacity = 0.85;
}
function removeDisabledStyles(btn) {
    if (!btn) return;
    btn.disabled = false;
    btn.classList.remove('disabled');
    btn.style.backgroundColor = "";
    btn.style.border = "";
    btn.style.opacity = "";
}
function findTargetButton(sel){
    var btn = null;
    sel.split(',').forEach(function(s){
        if (!btn) btn = document.querySelector(s.trim());
    });
    return btn;
}
function observeButton(sel, handler) {
    var btn = findTargetButton(sel);
    if (btn) return handler(btn);
    var observer = new MutationObserver(function() {
        var b = findTargetButton(sel);
        if (b) handler(b);
    });
    observer.observe(document.body, {childList:true, subtree:true});
}
function initBox(box){
    if (box._bzjBound) return; box._bzjBound = true;
    var btnSel = box.dataset.target;
    var codeBox = box.querySelector('.bzj-code');
    var refresh = box.querySelector('.bzj-refresh');
    var input = box.querySelector('.bzj-input');
    var status = box.querySelector('.bzj-status');
    var btn = null, currentCode = '';
    function disable() {
        observeButton(btnSel, function(_btn){
            btn = _btn; applyDisabledStyles(btn);
        });
    }
    function enable() {
        observeButton(btnSel, function(_btn){
            btn = _btn; removeDisabledStyles(btn);
        });
    }
    status.innerText = box.dataset.statusText || 'Match codes then wait to continue';
    status.style.color = '#737373';
    input.value = '';
    input.disabled = true;
    disable();
    async function generate() {
        codeBox.textContent = '.....';
        currentCode = '';
        disable();
        status.innerText = box.dataset.statusText || 'Match codes then wait to continue';
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
                codeBox.textContent = 'ERR'; input.disabled = true;
                status.innerText = 'Could not generate code (reload?)';
                status.style.color = 'red';
            }
        } catch(e) {
            codeBox.textContent = 'ERR'; input.disabled = true;
            status.innerText = 'Could not generate code (reload?)';
            status.style.color = 'red';
        }
        disable();
    }
    async function verify() {
        const val = input.value.trim().toUpperCase();
        if (!val || !currentCode || val.length !== currentCode.length) {
            status.innerText = box.dataset.statusText || 'Match codes then wait to continue';
            status.style.color = '#737373';
            disable();
            return;
        }
        try {
            const res = await fetch('/wp-admin/admin-ajax.php?action=bzj_verify_code',{
              method:'POST',
              headers:{'Content-Type':'application/x-www-form-urlencoded'},
              body:'code='+encodeURIComponent(val)
            });
            const json = await res.json();
            if(json.success) {
                status.innerText = 'Verified! Continue...';
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
    refresh.addEventListener('click', function(){
        generate(); input.value = '';
        status.innerText = box.dataset.statusText || 'Match codes then wait to continue';
        status.style.color = '#737373';
    });
    input.addEventListener('input', verify);
    generate();
}

function bindAll(){
    document.querySelectorAll('.bzj-registration-challenge').forEach(initBox);
}
bindAll();
let bindScheduled = false;
const observer = new MutationObserver(function() {
    if (bindScheduled) return;
    bindScheduled = true;
    requestAnimationFrame(function() {
        bindAll();
        bindScheduled = false;
    });
});
observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
<?php
});

/* ============================================================================
   16: (OPTIONAL) ELEMENTOR EMAIL PREFILL
============================================================================= */
add_action('wp_footer', function() {
    if (function_exists('is_page') && is_page('register')) { ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function getQueryVar(name) {
                let url = new URL(window.location.href); return url.searchParams.get(name) || '';
            }
            var email = getQueryVar('prefill_email');
            if (email) {
                var input = document.querySelector('#signup_email, input[name="signup_email"], input[name="email"]');
                if (input) input.value = email;
            }
        });
        </script>
    <?php }
});