<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCRED_Wsform' ) ) :
	final class myCRED_Wsform {

	    // Plugin Domain
        public $domain              = 'mycred_wsform';
        // Plugin Slug
        public $slug                = 'mycred-wsform';

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
            add_action( 'init',                  array( $this, 'load_textdomain' ), 5 );


		}

		  /**
         * Load Textdomain
         * 
         * 
         */
        public function load_textdomain() {

            // Load Translation
            $locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

            load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->domain . '-' . $locale . '.mo' );
            load_plugin_textdomain( $this->domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

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
				add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );
				add_filter( 'mycred_setup_hooks',    array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_wsform_form_submit_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_wsform_SLUG',           'mycred-wsform-integration' );
			$this->define( 'MYCRED_wsform',                __FILE__ );
			$this->define( 'MYCRED_wsform_ROOT_DIR',       plugin_dir_path( MYCRED_wsform ) );
			$this->define( 'MYCRED_wsform_ASSETS_DIR_URL', plugin_dir_url( MYCRED_wsform ) . 'assets/' );
			$this->define( 'MYCRED_wsform_INCLUDES_DIR',   MYCRED_wsform_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_wsform_INCLUDES_DIR . 'mycred-wsform-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_wsform_form_submit_hook() {

			$this->file( MYCRED_wsform_INCLUDES_DIR . 'mycred-wsform-submitting-form-hook.php' );
			$this->file( MYCRED_wsform_INCLUDES_DIR . 'mycred-wsform-specific-field-value-hook.php' );
			$this->file( MYCRED_wsform_INCLUDES_DIR . 'mycred-wsform-specific-field-value-on-specific-form-hook.php' );
			
		}

		public function load_assets() {}

		public function load_admin_assets( $hook ) {	
				wp_enqueue_script( 
					'mycred_wsform_admin_script', 
					MYCRED_wsform_ASSETS_DIR_URL . 'js/script.js', 
					array( 'jquery' ), 
					'1.0' 
				);
				wp_enqueue_style( 
					'mycred_wsform_admin_style', 
					MYCRED_wsform_ASSETS_DIR_URL . 'css/style.css', 
					array(), 
					'1.0' 
				);
			
		}

		public function register_hooks( $installed ) {
			$installed['successful_submit_form'] = array(
				'title'       => __('Successful: Submit Form', 'mycred_wsform'),
				'description' => __('Adds a myCRED hook for tracking points scored in wsform submit form events.', 'mycred_wsform'),
				'callback'    => array('myCRED_wsform_Submit_Form_Hook')
			);
			$installed['specific_field_value'] = array(
				'title'       => __('Submit: Specific Field Value', 'mycred_wsform'),
				'description' => __('Adds a myCRED hook for tracking points scored in specific field value events.', 'mycred_wsform'),
				'callback'    => array('myCRED_wsform_Specific_Field_Value_Hook')
			);
			$installed['specific_field_specific_form'] = array(
				'title'       => __('Submit: Specific Field Value on Specific Form', 'mycred_wsform'),
				'description' => __('Adds a myCRED hook for tracking points scored in submit a specific field value on a specific form events.', 'mycred_wsform'),
				'callback'    => array('myCRED_wsform_Specific_Field_Specific_Form_Hook')
			);
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['successful_submit_form']  = __('Successfully submitting a form', 'mycred_wsform');
			$list['specific_field_value']  = __('Successfully Submitting a Specific Field Value', 'mycred_wsform');
			$list['specific_field_value_specific_form']  = __('Successfully Submitting a Specific Field Value on Specific Form', 'mycred_wsform');
			return $list;
		}

	}
endif;

function myCRED_wsform() {
	return myCRED_Wsform::instance();
}
myCRED_wsform();
