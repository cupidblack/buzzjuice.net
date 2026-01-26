<?php
/**
 * buzz_utf8_compat.php
 *
 * MU-plugin to suppress the specific "seems_utf8 is deprecated" E_USER_DEPRECATED
 * notices emitted by WP 6.9+ while leaving other deprecation notices intact.
 *
 * Purpose:
 *  - Avoid large repeated debug-log entries coming from plugins (BuddyBoss BP-Forums in your trace)
 *    that still call the deprecated seems_utf8() function.
 *  - Non-invasive: does NOT modify plugin files; runs early as a mu-plugin.
 *
 * Install:
 *  - Create the directory wp-content/mu-plugins/ if it does not exist.
 *  - Place this file there. mu-plugins are loaded before normal plugins.
 *
 * Notes:
 *  - This only suppresses E_USER_DEPRECATED messages that mention "seems_utf8".
 *  - You should still update the plugin (BuddyBoss) to a version that calls wp_is_valid_utf8()
 *    or ask the vendor for a fix; this mu-plugin is a safe stop-gap to reduce log noise.
 */

if (defined('BUZZ_UTF8_COMPAT_LOADED')) {
    return;
}
define('BUZZ_UTF8_COMPAT_LOADED', true);

/**
 * If you want a tiny audit trail (only when WP_DEBUG is on) set this to true.
 * It writes to wp-content/buzz_utf8_compat.log
 */
if (!defined('BUZZ_UTF8_COMPAT_AUDIT')) define('BUZZ_UTF8_COMPAT_AUDIT', true);

/**
 * Filter / intercept E_USER_DEPRECATED messages early and selectively suppress ones
 * that refer to the deprecated function seems_utf8.
 *
 * We keep a reference to the previous handler and delegate non-matching errors to it.
 */
$buzz_prev_error_handler = set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) use (&$buzz_prev_error_handler) {
    // We only care about user-generated deprecation notices.
    // E_USER_DEPRECATED constant should exist in all current PHP versions.
    $is_user_deprecated = (defined('E_USER_DEPRECATED') && ($errno === E_USER_DEPRECATED || ($errno & E_USER_DEPRECATED)));
    if ($is_user_deprecated && is_string($errstr) && stripos($errstr, 'seems_utf8') !== false) {
        // Optional lightweight audit trail (avoid spamming WP_DEBUG_LOG)
        if (defined('WP_DEBUG') && WP_DEBUG && BUZZ_UTF8_COMPAT_AUDIT) {
            $log = sprintf("[%s] Suppressed deprecation: %s @ %s:%d\n", gmdate('Y-m-d H:i:s'), $errstr, $errfile ?? 'unknown', $errline ?? 0);
            @file_put_contents(WP_CONTENT_DIR . '/buzz_utf8_compat.log', $log, FILE_APPEND | LOCK_EX);
        }
        // Return true to indicate we've handled the error and stop propagation.
        return true;
    }

    // Delegate to previous handler if present
    if (is_callable($buzz_prev_error_handler)) {
        return call_user_func($buzz_prev_error_handler, $errno, $errstr, $errfile, $errline);
    }

    // Otherwise, return false so PHP's internal handler continues processing.
    return false;
});