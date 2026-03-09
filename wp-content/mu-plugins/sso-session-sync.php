<?php
/*
 * BuzzJuice Enterprise Stateless SSO Authority (WordPress Root)
 * Platforms: WoWonder ('streams'), QuickDate ('social'), WordPress ('buzznet')
 * Unified SSO: stateless JWT, background cross-domain authentication.
 * Uses centrally defined helpers from shared/sso_bridge_helpers.php.
 */

if (!defined('ABSPATH')) exit;

// --------------------------------------
// Load shared SSO helpers (all utilities)
// --------------------------------------
require_once __DIR__ . '/../../shared/sso_bridge_helpers.php';

// --------------------------------------
// CONFIG
// --------------------------------------
if (!defined('BUZZ_SSO_COOKIE'))     define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_SSO_TTL'))        define('BUZZ_SSO_TTL', 1200);
if (!defined('BUZZ_COOKIE_DOMAIN'))  define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))      define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_DEBUG_LOG'))      define('BUZZ_DEBUG_LOG', __DIR__ . '/wp_debug_buzz_sso.log');

$__buzz_sso_secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

// --------------------------------------
// Debug Logging (centralized helper)
// --------------------------------------
function bz_debug_log($msg, $extra = []) {
    if (!BUZZ_SSO_DEBUG) return;
    bz_sso_bridge_log($msg, $extra, BUZZ_DEBUG_LOG);
}

// ---------------------------------------------------
// Token endpoint (?sso_action=get_token&aud)
// ---------------------------------------------------
add_action('init', function() use ($__buzz_sso_secret) {
    if (empty($_GET['sso_action']) || $_GET['sso_action'] !== 'get_token') return;

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');

    if (!$__buzz_sso_secret) {
        status_header(500);
        echo wp_json_encode(['status'=>500,'error'=>'SSO secret not configured']);
        exit;
    }
    if (!is_user_logged_in()) {
        status_header(401);
        echo wp_json_encode(['status'=>401,'error'=>'User not logged in']);
        exit;
    }
    $user = wp_get_current_user();
    if (!$user || !$user->ID) {
        status_header(401);
        echo wp_json_encode(['status'=>401,'error'=>'Invalid WP session']);
        exit;
    }
    $aud = isset($_REQUEST['aud']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_REQUEST['aud']) : 'buzznet';
    if (!in_array($aud, ['buzznet','streams','social'], true)) $aud = 'buzznet';
    $payload = [
        'wp_user_id'    => (int)$user->ID,
        'wp_user_login' => (string)$user->user_login,
        'wp_user_email' => (string)$user->user_email,
        'wo_user_id'    => (string)get_user_meta($user->ID, 'wo_user_id', true),
        'qd_user_id'    => (string)get_user_meta($user->ID, 'qd_user_id', true)
    ];
    $token = bz_sso_jwt_encode($payload, $__buzz_sso_secret, $aud, BUZZ_SSO_TTL);
    bz_debug_log("Bridge token issued for aud=$aud", [
        'wp_user_id'=>$user->ID, 'aud'=>$aud, 'ip'=>$_SERVER['REMOTE_ADDR'] ?? 'CLI'
    ]);
    status_header(200);
    echo wp_json_encode(['status'=>200,'token'=>$token,'payload'=>$payload,'exp'=>time()+BUZZ_SSO_TTL]);
    exit;
});



// ---------------------------------------------------
// WP login: Issue SSO JWT for all platforms, background propagate
// ---------------------------------------------------
add_action('wp_login', function($user_login, $user) use ($__buzz_sso_secret) {
    if (!$__buzz_sso_secret) return;
    $payload = [
        'wp_user_id'    => (int)$user->ID,
        'wp_user_login' => (string)$user->user_login,
        'wp_user_email' => (string)$user->user_email,
        'wo_user_id'    => (string)get_user_meta($user->ID, 'wo_user_id', true),
        'qd_user_id'    => (string)get_user_meta($user->ID, 'qd_user_id', true),
        'jti'           => bin2hex(random_bytes(16)),
        'iat'           => time(),
        'exp'           => time() + BUZZ_SSO_TTL
    ];
    $token = bz_sso_jwt_encode($payload, $__buzz_sso_secret, 'buzznet', BUZZ_SSO_TTL);
    bz_sso_set_cookie($token, BUZZ_SSO_TTL);
}, 10, 2);

add_action('wp_logout', function() {
    setcookie(BUZZ_SSO_COOKIE, '', time() - 3600, '/', '.buzzjuice.net' /* BUZZ_COOKIE_DOMAIN */);
});



// ---------------------------------------------------
// BUZZ_SSO_COOKIE Auto-refresh (activity/throttled)
// ---------------------------------------------------
add_action('init', function() use ($__buzz_sso_secret) {
    if (empty($_GET['sso_action'])) return;
    if ($_GET['sso_action'] == 'issue_tokens') {
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        if (!$__buzz_sso_secret) { status_header(500); echo wp_json_encode(['status'=>500]); exit; }
        if (!is_user_logged_in()) { status_header(401); echo wp_json_encode(['status'=>401]); exit; }
        $user = wp_get_current_user();
        $aud = $_REQUEST['aud'] ?? 'buzznet'; // streams/social/buzznet
        $payload = [
            'wp_user_id'    => (int)$user->ID,
            'wp_user_login' => $user->user_login,
            'wp_user_email' => $user->user_email,
            'wo_user_id'    => get_user_meta($user->ID,'wo_user_id',true),
            'qd_user_id'    => get_user_meta($user->ID,'qd_user_id',true),
        ];
        $access_token = bz_sso_jwt_encode($payload, $__buzz_sso_secret, $aud, 600, 'access');
        $refresh_token = bz_sso_jwt_encode($payload, $__buzz_sso_secret, $aud, 216000, 'refresh');
        setcookie('buzz_access', $access_token, time()+600, '/', '.buzzjuice.net', true, true);
        setcookie('buzz_refresh', $refresh_token, time()+216000, '/', '.buzzjuice.net', true, true);
        status_header(200);
        echo wp_json_encode([
            'status'=>200,
            'access'=>$access_token,
            'refresh'=>$refresh_token,
            'exp'=>time()+600
        ]);
        exit;
    }
});
// ---------------------------------------------------
// LOGOUT HANDOFF
// ---------------------------------------------------
add_action('wp_logout', function() {
    // Expire WordPress login cookies
    foreach ($_COOKIE as $key => $val) {
        if (strpos($key, 'wordpress_logged_in_') === 0) {
            setcookie($key, '', time() - 3600, '/');
            unset($_COOKIE[$key]);
        }
    }
    // Expire SSO JWT cookies (all known variants)
    $domain = defined('BUZZ_COOKIE_DOMAIN') ? BUZZ_COOKIE_DOMAIN : '.buzzjuice.net';
    foreach (['buzz_sso','buzz_access','buzz_refresh'] as $c) {
        setcookie($c, '', time() - 3600, '/', $domain, true, true);
        unset($_COOKIE[$c]);
        setcookie($c, '', time() - 3600, '/');
    }
    // Destroy PHP session if active
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        @session_unset();
        @session_destroy();
    }
    // Chained orchestrator: WP disables local SSO/JWT, then cascades
    wp_safe_redirect('https://buzzjuice.net/shared/sso-logout.php?cabin=home');
    exit;
}, 10);

// Optional: forced SLO if WP cookie missing but SSO cookie present
add_action('init', function() {
    if (!is_user_logged_in() && isset($_COOKIE['buzz_sso'])) {
        $domain = defined('BUZZ_COOKIE_DOMAIN') ? BUZZ_COOKIE_DOMAIN : '.buzzjuice.net';
        foreach (['buzz_sso','buzz_access','buzz_refresh'] as $c) {
            setcookie($c, '', time() - 3600, '/', $domain, true, true);
            unset($_COOKIE[$c]);
            setcookie($c, '', time() - 3600, '/');
        }
        header('Location: https://buzzjuice.net/shared/sso-logout.php');
        exit();
    }
});

// --- logout without confirm ---
add_action('check_admin_referer', function($action, $result){
    if ($action === 'log-out' && !isset($_GET['_wpnonce'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : 'https://buzzjuice.net';
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));
        header("Location: $location");
        exit;
    }
}, 10, 2);

// ---------------------------------------------------
// Hardened last_url cookie sanitize (unchanged)
// ---------------------------------------------------
add_action('init', function() {
    if (empty($_COOKIE['last_url'])) return;
    $last  = wp_unslash($_COOKIE['last_url']);
    $probe = strtolower((string)$last);
    $markers = [
        'ww-sso-bridge.php', 'qd-sso-bridge.php',
        'sso_action=do_login', 'sso_client_log',
        'from_wp=1', '/shared/sso-logout.php'
    ];
    foreach ($markers as $m) {
        if (strpos($probe, $m) !== false) {
            @setcookie('last_url', '', time()-3600, '/');
            unset($_COOKIE['last_url']);
            bz_debug_log('Removed suspicious last_url cookie', ['original'=>$last]);
            return;
        }
    }
}, 5);

?>