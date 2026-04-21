<?php
/**
 * Standardized AJAX Response Helper Class
 *
 * Provides consistent JSON response formatting for all AJAX endpoints
 * following the standardized schema.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Ajax_Response Class
 *
 * Handles standardized JSON responses for AJAX endpoints.
 */
class CEV_Ajax_Response {

	/**
	 * Send a standardized success response.
	 *
	 * @since 1.0.0
	 * @param mixed  $data    Response data payload.
	 * @param string $message Human-readable success message.
	 * @param string $code    Optional short code for the response.
	 * @return void
	 */
	public static function send_success( $data = null, $message = '', $code = '' ) {
		$response = array(
			'success' => true,
		);

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		if ( ! empty( $code ) ) {
			$response['code'] = $code;
		}

		wp_send_json( $response );
	}

	/**
	 * Send a standardized error response.
	 *
	 * @since 1.0.0
	 * @param string $code    Short error code (e.g., 'invalid_email', 'limit_reached').
	 * @param string $message Human-readable error message.
	 * @param array  $details Optional additional error details (e.g., field errors).
	 * @return void
	 */
	public static function send_error( $code, $message, $details = array() ) {
		$response = array(
			'success' => false,
			'error'   => array(
				'code'    => $code,
				'message' => $message,
			),
		);

		if ( ! empty( $details ) ) {
			$response['error']['details'] = $details;
		}

		wp_send_json( $response );
	}

	/**
	 * Send error response for nonce verification failure.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function send_nonce_error() {
		self::send_error(
			'permission_denied',
			__( 'Security check failed. Please refresh the page and try again.', 'customer-email-verification' )
		);
	}

	/**
	 * Send error response for invalid email.
	 *
	 * @since 1.0.0
	 * @param string $email The invalid email address (optional, for details).
	 * @return void
	 */
	public static function send_invalid_email_error( $email = '' ) {
		$details = array();
		if ( ! empty( $email ) ) {
			$details['email'] = $email;
		}

		self::send_error(
			'invalid_email',
			__( 'Invalid email address.', 'customer-email-verification' ),
			$details
		);
	}

	/**
	 * Send error response for missing required field.
	 *
	 * @since 1.0.0
	 * @param string $field_name Name of the missing field.
	 * @return void
	 */
	public static function send_missing_field_error( $field_name ) {
		self::send_error(
			'missing_field',
			/* translators: %s: Field name */
			sprintf( __( '%s is required.', 'customer-email-verification' ), ucfirst( $field_name ) ),
			array( 'field' => $field_name )
		);
	}

	/**
	 * Send error response for resend limit reached.
	 *
	 * @since 1.0.0
	 * @param int $limit The resend limit that was reached.
	 * @return void
	 */
	public static function send_limit_reached_error( $limit = 0 ) {
		$message = cev_pro()->function->cev_pro_admin_settings( 'cev_resend_limit_message', '' );
		if ( empty( $message ) ) {
			$message = __( 'Too many attempts, please contact us for further assistance', 'customer-email-verification' );
		}

		$details = array();
		if ( $limit > 0 ) {
			$details['limit'] = $limit;
		}

		self::send_error(
			'limit_reached',
			$message,
			$details
		);
	}

	/**
	 * Send error response for CAPTCHA validation failure.
	 *
	 * @since 1.0.0
	 * @param string $captcha_message Specific CAPTCHA error message.
	 * @return void
	 */
	public static function send_captcha_error( $captcha_message = '' ) {
		$message = ! empty( $captcha_message )
			? $captcha_message
			: __( 'CAPTCHA verification failed. Please try again.', 'customer-email-verification' );

		self::send_error(
			'captcha_invalid',
			$message
		);
	}

	/**
	 * Send error response for email sending failure.
	 *
	 * @since 1.0.0
	 * @param string $log_message Optional message to log internally.
	 * @return void
	 */
	public static function send_send_failed_error( $log_message = '' ) {
		if ( ! empty( $log_message ) ) {
			error_log( 'CEV Email Send Failed: ' . $log_message );
		}

		self::send_error(
			'send_failed',
			__( 'Failed to send verification email. Please try again later.', 'customer-email-verification' )
		);
	}

	/**
	 * Send error response for invalid verification code.
	 *
	 * @since 1.0.0
	 * @param string $reason Optional reason (e.g., 'expired', 'invalid', 'session_expired').
	 * @return void
	 */
	public static function send_invalid_code_error( $reason = 'invalid' ) {
		$messages = array(
			'expired'         => __( 'The verification code has expired. Please request a new code.', 'customer-email-verification' ),
			'invalid'         => __( 'Invalid verification code. Please check your email and try again.', 'customer-email-verification' ),
			'session_expired' => __( 'Verification session expired. Please request a new code.', 'customer-email-verification' ),
			'max_attempts'    => __( 'Too many incorrect attempts. Please request a new verification code.', 'customer-email-verification' ),
		);

		$message = isset( $messages[ $reason ] ) ? $messages[ $reason ] : $messages['invalid'];

		self::send_error(
			'invalid_code',
			$message,
			array( 'reason' => $reason )
		);
	}

	/**
	 * Send error response for generic server error.
	 *
	 * @since 1.0.0
	 * @param string $log_message Message to log internally.
	 * @return void
	 */
	public static function send_server_error( $log_message = '' ) {
		if ( ! empty( $log_message ) ) {
			error_log( 'CEV Server Error: ' . $log_message );
		}

		self::send_error(
			'server_error',
			__( 'An unexpected error occurred. Please try again later.', 'customer-email-verification' )
		);
	}
}

