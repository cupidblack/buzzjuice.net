<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCred_Toolkit_zoom_addon' )) {
	
	/**
	* myCRED Give Wp Addons class
	**/
	class myCred_Toolkit_zoom_addon {
		
		
		
		/**
		* Construct
		**/
		public function __construct() {
			
			$this->za_define_constants();
			$this->za_init();
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
		private function za_init() {

			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active('mycred/mycred.php') && is_plugin_active('video-conferencing-with-zoom-api/video-conferencing-with-zoom-api.php')) {
				add_action( 'admin_enqueue_scripts', array( $this, 'za_load_admin_assets' ) );
				add_filter( 'mycred_setup_hooks', array( $this, 'za_register_hook' ), 10, 2 );
				add_action( 'mycred_load_hooks', array( $this, 'za_load_hook' ));
				add_action( 'init', array( $this, 'za_includes' ));
				add_filter( 'mycred_all_references', array( $this, 'za_register_refrences' ) ); 
				add_action('wp_loaded', array( $this, 'za_frontend_scripts' ));
			}
			add_action( 'admin_notices', array( $this, 'za_required_plugin_notices' ) ); 
		}
		
		/**
		* Give define constants
		**/ 
		private function za_define_constants() {

		
			$this->define( 'MYCRED_ZA_SLUG', 'mycred-toolkit');
			$this->define( 'MYCRED_ZA', __FILE__ );
			$this->define( 'MYCRED_ZA_ROOT_DIR', plugin_dir_path(MYCRED_ZA) );
			$this->define( 'MYCRED_ZA_ASSETS_DIR_URL', plugin_dir_url(MYCRED_ZA) . 'assets/' );
			$this->define( 'MYCRED_ZA_INCLUDES_DIR', MYCRED_ZA_ROOT_DIR . 'includes/' );
		}
		
		/**
		* Give load admin assets
		**/ 
		public function za_load_admin_assets( $hook ) {    
			
			   wp_enqueue_script( 
				   'mycred_za_admin_script', 
				   MYCRED_ZA_ASSETS_DIR_URL . 'js/za_script.js', 
				   array( 'jquery' ), 
				   '1.0' 
			   );
				
			   wp_enqueue_style( 
				   'mycred_za_admin_style', 
				   MYCRED_ZA_ASSETS_DIR_URL . 'css/za_style.css', 
				   array(), 
				   '1.0' 
			   );
		}
		
		/**
		* Load Frontend Scripts 
		**/
		public function za_frontend_scripts() {
			// Main javascipt file
			wp_enqueue_script('mycred_za_ajaxurl',
			MYCRED_ZA_ASSETS_DIR_URL . 'js/mycred_zoom_script.js', array( 'jquery' ), false, true);
		
			// AJAX: request
			wp_localize_script('mycred_za_ajaxurl', 'mycred_za_frontend_scripts_obj', array( 'ajax_url' => admin_url('admin-ajax.php') ));
		}
		
		/**
		* Load Includes File 
		**/
		public function za_includes() {
			$this->file(MYCRED_ZA_INCLUDES_DIR . 'mycred-za-functions.php');
		}
		
		/**
		* Give wp hook file
		**/ 
		public function za_load_hook() {
			
			$this->file( MYCRED_ZA_INCLUDES_DIR . 'mycred-za-hook.php' );
		}
		
		/**
		* Give wp register hook
		**/
		public function za_register_hook( $installed ) {
			$installed['mycred_zoom'] = array(
				'title'       => __('Points for zoom meeting', 'mycred-toolkit'),
				'description' => __('This is zoom meeting addon give wp hook.', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_ZA_Hook' )
			);
			return $installed;
		}
		
		/**
		* Give wp register refrences
		**/
		public function za_register_refrences( $list ) {
			$list['mycred_zoom'] = __('Joining Zoom Meeting', 'mycred-toolkit');
			return $list;
		}
		
		/**
		* Give wp required plugin notices
		**/
		public function za_required_plugin_notices() {
 
			$msg = __( 'need to be active and installed to use myCred plugin.', 'mycred-toolkit' );
			$msg_zoom = __( 'need to be active and installed to use myCred Zoom Addon plugin.', 'mycred-toolkit' );
			if ( !is_plugin_active('mycred/mycred.php') ) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/mycred/">%1$s</a> %2$s</p></div>', esc_html_e( 'myCred', 'mycred-toolkit' ), esc_html( $msg ) );
			} 
			if (!is_plugin_active('video-conferencing-with-zoom-api/video-conferencing-with-zoom-api.php')) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/video-conferencing-with-zoom-api/">%1$s</a> %2$s</p></div>', esc_html_e( 'Zoom Video Conferencing', 'mycred-toolkit' ), esc_html( $msg_zoom ) );
			}
		}
	}
	
}

new myCred_Toolkit_zoom_addon();
