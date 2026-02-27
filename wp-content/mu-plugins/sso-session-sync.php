<?php
/*
 * BuzzJuice Enterprise Stateless SSO Authority
 * Platforms: WoWonder ('aud: streams'), QuickDate ('aud: social')
 * WordPress = source of truth
 * Cookie: JWT, 20-min, auto-refresh, bridge endpoint: minute-locked HMAC.
 */

if (!defined('ABSPATH')) exit;

// ======== CONFIG ========
if (!defined('BUZZ_SSO_COOKIE'))     define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_SSO_TTL'))        define('BUZZ_SSO_TTL', 1200); // 20 min
if (!defined('BUZZ_COOKIE_DOMAIN'))  define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))      define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_DEBUG_LOG'))      define('BUZZ_DEBUG_LOG', __DIR__ . '/wp_debug_buzz_sso.log');

$__buzz_sso_secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

// ======== SHARED SSO HELPERS ========
require_once dirname(__DIR__, 2) . '/shared/sso_bridge_helpers.php';

// ======== COOKIE EXPIRE WRAPPER ========
function sso_expire_cookie() {
    setcookie(BUZZ_SSO_COOKIE, '', time()-3600, '/', BUZZ_COOKIE_DOMAIN, true, true);
    unset($_COOKIE[BUZZ_SSO_COOKIE]);
}

// ======== SECURE TOKEN ENDPOINT ========
add_action('init', function() use ($__buzz_sso_secret) {
    if (empty($_GET['sso_action']) || $_GET['sso_action'] !== 'get_token') return;

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');

    if (!$__buzz_sso_secret) {
        status_header(500); echo wp_json_encode(['error'=>'SSO secret missing']); exit;
    }
    if (!is_user_logged_in()) {
        status_header(401); echo wp_json_encode(['error'=>'Not logged in']); exit;
    }

    $aud = sanitize_key($_GET['aud'] ?? 'buzznet');
    if (!in_array($aud, ['streams','social','buzznet'], true)) $aud = 'buzznet';

    $client_sig = $_SERVER['HTTP_X_BUZZJUICE_SIGNATURE'] ?? '';
    $minute = floor(time()/60);
    $expected_sig = hash_hmac('sha256', $aud . '|' . $minute, $__buzz_sso_secret);

    if (!hash_equals($expected_sig, $client_sig)) {
        status_header(403); echo wp_json_encode(['error'=>'Invalid signature']); exit;
    }

    $user = wp_get_current_user();
    $payload = [
        'wp_user_id'    => (int)$user->ID,
        'wp_user_login' => (string)$user->user_login,
        'wp_user_email' => (string)$user->user_email,
        'wo_user_id'    => (string)get_user_meta($user->ID,'wo_user_id',true),
        'qd_user_id'    => (string)get_user_meta($user->ID,'qd_user_id',true)
    ];

    $token = bz_sso_jwt_encode($payload, $__buzz_sso_secret, $aud, BUZZ_SSO_TTL);

    echo wp_json_encode([
        'status'  => 200,
        'token'   => $token,
        'payload' => $payload,
        'exp'     => time()+BUZZ_SSO_TTL
    ]);
    exit;
});

// ======== BACKGROUND AUTH BOTH PLATFORMS ON LOGIN ========
add_action('wp_login', function($login, $user) use ($__buzz_sso_secret) {
    if (!$__buzz_sso_secret) return;
    if (is_admin()) return;
    if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) return;

    $payload = [
        'wp_user_id'    => (int)$user->ID,
        'wp_user_login' => (string)$user->user_login,
        'wp_user_email' => (string)$user->user_email,
        'wo_user_id'    => (string)get_user_meta($user->ID,'wo_user_id',true),
        'qd_user_id'    => (string)get_user_meta($user->ID,'qd_user_id',true)
    ];

    $token_wp      = bz_sso_jwt_encode($payload,$__buzz_sso_secret,'buzznet',BUZZ_SSO_TTL);
    $token_streams = bz_sso_jwt_encode($payload,$__buzz_sso_secret,'streams',BUZZ_SSO_TTL);
    $token_social  = bz_sso_jwt_encode($payload,$__buzz_sso_secret,'social', BUZZ_SSO_TTL);

    setcookie(BUZZ_SSO_COOKIE, $token_wp, [
        'expires'  => time()+BUZZ_SSO_TTL,
        'path'     => '/',
        'domain'   => BUZZ_COOKIE_DOMAIN,
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // propagate
    $targets = [
        'streams' => home_url('/streams/ww-sso-bridge.php'),
        'social'  => home_url('/social/qd-sso-bridge.php')
    ];
    foreach ($targets as $aud => $url) {
        $token = ($aud==='streams') ? $token_streams : $token_social;
        $body = [
            'sso_action' => 'do_login',
            'sso_token'  => $token
        ];
        $sig = hash_hmac('sha256', http_build_query($body), $__buzz_sso_secret);
        wp_remote_post($url, [
            'method'    => 'POST',
            'timeout'   => 2,
            'blocking'  => false,
            'sslverify' => true,
            'headers'   => [
                'User-Agent'            => 'BuzzJuiceWP-SSO/1.0',
                'X-Buzzjuice-Signature' => $sig
            ],
            'body' => $body
        ]);
    }

    // redirect, as before
    $redirect_to = !empty($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])
        ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : '/';
    foreach (['sso-landing.php','ww-sso-bridge.php','qd-sso-bridge.php','/shared/sso-logout.php'] as $b) {
        if (strpos($redirect_to, $b) !== false) { $redirect_to='/'; break; }
    }
    $sso_url = site_url('/sso-landing.php?token='.rawurlencode($token_wp).'&redirect_to='.rawurlencode($redirect_to));
    bz_sso_bridge_log('WP login SSO redirect (after background auth)', ['to'=>$sso_url], BUZZ_DEBUG_LOG);
    wp_safe_redirect($sso_url);
    exit;
}, 10, 2);

// ======== AUTO-REFRESH (THROTTLED) ========
add_action('init', function() use ($__buzz_sso_secret) {
    if (!$__buzz_sso_secret || !is_user_logged_in() || empty($_COOKIE[BUZZ_SSO_COOKIE])) return;
    $payload = bz_sso_jwt_validate($_COOKIE[BUZZ_SSO_COOKIE], $__buzz_sso_secret, 'buzznet');
    if (!$payload) return;
    $now = time();
    if (($payload['exp'] - $now) > 300) return;
    $lock_key = 'buzz_refresh_' . get_current_user_id();
    if (get_transient($lock_key)) return;
    set_transient($lock_key, 1, 300); // throttle 5min
    unset($payload['iat'],$payload['exp'],$payload['jti']);
    $new_token = bz_sso_jwt_encode($payload, $__buzz_sso_secret, 'buzznet', BUZZ_SSO_TTL);
    setcookie(BUZZ_SSO_COOKIE,$new_token,[
        'expires'=>$now+BUZZ_SSO_TTL,
        'path'=>'/',
        'domain'=>BUZZ_COOKIE_DOMAIN,
        'secure'=>true,
        'httponly'=>true,
        'samesite'=>'Lax'
    ]);
},1);

// ======================================================================
// LOGOUT HANDOFF
// ======================================================================
add_action('wp_logout', function() {
    sso_expire_cookie();
    wp_safe_redirect('https://buzzjuice.net/shared/sso-logout.php?from_wp=1&logged_out=1');
    exit;
},10);

// --- logout without confirm ---
add_action('check_admin_referer', function($action, $result){
    if ($action === 'log-out' && !isset($_GET['_wpnonce'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : 'https://buzzjuice.net';
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));
        header("Location: $location");
        exit;
    }
}, 10, 2);

// ======== LAST_URL COOKIE HARDENING ========
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
            bz_sso_bridge_log('Removed suspicious last_url cookie', ['original'=>$last], BUZZ_DEBUG_LOG);
            return;
        }
    }
}, 5);

?>