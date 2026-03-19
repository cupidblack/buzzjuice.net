<?php
@ini_set('session.cookie_httponly',1);
@ini_set('session.use_only_cookies',1);
if (!version_compare(PHP_VERSION, '7.1.0', '>=')) {
    exit("Required PHP_VERSION >= 7.1.0 , Your PHP_VERSION is : " . PHP_VERSION . "\n");
}
if (!function_exists("mysqli_connect")) {
    exit("MySQLi is required to run the application, please contact your hosting to enable php mysqli.");
}
date_default_timezone_set('UTC');

function bz_safe_session_start() {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    } catch (Throwable $e) {
        ini_set('session.gc_probability', 100);
        @session_destroy();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        @session_start();
    }
}
bz_safe_session_start();



@ini_set('gd.jpeg_ignore_warning', 1);
require_once __DIR__ . '/libraries/DB/vendor/joshcam/mysqli-database-class/MySQL-Maria.php';
require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/functions_general.php';
require_once __DIR__ . '/includes/tabels.php';
require_once __DIR__ . '/includes/functions_one.php';
require_once __DIR__ . '/includes/functions_two.php';
require_once __DIR__ . '/includes/functions_three.php';