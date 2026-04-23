<?php
/**
 * Plugin Name: Buzzjuice Members Page Gate (Global)
 * Description: Restrict /members page to users allowed by the global subscription gating helper.
 * Author: Koware / Buzzjuice
 * Version: 1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include global gating helper if not already loaded
$helper_path = ABSPATH . '/shared/subscription_gate_helpers.php';
if ( ! function_exists( 'bj_user_has_active_subscription' ) && file_exists( $helper_path ) ) {
    require_once $helper_path;
}

/**
 * Gate the /members page using global subscription helper
 */
function bj_gate_members_page_global() {

    if ( is_admin() ) return;

    // Prevent CLI execution
    if ( php_sapi_name() === 'cli' ) return;

    // Safe request parsing
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

    $path = '';
    if ($request_uri !== '') {
        $parsed = parse_url($request_uri, PHP_URL_PATH);
        $path = is_string($parsed) ? $parsed : '';
    }

    $request_path = trim($path, '/');

    // Use BuddyBoss detection if available
    if ( function_exists('bp_is_members_directory') ) {
        if ( ! bp_is_members_directory() ) return;
    } else {
        if ( $request_path !== 'members' ) return;
    }

    // Safe user handling
    $user = is_user_logged_in() ? wp_get_current_user() : null;

    if ( $user && user_can( $user, 'administrator' ) ) return;

    // Unified gating logic
    $has_access = function_exists('bz_user_can')
        ? bz_user_can('primary', $user)
        : false;

    if ( ! $has_access ) {

        $redirect_to = rawurlencode('go-pro');
        $redirect_url = "https://buzzjuice.net/streams/ww-sso-bridge.php?redirect_to={$redirect_to}";

        if ( ! headers_sent() ) {
            wp_redirect( $redirect_url );
            exit;
        }
    }
}

add_action( 'template_redirect', 'bj_gate_members_page_global', 1 );