<?php
@ini_set('session.cookie_httponly', 1);
@ini_set('session.use_only_cookies', 1);
@ini_set('session.cookie_secure', 1);
@ini_set('session.cookie_samesite', 'Lax');
@ini_set('session.use_strict_mode', 1);

if (!version_compare(PHP_VERSION, '7.1.0', '>=')) {
    exit("Required PHP_VERSION >= 7.1.0 , Your PHP_VERSION is : " . PHP_VERSION);
}
if (!function_exists("mysqli_connect")) {
    exit("MySQLi is required to run the application.");
}
date_default_timezone_set('UTC');

if (!defined('BUZZ_SSO_COOKIE')) define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_SSO_SECRET')) define('BUZZ_SSO_SECRET', getenv('BUZZ_SSO_SECRET') ?: '');

function ww_should_start_local_session() {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    // Only start session for local login, admin, or native POSTs, never just for SSO bridge/fanout
    return $method !== 'GET'
        || !empty($_GET['ww_login']) || !empty($_POST['ww_login'])
        || !empty($_GET['admin']) || !empty($_POST['admin'])
        || (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login');
}
if (session_status() === PHP_SESSION_NONE && ww_should_start_local_session()) {
    session_start();
}

@ini_set('gd.jpeg_ignore_warning', 1);
require_once('assets/libraries/DB/vendor/joshcam/mysqli-database-class/MySQL-Maria.php');
require_once('includes/cache.php');
require_once('includes/functions_general.php');
require_once('includes/tabels.php');
require_once('includes/functions_one.php');
require_once('includes/functions_two.php');
require_once('includes/functions_three.php');