<?php

if ( ! defined( 'EE_MYCRED_PAYMENT_METHOD_VERSION' ) ) {
	define( 'EE_MYCRED_PAYMENT_METHOD_VERSION', '1.0' );
}
if ( ! defined( 'EE_MYCRED_PAYMENT_METHOD_PLUGIN_FILE' ) ) {
	define( 'EE_MYCRED_PAYMENT_METHOD_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'MYCRED_EE_SLUG' ) ) {
	define( 'MYCRED_EE_SLUG', 'mycred-ee' );
}
if ( ! defined( 'MYCRED_DEFAULT_TYPE_KEY' ) ) {
	define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );
}

// ✅ Include the class that uses those constants
require_once plugin_dir_path( __FILE__ ) . 'EE_MyCRED_Payment_Method.class.php';

if ( ! class_exists( 'myCRED_Toolkit_EventEspresso' ) ) :
	final class myCRED_Toolkit_EventEspresso {



		// Instnace
		protected static $_instance = null;

		// Current session
		public $session             = null;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = null;
		public $plugin_name         = '';

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
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->slug        = 'mycred-ee';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred-toolkit';
			$this->plugin_name = 'myCRED for Event Espresso 4';

			// $this->define_constants();

			add_action( 'mycred_init', array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			$this->define( 'EE_MYCRED_PAYMENT_METHOD_VERSION', '1.0' );
			$this->define( 'EE_MYCRED_PAYMENT_METHOD_PLUGIN_FILE', __FILE__ );
			$this->define( 'MYCRED_EE_SLUG', $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );
		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() { }

		
    
		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );
		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			$references['event_ticket_payment'] = __( 'Ticket Payment (Event Espresso)', 'mycred-toolkit' );

			return $references;
		}
	}
endif;

function mycred_toolkit_event_espresso_plugin() {
	return myCRED_Toolkit_EventEspresso::instance();
}
mycred_toolkit_event_espresso_plugin();
