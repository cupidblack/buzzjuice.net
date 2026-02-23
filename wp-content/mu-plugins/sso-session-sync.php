<?php
/*
 * BuzzJuice SSO (stateless, unified MU-plugin)
 * - Stateless: signs/verifies all tokens; no PHP sessions, no shadow files
 * - WP login: issues short-lived HMAC token for SSO
 * - WP logout: immediately expires SSO token & chains orchestrator logout
 */

// --- Config ---
if (!defined('BUZZ_SSO_COOKIE'))    define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_SSO_TTL'))       define('BUZZ_SSO_TTL', 900); // 15 minutes
if (!defined('BUZZ_COOKIE_DOMAIN')) define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))     define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_DEBUG_LOG'))     define('BUZZ_DEBUG_LOG', __DIR__ . '/wp_debug_buzz_sso.log');

$__buzz_sso_secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

// --- Utility: HMAC Token ---
function bz_build_one_time_token(array $payload, $secret, $ttl = BUZZ_SSO_TTL) {
    $now = time();
    $payload['iat'] = $now;
    $payload['exp'] = $now + $ttl;
    $json = function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload);
    $sig  = hash_hmac('sha256', $json, $secret, true);
    return rtrim(strtr(base64_encode($json), '+/', '-_'), '=') . '.' .
           rtrim(strtr(base64_encode($sig),  '+/', '-_'), '=');
}
function bz_validate_token($token, $secret) {
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return false;
    $json = base64_decode(strtr($parts[0], '-_', '+/'));
    $sig  = base64_decode(strtr($parts[1], '-_', '+/'));
    $calc = hash_hmac('sha256', $json, $secret, true);
    if (!hash_equals($calc, $sig)) return false;
    $payload = @json_decode($json, true);
    if (!$payload || time() > $payload['exp']) return false;
    return $payload;
}

function bz_debug_log($msg, $extra = []) {
    if (!BUZZ_SSO_DEBUG) return;
    $ts = gmdate('Y-m-d H:i:s');
    @file_put_contents(BUZZ_DEBUG_LOG, "[$ts] $msg: " . json_encode($extra) . PHP_EOL, FILE_APPEND);
}
function bz_expire_buzz_cookie() {
    $expiry = time() - 3600;
    $domain = BUZZ_COOKIE_DOMAIN;
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, '', [
            'expires' => $expiry,
            'path' => '/',
            'domain' => $domain,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        setcookie(BUZZ_SSO_COOKIE, '', $expiry, '/', $domain, true, true);
    }
    unset($_COOKIE[BUZZ_SSO_COOKIE]);
}

// --- SSO login handoff on WP login ---
add_action('wp_login', function($login, $user) use ($__buzz_sso_secret) {
    if (!$__buzz_sso_secret) return;

    $payload = [
        'wp_user_id'    => (int)$user->ID,
        'wp_user_login' => (string)$user->user_login,
        'wp_user_email' => (string)$user->user_email,
    ];
    $token = bz_build_one_time_token($payload, $__buzz_sso_secret);

    // Set SSO cookie for bridges (optional; bridges should also accept token param)
    setcookie(BUZZ_SSO_COOKIE, $token, [
        'expires'  => time() + BUZZ_SSO_TTL,
        'path'     => '/',
        'domain'   => BUZZ_COOKIE_DOMAIN,
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // Redirect to sso-landing.php orchestrator
    $redirect_to = !empty($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])
        ? esc_url_raw(wp_unslash($_REQUEST['redirect_to']))
        : '/';
    $sso_url = site_url('/sso-landing.php?token=' . rawurlencode($token) . '&redirect_to=' . rawurlencode($redirect_to));
    bz_debug_log('wp_login SSO', ['redirect'=>$sso_url]);
    wp_safe_redirect($sso_url);
    exit;
}, 10, 2);

// --- Expire SSO cookie & orchestrate logout on WP logout ---
add_action('wp_logout', function() {
    bz_expire_buzz_cookie();
    // Chain to orchestrator
    wp_safe_redirect('https://buzzjuice.net/shared/sso-logout.php?from_wp=1&logged_out=1');
    exit;
}, 10);

// --- Token-based orchestrator logout ---
add_action('login_init', function() use ($__buzz_sso_secret) {
    if (empty($_GET['action']) || $_GET['action'] !== 'logout' || empty($_GET['sso_one_time'])) return;
    $payload = bz_validate_token($_GET['sso_one_time'], $__buzz_sso_secret);
    if (!$payload) return;
    bz_expire_buzz_cookie();
    wp_safe_redirect('https://buzzjuice.net/shared/sso-logout.php?from_wp=1&logged_out=1');
    exit;
}, 1);

// --- BuddyBoss login redirect compatibility (unchanged) ---
add_action('plugins_loaded', function() {
    if (function_exists('bb_login_redirect')) {
        remove_filter('bp_login_redirect', 'bb_login_redirect', PHP_INT_MAX);
        remove_filter('login_redirect', 'bb_login_redirect', PHP_INT_MAX);
        add_filter('bp_login_redirect', 'bluecrown_bb_login_redirect', PHP_INT_MAX, 3);
        add_filter('login_redirect', 'bluecrown_bb_login_redirect', PHP_INT_MAX, 3);
    }
});
function bluecrown_bb_login_redirect($redirect_to, $request, $user) {
    if ($user && is_object($user) && is_a($user, 'WP_User')) {
        if (in_array('administrator', (array)$user->roles, true)) {
            return $redirect_to;
        }
        if (function_exists('bb_redirect_after_action')) {
            $redirect_to = bb_redirect_after_action($redirect_to, $user->ID, 'login');
        }
    }
    if (!empty($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])) {
        $redirect_to = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
    } else {
        if (function_exists('bb_redirect_after_action')) {
            $redirect_to = bb_redirect_after_action($redirect_to, null, 'login');
        }
    }
    return $redirect_to;
}

// --- Optional: logout without confirm ---
add_action('check_admin_referer', function($action, $result){
    if ($action === 'log-out' && !isset($_GET['_wpnonce'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : 'https://buzzjuice.net';
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));
        header("Location: $location");
        exit;
    }
}, 10, 2);

// --- Sanitize last_url cookie (unchanged) ---
add_action('init', function() {
    if (empty($_COOKIE['last_url'])) return;
    $last = wp_unslash($_COOKIE['last_url']);
    $probe = strtolower((string)$last);
    $sso_markers = ['ww-sso-bridge.php','qd-sso-bridge.php','sso_action=do_login','sso_client_log','from_wp=1','/shared/sso-logout.php'];
    foreach ($sso_markers as $m) {
        if (strpos($probe, $m) !== false) {
            @setcookie('last_url', '', time()-3600, '/');
            unset($_COOKIE['last_url']);
            bz_debug_log('bz_sanitize_last_url_cookie: removed suspicious last_url cookie', ['original'=>$last]);
            return;
        }
    }
}, 5);

?>