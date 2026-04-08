<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'myCRED_Everestform' ) ) :
	#[AllowDynamicProperties]
	final class myCRED_Everestform {

	    // Plugin Domain
        public $domain              = 'mycred_everestform';
        // Plugin Slug
        public $slug                = 'mycred-everestform';

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
			if ( is_plugin_active('mycred/mycred.php') ) {
				$this->includes();
				add_action( 'wp_enqueue_scripts',    array( $this, 'load_assets' ) );
				add_filter( 'mycred_setup_hooks',    array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_everest_form_submit_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_EVERESTFORM_SLUG',           'mycred-everestform' );
			$this->define( 'MYCRED_EVERESTFORM',                __FILE__ );
			$this->define( 'MYCRED_EVERESTFORM_ROOT_DIR',       plugin_dir_path( MYCRED_EVERESTFORM ) );
			$this->define( 'MYCRED_EVERESTFORM_ASSETS_DIR_URL', plugin_dir_url( MYCRED_EVERESTFORM ) . 'assets/' );
			$this->define( 'MYCRED_EVERESTFORM_INCLUDES_DIR',   MYCRED_EVERESTFORM_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_EVERESTFORM_INCLUDES_DIR . 'mycred-everestform-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_everest_form_submit_hook() {

			$this->file( MYCRED_EVERESTFORM_INCLUDES_DIR . 'mycred-everestform-submitting-form-hook.php' );
			
		}

		public function load_assets() {}

		public function register_hooks( $installed ) {
			$installed['everestform_successful_submit_form'] = array(
				'title'       => __('Form Submissions (Everest Form)', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in metform submit form events.', 'mycred-toolkit'),
				'callback'    => array('myCRED_Everestform_Submit_Form_Hook')
			);
			
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['everestform_successful_submit_form']  = __('Successfully submitting a form', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_everestform() {
	return myCRED_everestform::instance();
}
myCRED_everestform();

