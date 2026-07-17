<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'myCRED_FluentCRM' ) ) :
	#[AllowDynamicProperties]
	final class myCRED_FluentCRM {

	    // Plugin Domain
        public $domain              = 'mycred_fluentcrm';
        // Plugin Slug
        public $slug                = 'mycred-fluentcrm';

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
			if ( is_plugin_active('mycred/mycred.php') && is_plugin_active('fluent-crm/fluent-crm.php') ) {
				$this->includes();
				add_action( 'admin_enqueue_scripts',    array( $this, 'load_assets' ) );
				add_filter( 'mycred_setup_hooks',    array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks',     array( $this, 'mycred_load_fluentcrm_hook' ),10 );
				add_filter( 'mycred_all_references', array( $this, 'register_refrences' ) );	
			} 
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants() {

			$this->define( 'MYCRED_FLUENTCRM_SLUG',           'mycred-fluentcrm' );
			$this->define( 'MYCRED_FLUENTCRM',                __FILE__ );
			$this->define( 'MYCRED_FLUENTCRM_ROOT_DIR',       plugin_dir_path( MYCRED_FLUENTCRM ) );
			$this->define( 'MYCRED_FLUENTCRM_ASSETS_DIR_URL', plugin_dir_url( MYCRED_FLUENTCRM ) . 'assets/' );
			$this->define( 'MYCRED_FLUENTCRM_INCLUDES_DIR',   MYCRED_FLUENTCRM_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes() {
			$this->file( MYCRED_FLUENTCRM_INCLUDES_DIR . 'mycred-fluentcrm-functions.php' );
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_fluentcrm_hook() {

			$this->file( MYCRED_FLUENTCRM_INCLUDES_DIR . 'mycred-fluentcrm-hook.php' );
			$this->file( MYCRED_FLUENTCRM_INCLUDES_DIR . 'mycred-fluentcrm-tags-hook.php' );
			$this->file( MYCRED_FLUENTCRM_INCLUDES_DIR . 'mycred-fluentcrm-tags-removed-hook.php' );
			$this->file( MYCRED_FLUENTCRM_INCLUDES_DIR . 'mycred-fluentcrm-lists-hook.php' );
			$this->file( MYCRED_FLUENTCRM_INCLUDES_DIR . 'mycred-fluentcrm-lists-removed-hook.php' );
			
		}

		public function load_assets() {
			// Only load script on myCred hooks page
			if ( is_admin() && is_mycred_hook_page() ) {
				wp_enqueue_script(
					'mycred-fluentcrm-script',
					MYCRED_FLUENTCRM_ASSETS_DIR_URL . 'js/script.js',
					array('jquery'),
					'1.0.0',
					true
				);
			}
		}

		public function register_hooks( $installed ) {
			$installed['fluentcrm_contact_created'] = array(
				'title'       => __('Contact Created (FluentCRM)', 'mycred-toolkit'),
				'description' => __('Awards myCred points when a contact is created in FluentCRM.', 'mycred-toolkit'),
				'callback'    => array('myCRED_FluentCRM_Contact_Hook')
			);
			
			$installed['fluentcrm_tag_added'] = array(
				'title'       => __('Tag Added (FluentCRM)', 'mycred-toolkit'),
				'description' => __('Awards myCred points when a tag is added to a contact in FluentCRM.', 'mycred-toolkit'),
				'callback'    => array('myCRED_FluentCRM_Tags_Hook')
			);
			
			$installed['fluentcrm_tag_removed'] = array(
				'title'       => __('Tag Removed (FluentCRM)', 'mycred-toolkit'),
				'description' => __('Awards myCred points when a tag is removed from a contact in FluentCRM.', 'mycred-toolkit'),
				'callback'    => array('myCRED_FluentCRM_Tags_Removed_Hook')
			);
			
			$installed['fluentcrm_list_added'] = array(
				'title'       => __('List Added (FluentCRM)', 'mycred-toolkit'),
				'description' => __('Awards myCred points when a contact is added to a list in FluentCRM.', 'mycred-toolkit'),
				'callback'    => array('myCRED_FluentCRM_Lists_Hook')
			);
			
			$installed['fluentcrm_list_removed'] = array(
				'title'       => __('List Removed (FluentCRM)', 'mycred-toolkit'),
				'description' => __('Awards myCred points when a contact is removed from a list in FluentCRM.', 'mycred-toolkit'),
				'callback'    => array('myCRED_FluentCRM_Lists_Removed_Hook')
			);
			
			return $installed;
		}

		public function register_refrences( $list ) {
			$list['fluentcrm_contact_created']  = __('Contact created in FluentCRM', 'mycred-toolkit');
			$list['fluentcrm_tag_added']  = __('Tag added to contact in FluentCRM', 'mycred-toolkit');
			$list['fluentcrm_tag_removed']  = __('Tag removed from contact in FluentCRM', 'mycred-toolkit');
			$list['fluentcrm_list_added']  = __('Contact added to list in FluentCRM', 'mycred-toolkit');
			$list['fluentcrm_list_removed']  = __('Contact removed from list in FluentCRM', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_fluentcrm() {
	return myCRED_FluentCRM::instance();
}
myCRED_fluentcrm();
