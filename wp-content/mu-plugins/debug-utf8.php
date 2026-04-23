<?php
/**
 * debug-utf8.php
 *
 * Passive diagnostic module ONLY.
 * All error handling is managed by runtime-error-guard.php.
 */

if (!defined('ABSPATH')) exit;

if (defined('BUZZ_UTF8_COMPAT_LOADED')) {
    return;
}
define('BUZZ_UTF8_COMPAT_LOADED', true);

/**
 * Optional constants (safe to keep if referenced elsewhere)
 */
if (!defined('BUZZ_UTF8_COMPAT_AUDIT')) {
    define('BUZZ_UTF8_COMPAT_AUDIT', true);
}

if (!defined('BUZZ_UTF8_LOG_INTERVAL')) {
    define('BUZZ_UTF8_LOG_INTERVAL', 12345);
}

/**
 * Optional helper only (NOT used unless called explicitly by runtime guard)
 */
function buzz_utf8_log_once($message, $file = null, $line = null) {

    $log_dir = ABSPATH . '/data/logs/';

    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }

    $lock_file = $log_dir . 'debug-utf8.lock';

    $now = time();
    $last_logged = file_exists($lock_file)
        ? (int) @file_get_contents($lock_file)
        : 0;

    if (($now - $last_logged) < BUZZ_UTF8_LOG_INTERVAL) {
        return;
    }

    @file_put_contents($lock_file, $now, LOCK_EX);

    $log = sprintf(
        "[%s] UTF8 deprecation observed: %s @ %s:%d\n",
        gmdate('Y-m-d H:i:s'),
        $message,
        $file ?? 'unknown',
        $line ?? 0
    );

    @file_put_contents(
        $log_dir . 'debug-utf8.log',
        $log,
        FILE_APPEND | LOCK_EX
    );
}

if (!function_exists('seems_utf8')) {
    function seems_utf8($str) {
        return function_exists('mb_check_encoding')
            ? mb_check_encoding($str, 'UTF-8')
            : wp_is_valid_utf8($str);
    }
}