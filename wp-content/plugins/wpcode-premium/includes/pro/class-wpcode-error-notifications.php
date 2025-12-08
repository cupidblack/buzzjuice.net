<?php
/**
 * This class handles notifications for errors, sending emails to users if they enabled this option.
 *
 * @package wpcode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Error_Notifications.
 */
class WPCode_Error_Notifications {

	/**
	 * WPCode_Error_Notifications constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wpcode_snippet_error_tracked', array( $this, 'maybe_send_error_notification' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'preview' ) );
	}

	/**
	 * Check if notifications for errors are enabled and send the email.
	 *
	 * @param array          $error The error details.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function maybe_send_error_notification( $error, $snippet ) {

		if ( ! empty( $error['doing_activation'] ) ) {
			// Do not send a notification if we encounter an error during a snippet activation as it will be displayed on the screen.
			return;
		}

		if ( 'deactivated' === $error['wpc_type'] ) {
			$this->maybe_send_deactivation_notification( $error, $snippet );

			return;
		}

		// Check if notifications are enabled.
		if ( ! wpcode()->settings->get_option( 'emails_errors' ) ) {
			return;
		}

		$send_to = wpcode()->settings->get_option( 'emails_errors_addresses', get_option( 'admin_email' ) );
		if ( empty( $send_to ) ) {
			return;
		}

		$site_domain = wp_parse_url( get_site_url(), PHP_URL_HOST );
		$subject     = sprintf(
		// Translators: %1$s - site domain, %2$s - snippet id.
			esc_html__( '[%1$s] Error detected in snippet #%2$d', 'wpcode-premium' ),
			$site_domain,
			$snippet->get_id()
		);

		$error_message = wpcode()->error->get_error_message( $error );
		$heading       = wpcode()->error->get_error_message_short( $error_message );

		$this->send_email( $error, $snippet, $subject, $send_to, $heading );
	}

	/**
	 * Send the email.
	 *
	 * @param array          $error The error details.
	 * @param WPCode_Snippet $snippet The snippet object.
	 * @param string         $subject The email subject.
	 * @param string         $send_to The email address to send the email to.
	 * @param string         $heading The email heading.
	 *
	 * @return void
	 */
	public function send_email( $error, $snippet, $subject, $send_to, $heading = '' ) {
		$email_body = $this->build_email_body( $error, $snippet, $heading );

		$email = new WPCode_Emails();

		// Let's explode $send_to to send emails to all the addresses.

		$send_to = explode( ',', $send_to );
		foreach ( $send_to as $email_address ) {
			$email->send( $email_address, $subject, $email_body );
		}
	}

	/**
	 * Build the email body.
	 *
	 * @param array          $error The error details.
	 * @param WPCode_Snippet $snippet The snippet object.
	 * @param string         $heading The email heading.
	 *
	 * @return string
	 */
	public function build_email_body( $error, $snippet, $heading ) {
		$error_message = wpcode()->error->get_error_message( $error );

		if ( ! empty( $heading ) ) {
			$email_body = '<h2 style="font-weight:700;font-size:22px;margin:0 0 4px">' . $heading . '</h2>';
		}

		$email_body .= '<p>';
		$email_body .= sprintf(
		// Translators: %s - snippet title.
			esc_html__( 'An error has been detected in your snippet "%s".', 'wpcode-premium' ),
			'<b><a href="' . esc_url( $snippet->get_edit_url() ) . '">' . $snippet->get_title() . '</a></b>'
		);
		$email_body .= '</p>';

		$email_body .= $this->get_snippet_buttons( $snippet );

		if ( ! empty( $error['url'] ) ) {
			$email_body .= '<p>' . esc_html__( 'The error occurred at the following URL:', 'wpcode-premium' ) . '</p>';

			$email_body .= '<div style="background:#f7f7f7;border:1px solid #ddd;border-radius:3px;padding:10px;margin:0 0 20px;">' . esc_url( $error['url'] ) . '</div>';
		}

		$email_body .= '<p>' . esc_html__( 'Full Error message:', 'wpcode-premium' ) . '</p>';

		$email_body .= '<pre style="background:#f7f7f7;border:1px solid #ddd;border-radius:3px;padding:10px;margin:0 0 20px;word-wrap:break-word;white-space:pre-wrap">' . esc_html( $error_message ) . '</pre>';

		$email_body .= $this->get_snippet_buttons( $snippet );

		return $email_body;
	}

	/**
	 * Get the buttons for the email.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return string
	 */
	public function get_snippet_buttons( $snippet ) {
		$message = '<p style="margin:0 0 20px"><a href="' . esc_url( $snippet->get_edit_url() ) . '" style="background:#3568B7;color:#fff;display:inline-block;font-size:14px;font-weight:700;line-height:1.3;padding:10px 20px;text-align:center;text-decoration:none;vertical-align:middle;white-space:nowrap;border-radius:3px" target="_blank">' . esc_html__( 'Edit snippet', 'wpcode-premium' ) . '</a> ';

		$message .= '<a href="' . esc_url( admin_url( 'admin.php?page=wpcode-tools&view=logs' ) ) . '" style="background:#3568B7;color:#fff;display:inline-block;font-size:14px;font-weight:700;line-height:1.3;padding:10px 20px;text-align:center;text-decoration:none;vertical-align:middle;white-space:nowrap;border-radius:3px" target="_blank">' . esc_html__( 'View error logs', 'wpcode-premium' ) . '</a></p>';

		return $message;
	}

	/**
	 * When a snippet is deactivated, check if notifications for errors are enabled and send the email.
	 *
	 * @param array          $error The error details.
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return void
	 */
	public function maybe_send_deactivation_notification( $error, $snippet ) {

		// Check if notifications are enabled.
		if ( ! wpcode()->settings->get_option( 'emails_deactivated' ) ) {
			return;
		}

		$send_to = wpcode()->settings->get_option( 'emails_deactivated_addresses', get_option( 'admin_email' ) );
		if ( empty( $send_to ) ) {
			return;
		}

		$site_domain = wp_parse_url( get_site_url(), PHP_URL_HOST );

		$subject = sprintf(
		// Translators: %1$s - site domain, %2$s - snippet id.
			esc_html__( '[%1$s] Snippet #%2$s was deactivated', 'wpcode-premium' ),
			$site_domain,
			$snippet->get_id()
		);

		$heading = $this->get_deactivated_email_heading();

		$this->send_email( $error, $snippet, $subject, $send_to, $heading );
	}

	/**
	 * Get the deactivated email heading in one place.
	 *
	 * @return string
	 */
	public function get_deactivated_email_heading() {
		return esc_html__( 'Your snippet has been automatically deactivated', 'wpcode-premium' );
	}

	/**
	 * Preview the email.
	 *
	 * @return void
	 */
	public function preview() {
		if ( ! is_user_logged_in() || ! current_user_can( 'wpcode_manage_settings' ) || ! current_user_can( 'wpcode_edit_snippets' ) ) {
			return;
		}

		if ( ! isset( $_GET['wpcode_email_preview'], $_GET['wpcode_email_template'] ) ) { // phpcs:ignore
			return;
		}

		$template  = sanitize_text_field( wp_unslash( $_GET['wpcode_email_template'] ) ); // phpcs:ignore
		$templates = array(
			'error',
			'deactivated',
		);

		if ( ! in_array( $template, $templates, true ) ) {
			return;
		}

		$error = array(
			'wpc_type' => $template,
			'url'      => 'https://example.com/checkout',
			'code'     => 'error_code',
			'message'  => 'Uncaught error: Call to undefined function',
		);
		// Let's see if we have any snippets and use the first one or default to a generated snippet.
		$snippets = get_posts(
			array(
				'post_type'      => 'wpcode',
				'posts_per_page' => 1,
				'post_status'    => 'any',
			)
		);

		if ( ! empty( $snippets ) ) {
			$snippet_data = $snippets[0];
		} else {
			$snippet_data = array(
				'id'    => 1,
				'title' => 'Update heading tags',
			);
		}

		$snippet = new WPCode_Snippet( $snippet_data );
		if ( 'error' === $template ) {
			$heading = wpcode()->error->get_error_message_short( $error['message'] );
		} elseif ( 'deactivated' === $template ) {
			$heading = $this->get_deactivated_email_heading();
		}

		$email = new WPCode_Emails();

		echo $email->build_email( $this->build_email_body( $error, $snippet, $heading ) ); // phpcs:ignore

		exit;
	}
}


new WPCode_Error_Notifications();
