<?php
/**
 * Plugin Name: Imunify Security Bot Protection (mu-plugin)
 * Description: Pre-WordPress bot classification + rate limiting. Installed by the Imunify Security plugin.
 * Version: 1.0.0
 * Author: CloudLinux
 */

if ( ! defined( 'ABSPATH' ) ) { return; }

// Load + run the bot-protection pipeline, catching every Throwable
// so PHP 7+ Error types (e.g. class-not-found / type errors) don't
// escape. PHP 5.6 only has Exception, so we branch on whether the
// Throwable interface is available, matching SafeInclude::load().
if ( interface_exists( 'Throwable' ) ) {
    try {
        $imunify_security_bot_loader = WP_PLUGIN_DIR . '/imunify-security/inc/App/Bot/MuLoader.php';
        if ( file_exists( $imunify_security_bot_loader ) ) {
            require_once $imunify_security_bot_loader;
            if ( class_exists( '\\CloudLinux\\Imunify\\App\\Bot\\MuLoader' ) ) {
                \CloudLinux\Imunify\App\Bot\MuLoader::run();
            }
        }
    } catch ( \Throwable $t ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        // Fail-open: any error in bot protection passes the request through.
    }
} else {
    try {
        $imunify_security_bot_loader = WP_PLUGIN_DIR . '/imunify-security/inc/App/Bot/MuLoader.php';
        if ( file_exists( $imunify_security_bot_loader ) ) {
            require_once $imunify_security_bot_loader;
            if ( class_exists( '\\CloudLinux\\Imunify\\App\\Bot\\MuLoader' ) ) {
                \CloudLinux\Imunify\App\Bot\MuLoader::run();
            }
        }
    } catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        // Fail-open: any error in bot protection passes the request through.
    }
}
