<?php
/**
 * Plugin Name: myCred BP Charges
 * Description: Charge your users for using BuddyPress features like sending messages, replying to messages or viewing other members profiles.
 * Version: 1.4.7
 * Tags: buddypress, messages, charge, cost, mycred
 * Author Email: support@mycred.me
 * Plugin URI: https://www.mycred.me/store/mycred-bp-charges/
 * Author: myCred
 * Author URI: http://mycred.me
 * Requires at least: WP 4.8
 * Tested up to: WP 6.3.2
 * Text Domain: bp_charge
 * Domain Path: /lang
 * License: Copyrighted
 *
 * Copyright © 2013 - 2023 myCred
 * 
 * Permission is hereby granted, to the licensed domain to install and run this
 * software and associated documentation files (the "Software") for an unlimited
 * time with the followning restrictions:
 *
 * - This software is only used under the domain name registered with the purchased
 *   license though the myCred website (mycred.me). Exception is given for localhost
 *   installations or test enviroments.
 *
 * - This software can not be copied and installed on a website not licensed.
 *
 * - This software is supported only if no changes are made to the software files
 *   or documentation. All support is voided as soon as any changes are made.
 *
 * - This software is not copied and re-sold under the current brand or any other
 *   branding in any medium or format.
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */  
if ( ! class_exists( 'myCRED_BP_Charges' ) ) :
	final class myCRED_BP_Charges {

		// Plugin Version
		public $version             = '1.4.7';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		// Modules
		public $modules             = NULL;

		// Point Types
		public $point_types         = NULL;

		// Account Object
		public $account             = NULL;

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
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.4.7' ); }

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.4.7' ); }

		/**
		 * Define
		 * @since 1.1.2
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
			elseif ( ! $definable && defined( $name ) )
				_doing_it_wrong( 'myCRED_BP_Charges->define()', 'Could not define: ' . $name . ' as it is already defined somewhere else!', '1.1.2' );
		}

		/**
		 * Require File
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
			else
				_doing_it_wrong( 'myCRED_BP_Charges->file()', 'Requested file ' . $required_file . ' not found.', '1.4.7' );
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
            $this->file( BP_CHARGES_ROOT . 'license/license.php' );
			
			add_action( 'bp_include',             array( $this, 'load_module' ), 120 );
			add_action( 'mycred_init',            array( $this, 'load_plugin_textdomain' ) );
			add_action( 'wp_enqueue_scripts',     array( $this, 'plugin_custom_styles' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ),20 );

			register_activation_hook( BP_CHARGES, array( $this, 'activate_mycred_bp_changes' ) );
		}

		/**
		 * Define Constants
		 * @since 1.1.2
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'BP_CHARGES_VERSION',       $this->version );
			$this->define( 'BP_CHARGES_SLUG',          'mycred-bp-charges' );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY',  'mycred_default' );

			$this->define( 'BP_CHARGES',               __FILE__ );
			$this->define( 'BP_CHARGES_ROOT',          plugin_dir_path( BP_CHARGES ) );
			$this->define( 'BP_CHARGES_ABSTRACT_DIR',  BP_CHARGES_ROOT . 'abstracts/' );
			$this->define( 'BP_CHARGES_CHARGE_DIR',    BP_CHARGES_ROOT . 'charges/' );
			$this->define( 'BP_CHARGES_INC_DIR',       BP_CHARGES_ROOT . 'includes/' );
			$this->define( 'BP_CHARGES_MODULES_DIR',   BP_CHARGES_ROOT . 'modules/' );
			$this->define( 'MYCRED_BP_CHARGES_INCLUDE_DIR_URL', plugin_dir_url( BP_CHARGES_ROOT) . 'includes/' );

		}

		/**
		 * Load Module
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function load_module() {            
			
			if ( ! class_exists( 'myCRED_Module' ) ) return;
			
			$this->file( BP_CHARGES_ABSTRACT_DIR . 'buddypress-charges.php' );

			$this->file( BP_CHARGES_INC_DIR . 'bp-charge-functions.php' );
			$this->file( BP_CHARGES_MODULES_DIR . 'bp-charges-module.php' );

			$this->file( BP_CHARGES_CHARGE_DIR . 'bp-charge-profile-view.php' );
			$this->file( BP_CHARGES_CHARGE_DIR . 'bp-charge-messaging.php' );
			$this->file( BP_CHARGES_CHARGE_DIR . 'bp-charge-activity-ranks-badges.php' );
			$this->file( BP_CHARGES_CHARGE_DIR . 'bp-charge-group-join.php' );

			$buddypress_charge = new myCRED_BuddyPress_Charges();
			$buddypress_charge->load();

		}

		/**
		 * Load Translation
		 * @since 1.0
		 * @version 1.0
		 */
		function load_plugin_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), 'bp_charge' );
			load_textdomain( 'bp_charge', WP_LANG_DIR . "/mycred-bp-charges/bp_charge-$locale.mo" );
			load_plugin_textdomain( 'bp_charge', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		}

		/**
		 * Activate
		 * @since 1.0
		 * @version 1.0.1
		 */
		function activate_mycred_bp_changes() {

			global $wpdb;

			$message    = array();

			// WordPress check
			$wp_version = $GLOBALS['wp_version'];
			if ( version_compare( $wp_version, '4.0', '<' ) )
				$message[] = __( 'This myCRED Add-on requires WordPress 4.0 or higher. Version detected:', 'bp_charge' ) . ' ' . $wp_version;

			// PHP check
			$php_version = phpversion();
			if ( version_compare( $php_version, '5.3', '<' ) )
				$message[] = __( 'This myCRED Add-on requires PHP 5.3 or higher. Version detected: ', 'bp_charge' ) . ' ' . $php_version;

			// SQL check
			$sql_version = $wpdb->db_version();
			if ( version_compare( $sql_version, '5.0', '<' ) )
				$message[] = __( 'This myCRED Add-on requires SQL 5.0 or higher. Version detected: ', 'bp_charge' ) . ' ' . $sql_version;

			// Not empty $message means there are issues
			if ( ! empty( $message ) ) {

				$error_message = implode( "\n", $message );
				die( __( 'Sorry but your WordPress installation does not reach the minimum requirements for running this add-on. The following errors were given:', 'bp_charge' ) . "\n" . $error_message );

			}

		}

		public function admin_enqueue_scripts() {

			wp_enqueue_style( 'mycred-bp-charges-admin-styles', plugin_dir_url( __FILE__ ) . '/assets/css/admin-styles.css' );

		}

		public function plugin_custom_styles() {

			wp_enqueue_style( 'mycred-bp-charges-styles', plugin_dir_url( __FILE__ ) . '/assets/css/styles.css' );
	   
		}

	}
endif;

function mycred_bp_charges_plugin() {
	return myCRED_BP_Charges::instance();
}
mycred_bp_charges_plugin();



