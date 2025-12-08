<?php
/**
 * Plugin Name:       Give - Currency Switcher
 * Plugin URI:        https://givewp.com/addons/currency-switcher/
 * Description:       Provide your donors with the ability to give using currency of their choice.
 * Version:           1.5.1
 * Author:            GiveWP
 * Author URI:        https://givewp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       give-currency-switcher
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
use GiveCurrencySwitcher\ExchangeRates\ExchangeRatesServiceProvider;
use GiveCurrencySwitcher\Infrastructure\Environment;
use GiveCurrencySwitcher\Revenue\RevenueServiceProvider;

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GIVE_CURRENCY_SWITCHER_VERSION' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_VERSION', '1.5.1' );
}
if ( ! defined( 'GIVE_CURRENCY_SWITCHER_MIN_GIVE_VER' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_MIN_GIVE_VER', '2.11.0' );
}
if ( ! defined( 'GIVE_CURRENCY_SWITCHER_SLUG' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_SLUG', 'give-currency-switcher' );
}
if ( ! defined( 'GIVE_CURRENCY_SWITCHER_PLUGIN_FILE' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'GIVE_CURRENCY_SWITCHER_PLUGIN_DIR' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_PLUGIN_DIR', plugin_dir_path( GIVE_CURRENCY_SWITCHER_PLUGIN_FILE ) );
}
if ( ! defined( 'GIVE_CURRENCY_SWITCHER_PLUGIN_URL' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_PLUGIN_URL', plugin_dir_url( GIVE_CURRENCY_SWITCHER_PLUGIN_FILE ) );
}
if ( ! defined( 'GIVE_CURRENCY_SWITCHER_BASENAME' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_BASENAME', plugin_basename( GIVE_CURRENCY_SWITCHER_PLUGIN_FILE ) );
}
if ( ! defined( 'GIVE_CURRENCY_SWITCHER_ADDON_NAME' ) ) {
	define( 'GIVE_CURRENCY_SWITCHER_ADDON_NAME', 'Give - Currency Switcher' );
}

if ( ! class_exists( 'Give_Currency_Switcher' ) ) :

	/**
	 * Give_Currency_Switcher Class
	 *
	 * @package Give_Currency_Switcher
	 * @since   1.0.0
	 */
	final class Give_Currency_Switcher {

		/**
		 * Holds the instance
		 *
		 * Ensures that only one instance of Give_Currency_Switcher exists in memory at any one
		 * time and it also prevents needing to define globals all over the place.
		 *
		 * TL;DR This is a static property property that holds the singleton instance.
		 *
		 * @var Give_Currency_Switcher object
		 * @static
		 */
		private static $instance;

		/**
		 * Give - Currency Switcher Admin Object.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @var    Give_Currency_Switcher_Admin object.
		 */
		public $plugin_admin;

		/**
		 * Give - Currency Switcher Frontend Object.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @var    Give_Currency_Switcher_Frontend object.
		 */
		public $plugin_public;

		/**
		 * Currency Switcher sections.
		 *
		 * @since 1.0
		 * @var array
		 */
		public static $section_tab;

		/**
		 * Notices (array)
		 *
		 * @since 1.2.2
		 * @var array
		 */
		public $notices = [];

		/**
		 * Get the instance and store the class inside it. This plugin utilises
		 * the PHP singleton design pattern.
		 *
		 * @see       Give_Currency_Switcher();
		 *
		 * @since     1.0.0
		 * @static
		 * @staticvar array $instance
		 * @access    public
		 *
		 * @return object self::$instance Instance
		 * @uses      Give_Currency_Switcher::includes() Loads all the classes.
		 * @uses      Give_Currency_Switcher::licensing() Add Give - Currency Switcher License.
		 *
		 * @uses      Give_Currency_Switcher::hooks() Setup hooks and actions.
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Give_Currency_Switcher ) ) {
				self::$instance = new Give_Currency_Switcher();
				add_action( 'plugins_loaded', [ self::$instance, 'init' ], 10 );
				add_action( 'admin_notices', [ self::$instance, 'admin_notices' ], 15 );
			}

			return self::$instance;
		}

		/**
		 * Init Give Currency Switcher .
		 *
		 * Sets up hooks, licensing and includes files.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return void
		 */
		public function init() {

			if ( ! self::$instance->check_environment() ) {
				return;
			}

			// Define the sections for various currency switcher settings.
			self::$section_tab = [
				'general-settings' => __( 'General', 'give-currency-switcher' ),
				'geolocation'      => __( 'Geolocation', 'give-currency-switcher' ),
				'payment-gateway'  => __( 'Payment Gateways', 'give-currency-switcher' ),
			];

			if ( is_admin() ) {
				self::$instance->activation();
			}
			self::$instance->hooks();
			self::$instance->licensing();
			self::$instance->includes();

		}

		/**
		 * Check plugin environment.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return bool
		 */
		public function check_environment() {

			// Verify dependency cases.
			if ( doing_action( 'plugins_loaded' ) && ! did_action( 'give_init' ) ) {

				// Check for if give plugin activate or not.
				$is_give_active = defined( 'GIVE_PLUGIN_BASENAME' ) ? is_plugin_active( GIVE_PLUGIN_BASENAME ) : false;

				if ( ! $is_give_active ) {

					$this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> plugin installed and activated for the Currency Switcher add-on to activate.', 'give-currency-switcher' ), 'https://givewp.com' ) );

					return false;
				}
			} elseif (
				defined( 'GIVE_VERSION' )
				&& version_compare( GIVE_VERSION, GIVE_CURRENCY_SWITCHER_MIN_GIVE_VER, '<' )
			) {
				// Min. Give. plugin version.

				// Show admin notice.
				$this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have <a href="%1$s" target="_blank">Give</a> core version %2$s+ for the Currency Switcher add-on to activate.', 'give-currency-switcher' ), 'https://givewp.com', GIVE_CURRENCY_SWITCHER_MIN_GIVE_VER ) );

				return false;
			}

			return true;
		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since  1.0.0
		 * @access protected
		 *
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'give-currency-switcher' ), '1.0' );
		}

		/**
		 * Disable Unserialize of the class.
		 *
		 * @since  1.0.0
		 * @access protected
		 *
		 * @return void
		 */
		public function __wakeup() {
			// Unserialize instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'give-currency-switcher' ), '1.0' );
		}

		/**
		 * Constructor Function.
		 *
		 * @since  1.0.0
		 * @access protected
		 */
		public function __construct() {
			self::$instance = $this;
		}

		/**
		 * Reset the instance of the class
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public static function reset() {
			self::$instance = null;
		}

		/**
		 * Includes.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function includes() {
			/**
			 * Give - GeoLocation Class.
			 */
			require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/lib/class-give-geo-location.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/includes/admin/class-give-currency-switcher-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/includes/frontend/class-give-currency-switcher-frontend.php';

			/**
			 * Give - Currency Switcher helper functions.
			 */
			require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/includes/give-currency-switcher-helpers.php';

			/**
			 * Give - Upgrade functionality.
			 */
			require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . '/includes/admin/upgrades/upgrade-functions.php';

			self::$instance->plugin_admin  = new Give_Currency_Switcher_Admin();
			self::$instance->plugin_public = new Give_Currency_Switcher_Frontend();
		}

		/**
		 * Hooks.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function hooks() {
			add_action( 'init', [ $this, 'load_textdomain' ] );
			add_action( 'admin_init', [ $this, 'activation_banner' ] );
			add_filter( 'plugin_action_links_' . GIVE_CURRENCY_SWITCHER_BASENAME, [ $this, 'action_links' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
		}

		/**
		 * Implement Give Licensing for Give - Currency Switcher Add On.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function licensing() {
			new Give_License(
				GIVE_CURRENCY_SWITCHER_PLUGIN_FILE,
				'Currency Switcher',
				GIVE_CURRENCY_SWITCHER_VERSION,
				'GiveWP'
			);
		}

		/**
		 * Load Plugin Text Domain
		 *
		 * Looks for the plugin translation files in certain directories and loads
		 * them to allow the plugin to be localised
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return bool True on success, false on failure.
		 */
		public function load_textdomain() {
			// Traditional WordPress plugin locale filter.
			$locale = apply_filters( 'plugin_locale', get_locale(), 'give-currency-switcher' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'give-currency-switcher', $locale );

			// Setup paths to current locale file.
			$mofile_local = trailingslashit( GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . 'languages' ) . $mofile;

			if ( file_exists( $mofile_local ) ) {
				// Look in the /wp-content/plugins/give-currency-switcher/languages/ folder.
				load_textdomain( 'give-currency-switcher', $mofile_local );
			} else {
				// Load the default language files.
				load_plugin_textdomain( 'give-currency-switcher', false, trailingslashit( GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . 'languages' ) );
			}

			return false;
		}

		/**
		 * Activation banner.
		 *
		 * Uses Give's core activation banners.
		 *
		 * @since 1.0.0
		 *
		 * @return bool
		 */
		public function activation_banner() {

			// Check for activation banner inclusion.
			if ( ! class_exists( 'Give_Addon_Activation_Banner' )
				 && file_exists( GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php' )
			) {

				include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
			}

			// Initialize activation welcome banner.
			if ( class_exists( 'Give_Addon_Activation_Banner' ) ) {

				// Only runs on admin.
				$args = [
					'file'              => GIVE_CURRENCY_SWITCHER_PLUGIN_FILE,
					'name'              => __( 'Currency Switcher', 'give-currency-switcher' ),
					'version'           => GIVE_CURRENCY_SWITCHER_VERSION,
					'settings_url'      => admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=currency-switcher' ),
					'documentation_url' => 'http://docs.givewp.com/addon-currency-switcher',
					'support_url'       => 'https://givewp.com/support/',
					'testing'           => false,
				];
				new Give_Addon_Activation_Banner( $args );
			}

			return true;
		}

		/**
		 * Adding additional setting page link along plugin's action link.
		 *
		 * @since   1.0.0
		 * @access  public
		 *
		 * @param array $actions get all actions.
		 *
		 * @return  array       return new action array
		 */
		public function action_links( $actions ) {

			$new_actions = [
				'settings' => sprintf(
					'<a href="%1$s">%2$s</a>',
					admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=currency-switcher' ),
					__( 'Settings', 'give-currency-switcher' )
				),
			];

			return array_merge( $new_actions, $actions );
		}

		/**
		 * Plugin row meta links.
		 *
		 * @since   1.0.0
		 * @access  public
		 *
		 * @param array  $plugin_meta An array of the plugin's metadata.
		 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
		 *
		 * @return  array  return meta links for plugin.
		 */
		public function plugin_row_meta( $plugin_meta, $plugin_file ) {

			// Return if not Give - Currency Switcher plugin.
			if ( GIVE_CURRENCY_SWITCHER_BASENAME !== $plugin_file ) {
				return $plugin_meta;
			}

			$new_meta_links = [
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url(
						add_query_arg(
							[
								'utm_source'   => 'plugins-page',
								'utm_medium'   => 'plugin-row',
								'utm_campaign' => 'admin',
							],
							'http://docs.givewp.com/addon-currency-switcher'
						)
					),
					__( 'Documentation', 'give-currency-switcher' )
				),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url(
						add_query_arg(
							[
								'utm_source'   => 'plugins-page',
								'utm_medium'   => 'plugin-row',
								'utm_campaign' => 'admin',
							],
							'https://givewp.com/addons/'
						)
					),
					__( 'Add-ons', 'give-currency-switcher' )
				),
			];

			return array_merge( $plugin_meta, $new_meta_links );
		}

		/**
		 * When plugin is activated create some schedules to update exchange rates.
		 *
		 * @access public
		 * @since  1.0
		 */
		public function activation() {

			$current_version = get_option( 'give_currency_switcher_version' );

			if ( version_compare( $current_version, GIVE_CURRENCY_SWITCHER_VERSION, '=' ) ) {
				return;
			}

			global $wpdb;

			// Update option.
			update_option( 'give_currency_switcher_version_upgraded_from', get_option( 'give_currency_switcher_version', GIVE_CURRENCY_SWITCHER_VERSION ) );
			update_option( 'give_currency_switcher_version', GIVE_CURRENCY_SWITCHER_VERSION );

			// Get the payment count in which currency was changed.
			$payment_count = $wpdb->get_var(
				$wpdb->prepare(
					"
					SELECT count(*)
					FROM $wpdb->donationmeta
					WHERE meta_key=%s
					",
					'_give_cs_enabled'
				)
			);

			if ( ! empty( $payment_count ) ) {
				return;
			}

			$completed_upgrades = [
				'give_cs_v11_reset_form_earning_meta',
				'give_cs_v11_update_form_earnings',
			];

			foreach ( $completed_upgrades as $completed_upgrade ) {
				give_set_upgrade_complete( $completed_upgrade );
			}

		}

		/**
		 * Allow this class and other classes to add notices.
		 *
		 * @param $slug
		 * @param $class
		 * @param $message
		 */
		public function add_admin_notice( $slug, $class, $message ) {
			$this->notices[ $slug ] = [
				'class'   => $class,
				'message' => $message,
			];
		}

		/**
		 * Display admin notices.
		 */
		public function admin_notices() {

			$allowed_tags = [
				'a'      => [
					'href'  => [],
					'title' => [],
					'class' => [],
					'id'    => [],
				],
				'br'     => [],
				'em'     => [],
				'span'   => [
					'class' => [],
				],
				'strong' => [],
			];

			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
				echo wp_kses( $notice['message'], $allowed_tags );
				echo '</p></div>';
			}

		}


	} //End Give_Currency_Switcher Class.

endif;

require_once GIVE_CURRENCY_SWITCHER_PLUGIN_DIR . 'vendor/autoload.php';

// Register the add-on service provider with the GiveWP core.
add_action(
	'before_give_init',
	function () {
		// Check Give min required version.
		if ( Environment::giveMinRequiredVersionCheck() ) {
			give()->registerServiceProvider( RevenueServiceProvider::class );
			give()->registerServiceProvider( ExchangeRatesServiceProvider::class );
		}
	}
);

/**
 * Loads a single instance of Give - Currency Switcher.
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $give_currency_switcher = Give_Currency_Switcher(); ?>
 *
 * @since   1.0.0
 *
 * @return object Give_Currency_Switcher
 */
function Give_Currency_Switcher() {
	return Give_Currency_Switcher::get_instance();
}

Give_Currency_Switcher();
