<?php
// WoWonder SSO Logout — no buzz_sso_secret required, requests WP logout URL with nonce

require_once __DIR__ . '/../assets/init.php';
require_once __DIR__ . '/../../shared/db_helpers.php';

// Ensure bridge helpers can be used
$bridge_helpers = __DIR__ . '/../../shared/sso_bridge_helpers.php';
if (file_exists($bridge_helpers)) require_once $bridge_helpers;

// --- Background POST invalidate ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    session_unset();
    if (!empty($_SESSION['user_id'])) {
        $_SESSION['user_id'] = '';
        @mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `session_id` = '" . Wo_Secure($_SESSION['user_id']) . "'");
    }
    @session_destroy();
    $domain = '.buzzjuice.net';
    $expiry = time() - 3600;
    foreach (['user_id', 'switched_accounts', 'buzz_sso', 'JWT', 'src', session_name(), 'PHPSESSID'] as $c) {
        if (isset($_COOKIE[$c])) unset($_COOKIE[$c]);
        setcookie($c, '', -1, '/', $domain);
        setcookie($c, '', -1, '/');
    }
    $wp_sid = $_COOKIE['PHPSESSID'] ?? $_SESSION['wp_php_session_id'] ?? null;
    if ($wp_sid) {
        $shadow_dir = realpath(__DIR__ . '/../../shared/sso_sessions') ?: (__DIR__ . '/../../shared/sso_sessions');
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
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
session_unset();
if (!empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = '';
    @mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `session_id` = '" . Wo_Secure($_SESSION['user_id']) . "'");
}
@session_destroy();
$domain = '.buzzjuice.net';
$expiry = time() - 3600;
foreach (['user_id','switched_accounts','buzz_sso','JWT','src', session_name(), 'PHPSESSID'] as $c) {
    if (isset($_COOKIE[$c])) unset($_COOKIE[$c]);
    setcookie($c, '', -1, '/', $domain);
    setcookie($c, '', -1, '/');
}

$current_url = $_SERVER['REQUEST_URI'];
$cabin_home_pattern = "/\?cabin=home/";
$cache_pattern = "/\?cache=/";
$social_home_pattern = "/\?social=home/";

if (preg_match($cabin_home_pattern, $current_url)) {
    header("Location: https://buzzjuice.net/social/logout.php?cabin=home");
    exit();
} elseif (preg_match($cache_pattern, $current_url)) {
    $matches = [];
    preg_match('/\?cache=([\d]+)/', $current_url, $matches);
    $cache_val = isset($matches[1]) ? $matches[1] : time();
    header("Location: https://buzzjuice.net/social/logout.php?cache={$cache_val}");
    exit();
} elseif (preg_match($social_home_pattern, $current_url)) {
    // Final step: request WP logout URL with nonce from orchestrator and redirect to it
    $sso_json = 'https://buzzjuice.net/shared/sso-logout.php?wp_final_logout=1&format=json';
    $wp_logout_location = false;

    // Try JSON server-to-server fetch first (preferred)
    $ctx = stream_context_create(['http'=>['method'=>'GET','timeout'=>5,'ignore_errors'=>true]]);
    $body = @file_get_contents($sso_json, false, $ctx);
    if ($body) {
        $data = @json_decode($body, true);
        if (is_array($data) && !empty($data['logout_url'])) {
            $wp_logout_location = $data['logout_url'];
        }
    }

    // If JSON failed, try to fetch redirect Location header (robust helper)
    if (!$wp_logout_location && function_exists('bz_fetch_remote_location')) {
        $wp_logout_location = bz_fetch_remote_location('https://buzzjuice.net/shared/sso-logout.php?wp_final_logout=1', 5);
    }

    if ($wp_logout_location) {
        header('Location: ' . $wp_logout_location);
        exit();
    } else {
        // Fallback: WP logout without nonce
        header("Location: https://buzzjuice.net/wp-login.php?action=logout");
        exit();
    }
} else {
    header("Location: https://buzzjuice.net/social/logout.php?social=home");
    exit();
}
?>