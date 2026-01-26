<?php
/**
 * Plugin Name: BuddyBoss Compatibility Fixes
 * Description: Suppress specific PHP deprecation/warning noise from BuddyBoss vendor code and Elementor, and ensure buddyboss textdomain is loaded on init.
 * Version: 1.0
 * Author: Copilot / You
 *
 * Notes:
 * - This mu-plugin intentionally suppresses very specific notices originating from:
 *     - vendor/alchemy/binary-driver/.../Configuration.php (PHP 8.x ArrayAccess/Iterator deprecation notices)
 *     - early textdomain "Translation loading for the <code>buddyboss</code> domain was triggered too early" notice
 *     - Elementor "Undefined array key \"topic\"" warning coming from elementor/includes/conditions.php
 * - The suppression is narrowly targeted by message content and file path so other errors remain visible.
 * - Also attempts to load the 'buddyboss' textdomain on init (priority 5).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Early error handler to filter specific noisy notices coming from third-party files.
 * We register this from an mu-plugin so it's applied before most plugins are loaded.
 */
$previous_error_handler = set_error_handler(
    function ( $errno, $errstr, $errfile, $errline ) use ( &$previous_error_handler ) {
        // Normalize message/file (make sure they're strings)
        $errstr_s = is_string( $errstr ) ? $errstr : '';
        $errfile_s = is_string( $errfile ) ? $errfile : '';

        // 1) Suppress the "Translation loading for the <code>buddyboss</code> domain was triggered too early" doing_it_wrong notice.
        //    The WP core emits that via _doing_it_wrong when textdomain loading happens before init.
        if ( ( $errno & ( E_USER_NOTICE | E_USER_WARNING | E_NOTICE ) ) && strpos( $errstr_s, 'Translation loading for the <code>buddyboss</code> domain' ) !== false ) {
            return true; // handled, do not pass to default handler or log
        }

        // 2) Suppress deprecation notices from the alchemy/binary-driver Configuration class (method return type notices).
        //    Match by file path fragment to avoid hiding unrelated deprecations.
        if ( ( $errno & E_DEPRECATED ) && strpos( $errfile_s, '/vendor/alchemy/binary-driver/src/Alchemy/BinaryDriver/Configuration.php' ) !== false ) {
            return true;
        }

        // 3) Suppress "Undefined array key \"topic\"" coming from Elementor conditions (harmless in many setups).
        if ( ( $errno & ( E_WARNING | E_NOTICE | E_USER_NOTICE ) )
            && strpos( $errstr_s, 'Undefined array key "topic"' ) !== false
            && strpos( $errfile_s, 'elementor/includes/conditions.php' ) !== false
        ) {
            return true;
        }

        // Not one of the specific suppressed messages: forward to previous handler if present.
        if ( is_callable( $previous_error_handler ) ) {
            return call_user_func( $previous_error_handler, $errno, $errstr, $errfile, $errline );
        }

        // Fallback: return false to let PHP handle it normally.
        return false;
    }
);

/**
 * Load the buddyboss textdomain on init (very early priority).
 * This helps reduce "translation loading too early" warnings by ensuring domain is available by init.
 */
add_action(
    'init',
    function () {
        $domain = 'buddyboss';

        // If the domain is already loaded, nothing to do.
        if ( function_exists( 'is_textdomain_loaded' ) && is_textdomain_loaded( $domain ) ) {
            return;
        }

        // Try theme languages folder first (buddyboss-theme), then fallback to default.
        $theme_lang_dir = get_template_directory() . '/languages';
        if ( is_dir( $theme_lang_dir ) ) {
            load_theme_textdomain( $domain, $theme_lang_dir );
        } else {
            // This will attempt the default locations (wp-content/languages/plugins etc).
            load_theme_textdomain( $domain );
        }
    },
    5
);