<?php
// WoWonder SSO Logout — no buzz_sso_secret required, requests WP logout URL with nonce

require_once __DIR__ . '/../assets/init.php';
require_once __DIR__ . '/../../shared/db_helpers.php';

// --- Deterministic cleanup + redirect to WP SSO endpoint ---
if (file_exists(__DIR__ . '/../../shared/logout-common.php')) {
    require_once __DIR__ . '/../../shared/logout-common.php';
}

bz_ensure_session_started();
$ww_session_id = bz_capture_session_id();
bz_logout_log('wowonder', $ww_session_id ?: null, 'logout_start', 'initiated', ['method' => $_SERVER['REQUEST_METHOD'] ?? 'GET']);

// If present, delete app session row (app owns its DB)
if (!empty($ww_session_id) && !empty($sqlConnect) && defined('T_APP_SESSIONS')) {
    $sid = $ww_session_id;
    if (function_exists('mysqli_real_escape_string')) {
        $sid = mysqli_real_escape_string($sqlConnect, $sid);
    } else {
        $sid = addslashes($sid);
    }
    @mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `session_id` = '{$sid}'");
    bz_logout_log('wowonder', $ww_session_id, 'db_session_delete', 'attempted');
}

// Application-specific cookie names to clear (WoWonder)
$wowonder_cookies = ['user_id', 'switched_accounts'];

// Clear app + shared cookies
bz_clear_cookies(array_merge($wowonder_cookies, ['buzz_sso', 'buzz_access', 'buzz_refresh', 'bbj_sso_ready']));

// Destroy PHP session
bz_destroy_php_session();

bz_logout_log('wowonder', $ww_session_id ?: null, 'logout_complete', 'redirecting_to_wp');

// Redirect to WordPress SSO endpoint (absolute redirect)
header('Location: https://buzzjuice.net/sso/logout');
exit();