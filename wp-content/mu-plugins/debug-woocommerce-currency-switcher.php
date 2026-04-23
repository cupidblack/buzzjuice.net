<?php
/**
 * debug-woocommerce-currency-switcher.php
 *
 * Logs WOOCS session failures + optionally suppresses them from WP/PHP logs
 */

if (defined('BZJ_WOOCS_LOADED')) return;
define('BZJ_WOOCS_LOADED', true);

define('BZJ_WOOCS_LOG_INTERVAL', 1);

/**
 * Optional suppression toggle (like Elementor/MyCRED pattern)
 */
if (!defined('BZJ_WOOCS_SUPPRESS')) {
    define('BZJ_WOOCS_SUPPRESS', true);
}

function bzj_woocs_log_dir() {
    $dir = ABSPATH . '/data/logs/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function bzj_woocs_log_once($msg, $file, $line) {

    $dir = bzj_woocs_log_dir();
    $lock = $dir . 'debug-woocs.lock';

    $now = time();
    $last = file_exists($lock) ? (int) @file_get_contents($lock) : 0;

    if (($now - $last) < BZJ_WOOCS_LOG_INTERVAL) {
        return;
    }

    @file_put_contents($lock, $now, LOCK_EX);

    $log = sprintf(
        "[%s] WOOCS session_start failure: %s @ %s:%d\nACTION: fix session.save_path or reapply storage.php patch\n",
        gmdate('Y-m-d H:i:s'),
        $msg,
        $file ?? 'unknown',
        $line ?? 0
    );

    @file_put_contents($dir . 'debug-woocs.log', $log, FILE_APPEND | LOCK_EX);
}