<?php
/**
 * debug-mycred.php
 *
 * Safety net for myCRED "rows" structure errors. Logs if plugin patch is lost on update.
 */

if (defined('DEBUG_MYCREDBUG_MU_LOADED')) return;
define('DEBUG_MYCREDBUG_MU_LOADED', true);

if (!defined('DEBUG_MYCREDBUG_LOG_INTERVAL')) {
    define('DEBUG_MYCREDBUG_LOG_INTERVAL', 12345); // seconds
}

function debug_mycred_log_dir() {
    $dir = ABSPATH . '/data/logs/';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    return $dir;
}

function debug_mycred_log_once($message, $file, $line) {
    $dir = debug_mycred_log_dir();
    $lock_file = $dir . 'debug-mycred.lock';
    $now = time();
    $last_logged = file_exists($lock_file) ? (int) @file_get_contents($lock_file) : 0;
    if (($now - $last_logged) < DEBUG_MYCREDBUG_LOG_INTERVAL) return;
    @file_put_contents($lock_file, $now, LOCK_EX);
    $log = sprintf(
        "[%s] myCRED 'rows' property bug detected: %s @ %s:%d\n",
        gmdate('Y-m-d H:i:s'),
        $message,
        $file ?? 'unknown',
        $line ?? 0
    );
    @file_put_contents($dir . 'debug-mycred.log', $log, FILE_APPEND | LOCK_EX);
}