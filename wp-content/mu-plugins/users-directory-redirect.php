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

    // Only run on frontend
    if ( is_admin() ) {
        return;
    }

    // Normalize request path
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $request_path = trim( parse_url( $request_uri, PHP_URL_PATH ), '/' );

    // Match /members (with or without trailing slash)
    if ( $request_path !== 'members' ) {
        return;
    }

    // Get current user
    $user = wp_get_current_user();

    // Allow admins always
    if ( user_can( $user, 'administrator' ) ) {
        return;
    }

    // Default: block access
    $has_access = false;

    // Use global subscription helper if available
    if ( function_exists( 'bj_user_has_active_subscription' ) ) {
        $has_access = bj_user_has_active_subscription( $user->ID );
    }

    // Redirect unauthorized users
    if ( ! $has_access ) {
        $redirect_to = urlencode( 'go-pro' );
        wp_redirect( "https://buzzjuice.net/streams/ww-sso-bridge.php?redirect_to={$redirect_to}" );
        exit;
    }
}

add_action( 'template_redirect', 'bj_gate_members_page_global', 1 );