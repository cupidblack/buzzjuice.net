<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCRED_ninjaforms' ) ) :
    #[AllowDynamicProperties]
    final class myCRED_ninjaforms {

        // Plugin Domain
        public $domain              = 'mycred_ninjaforms';
        // Plugin Slug
        public $slug                = 'mycred-ninjaforms';

        // Instance
        protected static $_instance = NULL;

        /**
         * Setup Instance
         * @since 1.0.4
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
         * @since 1.0.4
         * @version 1.0
         */
        private function define( $name, $value ) {
            if ( ! defined( $name ) )
                define( $name, $value );
        }

        /**
         * Require File
         * @since 1.0.4
         * @version 1.0
         */
        public function file( $required_file ) {
            if ( file_exists( $required_file ) )
                require_once $required_file;
        }

        /**
         * Construct
         * @since 1.0.4
         * @version 1.0
         */
        public function __construct() {
            $this->define_constants();
            $this->init();
            $this->plugin = plugin_basename( __FILE__ );
            add_action( 'init',                  array( $this, 'load_textdomain' ), 5 );
            add_action( 'mycred_init',              array( $this, 'load_license' ) );


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
         * Load License
         * 
         * @version 1.0
        */

        public function load_license() {

            if ( class_exists('myCRED_License') ) {
                
                new myCRED_License( 
                    array(
                        'version' => '1.0',
                        'slug'    => $this->slug,
                        'base'    => __FILE__
                    )
                );

            }
            else {

                add_action( 'admin_notices', array( $this, 'license_admin_notice' ) );

            }

        }

        /**
         * License Notice
         * 
         * 
         */

        public function license_admin_notice() {

            echo '<div class="notice notice-error is-dismissible"><p>myCRED ninjaforms Integration requires myCred 2.4.4.1 or greater version to work your license properly.</p></div>';

        }

        /**
         * Initialize
         * @since 1.0
         * @version 1.0
         */
        private function init() {
            $this->file( ABSPATH . 'wp-admin/includes/plugin.php' );
            if ( is_plugin_active('mycred/mycred.php') ) {
                $this->includes();
                add_action( 'wp_enqueue_scripts',    array( $this, 'load_assets' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );
                add_filter( 'mycred_setup_hooks',    array( $this, 'register_hooks' ), 10, 2 );
                add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_ninjaforms_form_submit_hook' ),10 );
                add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );    
            } 
        }

        /**
         * Define Constants
         * @since 1.1.1
         * @version 1.0
         */
        private function define_constants() {
            $this->define( 'MYCRED_ninjaforms_SLUG',           'mycred-ninjaforms-integration' );
            $this->define( 'MYCRED_ninjaforms',                __FILE__ );
            $this->define( 'MYCRED_ninjaforms_ROOT_DIR',       plugin_dir_path( MYCRED_ninjaforms ) );
            $this->define( 'MYCRED_ninjaforms_ASSETS_DIR_URL', plugin_dir_url( MYCRED_ninjaforms ) . 'assets/' );
            $this->define( 'MYCRED_ninjaforms_INCLUDES_DIR',   MYCRED_ninjaforms_ROOT_DIR . 'includes/' );
        }

        /**
         * Include Plugin Files
         * @since 1.1.1
         * @version 1.0
         */
        public function includes() {
            $this->file( MYCRED_ninjaforms_INCLUDES_DIR . 'mycred-ninjaforms-functions.php' );
        }

        /**
         * Include Hook Files
         * @since 1.1.1
         * @version 1.0
         */
        public function mycred_load_ninjaforms_form_submit_hook() {

            $this->file( MYCRED_ninjaforms_INCLUDES_DIR . 'mycred-ninjaforms-submitting-form-hook.php' );
            $this->file( MYCRED_ninjaforms_INCLUDES_DIR . 'mycred-ninjaforms-specific-field-value-hook.php' );
            $this->file( MYCRED_ninjaforms_INCLUDES_DIR . 'mycred-ninjaforms-specific-field-value-on-specific-form-hook.php' );
            
        }

        public function load_assets() {}

        public function load_admin_assets( $hook ) {    
                wp_enqueue_script( 
                    'mycred_ninjaforms_admin_script', 
                    MYCRED_ninjaforms_ASSETS_DIR_URL . 'js/script.js', 
                    array( 'jquery' ), 
                    '1.0' 
                );
                wp_enqueue_style( 
                    'mycred_ninjaforms_admin_style', 
                    MYCRED_ninjaforms_ASSETS_DIR_URL . 'css/style.css', 
                    array(), 
                    '1.0' 
                );
            
        }

        public function register_hooks( $installed ) {
            $installed['successful_submit_ninjaform'] = array(
                'title'       => __('Successful: Submit Form (Ninja Forms)', 'mycred-toolkit'),
                'description' => __('Adds a myCRED hook for tracking points scored in ninjaforms submit form events.', 'mycred-toolkit'),
                'callback'    => array('myCRED_ninjaforms_Submit_Form_Hook')
            );
            $installed['specific_field_value'] = array(
                'title'       => __('Submit: Specific Field Value (Ninja Forms)', 'mycred-toolkit'),
                'description' => __('Adds a myCRED hook for tracking points scored in specific field value events.', 'mycred-toolkit'),
                'callback'    => array('myCRED_ninjaforms_Specific_Field_Value_Hook')
            );
            $installed['specific_field_specific_form'] = array(
                'title'       => __('Submit: Specific Field Value on Specific Form (Ninja Forms)', 'mycred-toolkit'),
                'description' => __('Adds a myCRED hook for tracking points scored in submit a specific field value on a specific form events.', 'mycred-toolkit'),
                'callback'    => array('myCRED_ninjaforms_Specific_Field_Specific_Form_Hook')
            );
            return $installed;
        }

        public function register_refrences( $list ) {
            $list['successful_submit_ninjaform']  = __('Successfully submitting a form', 'mycred-toolkit');
            $list['specific_field_value']  = __('Successfully Submitting a Specific Field Value', 'mycred-toolkit');
            $list['specific_field_value_specific_form']  = __('Successfully Submitting a Specific Field Value on Specific Form', 'mycred-toolkit');
            return $list;
        }

    }
endif;

function myCRED_ninjaforms() {
    return myCRED_ninjaforms::instance();
}
myCRED_ninjaforms();