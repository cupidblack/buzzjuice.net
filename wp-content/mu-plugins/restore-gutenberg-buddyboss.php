<?php
/*
Plugin Name: Restore Gutenberg & Neutralize BuddyBoss Editor Assets
Description: MU-plugin to restore Gutenberg block controls (dimensions, spacing, padding) and dequeue conflicting BuddyBoss/BP/BB editor assets. Includes live admin debug and optional aggressive mode.
Version: 1.2
Author: cherrybuzzjuice (adapted)
*/

defined( 'WPINC' ) || die;

/**
 * ----- CONFIG -----
 * Set true to aggressively dequeue any handle starting with 'bp-' or containing 'buddyboss'.
 */
if ( ! defined( 'RBB_AGGRESSIVE_MODE' ) ) define( 'RBB_AGGRESSIVE_MODE', false );

/**
 * Set true to enable console debug of enqueued BuddyBoss handles.
 */
if ( ! defined( 'RBB_DEBUG_CONSOLE' ) ) define( 'RBB_DEBUG_CONSOLE', true );

/**
 * ----- BLACKLIST HANDLES -----
 * Add any new handles seen in REST debug output here.
 */
function rbb_get_blacklist_style_handles() {
    return array(
        'bp-medium-editor',
        'bp-medium-editor-beagle',
        'bp-admin-common-css',
        'bp-customizer-controls',
        'bp-hello-css',
        'bb-pro-enqueue-hello-style',
        'bb-access-control-admin',
        'bb-onesignal-admin',
        'bb-tutorlms-admin',
        'bb-readylaunch-admin-style',
        'bb-icons-rl-css',
        'bbp-admin-css',
        'buddyboss-admin-style',
        'bb_theme_block-buddypanel-style-css',
        'icon-picker',
        'buddyboss_legacy',
        'bp-select2',
        'jquery-datetimepicker',
        'bp-media-videojs-css',
        'bp-mentions-css',
        'bp-nouveau',
        'bp-nouveau-bb-icons',
        'bp-nouveau-icons-map',
        'redux-editor-styles',
    );
}

function rbb_get_blacklist_script_handles() {
    return array(
        'bp-api-request',
        'bp-medium-editor',
        'bp-select2',
        'bb-readylaunch-admin-script',
        'bb-pro-enqueue-hello-script',
        'bb-pro-admin-script',
        'bb-access-control-admin',
        'bb-tutorlms-admin',
        'bp-customizer-controls',
        'bp-hello-js',
        'bp-confirm',
        'bp-mentions',
        'bp-admin',
        'bp-admin-common-js',
        'bp-fitvids-js',
        'buddyboss-theme-mpt-featured-image',
        'buddyboss-theme-mpt-featured-image-modal',
        'icon-picker',
    );
}

/**
 * ----- RESTORE GUTENBERG SUPPORTS -----
 */
add_action( 'after_setup_theme', function() {
    add_theme_support( 'align-wide' );
//    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'custom-spacing' );
    add_theme_support( 'editor-font-sizes', array(
        array( 'name' => 'Small',  'size' => 13, 'slug' => 'small' ),
        array( 'name' => 'Normal', 'size' => 16, 'slug' => 'normal' ),
        array( 'name' => 'Large',  'size' => 24, 'slug' => 'large' ),
    ) );
}, 1 );

add_filter( 'register_block_type_args', function( $args, $name ) {
    if ( ! isset( $args['supports'] ) || ! is_array( $args['supports'] ) ) $args['supports'] = array();
    $defaults = array(
        'spacing'         => true,
        'customClassName' => true,
        'align'           => true,
        'html'            => true,
        'color'           => true,
        'typography'      => true,
        'anchor'          => true,
    );
    foreach ( $defaults as $k => $v ) {
        if ( ! array_key_exists( $k, $args['supports'] ) ) $args['supports'][$k] = $v;
    }
    return $args;
}, 20, 2 );

/**
 * ----- DEQUEUE BUDDYBOSS ASSETS -----
 */
add_action( 'enqueue_block_editor_assets', function() {
    if ( ! function_exists( 'is_admin' ) || ! is_admin() ) return;

    global $wp_styles, $wp_scripts;

    $style_handles  = rbb_get_blacklist_style_handles();
    $script_handles = rbb_get_blacklist_script_handles();

    // Aggressive mode
    if ( RBB_AGGRESSIVE_MODE ) {
        foreach ( $wp_styles->registered as $h => $d ) {
            if ( strpos($h,'bp-')===0 || stripos($h,'buddyboss')!==false ) $style_handles[] = $h;
        }
        foreach ( $wp_scripts->registered as $h => $d ) {
            if ( strpos($h,'bp-')===0 || stripos($h,'buddyboss')!==false ) $script_handles[] = $h;
        }
    }

    foreach ( $style_handles as $h ) {
        if ( wp_style_is( $h, 'enqueued' ) || wp_style_is( $h, 'registered' ) ) {
            wp_dequeue_style( $h );
            wp_deregister_style( $h );
        }
    }
    foreach ( $script_handles as $h ) {
        if ( wp_script_is( $h, 'enqueued' ) || wp_script_is( $h, 'registered' ) ) {
            wp_dequeue_script( $h );
            wp_deregister_script( $h );
        }
    }

    // Ensure Gutenberg core block styles
    if ( ! wp_style_is( 'wp-block-library', 'enqueued' ) ) wp_enqueue_style( 'wp-block-library' );
    if ( ! wp_style_is( 'wp-edit-blocks', 'enqueued' ) ) wp_enqueue_style( 'wp-edit-blocks' );
}, 9 );

/**
 * ----- ADMIN FOOTER DEBUG -----
 */
add_action('admin_footer', function() {
    if( ! current_user_can('manage_options') || ! function_exists('get_current_screen') ) return;
    $screen = get_current_screen();
    if( $screen && $screen->is_block_editor && RBB_DEBUG_CONSOLE ) {
        global $wp_styles, $wp_scripts;
        $styles = array_keys($wp_styles->registered);
        $scripts = array_keys($wp_scripts->registered);
        echo '<script>console.groupCollapsed("BuddyBoss Enqueued Handles (Block Editor)");';
        echo 'console.log("Styles:",', json_encode($styles),');';
        echo 'console.log("Scripts:",', json_encode($scripts),');';
        echo 'console.groupEnd();</script>';
    }
});

/**
 * ----- ADMIN NOTICE -----
 */
add_action( 'admin_notices', function() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( defined( 'WP_CONTENT_DIR' ) && strpos( __FILE__, WP_CONTENT_DIR . '/mu-plugins' ) === false ) {
        echo '<div class="notice notice-info is-dismissible"><p><strong>Restore Gutenberg Controls:</strong> Place this file in <code>wp-content/mu-plugins/</code> for earliest load and full effect.</p></div>';
    }
});
