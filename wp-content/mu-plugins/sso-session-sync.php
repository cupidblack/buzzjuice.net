<?php
/*
 * BuzzJuice Enterprise Stateless SSO Authority (WordPress Root)
 * Platforms: WoWonder ('streams'), QuickDate ('social'), WordPress ('buzznet')
 * Unified SSO: stateless JWT, background cross-domain authentication.
 * Uses centrally defined helpers from shared/sso_bridge_helpers.php.
 */

if (!defined('ABSPATH')) exit;

/**
 * Safe session config
 */

add_action('init', function () {
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @ini_set('session.cookie_domain', '.buzzjuice.net');
    }
}, 0);

// --------------------------------------
// Load shared SSO helpers (all utilities)
// --------------------------------------
require_once ABSPATH . '/shared/sso_bridge_helpers.php';

// --- load shared logout helper (if present) ---
if (file_exists(ABSPATH . '/shared/logout-common.php')) {
    require_once ABSPATH . '/shared/logout-common.php';
}

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

// ---------------------------------------------------
// BUZZ_SSO_COOKIE Auto-refresh (activity/throttled)
// ---------------------------------------------------
add_action('init', function() use ($__buzz_sso_secret) {
    if (empty($_GET['sso_action'])) return;
    if ($_GET['sso_action'] == 'issue_tokens') {
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        if (!$__buzz_sso_secret) { status_header(500); echo wp_json_encode(['status'=>500]); exit; }
        if (!is_user_logged_in()) {
            status_header(401);
            echo wp_json_encode([
                'status' => 401,
                'error' => 'User not logged in',
                'cookies_received' => array_keys($_COOKIE),
                'headers_received' => getallheaders() // if available
            ]);
            exit;
        }
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

// -----------------------------
// SSO: dedicated logout endpoint + hardened wp_logout cleanup
// -----------------------------

// Dedicated endpoint for cross-app handoff: /sso/logout or ?buzz_logout=1
add_action('init', function () {
    $req = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($req, PHP_URL_PATH) ?: '';
    $is_sso_path = (rtrim($path, '/') === '/sso/logout');

    if (empty($_GET['buzz_logout']) && !$is_sso_path) {
        return;
    }

    // Ensure helper available
    if (file_exists(ABSPATH . '/shared/logout-common.php')) {
        require_once ABSPATH . '/shared/logout-common.php';
    }

    // If a WP session exists, call wp_logout() which triggers the wp_logout hook below.
    if (function_exists('is_user_logged_in') && is_user_logged_in()) {
        if (function_exists('wp_logout')) {
            wp_logout();
        }
    } else {
        // If no WP session but SSO cookies exist, still perform final cleanup
        if (function_exists('bz_clear_cookies')) {
            bz_clear_cookies(['buzz_sso', 'buzz_access', 'buzz_refresh', 'bbj_sso_ready']);
        }
        if (function_exists('bz_destroy_php_session')) {
            bz_destroy_php_session();
        }
    }

    // redirect_to parameter or fallback to home
    $redirect_to = !empty($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : home_url('/');
    if (function_exists('wp_safe_redirect')) {
        wp_safe_redirect($redirect_to);
    } else {
        header('Location: ' . $redirect_to);
    }
    exit;
}, 1);

// Hardened WP logout hook: final defense-in-depth cleanup by WordPress.
add_action('wp_logout', function () {
    // Ensure helper loaded
    if (file_exists(ABSPATH . '/shared/logout-common.php')) {
        require_once ABSPATH . '/shared/logout-common.php';
    }

    $user_id = function_exists('get_current_user_id') ? get_current_user_id() : 0;
    if (function_exists('bz_logout_log')) {
        bz_logout_log('wordpress', $user_id, 'wp_logout_hook', 'initiated');
    }

    // 1) Destroy every WordPress session token for the user (global logout)
    if ($user_id > 0 && class_exists('WP_Session_Tokens')) {
        try {
            WP_Session_Tokens::get_instance($user_id)->destroy_all();
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'wp_session_tokens', 'success');
        } catch (Throwable $e) {
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'wp_session_tokens', 'error', ['err' => $e->getMessage()]);
        }
    }

    // 1.5) Update logout epoch on user meta (helps invalidate prior tokens by issued_at)
    if ($user_id > 0 && function_exists('update_user_meta')) {
        try {
            update_user_meta($user_id, 'bbj_logout_epoch', time());
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'logout_epoch', 'updated');
        } catch (Throwable $e) {
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'logout_epoch', 'error', ['err' => $e->getMessage()]);
        }
    }

    // 2) Destroy current session and clear auth cookies via WP APIs if available
    if (function_exists('wp_destroy_current_session')) {
        try {
            wp_destroy_current_session();
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'wp_destroy_current_session', 'success');
        } catch (Throwable $e) {
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'wp_destroy_current_session', 'error', ['err' => $e->getMessage()]);
        }
    }
    if (function_exists('wp_clear_auth_cookie')) {
        try {
            wp_clear_auth_cookie();
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'wp_clear_auth_cookie', 'success');
        } catch (Throwable $e) {
            if (function_exists('bz_logout_log')) bz_logout_log('wordpress', $user_id, 'wp_clear_auth_cookie', 'error', ['err' => $e->getMessage()]);
        }
    }

    // 3) Clear shared SSO cookies idempotently
    $shared = ['buzz_sso', 'buzz_access', 'buzz_refresh', 'bbj_sso_ready', 'JWT'];
    if (function_exists('bz_clear_cookies')) {
        bz_clear_cookies($shared);
        bz_logout_log('wordpress', $user_id, 'sso_cookies', 'cleared', ['cookies' => $shared]);
    }

    // 4) Destroy PHP session (idempotent)
    if (function_exists('bz_destroy_php_session')) {
        bz_destroy_php_session();
        bz_logout_log('wordpress', $user_id, 'php_session', 'destroyed');
    }

    // 5) Remove SSO transients
    if ($user_id > 0 && function_exists('delete_transient')) {
        delete_transient('bbj_sso_ready_' . $user_id);
        delete_transient('bbj_sso_verified_' . $user_id);
        bz_logout_log('wordpress', $user_id, 'sso_transients', 'deleted');
    }

    if (function_exists('bz_logout_log')) {
        bz_logout_log('wordpress', $user_id, 'wp_logout_hook', 'complete');
    }
}, 10);

// Forced SLO protection: if WP not logged-in but shared SSO cookie exists, clear SSO cookies and session and redirect to home.
add_action('init', function() {
    if (function_exists('is_user_logged_in') && !is_user_logged_in() && !empty($_COOKIE['buzz_sso'])) {
        if (file_exists(ABSPATH . '/shared/logout-common.php')) {
            require_once ABSPATH . '/shared/logout-common.php';
        }
        if (function_exists('bz_logout_log')) {
            bz_logout_log('wordpress', 'unknown', 'forced_slo', 'initiated', ['cookie' => 'buzz_sso']);
        }
        if (function_exists('bz_clear_cookies')) {
            bz_clear_cookies(['buzz_sso', 'buzz_access', 'buzz_refresh', 'bbj_sso_ready']);
        }
        if (function_exists('bz_destroy_php_session')) {
            bz_destroy_php_session();
        }

        $home = function_exists('home_url') ? home_url('/') : 'https://buzzjuice.net/';
        if (function_exists('wp_safe_redirect')) {
            wp_safe_redirect($home);
        } else {
            header('Location: ' . $home);
        }
        exit();
    }
}, 5);

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

// --- SSO READY marker: lets verified-gate know cookies and tokens are stable ---
// Path: wp-content/mu-plugins/sso-session-sync.php
/**
 * SSO session hydration marker: set after SSO/session sync on every page load if user is logged in.
 */
add_action('init', function () {
    if (!is_user_logged_in()) {
        return;
    }
    $user_id = get_current_user_id();
    // Mark both transient (Fast) and cookie (Cross-request fallback)
    set_transient('bbj_sso_ready_' . $user_id, 1, 120);

    setcookie(
        'bbj_sso_ready',
        '1',
        time() + 120,
        COOKIEPATH,
        COOKIE_DOMAIN,
        is_ssl(),
        true
    );
});