<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class MyCred_Woo_Plus_Checkout_Blocks_Integration implements IntegrationInterface {

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
		return array( 'mycred-woo-plus-checkout-order-total', 'mycred-woo-plus-checkout-partial-payment' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'mycred-woo-plus-editor-checkout-block' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$options = get_option('mycred_pref_woo');
		$mycred_partial_payment = $options['mwp_partial_payments'];
		
		$options = get_option('mycred_pref_woo');
        $settings = $options['mwp_display_reward'];
		
		$data['reward_checkout_product_total'] = $settings['checkout_product_total'] == 1 ? 'yes' : 'no';
		$data['partial_payment_display_position'] = $mycred_partial_payment['position'];
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
			'mycred-woo-plus-editor-checkout-block',
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
		$this->register_script_with_fallback(
			'mycred-woo-plus-checkout-order-total',
			'/build/checkout/checkout-order-total/frontend.js',
			'/build/checkout/checkout-order-total/frontend.asset.php'
		);

		$this->register_script_with_fallback(
			'mycred-woo-plus-checkout-partial-payment',
			'/build/checkout/frontend.js',
			'/build/checkout/frontend.asset.php'
		);
	}

	/**
	 * Register a script with a fallback mechanism.
	 *
	 * @param string $handle
	 * @param string $script_path
	 * @param string $asset_path
	 */
	protected function register_script_with_fallback( $handle, $script_path, $asset_path ) {
		$script_asset_path = plugins_url( $asset_path, MYCRED_WOOPLUS_THIS );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		$script_url = plugins_url( $script_path, MYCRED_WOOPLUS_THIS );
		wp_register_script(
			$handle,
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Enqueue a style with a fallback mechanism.
	 *
	 * @param string $handle
	 * @param string $style_path
	 */
	protected function enqueue_style_with_fallback( $handle, $style_path ) {
		$style_url = plugins_url( $style_path, MYCRED_WOOPLUS_THIS );
		wp_enqueue_style(
			$handle,
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
