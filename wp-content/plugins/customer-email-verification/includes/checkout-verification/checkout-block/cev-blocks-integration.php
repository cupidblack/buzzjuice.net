<?php
/**
 * Checkout Block Integration for Customer Email Verification PRO
 *
 * Handles WooCommerce Blocks integration for checkout verification.
 *
 * @package Customer_Email_Verification_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * CEV_Blocks_Integration Class
 *
 * Implements WooCommerce Blocks integration interface for checkout verification.
 */
class CEV_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @since 1.0.0
	 * @return string Integration name.
	 */
	public function get_name() {
		return 'cev-block';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function initialize() {
		// Feature gate: Only initialize if checkout verification is enabled.
		if ( 1 != cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 ) ) {
			return;
		}

		$this->register_shipping_workshop_block_frontend_scripts();
		$this->register_shipping_workshop_block_editor_scripts();
		$this->register_shipping_workshop_block_editor_styles();
		$this->register_main_integration();
	}


	/**
	 * Registers the main JS file required to add filters and Slot/Fills.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_main_integration() {
		$script_path = '/build/index.js';
		$style_path  = '/build/style-index.css';

		$script_url = plugins_url( $script_path, __FILE__ );
		$style_url  = plugins_url( $style_path, __FILE__ );

		$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_path ),
			);

		wp_enqueue_style(
			'cev-checkout-blocks-integration',
			$style_url,
			array(),
			$this->get_file_version( $style_path )
		);

		wp_register_script(
			'cev-checkout-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Note: Text domain kept as 'customer-email-verification' to align with plugin.
		wp_set_script_translations(
			'cev-checkout-blocks-integration',
			'customer-email-verification',
			dirname( __FILE__ ) . '/languages'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @since 1.0.0
	 * @return string[] Array of script handles.
	 */
	public function get_script_handles() {
		return array( 'cev-checkout-blocks-integration', 'cev-checkout-block-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @since 1.0.0
	 * @return string[] Array of script handles.
	 */
	public function get_editor_script_handles() {
		return array( 'cev-checkout-blocks-integration', 'cev-checkout-block-editor' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @since 1.0.0
	 * @return array Script data array.
	 */
	public function get_script_data() {
		return array(
			'shipping-workshop-active' => true,
		);
	}

	/**
	 * Register editor styles for checkout block.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_shipping_workshop_block_editor_styles() {
		$style_path = '/build/style-shipping-workshop-block.css';
		$style_url  = plugins_url( $style_path, __FILE__ );

		wp_enqueue_style(
			'cev-checkout-block',
			$style_url,
			array(),
			$this->get_file_version( $style_path )
		);
	}

	/**
	 * Register editor scripts for checkout block.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_shipping_workshop_block_editor_scripts() {
		$script_path       = '/build/shipping-workshop-block.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/shipping-workshop-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'cev-checkout-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'cev-checkout-block-editor',
			'customer-email-verification',
			dirname( __FILE__ ) . '/languages'
		);
	}

	/**
	 * Register frontend scripts for checkout block.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_shipping_workshop_block_frontend_scripts() {
		$script_path       = '/build/shipping-workshop-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/shipping-workshop-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'cev-checkout-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'cev-checkout-block-frontend',
			'customer-email-verification',
			dirname( __FILE__ ) . '/languages'
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @since 1.0.0
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			return SHIPPING_WORKSHOP_VERSION;
		}

		// Convert relative paths (starting with /) to absolute paths relative to plugin directory.
		if ( ! empty( $file ) && '/' === $file[0] ) {
			$file = dirname( __FILE__ ) . $file;
		}

		if ( file_exists( $file ) ) {
			return filemtime( $file );
		}

		return SHIPPING_WORKSHOP_VERSION;
	}
}
