<?php
/**
 * Enqueue class for Customer Email Verification PRO
 *
 * Move admin and front enqueue functions here from main plugin file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CEV_Enqueue_Pro' ) ) {

	class CEV_Enqueue_Pro {

		/**
		 * Constructor - register hooks
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 4 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		}

		/**
		 * Single callback for wp_enqueue_scripts that calls all frontend enqueue methods.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function enqueue_frontend_scripts() {
			// Enqueue signup verification scripts.
			$this->signup_verification_enqueue();
			
			// Enqueue checkout verification scripts if enabled.
			$this->enqueue_checkout_scripts();
			
			// Enqueue edit account verification scripts.
			$this->front_enqueue();
		}

		/**
		 * Enqueue admin styles and scripts for the Email Verification settings page.
		 *
		 * @param string $hook The current admin page hook suffix.
		 */
		public function admin_styles( $hook ) {
			// Ensure the styles and scripts are loaded only on the Email Verification settings page.
			if ( !isset( $_GET['page'] ) || 'customer-email-verification-for-woocommerce' != $_GET['page'] ) {
				return;
			}
			
			// Determine script suffix based on debug mode.
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			
			// Enqueue jQuery (required for many scripts).
			wp_enqueue_script( 'jquery' );
			
			// Enqueue select2 and related scripts for enhanced dropdown functionality.
			wp_enqueue_script( 'wc-select2', WC()->plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.3' );
			wp_enqueue_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );

			// Enqueue BlockUI script for loading spinners.
			wp_enqueue_script( 'wc-jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		
			// Enqueue WooCommerce admin styles.
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), time() );
			
			// Enqueue DataTables library for enhanced table management.
			wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), time(), true);
			wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css', array(), time());
			
			// Enqueue custom admin scripts and styles for the plugin.
			wp_enqueue_script( 'cev_pro_admin_js', cev_pro()->plugin_dir_url() . 'assets/js/admin.js', array('jquery', 'wp-util', 'datatables-js', 'wc-jquery-tiptip'), time(), true );
			wp_enqueue_style( 'cev-pro-admin-css', cev_pro()->plugin_dir_url() . 'assets/css/admin.css', array(), time() );
			
			// Localize script to pass variables and data to JavaScript.
			wp_localize_script( 'cev_pro_admin_js', 'cev_pro_admin_js', array() );
			wp_localize_script('cev_pro_admin_js', 'iconUrls', array(
				'verified' => cev_pro()->plugin_dir_url() . 'assets/css/images/checked.png',
				'unverified' => cev_pro()->plugin_dir_url() . 'assets/css/images/cross.png',
			));
			wp_localize_script('cev_pro_admin_js', 'cev_vars', array(
				'delete_user_nonce' => wp_create_nonce('delete_user_nonce'),
				'ajax_url' => admin_url('admin-ajax.php'),
				'cev_create_an_account_during_checkout' => get_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' ) ,
			)); 

			// Enqueue additional scripts and media uploader.
			wp_enqueue_script( 'media-lib-uploader-js' );
			wp_enqueue_script( 'wc-jquery-tiptip');
			wp_enqueue_media();
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script(
				'wc-dompurify',
				cev_pro()->plugin_dir_url() . '/assets/js/purify.min.js',
				array(),
				time(),
				true
			);
		}

		/**
		 * Frontend enqueue for edit account page only.
		 * Checkout verification enqueue is handled by CEV_Checkout_Enqueue class.
		 */
		public function front_enqueue() {
			// Register scripts for edit account page
			$this->register_checkout_scripts_and_styles();

			// edit-account check
			global $wp;
			$request = array();
			if ( isset( $wp->request ) ) {
				$request = explode( '/', $wp->request );
			}
			if ( ! empty( $request ) && ( end( $request ) === 'edit-account' ) && is_account_page() ) {
				$cev_temp_email = get_user_meta( get_current_user_id(), 'cev_temp_email', true );
				if ( null != $cev_temp_email ) {
					wp_enqueue_script( 'cev-underscore-js' );
					wp_enqueue_script( 'cev-front-js' );
					wp_enqueue_style( 'dashicons' );
				}
			}
		}

		/**
		 * Enqueue scripts and styles for signup verification.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function signup_verification_enqueue() {
			$plugin_instance = cev_pro();
			$plugin_url      = $plugin_instance->plugin_dir_url();
			wp_enqueue_script( 'cev-signup-script', $plugin_url . 'assets/js/signup-script.js', array( 'jquery', 'wp-i18n' ), time(), true );
			wp_enqueue_style( 'cev-custom-style', $plugin_url . 'assets/css/signup-style.css', array(), time() );

			// Set resend delay to 30 seconds for signup verification.
			// This is independent of OTP expiration setting.
			$resend_delay = 30;

			// Get success message from settings.
			$success_message = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_success_message', __( 'Your email is verified!', 'customer-email-verification' ) );
			
			// Get resend limit message from settings.
			$resend_limit_message = cev_pro()->function->cev_pro_admin_settings( 'cev_resend_limit_message', __( 'Too many attempts, please contact us for further assistance', 'customer-email-verification' ) );

			wp_localize_script(
				'cev-signup-script',
				'cev_ajax',
				array(
					'ajax_url'                        => admin_url( 'admin-ajax.php' ),
					'nonce'                           => wp_create_nonce( 'verify_otp_nonce' ),
					'loaderImage'                     => $plugin_url . 'assets/images/Eclipse.svg',
					'enableEmailVerification'         => (bool) cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification' ),
					'password_setup_link_enabled'     => get_option( 'woocommerce_registration_generate_password', 'no' ),
					'cev_password_validation'         => __( 'Password is required.', 'customer-email-verification' ),
					'cev_email_validation'            => __( 'Email is required.', 'customer-email-verification' ),
					'cev_email_exists_validation'     => __( 'An account with this email address already exists. Please use a different email or log in to your existing account.', 'customer-email-verification' ),
					'cev_valid_email_validation'      => __( 'Enter a valid email address.', 'customer-email-verification' ),
					'cev_Validation_error_position_id' => apply_filters( 'change_validation_error_message_position', '#customer_login' ),
					'count_down_text'                 => __( 'You can resend the code in', 'customer-email-verification' ),
					'resend_delay'                    => $resend_delay,
					'success_message'                 => $success_message,
					'resend_limit_message'            => $resend_limit_message,
					'resend_success_message'          => __( 'Verification email sent successfully', 'customer-email-verification' ),
				)
			);
		}

		/**
		 * Enqueue checkout verification scripts and styles.
		 *
		 * Registers and enqueues all necessary scripts and styles for checkout
		 * verification functionality, including localization data.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function enqueue_checkout_scripts() {
			// Feature gate: Only enqueue if checkout verification is enabled.
			if ( 1 != cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 ) ) {
				return;
			}

			// Register scripts and styles.
			$this->register_checkout_scripts_and_styles();

			// Localize scripts with AJAX data.
			$this->localize_checkout_scripts();

			// Enqueue on specific pages.
			$this->enqueue_checkout_on_pages();
		}

		/**
		 * Register all checkout scripts and styles.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function register_checkout_scripts_and_styles() {
			// Only register if not already registered.
			if ( wp_script_is( 'cev-underscore-js', 'registered' ) ) {
				return;
			}

			$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Register scripts.
			wp_register_script( 'cev-underscore-js', cev_pro()->plugin_dir_url() . 'assets/js/underscore-min.js', array( 'jquery' ), time() );
			wp_register_script( 'cev-front-js', cev_pro()->plugin_dir_url() . 'assets/js/front.js', array( 'jquery', 'wp-i18n' ), time(), true );
			wp_register_script( 'cev-inline-front-js', cev_pro()->plugin_dir_url() . 'assets/js/inline-verification.js', array( 'jquery', 'wp-i18n' ), time() );

			// Register styles and BlockUI script.
			wp_register_style( 'cev_front_style', cev_pro()->plugin_dir_url() . 'assets/css/front.css', array(), time() );
			wp_register_script( 'wc-jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		}

		/**
		 * Localize checkout scripts with AJAX data.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function localize_checkout_scripts() {
			$cev_ajax_object = $this->get_checkout_localization_data();

			// Localize for both scripts.
			wp_localize_script( 'cev-front-js', 'cev_ajax_object', $cev_ajax_object );
			wp_localize_script( 'cev-inline-front-js', 'cev_ajax_object', $cev_ajax_object );
		}

		/**
		 * Get localization data for checkout scripts.
		 *
		 * @since 1.0.0
		 * @return array Localization data array.
		 */
		private function get_checkout_localization_data() {
			$guest_checkout_enabled = 'yes' === get_option( 'woocommerce_enable_guest_checkout' );
			$cev_create_account_during_checkout = $guest_checkout_enabled ? cev_pro()->function->cev_pro_admin_settings( 'cev_create_an_account_during_checkout', 0 ) : 0;

			// Get session data.
			$cev_user_verified_data_raw = WC()->session->get( 'cev_user_verified_data', '' );
			$cev_user_verified_data = json_decode( (string) $cev_user_verified_data_raw, true );
			$cev_user_verified_data = $cev_user_verified_data ? $cev_user_verified_data : array();

			// Get settings.
			$cev_enable_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 );
			$cev_inline_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_checkout_dropdown_option' );
			$cev_enable_email_verification_free_orders = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );

			// Calculate order subtotal.
			$order_subtotal = 0;
			if ( WC()->cart ) {
				$order_subtotal = isset( WC()->cart->subtotal ) ? WC()->cart->subtotal : 0;
			}

			// Determine if inline verification is needed.
			$need_inline_verification = $this->calculate_need_inline_verification( $order_subtotal, $cev_enable_email_verification_checkout, $cev_inline_email_verification_checkout, $cev_enable_email_verification_free_orders );

			// Get verified email status using the central check_email_verify() helper.
			// For logged-in users this reads user meta (admin unverify takes effect immediately).
			// For guests it falls back to WC session.
			if ( is_user_logged_in() ) {
				$verified_email = wp_get_current_user()->user_email;
				$is_verified    = cev_pro()->function->check_email_verify( $verified_email );
				if ( ! $is_verified ) {
					$verified_email = '';
				}
			} else {
				$verified_email = isset( $cev_user_verified_data['email'] ) ? (string) $cev_user_verified_data['email'] : '';
				$is_verified    = false;
				if ( isset( $cev_user_verified_data['verified'] ) ) {
					$is_verified = filter_var( $cev_user_verified_data['verified'], FILTER_VALIDATE_BOOLEAN );
				}
			}

			$checkout_wc = is_plugin_active( 'checkout-for-woocommerce/checkout-for-woocommerce.php' );

			return array(
				'ajax_url'                                 => admin_url( 'admin-ajax.php' ),
				'checkout_send_verification_email'         => wp_create_nonce( 'checkout-send-verification-email' ),
				'checkout_verify_code'                     => wp_create_nonce( 'checkout-verify-code' ),
				'wc_cev_email_guest_user'                  => wp_create_nonce( 'wc_cev_email_guest_user' ),
				'cev_login_auth_with_otp_nonce'            => wp_create_nonce( 'cev_login_auth_with_otp' ),
				'cev_resend_login_otp_nonce'               => wp_create_nonce( 'cev_resend_login_otp' ),
				'count_down_text'                          => __( 'You can resend the code in', 'customer-email-verification' ),
				'cev_verification_checkout_dropdown_option' => cev_pro()->function->cev_pro_admin_settings( 'cev_verification_checkout_dropdown_option' ),
				'cev_enable_email_verification_checkout'   => cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout' ),
				'need_inline_verification'                 => $need_inline_verification ? 1 : 0,
				'is_user_logged_in'                        => is_user_logged_in() ? 1 : 0,
				'checkoutWC'                               => $checkout_wc ? true : false,
				'verification_send_msg'                    => __( 'We sent a verification code to your email', 'customer-email-verification' ),
				'email_verification_code_label'            => __( 'Email verification code', 'customer-email-verification' ),
				'verification_code_error_msg'              => __( 'Verification code does not match', 'customer-email-verification' ),
				'verify_button_text'                       => __( 'Verify', 'customer-email-verification' ),
				'resend_verification_msg'                  => __( 'Resend verification code', 'customer-email-verification' ),
				'email_required_msg'                       => __( 'Email is required for verification', 'customer-email-verification' ),
				'invalid_email_msg'                        => __( 'Email is Invalid', 'customer-email-verification' ),
				'email_verified_msg'                       => __( 'Email address verified', 'customer-email-verification' ),
				'email_verified_required_msg'              => __( 'Please verify your email to enable the Place Order button.', 'customer-email-verification' ),
				'all_ready_email_verified_msg'             => __( 'Email address is already verified', 'customer-email-verification' ),
				'cev_create_an_account_during_checkout'    => isset( $cev_create_account_during_checkout ) ? $cev_create_account_during_checkout : '',
				'guest_checkout_enabled'                   => ! empty( $guest_checkout_enabled ) ? 1 : 0,
				'verifiedEmail'                            => $verified_email,
				'isVerified'                               => $is_verified,
				'count_down_text'                          => __( 'You can resend the code in', 'customer-email-verification' ),
				'cancel_verification_text'                 => __( 'Cancel Verification', 'customer-email-verification' ),
				'please_enter_code_msg'                    => __( 'Please enter the verification code', 'customer-email-verification' ),
				'invalid_verification_code_msg'            => __( 'Invalid verification code', 'customer-email-verification' ),
				'network_error_msg'                        => __( 'Network error. Please check your connection and try again.', 'customer-email-verification' ),
				'server_error_msg'                         => __( 'Server error. Please try again later.', 'customer-email-verification' ),
				'invalid_response_msg'                     => __( 'Invalid response from server', 'customer-email-verification' ),
				'verification_error_msg'                   => __( 'An error occurred while verifying. Please try again.', 'customer-email-verification' ),
				'email_verified_success_msg'               => __( 'Email verified successfully', 'customer-email-verification' ),
			);
		}

		/**
		 * Calculate if inline verification is needed.
		 *
		 * @since 1.0.0
		 * @param float $order_subtotal                            Order subtotal.
		 * @param int   $cev_enable_email_verification_checkout    Checkout verification enabled.
		 * @param int   $cev_inline_email_verification_checkout    Inline verification mode.
		 * @param int   $cev_enable_email_verification_free_orders Free orders exempt setting.
		 * @return bool True if inline verification is needed, false otherwise.
		 */
		private function calculate_need_inline_verification( $order_subtotal, $cev_enable_email_verification_checkout, $cev_inline_email_verification_checkout, $cev_enable_email_verification_free_orders ) {
			// Verified logged-in users do not need inline verification.
			if ( is_user_logged_in()
				&& 'true' === get_user_meta( get_current_user_id(), 'customer_email_verified', true )
			) {
				return false;
			}

			// If order has value and free orders are not exempt, verification needed.
			if ( $order_subtotal > 0 && 1 !== (int) $cev_enable_email_verification_free_orders ) {
				return true;
			}

			// If order is free and inline mode is enabled, verification needed.
			if ( 0 === (int) $order_subtotal && 1 === (int) $cev_enable_email_verification_checkout && 2 === (int) $cev_inline_email_verification_checkout ) {
				return true;
			}

			return false;
		}

		/**
		 * Enqueue checkout scripts on specific pages.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function enqueue_checkout_on_pages() {
			global $wp;

			// Enqueue on login authentication page.
			$login_authentication_url = rtrim( wc_get_account_endpoint_url( 'login-authentication' ), '/' );

			if ( isset( $wp->request ) ) {
				$current_url = rtrim( home_url( $wp->request ), '/' );
				if ( $current_url === $login_authentication_url ) {
					$this->enqueue_verification_scripts();
				}
			}

			// Enqueue on checkout and cart pages.
			if ( is_checkout() || is_cart() ) {
				$this->enqueue_verification_scripts();

				// Enqueue inline verification script if needed.
				$cev_enable_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 1 );
				$cev_inline_email_verification_checkout = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_checkout_dropdown_option' );
				$cev_enable_email_verification_free_orders = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_free_orders' );
				$order_subtotal = WC()->cart ? ( isset( WC()->cart->subtotal ) ? WC()->cart->subtotal : 0 ) : 0;

				$need_inline_verification = $this->calculate_need_inline_verification(
					$order_subtotal,
					$cev_enable_email_verification_checkout,
					$cev_inline_email_verification_checkout,
					$cev_enable_email_verification_free_orders
				);

				$user_needs_inline = is_user_logged_in()
					? ( 'true' !== get_user_meta( get_current_user_id(), 'customer_email_verified', true ) )
					: true;

				if ( 1 === (int) $cev_enable_email_verification_checkout && 2 === (int) $cev_inline_email_verification_checkout && $need_inline_verification && $user_needs_inline ) {
					wp_enqueue_script( 'cev-inline-front-js' );
				}
			}
		}

		/**
		 * Enqueue verification scripts and styles.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function enqueue_verification_scripts() {
			wp_enqueue_style( 'cev_front_style' );
			wp_enqueue_script( 'cev-underscore-js' );
			wp_enqueue_script( 'cev-front-js' );
			wp_enqueue_script( 'wc-jquery-blockui' );
		}
	}
}
