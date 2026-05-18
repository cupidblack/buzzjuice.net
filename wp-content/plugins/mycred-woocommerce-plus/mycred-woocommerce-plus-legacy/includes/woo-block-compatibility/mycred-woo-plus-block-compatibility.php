<?php
/**
 * MyCred_Woo_Blocks_Compatibility class
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MyCred_Woo_Plus_Blocks_Compatibility' ) ) {

	/**
	 * WooCommerce Blocks Compatibility.
	 */
	class MyCred_Woo_Plus_Blocks_Compatibility {

		public function __construct() {
			
			// register mycred woocommerce plus blocks
			add_action( 'woocommerce_blocks_loaded', array( $this, 'mycred_wooplus_blocks') );

		}

		public function mycred_wooplus_blocks() {

			require_once MYCRED_WOOPLUS_BLOCKS_DIR . 'mycred-woo-plus-block-store-api.php';
			require_once MYCRED_WOOPLUS_BLOCKS_DIR . 'mycred-woo-plus-checkout-block-integration.php';
			require_once MYCRED_WOOPLUS_BLOCKS_DIR . 'mycred-woo-plus-cart-block-integration.php';
			$MyCred_Woo_Plus_Extend_Store_Endpoint = new MyCred_Woo_Plus_Extend_Store_Endpoint();
			$MyCred_Woo_Plus_Extend_Store_Endpoint->init();

			/**
			 * Registers myCred WooCommerce Plus Cart Block Integration.
			 */
			add_action(
				'woocommerce_blocks_cart_block_registration',
				function( $integration_registry ) {
					$integration_registry->register( new MyCred_Woo_Plus_Cart_Blocks_Integration() );
				}
			);

			/**
			 * Registers myCred WooCommerce Plus Checkout Block Integration.
			 */
			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function( $integration_registry ) {
					$integration_registry->register( new MyCred_Woo_Plus_Checkout_Blocks_Integration() );
				}
			);
		}

	}

	new MyCred_Woo_Plus_Blocks_Compatibility();
}
