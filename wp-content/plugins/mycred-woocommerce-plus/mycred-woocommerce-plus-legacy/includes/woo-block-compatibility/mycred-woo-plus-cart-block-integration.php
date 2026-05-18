<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class MyCred_Woo_Plus_Cart_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'mycredwooplus';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'mycred-woo-plus-order-total', 'mycred-woo-plus-partial-payment' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'mycred-woo-plus-editor-cart-block' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$data['reward_cart_product_total'] = get_option( 'reward_cart_product_total', 'no' );
		$data['reward_points_global'] = get_option( 'reward_points_global', 'no' );
		return $data;
	}

	/**
	 * Register scripts for delivery date block editor.
	 *
	 * @return void
	 */
	public function register_block_editor_scripts() {
		$script_asset_path = plugins_url( '/build/index.asset.php', MYCRED_WOOPLUS_THIS );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		$script_url = plugins_url( '/build/index.js', MYCRED_WOOPLUS_THIS );

		wp_register_script(
			'mycred-woo-plus-editor-cart-block',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	   
	}

	/**
	 * Register scripts for frontend block.
	 *
	 * @return void
	 */
	public function register_block_frontend_scripts() {

		 /**
		 * Register JS for Adding MyCred WooCommerce Plus Partial Payment Block
		 */
		$script_asset_path  = plugins_url( '/build/cart/cart-order-total/frontend.asset.php', MYCRED_WOOPLUS_THIS );
		$script_asset       = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);
		
		$script_url = plugins_url( '/build/cart/cart-order-total/frontend.js', MYCRED_WOOPLUS_THIS );
		wp_register_script(
			'mycred-woo-plus-order-total',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		/**
		 * Register JS for Adding MyCred WooCommerce Plus Partial Payment Block
		 */
		$script_asset_path  = plugins_url( '/build/cart/partial-payment-and-coupon/frontend.asset.php', MYCRED_WOOPLUS_THIS );
		$script_asset       = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);
		
		$script_url = plugins_url( '/build/cart/partial-payment-and-coupon/frontend.js', MYCRED_WOOPLUS_THIS );
		wp_register_script(
			'mycred-woo-plus-partial-payment',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		/**
		 * Register CSS for Styling field to Checkout Block
		 */
		$style_url = plugins_url( '/build/cart/cart-order-total/frontend.css', MYCRED_WOOPLUS_THIS );
		wp_enqueue_style(
			'mycred-woo-plus-frontend-css',
			$style_url,
			array(),
			MYCRED_WOOPLUS_VERSION
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return MYCRED_WOOPLUS_VERSION;
	}
}
