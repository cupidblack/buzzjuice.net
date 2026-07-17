<?php
/**
 * Free toolkit add-on slug expansion for unified registry.
 *
 * @package mycred-toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mycred_toolkit_expand_active_addon_slugs' ) ) :
	/**
	 * @param array $slugs Active slugs.
	 * @return array
	 */
	function mycred_toolkit_expand_active_addon_slugs( $slugs ) {
		if ( ! is_array( $slugs ) ) {
			$slugs = array();
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( in_array( 'buy-creds', $slugs, true ) ) {
			if ( is_plugin_active( 'woosquare/woocommerce-square-integration.php' )
				|| is_plugin_active( 'woosquare-pro/woocommerce-square-integration.php' )
				|| is_plugin_active( 'woosquare-premium/woocommerce-square-integration.php' ) ) {
				$slugs[] = 'mycred-square';
			}
		}

		return array_values( array_unique( $slugs ) );
	}
endif;

add_filter( 'mycred_expand_active_addon_slugs', 'mycred_toolkit_expand_active_addon_slugs' );
