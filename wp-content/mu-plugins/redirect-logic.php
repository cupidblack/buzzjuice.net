<?php
if (!defined('ABSPATH')) exit;

// Load dependencies
require_once ABSPATH . '/shared/db_helpers.php';
require_once ABSPATH . '/shared/sso_bridge_helpers.php';
require_once ABSPATH . '/wp-content/mu-plugins/sso-session-sync.php';

// --- Centralized, safe SSO secret getter ---
function bzj_sso_secret() {
    static $secret = null;
    if ($secret !== null) return $secret;
    if (function_exists('bzj_get_sso_secret')) {
        $secret = (string)bzj_get_sso_secret();
    } elseif (defined('BUZZ_SSO_SECRET')) {
        $secret = (string)constant('BUZZ_SSO_SECRET');
    } elseif (getenv('BUZZ_SSO_SECRET')) {
        $secret = (string)getenv('BUZZ_SSO_SECRET');
    } else {
        $secret = '';
    }
    $secret = trim($secret);
    return $secret;
}

// --- Safe JWT validation wrapper ---
function bzj_safe_jwt_validate($token, $audience, $type = 'access') {
    $secret = bzj_sso_secret();
    if ($secret === '' || !$token) {
        if (function_exists('bz_bridge_log')) {
            bz_bridge_log('SSO validation skipped: missing secret or token');
        }
        return false;
    }
    return bz_sso_jwt_validate($token, $secret, $audience, $type);
}

add_action('login_init', 'bbj_mu_redirect_logged_in_users', 100);
function bbj_mu_redirect_logged_in_users() {
    if (!defined('BUZZ_SSO_TTL_ACCESS'))    define('BUZZ_SSO_TTL_ACCESS', 12345);
    if (!defined('BUZZ_SSO_TTL_REFRESH'))   define('BUZZ_SSO_TTL_REFRESH', 216000);

    $BUZZ_SSO_SECRET = bzj_sso_secret(); // Always fetch at runtime

    // Early: skip if secret not set (prevents hash_hmac null fatal)
    if ($BUZZ_SSO_SECRET === '') {
        if (function_exists('bzj_log_once')) {
            bzj_log_once('debug-sso.log', 'SSO validation skipped: empty secret', __FILE__, __LINE__, 60);
        }
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    // Act ONLY on the login form page
    $php_self = isset($_SERVER['PHP_SELF']) ? strtolower((string)$_SERVER['PHP_SELF']) : '';
    $req_uri  = isset($_SERVER['REQUEST_URI']) ? strtolower((string)$_SERVER['REQUEST_URI']) : '';
    $pagenow  = isset($GLOBALS['pagenow']) ? strtolower((string)$GLOBALS['pagenow']) : '';
    if (
        strpos($php_self, 'wp-login.php') === false &&
        strpos($req_uri, 'wp-login.php') === false &&
        $pagenow !== 'wp-login.php'
    ) {
        return;
    }

    $audience = 'buzznet';
    $access_token  = $_COOKIE['buzz_access'] ?? $_REQUEST['buzz_access'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? $_REQUEST[BUZZ_SSO_COOKIE] ?? null);
    $refresh_token = $_COOKIE['buzz_refresh'] ?? $_REQUEST['buzz_refresh'] ?? null;
    $access_payload = false;

    // Validate access token (incl. fallback for universal audience)
    if ($access_token) {
        $access_payload = bzj_safe_jwt_validate($access_token, $audience, 'access');
        if (!$access_payload) {
            $access_payload = bzj_safe_jwt_validate($access_token, 'buzznet', 'access');
        }
    }
    // If access failed, try refresh
    if (!$access_payload && $refresh_token) {
        $refresh_payload = bzj_safe_jwt_validate($refresh_token, $audience, 'refresh');
        if (!$refresh_payload) {
            $refresh_payload = bzj_safe_jwt_validate($refresh_token, 'buzznet', 'refresh');
        }
        if ($refresh_payload) {
            $new_payload = [
                'wp_user_id'    => $refresh_payload['wp_user_id'] ?? null,
                'wp_user_login' => $refresh_payload['wp_user_login'] ?? null,
                'wp_user_email' => $refresh_payload['wp_user_email'] ?? null,
                'wo_user_id'    => $refresh_payload['wo_user_id'] ?? null,
                'qd_user_id'    => $refresh_payload['qd_user_id'] ?? null
            ];
            $new_access = bz_sso_jwt_encode($new_payload, $BUZZ_SSO_SECRET, $audience, BUZZ_SSO_TTL_ACCESS, 'access');
            bz_sso_set_cookie('buzz_access', $new_access, time()+BUZZ_SSO_TTL_ACCESS);
            $access_payload = bzj_safe_jwt_validate($new_access, $audience, 'access');
        }
    }

    // Fetch tokens via endpoint if both failed
    if (!$access_payload) {
        $wp_token_url = 'https://buzzjuice.net/?sso_action=issue_tokens&aud=' . urlencode($audience);

        $cookies = '';
        foreach ($_COOKIE as $name => $val) {
            if (strpos($name, 'wordpress_logged_in_') === 0 || strpos($name, 'wordpress_sec_') === 0) {
                $cookies .= "$name=$val; ";
            }
        }
        $cookies = trim($cookies);

        $headers = [
            'Cookie: ' . $cookies,
            'User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'BuzzSSO/1.0')
        ];
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => implode("\r\n", $headers),
                'timeout' => 5
            ]
        ]);
        $resp = @file_get_contents($wp_token_url, false, $context);
        $http_code = 0;
        if (isset($http_response_header[0])) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $matches)) {
                $http_code = (int)$matches[1];
            }
        }
        if ($resp !== false && $http_code === 200) {
            $data = json_decode($resp, true);
            if (!empty($data['access'])) {
                bz_sso_set_cookie('buzz_access', $data['access'], time()+BUZZ_SSO_TTL_ACCESS);
                if (!empty($data['refresh'])) {
                    bz_sso_set_cookie('buzz_refresh', $data['refresh'], time()+BUZZ_SSO_TTL_REFRESH);
                }
                $access_payload = bzj_safe_jwt_validate($data['access'], $audience, 'access')
                    ?: bzj_safe_jwt_validate($data['access'], 'buzznet', 'access');
            }
        }
        if ($http_code === 401) {
            $last_url = $_SERVER['REQUEST_URI'] ?? '/';
            bz_bridge_log('WP SSO endpoint returned 401 — user not logged in');
            header('Location: /wp-login.php?try=mu00&redirect_to=' . urlencode($last_url));
            exit;
        }
        // JS fallback code omitted for brevity
    }

    // Final fallback
    if (!$access_payload) {
        bz_bridge_log('Dual-token bootstrap failed — redirecting to login');
        $last_url = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /wp-login.php?try=mu01&redirect_to=' . urlencode($last_url));
        exit;
    }

    // SSO COOKIE AUTO-REFRESH
    static $last_refresh = 0;
    $now = time();
    if (
        isset($access_payload['exp']) &&
        ($access_payload['exp'] - $now) < 300 &&
        ($last_refresh == 0 || ($now - $last_refresh) > 60)
    ) {
        $user = wp_get_current_user();
        $new_payload = [
            'wp_user_id'    => (int)$user->ID,
            'wp_user_login' => (string)$user->user_login,
            'wp_user_email' => (string)$user->user_email,
            'wo_user_id'    => (string)get_user_meta($user->ID, 'wo_user_id', true),
            'qd_user_id'    => (string)get_user_meta($user->ID, 'qd_user_id', true)
        ];
        $new_token = bz_sso_jwt_encode($new_payload, $BUZZ_SSO_SECRET, 'buzznet', BUZZ_SSO_TTL_ACCESS);
        bz_sso_set_cookie($new_token, BUZZ_SSO_TTL_ACCESS);
        $last_refresh = $now;
        if (function_exists('bz_debug_log')) {
            bz_debug_log('BUZZ_SSO_COOKIE auto-refreshed', ['user' => $user->ID]);
        }
    }

    // --- REDIRECT EXCEPTIONS ---

    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') return;
    if (!empty($_REQUEST['reauth'])) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;
    if (defined('WP_CLI') && WP_CLI) return;

    $query_raw = isset($_SERVER['QUERY_STRING']) ? strtolower((string)$_SERVER['QUERY_STRING']) : '';
    if (isset($_REQUEST['action']) && strtolower((string)$_REQUEST['action']) === 'logout') return;
    if (isset($_REQUEST['sso_one_time']) || isset($_REQUEST['sso_one_time_token']) || isset($_REQUEST['sso_token'])) return;

    $sso_markers = array(
        'sso_one_time',
        'sso_one_time_token',
        'sso_token',
        'from_wp=1',
        '/shared/sso-logout.php',
        'ww-sso-bridge.php',
        'qd-sso-bridge.php',
        'sso_action=do_login',
        'sso_client_log',
        'buzz_sso',
    );
    $sso_markers = (array)apply_filters('bbj_sso_login_exempt_markers', $sso_markers);
    foreach ($sso_markers as $m) {
        if ($m && (strpos($req_uri, $m) !== false || strpos($query_raw, $m) !== false)) return;
    }

    // --- SAFE FINAL REDIRECT ---

    $current_user = wp_get_current_user();
    $default_redirect = admin_url();
    $redirect_to = '';
    if (isset($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to']) && $_REQUEST['redirect_to'] !== '') {
        $requested = wp_unslash((string)$_REQUEST['redirect_to']);
        $redirect_to = function_exists('wp_validate_redirect')
            ? wp_validate_redirect($requested, $default_redirect)
            : esc_url_raw($requested);
    } else {
        $redirect_to = apply_filters('login_redirect', $default_redirect, '', $current_user);
    }

    $login_page = wp_login_url();
    if (!$redirect_to) return;
    if (strpos($redirect_to, 'wp-login.php') !== false || untrailingslashit($redirect_to) === untrailingslashit($login_page)) {
        $redirect_to = $default_redirect;
    }
    wp_safe_redirect($redirect_to);
    exit;
}

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