<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'myCRED_Jetform' ) ) :
	#[AllowDynamicProperties]
	final class myCRED_Jetform {

	    // Plugin Domain
        public $domain              = 'mycred_jetform';
        // Plugin Slug
        public $slug                = 'mycred-jetform';

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
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_jet_form_submit_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_JETFORM_SLUG',           'mycred-jetform' );
			$this->define( 'MYCRED_JETFORM',                __FILE__ );
			$this->define( 'MYCRED_JETFORM_ROOT_DIR',       plugin_dir_path( MYCRED_JETFORM ) );
			$this->define( 'MYCRED_JETFORM_ASSETS_DIR_URL', plugin_dir_url( MYCRED_JETFORM ) . 'assets/' );
			$this->define( 'MYCRED_JETFORM_INCLUDES_DIR',   MYCRED_JETFORM_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_JETFORM_INCLUDES_DIR . 'mycred-jetformbuilder-functions' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_jet_form_submit_hook() {

			$this->file( MYCRED_JETFORM_INCLUDES_DIR . 'mycred-jetformbuilder-submitting-form-hook.php' );
			
		}

		public function load_assets() {}

		public function register_hooks( $installed ) {
			$installed['jetform_successful_submit_form'] = array(
				'title'       => __('Form Submissions (Jetformbuilder)', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in Jetformbuilder submit form events.', 'mycred-toolkit'),
				'callback'    => array('myCRED_Jetform_Submit_Form_Hook')
			);
			
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['jetform_successful_submit_form']  = __('Successfully submitting a form', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_jetform() {
	return myCRED_jetform::instance();
}
myCRED_jetform();

