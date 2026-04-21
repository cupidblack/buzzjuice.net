<?php
/**
 * Checkout AJAX Handler for Customer Email Verification
 *
 * Handles all AJAX requests related to checkout verification including
 * sending verification codes, verifying codes, and custom email verification.
 * All responses follow the standardized JSON schema.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Checkout_Ajax Class
 *
 * Manages all AJAX handlers for checkout verification with standardized responses.
 */
class CEV_Checkout_Ajax {

	/**
	 * Checkout verification instance.
	 *
	 * @var CEV_Checkout_Verification
	 */
	private $checkout_verification;

	/**
	 * Initialize the AJAX handler.
	 *
	 * @since 1.0.0
	 * @param CEV_Checkout_Verification $checkout_verification Checkout verification instance.
	 */
	public function __construct( $checkout_verification ) {
		$this->checkout_verification = $checkout_verification;
		$this->init();
	}

	/**
	 * Initialize AJAX hooks.
	 *
	 * Registers all AJAX action handlers for checkout verification.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init() {
		// Only register checkout-specific AJAX if checkout verification is enabled.
		if ( 1 == cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout' ) ) {
			add_action( 'wp_ajax_checkout_page_send_verification_code', array( $this, 'checkout_page_send_verification_code' ) );
			add_action( 'wp_ajax_nopriv_checkout_page_send_verification_code', array( $this, 'checkout_page_send_verification_code' ) );
			add_action( 'wp_ajax_nopriv_resend_verification_code_guest_user', array( $this, 'checkout_page_send_verification_code' ) );
			add_action( 'wp_ajax_resend_verification_code_guest_user', array( $this, 'checkout_page_send_verification_code' ) );
			add_action( 'wp_ajax_checkout_page_verify_code', array( $this, 'checkout_page_verify_code' ) );
			add_action( 'wp_ajax_nopriv_checkout_page_verify_code', array( $this, 'checkout_page_verify_code' ) );
			add_action( 'wp_ajax_send_email_on_chekout_page', array( $this, 'send_email_on_chekout_page' ), 10, 1 );
			add_action( 'wp_ajax_nopriv_send_email_on_chekout_page', array( $this, 'send_email_on_chekout_page' ), 10, 1 );
		}

		// Always register custom email verification (used by other features).
		add_action( 'wp_ajax_custom_email_verification', array( $this, 'custom_email_verification' ) );
		add_action( 'wp_ajax_nopriv_custom_email_verification', array( $this, 'custom_email_verification' ) );
	}

	/**
	 * Send verification code to guest user on checkout page.
	 *
	 * Handles AJAX request to send verification email to guest user.
	 * Returns standardized success/error response with resend limit information.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function checkout_page_send_verification_code() {
		try {
			// Verify nonce.
			if ( ! check_ajax_referer( 'wc_cev_email_guest_user', 'wp_nonce', false ) ) {
				CEV_Ajax_Response::send_nonce_error();
				return;
			}

			// Check if checkout verification is enabled.
			if ( 1 != cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification_checkout', 0 ) ) {
				CEV_Ajax_Response::send_error(
					'feature_disabled',
					__( 'Checkout verification is disabled.', 'customer-email-verification' )
				);
				return;
			}

			// Get and validate email.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$email = isset( $_POST['email'] ) ? wc_clean( wp_unslash( $_POST['email'] ) ) : '';

			if ( empty( $email ) ) {
				CEV_Ajax_Response::send_missing_field_error( 'email' );
				return;
			}

			if ( ! is_email( $email ) ) {
				CEV_Ajax_Response::send_invalid_email_error( $email );
				return;
			}

			// Check if email is already verified.
			$verified = cev_pro()->function->check_email_verify( $email );

			if ( true === $verified ) {
				$this->set_verified_session_data( $email );
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'     => $email,
						'isAlreadyVerified' => true,
					),
					__( 'Email is already verified.', 'customer-email-verification' ),
					'already_verified'
				);
				return;
			}

			// Check resend limit before sending.
			$resend_limit_info = $this->get_guest_resend_limit_info( $email );
			if ( $resend_limit_info['limit_reached'] ) {
				CEV_Ajax_Response::send_limit_reached_error( $resend_limit_info['limit'] );
				return;
			}

			// Send verification email with race-safe resend logic.
			$email_handler = $this->checkout_verification->get_email_handler();
			$result        = $this->send_verification_email_safe( $email, $email_handler );

			if ( $result['success'] ) {
				// Snapshot cart total at OTP send time to prevent cart manipulation bypass.
				WC()->session->set( 'cev_cart_total_at_otp_send', WC()->cart->subtotal );

				// Get updated resend limit info after sending.
				$updated_limit_info = $this->get_guest_resend_limit_info( $email );
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'      => $email,
						'limitReached'      => $updated_limit_info['limit_reached'],
						'remainingAttempts' => $updated_limit_info['remaining_attempts'],
					),
					__( 'Verification email sent successfully.', 'customer-email-verification' ),
					'email_sent'
				);
			} else {
				// Log error and return generic message.
				error_log( 'CEV Checkout: Failed to send verification email to ' . $email . ' - ' . $result['error'] );
				CEV_Ajax_Response::send_send_failed_error( 'Email send failed for: ' . $email );
			}
		} catch ( Exception $e ) {
			error_log( 'CEV Checkout AJAX Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Verify PIN code on checkout page.
	 *
	 * Handles AJAX request to verify the PIN code entered by guest user.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function checkout_page_verify_code() {
		try {
			// Verify nonce.
			if ( ! check_ajax_referer( 'checkout-verify-code', 'security', false ) ) {
				CEV_Ajax_Response::send_nonce_error();
				return;
			}

			// Get and validate PIN.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$post_pin = isset( $_POST['pin'] ) ? wc_clean( wp_unslash( $_POST['pin'] ) ) : '';

			if ( empty( $post_pin ) ) {
				CEV_Ajax_Response::send_missing_field_error( 'pin' );
				return;
			}

			// Get verification data from session.
			$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
			$cev_user_verification_data     = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;

			if ( empty( $cev_user_verification_data ) || ! isset( $cev_user_verification_data->pin ) || ! isset( $cev_user_verification_data->email ) ) {
				CEV_Ajax_Response::send_invalid_code_error( 'session_expired' );
				return;
			}

			// Brute force protection: check failed attempt count.
			$max_attempts    = 5;
			$failed_attempts = isset( $cev_user_verification_data->failed_attempts ) ? (int) $cev_user_verification_data->failed_attempts : 0;

			if ( $failed_attempts >= $max_attempts ) {
				// PIN already invalidated — require a new one.
				CEV_Ajax_Response::send_invalid_code_error( 'max_attempts' );
				return;
			}

			$session_pin   = base64_decode( $cev_user_verification_data->pin ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$session_email = $cev_user_verification_data->email;

			// Check PIN expiration if enabled.
			$expiration_setting = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' );
			if ( 'never' !== $expiration_setting && isset( $cev_user_verification_data->startdate ) ) {
				$start_time = isset( $cev_user_verification_data->startdate ) ? (int) $cev_user_verification_data->startdate : 0;
				$expire_time = isset( $cev_user_verification_data->enddate ) ? (int) $cev_user_verification_data->enddate : 0;
				$current_time = time();

				if ( $expire_time > 0 && $current_time > $expire_time ) {
					CEV_Ajax_Response::send_invalid_code_error( 'expired' );
					return;
				}
			}

			// Verify PIN matches.
			if ( $session_pin === $post_pin ) {
				$this->set_verified_session_data( $session_email );
				$this->mark_user_as_verified( $session_email );
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress' => $session_email,
					),
					__( 'Email verified successfully.', 'customer-email-verification' ),
					'verified'
				);
			} else {
				// Increment failed attempts counter.
				$failed_attempts++;
				if ( $failed_attempts >= $max_attempts ) {
					// Max attempts reached — invalidate PIN by clearing session data.
					WC()->session->set( 'cev_user_verification_data', null );
					CEV_Ajax_Response::send_invalid_code_error( 'max_attempts' );
				} else {
					// Update session with incremented counter.
					$cev_user_verification_data->failed_attempts = $failed_attempts;
					WC()->session->set( 'cev_user_verification_data', wp_json_encode( $cev_user_verification_data ) );
					CEV_Ajax_Response::send_invalid_code_error( 'invalid' );
				}
			}
		} catch ( Exception $e ) {
			error_log( 'CEV Checkout Verify AJAX Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Custom email verification handler.
	 *
	 * Generic handler for email verification requests from various sources.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function custom_email_verification() {
		try {
			// For logged-in users, check verification status first.
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();

				// Already verified — return already_verified (JS handles this gracefully).
				if ( 'true' === get_user_meta( $user_id, 'customer_email_verified', true ) ) {
					CEV_Ajax_Response::send_success(
						array( 'logged_in' => true, 'isAlreadyVerified' => true ),
						__( 'Email is already verified.', 'customer-email-verification' ),
						'already_verified'
					);
					return;
				}

				// Not verified — verify nonce, then send code to their account email.
				if ( ! check_ajax_referer( 'wc_cev_email_guest_user', 'wp_nonce', false ) ) {
					CEV_Ajax_Response::send_nonce_error();
					return;
				}

				$email             = wp_get_current_user()->user_email;
				$resend_limit_info = $this->get_guest_resend_limit_info( $email );

				if ( $resend_limit_info['limit_reached'] ) {
					CEV_Ajax_Response::send_limit_reached_error( $resend_limit_info['limit'] );
					return;
				}

				$email_handler = $this->checkout_verification->get_email_handler();
				$result        = $this->send_verification_email_safe( $email, $email_handler );

				if ( $result['success'] ) {
					$updated = $this->get_guest_resend_limit_info( $email );
					CEV_Ajax_Response::send_success(
						array(
							'emailAddress'      => $email,
							'limitReached'      => $updated['limit_reached'],
							'remainingAttempts' => $updated['remaining_attempts'],
						),
						__( 'Verification email sent successfully.', 'customer-email-verification' ),
						'email_sent'
					);
				} else {
					CEV_Ajax_Response::send_send_failed_error( 'Failed for logged-in user: ' . $email );
				}
				return;
			}

			// Verify nonce.
			if ( ! check_ajax_referer( 'wc_cev_email_guest_user', 'wp_nonce', false ) ) {
				CEV_Ajax_Response::send_nonce_error();
				return;
			}

			// Get and validate email.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$email = isset( $_POST['email'] ) ? wc_clean( wp_unslash( $_POST['email'] ) ) : '';

			if ( empty( $email ) ) {
				CEV_Ajax_Response::send_missing_field_error( 'email' );
				return;
			}

			if ( ! is_email( $email ) ) {
				CEV_Ajax_Response::send_invalid_email_error( $email );
				return;
			}

			// Set unverified session data initially.
			$verification_data = array(
				'email'    => $email,
				'verified' => false,
			);
			WC()->session->set( 'cev_user_verified_data', wp_json_encode( $verification_data ) );

			// Check if email is already verified.
			$verified = cev_pro()->function->check_email_verify( $email );

			if ( true === $verified ) {
				$this->set_verified_session_data( $email );
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'     => $email,
						'isAlreadyVerified' => true,
					),
					__( 'Email is already verified.', 'customer-email-verification' ),
					'already_verified'
				);
				return;
			}

			// Check resend limit before sending.
			$resend_limit_info = $this->get_guest_resend_limit_info( $email );
			if ( $resend_limit_info['limit_reached'] ) {
				CEV_Ajax_Response::send_limit_reached_error( $resend_limit_info['limit'] );
				return;
			}

			// Send verification email with race-safe resend logic.
			$email_handler = $this->checkout_verification->get_email_handler();
			$result        = $this->send_verification_email_safe( $email, $email_handler );

			if ( $result['success'] ) {
				// Get updated resend limit info after sending.
				$updated_limit_info = $this->get_guest_resend_limit_info( $email );
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'      => $email,
						'limitReached'      => $updated_limit_info['limit_reached'],
						'remainingAttempts' => $updated_limit_info['remaining_attempts'],
					),
					__( 'Verification email sent successfully.', 'customer-email-verification' ),
					'email_sent'
				);
			} else {
				// Log error and return generic message.
				error_log( 'CEV Custom Verification: Failed to send email to ' . $email . ' - ' . $result['error'] );
				CEV_Ajax_Response::send_send_failed_error( 'Email send failed for: ' . $email );
			}
		} catch ( Exception $e ) {
			error_log( 'CEV Custom Verification AJAX Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Send email on checkout page (inline verification).
	 *
	 * Handles AJAX request to send verification email, typically triggered
	 * by the "Send Verification Code" button on checkout.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function send_email_on_chekout_page() {
		try {
			// Verify nonce.
			if ( ! check_ajax_referer( 'checkout-send-verification-email', 'security', false ) ) {
				CEV_Ajax_Response::send_nonce_error();
				return;
			}

			// Get and validate email.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$email = isset( $_POST['email'] ) ? wc_clean( wp_unslash( $_POST['email'] ) ) : '';

			if ( empty( $email ) ) {
				CEV_Ajax_Response::send_error(
					'invalid_email',
					__( 'Email is required.', 'customer-email-verification' ),
					array(
						'field' => 'email',
						'checkoutWC' => $this->is_checkout_wc_active(),
					)
				);
				return;
			}

			if ( ! is_email( $email ) ) {
				CEV_Ajax_Response::send_error(
					'invalid_email',
					__( 'Invalid email address.', 'customer-email-verification' ),
					array(
						'field' => 'email',
						'checkoutWC' => $this->is_checkout_wc_active(),
					)
				);
				return;
			}

			$checkout_wc = $this->is_checkout_wc_active();

			// Check if email is already verified.
			$verified = cev_pro()->function->check_email_verify( $email );

			if ( true === $verified ) {
				$this->set_verified_session_data( $email );
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'      => $email,
						'isAlreadyVerified' => true,
						'isCheckoutWcActive' => $checkout_wc,
					),
					__( 'Email is already verified.', 'customer-email-verification' ),
					'already_verified'
				);
				return;
			}

			// Check if email is already verified in session (guests only).
			// Logged-in users are already checked via check_email_verify() above which reads user meta.
			// Skipping session check for logged-in users prevents stale session data from blocking OTP.
			if ( ! is_user_logged_in() ) {
				$cev_user_verified_data_raw = WC()->session->get( 'cev_user_verified_data' );
				$cev_user_verified_data     = ! is_null( $cev_user_verified_data_raw ) ? json_decode( $cev_user_verified_data_raw ) : null;

				if ( isset( $cev_user_verified_data->email ) && $cev_user_verified_data->email === $email && true === $cev_user_verified_data->verified ) {
					CEV_Ajax_Response::send_success(
						array(
							'emailAddress'      => $email,
							'needsVerification' => false,
							'isCheckoutWcActive' => $checkout_wc,
						),
						__( 'Email is already verified in this session.', 'customer-email-verification' ),
						'session_verified'
					);
					return;
				}
			}

			// Check if verification email was already sent for this email.
			$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
			$cev_user_verification_data     = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;

			if ( isset( $cev_user_verification_data->email ) && $cev_user_verification_data->email === $email ) {
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'      => $email,
						'needsVerification' => true,
						'isEmailSent'       => true,
						'isCheckoutWcActive' => $checkout_wc,
					),
					__( 'Verification code already sent. Please check your email.', 'customer-email-verification' ),
					'already_sent'
				);
				return;
			}

			// Check resend limit before sending.
			$resend_limit_info = $this->get_guest_resend_limit_info( $email );
			if ( $resend_limit_info['limit_reached'] ) {
				CEV_Ajax_Response::send_error(
					'limit_reached',
					cev_pro()->function->cev_pro_admin_settings(
						'cev_resend_limit_message',
						__( 'Too many attempts, please contact us for further assistance', 'customer-email-verification' )
					),
					array(
						'limit'     => $resend_limit_info['limit'],
						'checkoutWC' => $checkout_wc,
					)
				);
				return;
			}

			// Send verification email.
			WC()->customer->set_billing_email( $email );
			$email_handler = $this->checkout_verification->get_email_handler();
			$result        = $this->send_verification_email_safe( $email, $email_handler );

			if ( $result['success'] ) {
				// Snapshot cart total at OTP send time to prevent cart manipulation bypass.
				WC()->session->set( 'cev_cart_total_at_otp_send', WC()->cart->subtotal );

				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'      => $email,
						'isEmailSent'       => true,
						'needsVerification' => true,
						'isCheckoutWcActive' => $checkout_wc,
					),
					__( 'Verification email sent successfully.', 'customer-email-verification' ),
					'email_sent'
				);
			} else {
				// Log error and return generic message.
				error_log( 'CEV Inline Checkout: Failed to send email to ' . $email . ' - ' . $result['error'] );
				CEV_Ajax_Response::send_error(
					'send_failed',
					__( 'Failed to send verification email. Please try again later.', 'customer-email-verification' ),
					array(
						'checkoutWC' => $checkout_wc,
					)
				);
			}
		} catch ( Exception $e ) {
			error_log( 'CEV Inline Checkout AJAX Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Send verification email with race-safe resend logic.
	 *
	 * Uses transient locks to prevent race conditions when multiple
	 * requests try to send emails simultaneously.
	 *
	 * @since 1.0.0
	 * @param string                $email         Email address.
	 * @param CEV_Checkout_Email    $email_handler Email handler instance.
	 * @return array Array with 'success' (bool) and 'error' (string) keys.
	 */
	private function send_verification_email_safe( $email, $email_handler ) {
		$lock_key = 'cev_checkout_send_lock_' . md5( $email );
		$lock_timeout = 10; // 10 seconds lock timeout.

		// Try to acquire lock to prevent race conditions.
		$lock_acquired = false;
		$lock_attempts = 0;
		$max_lock_attempts = 5;

		while ( ! $lock_acquired && $lock_attempts < $max_lock_attempts ) {
			$existing_lock = get_transient( $lock_key );
			if ( false === $existing_lock ) {
				// Lock acquired - set it.
				set_transient( $lock_key, time(), $lock_timeout );
				$lock_acquired = true;
			} else {
				// Lock exists - wait a bit and retry.
				usleep( 200000 ); // 200ms
				$lock_attempts++;
			}
		}

		// If we couldn't acquire lock, another request is processing.
		if ( ! $lock_acquired ) {
			return array(
				'success' => false,
				'error'   => 'Could not acquire lock for email sending',
			);
		}

		try {
			// Send email.
			$result = $email_handler->send_verification_email_to_guest_user( $email );

			// Release lock.
			delete_transient( $lock_key );

			if ( $result ) {
				return array(
					'success' => true,
					'error'   => '',
				);
			} else {
				return array(
					'success' => false,
					'error'   => 'Email handler returned false',
				);
			}
		} catch ( Exception $e ) {
			// Release lock on error.
			delete_transient( $lock_key );
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Set verified session data for guest user.
	 *
	 * @since 1.0.0
	 * @param string $email Email address that was verified.
	 * @return void
	 */
	private function set_verified_session_data( $email ) {
		$verification_data = array(
			'email'    => $email,
			'verified' => true,
		);

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		WC()->session->set( 'cev_user_verified_data', wp_json_encode( $verification_data ) );
		WC()->customer->set_billing_email( $email );
	}

	/**
	 * Mark user as verified in user meta.
	 *
	 * @since 1.0.0
	 * @param string $email Email address of the user.
	 * @return void
	 */
	private function mark_user_as_verified( $email ) {
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			update_user_meta( $user->ID, 'customer_email_verified', 'true' );
		}
	}

	/**
	 * Get resend limit information for guest user.
	 *
	 * Retrieves the resend limit status for a guest user based on their email
	 * and the resend count stored in WooCommerce session.
	 *
	 * @since 1.0.0
	 * @param string $email Email address of the guest user.
	 * @return array Array with 'limit_reached' (bool), 'limit' (int), and 'remaining_attempts' (int) keys.
	 */
	private function get_guest_resend_limit_info( $email ) {
		// Get resend limit setting.
		$resend_limit = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );

		// Get current resend count from session.
		$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
		$cev_user_verification_data     = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;

		$resend_count = 0;
		if ( isset( $cev_user_verification_data->email ) && $cev_user_verification_data->email === $email ) {
			$resend_count = isset( $cev_user_verification_data->cev_guest_user_resend_times ) ? (int) $cev_user_verification_data->cev_guest_user_resend_times : 0;
		}

		// Check if limit is reached (limit + 1 because first send doesn't count as resend).
		$limit_reached = ( $resend_count >= $resend_limit + 1 );
		$remaining_attempts = max( 0, ( $resend_limit + 1 ) - $resend_count );

		return array(
			'limit_reached'     => $limit_reached,
			'limit'             => $resend_limit,
			'remaining_attempts' => $remaining_attempts,
		);
	}

	/**
	 * Check if CheckoutWC plugin is active.
	 *
	 * @since 1.0.0
	 * @return bool True if CheckoutWC is active, false otherwise.
	 */
	private function is_checkout_wc_active() {
		return is_plugin_active( 'checkout-for-woocommerce/checkout-for-woocommerce.php' );
	}
}
