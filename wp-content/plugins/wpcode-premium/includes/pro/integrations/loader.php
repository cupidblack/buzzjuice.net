<?php
/**
 * This file is used to load integrations with other plugins.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'elementor/widgets/register', 'wpcode_elementor_register_widgets' );

add_action( 'elementor/editor/before_enqueue_scripts', 'wpcode_elementor_widgets_assets' );

add_action( 'wp_ajax_wpcode_get_snippet_shortcode_attributes', 'wpcode_integrations_get_snippet_shortcode_attributes' );


/**
 * Register the widgets.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager The Elementor widgets manager.
 *
 * @return void
 */
function wpcode_elementor_register_widgets( $widgets_manager ) {

	require_once WPCODE_PLUGIN_PATH . 'includes/pro/integrations/class-wpcode-elementor-widget-snippet.php';

	$widgets_manager->register( new WPCode_Elementor_Widget_Snippet() );
}

/**
 * Enqueue the assets for the Elementor widgets.
 *
 * @return void
 */
function wpcode_elementor_widgets_assets() {

	$admin_asset_file = WPCODE_PLUGIN_PATH . 'build/admin-integrations.asset.php';

	if ( ! file_exists( $admin_asset_file ) ) {
		return;
	}

	$asset = require $admin_asset_file;
	wp_enqueue_style( 'wpcode-admin-integrations-styles', WPCODE_PLUGIN_URL . 'build/admin-integrations.css', array(), $asset['version'] );

	wp_enqueue_script( 'wpcode-admin-integrations', WPCODE_PLUGIN_URL . 'build/admin-integrations.js', $asset['dependencies'], $asset['version'], true );

	wp_localize_script(
		'wpcode-admin-integrations',
		'wpcode_integrations',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpcode-integrations' ),
		)
	);
}

/**
 * Get the shortcode attributes for a snippet.
 *
 * @return void
 */
function wpcode_integrations_get_snippet_shortcode_attributes() {
	check_ajax_referer( 'wpcode-integrations' );

	$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;

	if ( ! $snippet_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid snippet ID.', 'wpcode-premium' ) ) );
	}

	$snippet = wpcode_get_snippet( $snippet_id );

	$shortcode_attributes = $snippet->get_shortcode_attributes();

	// Create an array where shortcode attributes values are keys with empty values.
	$shortcode_attributes = array_combine( array_values( $shortcode_attributes ), array_fill( 0, count( $shortcode_attributes ), '' ) );

	wp_send_json_success(
		array(
			'attributes' => $shortcode_attributes,
		)
	);
}
