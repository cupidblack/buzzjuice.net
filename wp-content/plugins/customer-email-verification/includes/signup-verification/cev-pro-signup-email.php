<?php
/**
 * Email Sending Class for Signup Verification
 *
 * Handles sending verification emails during the signup process,
 * including PIN generation, database updates, and email template rendering.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Signup_Email Class
 *
 * Manages email sending functionality for signup verification.
 */
class CEV_Signup_Email {

	/**
	 * Send signup verification email to recipient.
	 *
	 * Generates a verification PIN, stores it in the database, and sends
	 * the verification email using WooCommerce email templates.
	 *
	 * @since 1.0.0
	 * @param string $recipient Email address to send verification to.
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	public function send_verification_email( $recipient ) {
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return false;
		}

		// Generate verification PIN and secret code.
		$verification_pin = cev_pro()->function->generate_verification_pin();
		$secret_code      = md5( $recipient . time() );

		// Store or update verification data in database.
		$this->save_verification_data( $recipient, $verification_pin, $secret_code );

		// Set registered user email for merge tags.
		cev_pro()->function->registered_user_email = $recipient;

		// Prepare email content.
		$email_content = $this->prepare_email_content();

		// Send the email.
		return $this->send_email( $recipient, $email_content );
	}

	/**
	 * Save or update verification data in the database.
	 *
	 * @since 1.0.0
	 * @param string $recipient      Email address.
	 * @param string $verification_pin Generated PIN.
	 * @param string $secret_code    Secret code for verification.
	 * @return void
	 */
	private function save_verification_data( $recipient, $verification_pin, $secret_code ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name cannot be a placeholder.
		$email_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}cev_user_log WHERE email = %s", $recipient ) );
		$current_time = current_time( 'mysql' );

		if ( $email_exists ) {
			// Update existing record.
			$wpdb->update(
				$wpdb->prefix . 'cev_user_log',
				array(
					'pin'          => $verification_pin,
					'verified'     => 'false',
					'secret_code'  => $secret_code,
					'last_updated' => $current_time,
				),
				array( 'email' => $recipient ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
		} else {
			// Insert new record.
			$wpdb->insert(
				$wpdb->prefix . 'cev_user_log',
				array(
					'email'        => $recipient,
					'pin'          => $verification_pin,
					'verified'     => 'false',
					'secret_code'  => $secret_code,
					'last_updated' => $current_time,
				),
				array( '%s', '%s', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Prepare email content using WooCommerce templates.
	 *
	 * @since 1.0.0
	 * @return array Email content array with subject, heading, and content.
	 */
	private function prepare_email_content() {
		$CEV_Customizer_Options = new CEV_Customizer_Options();

		// Get email subject and heading.
		$email_subject = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_subject', $CEV_Customizer_Options->defaults['cev_verification_email_subject'] );
		$email_subject = cev_pro()->function->maybe_parse_merge_tags( $email_subject, cev_pro()->function );
		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_heading', $CEV_Customizer_Options->defaults['cev_verification_email_heading'] );

		// Get email body content.
		$content        = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_body', $CEV_Customizer_Options->defaults['cev_verification_email_body'] );
		$content        = cev_pro()->function->maybe_parse_merge_tags( $content, cev_pro()->function );
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_new_verification_Footer_content', $CEV_Customizer_Options->defaults['cev_new_verification_Footer_content'] );

		// Load email template.
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-email-verification.php';
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			$email_content = wc_get_template_html(
				'emails/cev-email-verification.php',
				array(
					'email_heading' => $email_heading,
					'content'       => $content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				get_stylesheet_directory() . '/woocommerce/'
			);
		} else {
			$email_content = wc_get_template_html(
				'emails/cev-email-verification.php',
				array(
					'email_heading' => $email_heading,
					'content'       => $content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				cev_pro()->get_plugin_path() . '/templates/'
			);
		}

		return array(
			'subject' => $email_subject,
			'heading' => $email_heading,
			'content' => $email_content,
		);
	}

	/**
	 * Send the verification email.
	 *
	 * @since 1.0.0
	 * @param string $recipient     Email address to send to.
	 * @param array  $email_content Email content array with subject, heading, and content.
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	private function send_email( $recipient, $email_content ) {
		$mailer = WC()->mailer();

		// Create email object and style the content.
		$email      = new WC_Email();
		$email_body = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_content['heading'], $email_content['content'] ) ) );
		$email_body = apply_filters( 'wc_cev_decode_html_content', $email_body );

		// Add email filters for from address and name.
		add_filter( 'wp_mail_from', array( cev_pro()->function, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( cev_pro()->function, 'get_from_name' ) );

		// Send the email.
		$result = wp_mail( $recipient, $email_content['subject'], $email_body, $email->get_headers() );

		// Remove filters after sending.
		remove_filter( 'wp_mail_from', array( cev_pro()->function, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( cev_pro()->function, 'get_from_name' ) );

		return $result;
	}

}

