<?php
/**
 * Plugin Name: BZJ GiveWP Email OTP Gate
 * Description: Requires a verified 5-character email OTP for anonymous GiveWP donors at the email-entry step only. Completely scoped to GiveWP forms; does not affect Elementor, BuddyBoss, or general WordPress registration.
 * Version: 1.2.0
 * Author: BuzzJuice
 */

if (!defined('ABSPATH')) {
    exit;
}

defined('BZJ_GIVE_OTP_VERSION') || define('BZJ_GIVE_OTP_VERSION', '1.2.0');
defined('BZJ_GIVE_OTP_TTL') || define('BZJ_GIVE_OTP_TTL', 15 * MINUTE_IN_SECONDS);
defined('BZJ_GIVE_OTP_LENGTH') || define('BZJ_GIVE_OTP_LENGTH', 5);
defined('BZJ_GIVE_OTP_MAX_ATTEMPTS') || define('BZJ_GIVE_OTP_MAX_ATTEMPTS', 5);
defined('BZJ_GIVE_OTP_RESEND_COOLDOWN') || define('BZJ_GIVE_OTP_RESEND_COOLDOWN', 60);
defined('BZJ_GIVE_OTP_STATE_TTL') || define('BZJ_GIVE_OTP_STATE_TTL', 30 * MINUTE_IN_SECONDS);
defined('BZJ_GIVE_OTP_COOKIE') || define('BZJ_GIVE_OTP_COOKIE', 'bzj_give_vtx');
defined('BZJ_GIVE_OTP_NONCE_ACTION') || define('BZJ_GIVE_OTP_NONCE_ACTION', 'bzj_give_email_otp');
defined('BZJ_GIVE_OTP_LOG_DIR') || define('BZJ_GIVE_OTP_LOG_DIR', ABSPATH . '/data/logs/bzj-registration-kernel/');
defined('BZJ_GIVE_OTP_FORM_IDS') || define('BZJ_GIVE_OTP_FORM_IDS', '');

/* ==========================================================================
 * 1. BASIC UTILITIES
 * ========================================================================== */

function bzj_give_otp_normalize_email($email) {
    $email = sanitize_email((string) $email);
    return ($email && is_email($email)) ? strtolower(trim($email)) : '';
}

function bzj_give_otp_email_hash($email) {
    $email = bzj_give_otp_normalize_email($email);
    return $email ? hash('sha256', $email) : '';
}

function bzj_give_otp_hash($otp) {
    return hash_hmac('sha256', strtoupper(trim((string) $otp)), wp_salt('auth'));
}

function bzj_give_otp_client_ip() {
    return isset($_SERVER['REMOTE_ADDR'])
        ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
        : '';
}

function bzj_give_otp_log($event, $data = array()) {
    if (!file_exists(BZJ_GIVE_OTP_LOG_DIR)) {
        wp_mkdir_p(BZJ_GIVE_OTP_LOG_DIR);
    }

    $payload = array(
        'ts'   => gmdate('c'),
        'ip'   => bzj_give_otp_client_ip(),
        'ua'   => isset($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
            : '',
        'type' => 'give_' . sanitize_key($event),
        'data' => is_array($data) ? $data : array(),
    );

    @file_put_contents(
        BZJ_GIVE_OTP_LOG_DIR . 'bzj-give-email-otp.log',
        wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n---\n",
        FILE_APPEND | LOCK_EX
    );
}

function bzj_give_otp_json_error($message, $status = 400, $extra = array()) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    nocache_headers();
    wp_send_json_error(array_merge(array('message' => $message), $extra), $status);
}

function bzj_give_otp_json_success($data = array()) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    nocache_headers();
    wp_send_json_success($data);
}

/* ==========================================================================
 * 2. GIVEWP FORM / REQUEST SCOPE
 * ========================================================================== */

function bzj_give_otp_allowed_form($form_id) {
    $form_id = absint($form_id);
    if (!$form_id) {
        return false;
    }

    $configured = BZJ_GIVE_OTP_FORM_IDS;

    if ($configured === '' || $configured === array()) {
        return true;
    }

    if (is_string($configured)) {
        $configured = array_filter(
            array_map('absint', preg_split('/\s*,\s*/', $configured))
        );
    }

    return in_array($form_id, $configured, true);
}

function bzj_give_otp_request_form_id() {
    foreach (array('give-form-id', 'form_id', 'formId', 'form-id') as $key) {
        if (isset($_POST[$key])) {
            $id = absint(wp_unslash($_POST[$key]));
            if ($id > 0) {
                return $id;
            }
        }
    }
    return 0;
}

function bzj_give_otp_request_email() {
    foreach (array('give_email', 'give_user_email', 'donor_email', 'email') as $key) {
        if (isset($_POST[$key])) {
            $email = bzj_give_otp_normalize_email(wp_unslash($_POST[$key]));
            if ($email) {
                return $email;
            }
        }
    }
    return '';
}

/**
 * Determine whether the current server request is actually GiveWP-related.
 * Does NOT rely on generic URL patterns like "donate" or "donor".
 */
function bzj_give_otp_is_give_request() {
    if (!empty($_POST['give-form-id'])) {
        return true;
    }
    if (!empty($_POST['give_action'])) {
        return true;
    }
    if (!empty($_POST['give-gateway'])) {
        return true;
    }
    if (!empty($_POST['give_email']) || !empty($_POST['give_user_email'])) {
        return true;
    }

    $route = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $route = is_string($route) ? $route : '';

    return (bool) preg_match('~/(?:wp-json/)?(?:givewp|give)/~i', $route);
}

/* ==========================================================================
 * 3. BROWSER TRANSACTION STATE
 * ========================================================================== */

function bzj_give_otp_new_transaction_id() {
    try {
        return bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        return hash('sha256', uniqid((string) wp_rand(), true) . microtime(true));
    }
}

function bzj_give_otp_valid_transaction_id($value) {
    return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
}

function bzj_give_otp_set_cookie($transaction_id) {
    if (!bzj_give_otp_valid_transaction_id($transaction_id)) {
        return false;
    }

    $secure = is_ssl();
    $path   = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';

    if (PHP_VERSION_ID >= 70300) {
        return setcookie(
            BZJ_GIVE_OTP_COOKIE,
            $transaction_id,
            array(
                'expires'  => time() + BZJ_GIVE_OTP_STATE_TTL,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );
    }

    return setcookie(
        BZJ_GIVE_OTP_COOKIE,
        $transaction_id,
        time() + BZJ_GIVE_OTP_STATE_TTL,
        $path,
        $domain,
        $secure,
        true
    );
}

function bzj_give_otp_transaction_id() {
    if (empty($_COOKIE[BZJ_GIVE_OTP_COOKIE])) {
        return '';
    }

    $value = wp_unslash($_COOKIE[BZJ_GIVE_OTP_COOKIE]);

    return bzj_give_otp_valid_transaction_id($value) ? $value : '';
}

function bzj_give_otp_ensure_transaction() {
    $id = bzj_give_otp_transaction_id();

    if ($id) {
        return $id;
    }

    $id = bzj_give_otp_new_transaction_id();
    bzj_give_otp_set_cookie($id);
    $_COOKIE[BZJ_GIVE_OTP_COOKIE] = $id;

    return $id;
}

function bzj_give_otp_state_key($transaction_id) {
    return 'bzj_give_v_' . hash('sha256', $transaction_id);
}

function bzj_give_otp_get_state() {
    $id = bzj_give_otp_transaction_id();

    if (!$id) {
        return false;
    }

    $state = get_transient(bzj_give_otp_state_key($id));

    return is_array($state) ? $state : false;
}

function bzj_give_otp_set_state($transaction_id, $state) {
    if (!bzj_give_otp_valid_transaction_id($transaction_id)) {
        return false;
    }

    return set_transient(
        bzj_give_otp_state_key($transaction_id),
        $state,
        BZJ_GIVE_OTP_STATE_TTL
    );
}

function bzj_give_otp_delete_state() {
    $id = bzj_give_otp_transaction_id();

    if ($id) {
        delete_transient(bzj_give_otp_state_key($id));
    }
}

function bzj_give_otp_state_verified_for($state, $email, $form_id) {
    if (!is_array($state) || empty($state['verified']) || empty($state['email_hash'])) {
        return false;
    }

    if (!empty($state['verified_at'])) {
        $age = time() - (int) $state['verified_at'];
        if ($age < 0 || $age > BZJ_GIVE_OTP_STATE_TTL) {
            return false;
        }
    }

    $email = bzj_give_otp_normalize_email($email);

    if (!$email || !hash_equals($state['email_hash'], bzj_give_otp_email_hash($email))) {
        return false;
    }

    if ($form_id > 0 && (empty($state['form_id']) || (int) $state['form_id'] !== (int) $form_id)) {
        return false;
    }

    return true;
}

/* ==========================================================================
 * 4. RATE LIMITING
 * ========================================================================== */

function bzj_give_otp_ip_key() {
    return 'bzj_give_ip_' . substr(
        hash('sha256', bzj_give_otp_client_ip() . '|' . wp_salt('auth')),
        0,
        32
    );
}

function bzj_give_otp_email_key($email) {
    return 'bzj_give_em_' . substr(bzj_give_otp_email_hash($email), 0, 32);
}

function bzj_give_otp_send_counts_ok($email) {
    return (int) get_transient(bzj_give_otp_ip_key()) < 20
        && (int) get_transient(bzj_give_otp_email_key($email)) < 5;
}

function bzj_give_otp_record_send($email) {
    $ip = bzj_give_otp_ip_key();
    $em = bzj_give_otp_email_key($email);

    set_transient($ip, (int) get_transient($ip) + 1, HOUR_IN_SECONDS);
    set_transient($em, (int) get_transient($em) + 1, HOUR_IN_SECONDS);
}

function bzj_give_otp_cooldown_remaining($state) {
    if (!is_array($state) || empty($state['last_sent_at'])) {
        return 0;
    }

    return max(0, BZJ_GIVE_OTP_RESEND_COOLDOWN - (time() - (int) $state['last_sent_at']));
}

/* ==========================================================================
 * 5. OTP GENERATION
 *
 * Deliberately excludes visually ambiguous characters:
 * I, L, O, 0, 1 to avoid user confusion.
 * ========================================================================== */

function bzj_give_otp_generate() {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    try {
        $bytes = random_bytes(BZJ_GIVE_OTP_LENGTH);
        $otp   = '';

        for ($i = 0; $i < BZJ_GIVE_OTP_LENGTH; $i++) {
            $otp .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
        }

        return $otp;

    } catch (Throwable $e) {

        $otp = '';

        for ($i = 0; $i < BZJ_GIVE_OTP_LENGTH; $i++) {
            $otp .= $alphabet[wp_rand(0, strlen($alphabet) - 1)];
        }

        return $otp;
    }
}

/* ==========================================================================
 * 6. AJAX: REQUEST / RESEND OTP
 * ========================================================================== */

function bzj_give_otp_request_handler() {
    check_ajax_referer(BZJ_GIVE_OTP_NONCE_ACTION, 'nonce');

    $email   = bzj_give_otp_normalize_email(wp_unslash($_POST['email'] ?? ''));
    $form_id = absint(wp_unslash($_POST['form_id'] ?? 0));

    if (!$email) {
        bzj_give_otp_json_error('Please enter a valid email address.', 400);
    }

    if ($form_id && !bzj_give_otp_allowed_form($form_id)) {
        bzj_give_otp_json_error(
            'Email verification is not available for this donation form.',
            403
        );
    }

    $transaction_id = bzj_give_otp_ensure_transaction();
    $state          = bzj_give_otp_get_state();
    $cooldown       = bzj_give_otp_cooldown_remaining($state);

    if ($cooldown > 0) {
        bzj_give_otp_json_error(
            sprintf('Please wait %d seconds before requesting another code.', $cooldown),
            429,
            array('cooldown' => $cooldown)
        );
    }

    if (!bzj_give_otp_send_counts_ok($email)) {
        bzj_give_otp_log('otp_rate_limited', array('form_id' => $form_id));
        bzj_give_otp_json_error(
            'Too many verification requests. Please try again later.',
            429
        );
    }

    $otp = bzj_give_otp_generate();
    $now = time();

    $new_state = array(
        'version'        => 2,
        'transaction'    => $transaction_id,
        'form_id'        => $form_id,
        'email_hash'     => bzj_give_otp_email_hash($email),
        'otp_hash'       => bzj_give_otp_hash($otp),
        'otp_expires_at' => $now + BZJ_GIVE_OTP_TTL,
        'last_sent_at'   => $now,
        'attempts'       => 0,
        'verified'       => false,
        'verified_at'    => 0,
        'created_at'     => $now,
    );

    if (!bzj_give_otp_set_state($transaction_id, $new_state)) {
        bzj_give_otp_json_error(
            'Unable to start email verification. Please try again.',
            500
        );
    }

    $subject = 'BuzzJuice Donation Email Verification Code';
    $message = sprintf(
        "Your BuzzJuice Donation email verification code is:\n\n%s\n\nThis 5-character code expires in %d minutes.\n\nIf you did not request this code, you can ignore this email.",
        $otp,
        (int) (BZJ_GIVE_OTP_TTL / MINUTE_IN_SECONDS)
    );

    if (!wp_mail($email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'))) {
        bzj_give_otp_delete_state();
        bzj_give_otp_log('otp_email_failed', array('form_id' => $form_id));
        bzj_give_otp_json_error(
            'We could not send the verification email. Please try again.',
            500
        );
    }

    bzj_give_otp_record_send($email);
    bzj_give_otp_log('otp_sent', array('form_id' => $form_id));

    bzj_give_otp_json_success(array(
        'message'  => 'Verification code sent.',
        'expires'  => BZJ_GIVE_OTP_TTL,
        'cooldown' => BZJ_GIVE_OTP_RESEND_COOLDOWN,
    ));
}

add_action('wp_ajax_nopriv_bzj_give_request_otp', 'bzj_give_otp_request_handler');

/* ==========================================================================
 * 7. AJAX: VERIFY OTP
 * ========================================================================== */

function bzj_give_otp_verify_handler() {
    check_ajax_referer(BZJ_GIVE_OTP_NONCE_ACTION, 'nonce');

    $code    = strtoupper(sanitize_text_field(wp_unslash($_POST['code'] ?? '')));
    $code    = preg_replace('/[^A-Z0-9]/', '', $code);
    $form_id = absint(wp_unslash($_POST['form_id'] ?? 0));

    if (strlen($code) !== BZJ_GIVE_OTP_LENGTH) {
        bzj_give_otp_json_error('Enter the complete verification code.', 400);
    }

    $transaction_id = bzj_give_otp_transaction_id();
    $state          = bzj_give_otp_get_state();

    if (!$transaction_id || !$state) {
        bzj_give_otp_json_error(
            'Your verification session expired. Request a new code.',
            403,
            array('reset' => true)
        );
    }

    if ($form_id > 0 && !empty($state['form_id']) && (int) $state['form_id'] !== $form_id) {
        bzj_give_otp_delete_state();
        bzj_give_otp_json_error(
            'Your verification session belongs to a different donation form. Request a new code.',
            403,
            array('reset' => true)
        );
    }

    if (empty($state['otp_hash']) || empty($state['otp_expires_at'])) {
        bzj_give_otp_json_error(
            'Please request a new verification code.',
            403,
            array('reset' => true)
        );
    }

    if (time() > (int) $state['otp_expires_at']) {
        bzj_give_otp_delete_state();
        bzj_give_otp_json_error(
            'This verification code has expired. Request a new code.',
            403,
            array('reset' => true)
        );
    }

    $attempts = (int) ($state['attempts'] ?? 0);

    if ($attempts >= BZJ_GIVE_OTP_MAX_ATTEMPTS) {
        bzj_give_otp_delete_state();
        bzj_give_otp_json_error(
            'Too many incorrect attempts. Request a new code.',
            403,
            array('reset' => true)
        );
    }

    if (!hash_equals($state['otp_hash'], bzj_give_otp_hash($code))) {
        $state['attempts'] = $attempts + 1;
        bzj_give_otp_set_state($transaction_id, $state);

        $remaining = max(0, BZJ_GIVE_OTP_MAX_ATTEMPTS - $state['attempts']);

        bzj_give_otp_log(
            'otp_verification_failed',
            array('attempts' => $state['attempts'], 'remaining' => $remaining)
        );

        bzj_give_otp_json_error(
            'Incorrect verification code.',
            403,
            array('attempts_remaining' => $remaining)
        );
    }

    $state['verified']       = true;
    $state['verified_at']    = time();
    $state['otp_hash']       = '';
    $state['otp_expires_at'] = 0;
    $state['attempts']       = 0;

    bzj_give_otp_set_state($transaction_id, $state);

    bzj_give_otp_log('otp_verified', array('form_id' => $state['form_id'] ?? 0));

    bzj_give_otp_json_success(array(
        'message' => 'Email verified successfully.',
        'form_id' => (int) ($state['form_id'] ?? 0),
    ));
}

add_action('wp_ajax_nopriv_bzj_give_verify_otp', 'bzj_give_otp_verify_handler');

/* ==========================================================================
 * 8. AUTHORITATIVE SERVER-SIDE GIVEWP DONATION GATE
 * ========================================================================== */

function bzj_give_otp_validate_donation() {
    if (!bzj_give_otp_is_give_request() || is_user_logged_in()) {
        return;
    }

    $form_id = bzj_give_otp_request_form_id();

    if ($form_id && !bzj_give_otp_allowed_form($form_id)) {
        return;
    }

    $email = bzj_give_otp_request_email();

    if (!$email) {
        if (function_exists('give_set_error')) {
            give_set_error('bzj_give_email_verification', 'Please enter a valid email address.');
        }
        bzj_give_otp_log('donation_blocked_invalid_email', array('form_id' => $form_id));
        return;
    }

    $state = bzj_give_otp_get_state();

    if (!$state || !bzj_give_otp_state_verified_for($state, $email, $form_id)) {
        if (function_exists('give_set_error')) {
            give_set_error(
                'bzj_give_email_verification',
                'Please verify your email address before continuing with your donation.'
            );
        }
        bzj_give_otp_log('donation_blocked_unverified', array('form_id' => $form_id));
        return;
    }

    bzj_give_otp_log('donation_authorized', array('form_id' => $form_id));
}

add_action('give_checkout_error_checks', 'bzj_give_otp_validate_donation', 5, 1);

/* ==========================================================================
 * 9. GIVEWP ACCOUNT CREATION GATE
 *
 * This is deliberately GiveWP-specific and never affects:
 * - BuddyBoss registration
 * - Elementor forms
 * - Standard WordPress registration
 * - WooCommerce checkout
 * ========================================================================== */

function bzj_give_otp_validate_registration() {
    if (is_user_logged_in()) {
        return;
    }

    $email = bzj_give_otp_normalize_email(wp_unslash($_POST['give_user_email'] ?? ''));

    if (!$email) {
        $email = bzj_give_otp_request_email();
    }

    $form_id = bzj_give_otp_request_form_id();
    $state   = bzj_give_otp_get_state();

    if (!$email || !bzj_give_otp_state_verified_for($state, $email, $form_id)) {
        if (function_exists('give_set_error')) {
            give_set_error(
                'bzj_give_email_verification',
                'Please verify your email address before creating your account.'
            );
        }
        bzj_give_otp_log('give_registration_blocked', array('form_id' => $form_id));
        return;
    }

    bzj_give_otp_log('give_registration_authorized', array('form_id' => $form_id));
}

add_action('give_pre_process_register_form', 'bzj_give_otp_validate_registration', 1);

/* ==========================================================================
 * 10. PUBLIC BRIDGE FOR bzj-registration-kernel.php
 * ========================================================================== */

/**
 * Used by bzj-registration-kernel.php.
 *
 * IMPORTANT: This does not modify the kernel's normal BuddyBoss/AffiliateWP state.
 * It only authorizes a GiveWP registration when the separate Give OTP
 * transaction state has been verified for the exact email/form combination.
 */
function bzj_give_email_otp_authorizes_registration() {
    if (is_user_logged_in()) {
        return true;
    }

    if (!bzj_give_otp_is_give_request()) {
        return false;
    }

    $form_id = bzj_give_otp_request_form_id();

    if ($form_id && !bzj_give_otp_allowed_form($form_id)) {
        return false;
    }

    $email = bzj_give_otp_request_email();

    return $email && bzj_give_otp_state_verified_for(
        bzj_give_otp_get_state(),
        $email,
        $form_id
    );
}

/* ==========================================================================
 * 11. POST-INSERT ANOMALY LOGGER
 *
 * Normally the kernel's pre-insert firewall prevents an unverified user.
 * This hook deliberately does NOT delete a user because the existing kernel
 * owns the post-insert deletion policy.
 * ========================================================================== */

function bzj_give_otp_log_inserted_user($user_id) {
    if (!$user_id || is_user_logged_in() || !bzj_give_otp_is_give_request()) {
        return;
    }

    $form_id = bzj_give_otp_request_form_id();
    $user    = get_userdata($user_id);

    if (!$user) {
        return;
    }

    $email = bzj_give_otp_normalize_email($user->user_email);
    $state = bzj_give_otp_get_state();

    if (bzj_give_otp_state_verified_for($state, $email, $form_id)) {
        bzj_give_otp_log(
            'inserted_user_authorized',
            array('user_id' => (int) $user_id, 'form_id' => $form_id)
        );
    } else {
        bzj_give_otp_log(
            'inserted_user_without_verified_otp',
            array('user_id' => (int) $user_id, 'form_id' => $form_id)
        );
    }
}

add_action('give_insert_user', 'bzj_give_otp_log_inserted_user', 5, 1);

/* ==========================================================================
 * 12. FRONTEND CONFIGURATION
 *
 * CRITICAL SCOPING RULES:
 *
 * 1. Roots: ONLY GiveWP identifiers. No generic page elements.
 * 2. Buttons: Either Give-specific classes OR constrained within a Give root.
 * 3. Emails: ONLY queried INSIDE a Give root. Never page-wide.
 * 4. Step-3 exclusion: No OTP UI or blocking on #givewp-donation-form-step-3.
 *
 * There is intentionally NO generic button[type="submit"] or input[type="email"]
 * at the page level.
 * ========================================================================== */

add_action(
    'wp_head',
    function() {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }

        $config = array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce(BZJ_GIVE_OTP_NONCE_ACTION),
            'otpLength' => BZJ_GIVE_OTP_LENGTH,
            'cooldown'  => BZJ_GIVE_OTP_RESEND_COOLDOWN,
            'selectors' => array(
                /*
                 * These are the ONLY elements allowed to establish a GiveWP root.
                 * If none of these match, the OTP gate does not apply.
                 */
                'roots' => array(
                    '#give-next-gen',
                    '[data-givewp-form]',
                    '.givewp-donation-form',
                    'form.give-form',
                ),

                /*
                 * All button selectors are either Give-specific classes
                 * OR constrained to a Give parent.
                 *
                 * EXCLUDED: #givewp-donation-form-step-3 button
                 * The "Contribute Now" button in step 3 is never blocked.
                 */
                'buttons' => array(
                    '#give-next-gen > button:not(#givewp-donation-form-step-3 button)',
                    'button.givewp-donation-form__steps-button-next',
                    '.givewp-donation-form button[type="submit"]:not(#givewp-donation-form-step-3 button)',
                    '.give-form button[type="submit"]:not(#givewp-donation-form-step-3 button)',
                    '[data-givewp-form] button[type="submit"]:not(#givewp-donation-form-step-3 button)',
                ),

                /*
                 * Email selectors are ONLY evaluated within a Give root.
                 * These are never queried at the document level.
                 */
                'emails' => array(
                    'input[name="give_email"]',
                    'input[name="donor_email"]',
                    'input[type="email"]',
                ),
            ),
        );

        echo '<script>window.BZJGiveOTP=' .
            wp_json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
            ';</script>';
    },
    1
);

/* ==========================================================================
 * 13. FRONTEND CSS + JAVASCRIPT
 * ========================================================================== */

add_action(
    'wp_footer',
    function() {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        ?>
<style id="bzj-give-email-otp-style">
.bzj-give-email-verification {
    margin: 14px 0;
    padding: 12px;
    border: 1px solid #d9dce3;
    border-radius: 8px;
    background: #fafbfd;
    width: 100%;
    box-sizing: border-box;
}

.bzj-give-email-verification__row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.bzj-give-email-verification__code {
    width: 110px !important;
    min-width: 90px;
    text-transform: uppercase;
    font-family: monospace;
    font-weight: 700;
    letter-spacing: .12em;
    text-align: center;
}

.bzj-give-email-verification__resend,
.bzj-give-email-verification__reset {
    white-space: nowrap;
}

.bzj-give-email-verification__status {
    display: block;
    margin-top: 7px;
    font-size: .9em;
}

.bzj-give-email-verification__status.is-success {
    color: #237a35;
}

.bzj-give-email-verification__status.is-error {
    color: #b42318;
}

.bzj-give-email-verification__status.is-neutral {
    color: #737373;
}

.bzj-give-email-verification__hint {
    display: block;
    margin-bottom: 8px;
    font-size: .9em;
    color: #555;
}

.bzj-give-email-verification__email--locked {
    background: #eee !important;
    color: #666 !important;
    cursor: not-allowed;
}

.bzj-give-otp-disabled {
    opacity: .65 !important;
    cursor: not-allowed !important;
}
</style>

<script id="bzj-give-email-otp-script">
(function() {
    'use strict';

    const CONFIG = window.BZJGiveOTP || null;

    if (!CONFIG) {
        return;
    }

    const ROOT_SELECTORS   = CONFIG.selectors.roots || [];
    const BUTTON_SELECTORS = CONFIG.selectors.buttons || [];
    const EMAIL_SELECTORS  = CONFIG.selectors.emails || [];

    const instances = new WeakMap();
    const observed  = new WeakSet();

    /* ====================================================================
     * Query and discovery helpers
     * ==================================================================== */

    function queryAll(root, selectors) {
        const found = [];

        if (!root || !root.querySelectorAll) {
            return found;
        }

        selectors.forEach(function(selector) {
            try {
                root.querySelectorAll(selector).forEach(function(node) {
                    if (!found.includes(node)) {
                        found.push(node);
                    }
                });
            } catch (e) {
                /* Ignore invalid selectors */
            }
        });

        return found;
    }

    function firstVisible(root, selectors) {
        const nodes = queryAll(root, selectors);
        return nodes.find(function(n) {
            return n.offsetParent !== null;
        }) || nodes[0] || null;
    }

    function getDocuments() {
        const docs = [document];

        document.querySelectorAll('iframe').forEach(function(frame) {
            try {
                if (frame.contentDocument && !docs.includes(frame.contentDocument)) {
                    docs.push(frame.contentDocument);
                }
            } catch (e) {
                /* Cross-origin frames cannot be inspected */
            }
        });

        return docs;
    }

    /* ====================================================================
     * GiveWP ROOT DISCOVERY
     *
     * THIS IS THE SECURITY BOUNDARY.
     *
     * We start by finding GiveWP roots, not by finding every button
     * on the page. If a button is not inside a GiveWP root, it is ignored.
     * ==================================================================== */

    function getGiveRoots(doc) {
        return queryAll(doc, ROOT_SELECTORS);
    }

    function buttonBelongsToRoot(button, root) {
        return !!(
            button &&
            root &&
            (button === root || root.contains(button))
        );
    }

    function isInStep3(button) {
        /*
         * Step 3 has the ID givewp-donation-form-step-3.
         * If the button is inside this element, skip it entirely.
         */
        if (!button) {
            return false;
        }

        let node = button;
        while (node && node.parentElement) {
            if (node.id === 'givewp-donation-form-step-3') {
                return true;
            }
            node = node.parentElement;
        }

        return false;
    }

    function findGiveButton(root) {
        const buttons = queryAll(root, BUTTON_SELECTORS);

        /*
         * Find the first visible button that is NOT in step-3.
         */
        for (let i = 0; i < buttons.length; i++) {
            if (buttons[i].offsetParent !== null && !isInStep3(buttons[i])) {
                return buttons[i];
            }
        }

        return null;
    }

    function findGiveEmail(root) {
        return firstVisible(root, EMAIL_SELECTORS);
    }

    function findFormId(root, email, button) {
        const values = [];

        [root, email, button].forEach(function(node) {
            if (!node || !node.getAttribute) {
                return;
            }

            ['data-form-id', 'data-give-form-id', 'data-formid'].forEach(
                function(attribute) {
                    const value = node.getAttribute(attribute);

                    if (/^\d+$/.test(String(value || ''))) {
                        values.push(parseInt(value, 10));
                    }
                }
            );
        });

        const hidden = queryAll(root, [
            'input[name="give-form-id"]',
            'input[name="form_id"]',
            'input[name="formId"]',
            'input[id*="give-form-id" i]',
        ])[0];

        if (hidden && /^\d+$/.test(String(hidden.value || ''))) {
            values.push(parseInt(hidden.value, 10));
        }

        [root.id, email && email.form && email.form.id, button && button.form && button.form.id].forEach(
            function(value) {
                const match = String(value || '').match(/(?:give-form|form)[-_]?(\d+)/i);
                if (match) {
                    values.push(parseInt(match[1], 10));
                }
            }
        );

        return values.find(function(id) {
            return id > 0;
        }) || 0;
    }

    /* ====================================================================
     * Button and email utilities
     * ==================================================================== */

    function setDisabled(button, disabled) {
        if (!button) {
            return;
        }

        button.disabled = !!disabled;
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        button.classList.toggle('bzj-give-otp-disabled', !!disabled);
    }

    function setStatus(instance, message, type) {
        if (!instance.status) {
            return;
        }

        instance.status.textContent = message || '';
        instance.status.className =
            'bzj-give-email-verification__status is-' + (type || 'neutral');
    }

    function normalizeEmail(value) {
        return String(value || '').trim().toLowerCase();
    }

    function validEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizeEmail(value));
    }

    function setEmailValue(input, value) {
        if (!input) {
            return;
        }

        const proto = Object.getPrototypeOf(input);
        const desc  = proto && Object.getOwnPropertyDescriptor(proto, 'value');

        if (desc && desc.set) {
            desc.set.call(input, value);
        } else {
            input.value = value;
        }

        try {
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) {
            /* Ignore errors */
        }
    }

    /* ====================================================================
     * AJAX helpers
     * ==================================================================== */

    function post(action, data) {
        const body = new URLSearchParams();

        body.set('action', action);
        body.set('nonce', CONFIG.nonce);

        Object.keys(data || {}).forEach(function(key) {
            body.set(key, data[key]);
        });

        return fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: body.toString(),
        })
            .then(function(response) {
                return response.json().catch(function() {
                    return {
                        success: false,
                        data: { message: 'Invalid server response.' },
                    };
                });
            });
    }

    /* ====================================================================
     * State management: reset, lock, cooldown
     * ==================================================================== */

    function reset(instance, message) {
        instance.verified      = false;
        instance.verifiedEmail = '';
        instance.requestedEmail = '';
        instance.cooldownUntil = 0;

        if (instance.cooldownTimer) {
            clearInterval(instance.cooldownTimer);
            instance.cooldownTimer = null;
        }

        if (instance.email) {
            instance.email.readOnly = false;
            instance.email.removeAttribute('aria-readonly');
            instance.email.classList.remove('bzj-give-email-verification__email--locked');
            instance.email.style.background = '';
            instance.email.style.color = '';
        }

        if (instance.code) {
            instance.code.value    = '';
            instance.code.disabled = true;
        }

        if (instance.resend) {
            instance.resend.disabled  = false;
            instance.resend.textContent = 'Send OTP';
        }

        setDisabled(instance.button, true);

        setStatus(
            instance,
            message || 'Enter your email and request a verification code.',
            'neutral'
        );
    }

    function lockVerifiedEmail(instance) {
        setEmailValue(instance.email, instance.verifiedEmail);

        instance.email.readOnly = true;
        instance.email.setAttribute('aria-readonly', 'true');
        instance.email.classList.add('bzj-give-email-verification__email--locked');

        if (instance.resend) {
            instance.resend.disabled = true;
            instance.resend.textContent = 'Resend OTP';
        }

        if (instance.code) {
            instance.code.disabled = true;
            instance.code.value    = '';
        }
    }

    function startCooldown(instance, seconds) {
        instance.cooldownUntil = Date.now() + Math.max(0, seconds) * 1000;

        if (instance.cooldownTimer) {
            clearInterval(instance.cooldownTimer);
        }

        function tick() {
            const remaining = Math.max(
                0,
                Math.ceil((instance.cooldownUntil - Date.now()) / 1000)
            );

            if (remaining > 0) {
                instance.resend.disabled    = true;
                instance.resend.textContent = 'Resend (' + remaining + 's)';
            } else {
                clearInterval(instance.cooldownTimer);
                instance.cooldownTimer = null;

                if (!instance.verified) {
                    instance.resend.disabled = false;
                    instance.resend.textContent = 'Resend OTP';
                }
            }
        }

        tick();
        instance.cooldownTimer = setInterval(tick, 500);
    }

    /* ====================================================================
     * OTP request
     * ==================================================================== */

    function requestOtp(instance) {
        const email = normalizeEmail(instance.email.value);

        if (!validEmail(email)) {
            setStatus(instance, 'Enter a valid email address first.', 'error');
            instance.email.focus();
            return;
        }

        const formId = findFormId(instance.root, instance.email, instance.button);

        instance.requestedEmail = email;
        instance.verifiedEmail  = '';
        instance.verified       = false;

        setDisabled(instance.button, true);
        setStatus(instance, 'Sending a verification code to ' + email + '…', 'neutral');

        post('bzj_give_request_otp', { email: email, form_id: formId })
            .then(function(result) {
                const data = result && result.data ? result.data : {};

                if (!result || !result.success) {
                    if (data.reset) {
                        reset(instance, data.message || 'Request a new verification code.');
                    } else {
                        setStatus(
                            instance,
                            data.message || 'Unable to send the verification code.',
                            'error'
                        );
                    }
                    return;
                }

                instance.requestedEmail = email;
                instance.code.disabled  = false;
                instance.code.value     = '';

                setDisabled(instance.button, true);

                setStatus(
                    instance,
                    data.message || 'Verification code sent. Enter it below.',
                    'neutral'
                );

                startCooldown(instance, parseInt(data.cooldown || CONFIG.cooldown, 10));

                instance.code.focus();
            })
            .catch(function() {
                setStatus(
                    instance,
                    'Unable to contact the verification service. Please try again.',
                    'error'
                );
            });
    }

    /* ====================================================================
     * OTP verification
     * ==================================================================== */

    function verifyOtp(instance) {
        const code = String(instance.code.value || '')
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');

        instance.code.value = code;

        setDisabled(instance.button, true);

        if (code.length !== CONFIG.otpLength) {
            if (instance.requestedEmail) {
                setStatus(
                    instance,
                    'Enter the complete 5-character code sent to your email.',
                    'neutral'
                );
            }
            return;
        }

        const email = normalizeEmail(instance.email.value);

        if (!validEmail(email)) {
            reset(instance, 'Enter a valid email address and request a new code.');
            return;
        }

        if (instance.requestedEmail && email !== instance.requestedEmail) {
            instance.code.value       = '';
            instance.code.disabled    = true;
            instance.requestedEmail   = '';

            setDisabled(instance.button, true);

            if (instance.resend) {
                instance.resend.disabled = false;
                instance.resend.textContent = 'Send OTP';
            }

            setStatus(
                instance,
                'Email changed. Request a new verification code for this email.',
                'error'
            );

            return;
        }

        const formId = findFormId(instance.root, instance.email, instance.button);

        setStatus(instance, 'Checking verification code…', 'neutral');

        post('bzj_give_verify_otp', { code: code, form_id: formId })
            .then(function(result) {
                const data = result && result.data ? result.data : {};

                if (!result || !result.success) {
                    if (data.reset) {
                        reset(instance, data.message || 'Request a new verification code.');
                    } else {
                        /*
                         * IMPORTANT: Wrong codes do NOT disable the code field.
                         * The user can attempt again without starting over.
                         */
                        instance.code.value = '';
                        instance.code.focus();

                        setStatus(
                            instance,
                            data.message || 'Incorrect verification code. Try again.',
                            'error'
                        );
                    }
                    return;
                }

                instance.verified      = true;
                instance.verifiedEmail = email;

                setEmailValue(instance.email, email);

                lockVerifiedEmail(instance);
                setDisabled(instance.button, false);

                setStatus(instance, 'Email verified. You can continue.', 'success');
            })
            .catch(function() {
                setStatus(instance, 'Unable to verify the code. Please try again.', 'error');
                setDisabled(instance.button, true);
            });
    }

    /* ====================================================================
     * Build OTP UI
     * ==================================================================== */

    function build(instance) {
        if (!instance.root || !instance.button || !instance.email) {
            return false;
        }

        let wrapper = instance.root.querySelector('.bzj-give-email-verification');

        if (
            wrapper &&
            wrapper.isConnected &&
            wrapper.parentNode === instance.button.parentNode
        ) {
            instance.wrapper = wrapper;
            instance.code    = wrapper.querySelector('.bzj-give-email-verification__code');
            instance.resend  = wrapper.querySelector('.bzj-give-email-verification__resend');
            instance.reset   = wrapper.querySelector('.bzj-give-email-verification__reset');
            instance.status  = wrapper.querySelector('.bzj-give-email-verification__status');
            return true;
        }

        wrapper = instance.email.ownerDocument.createElement('div');
        wrapper.className = 'bzj-give-email-verification';
        wrapper.setAttribute('data-bzj-give-otp', '1');

        const hint = instance.email.ownerDocument.createElement('span');
        hint.className  = 'bzj-give-email-verification__hint';
        hint.textContent = 'Verify your email to continue with your donation.';

        const row = instance.email.ownerDocument.createElement('div');
        row.className = 'bzj-give-email-verification__row';

        const code = instance.email.ownerDocument.createElement('input');
        code.type         = 'text';
        code.className    = 'bzj-give-email-verification__code';
        code.maxLength    = CONFIG.otpLength;
        code.autocomplete = 'one-time-code';
        code.inputMode    = 'text';
        code.placeholder  = 'OTP code';
        code.setAttribute('aria-label', '5-character email verification code');
        code.disabled = true;

        const resend = instance.email.ownerDocument.createElement('button');
        resend.type       = 'button';
        resend.className  = 'bzj-give-email-verification__resend';
        resend.textContent = 'Send OTP';

        const resetButton = instance.email.ownerDocument.createElement('button');
        resetButton.type       = 'button';
        resetButton.className  = 'bzj-give-email-verification__reset';
        resetButton.textContent = 'Reset';

        const status = instance.email.ownerDocument.createElement('span');
        status.className = 'bzj-give-email-verification__status is-neutral';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');

        row.appendChild(code);
        row.appendChild(resend);
        row.appendChild(resetButton);

        wrapper.appendChild(hint);
        wrapper.appendChild(row);
        wrapper.appendChild(status);

        /*
         * Insert OTP UI immediately before the currently active Give button.
         */
        instance.button.parentNode.insertBefore(wrapper, instance.button);

        instance.wrapper = wrapper;
        instance.code    = code;
        instance.resend  = resend;
        instance.reset   = resetButton;
        instance.status  = status;

        resend.addEventListener('click', function() {
            if (!instance.verified) {
                requestOtp(instance);
            }
        });

        resetButton.addEventListener('click', function() {
            reset(instance, 'Enter your email and request a verification code.');
            instance.email.focus();
        });

        code.addEventListener('input', function() {
            verifyOtp(instance);
        });

        instance.email.addEventListener('input', function() {
            const current = normalizeEmail(instance.email.value);

            if (instance.verified && current !== instance.verifiedEmail) {
                reset(instance, 'Email changed. Request a new verification code.');
                return;
            }

            if (instance.requestedEmail && current !== instance.requestedEmail) {
                instance.code.value       = '';
                instance.code.disabled    = true;
                instance.requestedEmail   = '';

                setDisabled(instance.button, true);

                if (instance.resend) {
                    instance.resend.disabled = false;
                    instance.resend.textContent = 'Send OTP';
                }

                setStatus(
                    instance,
                    'Email changed. Request a new verification code for this email.',
                    'error'
                );
            }
        });

        instance.email.addEventListener('change', function() {
            const current = normalizeEmail(instance.email.value);

            if (instance.verified && current !== instance.verifiedEmail) {
                reset(instance, 'Email changed. Request a new verification code.');
            }
        });

        reset(instance, 'Enter your email and request a verification code.');

        return true;
    }

    /* ====================================================================
     * Bind ONE GiveWP root
     * ==================================================================== */

    function bindRoot(root) {
        if (!root || !root.isConnected) {
            return;
        }

        const button = findGiveButton(root);
        const email  = findGiveEmail(root);

        if (!button || !email || !buttonBelongsToRoot(button, root)) {
            return;
        }

        let instance = instances.get(button);

        if (!instance) {
            instance = {
                root: root,
                button: button,
                email: email,
                wrapper: null,
                code: null,
                resend: null,
                reset: null,
                status: null,
                verified: false,
                verifiedEmail: '',
                requestedEmail: '',
                cooldownUntil: 0,
                cooldownTimer: null,
            };

            instances.set(button, instance);
        } else {
            instance.root  = root;
            instance.email = email;
        }

        if (
            !instance.wrapper ||
            !instance.wrapper.isConnected ||
            instance.wrapper.parentNode !== button.parentNode
        ) {
            instance.wrapper = null;
            instance.code    = null;
            instance.resend  = null;
            instance.reset   = null;
            instance.status  = null;

            build(instance);
        }

        /*
         * React can replace the button. A new button is always locked
         * until this instance has independently verified OTP.
         */
        if (!instance.verified) {
            setDisabled(button, true);
        }
    }

    /* ====================================================================
     * Bind only GiveWP roots in a document
     * ==================================================================== */

    function bindDocument(doc) {
        getGiveRoots(doc).forEach(bindRoot);
    }

    /* ====================================================================
     * Observe GiveWP React DOM
     * ==================================================================== */

    function observeDocument(doc) {
        if (!doc || !doc.documentElement) {
            return;
        }

        bindDocument(doc);

        if (observed.has(doc)) {
            return;
        }

        observed.add(doc);

        const observer = new MutationObserver(function() {
            window.requestAnimationFrame(function() {
                bindDocument(doc);
            });
        });

        observer.observe(doc.documentElement, {
            subtree: true,
            childList: true,
        });
    }

    /* ====================================================================
     * Boot
     * ==================================================================== */

    function boot() {
        getDocuments().forEach(observeDocument);

        /*
         * Detect GiveWP iframe creation/replacement.
         */
        const frameObserver = new MutationObserver(function() {
            getDocuments().forEach(observeDocument);
        });

        if (document.documentElement) {
            frameObserver.observe(document.documentElement, {
                subtree: true,
                childList: true,
            });
        }

        /*
         * Give React forms can initialise asynchronously.
         * Perform a short series of scans to catch them.
         */
        let scans = 0;

        const scanTimer = setInterval(function() {
            getDocuments().forEach(bindDocument);

            scans++;

            if (scans >= 30) {
                clearInterval(scanTimer);
            }
        }, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
        <?php
    },
    999
);