<?php
/**
 * Checkout Block Integration Loader for Customer Email Verification PRO
 *
 * Integrated Checkout Block loader for Customer Email Verification (CEV).
 * Handles WooCommerce Blocks integration for checkout verification.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Customer_Email_Verification_Checkout_Block Class
 *
 * Manages checkout block integration for email verification.
 */
class WC_Customer_Email_Verification_Checkout_Block {

	/**
	 * Instance of this class.
	 *
	 * @var WC_Customer_Email_Verification_Checkout_Block|null Class Instance
	 */
	private static $instance = null;

	/**
	 * Get the class instance.
	 *
	 * @since  1.0.0
	 * @return WC_Customer_Email_Verification_Checkout_Block Class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * Initialize the class properties and add necessary hooks.
	 * Only runs if checkout verification is enabled.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Feature gate: Only initialize if checkout verification is enabled.
		if ( 1 != cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 ) ) {
			return;
		}

		// Define version constant if not already defined.
		$this->define_version_constant();

		// Hook into the WooCommerce Blocks loaded action to include and register CEV integration.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'maybe_include_cev_integration' ) );

		// Register block category for any blocks introduced by CEV.
		add_action( 'block_categories_all', array( $this, 'register_cev_block_category' ), 10, 2 );
	}

	/**
	 * Define version constant if not already defined.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function define_version_constant() {
		if ( ! defined( 'SHIPPING_WORKSHOP_VERSION' ) ) {
			$plugin_data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
			$version     = ! empty( $plugin_data['version'] ) ? $plugin_data['version'] : time();
			define( 'SHIPPING_WORKSHOP_VERSION', $version );
		}
	}

	/**
	 * Include CEV integration file and register integration with Blocks.
	 *
	 * Called when 'woocommerce_blocks_loaded' fires.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_include_cev_integration() {
		// Feature gate: Only include if checkout verification is enabled.
		if ( 1 != cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 ) ) {
			return;
		}

		$integration_file = cev_pro()->get_plugin_path() . '/includes/checkout-verification/checkout-block/cev-blocks-integration.php';

		if ( ! file_exists( $integration_file ) ) {
			return;
		}

		require_once $integration_file;

		// Register integration with the blocks integration registry if available.
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				if ( class_exists( 'CEV_Blocks_Integration' ) ) {
					$integration_registry->register( new CEV_Blocks_Integration() );
				}
			}
		);
	}

	/**
	 * Registers the slug as a block category with WordPress.
	 *
	 * @since 1.0.0
	 * @param array                                $categories      Existing block categories.
	 * @param WP_Block_Editor_Context|WP_Post|null $post_or_context Second arg passed by WP; kept for compatibility.
	 * @return array Modified categories array with cev-checkout-block appended.
	 */
	public function register_cev_block_category( $categories, $post_or_context = null ) {
		// Feature gate: Only register if checkout verification is enabled.
		if ( 1 != cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 ) ) {
			return $categories;
		}

		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'shipping-workshop',
					'title' => __( 'Shipping_Workshop Blocks', 'shipping-workshop' ),
				),
			)
		);
	}
}
