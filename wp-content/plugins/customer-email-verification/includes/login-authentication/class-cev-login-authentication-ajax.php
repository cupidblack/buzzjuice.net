<?php
/**
 * Login Authentication AJAX Handler
 *
 * Handles all AJAX requests for login authentication functionality.
 * Uses standardized response format via CEV_Ajax_Response class.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Login_Authentication_Ajax Class
 *
 * Handles AJAX requests for login authentication with OTP verification.
 */
class CEV_Login_Authentication_Ajax {

	/**
	 * Instance of the login authentication class.
	 *
	 * @var WC_Customer_Email_Verification_Login_Authentication
	 */
	private $login_auth;

	/**
	 * Initialize the AJAX handler.
	 *
	 * @param WC_Customer_Email_Verification_Login_Authentication $login_auth Login authentication instance.
	 */
	public function __construct( $login_auth ) {
		$this->login_auth = $login_auth;
		$this->init();
	}

	/**
	 * Initialize AJAX hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'wp_ajax_nopriv_cev_login_auth_with_otp', array( $this, 'handle_otp_verification' ) );
		add_action( 'wp_ajax_cev_login_auth_with_otp', array( $this, 'handle_otp_verification' ) );
		add_action( 'wp_ajax_nopriv_cev_resend_login_otp', array( $this, 'handle_resend_otp' ) );
		add_action( 'wp_ajax_cev_resend_login_otp', array( $this, 'handle_resend_otp' ) );
	}

	/**
	 * Handle OTP verification AJAX request.
	 *
	 * @return void
	 */
	public function handle_otp_verification() {
		// Verify nonce.
		$nonce = isset( $_POST['cev_login_auth_with_otp'] ) ? sanitize_key( wp_unslash( $_POST['cev_login_auth_with_otp'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cev_login_auth_with_otp' ) ) {
			CEV_Ajax_Response::send_nonce_error();
			return;
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			CEV_Ajax_Response::send_error(
				'not_logged_in',
				__( 'You must be logged in to verify your OTP.', 'customer-email-verification' )
			);
			return;
		}

		$user_id = get_current_user_id();

		// Get stored and submitted OTP.
		$stored_otp   = get_user_meta( $user_id, 'cev_login_otp', true );
		$submitted_otp = isset( $_POST['cev_login_otp'] ) ? wc_clean( wp_unslash( $_POST['cev_login_otp'] ) ) : '';

		// Validate OTP presence.
		if ( empty( $stored_otp ) ) {
			CEV_Ajax_Response::send_error(
				'invalid_code',
				__( 'No OTP code found. Please request a new code.', 'customer-email-verification' ),
				array( 'reason' => 'not_found' )
			);
			return;
		}

		if ( empty( $submitted_otp ) ) {
			CEV_Ajax_Response::send_missing_field_error( 'cev_login_otp' );
			return;
		}

		// Verify OTP match.
		if ( $stored_otp !== $submitted_otp ) {
			$max_attempts    = 5;
			$failed_attempts = (int) get_user_meta( $user_id, 'cev_login_failed_attempts', true );
			$failed_attempts++;
			if ( $failed_attempts >= $max_attempts ) {
				// Max attempts reached — delete OTP to invalidate it.
				delete_user_meta( $user_id, 'cev_login_otp' );
				delete_user_meta( $user_id, 'cev_login_failed_attempts' );
				CEV_Ajax_Response::send_invalid_code_error( 'max_attempts' );
			} else {
				update_user_meta( $user_id, 'cev_login_failed_attempts', $failed_attempts );
				CEV_Ajax_Response::send_invalid_code_error( 'invalid' );
			}
			return;
		}

		// OTP verified successfully - process verification.
		try {
			$this->process_successful_verification( $user_id );
		} catch ( Exception $e ) {
			error_log( 'CEV Login OTP Verification Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Process successful OTP verification.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 * @throws Exception If processing fails.
	 */
	private function process_successful_verification( $user_id ) {
		// Get redirect page.
		$my_account       = cev_pro()->my_account;
		$redirect_page_id = get_option( 'cev_redirect_page_after_varification', $my_account );

		// Clean up OTP data, resend counter, and failed attempt counter.
		delete_user_meta( $user_id, 'cev_login_otp' );
		delete_user_meta( $user_id, 'cev_login_otp_required' );
		delete_user_meta( $user_id, 'cev_user_resend_times' );
		delete_user_meta( $user_id, 'cev_login_failed_attempts' );

		// Update login details.
		$login_details = $this->login_auth->get_current_login_details();
		if ( ! empty( $login_details ) ) {
			$this->login_auth->update_user_login_details( $user_id, $login_details );
		}

		// Get success message.
		$verification_success_message = get_option(
			'cev_verification_success_message',
			__( 'Your Email is verified!', 'customer-email-verification' )
		);

		// Add WooCommerce notice.
		wc_add_notice( $verification_success_message, 'notice' );

		// Send success response.
		CEV_Ajax_Response::send_success(
			array(
				'url' => get_permalink( $redirect_page_id ),
			),
			$verification_success_message,
			'otp_verified'
		);
	}

	/**
	 * Handle resend login OTP AJAX request.
	 *
	 * @return void
	 */
	public function handle_resend_otp() {
		// Verify nonce.
		$nonce = isset( $_POST['cev_resend_login_otp'] ) ? sanitize_key( wp_unslash( $_POST['cev_resend_login_otp'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cev_resend_login_otp' ) ) {
			CEV_Ajax_Response::send_nonce_error();
			return;
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			CEV_Ajax_Response::send_error(
				'not_logged_in',
				__( 'You must be logged in to resend OTP.', 'customer-email-verification' )
			);
			return;
		}

		$user_id = get_current_user_id();
		$user    = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			CEV_Ajax_Response::send_error(
				'invalid_user',
				__( 'Invalid user.', 'customer-email-verification' )
			);
			return;
		}

		$user_email = $user->user_email;

		// Ensure session exists.
		if ( false === WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		// Get stored OTP.
		$login_otp         = get_user_meta( $user_id, 'cev_login_otp', true );
		$login_otp_required = get_user_meta( $user_id, 'cev_login_otp_required', true );

		// Check if OTP is required and exists.
		if ( empty( $login_otp_required ) || ( '1' !== $login_otp_required && true !== $login_otp_required && 1 !== $login_otp_required ) ) {
			CEV_Ajax_Response::send_error(
				'otp_not_required',
				__( 'OTP verification is not required for your account.', 'customer-email-verification' )
			);
			return;
		}

		// Check resend limit.
		$resend_limit = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );
		
		if ( $resend_limit > 0 ) {
			// Check if resend limit is reached.
			if ( cev_pro()->function->is_resend_limit_reached( $user_id ) ) {
				$resend_limit_message = cev_pro()->function->cev_pro_admin_settings( 
					'cev_resend_limit_message', 
					__( 'Too many attempts, please contact us for further assistance', 'customer-email-verification' ) 
				);
				CEV_Ajax_Response::send_error(
					'resend_limit_reached',
					$resend_limit_message,
					array( 'code' => 'resend_limit_reached' )
				);
				return;
			}
		}

		if ( empty( $login_otp ) ) {
			// Generate new OTP if none exists.
			$login_otp = cev_pro()->function->generate_verification_pin();
			update_user_meta( $user_id, 'cev_login_otp', $login_otp );
		}

		// Prevent duplicate sends within 2 minutes using transient.
		$transient_key = 'cev_resend_login_otp_ajax_' . $user_id;
		$last_sent = get_transient( $transient_key );
		
		if ( false !== $last_sent ) {
			// Email was sent recently, don't send again.
			CEV_Ajax_Response::send_error(
				'email_sent_recently',
				__( 'Please wait a few minutes before requesting another OTP email.', 'customer-email-verification' )
			);
			return;
		}

		// Send the OTP email.
		$email_sent = $this->login_auth->send_login_otp_email( $user_email, $login_otp );

		// Check if email was sent successfully.
		if ( ! $email_sent ) {
			CEV_Ajax_Response::send_error(
				'email_send_failed',
				__( 'Failed to send OTP email. Please try again later.', 'customer-email-verification' )
			);
			return;
		}

		// Set transient to prevent duplicate sends for 2 minutes (120 seconds).
		set_transient( $transient_key, time(), 120 );

		// Increment resend counter if limit is enabled.
		if ( $resend_limit > 0 ) {
			cev_pro()->function->increment_resend_counter( $user_id );
		}

		// Send success response.
		CEV_Ajax_Response::send_success(
			array(),
			__( 'A new login OTP email has been sent.', 'customer-email-verification' ),
			'otp_resent'
		);
	}

}

