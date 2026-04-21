<?php
/**
 * Checkout Email Handler for Customer Email Verification
 *
 * Handles sending verification emails to guest users during the checkout process,
 * including generating PINs, preparing email content, and managing email templates.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Checkout_Email Class
 *
 * Manages email-related functionalities for checkout verification.
 */
class CEV_Checkout_Email {

	/**
	 * Email type context (e.g. 'checkout').
	 *
	 * @since 2.8.13
	 * @var string
	 */
	public $email_type = 'checkout';

	/**
	 * Browser name for login/checkout context.
	 *
	 * @since 2.8.13
	 * @var string
	 */
	public $login_browser = '';

	/**
	 * Device type for login/checkout context.
	 *
	 * @since 2.8.13
	 * @var string
	 */
	public $login_device = '';

	/**
	 * IP address for login/checkout context.
	 *
	 * @since 2.8.13
	 * @var string
	 */
	public $login_ip = '';

	/**
	 * Login time for login/checkout context.
	 *
	 * @since 2.8.13
	 * @var string
	 */
	public $login_time = '';

	/**
	 * User ID for verification context.
	 *
	 * @since 2.8.13
	 * @var int
	 */
	public $wuev_user_id = 0;

	/**
	 * Checkout verification instance.
	 *
	 * @var CEV_Checkout_Verification
	 */
	private $checkout_verification;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param CEV_Checkout_Verification $checkout_verification Checkout verification instance.
	 */
	public function __construct( CEV_Checkout_Verification $checkout_verification ) {
		$this->checkout_verification = $checkout_verification;
	}

	/**
	 * Send verification email to guest user from Cart or Checkout page.
	 *
	 * Generates a verification PIN, stores it in session, and sends an email
	 * with the PIN and verification link to the guest user.
	 *
	 * @since 1.0.0
	 * @param string $recipient Email address to send verification to.
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	public function send_verification_email_to_guest_user( $recipient ) {
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return false;
		}

		// Check resend limit before sending.
		if ( ! $this->can_send_verification_email( $recipient ) ) {
			return false;
		}

		// Generate verification data.
		$verification_data = $this->generate_verification_data( $recipient );

		// Store verification data in session.
		WC()->session->set( 'cev_user_verification_data', wp_json_encode( $verification_data ) );

		// Prepare and send email.
		return $this->send_verification_email( $recipient, $verification_data );
	}

	/**
	 * Check if verification email can be sent (resend limit check).
	 *
	 * @since 1.0.0
	 * @param string $recipient Email address.
	 * @return bool True if email can be sent, false if limit reached.
	 */
	private function can_send_verification_email( $recipient ) {
		$cev_redirect_limit_resend = cev_pro()->function->cev_pro_admin_settings( 'cev_redirect_limit_resend', 1 );
		$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
		$cev_user_verification_data     = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;

		$cev_guest_user_resend_times = isset( $cev_user_verification_data->cev_guest_user_resend_times ) ? $cev_user_verification_data->cev_guest_user_resend_times : 0;

		// If same email and limit reached, cannot send.
		if ( isset( $cev_user_verification_data->email ) && $cev_user_verification_data->email === $recipient ) {
			if ( $cev_guest_user_resend_times >= $cev_redirect_limit_resend + 1 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Generate verification data for guest user.
	 *
	 * @since 1.0.0
	 * @param string $recipient Email address.
	 * @return array Verification data array.
	 */
	private function generate_verification_data( $recipient ) {
		$CEV_Customizer_Options = new CEV_Customizer_Options();
		$verification_pin       = cev_pro()->function->generate_verification_pin();
		$expire_time            = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' );

		if ( empty( $expire_time ) ) {
			$expire_time = 'never';
		}

		// Get current resend count.
		$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
		$cev_user_verification_data     = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;

		$cev_guest_user_resend_times = 0;
		if ( isset( $cev_user_verification_data->email ) && $cev_user_verification_data->email === $recipient ) {
			$cev_guest_user_resend_times = isset( $cev_user_verification_data->cev_guest_user_resend_times ) ? $cev_user_verification_data->cev_guest_user_resend_times : 0;
			$cev_guest_user_resend_times++;
		} else {
			$cev_guest_user_resend_times = 1;
		}

		return array(
			'email'                    => $recipient,
			'pin'                      => base64_encode( $verification_pin ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'startdate'                => time(),
			'enddate'                  => time() + (int) $expire_time,
			'cev_guest_user_resend_times' => $cev_guest_user_resend_times,
		);
	}

	/**
	 * Send verification email to recipient.
	 *
	 * @since 1.0.0
	 * @param string $recipient          Email address.
	 * @param array  $verification_data  Verification data.
	 * @return bool True if email was sent, false otherwise.
	 */
	private function send_verification_email( $recipient, $verification_data ) {
		$CEV_Customizer_Options = new CEV_Customizer_Options();

		// Set up context properties for merge tag parsing (browser, device, IP, time, etc.).
		cev_pro()->function->set_checkout_email_context_properties( $this );

		// Get email content.
		$email_subject = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_subject_che', $CEV_Customizer_Options->defaults['cev_verification_email_subject_che'] );
		$email_subject = cev_pro()->function->maybe_parse_merge_tags( $email_subject, $this );

		$email_heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_heading_che', $CEV_Customizer_Options->defaults['cev_verification_email_heading_che'] );
		$content       = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_email_body_che', $CEV_Customizer_Options->defaults['cev_verification_email_body_che'] );
		$content       = cev_pro()->function->maybe_parse_merge_tags( $content, $this );
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_new_verification_Footer_content_che', $CEV_Customizer_Options->defaults['cev_new_verification_Footer_content_che'] );
		$footer_content = cev_pro()->function->maybe_parse_merge_tags( $footer_content, $this );

		// Get email template.
		$email_content = $this->get_email_template_content( $email_heading, $content, $footer_content );

		// Send email.
		$mailer = WC()->mailer();
		$email  = new WC_Email();
		$email->id = 'CEV_Guest_User_Verification';

		$email_body = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $email_content ) ) );
		$email_body = apply_filters( 'wc_cev_decode_html_content', $email_body );

		$result = wp_mail( $recipient, $email_subject, $email_body, $email->get_headers() );

		return $result;
	}

	/**
	 * Get email template content.
	 *
	 * @since 1.0.0
	 * @param string $email_heading  Email heading.
	 * @param string $content        Email content.
	 * @param string $footer_content Footer content.
	 * @return string Email template HTML.
	 */
	private function get_email_template_content( $email_heading, $content, $footer_content ) {
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-email-verification.php';

		if ( file_exists( $local_template ) && is_readable( $local_template ) ) {
			return wc_get_template_html(
				'emails/cev-email-verification.php',
				array(
					'email_heading'  => $email_heading,
					'content'        => $content,
					'footer_content' => $footer_content,
				),
				'customer-email-verification/',
				get_stylesheet_directory() . '/woocommerce/'
			);
		}

		return wc_get_template_html(
			'emails/cev-email-verification.php',
			array(
				'email_heading'  => $email_heading,
				'content'        => $content,
				'footer_content' => $footer_content,
			),
			'customer-email-verification/',
			cev_pro()->get_plugin_path() . '/templates/'
		);
	}

	/**
	 * Alias method for backward compatibility with merge tag {cev_user_verification_pin}.
	 * 
	 * This method now delegates to the consolidated routing method in cev-pro-functions.php.
	 * Passes $this as context object to ensure proper routing (guest vs registered user).
	 * 
	 * @return string Verification pin HTML.
	 */
	public function cev_user_verification_pin() {
		return cev_pro()->function->cev_user_verification_pin( $this );
	}

	/**
	 * Alias method for backward compatibility.
	 * 
	 * This method now delegates to the consolidated method in cev-pro-functions.php.
	 * 
	 * @return string Verification pin HTML.
	 */
	public function cev_guest_user_verification_pin() {
		return cev_pro()->function->cev_guest_user_verification_pin();
	}

}
