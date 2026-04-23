<?php
/**
 * debug-elementor.php (PASSIVE MODULE)
 * 
 * No error handlers, no runtime dependencies.
 * Only contains optional logging helpers if needed by central router.
 */

if (!defined('ABSPATH')) exit;

if (defined('DEBUG_ELEMENTOR_MU_LOADED')) return;
define('DEBUG_ELEMENTOR_MU_LOADED', true);

if (!defined('DEBUG_ELEMENTOR_LOG_INTERVAL')) {
    define('DEBUG_ELEMENTOR_LOG_INTERVAL', 300);
}

/**
 * Ensure log directory exists
 */
function debug_elementor_log_dir() {
    $dir = ABSPATH . '/data/logs/';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    return $dir;
}

/**
 * Optional: rate-limited logger (ONLY called by central runtime-error-guard.php)
 */
function debug_elementor_log_once($message, $file = null, $line = null) {

    $dir = debug_elementor_log_dir();
    $lock_file = $dir . 'debug-elementor.lock';

    $now = time();
    $last_logged = file_exists($lock_file)
        ? (int) @file_get_contents($lock_file)
        : 0;

    if (($now - $last_logged) < DEBUG_ELEMENTOR_LOG_INTERVAL) {
        return;
    }

    @file_put_contents($lock_file, $now, LOCK_EX);

    $log = sprintf(
        "[%s] Elementor warning: %s @ %s:%d\n",
        gmdate('Y-m-d H:i:s'),
        $message,
        $file ?? 'unknown',
        $line ?? 0
    );

    @file_put_contents(
        $dir . 'debug-elementor.log',
        $log,
        FILE_APPEND | LOCK_EX
    );
}