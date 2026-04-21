<?php
/**
 * All hooks for Customer Email Verification PRO
 * (admin menu, endpoints, my account, WPML, REST disable)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CEV_Pro_Hooks' ) ) {

	class CEV_Pro_Hooks {

		protected $plugin;
		private static $hooks_registered = false;

		/**
		 * Initialize hooks class
		 * 
		 * @param object $plugin_instance Optional. Main plugin instance. If not provided, gets it via cev_pro()
		 */
		public function __construct( $plugin_instance = null ) {
			// Prevent multiple registrations
			if ( self::$hooks_registered ) {
				return;
			}
			
			// Get main plugin instance - ensure we have a valid instance
			if ( null === $plugin_instance ) {
				if ( function_exists( 'cev_pro' ) ) {
					$plugin_instance = cev_pro();
				} else {
					return; // Can't proceed without plugin instance
				}
			}
			$this->plugin = $plugin_instance;
			
			// Mark as registered
			self::$hooks_registered = true;

			// Admin menu
			add_action( 'admin_menu', array( $this->plugin->admin, 'add_email_verification_submenu' ), 99 );

			// My account menu
			add_filter( 'woocommerce_account_menu_items', array( $this, 'cev_account_menu_items' ), 10 );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'hide_cev_menu_my_account' ), 999 );

			// Endpoints (login-authentication)
			add_action( 'init', array( $this, 'cev_add_my_account_endpoint' ) );

			// Only register if login authentication object exists
			if ( isset( $this->plugin->cev_pro_login_authentication ) && is_object( $this->plugin->cev_pro_login_authentication ) ) {
				add_action( 'woocommerce_account_login-authentication_endpoint', array( $this->plugin->cev_pro_login_authentication, 'cev_login_authentication_endpoint_content' ) );
			}

			// WPML endpoint support
			add_filter( 'wcml_register_endpoints_query_vars', array( $this, 'register_endpoint_WPML' ), 10, 3 );
			add_filter( 'wcml_endpoint_permalink_filter', array( $this, 'endpoint_permalink_filter' ), 10, 2 );
			add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ) );

			// Security: Disable Store API
			add_filter( 'rest_pre_dispatch', array( $this, 'disable_wc_endpoint' ), 10, 3 );

			// Resend verification email
			add_action( 'wp', array( $this, 'cev_resend_verification_email' ) );

			// Auto-verify user meta after successful signup verification.
			add_action( 'user_register', array( $this, 'auto_verify_user_on_registration' ), 10, 1 );

			// Tracking data
			add_filter( 'zorem_tracking_data', array( $this, 'get_settings_data' ) );

			// Verification popup message
			add_filter( 'cev_verification_popup_message', array( $this, 'cev_verification_popup_message_callback' ), 10, 2 );

			// Email content decoding filter (moved from Email Common class)
			add_filter( 'wc_cev_decode_html_content', array( cev_pro()->function, 'wc_cev_decode_html_content' ), 1 );
		}

		/**
		 * Auto-verify user on registration when signup OTP was previously verified.
		 *
		 * When a customer verifies their email via the registration OTP popup before
		 * creating an account, we store a transient keyed by their email address.
		 * This callback runs after the WordPress user is created and, if that flag
		 * exists, marks the user as verified in user meta.
		 *
		 * @since 2.8.13
		 * @param int $user_id Newly created user ID.
		 * @return void
		 */
		public function auto_verify_user_on_registration( $user_id ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! $user || ! isset( $user->user_email ) || ! is_email( $user->user_email ) ) {
				return;
			}

			$email               = $user->user_email;
			$verified_transient_key = 'cev_signup_email_verified_' . md5( strtolower( $email ) );
			$was_verified        = get_transient( $verified_transient_key );

			if ( false === $was_verified ) {
				return;
			}

			update_user_meta( $user_id, 'customer_email_verified', 'true' );
			delete_transient( $verified_transient_key );
		}

		/**
		 * Add custom menu items to WooCommerce My Account page
		 * 
		 * Adds "Login Authentication" menu item to the My Account navigation menu.
		 *
		 * @since 1.0.0
		 *
		 * @param array $items Existing My Account menu items
		 * @return array Modified menu items with new endpoints added
		 */
		public function cev_account_menu_items( $items ) {
			// Add login authentication menu item
			$items['login-authentication'] = __( 'Login Authentication', 'customer-email-verification' );
			
			return $items;
		}

		/**
		 * Hide custom menu items from My Account navigation
		 * 
		 * These endpoints are registered but hidden from the menu. They're accessed
		 * programmatically when needed.
		 *
		 * @since 1.0.0
		 *
		 * @param array $items My Account menu items
		 * @return array Menu items with verification endpoints removed
		 */
		public function hide_cev_menu_my_account( $items ) {
			// Remove login authentication from visible menu
			unset($items['login-authentication']);
			
			return $items;
		}

		/**
		 * Register custom rewrite endpoints for My Account page
		 * 
		 * Creates URL endpoints that can be accessed via:
		 * - /my-account/login-authentication/
		 * 
		 * Also handles permalink structure updates for older plugin versions.
		 *
		 * @since 1.0.0
		 */
		public function cev_add_my_account_endpoint() {
			// Register login authentication endpoint
			add_rewrite_endpoint( 'login-authentication', EP_PAGES );
			
			// Update permalink structure for older plugin versions (legacy compatibility)
			if (version_compare(get_option( 'cev_version' ), '1.7', '<') ) {
				global $wp_rewrite;
				$wp_rewrite->set_permalink_structure('/%postname%/');
				$wp_rewrite->flush_rules();
				update_option( 'cev_version', '1.7');
			}
		}

		/**
		 * Register endpoints with WPML for multilingual support
		 * 
		 * Ensures that custom endpoints are properly translated and registered
		 * when using WPML (WordPress Multilingual Plugin).
		 *
		 * @since 1.0.0
		 *
		 * @param array  $query_vars Existing query variables
		 * @param array  $wc_vars    WooCommerce query variables
		 * @param object $obj        WPML endpoint translation object
		 * @return array Modified query variables with translated endpoints
		 */
		public function register_endpoint_WPML( $query_vars, $wc_vars, $obj ) {
			// Register translated login authentication endpoint
			$query_vars['login-authentication'] = $obj->get_endpoint_translation('login-authentication', isset( $wc_vars['login-authentication']) ? $wc_vars['login-authentication'] : 'login-authentication' );
			
			return $query_vars;
		}

		/**
		 * Filter endpoint permalinks for WPML compatibility
		 * 
		 * Ensures endpoint permalinks remain consistent across different languages
		 * when using WPML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $endpoint The endpoint slug
		 * @param string $key      The endpoint key
		 * @return string The filtered endpoint slug
		 */
		public function endpoint_permalink_filter( $endpoint, $key ) {
			// Return standard endpoint for login authentication
			if ( 'login-authentication' == $key ) {
				return 'login-authentication';
			}
			
			return $endpoint;
		}

		/**
		 * Add custom query variables for endpoints
		 * 
		 * Registers custom query variables so WordPress recognizes the endpoints
		 * in URL parsing.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_vars Existing WooCommerce query variables
		 * @return array Modified query variables with custom endpoints
		 */
		public function add_query_vars( $query_vars ) {
			// Add login authentication query var
			$query_vars['login-authentication'] = 'login-authentication';
			
			return $query_vars;
		}

		/**
		 * Disable WC Store API checkout endpoint to stop carding attacks.
		 *
		 * Hooked to rest_pre_dispatch so it returns a proper JSON 403 error
		 * to REST clients (WC Blocks uses fetch, not browser navigation).
		 * Covers both the legacy route (/wc/store/checkout) and the current
		 * versioned route (/wc/store/v1/checkout).
		 *
		 * @since 1.0.0
		 * @param mixed           $result  Current dispatch result (null = not handled yet).
		 * @param WP_REST_Server  $server  REST server instance.
		 * @param WP_REST_Request $request Current REST request.
		 * @return mixed WP_Error if blocked, original $result otherwise.
		 */
		public function disable_wc_endpoint( $result, $server, $request ) {
			if ( 1 !== (int) cev_pro()->function->cev_pro_admin_settings( 'cev_disable_wooCommerce_store_api', 0 ) ) {
				return $result;
			}

			$route = $request->get_route();

			// Block both the legacy (/wc/store/checkout) and versioned (/wc/store/v1/checkout) routes.
			if ( false !== strpos( $route, '/wc/store/v1/checkout' ) || false !== strpos( $route, '/wc/store/checkout' ) ) {
				return new WP_Error(
					'cev_store_api_disabled',
					__( 'Checkout via the Store API is disabled.', 'customer-email-verification' ),
					array( 'status' => 403 )
				);
			}

			return $result;
		}

		/**
		 * Resend verification email handler
		 * 
		 * This function sends a new verification email to user if the user clicks on 'resend verification email' link.
		 * If the email is already verified then it shows a notice.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function cev_resend_verification_email() {
			if ( isset( $_GET['cev_redirect_limit_resend'] ) && '' !== $_GET['cev_redirect_limit_resend'] ) { // WPCS: input var ok, CSRF ok.
				
				$cev_redirect_limit_resend = wc_clean( $_GET['cev_redirect_limit_resend'] );
				$user_id = base64_decode( $cev_redirect_limit_resend ); // WPCS: input var ok, CSRF ok.

				if ( false === WC()->session->has_session() ) {
					WC()->session->set_customer_session_cookie( true );
				}

				$verified = get_user_meta( $user_id, 'customer_email_verified', true );

				if ( 'true' === $verified ) {
					$verified_message = cev_get_setting('cev_verified_user_message', 'Your email is already verified');
					wc_add_notice( $verified_message, 'notice' );
				} else {
					// Get my account page ID
					$my_account = get_option( 'woocommerce_myaccount_page_id' );
					if ( '' === $my_account ) {
						$my_account = get_option( 'page_on_front' );
					}

					cev_pro()->function->wuev_user_id                  = $user_id;
					cev_pro()->function->wuev_myaccount_page_id        = $my_account;
					
					$current_user = get_user_by( 'id', $user_id );
					
					$resend_limit_reached = cev_pro()->function->is_resend_limit_reached( $user_id );
					
					if ( $resend_limit_reached ) {
						return;
					}
					cev_pro()->function->increment_resend_counter( $user_id );
					
					cev_pro()->function->code_mail_sender( $current_user->user_email );
					$message = cev_get_setting('cev_resend_verification_email_message', 'A new verification link is sent. Check email.');
					$message = cev_pro()->function->maybe_parse_merge_tags( $message, cev_pro()->function );
					wc_add_notice( $message, 'notice' );
				}
			}
		}

		/**
		 * Get settings data for tracking
		 *
		 * @since 1.0.0
		 * @param array $data Existing tracking data.
		 * @return array Modified tracking data with plugin settings.
		 */
		public function get_settings_data( $data ) {
			$data['settings'] = array(
				'meta_key' => 'cev_pro_settings',
				'settings_data' => array(
					'cev_enable_email_verification' => array(
						'label' => 'Enable Signup Verification',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification', 1 )
					),
					'cev_enable_email_verification_checkout' => array(
						'label' => 'Enable Checkout Verification',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 )
					),
					'cev_enable_email_verification_free_orders' => array(
						'label' => 'Require checkout verification only for free orders',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders', 0 )
					),
					'cev_verification_type' => array(
						'label' => 'Verification type',
						'value' => cev_get_setting('cev_verification_type', 'otp')
					),
					'cev_verification_code_length' => array(
						'label' => 'OTP length',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_length', '1' )
					),
					'cev_verification_code_expiration' => array(
						'label' => 'OTP expiration',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' )
					),
					'cev_redirect_limit_resend' => array(
						'label' => 'Verification email resend limit',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', '1' )
					),
					'cev_resend_limit_message' => array(
						'label' => 'Resend limit message',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_resend_limit_message', 'Too many attempts, please contact us for further assistance' )
					),
					'cev_verification_success_message' => array(
						'label' => 'Email verification success message',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_verification_success_message', 'Your email is verified!' )
					),
					'cev_skip_verification_for_selected_roles' => array(
						'label' => 'Skip email verification for the selected user roles',
						'value' => cev_get_setting('cev_skip_verification_for_selected_roles', [])
					),
					'cev_enable_login_authentication' => array(
						'label' => 'Enable Login Authentication',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_enable_login_authentication', 1 )
					),
					'enable_email_otp_for_account' => array(
						'label' => 'Require OTP verification for unrecognized login',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'enable_email_otp_for_account', 1 )
					),
					'enable_email_auth_for_new_device' => array(
						'label' => 'Login from a new device',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_new_device', 1 )
					),
					'enable_email_auth_for_new_location' => array(
						'label' => 'Login from a new location',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_new_location', 1 )
					),
					'enable_email_auth_for_login_time' => array(
						'label' => 'Last login more then',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'enable_email_auth_for_login_time', 1 )
					),
					'cev_last_login_more_then_time' => array(
						'label' => 'Last Login more then Time',
						'value' => cev_pro()->function->cev_pro_admin_settings( 'cev_last_login_more_then_time', '15' )
					),
				),
			);

			return $data;
		}

		/**
		 * Return Email verification widget message
		 *
		 * Filters the verification popup message to allow customization via customizer settings.
		 * Replaces placeholders and parses merge tags.
		 *
		 * @since 1.0.0
		 * @param string $message Default verification message.
		 * @param string $email   Customer email address.
		 * @return string Filtered verification message.
		 */
		public function cev_verification_popup_message_callback( $message, $email ) {
			$message_text = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_message', $message );
			$message_text = str_replace( '{customer_email}', $email, $message_text );
			$message_text = cev_pro()->function->maybe_parse_merge_tags( $message_text, cev_pro()->function );
			if ( '' != $message_text ) {
				return $message_text;
			}
			return $message;
		}

	}
}
