<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCred_Toolkit_give_wp_addon' )) {
	
	/**
	* myCRED Give Wp Addons class
	**/
	class myCred_Toolkit_give_wp_addon {
		
	
		
		/**
		* Construct
		**/
		public function __construct() {
			$this->gwp_define_constants();
			$this->gwp_init();
		}
		
		/**
		* Check Required Files
		**/
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}
		/**
		* Check Define Path
		**/
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
		
		/**
		* Give Initialize
		**/
		private function gwp_init() {

			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active('mycred/mycred.php') && is_plugin_active('give/give.php')) {
				add_action( 'admin_enqueue_scripts', array( $this, 'gwp_admin_scripts' ) );
				add_action('wp_enqueue_scripts', array( $this, 'gwp_frontend_scripts' ));
				add_action( 'init', array( $this, 'gwp_includes' )); 
				add_action( 'mycred_load_hooks', array( $this, 'gwp_load_hook' ));
				add_filter( 'mycred_setup_hooks', array( $this, 'gwp_register_hook' ), 10, 2 );
				add_filter( 'mycred_all_references', array( $this, 'gwp_register_refrences' ) ); 
				
				//Use Badge Addon filters
				add_filter( 'mycred_badge_requirement', 'gwp_badge_requirement', 10, 5 );
				add_filter( 'mycred_badge_requirement_specific_template', 'gwp_badge_template', 10, 5 );
				add_action( 'admin_head', 'gwp_admin_header' );
				
			}
			add_action( 'admin_notices', array( $this, 'gwp_required_plugin_notices' ) ); 
		}
		
		/**
		* Give define constants
		**/ 
		private function gwp_define_constants() {
		
			$this->define( 'MYCRED_GWP_SLUG', 'mycred-toolkit');
			$this->define( 'MYCRED_GWP', __FILE__ );
			$this->define( 'MYCRED_GWP_ROOT_DIR', plugin_dir_path(MYCRED_GWP) );
			$this->define( 'MYCRED_GWP_ASSETS_DIR_URL', plugin_dir_url(MYCRED_GWP) . 'assets/' );
			$this->define( 'MYCRED_GWP_INCLUDES_DIR', MYCRED_GWP_ROOT_DIR . 'includes/' );
		}
		/**
		* Load Admin Scripts 
		**/
		public function gwp_admin_scripts() {
			//Script
			wp_enqueue_script( 
				'mycred_gwp_script', 
				MYCRED_GWP_ASSETS_DIR_URL . 'js/give_wp_script.js', 
				array( 'jquery' ), 
				'1.0' 
			);
			//CSS
			wp_enqueue_style( 
				'mycred_gwp_style', 
				MYCRED_GWP_ASSETS_DIR_URL . 'css/give_wp_style.css', 
				array(), 
				'1.0' 
			);
		}
		/**
		* Load Frontend Scripts 
		**/
		public function gwp_frontend_scripts() {
			// Main javascipt file
			wp_enqueue_script('mycred_give_wp_ajaxurl',
			MYCRED_GWP_ASSETS_DIR_URL . 'js/mycred_give_wp_script.js', array( 'jquery' ), true, false);
		
			// AJAX: request
			wp_localize_script('mycred_give_wp_ajaxurl', 'mycred_give_wp_frontend_scripts_obj', array( 'ajax_url' => admin_url('admin-ajax.php') ));
		}
		
		/**
		* Load Includes File 
		**/
		public function gwp_includes() {
			$this->file(MYCRED_GWP_INCLUDES_DIR . 'mycred_give_wp_functions.php');
		}
		
		/**
		* Give wp hook file
		**/ 
		public function gwp_load_hook() {
			$this->file( MYCRED_GWP_INCLUDES_DIR . 'mycred_give_wp_multiple_hook.php' );
		}
		
		/**
		* Give wp register hook
		**/
		public function gwp_register_hook( $installed ) {
			$installed['mycred_give_wp_multiple'] = array(
				'title'       => __('Points for GiveWP Donation', 'mycred-toolkit'),
				'description' => __('This is give wp addon for specific form', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_GWP_Multiple_Hook' )
			);
			return $installed;
		}
		
		/**
		* Give wp register refrences
		**/
		public function gwp_register_refrences( $list ) {
			$list['mycred_give_wp_multiple'] = __('Points for completing give wp specific form', 'mycred-toolkit');
			return $list;
		}
		
		/**
		* Give wp required plugin notices
		**/
		public function gwp_required_plugin_notices() {
 
			$msg = __( 'need to be active and installed to use myCred plugin.', 'mycred-toolkit' );
			$msg_give = __( 'need to be active and installed to use myCred GiveWP Addon plugin.', 'mycred-toolkit' );
			if ( !is_plugin_active('mycred/mycred.php') ) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/mycred/">%1$s</a> %2$s</p></div>', esc_html__( 'myCred', 'mycred-toolkit' ), esc_html( $msg ) );
			} 
			if (!is_plugin_active('give/give.php')) {
				printf( '<div class="notice notice-error"><p><a href="https://givewp.com/">%1$s</a> %2$s</p></div>', esc_html__( 'Give - Donation Plugin', 'mycred-toolkit' ), esc_html( $msg_give ) );
			} 
		}
	} //end class
	
} // Check class
new myCred_Toolkit_give_wp_addon();
