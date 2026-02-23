<?php
/**
 * Load scripts for the admin area.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'wpcode_admin_scripts_pro', 15 );
add_action( 'admin_enqueue_scripts', 'wpcode_admin_scripts_global_pro' );
add_filter( 'wpcode_admin_js_data', 'wpcode_admin_js_data_pro' );

/**
 * Load admin scripts here.
 *
 * @return void
 */
function wpcode_admin_scripts_pro() {

	$current_screen = get_current_screen();

	if ( ! isset( $current_screen->id ) || false === strpos( $current_screen->id, 'wpcode' ) ) {
		return;
	}

	$admin_asset_file = WPCODE_PLUGIN_PATH . 'build/admin-pro.asset.php';

	if ( ! file_exists( $admin_asset_file ) ) {
		return;
	}

	$asset = require $admin_asset_file;

	wp_enqueue_style( 'wpcode-admin-pro-css', WPCODE_PLUGIN_URL . 'build/admin-pro.css', null, $asset['version'] );

	wp_enqueue_script( 'wpcode-admin-pro-js', WPCODE_PLUGIN_URL . 'build/admin-pro.js', $asset['dependencies'], $asset['version'], true );
}

/**
 * Load version-specific global scripts.
 *
 * @return void
 */
function wpcode_admin_scripts_global_pro() {
	wpcode_admin_scripts_global( 'pro' );

	if ( ! current_user_can( 'wpcode_activate_snippets' ) ) {
		return;
	}

	wp_localize_script(
		'wpcode-admin-global-js',
		'wpcode',
		apply_filters(
			'wpcode_admin_global_js_data',
			array(
				'nonce'                   => wp_create_nonce( 'wpcode_admin_global' ),
				'post_id'                 => get_the_ID(),
				'locations_number'        => wpcode_get_auto_insert_locations_with_number(),
				'library_refreshed_title' => __( 'Library Refreshed', 'insert-headers-and-footers' ),
				'library_refreshed_text'  => __( 'The library has been successfully refreshed.', 'insert-headers-and-footers' ),
			)
		)
	);
}

/**
 * Filter the data passed to the admin JS.
 *
 * @param array $data The data to pass to the admin JS.
 *
 * @return array
 */
function wpcode_admin_js_data_pro( $data ) {

	$keys_to_remove = array(
		'bonus',
		'price',
		'regular_price',
		'wpcode_lite_users',
		'purchased',
	);

	foreach ( $keys_to_remove as $key ) {
		unset( $data[ $key ] );
	}

	$data['library_refreshed_title'] = __( 'Library Refreshed', 'wpcode-premium' );
	$data['library_refreshed_text']  = __( 'The library has been successfully refreshed.', 'wpcode-premium' );

	return $data;
}
