<?php
// QuickDate SSO Logout — no buzz_sso_secret required, requests WP logout URL with nonce

$bootstrap = __DIR__ . '/bootstrap.php';
if (file_exists($bootstrap)) require_once $bootstrap;
$shared_helpers = __DIR__ . '/../shared/db_helpers.php';
if (file_exists($shared_helpers)) require_once $shared_helpers;

// --- Background POST invalidate ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    $sso_keys = [
        'wp_user_id','wp_user_login','wp_user_email',
        'wo_user_id','qd_user_id','qd_ready','expected_user_id',
        'buzz_sso_last_sync','wp_php_session_id','wp_session_name',
        'buzz_sso_last','buzz_sso_serialized','wp_sso_login','JWT','user_id'
    ];
    foreach ($sso_keys as $k) if (isset($_SESSION[$k])) unset($_SESSION[$k]);
    @session_unset();
    @session_destroy();
    $domain = '.buzzjuice.net';
    $expiry = time() - 3600;
    foreach (['buzz_sso','JWT','src','user_id', session_name(), 'PHPSESSID'] as $c) {
        if (isset($_COOKIE[$c])) unset($_COOKIE[$c]);
        setcookie($c, '', -1, '/', $domain);
        setcookie($c, '', -1, '/');
    }
    $wp_sid = $_COOKIE['PHPSESSID'] ?? $_SESSION['wp_php_session_id'] ?? null;
    if ($wp_sid) {
        $shadow_dir = realpath(__DIR__ . '/../shared/sso_sessions') ?: (__DIR__ . '/../shared/sso_sessions');
        $shadow_id = 'shadow_' . $wp_sid;
        $base = rtrim($shadow_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $shadow_id;
        foreach (['', '.ser', '.json'] as $suf) {
            $p = $base . $suf;
            if (is_file($p)) @unlink($p);
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['logged_out'=>1]);
    exit();
}

// --- GET logout flow (cascade, no secret required) ---
if (session_status() === PHP_SESSION_NONE) @session_start();
$_SESSION = [];
@session_unset();
@session_destroy();
$expiry = time() - 3600;
$domain = '.buzzjuice.net';
foreach (['JWT','src','buzz_sso','user_id', session_name(), 'PHPSESSID'] as $c) {
    if (isset($_COOKIE[$c])) unset($_COOKIE[$c]);
    setcookie($c, '', -1, '/', $domain);
    setcookie($c, '', -1, '/');
}

$current_url = $_SERVER['REQUEST_URI'];
$cabin_home_pattern = "/\?cabin=home/";
$cache_pattern = "/\?cache=/";
$social_home_pattern = "/\?social=home/";

if (preg_match($cabin_home_pattern, $current_url)) {
    header("Location: https://buzzjuice.net/");
    exit();
} elseif (preg_match($cache_pattern, $current_url)) {
    // Final step: request WP logout URL with nonce from orchestrator and redirect to it
    $resp = file_get_contents('https://buzzjuice.net/shared/sso-logout.php?wp_final_logout=1');
    if (preg_match('/Location:\s*([^\s]+)/i', $http_response_header[0] ?? '', $m)) {
        header('Location: ' . $m[1]);
        exit();
    } else {
        header("Location: https://buzzjuice.net/wp-login.php?action=logout");
        exit();
    }
} elseif (preg_match($social_home_pattern, $current_url)) {
    header("Location: https://buzzjuice.net/streams/logout/?social=home");
    exit();
} else {
    header("Location: https://buzzjuice.net/streams/logout/?social=home");
    exit();
}
?>