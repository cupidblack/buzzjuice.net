<?php
/**
 * runtime-error-guard.php
 * ONE global error handler for all plugin suppression and logging.
 * Place this file in /wp-content/mu-plugins/
 */

if (defined('BZJ_RUNTIME_ERROR_GUARD')) return;
define('BZJ_RUNTIME_ERROR_GUARD', true);

if (!defined('BZJ_LOG_DIR')) {
    define('BZJ_LOG_DIR', ABSPATH . '/data/logs/');
}

define('BZJ_LOG_INTERVAL', 300); // seconds: per error/per type

if (!is_dir(BZJ_LOG_DIR)) {
    @mkdir(BZJ_LOG_DIR, 0777, true);
}

function bzj_log_once($file, $message, $errfile, $errline) {
    $lock = BZJ_LOG_DIR . md5($file) . '.lock';
    $now  = time();
    $last = file_exists($lock) ? (int) @file_get_contents($lock) : 0;

    if (($now - $last) < BZJ_LOG_INTERVAL) return;

    @file_put_contents($lock, $now, LOCK_EX);

    $log = sprintf(
        "[%s]\n%s\nFile: %s:%d\n\n",
        gmdate('Y-m-d H:i:s'),
        $message,
        $errfile ?? 'unknown',
        $errline ?? 0
    );

    @file_put_contents(BZJ_LOG_DIR . $file, $log, FILE_APPEND | LOCK_EX);
}

set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
    if (!is_string($errstr)) return false;

    // --- UTF8 deprecation ("seems_utf8 is deprecated")
    if (
        ($errno === E_USER_DEPRECATED || ($errno & E_USER_DEPRECATED)) &&
        stripos($errstr, 'seems_utf8') !== false
    ) {
        bzj_log_once('debug-utf8.log', $errstr, $errfile, $errline);
        return true;
    }

    // --- Elementor "Undefined array key 'topic'" warning
    if (
        ($errno === E_WARNING || $errno === E_NOTICE) &&
        strpos($errstr, 'Undefined array key "topic"') !== false &&
        strpos((string)$errfile, 'elementor') !== false
    ) {
        bzj_log_once('debug-elementor.log', $errstr, $errfile, $errline);
        return true;
    }

    // --- WooCommerce Currency Switcher session_start errors (optional)
    if (
        strpos($errstr, 'session_start():') !== false &&
        strpos((string)$errfile, 'woocommerce-currency-switcher') !== false
    ) {
        bzj_log_once('debug-woocs.log', $errstr, $errfile, $errline);
        return true;
    }

    // --- myCred BP Charges translation + "headers already sent"
    if (
        strpos($errstr, 'bp_charge') !== false ||
        strpos($errstr, 'headers already sent') !== false ||
        strpos((string)$errfile, 'mycred-bp-charges') !== false
    ) {
        bzj_log_once('debug-bp-charges.log', $errstr, $errfile, $errline);
        return true;
    }

    // Not a handled custom error: Let default PHP error handler process it.
    return false;
});