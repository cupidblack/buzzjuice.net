<?php
// QuickDate SSO Logout — no buzz_sso_secret required, requests WP logout URL with nonce

// --- Deterministic cleanup + redirect to WP SSO endpoint ---
$bootstrap = __DIR__ . '/bootstrap.php';
if (file_exists($bootstrap)) require_once $bootstrap;
if (file_exists(__DIR__ . '/../shared/logout-common.php')) require_once __DIR__ . '/../shared/logout-common.php';

bz_ensure_session_started();
$session_id = bz_capture_session_id();
bz_logout_log('quickdate', $session_id ?: null, 'logout_start', 'initiated', ['method' => $_SERVER['REQUEST_METHOD'] ?? 'GET']);

// App-specific DB cleanup: remove session rows / web_token references
if (!empty($session_id) && isset($db)) {
    try {
        if (method_exists($db, 'where') && method_exists($db, 'delete')) {
            $db->where('session_id', $session_id)->delete('sessions');
            $db->where('web_token', $session_id)->update('users', [
                'web_token' => null,
                'web_token_created_at' => '0',
                'web_device' => null
            ]);
            bz_logout_log('quickdate', $session_id, 'db_cleanup', 'success');
        }
    } catch (Throwable $e) {
        bz_logout_log('quickdate', $session_id, 'db_cleanup', 'error', ['err' => $e->getMessage()]);
    }
}

// Clear app + shared cookies
$qd_cookies = ['JWT', 'quickdating', 'verify_email', 'verify_phone', 'src', 'mode'];
bz_clear_cookies(array_merge($qd_cookies, ['buzz_sso', 'buzz_access', 'buzz_refresh', 'bbj_sso_ready']));

// Destroy PHP session
bz_destroy_php_session();

bz_logout_log('quickdate', $session_id ?: null, 'logout_complete', 'redirecting_to_wp');

// Redirect to WordPress SSO endpoint
header('Location: https://buzzjuice.net/sso/logout');
exit();