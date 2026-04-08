<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'myCRED_Formmaker' ) ) :
	#[AllowDynamicProperties]
	final class myCRED_Formmaker {

	    // Plugin Domain
        public $domain              = 'mycred_formmaker';
        // Plugin Slug
        public $slug                = 'mycred-formmaker';

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
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_form_maker_submit_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_FORMMAKER_SLUG',           'mycred-formmaker' );
			$this->define( 'MYCRED_FORMMAKER',                __FILE__ );
			$this->define( 'MYCRED_FORMMAKER_ROOT_DIR',       plugin_dir_path( MYCRED_FORMMAKER ) );
			$this->define( 'MYCRED_FORMMAKER_ASSETS_DIR_URL', plugin_dir_url( MYCRED_FORMMAKER ) . 'assets/' );
			$this->define( 'MYCRED_FORMMAKER_INCLUDES_DIR',   MYCRED_FORMMAKER_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_FORMMAKER_INCLUDES_DIR . 'mycred-formmaker-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_form_maker_submit_hook() {

			$this->file( MYCRED_FORMMAKER_INCLUDES_DIR . 'mycred-formmaker-submitting-form-hook.php' );
			
		}

		public function load_assets() {}

		public function register_hooks( $installed ) {
			$installed['formmaker_successful_submit_form'] = array(
				'title'       => __('Form Submissions (Form Maker)', 'mycred-toolkit'),
				'description' => __('Adds a myCRED hook for tracking points scored in form maker submit form events.', 'mycred-toolkit'),
				'callback'    => array('myCRED_Formmaker_Submit_Form_Hook')
			);
			
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['formmaker_successful_submit_form']  = __('Successfully submitting a form', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_formmaker() {
	return myCRED_formmaker::instance();
}
myCRED_formmaker();

