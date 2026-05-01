<?php
/**
 * Buzzjuice Central Error Router & Logger
 * - Plugin errors routed to /data/logs/ only.
 * - "Unrouted" errors go to standard log (debug.log).
 * - All plugin routines/features should NOT set their own error handlers!
 */

if (!defined('ABSPATH')) exit;

if (defined('BZJ_RUNTIME_ERROR_GUARD')) return;
define('BZJ_RUNTIME_ERROR_GUARD', true);

if (!defined('BZJ_LOG_DIR')) define('BZJ_LOG_DIR', ABSPATH . '/data/logs/');
if (!is_dir(BZJ_LOG_DIR)) @mkdir(BZJ_LOG_DIR, 0777, true);

// Per-plugin log intervals (seconds)
$BZJ_INTERVALS = [
    'utf8'         => 200,
    'elementor'    => 180,
    'woocs'        => 60,
    'bp-charges'   => 30,
    // add more as needed
];

// Utility for rotating large log files (default 5MB)
function bzj_rotate_log($filename, $maxSize = 5 * 1024 * 1024) {
    if (file_exists($filename) && filesize($filename) >= $maxSize) {
        @rename($filename, $filename . '.' . date('Ymd_His'));
    }
}

// Stack trace builder
function bzj_stack_trace($skip = 0) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $out = '';
    foreach ($trace as $i => $frame) {
        if ($i < $skip) continue;
        $file = $frame['file'] ?? '[internal]';
        $line = $frame['line'] ?? '';
        $func = '';
        if (isset($frame['class']) || isset($frame['function'])) {
            $parts = [];
            if (isset($frame['class']))   $parts[] = $frame['class'];
            if (isset($frame['type']))    $parts[] = $frame['type'];
            if (isset($frame['function']))$parts[] = $frame['function'] . '()';
            $func = ' ' . implode('', $parts);
        }
        $out .= "\n" . $file . ' -- line ' . $line . $func;
    }
    return $out;
}

// Standard log to PHP (ALWAYS NO DUPLICATES; called only for unhandled errors)
function bzj_log_standard($errstr, $errfile, $errline) {
    $log  = "\n==========";
    $log .= "\nERROR: $errstr";
    $log .= "\nIn file: $errfile on line $errline";
    $log .= "\nSTACK TRACE:" . bzj_stack_trace(2);
    $log .= "\n==========";
    error_log($log);
}

// Plugin-specific rate-limited logger
function bzj_log_once($logfile, $errstr, $errfile, $errline, $interval) {
    $fpath = BZJ_LOG_DIR . $logfile;
    $lockf = $fpath . '.lock';
    $now = time();
    $last = file_exists($lockf) ? (int) @file_get_contents($lockf) : 0;
    if (($now - $last) < $interval) return;
    @file_put_contents($lockf, $now, LOCK_EX);
    bzj_rotate_log($fpath);
    $log  = "[" . gmdate('Y-m-d H:i:s') . "]";
    $log .= "\nERROR: " . ($errstr ?? 'Unknown error');
    $log .= "\nIn file: " . ($errfile ?? 'unknown') . ' on line ' . ($errline ?? '0');
    $log .= "\nSTACK TRACE:" . bzj_stack_trace(3);
    $log .= "\n==========\n";
    @file_put_contents($fpath, $log, FILE_APPEND | LOCK_EX);
}

/**
 * Safe array getter (system contract enforcement)
 */
function bzj_arr_get($arr, $key, $default = []) {
    return (is_array($arr) && array_key_exists($key, $arr))
        ? $arr[$key]
        : $default;
}

/**
 * Block early translation loading violations
 */
add_filter('load_textdomain_mofile', function ($mofile, $domain) {

    if ($domain === 'bp_charge' && !did_action('init')) {
        return false; // blocks early loading attempts
    }

    return $mofile;

}, 1, 2);

// ACTUAL HANDLER
set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) use ($BZJ_INTERVALS) {
    if (!is_string($errstr)) return false;

    // --- ROUTED ERRORS (match, log ONLY to plugin logs)
    // 1. UTF8 deprecated
    if (
        ($errno === E_USER_DEPRECATED || ($errno & E_USER_DEPRECATED)) &&
        stripos($errstr, 'seems_utf8') !== false
    ) {
        bzj_log_once('debug-utf8.log', $errstr, $errfile, $errline, $BZJ_INTERVALS['utf8']);
        return true; // Suppress
    }

    // 2. Elementor "Undefined array key 'topic'"
    if (
        ($errno === E_WARNING || $errno === E_NOTICE) &&
        strpos($errstr, 'Undefined array key "topic"') !== false &&
        strpos((string)$errfile, 'elementor') !== false
    ) {
        bzj_log_once('debug-elementor.log', $errstr, $errfile, $errline, $BZJ_INTERVALS['elementor']);
        return true; // Suppress
    }

    // 3. WOOCS session_start errors
    if (
        (strpos($errstr, 'session_start():') !== false ||
         strpos($errstr, 'Failed to read session data') !== false ||
         strpos($errstr, 'Session cannot be started after headers have already been sent') !== false
        )
        && strpos((string)$errfile, 'woocommerce-currency-switcher') !== false
    ) {
        bzj_log_once('debug-woocs.log', $errstr, $errfile, $errline, $BZJ_INTERVALS['woocs']);
        return true; // Suppress
    }

    // 4. myCred/BP charges/special cases
    if (
        strpos($errstr, 'bp_charge') !== false ||
        strpos($errstr, 'headers already sent') !== false ||
        strpos((string)$errfile, 'mycred-bp-charges') !== false
    ) {
        bzj_log_once('debug-bp-charges.log', $errstr, $errfile, $errline, $BZJ_INTERVALS['bp-charges']);
        return true; // Suppress
    }
    
    // Add before general log fallback:
    if (
        strpos($errstr, 'Cron reschedule event error') !== false ||
        strpos($errstr, 'Cron unschedule event error') !== false
    ) {
        bzj_log_once('debug-cron.log', $errstr, $errfile, $errline, 600); // 10 minutes
        return true;
    }
    
    if (
        strpos($errfile, 'mycred-learndash-leaderboard.php') !== false &&
        strpos($errstr, 'foreach() argument must be of type array|object, string given') !== false
    ) {
        bzj_log_once('debug-mycred-toolkit.log', $errstr, $errfile, $errline, 30);
        return true;
    } elseif (
        strpos((string)$errfile, 'mycred-learndash-leaderboard.php') !== false &&
        strpos($errstr, 'foreach() argument must be of type array|object') !== false
    ) {
        bzj_log_once('debug-mycred-toolkit.log', $errstr, $errfile, $errline, 30);
        return true;
    }
    
    if (strpos($errstr, 'load_textdomain_just_in_time') !== false) {
        bzj_log_once('debug-mycred-bp-charges.log', $errstr, $errfile, $errline, 600);
        return true;
    }
    
    if (
        strpos($errfile, 'mycred-learndash') !== false &&
        (
            strpos($errstr, 'Undefined array key') !== false ||
            strpos($errstr, 'Trying to access array offset') !== false
        )
    ) {
        bzj_log_once('debug-mycred-toolkit.log', $errstr, $errfile, $errline, 60);
        return true;
    }
    
    if (
        strpos($errfile, 'kses.php') !== false &&
        strpos($errstr, 'Passing null to parameter') !== false &&
        strpos($errstr, 'preg_replace') !== false
    ) {
        bzj_log_once('debug-kses.log', $errstr, $errfile, $errline, 60);
        return true;
    }
    
    if (strpos($errfile, 'mustache') !== false || strpos($errfile, 'WP_CLI/Dispatcher') !== false) {
        bzj_log_once('debug-wp-cli.log', $errstr, $errfile, $errline, 3600);
        return true;
    }
    if (strpos($errstr, 'dynamic property') !== false) {
        bzj_log_once('debug-php-deprecated.log', $errstr, $errfile, $errline, 900);
        return true;
    }
    
    if (
        strpos($errfile, 'bb-forums.php') !== false &&
        strpos($errstr, 'str_replace(): Passing null to parameter') !== false
    ) {
        bzj_log_once('debug-buddyboss-theme.log', $errstr, $errfile, $errline, 60);
        return true;
    }
    
    if (
    strpos($errfile, 'ld-course-steps-functions.php') !== false &&
        strpos($errstr, 'Attempt to read property "post_type" on null') !== false
    ) {
        bzj_log_once('debug-sfwd-lms.log', $errstr, $errfile, $errline, 60);
        return true;
    }

    if (
        strpos($errfile, 'filter/layout.php') !== false &&
        (strpos($errstr, 'Undefined array key') !== false || strpos($errstr, 'array offset') !== false)
    ) {
        bzj_log_once('debug-sfwd-lms.log', $errstr, $errfile, $errline, 60);
        return true;
    }
    
    // Add new categories below as needed. Example:
    // if (strpos($errstr, 'new-error-string') !== false) { bzj_log_once('myplugin', ...); return true; }
    
    /* if (strpos($errstr, 'new pattern') !== false) {
        bzj_log_once('debug-new.log', $errstr, $errfile, $errline);
        return true;
    } */

    // --- DEFAULT: UNROUTED ERROR, STANDARD PHP LOG ONLY
    bzj_log_standard($errstr, $errfile, $errline);
    return false;
}, E_ALL);