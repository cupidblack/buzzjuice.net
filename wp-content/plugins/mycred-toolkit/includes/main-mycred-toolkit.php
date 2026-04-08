<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}
/**
 * Class to connect mycred with toolkit
 * 
 * @since 1.0
 * @version 1.0
 */
// If this file is called directly, abort.

if ( ! class_exists( 'MyCRED_Toolkit' ) ) :
	class MyCRED_Toolkit {
		
		/**
		 * Construct
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'mycred_toolkit_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mycred_toolkit_scripts' ) );
		}
		
		/**
		 * Register toolkit menu
		 */
		
		public function mycred_toolkit_menu() {
			mycred_add_main_submenu( 
				__( 'Toolkit', 'mycred-toolkit' ),
				__( 'Toolkit', 'mycred-toolkit' ),
				'manage_options', 
				'mycred-toolkit',
				array( $this, 'mycred_toolkit_callback' )
			);
		}

		/**
		 * Register toolkit menu callback
		 */
		public function mycred_toolkit_callback() {
			wp_enqueue_script('wp-element');
			wp_enqueue_script( 'mycred-toolkit-script' );
			
			echo '<div id="mycred-toolkit" style="margin-left:-20px"></div>';
		}

		/**
		 * Register style & scripts for toolkit
		 */
		public function mycred_toolkit_scripts() { 
			wp_localize_script('mycred-toolkit-script', 'mycredAddonsData', array(
				'upgraded' => apply_filters('mycred_toolkit_plan_check', true ),
				'nonce' => wp_create_nonce('wp_rest'),
				'root' => esc_url_raw( rest_url() ),
			));
		}
	}



endif;
$myCRED_Toolkit = new MyCRED_Toolkit();
