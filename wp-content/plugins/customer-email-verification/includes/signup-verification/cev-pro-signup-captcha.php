<?php
/**
 * CAPTCHA Validation Class for Signup Verification
 *
 * Handles validation of reCaptcha for WooCommerce plugin only.
 * If the plugin is not active, CAPTCHA validation is skipped.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Signup_Captcha Class
 *
 * Manages CAPTCHA validation for signup verification process.
 * Only supports reCaptcha for WooCommerce plugin.
 */
class CEV_Signup_Captcha {

	/**
	 * Validate CAPTCHA if reCaptcha for WooCommerce plugin is active.
	 *
	 * If the plugin is not active, returns true immediately (no CAPTCHA required).
	 * If the plugin is active, validates the CAPTCHA response.
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error True if valid or plugin not active, WP_Error if invalid.
	 */
	public function validate() {
		// Verify nonce first.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'verify_otp_nonce' ) ) {
			wp_send_json_error(
				array(
					'verified' => false,
					'message'  => __( 'Nonce verification failed.', 'customer-email-verification' ),
				)
			);
		}

		// Check if reCaptcha for WooCommerce plugin is active.
		if ( ! $this->is_recaptcha_woocommerce_active() ) {
			// Plugin not active - skip CAPTCHA validation completely.
			return true;
		}

		// Plugin is active - validate CAPTCHA.
		// Sanitize CAPTCHA response from POST data.
		$sanitized_post = array(
			'g-recaptcha-response' => isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '',
		);

		// Get remote IP address for CAPTCHA verification.
		$remote_ip = $this->get_remote_ip();

		// Validate reCAPTCHA response.
		return $this->validate_recaptcha_woocommerce( $remote_ip, $sanitized_post['g-recaptcha-response'] );
	}

	/**
	 * Check if reCaptcha for WooCommerce plugin is active.
	 *
	 * @since 1.0.0
	 * @return bool True if plugin is active, false otherwise.
	 */
	private function is_recaptcha_woocommerce_active() {
		// Check if plugin main file exists (plugin is installed).
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check if plugin is active by checking the main file.
		return is_plugin_active( 'recaptcha-for-woocommerce/woo-recaptcha.php' );
	}

	/**
	 * Get the remote IP address from the request.
	 *
	 * @since 1.0.0
	 * @return string Validated IP address or empty string.
	 */
	private function get_remote_ip() {
		$remote_ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$validated_ip = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
			$remote_ip    = $validated_ip ? $validated_ip : '';
		}
		return $remote_ip;
	}

	/**
	 * Validate reCAPTCHA using reCaptcha for WooCommerce plugin.
	 *
	 * @since 1.0.0
	 * @param string $remote_ip The remote IP address.
	 * @param string $recaptcha_response The sanitized reCAPTCHA response token.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_recaptcha_woocommerce( $remote_ip, $recaptcha_response ) {
		// Check if CAPTCHA response is provided.
		if ( empty( $recaptcha_response ) ) {
			return new WP_Error( 'captcha_empty', __( 'Please complete the CAPTCHA.', 'customer-email-verification' ) );
		}

		// Get reCAPTCHA secret key from reCaptcha for WooCommerce plugin.
		$secret_key = $this->get_recaptcha_woocommerce_secret_key();

		if ( empty( $secret_key ) ) {
			// No secret key found - this should not happen if plugin is properly configured.
			// Return error to prompt user to configure the plugin.
			return new WP_Error( 'captcha_config', __( 'CAPTCHA is not properly configured. Please contact the site administrator.', 'customer-email-verification' ) );
		}

		// Verify with Google reCAPTCHA API.
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret'   => $secret_key,
					'response' => $recaptcha_response,
					'remoteip' => $remote_ip,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// If there's an error with the request, return error.
			return new WP_Error( 'captcha_error', __( 'CAPTCHA verification error. Please try again.', 'customer-email-verification' ) );
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $result['success'] ) && $result['success'] ) {
			return true;
		}

		// If verification fails, return an error.
		return new WP_Error( 'captcha_invalid', __( 'CAPTCHA verification failed. Please try again.', 'customer-email-verification' ) );
	}

	/**
	 * Get reCAPTCHA secret key from reCaptcha for WooCommerce plugin.
	 *
	 * @since 1.0.0
	 * @return string Secret key or empty string if not found.
	 */
	private function get_recaptcha_woocommerce_secret_key() {
		// Check common option names used by reCaptcha for WooCommerce plugin.
		// Try different possible option names.
		$possible_options = array(
			'woo_recaptcha_secret_key',
			'woo_recaptcha_settings',
			'woocommerce_recaptcha_secret',
			'woocommerce_recaptcha_settings',
			'recaptcha_for_woocommerce_secret',
			'recaptcha_for_woocommerce_settings',
		);

		foreach ( $possible_options as $option_name ) {
			$settings = get_option( $option_name );
			if ( is_array( $settings ) && isset( $settings['secret_key'] ) && ! empty( $settings['secret_key'] ) ) {
				return $settings['secret_key'];
			} elseif ( is_string( $settings ) && ! empty( $settings ) ) {
				// If the option is directly the secret key.
				return $settings;
			}
		}

		// Try to get from WooCommerce settings if plugin uses standard WooCommerce settings structure.
		$wc_recaptcha_settings = get_option( 'woocommerce_recaptcha_settings' );
		if ( is_array( $wc_recaptcha_settings ) && isset( $wc_recaptcha_settings['secret_key'] ) && ! empty( $wc_recaptcha_settings['secret_key'] ) ) {
			return $wc_recaptcha_settings['secret_key'];
		}

		return '';
	}
}

