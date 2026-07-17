<?php
/**
 * Plugin Name: myCred Addons
 * Plugin URI: https://mycred.me
 * Description: myCred Addons is a powerful package of free add-ons, designed to enhance user engagement through gamification and loyalty rewards.
 * Version: 1.4.6
 * Tags: toolkit, credit, loyalty program, engagement, reward, woocommerce rewards
 * Author: myCred
 * Author URI: https://wpexperts.io
 * Author Email: support@mycred.me
 * Requires at least: 4.8
 * Tested up to: 7.0
 * Text Domain: mycred-toolkit
 * Requires Plugins:  mycred
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'MyCRED_Toolkit_Core' ) ) :
	final class MyCRED_Toolkit_Core { 

		// Plugin Version
		public $version             = '1.4.6';

		// Plugin slug
		public $slug                = 'mycred-toolkit';

		// Instnace
		protected static $_instance = null;

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Define
		 *       
		 * @since   1.0
		 * @version 1.0
		 */
		
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			} elseif ( ! $definable && defined( $name ) ) {
				_doing_it_wrong( 'MyCRED_Toolkit_Core->define()', 'Could not define: ' . esc_attr($name) . ' as it is already defined somewhere else!', esc_attr($this->version) );
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->includes();
			add_action( 'AHEE__EE_System__load_espresso_addons', array( $this, 'espresso_setup_gateway' ) );
		}

		/**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 *
		 * @since   1.0
		 * @version 1.0
		 */
		private function define_constants() {
			$this->define( 'MYCRED_TOOLKIT_VERSION', $this->version );
			$this->define( 'MYCRED_TOOLKIT_SLUG', $this->slug );
			$this->define( 'MYCRED_TOOLKIT_THIS', __FILE__ );
			$this->define( 'MYCRED_TOOLKIT_FILE_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'MYCRED_TOOLKIT_ROOT_DIR', plugin_dir_path( MYCRED_TOOLKIT_THIS ) );
			$this->define( 'MYCRED_TOOLKIT_INCLUDES_DIR', MYCRED_TOOLKIT_ROOT_DIR . 'includes/' );
			$this->define( 'MYCRED_TOOLKIT_UNIFIED_ADDONS', true );
		}

		public function includes() {
			include_once MYCRED_TOOLKIT_ROOT_DIR . 'includes/main-mycred-toolkit.php';
			include_once MYCRED_TOOLKIT_ROOT_DIR . 'includes/mycred-toolkit-addon-expansion.php';
			include_once MYCRED_TOOLKIT_ROOT_DIR . 'includes/controllers/mycred_rest_api.php';
		}

		public function espresso_setup_gateway() {

			if ( version_compare( EVENT_ESPRESSO_VERSION, '4.6', '<' ) ) return;

			if ( ! function_exists( 'mycred' ) ) return;

			if ( class_exists( 'EE_Addon' ) ) {

				$enabled_addons = function_exists( 'mycred_get_active_addon_slugs' )
					? mycred_get_active_addon_slugs()
					: get_option( 'mycred_enabled_addons', array() );

				if ( in_array( 'mycred-ee', $enabled_addons, true ) ) {

				include_once MYCRED_TOOLKIT_ROOT_DIR . 'includes/addons/mycred-ee/EE_MyCRED_Payment_Method.class.php';

				EE_MyCRED_Payment_Method::register_addon();

			    }

			}

		}
	}
	MyCRED_Toolkit_Core::instance();
endif;
