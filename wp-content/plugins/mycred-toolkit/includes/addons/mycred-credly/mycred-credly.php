<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MyCRED_Credly' ) ) :
	final class MyCRED_Credly {

		// Instnace
		protected static $_instance = null;

		public $credly_admin_notice = '';

		/**
		 * Setup Instance
		 * @since 1.1.2
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
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.1.2' ); }

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.1.2' ); }

		/**
		 * Define
		 * @since 1.1.2
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			} elseif ( ! $definable && defined( $name ) ) {
				_doing_it_wrong( 'MyCRED_Credly->define()', 'Could not define: ' . esc_html( $name ) . ' as it is already defined somewhere else!', '1.1.2' );
			}
		}

		/**
		 * Require File
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			} else {
				_doing_it_wrong( 'MyCRED_Credly->file()', 'Requested file ' . esc_html( $required_file ) . ' not found.', '1.1.2' );
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
			add_action( 'mycred_init', array( $this, 'load_modules' ), 10, 1 );
		}

		/**
		 * Define Constants
		 * @since 1.1.2
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'MYCRED_CREDLY_VERSION', '1.2' );
			$this->define( 'MYCRED_CREDLY_SLUG', 'mycred-credly' );
			$this->define( 'MYCRED_CREDLY', __FILE__ );
			$this->define( 'MYCRED_CREDLY_ROOT', plugin_dir_path( MYCRED_CREDLY ) );
			$this->define( 'MYCRED_CREDLY_INC', MYCRED_CREDLY_ROOT . 'includes/' );
			$this->define( 'MYCRED_CREDLY_ASSETS', plugin_dir_url( MYCRED_CREDLY ) . 'assets/' );
		}

		/**
		 * Load Module
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function load_modules() {

			$this->load_plugin_textdomain();

			if ( class_exists( 'myCRED_Addons_Module' ) ) {
				$mycred_modules = new myCRED_Addons_Module();
				if ( $mycred_modules->is_active( 'badges' ) ) {

					add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
					add_action( 'wp_enqueue_scripts', array( $this, 'wp_load_assets' ) );

					$this->file( MYCRED_CREDLY_INC . 'mycred-credly-settings.php' );

					$this->file( MYCRED_CREDLY_INC . 'mycred-credly-badge.php' );
				} else {
					$this->mycred_credly_set_admin_notices( __('myCred Credly requires myCred Badges Addon to be activatd', 'mycred-toolkit') );
				}
			}
		}

		public function wp_load_assets() {
			wp_register_style( 'style-mycred-credly', MYCRED_CREDLY_ASSETS . 'css/style.css' ); 
		}

		/**
		 * Load Assets
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function load_assets() {

			$screen = get_current_screen();
			if ( $screen->id == MYCRED_BADGE_KEY ) {
				add_thickbox();
			}
			
			wp_register_style( 'style-mycred-credly', MYCRED_CREDLY_ASSETS . 'css/style.css' );
			wp_enqueue_style( 'style-mycred-credly' );
			wp_enqueue_script( 'script-mycred-credly', MYCRED_CREDLY_ASSETS . 'js/script.js', array( 'jquery', 'jquery-ui-autocomplete' ) );
			wp_localize_script( 'script-mycred-credly', 'mycred-toolkit', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('mycred_nounce_credly_badge_list'),
				'loading' => MYCRED_CREDLY_ASSETS . 'images/loading.gif'
			));
		}

		/**
		 * Load Translation
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_plugin_textdomain() {
			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), 'mycred_credly' );
			load_textdomain( 'mycred_credly', WP_LANG_DIR . "/mycred-credly/mycred_credly-$locale.mo" );
			load_plugin_textdomain( 'mycred_credly', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		}
		
		public function mycred_credly_set_admin_notices( $msg ) {
			$this->credly_admin_notice = $msg;
			add_action( 'admin_notices', array( $this, 'mycred_credly_admin_notice' ) );
		}

		public function mycred_credly_admin_notice() {
			$class = 'notice notice-error';
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $this->credly_admin_notice ) );
		}
	}

	MyCRED_Credly::instance();
endif;
