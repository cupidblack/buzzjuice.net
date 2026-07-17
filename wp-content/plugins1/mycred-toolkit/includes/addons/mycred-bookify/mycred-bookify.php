<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCred_Bookify_Core' ) ) :
	final class myCred_Bookify_Core {


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
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value ) {
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
			$this->define_constants();
			$this->init();
			$this->includes();
		}

		/**
		 * Initialize
		 * @since 1.0
		 * @version 1.0
		 */
		private function init() {


			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );

			if ( is_plugin_active('mycred/mycred.php') && is_plugin_active('bookify/bookify.php') ) {

				add_filter( 'mycred_setup_hooks', array( $this, 'mycred_register_bookify' ), 10  );
				add_action( 'mycred_load_hooks', array( $this, 'mycred_load_bookify_hook' ));
				add_filter( 'mycred_all_references', array( $this, 'bookify_register_refrences' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'bookify_admin_script' ));
			}
		}

		
			
		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'mycred_bookify_SLUG', 'mycred-bookify' );
			$this->define( 'mycred_bookify', __FILE__ );
			$this->define( 'mycred_bookify_ROOT_DIR', plugin_dir_path( mycred_bookify ) );
			$this->define( 'mycred_bookify_ASSETS_DIR_URL', plugin_dir_url( mycred_bookify ) . 'assets/' );
			$this->define( 'mycred_bookify_INCLUDES_DIR', mycred_bookify_ROOT_DIR . 'includes/' );
		}

		public function mycred_load_bookify_hook() {
			
			$this->file( mycred_bookify_INCLUDES_DIR . 'mycred-complete-booking.php' );
		}

		public function includes() {

			$this->file( mycred_bookify_INCLUDES_DIR . 'function.php' );
		}

		public function mycred_register_bookify( $installed ) {
			
			$installed['bookify_booking_completion'] = array(
				'title'        => __('Completing a Booking (Bookify)', 'mycred-toolkit'),
				'description'  => __('Optional hook description. Must be defined but can be empty.', 'mycred-toolkit'),
				'callback'     => array( 'mycred_bookify_Booking_Completion_Hook_Class' ),
			);

			return $installed;
		}

		public function bookify_register_refrences( $list ) {

			$list['bookify_booking_completion'] = __('Completing booking', 'mycred-toolkit');
			return $list;
		}

		public function bookify_admin_script( $hook ) {

			if ( is_mycred_hook_page( $hook ) ) {

				wp_enqueue_style( 'mycred-bookify-style', mycred_bookify_ASSETS_DIR_URL . 'css/style.css' );
			}
		}
	}
	myCred_Bookify_Core::instance();
endif;
