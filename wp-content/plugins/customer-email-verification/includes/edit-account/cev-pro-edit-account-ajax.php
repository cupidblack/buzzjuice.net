<?php
/**
 * Edit Account AJAX Handler
 *
 * Handles all AJAX requests for edit account email verification functionality.
 * Uses standardized response format via CEV_Ajax_Response class.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Edit_Account_Ajax Class
 *
 * Handles AJAX requests for edit account email verification.
 */
class CEV_Edit_Account_Ajax {

	/**
	 * Instance of the edit account verification class.
	 *
	 * @var CEV_Edit_Account_Verification
	 */
	private $edit_account_verification;

	/**
	 * Initialize the AJAX handler.
	 *
	 * @since 1.0.0
	 * @param CEV_Edit_Account_Verification $edit_account_verification Edit account verification instance.
	 */
	public function __construct( $edit_account_verification ) {
		$this->edit_account_verification = $edit_account_verification;
		$this->init();
	}

	/**
	 * Initialize AJAX hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init() {
		add_action( 'wp_ajax_verify_email_on_edit_account', array( $this, 'handle_verify_email' ) );
		add_action( 'wp_ajax_resend_verification_code_on_edit_account', array( $this, 'handle_resend_code' ) );
	}

	/**
	 * Handle email verification AJAX request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_verify_email() {
		// Verify nonce.
		$nonce = isset( $_POST['wp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['wp_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wc_cev_email_edit_user_verify' ) ) {
			CEV_Ajax_Response::send_nonce_error();
			return;
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			CEV_Ajax_Response::send_error(
				'not_logged_in',
				__( 'You must be logged in to verify your email.', 'customer-email-verification' )
			);
			return;
		}

		$user_id = get_current_user_id();

		// Get and validate PIN code.
		$pin_code = isset( $_POST['pin'] ) ? wc_clean( wp_unslash( $_POST['pin'] ) ) : '';

		if ( empty( $pin_code ) ) {
			CEV_Ajax_Response::send_missing_field_error( 'pin' );
			return;
		}

		// Verify PIN code.
		$is_valid = $this->edit_account_verification->verify_pin_code( $pin_code );

		if ( ! $is_valid ) {
			// Check if code expired.
			$verification_data = get_user_meta( $user_id, 'cev_email_verification_pin', true );
			$expiration_option = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' );

			if ( 'never' !== $expiration_option && ! empty( $verification_data ) && is_array( $verification_data ) ) {
				$current_time = time();
				$expire_time  = isset( $verification_data['enddate'] ) ? (int) $verification_data['enddate'] : 0;

				if ( $current_time > $expire_time ) {
					delete_user_meta( $user_id, 'cev_edit_account_failed_attempts' );
					CEV_Ajax_Response::send_invalid_code_error( 'expired' );
					return;
				}
			}

			// Brute force protection: track failed attempts via user meta.
			$max_attempts    = 5;
			$failed_attempts = (int) get_user_meta( $user_id, 'cev_edit_account_failed_attempts', true );
			$failed_attempts++;
			if ( $failed_attempts >= $max_attempts ) {
				// Max attempts reached — delete PIN to invalidate it.
				delete_user_meta( $user_id, 'cev_email_verification_pin' );
				delete_user_meta( $user_id, 'cev_edit_account_failed_attempts' );
				CEV_Ajax_Response::send_invalid_code_error( 'max_attempts' );
			} else {
				update_user_meta( $user_id, 'cev_edit_account_failed_attempts', $failed_attempts );
				CEV_Ajax_Response::send_invalid_code_error( 'invalid' );
			}
			return;
		}

		// Clear failed attempt counter now that PIN is correct.
		delete_user_meta( $user_id, 'cev_edit_account_failed_attempts' );

		// Complete verification process.
		try {
			$result = $this->edit_account_verification->complete_verification();

			if ( ! $result ) {
				CEV_Ajax_Response::send_server_error( 'Failed to update user email during verification.' );
				return;
			}

			$success_message = get_option(
				'cev_verification_success_message',
				__( 'Your email is verified!', 'customer-email-verification' )
			);

			CEV_Ajax_Response::send_success(
				array(),
				$success_message,
				'email_verified'
			);
		} catch ( Exception $e ) {
			error_log( 'CEV Edit Account Verification Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Handle resend verification code AJAX request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_resend_code() {
		// Verify nonce.
		$nonce = isset( $_POST['wp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['wp_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wc_cev_email_edit_user_verify' ) ) {
			CEV_Ajax_Response::send_nonce_error();
			return;
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			CEV_Ajax_Response::send_error(
				'not_logged_in',
				__( 'You must be logged in to resend verification code.', 'customer-email-verification' )
			);
			return;
		}

		$user_id = get_current_user_id();

		// Check resend limit.
		if ( cev_pro()->function->is_resend_limit_reached( $user_id ) ) {
			$limit = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );
			CEV_Ajax_Response::send_limit_reached_error( $limit );
			return;
		}

		// Get temporary email.
		$temp_email = get_user_meta( $user_id, 'cev_temp_email', true );

		if ( empty( $temp_email ) ) {
			CEV_Ajax_Response::send_error(
				'no_pending_verification',
				__( 'No pending email verification found.', 'customer-email-verification' )
			);
			return;
		}

		// Set user ID for email common class.
		cev_pro()->function->wuev_user_id = $user_id;

		// Send verification email.
		try {
			$result = $this->edit_account_verification->send_verification_email( $temp_email );

			if ( ! $result ) {
				CEV_Ajax_Response::send_send_failed_error( 'Failed to send verification email for edit account.' );
				return;
			}

			// Increment resend counter.
			cev_pro()->function->increment_resend_counter( $user_id );

			$message = __( 'Verification code has been resent to your email.', 'customer-email-verification' );

			CEV_Ajax_Response::send_success(
				array(
					'resendLimitReached' => cev_pro()->function->is_resend_limit_reached( $user_id ),
				),
				$message,
				'code_resent'
			);
		} catch ( Exception $e ) {
			error_log( 'CEV Edit Account Resend Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_send_failed_error( $e->getMessage() );
		}
	}
}

