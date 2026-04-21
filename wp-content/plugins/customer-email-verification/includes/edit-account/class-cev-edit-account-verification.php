<?php
/**
 * Edit Account Email Verification Handler
 *
 * Handles email verification when users change their email address in the edit account page.
 * Manages verification PIN generation, email sending, and verification flow.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CEV_Edit_Account_Verification Class
 *
 * Manages email verification for edit account functionality.
 */
class CEV_Edit_Account_Verification {

	/**
	 * Instance of this class.
	 *
	 * @var CEV_Edit_Account_Verification
	 */
	private static $instance;

	/**
	 * AJAX handler instance.
	 *
	 * @var CEV_Edit_Account_Ajax
	 */
	private $ajax_handler;

	/**
	 * Get the class instance.
	 *
	 * @since 1.0.0
	 * @return CEV_Edit_Account_Verification
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the main plugin function.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( did_action( 'woocommerce_init' ) ) {
			$this->init();
		} else {
			add_action( 'woocommerce_init', array( $this, 'init' ) );
		}
	}

	/**
	 * Initialize hooks and actions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Edit account email verification hooks.
		add_action( 'woocommerce_save_account_details_errors', array( $this, 'validate_email_on_change_from_edit_account' ), 10, 2 );
		add_action( 'woocommerce_edit_account_form_start', array( $this, 'add_email_verification_field_edit_account' ) );
		add_action( 'wp_footer', array( $this, 'set_cev_temp_email_in_my_account' ) );

		// Initialize AJAX handler.
		require_once cev_pro()->get_plugin_path() . '/includes/edit-account/cev-pro-edit-account-ajax.php';
		$this->ajax_handler = new CEV_Edit_Account_Ajax( $this );
	}

	/**
	 * Validate email change on edit account form submission.
	 *
	 * Checks if user changed email, sends verification email to new address,
	 * and adds validation error to prevent immediate email change.
	 *
	 * @since 1.0.0
	 * @param WP_Error $errors Validation errors object.
	 * @param WP_User  $user   User object.
	 * @return void
	 */
	public function validate_email_on_change_from_edit_account( $errors, $user ) {
		$current_user = wp_get_current_user();
		if ( ! $current_user || ! $current_user->exists() ) {
			return;
		}

		$old_email = $current_user->user_email;
		$new_email = $user->user_email;

		// Check if email has changed.
		if ( $old_email !== $new_email ) {
			// Check if the new email is already registered to another user.
			$existing_user_id = email_exists( $new_email );
			if ( $existing_user_id && (int) get_current_user_id() !== (int) $existing_user_id ) {
				$errors->add(
					'email_exists',
					__( 'An account is already registered with your email address.', 'customer-email-verification' )
				);
				return;
			}

			// Store temporary email for verification.
			update_user_meta( get_current_user_id(), 'cev_temp_email', sanitize_email( $new_email ) );

			// Set user ID for email common class.
			cev_pro()->function->wuev_user_id = get_current_user_id();

			// Send verification email to new address.
			$this->send_verification_email( $new_email );

			// Add validation error to prevent email change until verified.
			$errors->add(
				'validation',
				__( 'We have sent a verification email, please verify your email address.', 'customer-email-verification' )
			);
		}
	}

	/**
	 * Add email verification field to edit account form.
	 *
	 * Displays verification PIN input field and buttons when user has pending email change.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_email_verification_field_edit_account() {
		$user_id      = get_current_user_id();
		$temp_email   = get_user_meta( $user_id, 'cev_temp_email', true );

		if ( empty( $temp_email ) ) {
			return;
		}

		// Display verification PIN input field.
		woocommerce_form_field(
			'my_account_email_verification',
			array(
				'type'        => 'text',
				'placeholder' => __( '4-digit code', 'customer-email-verification' ),
				'required'    => true,
				'label'       => __( 'Verify Your Email Address', 'customer-email-verification' ),
			)
		);

		// Check resend limit.
		$resend_limit_reached = cev_pro()->function->is_resend_limit_reached( $user_id );
		$resend_limit_class   = $resend_limit_reached ? 'cev_disibale_values' : '';

		?>
		<span class="cev-pin-verification__failure_code" style="display:none;">
			<?php esc_html_e( 'Invalid PIN Code', 'customer-email-verification' ); ?>
		</span>
		<p>
			<button 
				type="button" 
				wp_nonce="<?php echo esc_attr( wp_create_nonce( 'wc_cev_email_edit_user_verify' ) ); ?>" 
				class="woocommerce-Button button verify_account_email_my_account" 
				name="verify_account_email" 
				value="<?php esc_attr_e( 'Verify', 'customer-email-verification' ); ?>"
			>
				<?php esc_html_e( 'Verify', 'customer-email-verification' ); ?>
			</button>
			<a 
				href="javascript:void(0);" 
				class="a_cev_resend resend_verification_code_my_account limit_reched <?php echo esc_attr( $resend_limit_class ); ?> cev_resend_link_front" 
				name="resend_verification_code" 
				value="<?php esc_attr_e( 'Resend verification code', 'customer-email-verification' ); ?>"
			>
				<?php esc_html_e( 'Resend verification code', 'customer-email-verification' ); ?>
				<span class="dashicons dashicons-yes show_cev_resend_yes" style="display:none;"></span>
			</a>
			<span class="cev_resend_timer" style="display:none; padding-left:0.5rem; font-size:0.75rem; color:#6b7280;"></span>
		</p>
		<?php
	}

	/**
	 * Set temporary email value in edit account form.
	 *
	 * Outputs JavaScript to populate email field with temporary email when verification is pending.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_cev_temp_email_in_my_account() {
		global $wp;

		// Check if we're on the edit-account page.
		$request = explode( '/', $wp->request );
		if ( 'edit-account' !== end( $request ) || ! is_account_page() ) {
			return;
		}

		$user_id    = get_current_user_id();
		$temp_email = get_user_meta( $user_id, 'cev_temp_email', true );

		if ( empty( $temp_email ) ) {
			return;
		}

		?>
		<style>
		a.a_cev_resend {
			vertical-align: super;
		}
		span.dashicons.dashicons-yes.show_cev_resend_yes {
			vertical-align: middle;
		}
		.cev_disibale_values {
			pointer-events: none;
			cursor: not-allowed;
			opacity: 0.5;
		}
		</style>
		<script>
		jQuery( document ).ready(function() {
			jQuery('#account_email').val('<?php echo esc_js( $temp_email ); ?>');
		});
		</script>
		<?php
	}

	/**
	 * Send verification email to recipient.
	 *
	 * Generates verification PIN, stores verification data, and sends email.
	 *
	 * @since 1.0.0
	 * @param string $recipient Recipient email address.
	 * @return bool True if email sent successfully, false otherwise.
	 */
	public function send_verification_email( $recipient ) {
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$customizer_options = new CEV_Customizer_Options();

		// Generate verification PIN.
		$verification_pin = cev_pro()->function->generate_verification_pin();

		// Calculate expiration time.
		$expiration_option = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' );
		$expiration_time   = $this->calculate_expiration_time( $expiration_option );

		// Store verification data.
		$verification_data = array(
			'pin'       => $verification_pin,
			'startdate' => time(),
			'enddate'   => $expiration_time,
		);

		update_user_meta( $user_id, 'cev_email_verification_pin', $verification_data );

		// Set user ID and email for merge tag parsing.
		cev_pro()->function->wuev_user_id = $user_id;
		cev_pro()->function->registered_user_email = $recipient;

		// Get email content.
		$email_subject = $this->get_email_subject( $customizer_options );
		$email_heading = $this->get_email_heading( $customizer_options );
		$email_content = $this->get_email_content( $recipient, $verification_pin );
		$footer_content = cev_pro()->function->cev_pro_customizer_settings( 'cev_new_verification_Footer_content', '' );

		// Get email template.
		$email_body = $this->get_email_template_content( $email_heading, $email_content, $footer_content );

		// Send email.
		return $this->send_email( $recipient, $email_subject, $email_body );
	}

	/**
	 * Calculate expiration time based on option.
	 *
	 * @since 1.0.0
	 * @param string $expiration_option Expiration option value.
	 * @return int Expiration timestamp.
	 */
	private function calculate_expiration_time( $expiration_option ) {
		if ( empty( $expiration_option ) || 'never' === $expiration_option ) {
			// Return far future timestamp for 'never' expiration.
			return time() + ( 365 * 24 * 60 * 60 ); // 1 year from now.
		}

		return time() + (int) $expiration_option;
	}


	/**
	 * Get email subject.
	 *
	 * @since 1.0.0
	 * @param CEV_Customizer_Options $customizer_options Customizer options instance.
	 * @return string Email subject.
	 */
	private function get_email_subject( $customizer_options ) {
		$email_subject = cev_pro()->function->cev_pro_customizer_settings(
			'cev_verification_email_subject_ed',
			$customizer_options->defaults['cev_verification_email_subject_ed']
		);
		$email_subject = apply_filters( 'cev_edit_email_subject_content', $email_subject );
		$email_subject = cev_pro()->function->maybe_parse_merge_tags( $email_subject, cev_pro()->function );

		return $email_subject;
	}

	/**
	 * Get email heading.
	 *
	 * @since 1.0.0
	 * @param CEV_Customizer_Options $customizer_options Customizer options instance.
	 * @return string Email heading.
	 */
	private function get_email_heading( $customizer_options ) {
		$email_heading = cev_pro()->function->cev_pro_customizer_settings(
			'cev_verification_email_heading_ed',
			$customizer_options->defaults['cev_verification_email_heading_ed']
		);
		$email_heading = apply_filters( 'cev_edit_email_heading_content', $email_heading );
		$email_heading = cev_pro()->function->maybe_parse_merge_tags( $email_heading, cev_pro()->function );

		return $email_heading;
	}

	/**
	 * Get email content body.
	 *
	 * @since 1.0.0
	 * @param string $recipient         Recipient email address.
	 * @param string $verification_pin  Verification PIN code.
	 * @return string Email content.
	 */
	private function get_email_content( $recipient, $verification_pin ) {
		$default_content = sprintf(
			/* translators: %1$s: Recipient email address, %2$s: Verification PIN code */
			__( '<p> Please confirm <strong>%1$s</strong> as your new email address. The change will not take effect until you verify this email address.</p><p> Your verification code: <strong>%2$s</strong> </p>', 'customer-email-verification' ),
			$recipient,
			$verification_pin
		);
		$content = cev_pro()->function->cev_pro_customizer_settings(
			'cev_verification_email_body_ed',
			$default_content
		);

		$content = cev_pro()->function->maybe_parse_merge_tags( $content, cev_pro()->function );
		$content = apply_filters( 'cev_edit_email_content', $content, $recipient, $verification_pin );

		return $content;
	}

	/**
	 * Get email template content.
	 *
	 * @since 1.0.0
	 * @param string $email_heading  Email heading.
	 * @param string $email_content   Email content body.
	 * @param string $footer_content  Footer content.
	 * @return string Email template HTML.
	 */
	private function get_email_template_content( $email_heading, $email_content, $footer_content ) {
		$local_template = get_stylesheet_directory() . '/woocommerce/emails/cev-email-verification.php';
		$template_path  = ( file_exists( $local_template ) && is_writable( $local_template ) )
			? get_stylesheet_directory() . '/woocommerce/'
			: cev_pro()->get_plugin_path() . '/templates/';

		$email_content_html = wc_get_template_html(
			'emails/cev-email-verification.php',
			array(
				'email_heading'  => $email_heading,
				'content'        => $email_content,
				'footer_content' => $footer_content,
			),
			'customer-email-verification/',
			$template_path
		);

		$mailer = WC()->mailer();
		$email  = new WC_Email();
		$email->id = 'CEV_Registration_Verification';

		$email_body = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $email_content_html ) ) );
		$email_body = apply_filters( 'wc_cev_decode_html_content', $email_body );

		return $email_body;
	}

	/**
	 * Send email using WordPress mail function.
	 *
	 * @since 1.0.0
	 * @param string $recipient     Recipient email address.
	 * @param string $email_subject Email subject.
	 * @param string $email_body    Email body HTML.
	 * @return bool True if email sent successfully.
	 */
	private function send_email( $recipient, $email_subject, $email_body ) {
		add_filter( 'wp_mail_from', array( cev_pro()->function, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( cev_pro()->function, 'get_from_name' ) );

		$email = new WC_Email();
		$result = wp_mail( $recipient, $email_subject, $email_body, $email->get_headers() );

		remove_filter( 'wp_mail_from', array( cev_pro()->function, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( cev_pro()->function, 'get_from_name' ) );

		return $result;
	}


	/**
	 * Verify PIN code for email verification.
	 *
	 * @since 1.0.0
	 * @param string $pin_code PIN code to verify.
	 * @return bool True if PIN is valid, false otherwise.
	 */
	public function verify_pin_code( $pin_code ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$verification_data = get_user_meta( $user_id, 'cev_email_verification_pin', true );

		if ( empty( $verification_data ) || ! is_array( $verification_data ) ) {
			return false;
		}

		// Check expiration if enabled.
		$expiration_option = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_expiration', 'never' );
		if ( 'never' !== $expiration_option ) {
			$current_time = time();
			$expire_time  = isset( $verification_data['enddate'] ) ? (int) $verification_data['enddate'] : 0;

			if ( $current_time > $expire_time ) {
				return false;
			}
		}

		// Verify PIN match.
		$stored_pin = isset( $verification_data['pin'] ) ? $verification_data['pin'] : '';

		return $stored_pin === $pin_code;
	}

	/**
	 * Complete email verification process.
	 *
	 * Updates user email and cleans up verification data.
	 *
	 * @since 1.0.0
	 * @return bool True if verification completed successfully.
	 */
	public function complete_verification() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$temp_email = get_user_meta( $user_id, 'cev_temp_email', true );

		if ( empty( $temp_email ) ) {
			return false;
		}

		$args = array(
			'ID'         => $user_id,
			'user_email' => sanitize_email( $temp_email ),
		);

		$result = wp_update_user( $args );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		// Clean up verification data and reset resend counter.
		// Using delete_user_meta() avoids undefined index issues when reading this meta later.
		delete_user_meta( $user_id, 'cev_user_resend_times' );
		delete_user_meta( $user_id, 'cev_temp_email' );
		delete_user_meta( $user_id, 'cev_email_verification_pin' );
		delete_user_meta( $user_id, 'customer_email_verification_code' );

		// Add success notice.
		$success_message = get_option(
			'cev_verification_success_message',
			__( 'Your email is verified!', 'customer-email-verification' )
		);
		wc_add_notice( $success_message, 'notice' );

		return true;
	}

}

