<?php
/**
 * debug-mycred-bp-charges.php
 * Passive diagnostic module (no active error handling)
 */

if (!defined('ABSPATH')) exit;

/**
 * Optional: plugin-specific logging helpers (ONLY if needed)
 * These must NOT be registered as error handlers.
 */

if (!function_exists('mycred_bp_charges_log_note')) {
    function mycred_bp_charges_log_note($message) {
        $file = ABSPATH . '/data/logs/debug-mycred-bp-charges.log';

        $log = sprintf(
            "[%s] %s\n",
            gmdate('Y-m-d H:i:s'),
            $message
        );

        @file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Example usage (manual calls only):
 * mycred_bp_charges_log_note('Plugin loaded');
 */