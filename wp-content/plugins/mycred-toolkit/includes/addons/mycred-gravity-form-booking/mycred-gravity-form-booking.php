<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! class_exists( 'MyCRED_Gfbooking' ) ) :
	class MyCRED_Gfbooking {


		// Instance
		protected static $_instance = null;

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
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File
		 * @version 1.0
		 * 
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @version 1.0
		 * 
		 */
		public function __construct() {
			$this->define_constants();
			$this->init();  
		}


		/**
		 * Initialize
		 * @version 1.0
		 * 
		 */
		private function init() {
			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active('mycred/mycred.php') ) {
				$this->includes();
				add_filter( 'mycred_setup_hooks', array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks', array( $this, 'mycred_load_wpforms_form_submit_hook' ), 10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );    
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {
			$this->define( 'MYCRED_gfbooking_SLUG', 'mycred-gf-bookings-integration' );
			$this->define( 'MYCRED_gfbooking', __FILE__ );
			$this->define( 'MYCRED_gfbooking_ROOT_DIR', plugin_dir_path( MYCRED_gfbooking ) );
			$this->define( 'MYCRED_gfbooking_INCLUDES_DIR', MYCRED_gfbooking_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_gfbooking_INCLUDES_DIR . 'mycred-gfbooking-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_wpforms_form_submit_hook() {

			$this->file( MYCRED_gfbooking_INCLUDES_DIR . 'mycred-gfbooking-booking-confirmation-hook.php' );
		}


		public function register_hooks( $installed ) {
			$installed['booking_confirmation'] = array(
				'title'       => __('Booking Confirmation', 'mycred-toolkit'),
				'description' => __('Adds a myCred hook for tracking points scored on booking confirmation events.', 'mycred-toolkit'),
				'callback'    => array( 'MyCRED_Gfbooking_Booking_Confirmation_Hook' )
			);
			
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['gf_booking_confirmation']  = __('Successfully booking confirmation', 'mycred-toolkit');
			
			return $list;
		}
	}
	MyCRED_Gfbooking::instance();
endif;
