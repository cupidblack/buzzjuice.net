<?php
require_once __DIR__ . '/../assets/init.php';
require_once __DIR__ . '/../../shared/db_helpers.php';
$bridge_helpers = __DIR__ . '/../../shared/sso_bridge_helpers.php';
if (file_exists($bridge_helpers)) require_once $bridge_helpers;

// --- Background POST invalidate ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    foreach (['buzz_sso','buzz_access','buzz_refresh','user_id','switched_accounts','JWT','src', session_name(), 'PHPSESSID'] as $c) {
        if (isset($_COOKIE[$c])) unset($_COOKIE[$c]);
        setcookie($c, '', -1, '/', '.buzzjuice.net');
        setcookie($c, '', -1, '/');
    }
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    session_unset();
    @session_destroy();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['logged_out'=>1]);
    exit();
}

// --- GET logout flow (cascade, stateless SSO) ---
foreach (['buzz_sso','buzz_access','buzz_refresh','user_id','switched_accounts','JWT','src', session_name(), 'PHPSESSID'] as $c) {
    if (isset($_COOKIE[$c])) unset($_COOKIE[$c]);
    setcookie($c, '', -1, '/', '.buzzjuice.net');
    setcookie($c, '', -1, '/');
}
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
session_unset();
@session_destroy();

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
    // Request WP logout URL (nonce) from orchestrator and redirect to it
    $sso_json = 'https://buzzjuice.net/shared/sso-logout.php?wp_final_logout=1&format=json';
    $wp_logout_location = false;
    $ctx = stream_context_create(['http'=>['method'=>'GET','timeout'=>5,'ignore_errors'=>true]]);
    $body = @file_get_contents($sso_json, false, $ctx);
    if ($body) {
        $data = @json_decode($body, true);
        if (is_array($data) && !empty($data['logout_url'])) {
            $wp_logout_location = $data['logout_url'];
        }
    }
    if (!$wp_logout_location && function_exists('bz_fetch_remote_location')) {
        $wp_logout_location = bz_fetch_remote_location('https://buzzjuice.net/shared/sso-logout.php?wp_final_logout=1', 5);
    }
    if ($wp_logout_location) {
        header('Location: ' . $wp_logout_location);
        exit();
    } else {
        header("Location: https://buzzjuice.net/wp-login.php?action=logout");
        exit();
    }
} else {
    header("Location: https://buzzjuice.net/social/logout.php?social=home");
    exit();
}
?>