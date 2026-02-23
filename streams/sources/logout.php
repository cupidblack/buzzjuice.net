<?php
require_once __DIR__ . '/../assets/init.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Clean up session and local WoWonder login
if (!empty($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    @mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `session_id` = '" . Wo_Secure($uid) . "'");
}
$_SESSION = [];
session_unset();
session_destroy();

// Clean up relevant cookies
$domain = '.buzzjuice.net';
$expiry = time() - 3600;
foreach (['user_id','switched_accounts','buzz_sso'] as $c) {
    if (isset($_COOKIE[$c])) unset($_COOKIE[$c]);
    setcookie($c, '', $expiry, '/', $domain);
    setcookie($c, '', $expiry, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['logged_out'=>1]);
    exit();
}

header("Location: https://buzzjuice.net/");
exit;