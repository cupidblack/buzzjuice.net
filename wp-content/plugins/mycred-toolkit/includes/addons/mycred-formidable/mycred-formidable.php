<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'myCRED_Formidable' ) ) :
	#[AllowDynamicProperties]
	final class myCRED_Formidable {

	    // Plugin Domain
        public $domain              = 'mycred_formidableforms';
        // Plugin Slug
        public $slug                = 'mycred-formidableforms';

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
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_formidable_form_submit_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_FORMIDABLE_SLUG',           'mycred-formidable' );
			$this->define( 'MYCRED_FORMIDABLE',                __FILE__ );
			$this->define( 'MYCRED_FORMIDABLE_ROOT_DIR',       plugin_dir_path( MYCRED_FORMIDABLE ) );
			$this->define( 'MYCRED_FORMIDABLE_ASSETS_DIR_URL', plugin_dir_url( MYCRED_FORMIDABLE ) . 'assets/' );
			$this->define( 'MYCRED_FORMIDABLE_INCLUDES_DIR',   MYCRED_FORMIDABLE_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_FORMIDABLE_INCLUDES_DIR . 'mycred-formidable-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_formidable_form_submit_hook() {

			$this->file( MYCRED_FORMIDABLE_INCLUDES_DIR . 'mycred-formidable-submitting-form-hook.php' );
			
		}

		public function load_assets() {}

		public function register_hooks( $installed ) {
			$installed['successful_submit_form'] = array(
				'title'       => __('Form Submissions (Formidable)', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in formidable submit form events.', 'mycred-toolkit'),
				'callback'    => array('myCRED_Formidable_Submit_Form_Hook')
			);
			
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['formidable_successful_submit_form']  = __('Successfully submitting a form', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_formidable() {
	return myCRED_formidable::instance();
}
myCRED_formidable();

