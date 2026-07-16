<?php
/**
 * Store Credit Payment Method
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

namespace AffiliateWP\Core\Payouts\Methods\StoreCredit;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store Credit payment method class.
 *
 * @since 2.29.0
 */
final class AffiliateWP_Store_Credit {

	/**
	 * The AffiliateWP_Store_Credit singleton instance.
	 *
	 * @since 2.29.0
	 * @var AffiliateWP_Store_Credit instance.
	 */
	private static $instance;

	/**
	 * The plugin version.
	 *
	 * @since 2.29.0
	 * @var   string $version
	 */
	private $version = '1.0.0';

	/**
	 * Main plugin file path.
	 *
	 * @since 2.29.0
	 * @var   string
	 */
	public $file;

	/**
	 * True if the AffiliateWP core debugger is active.
	 *
	 * @since 2.29.0
	 * @var   boolean $debug Debug variable.
	 */
	public $debug;

	/**
	 * Holds the instance of Affiliate_WP_Logging.
	 *
	 * @since 2.29.0
	 * @var   array $logs Error logs.
	 */
	public $logs;

	/**
	 * Transactions DB
	 *
	 * @since 2.29.0
	 *
	 * @var \AffiliateWP\Core\Store_Credit\Transactions\DB
	 */
	public $transactions;

	/**
	 * Main AffiliateWP_Store_Credit instance
	 *
	 * @since 2.29.0
	 * @static
	 * @static var array $instance
	 *
	 * @return \AffiliateWP_Store_Credit The one true AffiliateWP_Store_Credit
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AffiliateWP_Store_Credit ) ) {

			self::$instance = new AffiliateWP_Store_Credit();

			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->init();

			// Mark that core has initialized
			do_action( 'affwp_store_credit_core_init' );
		}

		return self::$instance;
	}


	/**
	 * Throws an error on object clone.
	 *
	 * @since 2.29.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instance of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'affiliate-wp' ), '2.29.0' );
	}

	/**
	 * Disables unserializing of the class.
	 *
	 * @since 2.29.0
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'affiliate-wp' ), '2.29.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @since 2.29.0
	 * @return void
	 */
	private function setup_constants() {
		// Plugin version - use a different constant name to avoid conflicts with addon
		if ( ! defined( 'AFFWP_SC_CORE_VERSION' ) ) {
			define( 'AFFWP_SC_CORE_VERSION', $this->version );
		}

		// Plugin Folder Path - Using CORE prefix to avoid conflicts with addon
		if ( ! defined( 'AFFWP_SC_CORE_PLUGIN_DIR' ) ) {
			define( 'AFFWP_SC_CORE_PLUGIN_DIR', $this->get_path() );
		}

		// Plugin Folder URL - Using CORE prefix to avoid conflicts with addon
		if ( ! defined( 'AFFWP_SC_CORE_PLUGIN_URL' ) ) {
			define( 'AFFWP_SC_CORE_PLUGIN_URL', $this->get_url() );
		}
	}

	/**
	 * Get the Store Credit module directory path.
	 *
	 * @since 2.29.0
	 * @return string
	 */
	private function get_path() {
		return AFFILIATEWP_PLUGIN_DIR . 'includes/payouts/methods/store-credit/';
	}

	/**
	 * Get the Store Credit module directory URL.
	 *
	 * @since 2.29.0
	 * @return string
	 */
	private function get_url() {
		return AFFILIATEWP_PLUGIN_URL . 'includes/payouts/methods/store-credit/';
	}

	/**
	 * Includes required files.
	 *
	 * @since 2.29.0
	 * @access private
	 * @return void
	 */
	private function includes() {
		$store_credit_enabled = affiliate_wp()->settings->get( 'store-credit' );
		$is_settings_page     = is_admin() && isset( $_GET['page'] ) && 'affiliate-wp-settings' === $_GET['page'];

		// Always load minimal required files
		require_once $this->get_path() . 'functions.php';
		require_once $this->get_path() . 'class-store-credit-transactions.php'; // Always needed for init()

		// Only load additional files if Store Credit is enabled
		if ( $store_credit_enabled ) {
			// Shortcode.
			require_once $this->get_path() . 'class-shortcode.php';

			// Dashboard integration.
			require_once $this->get_path() . 'class-dashboard.php';
		}

		// Load admin class in admin area if addon isn't handling it
		if ( is_admin() && ( $store_credit_enabled || $is_settings_page ) ) {
			// Check early to prevent class conflicts
			$addon_active = class_exists( '\AffiliateWP_Store_Credit' ) || defined( 'AFFWP_SC_VERSION' );

			if ( ! $addon_active ) {
				require_once $this->get_path() . 'class-store-credit-admin.php';
			}
		}

		// Always load integrations if Store Credit is enabled
		$store_credit_enabled = affiliate_wp()->settings->get( 'store-credit' );

		// Load integrations if Store Credit is enabled (regardless of admin/frontend)
		if ( ! $store_credit_enabled ) {
			return;
		}

		// Always load integrations - core module handles its own functionality
		require_once $this->get_path() . 'integrations/class-base.php';

		// Load the class for each integration enabled.
		foreach ( affiliate_wp()->integrations->get_enabled_integrations() as $filename => $integration ) {
			if ( file_exists( $this->get_path() . 'integrations/class-' . $filename . '.php' ) ) {
				require_once $this->get_path() . 'integrations/class-' . $filename . '.php';
			}
		}
	}

	/**
	 * Render Store Credit settings panel.
	 *
	 * @since 2.29.0
	 */
	public function render_settings() {
		include $this->get_path() . 'templates/settings-panel.php';
	}

	/**
	 * Defines init processes for this instance.
	 *
	 * @since  2.29.0
	 *
	 * @return void
	 */
	public function init() {
		$this->debug = (bool) affiliate_wp()->settings->get( 'debug_mode', false );

		$this->transactions = new \AffiliateWP\Core\Store_Credit\Transactions\DB();

		if ( $this->debug ) {
			$this->logs = new \Affiliate_WP_Logging();
		}

		// Only initialize components if Store Credit is enabled
		$store_credit_enabled = affiliate_wp()->settings->get( 'store-credit' );

		if ( $store_credit_enabled ) {
			// Initialize shortcode
			new \AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_Shortcode();

			// Initialize dashboard integration
			new \AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_Dashboard();
		}

		// Initialize admin functionality when needed
		if ( is_admin() ) {
			// Check if addon will handle admin or if it already exists
			$addon_active       = class_exists( '\AffiliateWP_Store_Credit' ) || defined( 'AFFWP_SC_VERSION' );
			$addon_admin_exists = class_exists( '\AffiliateWP_Store_Credit_Admin' );

			// Only initialize core admin if addon isn't handling it
			if ( ! $addon_active && ! $addon_admin_exists ) {
				// Use the fully namespaced class name for core
				if ( class_exists( '\AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_Admin' ) ) {
					new \AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_Admin();
				}
			}
			// If addon is active, it will handle its own admin initialization
		}
	}

	/**
	 * Writes a log message.
	 *
	 * @access  public
	 * @since   2.29.0
	 *
	 * @param string $message An optional message to log. Default is an empty string.
	 */
	public function log( $message = '' ) {

		if ( ! $this->debug ) {
			return;
		}

		$this->logs->log( $message );
	}
}
