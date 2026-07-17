<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCRED_Wpforms' ) ) :
	final class myCRED_Wpforms {

	    // Plugin Domain
        public $domain              = 'mycred_wpforms';
        // Plugin Slug
        public $slug                = 'mycred-wpforms';

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
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_wpforms_form_submit_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_wpforms_SLUG',           'mycred-wpforms-integration' );
			$this->define( 'MYCRED_wpforms',                __FILE__ );
			$this->define( 'MYCRED_wpforms_ROOT_DIR',       plugin_dir_path( MYCRED_wpforms ) );
			$this->define( 'MYCRED_wpforms_ASSETS_DIR_URL', plugin_dir_url( MYCRED_wpforms ) . 'assets/' );
			$this->define( 'MYCRED_wpforms_INCLUDES_DIR',   MYCRED_wpforms_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_wpforms_INCLUDES_DIR . 'mycred-wpforms-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_wpforms_form_submit_hook() {

			$this->file( MYCRED_wpforms_INCLUDES_DIR . 'mycred-wpforms-submitting-form-hook.php' );
			$this->file( MYCRED_wpforms_INCLUDES_DIR . 'mycred-wpforms-specific-field-value-hook.php' );
			$this->file( MYCRED_wpforms_INCLUDES_DIR . 'mycred-wpforms-specific-field-value-on-specific-form-hook.php' );
			
		}

		public function load_assets() {}

		public function load_admin_assets( $hook ) {	
				wp_enqueue_script( 
					'mycred_wpforms_admin_script', 
					MYCRED_wpforms_ASSETS_DIR_URL . 'js/script.js', 
					array( 'jquery' ), 
					'1.0' 
				);
				wp_enqueue_style( 
					'mycred_wpforms_admin_style', 
					MYCRED_wpforms_ASSETS_DIR_URL . 'css/style.css', 
					array(), 
					'1.0' 
				);
			
		}

		public function register_hooks( $installed ) {
			$installed['successful_submit_form'] = array(
				'title'       => __('Successful: Submit Form', 'mycred_wpforms'),
				'description' => __('Adds a myCRED hook for tracking points scored in wpforms submit form events.', 'mycred_wpforms'),
				'callback'    => array('myCRED_wpforms_Submit_Form_Hook')
			);
			$installed['specific_field_value'] = array(
				'title'       => __('Submit: Specific Field Value', 'mycred_wpforms'),
				'description' => __('Adds a myCRED hook for tracking points scored in specific field value events.', 'mycred_wpforms'),
				'callback'    => array('myCRED_wpforms_Specific_Field_Value_Hook')
			);
			$installed['specific_field_specific_form'] = array(
				'title'       => __('Submit: Specific Field Value on Specific Form', 'mycred_wpforms'),
				'description' => __('Adds a myCRED hook for tracking points scored in submit a specific field value on a specific form events.', 'mycred_wpforms'),
				'callback'    => array('myCRED_wpforms_Specific_Field_Specific_Form_Hook')
			);
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['successful_submit_form']  = __('Successfully submitting a form', 'mycred_wpforms');
			$list['specific_field_value']  = __('Successfully Submitting a Specific Field Value', 'mycred_wpforms');
			$list['specific_field_value_specific_form']  = __('Successfully Submitting a Specific Field Value on Specific Form', 'mycred_wpforms');
			return $list;
		}

	}
endif;

function myCRED_wpforms() {
	return myCRED_wpforms::instance();
}
myCRED_wpforms();

