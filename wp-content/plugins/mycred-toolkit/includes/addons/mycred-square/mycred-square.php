<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ms_buyCRED_Toolkit_Square_Gateway_Core' ) ) :
	final class ms_buyCRED_Toolkit_Square_Gateway_Core {

	

		// Plugin Slug
		public $slug                = 'mycred-square';

		// Textdomain
		public $domain              = 'mycred-toolkit';

		// Plugin name
		public $plugin_name         = 'MyCred Square';

		// Plugin ID
		public $id                  = '';

		// Plugin file
		public $plugin              = '';

		// Instnace
		protected static $_instance = null;

		// Current session
		public $session             = null;

		/**
		 * Setup Instance
		 */

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}
		public function __construct() {
		
			//check woosquare install or not
			$this->mcs_check_woosquare();

			//define constant variable
			$this->mcs_define_constants();


			//mycred methode
			$this->mcs_mycred();

			$this->plugin = $this->slug . '/' . $this->slug . '.php';

		//  $this->redirect_aftersuccc();
		}


		/**
		 * Check Woosquare
		 */
		public function mcs_check_woosquare() {
			$class = 'notice notice-error';
			if (!in_array('woosquare/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))
					and
					( !in_array('woosquare-pro/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins'))) )
					and
					( !in_array('woosquare-premium/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins'))) )
					or
					( !in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins'))) )
					or
					version_compare( PHP_VERSION, '5.5.0', '<' )
			) {

				$message = __('To use "My Cred Square " Woosquare and myCred must be activated!', 'mycred-toolkit');

				if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! wp_doing_ajax() ) {
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				}

				include_once ABSPATH . 'wp-admin/includes/plugin.php' ;
				if ( function_exists( 'deactivate_plugins' ) && is_plugin_active( 'mycred-square/mycred-square.php' ) ) {
					deactivate_plugins( 'mycred-square/mycred-square.php' );
				}
				//wp_die('','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
			}
		}

		/**
		 * Constant Defined
		 */

		private function mcs_define_constants() {

		
			$this->define( 'MS_MYCRED_SQUARE_SLUG', $this->slug );
			$this->define( 'MS_MYCRED_SLUG', 'mycred-toolkit' );
			$this->define( 'MS_MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );
			$this->define( 'MS_MYCRED_SQUARE', __FILE__ );
			$this->define( 'MS_MYCRED_SQUARE_ROOT_DIR', plugin_dir_path( MS_MYCRED_SQUARE ) );
			$this->define( 'MS_MYCRED_SQUARE_INCLUDES_DIR', MS_MYCRED_SQUARE_ROOT_DIR . 'includes/' );
			$this->define( 'MS_MYCRED_SQUARE_GATEWAY_DIR', MS_MYCRED_SQUARE_ROOT_DIR . 'gateways/' );
			$this->define( 'MS_MYCRED_SQUARE_MODULES_DIR', MS_MYCRED_SQUARE_ROOT_DIR . 'modules/' );
		}
		public function redirect_aftersuccc() {
			if (
					!empty($_REQUEST['access_token']) and
					!empty($_REQUEST['token_type']) and
					sanitize_text_field($_REQUEST['token_type']) == 'bearer'
			) {

				// if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

				if (function_exists('wp_verify_nonce') && !wp_verify_nonce($_GET['wc_woosquare_token_nonce'], 'connect_woosquare')) {
					wp_die(esc_html__('Cheatin&#8217; huh?', 'mycred-toolkit'));
				}


				$existing_token = get_option('woo_square_access_token_free');

				// if token already exists, don't continue
				// if (  empty( $existing_token ) OR empty(get_option('woo_square_access_token_cauth')) ) {
				$url_identifiers = array_map('esc_attr', $_REQUEST);
				update_option('woo_square_auth_response', sanitize_text_field($url_identifiers));
				update_option('woo_square_access_token_free', sanitize_text_field($_REQUEST['access_token']));
				update_option('woo_square_access_token_cauth', sanitize_text_field($_REQUEST['access_token']));
				update_option('woo_square_update_msg_dissmiss', 'connected');
				delete_option('woo_square_auth_notice');
				// }


				wp_redirect( add_query_arg(
						array(
							'page' => 'square-settings',
						),
						admin_url('admin.php')
				) );
				exit;


			}


			if (
					!empty($_REQUEST['disconnect_woosquare']) and
					!empty($_REQUEST['wc_woosquare_token_nonce'])
			) {
				
				if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['wc_woosquare_token_nonce'], 'disconnect_woosquare' ) ) {
					wp_die( esc_html__( 'Cheatin&#8217; huh?', 'mycred-toolkit' ) );
				}



				//revoke token
				$oauth_connect_url = WOOSQU_CONNECTURL;
				$headers = array(
					'Authorization' => 'Bearer ' . get_option('woo_square_access_token_free'), // Use verbose mode in cURL to determine the format you want for this header
					'Content-Type'  => 'application/json;',
				);
				$redirect_url = add_query_arg(
						array(
							'app_name'    => WOOSQU_APPNAME,
							'plug'    => WOOSQU_PLUGIN_NAME,
						),
						admin_url( 'admin.php' )
				);
				$redirect_url = wp_nonce_url( $redirect_url, 'connect_wcsrs', 'wc_wcsrs_token_nonce' );
				$site_url = ( urlencode( $redirect_url ) );
				$args_renew = array(
					'body' => array(
						'header' => $headers,
						'action' => 'revoke_token',
						'site_url'    => $site_url,
					),
					'timeout' => 45,
				);

				$oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );

				$decoded_oauth_response = json_decode( wp_remote_retrieve_body( $oauth_response ) );

				delete_option('woo_square_access_token_free');
				delete_option('woo_square_location_id_free');
				delete_option('woo_square_access_token_cauth');
				delete_option('woo_square_locations_free');
				delete_option('woo_square_business_name_free');
				delete_option('woo_square_auth_response');
				wp_redirect(add_query_arg(
						array(
							'page'    => 'square-settings',
						),
						admin_url( 'admin.php' )
				));
				exit;

			}
		}

		public function includes() {}


		/**
		 * Mycred Register
		 */

		public function mcs_mycred() {
			//register_activation_hook(   MS_MYCRED_SQUARE, array( __CLASS__, 'mcs_activate_plugin' ) );
			//register_deactivation_hook( MS_MYCRED_SQUARE, array( __CLASS__, 'mcs_deactivate_plugin' ) );
			//register_uninstall_hook(    MS_MYCRED_SQUARE, array( __CLASS__, 'mcs_uninstall_plugin' ) );
			add_filter( 'mycred_setup_gateways', array( $this, 'mcs_add_gateway' ) );
			add_action( 'mycred_buycred_load_gateways', array( $this, 'mcs_load_gateways' ) );
			
			add_action( 'wp_loaded', array( $this, 'redirect_aftersuccc' ), '', 10 );
			add_action('admin_menu', array( $this, 'mcs_square_custom_menus' ));
		}
		
		
		function mcs_square_custom_menus() {
		
			add_menu_page('MyCred Square', 'MyCred Square', 'manage_options', get_admin_url() . 'admin.php?page=mycred-gateways');
				add_submenu_page(get_admin_url() . 'admin.php?page=mycred-gateways', 'MyCred Square', 'MyCred Square', 'manage_options', get_admin_url() . 'admin.php?page=mycred-gateways');
				add_submenu_page( get_admin_url() . 'admin.php?page=mycred-gateways', __( 'Support', 'mycred-toolkit' ), __( 'Support', 'mycred-toolkit' ), 'manage_options', 'http://support.apiexperts.io/' );

			/* Technical Documentation */
				add_submenu_page( get_admin_url() . 'admin.php?page=mycred-gateways', __( 'Technical Documentation', 'mycred-toolkit' ), __( 'Technical Documentation', 'mycred-toolkit' ), 'manage_options', 'https://codex.mycred.me/chapter-iii/buycred/mycred-square/' );

			/* Contact Us */
				add_submenu_page( get_admin_url() . 'admin.php?page=mycred-gateways', __( 'Customization', 'mycred-toolkit' ), __( 'Customization', 'mycred-toolkit' ), 'manage_options', 'https://apiexperts.io/contact-us/' );
		}

		/**
		 * Mycred Register
		 */

		public function mcs_load_gateways() {
			$this->file( MS_MYCRED_SQUARE_GATEWAY_DIR . 'mycred-square.php' );
		}


		/**
		 * Gateway Class Register
		 */
		public function mcs_add_gateway( $gateways ) {

			$gateways['mycred-toolkit']     = array(
				'title'    => 'MyCred Square',
				'external' => true,
				'callback' => array( 'ms_myCRED_Square' )
			);

			return $gateways;
		}


		public function mcs_remove_square() {

			add_filter( 'mycred_setup_gateways', array( $this, 'remove_gateways' ) );
		}

		public static function mcs_activate_plugin() {

			global $wpdb;

			$message = array();

			// WordPress check
			$wp_version = $GLOBALS['wp_version'];
			if ( version_compare( $wp_version, '4.0', '<' ) ) {
				$message[] = esc_html(__( 'This myCRED Add-on requires WordPress 4.0 or higher. Version detected:', 'mycred-toolkit' )) . ' ' . $wp_version;
			}

			// PHP check
			$php_version = phpversion();
			if ( version_compare( $php_version, '5.3.3', '<' ) ) {
				$message[] = esc_html(__( 'This myCRED Add-on requires PHP 5.3.3 or higher. Version detected: ', 'mycred-toolkit' )) . ' ' . $php_version;
			}

			// SQL check
			$sql_version = $wpdb->db_version();
			if ( version_compare( $sql_version, '5.0', '<' ) ) {
				$message[] = esc_html(__( 'This myCRED Add-on requires SQL 5.0 or higher. Version detected: ', 'mycred-toolkit' )) . ' ' . $sql_version;
			}

			// myCRED Check
			if ( defined( 'MS_myCRED_VERSION' ) && version_compare( MS_myCRED_VERSION, '1.6', '<' ) ) {
				$message[] = esc_html(__( 'This add-on requires myCRED 1.6 or higher. Version detected:', 'mycred-toolkit' )) . ' ' . MS_myCRED_VERSION;
			}

			// Not empty $message means there are issues
			if ( ! empty( $message ) ) {

				$error_message = implode( "\n", $message );
				die( esc_html(esc_html__( 'Sorry but your WordPress installation does not reach the minimum requirements for running this add-on. The following errors were given:', 'mycred-toolkit' )) . "\n" . esc_html( $error_message) );

			}
		}


		public static function mcs_deactivate_plugin() { }


		public static function mcs_uninstall_plugin() { }
	}
endif;

function ms_mycred_toolkit_buycred_square_gateway() {
	return ms_buyCRED_Toolkit_Square_Gateway_Core::instance();
}
ms_mycred_toolkit_buycred_square_gateway();
