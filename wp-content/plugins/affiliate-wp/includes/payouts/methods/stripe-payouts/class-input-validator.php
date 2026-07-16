<?php
/**
 * Input Validator Class
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Classes/Input Validator
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Payouts_Input_Validator Class
 *
 * Centralized input validation for security enhancement
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Payouts_Input_Validator {

	/**
	 * Validate and sanitize parameters
	 *
	 * @since 2.29.0
	 * @param array $params         Array of parameters to validate
	 * @param array $required_params Array of required parameter names
	 * @param array $optional_params Array of optional parameter names with validation rules
	 * @return array|WP_Error Validated parameters or error
	 */
	public static function validate_params( $params, $required_params = [], $optional_params = [] ) {
		$validated = [];

		// Check required parameters
		foreach ( $required_params as $param ) {
			if ( ! isset( $params[ $param ] ) || empty( $params[ $param ] ) ) {
				return new WP_Error( 'missing_param', sprintf( __( 'Required parameter "%s" is missing.', 'affiliate-wp' ), $param ) );
			}

			$validated[ $param ] = self::validate_single_param( $param, $params[ $param ] );
			if ( is_wp_error( $validated[ $param ] ) ) {
				return $validated[ $param ];
			}
		}

		// Check optional parameters
		foreach ( $optional_params as $param => $validation_rule ) {
			// Handle both indexed and associative arrays
			if ( is_numeric( $param ) ) {
				$param = $validation_rule;
				$validation_rule = null;
			}
			
			if ( isset( $params[ $param ] ) && ! empty( $params[ $param ] ) ) {
				$validated[ $param ] = self::validate_single_param( $param, $params[ $param ], $validation_rule );
				if ( is_wp_error( $validated[ $param ] ) ) {
					return $validated[ $param ];
				}
			}
		}

		return $validated;
	}

	/**
	 * Validate and sanitize GET parameters (wrapper for backwards compatibility)
	 *
	 * @deprecated 2.29.0 Use validate_params() instead
	 * @since 2.29.0
	 * @param array $required_params Array of required parameter names
	 * @param array $optional_params Array of optional parameter names with validation rules
	 * @return array|WP_Error Validated parameters or error
	 */
	public static function validate_get_params( $required_params = [], $optional_params = [] ) {
		return self::validate_params( $_GET, $required_params, $optional_params );
	}

	/**
	 * Validate a single parameter based on its type
	 *
	 * @since 2.29.0
	 * @param string $param_name
	 * @param mixed  $value
	 * @param string $validation_rule
	 * @return mixed|WP_Error
	 */
	private static function validate_single_param( $param_name, $value, $validation_rule = null ) {
		// Auto-detect validation rule based on parameter name if not provided
		if ( ! $validation_rule ) {
			if ( strpos( $param_name, 'affiliate_id' ) !== false ) {
				$validation_rule = 'affiliate_id';
			} elseif ( strpos( $param_name, 'referral_id' ) !== false ) {
				$validation_rule = 'referral_id';
			} elseif ( strpos( $param_name, 'amount' ) !== false ) {
				$validation_rule = 'amount';
			} elseif ( strpos( $param_name, 'email' ) !== false ) {
				$validation_rule = 'email';
			} elseif ( strpos( $param_name, 'url' ) !== false ) {
				$validation_rule = 'url';
			} elseif ( strpos( $param_name, 'error' ) !== false && $param_name !== 'payout_error' ) {
				$validation_rule = 'stripe_error';
			} elseif ( strpos( $param_name, 'code' ) !== false ) {
				$validation_rule = 'oauth_code';
			} else {
				$validation_rule = 'text';
			}
		}

		switch ( $validation_rule ) {
			case 'affiliate_id':
				return affwp_stripe_payouts_validate_affiliate_id( $value );

			case 'referral_id':
				return affwp_stripe_payouts_validate_referral_id( $value );

			case 'amount':
				return affwp_stripe_payouts_validate_amount( $value );

			case 'email':
				return affwp_stripe_payouts_validate_email( $value );

			case 'url':
				return affwp_stripe_payouts_validate_url( $value );

			case 'stripe_error':
				return affwp_stripe_payouts_validate_stripe_error( $value );

			case 'oauth_code':
				return affwp_stripe_payouts_validate_oauth_code( $value );

			case 'text':
			default:
				return affwp_stripe_payouts_validate_text_field( $value );
		}
	}

	/**
	 * Validate form data for batch operations
	 *
	 * @since 2.29.0
	 * @param array $form_data
	 * @return array|WP_Error
	 */
	public static function validate_batch_form_data( $form_data ) {
		$validated = [];

		// Validate date range
		if ( isset( $form_data['from'] ) ) {
			$from_date = sanitize_text_field( $form_data['from'] );
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_date ) ) {
				return new WP_Error( 'invalid_date', __( 'Invalid start date format.', 'affiliate-wp' ) );
			}
			$validated['from'] = $from_date;
		}

		if ( isset( $form_data['to'] ) ) {
			$to_date = sanitize_text_field( $form_data['to'] );
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to_date ) ) {
				return new WP_Error( 'invalid_date', __( 'Invalid end date format.', 'affiliate-wp' ) );
			}
			$validated['to'] = $to_date;
		}

		// Validate affiliate ID if provided
		if ( isset( $form_data['affiliate_id'] ) && ! empty( $form_data['affiliate_id'] ) ) {
			$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $form_data['affiliate_id'] );
			if ( is_wp_error( $affiliate_id ) ) {
				return $affiliate_id;
			}
			$validated['affiliate_id'] = $affiliate_id;
		}

		return $validated;
	}

	/**
	 * Validate settings form data
	 *
	 * @since 2.29.0
	 * @param array $settings_data
	 * @return array|WP_Error
	 */
	public static function validate_settings_data( $settings_data ) {
		$validated = [];
		$errors    = [];

		// Validate secret keys
		$secret_key_fields = [ 'stripe_test_secret_key', 'stripe_live_secret_key' ];
		foreach ( $secret_key_fields as $field ) {
			if ( isset( $settings_data[ $field ] ) && ! empty( $settings_data[ $field ] ) ) {
				$key         = sanitize_text_field( $settings_data[ $field ] );
				$mode_prefix = strpos( $field, 'test' ) !== false ? 'sk_test_' : 'sk_live_';

				if ( strpos( $key, $mode_prefix ) !== 0 ) {
					$errors[] = sprintf( __( '%1$s must start with "%2$s".', 'affiliate-wp' ), $field, $mode_prefix );
				} elseif ( strlen( $key ) < 20 ) {
					$errors[] = sprintf( __( '%s appears to be too short.', 'affiliate-wp' ), $field );
				} else {
					$validated[ $field ] = $key;
				}
			}
		}

		// Validate webhook secret
		if ( isset( $settings_data['stripe_webhook_secret'] ) && ! empty( $settings_data['stripe_webhook_secret'] ) ) {
			$webhook_secret = sanitize_text_field( $settings_data['stripe_webhook_secret'] );
			if ( strpos( $webhook_secret, 'whsec_' ) !== 0 ) {
				$errors[] = __( 'Webhook secret must start with "whsec_".', 'affiliate-wp' );
			} else {
				$validated['stripe_webhook_secret'] = $webhook_secret;
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', implode( ' ', $errors ) );
		}

		return $validated;
	}
}
