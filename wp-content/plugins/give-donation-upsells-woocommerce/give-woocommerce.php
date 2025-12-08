<?php
use GiveWooCommerceUpsells\Infrastructure\Activation;
use GiveWooCommerceUpsells\Infrastructure\Environment;
use GiveWooCommerceUpsells\Infrastructure\PluginUpgrade;
use GiveWooCommerceUpsells\ServiceProviders\RevenueServiceProvider;

/**
 * Plugin Name: Give - Donation Upsells for WooCommerce
 * Plugin URI:  https://givewp.com/addons/donation-upsells-for-woocommerce/
 * Description: Allow your shop customers to donate at the cart or checkout in WooCommerce.
 * Version:     1.2.1
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author:      GiveWP
 * Author URI:  https://givewp.com
 * Text Domain: give-woocommerce
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 4.0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) or exit;

if( ! defined( 'GIVE_WOOCOMMERCE_VERSION' ) ) {
	define( 'GIVE_WOOCOMMERCE_VERSION', '1.2.1' );
}

if( ! defined('GIVE_WOOCOMMERCE_ADDON_NAME') ) {
	define( 'GIVE_WOOCOMMERCE_ADDON_NAME', 'Donation Upsells for WooCommerce' );
}

if( ! defined( 'GIVE_WOOCOMMERCE_MIN_GIVE_VER' ) ) {
	define( 'GIVE_WOOCOMMERCE_MIN_GIVE_VER', '2.13.0' );
}

if( ! defined('GIVE_WOOCOMMERCE_PLUGIN_FILE' ) ) {
	define( 'GIVE_WOOCOMMERCE_PLUGIN_FILE', __FILE__ );
}

if( ! defined( 'GIVE_WOOCOMMERCE_PLUGIN_DIR' ) ) {
	define( 'GIVE_WOOCOMMERCE_PLUGIN_DIR', dirname( GIVE_WOOCOMMERCE_PLUGIN_FILE ) );
}

if( ! defined( 'GIVE_WOOCOMMERCE_PLUGIN_URL' ) ) {
	define( 'GIVE_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url( GIVE_WOOCOMMERCE_PLUGIN_FILE ) );
}

if( ! defined( 'GIVE_WOOCOMMERCE_BASENAME' ) ) {
	define( 'GIVE_WOOCOMMERCE_BASENAME', plugin_basename( GIVE_WOOCOMMERCE_PLUGIN_FILE ) );
}

// Check if class isn't already exists.
if ( ! class_exists( 'Give_WooCommerce' ) ) {

	/**
	 * Class Give_WooCommerce
	 *
	 * @since 1.0.0
	 */
	class Give_WooCommerce {

		/**
		 * @since 1.0.0
		 *
		 * @var Give_WooCommerce The reference the singleton instance of this class.
		 */
		private static $instance;

		/**
		 * Notices (array)
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $notices = [];

		/**
		 * Service Providers to register with GiveWP core for bootstrapping
		 *
		 * @unreleased
		 *
		 * @var string[]
		 */
		private $service_providers = [
			RevenueServiceProvider::class,
		];

		/**
		 * Returns the singleton instance of this class.
		 *
		 * @since 1.0.0
		 * @return Give_WooCommerce The singleton instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
				self::$instance->setup();
			}

			return self::$instance;
		}

		/**
		 * Setup Give WooCommerce.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function setup() {
			add_action( 'before_give_init', [ $this, 'register_service_providers' ] );

			// Give init hook.
			add_action( 'give_init', [ $this, 'init' ], 10 );

			/**
			 * Filter added for display Settings and Documentation links in plugin page.
			 *
			 * @since 1.0.4
			 */
			add_filter( 'plugin_action_links_' . GIVE_WOOCOMMERCE_BASENAME, [ $this, 'action_links' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
		}


		/**
		 * Init the plugin after plugins_loaded so environment variables are set.
		 *
		 * @since 1.0.0
		 */
		public function init() {

			$this->licensing();
			load_plugin_textdomain( 'give-woocommerce', false, GIVE_WOOCOMMERCE_PLUGIN_DIR . '/languages' );

			$this->includes();
			$this->activation_banner();

			// Register WooCommerce payment gateway.
			add_filter( 'give_payment_gateways', [ $this, 'register_woocommerce_gateway' ], 10, 1 );
		}

		/**
		 * Register WooCommerce Gateway.
		 *
		 * @since 1.0.0
		 *
		 * @param array $gateways Give gateways list.
		 *
		 * @return array
		 */
		public function register_woocommerce_gateway( $gateways ) {
			// Include all of the helper functions.
			require_once GIVE_WOOCOMMERCE_PLUGIN_DIR . '/includes/class-give-woocommerce-gateway.php';

			$gateways['woocommerce'] = [
				'admin_label' => __( 'WooCommerce', 'give-woocommerce' ),
				'checkout_label' => __( 'WooCommerce', 'give-woocommerce' ),
			];

			return $gateways;
		}

		/**
		 * Implement Give Licensing for Give WooCommerce Add On.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function licensing() {
			if ( class_exists( 'Give_License' ) ) {
				new Give_License(
					GIVE_WOOCOMMERCE_PLUGIN_FILE,
					'Donation Upsells for WooCommerce',
					GIVE_WOOCOMMERCE_VERSION,
					'WordImpress'
				);
			}
		}

		/**
		 * Include required files
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			// Give WooCommerce custom exception handling.
			require_once GIVE_WOOCOMMERCE_PLUGIN_DIR . '/includes/class-give-wc-exception.php';

			// Give WooCommerce add-on's all of the Admin functionality.
			require_once GIVE_WOOCOMMERCE_PLUGIN_DIR . '/includes/class-give-woocommerce-admin.php';

			// Give WooCommerce add-on's front-end functionality.
			require_once GIVE_WOOCOMMERCE_PLUGIN_DIR . '/includes/class-give-woocommerce-frontend.php';

			// Include all of the helper functions.
			require_once GIVE_WOOCOMMERCE_PLUGIN_DIR . '/includes/give-woocommerce-helper.php';

			// Keep WC Order and Give Donation sync.
			require_once GIVE_WOOCOMMERCE_PLUGIN_DIR . '/includes/class-give-woocommerce-sync.php';
		}

		/**
		 * Show activation banner for this add-on.
		 *
		 * @since 1.0.0
		 *
		 * @return bool
		 */
		public function activation_banner() {

			// Check for activation banner inclusion.
			if (
				! class_exists( 'Give_Addon_Activation_Banner' )
				&& file_exists( GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php' )
			) {
				include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
			}

			// Initialize activation welcome banner.
			if ( class_exists( 'Give_Addon_Activation_Banner' ) ) {

				// Only runs on admin.
				$args = [
					'file' => GIVE_WOOCOMMERCE_PLUGIN_FILE,
					'name' => __( 'Donation Upsells for WooCommerce', 'give-woocommerce' ),
					'version' => GIVE_WOOCOMMERCE_VERSION,
					'settings_url' => admin_url( 'admin.php?page=wc-settings&tab=settings_tab_give' ),
					'documentation_url' => 'http://docs.givewp.com/addon-give-woocommerce',
					'support_url' => 'https://givewp.com/support/',
					'testing' => false,
				];

				// Show activation banner.
				new Give_Addon_Activation_Banner( $args );
			}

			return true;
		}

		/**
		 * Adding additional setting page link along plugin's action link.
		 *
		 * @since   1.0.4
		 * @access  public
		 *
		 * @param array $actions get all actions.
		 *
		 * @return  array return new action array
		 */
		public function action_links( $actions ) {

			if ( ! class_exists( 'Give' ) ) {
				return $actions;
			}

			// Check min Give version.
			if ( defined( 'GIVE_WOOCOMMERCE_MIN_GIVE_VER' ) && version_compare( GIVE_VERSION, GIVE_WOOCOMMERCE_MIN_GIVE_VER, '<' ) ) {
				return $actions;
			}

			$new_actions = [
				'settings' => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=wc-settings&tab=settings_tab_give' ), __( 'Settings', 'give-woocommerce' ) ),
			];

			return array_merge( $new_actions, $actions );

		}

		/**
		 * Plugin row meta links.
		 *
		 * @since   1.0.4
		 * @access  public
		 *
		 * @param array  $plugin_meta An array of the plugin's metadata.
		 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
		 *
		 * @return  array  return meta links for plugin.
		 */
		public function plugin_row_meta( $plugin_meta, $plugin_file ) {

			// Return if not Give-Woocommerce plugin.
			if ( $plugin_file !== GIVE_WOOCOMMERCE_BASENAME ) {
				return $plugin_meta;
			}

			$new_meta_links = [
				sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( add_query_arg( [
					'utm_source' => 'plugins-page',
					'utm_medium' => 'plugin-row',
					'utm_campaign' => 'admin',
				], 'http://docs.givewp.com/addon-give-woocommerce' ) ), __( 'Documentation', 'give-woocommerce' ) ),
			];

			return array_merge( $plugin_meta, $new_meta_links );

		}

		/**
		 * Registers the Service Providers with GiveWP core
		 *
		 * @unreleased
		 */
		public function register_service_providers() {
			foreach ( $this->service_providers as $service_provider ) {
				give()->registerServiceProvider( $service_provider );
			}
		}
	}
}

/**
 * Loads a single instance of Give WooCommerce Add-on.
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @see     Give_WooCommerce::get_instance()
 *
 * @since   1.0.0
 *
 * @return object Give_WooCommerce Returns an instance of the  class
 */
function give_woocommerce() {
	return Give_WooCommerce::get_instance();
}

give_woocommerce();

require_once GIVE_WOOCOMMERCE_PLUGIN_DIR . '/vendor/autoload.php';

register_activation_hook( GIVE_WOOCOMMERCE_PLUGIN_FILE,[ Activation::class, 'activateAddon' ] );
register_uninstall_hook( GIVE_WOOCOMMERCE_PLUGIN_FILE, [ Activation::class, 'uninstallAddon' ] );

add_action(
	'admin_init',
	function () {
		$hasEnvironment = Environment::checkEnvironment();

		if( $hasEnvironment ) {
			give( PluginUpgrade::class )->storePluginUpgradeVersion();
		}
	}
);

