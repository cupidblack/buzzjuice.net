<?php
/**
 * Plugin Name: myCred Progress Bar
 * Description: add progress bar setting in rank and badges.
 * Version: 1.3.4
 * Tags: ranks,badges,progrss
 * Author: myCred
 * Author URI: http://mycred.me
 * Author Email: support@mycred.me
 * Requires at least: WP 4.8
 * Tested up to: WP 5.8.1
 * Text Domain: mycredpb
 * Domain Path: /lang
 */
if ( ! class_exists( 'mycred_progress_bar' ) ) :
	final class mycred_progress_bar {

		// Plugin Version
		public $version             = '1.3.4'; 

		public $slug                = 'mycred-progress-bar';

		// Instnace
		protected static $_instance = NULL;

		// Plugin name
		public $plugin_name         = 'myCred Progress Bar';

		// Plugin ID
		public $id                  = 220;

		// Current session
		public $session             = NULL;

		public $domain              = 'mycredpb';

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
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
			elseif ( ! $definable && defined( $name ) )
				_doing_it_wrong( 'mycred_progress_bar->define()', 'Could not define: ' . $name . ' as it is already defined somewhere else!', $this->version );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
			else
				_doing_it_wrong( 'mycred_progress_bar->file()', 'Requested file ' . $required_file . ' not found.', $this->version );
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->includes();
			$this->load_shortcode();

			register_activation_hook( MYCRED_PB_THIS, array( $this, 'activate_plugin' ) );
			
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ),20 );
			add_action( 'mycred_init',        array( $this, 'load_license' ) );

		}

		/**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'MYCRED_PB_VERSION',      $this->version );
			$this->define( 'MYCRED_PB_SLUG',         $this->slug );
			$this->define( 'MYCRED_PB_THIS',         __FILE__ );
			$this->define( 'MYCRED_PB_ROOT_DIR',     plugin_dir_path( MYCRED_PB_THIS ) );
			$this->define( 'MYCRED_PB_INCLUDES_DIR', MYCRED_PB_ROOT_DIR . 'include/' );

		}

		/**
		 * Include Plugin Files
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() {
         	
			$this->file( MYCRED_PB_INCLUDES_DIR . 'mycred-progressbar-shortcodes.php' );
			$this->file( MYCRED_PB_ROOT_DIR . 'class.mycred-license.php' );
		}

		/**
		 * Register shortcode
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_shortcode() {

			add_shortcode( 'mycred_my_ranks_progress','mycred_render_my_ranks_progress' );
			add_shortcode( 'mycred_badges_progress','mycred_render_badges_progress' );
		}

		/**
		 * Enqueue Style
		 * @since 1.0
		 * @version 1.0
		 */
		public static function enqueue_styles() {

			wp_register_style(
				'mycred-progressbar-circle',
				plugins_url( 'assets/css/circle.css', MYCRED_PB_THIS )
			);

			wp_enqueue_style( 'mycred-progressbar-circle' );

			wp_register_style(
				'mycred-progressbar-booststrap',
				'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
				MYCRED_PB_THIS
			);

		}

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		}

		/**
		 * Activate
		 * @since 1.0
		 * @version 1.0
		 */
		public static function activate_plugin() {

			global $wpdb;

			$message = array();

			// WordPress check
			$wp_version = $GLOBALS['wp_version'];
			if ( version_compare( $wp_version, '4.5', '<' ) )
				$message[] = __( 'This myCRED Add-on requires WordPress 4.5 or higher. Version detected:', 'mycredpb' ) . ' ' . $wp_version;

			// PHP check
			$php_version = phpversion();
			if ( version_compare( $php_version, '5.6', '<' ) )
				$message[] = __( 'This myCRED Add-on requires PHP 5.6 or higher. Version detected: ', 'mycredpb' ) . ' ' . $php_version;

			// SQL check
			$sql_version = $wpdb->db_version();
			if ( version_compare( $sql_version, '5.0', '<' ) )
				$message[] = __( 'This myCRED Add-on requires SQL 5.0 or higher. Version detected: ', 'mycredpb' ) . ' ' . $sql_version;

			// myCred check
			if ( ! class_exists( 'myCRED_Core' ) )
				$message[] = __( 'This myCRED Add-on requires myCred plugin', 'mycredpb' );

			if ( ! class_exists( 'myCRED_Badge_Module' ) )
				$message[] = __( 'Please enable badge addon', 'mycredpb' );

			// Not empty $message means there are issues
			if ( ! empty( $message ) ) {

				$error_message = implode( "\n", $message );
				die( __( 'Sorry but your WordPress installation does not reach the minimum requirements for running this add-on. The following errors were given:', 'mycredpartwoo' ) . "\n" . $error_message );

			}

		}

		/**
		 * Load License
		 * @since 1.3.3
		 * @version 1.0
		 */
		public function load_license() {

			if ( class_exists('myCRED_License') ) {
				
				new myCRED_License( 
					array(
						'version' => $this->version,
						'slug'    => $this->slug,
						'base'    => __FILE__
					)
				);

			}
			else {

				add_action( 'admin_notices', array( $this, 'license_admin_notice' ) );

			}

		}

		public function license_admin_notice() {

			echo '<div class="notice notice-error is-dismissible"><p>myCred Progress Bar requires myCred 2.3.1 or greater version to work your license properly.</p></div>';

		}

	}
endif;



function mycred_progress_bar() {
	return mycred_progress_bar::instance();
}
mycred_progress_bar();