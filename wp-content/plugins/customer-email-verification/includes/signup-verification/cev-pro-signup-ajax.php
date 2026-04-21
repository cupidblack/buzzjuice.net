<?php
/**
 * AJAX Handlers Class for Signup Verification
 *
 * Handles all AJAX requests related to signup verification including
 * email existence checks, OTP verification, and OTP resending.
 * All responses follow the standardized JSON schema.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Signup_Ajax Class
 *
 * Manages AJAX request handling for signup verification with standardized responses.
 */
class CEV_Signup_Ajax {

	/**
	 * CAPTCHA validator instance.
	 *
	 * @var CEV_Signup_Captcha
	 */
	private $captcha_validator;

	/**
	 * Email sender instance.
	 *
	 * @var CEV_Signup_Email
	 */
	private $email_sender;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param CEV_Signup_Captcha $captcha_validator CAPTCHA validator instance.
	 * @param CEV_Signup_Email   $email_sender      Email sender instance.
	 */
	public function __construct( $captcha_validator, $email_sender ) {
		$this->captcha_validator = $captcha_validator;
		$this->email_sender      = $email_sender;
	}

	/**
	 * Handle AJAX request to check if email exists.
	 *
	 * Validates the email, checks CAPTCHA if present, verifies email doesn't
	 * already exist, and sends verification email if valid.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check_email_exists() {
		try {
			// Sanitize POST data (includes nonce verification).
			$sanitized_post = $this->sanitize_post_data();

			// Validate email input.
			$email = $this->get_email_from_request( $sanitized_post );
			if ( is_wp_error( $email ) ) {
				$error_data = $email->get_error_data();
				$error_email = isset( $error_data['email'] ) ? $error_data['email'] : '';
				CEV_Ajax_Response::send_invalid_email_error( $error_email );
				return;
			}

			// Check if any CAPTCHA plugin is active and validate it.
			$captcha_valid = $this->captcha_validator->validate();
			if ( is_wp_error( $captcha_valid ) ) {
				CEV_Ajax_Response::send_captcha_error( $captcha_valid->get_error_message() );
				return;
			}

			// Check if email is already verified.
			$verification_status = $this->check_email_verification_status( $email );
			if ( ! empty( $verification_status ) ) {
				CEV_Ajax_Response::send_success(
					array(
						'emailAddress'     => $email,
						'isAlreadyVerified' => true,
					),
					$verification_status['message'],
					'already_verified'
				);
				return;
			}

			// Validate against WooCommerce and WordPress registration filters.
			$registration_errors = $this->validate_registration( $email );
			if ( is_wp_error( $registration_errors ) && $registration_errors->has_errors() ) {
				CEV_Ajax_Response::send_error(
					'registration_validation_failed',
					$registration_errors->get_error_message(),
					array(
						'email' => $email,
					)
				);
				return;
			}

			// Send OTP email for verification.
			$email_sent = $this->email_sender->send_verification_email( $email );

			// Track initial email send for resend limit counting.
			if ( $email_sent ) {
				$resend_limit = cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );
				if ( $resend_limit > 0 ) {
					$resend_transient_key = 'cev_signup_resend_' . md5( $email );
					// Initialize count to 1 for initial send.
					set_transient( $resend_transient_key, 1, DAY_IN_SECONDS );
				}

				CEV_Ajax_Response::send_success(
					array(
						'emailAddress' => $email,
					),
					__( 'Verification email has been sent.', 'customer-email-verification' ),
					'email_sent'
				);
			} else {
				// Log error and return generic message.
				error_log( 'CEV Signup: Failed to send verification email to ' . $email );
				CEV_Ajax_Response::send_send_failed_error( 'Email send failed for: ' . $email );
			}
		} catch ( Exception $e ) {
			error_log( 'CEV Signup Check Email AJAX Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Handle AJAX request to verify OTP.
	 *
	 * Validates the OTP code against the stored PIN in the database.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function verify_otp() {
		global $wpdb;

		// Start output buffering to catch any unexpected output.
		ob_start();

		try {
			// Sanitize POST data (includes nonce verification).
			// Note: sanitize_post_data() will exit on nonce failure.
			$sanitized_post = $this->sanitize_post_data();

			// Get OTP and email from sanitized POST data.
			$otp   = isset( $sanitized_post['otp'] ) ? $sanitized_post['otp'] : '';
			$email = isset( $sanitized_post['email'] ) ? $sanitized_post['email'] : '';

			// Validate OTP is provided.
			if ( empty( $otp ) ) {
				ob_end_clean();
				CEV_Ajax_Response::send_missing_field_error( 'otp' );
				return;
			}

			// Validate email is provided.
			if ( empty( $email ) || ! is_email( $email ) ) {
				ob_end_clean();
				CEV_Ajax_Response::send_invalid_email_error( $email );
				return;
			}

			// Check OTP against database.
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name cannot be a placeholder.
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cev_user_log WHERE email = %s AND pin = %s", $email, $otp ) );

			if ( ! $row ) {
				// Brute force protection: track failed attempts per email via transient.
				$max_attempts    = 5;
				$attempts_key    = 'cev_signup_failed_' . md5( strtolower( $email ) );
				$failed_attempts = (int) get_transient( $attempts_key );
				$failed_attempts++;
				ob_end_clean();
				if ( $failed_attempts >= $max_attempts ) {
					// Max attempts reached — delete OTP record to invalidate it.
					$wpdb->delete( $wpdb->prefix . 'cev_user_log', array( 'email' => $email ), array( '%s' ) );
					delete_transient( $attempts_key );
					CEV_Ajax_Response::send_invalid_code_error( 'max_attempts' );
				} else {
					set_transient( $attempts_key, $failed_attempts, HOUR_IN_SECONDS );
					CEV_Ajax_Response::send_invalid_code_error( 'invalid' );
				}
				return;
			}

			// Check if already verified.
			// Note: verified is stored as varchar(10), so we check for 'true' or '1'.
			if ( 'true' === $row->verified || '1' === $row->verified || (int) $row->verified > 0 ) {
				ob_end_clean();
				CEV_Ajax_Response::send_error(
					'already_verified',
					__( 'This email has already been verified.', 'customer-email-verification' ),
					array(
						'email' => $email,
					)
				);
				return;
			}

			// Check OTP expiration if expiration is enabled.
			$expiration_setting = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' );
			if ( 'never' !== $expiration_setting && ! empty( $row->last_updated ) ) {
				$last_updated_timestamp = strtotime( $row->last_updated );
				$current_timestamp       = current_time( 'timestamp' );
				$expiration_seconds      = (int) $expiration_setting;
				$time_elapsed            = $current_timestamp - $last_updated_timestamp;

				if ( $time_elapsed > $expiration_seconds ) {
					ob_end_clean();
					CEV_Ajax_Response::send_invalid_code_error( 'expired' );
					return;
				}
			}

			// Store a short-lived flag so we can auto-verify the user meta after registration.
			// This ensures that users who verify their email via OTP before registering
			// are marked as verified once their WordPress account is created.
			$verified_transient_key = 'cev_signup_email_verified_' . md5( strtolower( $email ) );
			set_transient( $verified_transient_key, true, HOUR_IN_SECONDS );

			// Delete the verification record from cev_user_log table after successful verification.
			// This cleans up the temporary verification data after the user successfully verifies their OTP.
			$wpdb->delete(
				$wpdb->prefix . 'cev_user_log',
				array( 'email' => $email ),
				array( '%s' )
			);

			// Clear any failed attempt counter for this email.
			delete_transient( 'cev_signup_failed_' . md5( strtolower( $email ) ) );

			// Clean any output before sending JSON.
			ob_end_clean();

			// Send success response.
			CEV_Ajax_Response::send_success(
				array(
					'email'        => $email,
					'redirect_url' => home_url() . '/my-account/',
					'verified'     => true, // Add verified flag for JavaScript compatibility.
				),
				__( 'Registration and verification successful', 'customer-email-verification' ),
				'verified'
			);
		} catch ( Exception $e ) {
			ob_end_clean();
			error_log( 'CEV Signup Verify OTP AJAX Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Handle AJAX request to resend OTP.
	 *
	 * Resends the verification email with a new PIN.
	 * Enforces resend limit based on admin settings with race-safe logic.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function resend_otp() {
		global $wpdb;

		// Start output buffering to catch any unexpected output.
		ob_start();

		try {
			// Sanitize POST data (includes nonce verification).
			// Note: sanitize_post_data() will exit on nonce failure.
			$sanitized_post = $this->sanitize_post_data();

			// Get email from sanitized POST data.
			$recipient = isset( $sanitized_post['email'] ) ? $sanitized_post['email'] : '';
			if ( empty( $recipient ) || ! is_email( $recipient ) ) {
				ob_end_clean();
				CEV_Ajax_Response::send_invalid_email_error( $recipient );
				return;
			}

			// Check resend limit if enabled.
			$resend_limit = (int) cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );

			// If resend is disabled (limit = 0), block resend.
			if ( 0 === $resend_limit ) {
				ob_end_clean();
				CEV_Ajax_Response::send_limit_reached_error( 0 );
				return;
			}

			// If limit is set and greater than 0, check resend attempts with race-safe logic.
			if ( $resend_limit > 0 ) {
				// Track resend attempts using a transient key based on email.
				$resend_transient_key = 'cev_signup_resend_' . md5( $recipient );

				// Use a lock transient to prevent race conditions.
				$lock_key = $resend_transient_key . '_lock';
				$lock_timeout = 10; // 10 seconds lock timeout.

				// Try to acquire lock. If lock exists, another request is processing.
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
					ob_end_clean();
					CEV_Ajax_Response::send_error(
						'concurrent_request',
						__( 'Another request is processing. Please wait a moment and try again.', 'customer-email-verification' )
					);
					return;
				}

				// Get current resend count (inside lock).
				$resend_count = (int) get_transient( $resend_transient_key );

				// The resend_limit represents the number of resend attempts allowed.
				// Initial send counts as 1, so we need to check if resend_count exceeds (limit + 1).
				// For example: "Allow 1 Attempt" means 1 initial send + 1 resend = 2 total sends allowed.
				// So we block when resend_count > (limit + 1) or resend_count >= (limit + 2).
				// Simplified: block when resend_count > limit (since initial send is already counted).
				if ( $resend_count > $resend_limit ) {
					// Release lock before returning.
					delete_transient( $lock_key );
					ob_end_clean();
					CEV_Ajax_Response::send_limit_reached_error( $resend_limit );
					return;
				}

				// Increment resend count BEFORE sending email (atomic operation).
				// This prevents race conditions where two requests both pass the limit check.
				$resend_count++;
				set_transient( $resend_transient_key, $resend_count, DAY_IN_SECONDS );

				// Send verification email.
				$result = $this->email_sender->send_verification_email( $recipient );

				// Release lock after email is sent (or failed).
				delete_transient( $lock_key );

				// If email sending failed, decrement the count we just incremented.
				// This allows the user to retry without being blocked by a failed attempt.
				if ( ! $result ) {
					$resend_count--;
					if ( $resend_count > 0 ) {
						set_transient( $resend_transient_key, $resend_count, DAY_IN_SECONDS );
					} else {
						delete_transient( $resend_transient_key );
					}
				}
			} else {
				// No limit - send email directly.
				$result = $this->email_sender->send_verification_email( $recipient );
			}

			ob_end_clean();

			if ( $result ) {
				// Email sent successfully - return success response.
				CEV_Ajax_Response::send_success(
					array(
						'email' => $recipient,
					),
					__( 'Verification email sent successfully', 'customer-email-verification' ),
					'email_sent'
				);
			} else {
				// Email sending failed - return error response.
				error_log( 'CEV Signup Resend: Failed to send verification email to ' . $recipient );
				CEV_Ajax_Response::send_send_failed_error( 'Email send failed for: ' . $recipient );
			}
		} catch ( Exception $e ) {
			ob_end_clean();
			error_log( 'CEV Signup Resend OTP AJAX Error: ' . $e->getMessage() );
			CEV_Ajax_Response::send_server_error( $e->getMessage() );
		}
	}

	/**
	 * Sanitize POST data after nonce verification.
	 *
	 * This method also double-checks the nonce to satisfy static analysis tools
	 * which flag direct access to $_POST without an explicit nonce check.
	 *
	 * @since 1.0.0
	 * @return array Array of sanitized POST data (or exits with JSON error on failure).
	 */
	private function sanitize_post_data() {
		// Ensure we don't process form data without nonce verification.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'verify_otp_nonce' ) ) {
			CEV_Ajax_Response::send_nonce_error();
			// wp_send_json_error exits after echoing JSON, but call exit to be explicit.
			exit;
		}

		$sanitized = array();

		if ( isset( $_POST['email'] ) ) {
			// Use wp_unslash then sanitize.
			$sanitized['email'] = sanitize_email( wp_unslash( $_POST['email'] ) );
		}

		if ( isset( $_POST['otp'] ) ) {
			$sanitized['otp'] = sanitize_text_field( wp_unslash( $_POST['otp'] ) );
		}

		return $sanitized;
	}

	/**
	 * Get and validate email from sanitized POST data.
	 *
	 * @since 1.0.0
	 * @param array $sanitized_post Array of sanitized POST data.
	 * @return string|WP_Error Valid email address or WP_Error if invalid.
	 */
	private function get_email_from_request( $sanitized_post ) {
		$email = isset( $sanitized_post['email'] ) ? $sanitized_post['email'] : '';
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'customer-email-verification' ), array( 'email' => $email ) );
		}
		return $email;
	}

	/**
	 * Check email verification status in database.
	 *
	 * @since 1.0.0
	 * @param string $email Email address to check.
	 * @return array|false Verification status array or false if not found.
	 */
	private function check_email_verification_status( $email ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name cannot be a placeholder.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cev_user_log WHERE email = %s", $email ) );

		// Note: verified is stored as varchar(10), so we check for 'true' or '1'.
		if ( $row && ( 'true' === $row->verified || '1' === $row->verified || (int) $row->verified > 0 ) ) {
			return array(
				'already_verify' => true,
				'message'        => __( 'Email is already verified.', 'customer-email-verification' ),
			);
		}

		return false;
	}

	/**
	 * Validate email against WooCommerce and WordPress registration filters.
	 *
	 * @since 1.0.0
	 * @param string $email Email address to validate.
	 * @return WP_Error Error object if validation fails, empty WP_Error if valid.
	 */
	private function validate_registration( $email ) {
		$errors = new WP_Error();
		$errors = apply_filters( 'woocommerce_registration_errors', $errors, '', $email );
		$errors = apply_filters( 'registration_errors', $errors, '', $email );
		return $errors;
	}
}
