<?php
declare(strict_types=1);
// Unified orchestrator for stateless SSO logout

if (!headers_sent()) {
    header('Expires: 0');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// JSON endpoint: for server-to-server WP logout with nonce
if (isset($_GET['wp_final_logout']) && $_GET['format'] === 'json') {
    require_once dirname(__DIR__) . '/wp-load.php';
    $logout_url = function_exists('wp_logout_url') ? wp_logout_url('https://buzzjuice.net/') : 'https://buzzjuice.net/wp-login.php?action=logout';
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['logout_url' => $logout_url]);
    exit();
}

// Chained redirect orchestration
if (isset($_GET['cabin']) && $_GET['cabin'] === 'home') {
    header('Location: https://buzzjuice.net/streams/logout/?cabin=home');
    exit();
}
if (isset($_GET['cache'])) {
    header('Location: https://buzzjuice.net/social/logout.php?cache=' . urlencode($_GET['cache']));
    exit();
}
if (isset($_GET['social']) && $_GET['social'] === 'home') {
    header('Location: https://buzzjuice.net/streams/logout/?social=home');
    exit();
}
if (isset($_GET['wp_final_logout'])) {
    require_once dirname(__DIR__) . '/wp-load.php';
    $logout_url = function_exists('wp_logout_url') ? wp_logout_url('https://buzzjuice.net/') : 'https://buzzjuice.net/wp-login.php?action=logout';
    header('Location: ' . $logout_url);
    exit();
}

// Default: background POST JS
echo '<!doctype html><html><head><meta charset="utf-8"><title>Signing Out…</title></head><body>';
echo '<script>(function(){';
echo 'var endpoints = ["https://buzzjuice.net/streams/logout/", "https://buzzjuice.net/social/logout.php"];';
echo 'endpoints.forEach(function(ep){ fetch(ep, {method:"POST",headers:{"Content-Type":"application/json"},credentials:"include"}); });';
echo 'setTimeout(function(){ window.location.href = "https://buzzjuice.net/wp-login.php?action=logout"; }, 2500);';
echo '})();</script>';
echo '<h2>Signing out…</h2><p>You will be redirected soon. <a href="https://buzzjuice.net/wp-login.php?action=logout">Click here if not redirected.</a></p>';
echo '</body></html>';
exit;
?>