<?php

if (!defined('MYCRED_BADGR_DIR')) {
	define('MYCRED_BADGR_DIR', plugin_dir_path(__FILE__));
}

if ( !defined( 'MYCRED_BADGER_PREFIX' ) ) {
	define( 'MYCRED_BADGER_PREFIX' , 'mycred_br' );
}

if ( !defined( 'MYCRED_BADGR_VERSION' ) ) {
	define( 'MYCRED_BADGR_VERSION' , '1.0.4' );
}

if ( !class_exists( 'myCRED_Badgr' ) ) :
	class myCRED_Toolkit_Badgr {

		/**
		 * myCRED_Badgr constructor.
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {
			load_plugin_textdomain( 'mycred-badgr', false, MYCRED_BADGR_DIR . 'languages' );
		
			add_action('mycred_init', array( $this, 'badgr_init' ));

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 1000 );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_scripts' ) );
		}

		/**
		 * Initializes Badgr Class if meet requirements
		 * @since 1.0
		 * @version 1.0
		 */
		public function badgr_init() {
			if ( !class_exists( 'myCRED_Addons_Module' ) ) {
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			
				return;
			}
		
			require_once MYCRED_BADGR_DIR . 'includes/badgr-settings.php';

			require_once MYCRED_BADGR_DIR . 'includes/class-mycred-badgr-api.php';

			require_once MYCRED_BADGR_DIR . 'includes/functions.php';

			require_once MYCRED_BADGR_DIR . 'includes/classes/mycred_badgr_user.php';
		
			require_once MYCRED_BADGR_DIR . 'includes/shortcodes/mycred-badgr-login.php';
		}

		/**
		 * Enqueue Admin Scripts
		 * @since 1.0
		 * @version 1.0
		 */
		public function admin_enqueue() {
			wp_enqueue_script( MYCRED_BADGER_PREFIX . 'admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/admin-script.js', '', MYCRED_BADGR_VERSION );


			wp_enqueue_style( 'badgr-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css' , array(), '0.1.0', 'all' );
		}

		/**
		 * Enqueue Front Scripts
		 * @since 1.0
		 * @version 1.0
		 */
		public function enqueue_front_scripts() {
			wp_enqueue_script( MYCRED_BADGER_PREFIX . 'front-js', plugin_dir_url( __FILE__ ) . 'assets/js/front-script.js', array( 'jquery' ), MYCRED_BADGR_VERSION );
		}

		/**
		 * Add Admin notices
		 * @since 1.0
		 * @version 1.0
		 */
		public function admin_notices() {
			$class = 'notice notice-error';
		
			$message = __('In order to use myCred Badgr install and active myCred.', 'mycred-toolkit');

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}

new myCRED_Toolkit_Badgr();

endif;
