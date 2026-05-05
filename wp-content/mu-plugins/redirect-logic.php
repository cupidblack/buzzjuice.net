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
    // 1. Don't process if not actually logged in via WP
    if (!is_user_logged_in()) return;

    // 2. Only run on wp-login.php
    $php_self = strtolower($_SERVER['PHP_SELF'] ?? '');
    $req_uri  = strtolower($_SERVER['REQUEST_URI'] ?? '');
    $pagenow  = strtolower($GLOBALS['pagenow'] ?? '');
    if (
        strpos($php_self, 'wp-login.php') === false &&
        strpos($req_uri, 'wp-login.php') === false &&
        $pagenow !== 'wp-login.php'
    ) {
        return;
    }

    // 3. Audience and config source of truth
    $audience = 'buzznet';
    if (!defined('BUZZ_SSO_COOKIE'))     define('BUZZ_SSO_COOKIE', 'buzz_sso');
    if (!defined('BUZZ_SSO_TTL_ACCESS')) define('BUZZ_SSO_TTL_ACCESS', 600);
    if (!defined('BUZZ_SSO_TTL_REFRESH'))define('BUZZ_SSO_TTL_REFRESH', 216000);

    // 4. Get SSO secret
    $secret = bzj_sso_secret();
    if (!$secret) return;

    // 5. Try access/refresh cookie JWT (fast-path)
    $access_token  = $_COOKIE['buzz_access'] ?? $_COOKIE[BUZZ_SSO_COOKIE] ?? null;
    $refresh_token = $_COOKIE['buzz_refresh'] ?? null;
    $access_payload = false;

    if ($access_token) {
        $access_payload = bzj_safe_jwt_validate($access_token, $audience, 'access')
            ?: bzj_safe_jwt_validate($access_token, 'buzznet', 'access');
    }

    if (!$access_payload && $refresh_token) {
        $refresh_payload = bzj_safe_jwt_validate($refresh_token, $audience, 'refresh')
            ?: bzj_safe_jwt_validate($refresh_token, 'buzznet', 'refresh');
        if ($refresh_payload) {
            $new_payload = [
                'wp_user_id'    => $refresh_payload['wp_user_id'] ?? null,
                'wp_user_login' => $refresh_payload['wp_user_login'] ?? null,
                'wp_user_email' => $refresh_payload['wp_user_email'] ?? null,
                'wo_user_id'    => $refresh_payload['wo_user_id'] ?? null,
                'qd_user_id'    => $refresh_payload['qd_user_id'] ?? null
            ];
            $new_access = bz_sso_jwt_encode($new_payload, $secret, $audience, BUZZ_SSO_TTL_ACCESS, 'access');
            bz_sso_set_cookie('buzz_access', $new_access, time() + BUZZ_SSO_TTL_ACCESS);
            $access_payload = bzj_safe_jwt_validate($new_access, $audience, 'access');
        }
    }

    // 6. Try authoritative server-side endpoint if above fails
    if (!$access_payload) {
        $wp_token_url = site_url('/?sso_action=issue_tokens&aud=' . urlencode($audience));
        $cookie_str = '';
        foreach ($_COOKIE as $k => $v) {
            if (strpos($k, 'wordpress_logged_in_') === 0 || strpos($k, 'wordpress_sec_') === 0) {
                $cookie_str .= "$k=$v; ";
            }
        }
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Cookie: ' . trim($cookie_str) . "\r\nUser-Agent: BuzzSSO/1.0",
                'timeout' => 5
            ]
        ]);
        $resp = @file_get_contents($wp_token_url, false, $context);
        $http_code = 0;
        if (isset($http_response_header[0]) &&
            preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $matches)) {
            $http_code = (int)$matches[1];
        }
        if ($resp && $http_code === 200) {
            $data = json_decode($resp, true);
            if (!empty($data['access'])) {
                bz_sso_set_cookie('buzz_access', $data['access'], time() + BUZZ_SSO_TTL_ACCESS);
                if (!empty($data['refresh']))
                    bz_sso_set_cookie('buzz_refresh', $data['refresh'], time() + BUZZ_SSO_TTL_REFRESH);
                $access_payload = bzj_safe_jwt_validate($data['access'], $audience, 'access');
            }
        }
    }

    // 7. Final fallback: browser JS SSO hydration (top reliability for edge cases)
    if (!$access_payload) {
        echo '<!DOCTYPE html>
        <html>
        <head>
        <meta charset="utf-8">
        <title>Refreshing SSO tokens…</title>
        <script>
        // Force direct token minting from browser to work around http-only/cookie/SameSite/lax issues.
        (async function () {
            try {
                const res = await fetch("'.esc_attr(site_url('/?sso_action=issue_tokens&aud=buzznet')).'", {
                    credentials: "include"
                });
                if (res.status === 200) {
                    window.location.reload();
                    return;
                }
                if (res.status === 401) {
                    window.location.href = "/wp-login.php?try=mu-js401";
                    return;
                }
            } catch(e) {}
            window.location.href = "/wp-login.php?try=mu-jsfail";
        })();
        </script>
        </head>
        <body>Refreshing your login session…</body>
        </html>';
        exit;
    }

    // 8. Wait for "sso ready" marker set by sso-session-sync.php
    $user_id = get_current_user_id();
    if (!get_transient('bbj_sso_ready_' . $user_id)) {
        // SSO sync sets this transient/cookie. Wait max 2s, then reload.
        echo '<script>setTimeout(function(){ location.reload(); }, 400);</script>';
        exit;
    }

    // 9. Proactive token refresh if access is about to expire
    if (isset($access_payload['exp']) && ($access_payload['exp'] - time() < 300)) {
        $user = wp_get_current_user();
        $new_payload = [
            'wp_user_id'    => (int)$user->ID,
            'wp_user_login' => (string)$user->user_login,
            'wp_user_email' => (string)$user->user_email,
            'wo_user_id'    => (string)get_user_meta($user->ID, 'wo_user_id', true),
            'qd_user_id'    => (string)get_user_meta($user->ID, 'qd_user_id', true)
        ];
        $new_token = bz_sso_jwt_encode($new_payload, $secret, 'buzznet', BUZZ_SSO_TTL_ACCESS);
        bz_sso_set_cookie($new_token, BUZZ_SSO_TTL_ACCESS);
    }

    // 10. Prevent redirect on POST, reauths, ajax, REST, cron, CLI, logout, or one-time token flows
    if ($_SERVER['REQUEST_METHOD'] === 'POST') return;
    if (!empty($_REQUEST['reauth'])) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;
    if (defined('WP_CLI') && WP_CLI) return;
    if (isset($_REQUEST['action']) && strtolower((string)$_REQUEST['action']) === 'logout') return;
    if (isset($_REQUEST['sso_one_time']) || isset($_REQUEST['sso_one_time_token']) || isset($_REQUEST['sso_token'])) return;

    // 11. Final safe redirect (never redirect to wp-login.php)
    $default_redirect = admin_url();
    $redirect_to = '';
    if (isset($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to']) && $_REQUEST['redirect_to'] !== '') {
        $requested = wp_unslash((string)$_REQUEST['redirect_to']);
        $redirect_to = function_exists('wp_validate_redirect')
            ? wp_validate_redirect($requested, $default_redirect)
            : esc_url_raw($requested);
    } else {
        $current_user = wp_get_current_user();
        $redirect_to = apply_filters('login_redirect', $default_redirect, '', $current_user);
    }
    $login_page = wp_login_url();
    if (strpos($redirect_to, 'wp-login.php') !== false ||
        untrailingslashit($redirect_to) === untrailingslashit($login_page)) {
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