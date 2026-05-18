<?php
/**
 * BZJ Registration Kernel v41 — Unified Registration Event Bus (UREB)
 * - Human code challenge on all registration UIs (BuddyBoss, GiveWP, Woo, AffiliateWP)
 * - Backend only enforces on flows where UI was present (avoids API/CLI pitfalls!)
 * - Logs in rotating files, date-stamped, up to 20 x 512KB.
 * - Button-styling enforced even in React/multi-step forms.
 * - Handles legacy PHP. MutationObserver for React.
 */

if (!defined('ABSPATH')) exit;

/* ============================================================================
   CONFIGURATION
============================================================================ */

define('BZJ_TTL', 20 * MINUTE_IN_SECONDS); // Code timeout
define('BZJ_REG_KNL_LOG_DIR', ABSPATH . '/data/logs/bzj-registration-kernel/');

/* ============================================================================
   SSO SECRET LOAD
============================================================================ */

if (!defined('BZJ_SSO_SECRET')) {
    if (file_exists(ABSPATH . '/shared/db_helpers.php')) require_once ABSPATH . '/shared/db_helpers.php';
    define('BZJ_SSO_SECRET', defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: 'missing-secret'));
}

/* ============================================================================
   LOGGING: ROTATING, SANITIZED, MAX 20 FILES, 512KB EACH
============================================================================ */

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
   CONTEXT DETECTION (PLATFORM)
============================================================================ */

function bzj_context() {
    if (function_exists('bp_is_register_page') && bp_is_register_page()) return 'buddyboss';
    if (!empty($_POST['give-form-id']) || (strpos($_SERVER['REQUEST_URI'] ?? '', 'give') !== false)) return 'givewp';
    if (!empty($_POST['affwp_register_nonce'])) return 'affiliatewp';
    if (!empty($_POST['woocommerce-register-nonce']) || !empty($_POST['createaccount']) || (function_exists('is_checkout') && is_checkout())) return 'woocommerce';
    return 'unknown';
}

/* ============================================================================
   CHALLENGE STATE, FINGERPRINT, UTILITIES
============================================================================ */

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
   SAFELY RESET CHALLENGE — ONLY ON GET ON REGISTRATION UI
============================================================================ */

add_action('template_redirect', function () {
    if (!is_user_logged_in() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && bzj_should_inject_ui()) {
        bzj_set_state(['verified' => false, 'context' => bzj_context()]);
        delete_transient("bzj_code_" . bzj_fp());
    }
}, 1);

function bzj_should_inject_ui() {
    // Also allow on registration page loads for all supported forms
    return (
        (function_exists('bp_is_register_page') && bp_is_register_page()) ||
        (function_exists('is_checkout') && is_checkout()) ||
        (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], 'give') !== false || strpos($_SERVER['REQUEST_URI'], 'affiliate-area') !== false))
    );
}

/* ============================================================================
   AJAX: CODE GENERATION / VERIFICATION
============================================================================ */

add_action('wp_ajax_nopriv_bzj_generate_code', function () {
    $fp = bzj_fp();
    $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    set_transient("bzj_code_$fp", $code, BZJ_TTL);
    bzj_set_state(['verified' => false, 'context' => bzj_context()]);
    bzj_log('code_generated', ['code' => $code]);
    wp_send_json_success(['code' => $code]);
});
add_action('wp_ajax_nopriv_bzj_verify_code', function () {
    $fp = bzj_fp();
    $input = strtoupper(trim($_POST['code'] ?? ''));
    $stored = get_transient("bzj_code_$fp");
    if (!$stored || $input !== $stored) {
        bzj_log('verify_failed', compact('input', 'stored'));
        wp_send_json_error(['msg' => 'invalid']);
    }
    bzj_set_state(['verified' => true, 'context' => bzj_context()]);
    bzj_log('verify_success', ['code' => $input]);
    wp_send_json_success(['msg' => 'ok']);
});

/* ============================================================================
   BACKEND ENFORCEMENT — ONLY IF MARKER PRESENT (UI shown)
============================================================================ */

function bzj_can_register() {
    // Main fix: if marker NOT present, do not enforce (API/CLI flows safe, UI flows gated)
    if (empty($_POST['bzj_form_marker'])) return true;

    $state = bzj_state();
    if (!empty($state['verified'])) return true;

    if (!empty($_POST['bzj_sso_secret']) && hash_equals($_POST['bzj_sso_secret'], BZJ_SSO_SECRET)) {
        return true;
    }

    return false;
}

// Unified enforcement, triggers for all known registration events
function bzj_enforce($user_id = null, $context = '') {
    if (bzj_can_register()) return true;

    bzj_log('blocked_registration', [
        'context' => $context,
        'user_id' => $user_id,
        'post' => $_POST,
    ]);
    if ($user_id) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
    }
    return false;
}
add_action('user_register', function($id){ bzj_enforce($id, bzj_context()); }, 1);
add_action('woocommerce_created_customer', function($id){ bzj_enforce($id, 'woocommerce'); }, 10);
add_action('give_insert_user', function($id){ bzj_enforce($id, 'givewp'); }, 10);
add_action('affwp_register_user', function($id){ bzj_enforce($id, 'affiliatewp'); }, 10);

add_filter('registration_errors', function($errors){
    if (!bzj_can_register()) $errors->add('bzj_block', 'Verification required before account creation.');
    return $errors;
}, 1);

/* ============================================================================
   CHALLENGE UI INJECTION — PLATFORM-SPECIFIC, ALWAYS INCLUDES MARKER
============================================================================ */

function bzj_render_ui($targetSelector, $statusText = 'Match codes to continue') { ?>
<div class="bzj-registration-challenge" data-target="<?php echo esc_attr($targetSelector); ?>"
     style="margin-top:10px;padding:6px 8px;border:1.5px solid #e1e1e5;border-radius:7px;text-align:center;">
    <div style="display:flex;align-items:center;gap:10px;justify-content:center;">
        <span class="bzj-code"
              style="font-family:monospace;font-size:1.2em;font-weight:bold;padding:2px 18px;border:1px solid #d0d1d7;border-radius:5px;background:#fafafd;">-----</span>
        <button type="button" class="bzj-refresh"
                style="font-size:1.2em;padding:0 7px 1px 7px;margin:0 3px;border-radius:5px;">⟳</button>
        <input type="text" class="bzj-input"
               placeholder="Type code" maxlength="5" autocomplete="off"
               style="width:85px;border: 1px solid #d0d1d7;border-radius:5px;padding:3px;">
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
    bzj_render_ui('#signup-form > div.submit input[type=submit], #signup_submit, #signup-form button[type=submit], button[name=signup_submit]', 'Match codes to create account');
}, 20);

// Give Donation Form
add_action('give_donation_form_before_submit', function() {
    bzj_render_ui('#givewp-donation-form-step-0 > div > button', 'Match codes to submit donation');
}, 20);

// AffiliateWP Register
add_action('affwp_register_fields_before_submit', function() {
    bzj_render_ui('#affwp-register-form > fieldset > input.button', 'Match codes to register');
}, 20);

// WooCommerce checkout/register (mostly block theme, React)
add_action('woocommerce_after_checkout_registration_form', function() {
    bzj_render_ui('div.wc-block-components-sidebar-layout.wc-block-checkout.is-large > div.wc-block-components-main.wc-block-checkout__main.wp-block-woocommerce-checkout-fields-block > form > div.wc-block-checkout__actions.wp-block-woocommerce-checkout-actions-block > div.wc-block-checkout__actions_row > button, #post-50 > div > div > div.wc-block-components-sidebar-layout.wc-block-checkout.is-large > div.wc-block-components-main.wc-block-checkout__main.wp-block-woocommerce-checkout-fields-block > form > div.wc-block-checkout__actions.wp-block-woocommerce-checkout-actions-block > div.wc-block-checkout__actions_row > button > div, #post-50 > div > div > div.wc-block-components-sidebar-layout.wc-block-checkout.is-large > div.wc-block-components-main.wc-block-checkout__main.wp-block-woocommerce-checkout-fields-block > form > div.wc-block-checkout__actions.wp-block-woocommerce-checkout-actions-block > div.wc-block-checkout__actions_row > button > div > div', 'Match codes to place order');
}, 20);

/* ============================================================================
   STYLE ENFORCEMENT FOR DISABLED BUTTONS (PER REQUIREMENT)
============================================================================ */
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
   JS: MUTATIONSAFE, MULTIPLATFORM BUTTON GATE, AJAX CODE HANDLING
============================================================================ */

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
    // Support comma-separated selectors (first match)
    var btn = null;
    sel.split(',').forEach(function(s){
        if (!btn) btn = document.querySelector(s.trim());
    });
    return btn;
}
function observeButton(sel, handler) {
    var btn = findTargetButton(sel);
    if (btn) return handler(btn);
    // React/SPA/step forms: Watch for dynamic load
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
    status.innerText = box.dataset.statusText || 'Match codes to continue';
    status.style.color = '#737373';
    input.value = '';
    input.disabled = true;
    disable();

    async function generate() {
        codeBox.textContent = '.....';
        currentCode = '';
        disable();
        status.innerText = box.dataset.statusText || 'Match codes to continue';
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
            status.innerText = box.dataset.statusText || 'Match codes to continue';
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
        status.innerText = box.dataset.statusText || 'Match codes to continue';
        status.style.color = '#737373';
    });
    input.addEventListener('input', verify);
    generate();
}
// Initial binding and dynamic (mutation observer)
function bindAll(){
    document.querySelectorAll('.bzj-registration-challenge').forEach(initBox);
}
bindAll();
var observer = new MutationObserver(bindAll);
observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
<?php
});

/* ============================================================================
   (Optional) ELEMENTOR EMAIL PREFILL, KEPT FOR COMPAT
============================================================================ */
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