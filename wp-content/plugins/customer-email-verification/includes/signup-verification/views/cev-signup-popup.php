<?php
/**
 * Signup Verification Popup Template
 *
 * Displays the OTP verification popup on the My Account login/registration page.
 * This template is included when users attempt to register a new account.
 * The popup allows users to enter the verification code sent to their email address.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Initialize customizer options for default values.
$cev_customizer_options = new CEV_Customizer_Options();
$defaults               = is_object( $cev_customizer_options ) && isset( $cev_customizer_options->defaults ) && is_array( $cev_customizer_options->defaults ) ? $cev_customizer_options->defaults : array();

// Get plugin settings for popup customization.
$widget_header_image_width     = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_header_image_width', '150' );
$button_text_header_font_size  = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_text_header_font_size', '22' );
$enable_email_verification     = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification' );
$password_setup_link_enabled   = get_option( 'woocommerce_registration_generate_password', 'no' );
$widget_content_width          = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_content_width', '460' );
$content_align                 = cev_pro()->function->cev_pro_customizer_settings( 'cev_content_align', 'center' );
$widget_content_padding        = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_content_padding', '40' );

// Get popup background color with fallback to default.
$popup_background_color = cev_pro()->function->cev_pro_customizer_settings(
	'cev_verification_popup_background_color',
	isset( $defaults['cev_verification_popup_background_color'] ) ? $defaults['cev_verification_popup_background_color'] : '#000000'
);

// Get verification image URL with fallback to default icon.
$verification_image = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_image', cev_pro()->plugin_dir_url() . 'assets/css/images/email-verification-icon.svg' );

// Get verification header text with fallback to default.
$verification_header = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_header', isset( $defaults['cev_verification_header'] ) ? $defaults['cev_verification_header'] : '' );
$heading_default     = __( 'Verify its you.', 'customer-email-verification' );
$heading_text        = ! empty( $verification_header ) ? $verification_header : $heading_default;

// Get verification message from customizer settings (with placeholder for email).
$verification_message = cev_pro()->function->cev_pro_customizer_settings(
	'cev_verification_message',
	__( 'We sent verification code to {customer_email}. To verify your email address, please check your inbox and enter the code below.', 'customer-email-verification' )
);
// Parse other merge tags (like {site_title}) but keep {customer_email} for JavaScript replacement.
$verification_message = cev_pro()->function->maybe_parse_merge_tags( $verification_message, cev_pro()->function );
// Note: {customer_email} will be replaced dynamically via JavaScript when popup is shown.

// Determine OTP input field count based on code length setting.
$code_length = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_length', 4 );
if ( '1' === $code_length ) {
	$otp_digits = 4;
} elseif ( '2' === $code_length ) {
	$otp_digits = 6;
} else {
	// Default to 4 digits if invalid setting is provided.
	$otp_digits = 4;
}

// Get footer message with merge tag support.
$footer_message = cev_pro()->function->cev_pro_customizer_settings(
	'cev_verification_widget_footer',
	isset( $defaults['cev_verification_widget_footer'] ) ? $defaults['cev_verification_widget_footer'] : ''
);

// Parse merge tags in footer message if method exists.
if ( ! empty( $footer_message ) ) {
	$footer_message = cev_pro()->function->maybe_parse_merge_tags( $footer_message, cev_pro()->function );
}

// Get overlay background color for popup backdrop from consolidated customizer settings.
$overlay_background_color = cev_pro()->function->cev_pro_customizer_settings(
	'cev_verification_popup_overlay_background_color',
	isset( $defaults['cev_verification_popup_overlay_background_color'] ) ? $defaults['cev_verification_popup_overlay_background_color'] : 'rgba(0, 0, 0, 0.7)'
);
?>

<!-- OTP Verification Popup Container -->
<div id="otp-popup" style="display: none;" class="cev-authorization-grid__visual" role="dialog" aria-labelledby="otp-popup-title" aria-modal="true">
	<div class="otp_popup_inn">
		<div class="otp_content">
			<div class="cev-authorization-grid__visual">
				<div class="cev-authorization-grid__holder">
					<div class="cev-authorization-grid__inner" style="max-width: <?php echo esc_attr( $widget_content_width ); ?>px;">
						<div class="cev-authorization" style="background: <?php echo esc_attr( $popup_background_color ); ?>;">
							<section class="cev-authorization__holder" style="text-align: <?php echo esc_attr( $content_align ); ?>; padding: <?php echo esc_attr( $widget_content_padding ); ?>px;">
								<!-- Back Button -->
								<div class="back_btn" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Close popup', 'customer-email-verification' ); ?>">
									<svg fill="#000000" version="1.1" viewBox="-6.07 -6.07 72.87 72.87" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
										<polygon points="0,30.365 29.737,60.105 29.737,42.733 60.731,42.729 60.731,18.001 29.737,17.999 29.737,0.625"></polygon>
									</svg>
								</div>

								<!-- Verification Image -->
								<?php if ( ! empty( $verification_image ) ) : ?>
									<div class="popup_image">
										<img src="<?php echo esc_url( $verification_image ); ?>" style="width:<?php echo esc_attr( $widget_header_image_width ); ?>px;" alt="<?php esc_attr_e( 'Email verification', 'customer-email-verification' ); ?>">
									</div>
								<?php endif; ?>

								<!-- Popup Heading and Description -->
								<div class="cev-authorization__heading">
									<span id="otp-popup-title" class="cev-authorization__title" style="font-size: <?php echo esc_attr( $button_text_header_font_size ); ?>px;">
										<?php echo wp_kses_post( $heading_text ); ?>
									</span>
									<span class="cev-authorization__description" style="text-align:<?php echo esc_attr( $content_align ); ?>;">
										<?php echo wp_kses_post( $verification_message ); ?>
									</span>
								</div>

								<!-- OTP Input Fields -->
								<div class="otp-container" role="group" aria-label="<?php esc_attr_e( 'Enter verification code', 'customer-email-verification' ); ?>">
									<?php for ( $i = 1; $i <= $otp_digits; $i++ ) : ?>
										<?php
										/* translators: %d: OTP digit position number */
										$aria_label = sprintf( esc_attr__( 'OTP digit %d', 'customer-email-verification' ), (int) $i );
										?>
										<input
											type="text"
											maxlength="1"
											class="otp-input"
											id="otp_input_<?php echo esc_attr( $i ); ?>"
											aria-label="<?php echo esc_attr( $aria_label ); ?>"
											inputmode="numeric"
											autocomplete="one-time-code"
										/>
									<?php endfor; ?>
								</div>

								<!-- Error Message Container -->
								<div class="error_mesg" role="alert" aria-live="polite"></div>

								<!-- Hidden Form Fields -->
								<input type="hidden" value="<?php echo esc_attr( $enable_email_verification ); ?>" name="cev_enable_email_verification_popup" id="cev_enable_email_verification_popup">
								<input type="hidden" value="<?php echo esc_attr( $password_setup_link_enabled ); ?>" name="password_setup_link_enabled" id="password_setup_link_enabled">

								<!-- Verify Button -->
								<button id="verify-otp-button" type="button" style="display: none;" aria-label="<?php esc_attr_e( 'Verify OTP code', 'customer-email-verification' ); ?>">
									<?php esc_html_e( 'Verify', 'customer-email-verification' ); ?>
								</button>

								<!-- Resend Success Message -->
								<p class="resend_sucsess" style="color: green; display:none" role="status" aria-live="polite">
									<?php esc_html_e( 'OTP sent successfully', 'customer-email-verification' ); ?>
								</p>

								<!-- Footer Message -->
								<?php if ( ! empty( $footer_message ) ) : ?>
									<footer class="cev-authorization__footer" style="text-align: <?php echo esc_attr( $content_align ); ?>; display:none;">
										<?php echo wp_kses_post( $footer_message ); ?>
									</footer>
								<?php endif; ?>

								<!-- Resend Timer -->
								<span class="cev_resend_timer" style="display:none; padding-left:0.5rem; font-size:0.75rem; color:#6b7280;" aria-live="polite"></span>
							</section>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Loading Overlay -->
<div class="cev_loading_overlay" aria-hidden="true"></div>

<!-- Inline Styles for Popup Styling -->
<style>
	/* Popup overlay background with transparency */
	.cev-authorization-grid__visual {
		background-color: <?php echo esc_attr( cev_pro()->function->hex2rgba( $overlay_background_color, '0.7' ) ); ?> !important;
	}

	/* Prevent background interference */
	html {
		background: none;
	}

	/* Hide WordPress customizer edit shortcuts on popup */
	.customize-partial-edit-shortcut-button {
		display: none;
	}
</style>
