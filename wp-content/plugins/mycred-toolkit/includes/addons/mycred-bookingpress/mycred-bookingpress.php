<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'myCRED_Bookingpress' ) ) :
	#[AllowDynamicProperties]
	final class myCRED_Bookingpress {

	    // Plugin Domain
        public $domain              = 'mycred_bookingpress';
        // Plugin Slug
        public $slug                = 'mycred-bookingpress';

		// Instance
		protected static $_instance = NULL;

		/**
		 * Setup Instance
		 * @version 1.0
		 * 
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Define
		 * @version 1.0
		 * 
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @version 1.0
		 * 
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @version 1.0
		 * 
		 */
		public function __construct() {
			$this->define_constants();
			$this->init();
			$this->plugin = plugin_basename( __FILE__ );
            
		}

		/**
		 * Initialize
		 * @version 1.0
		 * 
		 */
		private function init() {
			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active('mycred/mycred.php') && is_plugin_active('bookingpress-appointment-booking/bookingpress-appointment-booking.php') ) {
				$this->includes();
				add_action( 'wp_enqueue_scripts',    array( $this, 'load_assets' ) );
				add_filter( 'mycred_setup_hooks',    array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_booking_press_complete_booking_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_BOOKINGPRESS_SLUG',           'mycred-fbookingpress' );
			$this->define( 'MYCRED_BOOKINGPRESS',                __FILE__ );
			$this->define( 'MYCRED_BOOKINGPRESS_ROOT_DIR',       plugin_dir_path( MYCRED_BOOKINGPRESS ) );
			$this->define( 'MYCRED_BOOKINGPRESS_ASSETS_DIR_URL', plugin_dir_url( MYCRED_BOOKINGPRESS ) . 'assets/' );
			$this->define( 'MYCRED_BOOKINGPRESS_INCLUDES_DIR',   MYCRED_BOOKINGPRESS_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_BOOKINGPRESS_INCLUDES_DIR . 'mycred-bookingpress-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_booking_press_complete_booking_hook() {

			$this->file( MYCRED_BOOKINGPRESS_INCLUDES_DIR . 'mycred-bookingpress-complete-booking-hook.php' );
			
		}

		public function load_assets() {}

		public function register_hooks( $installed ) {
			$installed['bookingpress_successful_booking_complete'] = array(
				'title'       => __('Compleing a Booking (BookingPress)', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in bookingpress complete booking events.', 'mycred-toolkit'),
				'callback'    => array('myCRED_BookingPress_Complete_Booking_Hook')
			);
			
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['bookingpress_successful_booking_complete']  = __('Successfully completing booking', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_bookingpress() {
	return myCRED_bookingpress::instance();
}
myCRED_bookingpress();

