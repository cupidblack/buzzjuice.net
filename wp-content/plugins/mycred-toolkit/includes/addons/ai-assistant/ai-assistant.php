<?php
/**
 * Addon: AI Assistant (Experiment)
 * Version: 1.0.2
 * Description: Integrates WordPress 7.0 AI Core features with the myCred points system.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if WordPress AI Core features are supported.
if ( ! function_exists( 'wp_supports_ai' ) || ! wp_supports_ai() ) {
	return;
}

if ( ! class_exists( 'myCRED_AI' ) ) :
	class myCRED_AI {

		// Addon Version
		public $version             = '1.0.2';

		// Instance
		protected static $_instance = NULL;

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {
			if ( ! defined( 'MYCRED_AI_VERSION' ) ) {
				define( 'MYCRED_AI_VERSION', $this->version );
			}
			if ( ! defined( 'MYCRED_AI_PLUGIN_FILE' ) ) {
				define( 'MYCRED_AI_PLUGIN_FILE', __FILE__ );
			}
			if ( ! defined( 'MYCRED_AI_ROOT_DIR' ) ) {
				define( 'MYCRED_AI_ROOT_DIR', plugin_dir_path( MYCRED_AI_PLUGIN_FILE ) );
			}
			if ( ! defined( 'MYCRED_AI_INCLUDES_DIR' ) ) {
				define( 'MYCRED_AI_INCLUDES_DIR', MYCRED_AI_ROOT_DIR . 'includes/' );
			}
			if ( ! defined( 'MYCRED_AI_ASSETS_URL' ) ) {
				define( 'MYCRED_AI_ASSETS_URL', plugins_url( 'assets/', MYCRED_AI_PLUGIN_FILE ) );
			}
		}

		/**
		 * Includes and Core Initialization
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() {
			$admin_class_file = MYCRED_AI_INCLUDES_DIR . 'class-mycred-ai-admin.php';
			if ( file_exists( $admin_class_file ) ) {
				require_once $admin_class_file;
				if ( class_exists( 'myCRED_AI_Admin' ) ) {
					// Instantiate the AI Admin class.
					myCRED_AI_Admin::instance();
				}
			}
		}
	}

	return myCRED_AI::instance();
endif;
