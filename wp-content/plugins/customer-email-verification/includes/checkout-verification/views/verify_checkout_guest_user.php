<?php
/**
 * Guest User Checkout Verification Popup Template
 *
 * This template displays the email verification popup for guest users during checkout.
 * It includes email input, OTP verification fields, and all necessary styling options.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get verification data from session.
// Check both session keys: cev_user_verification_data (set when code is sent) and cev_user_verified_data (set when verified).
$cev_user_verification_data_raw = WC()->session->get( 'cev_user_verification_data' );
$cev_user_verification_data = ! is_null( $cev_user_verification_data_raw ) ? json_decode( $cev_user_verification_data_raw ) : null;

$cev_user_verified_data_raw = WC()->session->get( 'cev_user_verified_data' );
$cev_user_verified_data = ! is_null( $cev_user_verified_data_raw ) ? json_decode( $cev_user_verified_data_raw ) : null;

// Determine email and verification status from session data.
// Priority: cev_user_verification_data (active verification) > cev_user_verified_data (already verified).
$email = 'youremail@gmail.com';
if ( ! empty( $cev_user_verification_data ) && isset( $cev_user_verification_data->email ) ) {
	// Email is in active verification session (code sent, not yet verified).
	$email = $cev_user_verification_data->email;
	$cev_already_verify_style = 'pointer-events: all';
} elseif ( ! empty( $cev_user_verified_data ) && isset( $cev_user_verified_data->email ) ) {
	// Email is in verified session (already verified).
	$email = $cev_user_verified_data->email;
	$cev_already_verify_style = 'pointer-events: all';
} else {
	// No email found in session, use default placeholder.
	$cev_already_verify_style = 'pointer-events: none;color: rgba(0,0,0,.6)';
}

// Initialize customizer options for default values.
$CEV_Customizer_Options = new CEV_Customizer_Options();

// Get button styling options.
$cev_button_color_widget_header        = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_color_widget_header', '#212121' );
$cev_button_text_color_widget_header   = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_text_color_widget_header', '#ffffff' );
$cev_button_text_header_font_size       = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_text_header_font_size', '22' );
$verification_popup_button_size         = cev_pro()->function->cev_pro_customizer_settings( 'cev_popup_button_size', $CEV_Customizer_Options->defaults['cev_popup_button_size'] );
$cev_button_text_size_widget_header    = ( 'large' === $verification_popup_button_size ) ? 18 : 16;
$cev_button_padding_size_widget_header = ( 'large' === $verification_popup_button_size ) ? '15px 25px' : '12px 20px';

// Get widget header image settings.
$cev_widget_header_image_width = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_header_image_width', '80' );

// Get content and messaging options.
$heading_default = __( 'Verify its you.', 'customer-email-verification' );
$heading         = cev_pro()->function->cev_pro_customizer_settings( 'cev_header_text_checkout', $heading_default );
if ( empty( $heading ) ) {
	$heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_header', $heading_default );
}
$image           = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_image', cev_pro()->plugin_dir_url() . 'assets/css/images/email-verification-icon.svg' );

// Get verification message from customizer settings (with placeholder for email).
$verification_message = cev_pro()->function->cev_pro_customizer_settings(
	'cev_verification_widget_message_checkout',
	$CEV_Customizer_Options->defaults['cev_verification_widget_message_checkout']
);
// Parse other merge tags (like {site_title}) but keep {customer_email} for JavaScript replacement.
$verification_message = cev_pro()->function->maybe_parse_merge_tags( $verification_message, cev_pro()->function );
// Note: {customer_email} will be replaced dynamically via JavaScript when popup is shown.

$cev_content_align = cev_pro()->function->cev_pro_customizer_settings( 'cev_content_align', 'center' );
$email_address     = __( 'Email address', 'customer-email-verification' );

// Get button text options (guard against missing default key).
$verify_send_default = isset( $CEV_Customizer_Options->defaults['cev_verification_header_send_verify_button_text'] )
	? $CEV_Customizer_Options->defaults['cev_verification_header_send_verify_button_text']
	: __( 'Verify Your Email', 'customer-email-verification' );

$verify_send = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_header_send_verify_button_text', $verify_send_default );

// Get OTP section visibility setting.
$cev_verification_widget_hide_otp_section = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_widget_hide_otp_section', $CEV_Customizer_Options->defaults['cev_verification_widget_hide_otp_section'] );

// Get button width setting.
$sample_toggle_switch_cev = cev_pro()->function->cev_pro_customizer_settings( 'sample_toggle_switch_cev', $CEV_Customizer_Options->defaults['sample_toggle_switch_cev'] );
$width                    = ( '1' === $sample_toggle_switch_cev ) ? '100%' : 'auto';

// Get widget dimensions and styling.
$widget_content_width        = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_content_width', '460px' );
$popup_background_color      = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_background_color', '#fafafa' );
$widget_content_padding      = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_content_padding', '40' );

// Prepare escaped values for use in HTML attributes.
$escaped_content_width     = esc_attr( $widget_content_width );
$escaped_background_color  = esc_attr( $popup_background_color );
$escaped_content_align     = esc_attr( $cev_content_align );
$escaped_content_padding   = esc_attr( $widget_content_padding );
$escaped_image_width       = esc_attr( $cev_widget_header_image_width );
$escaped_font_size         = esc_attr( $cev_button_text_header_font_size );
$escaped_button_color      = esc_attr( $cev_button_color_widget_header );
$escaped_button_text_color = esc_attr( $cev_button_text_color_widget_header );
$escaped_button_text_size  = esc_attr( $cev_button_text_size_widget_header );
$escaped_button_padding    = esc_attr( $cev_button_padding_size_widget_header );
$escaped_width             = esc_attr( $width );
$escaped_image_url         = esc_url( $image );
$escaped_verify_style       = esc_attr( $cev_already_verify_style );

// Get nonces for AJAX requests.
$wp_nonce_send    = wp_create_nonce( 'wc_cev_email_guest_user' );
$wp_nonce_verify  = wp_create_nonce( 'wc_cev_email_guest_user_verify' );
$escaped_nonce_send   = esc_attr( $wp_nonce_send );
$escaped_nonce_verify = esc_attr( $wp_nonce_verify );
?>

<div class="cev-authorization-grid__visual" style="display:block;">
	<div class="cev-authorization-grid__holder">
		<div class="cev-authorization-grid__inner" style="max-width: <?php echo esc_attr( $escaped_content_width ); ?>px;">
			<div class="cev-authorization" style="background: <?php echo esc_attr( $escaped_background_color ); ?>;">
				<form id="cev_verify_email" class="cev_pin_verification_form_pro" method="post" novalidate>
					<section class="cev-authorization__holder" style="text-align: <?php echo esc_attr( $escaped_content_align ); ?>; padding: <?php echo esc_attr( $escaped_content_padding ); ?>px;">
						<?php // Display verification image if available. ?>
						<div class="popup_image">
							<?php if ( ! empty( $image ) ) : ?>
								<img src="<?php echo esc_url( $escaped_image_url ); ?>" style="width:<?php echo esc_attr( $escaped_image_width ); ?>px;" alt="<?php esc_attr_e( 'Email Verification', 'customer-email-verification' ); ?>">
							<?php endif; ?>
						</div>

						<?php // Main heading and description section. ?>
						<div class="cev-authorization__heading">
							<span class="cev-authorization__title" style="font-size: <?php echo esc_attr( $escaped_font_size ); ?>px;">
								<?php echo esc_html( $heading ); ?>
							</span>
							<span class="cev-authorization__description" style="text-align:<?php echo esc_attr( $escaped_content_align ); ?>;">
								<?php 
								// Use the parsed verification message with merge tags.
								$display_message = $verification_message;
								// Replace {customer_email} placeholder if email is available.
								if ( ! empty( $email ) && 'youremail@gmail.com' !== $email ) {
									$display_message = str_replace( '{customer_email}', '<strong>' . esc_html( $email ) . '</strong>', $display_message );
								}
								echo wp_kses_post( $display_message ); 
								?>
							</span>
						</div>

						<?php // Email input section. ?>
						<div class="cev-field cev-field_size_extra-large cev-field_icon_left cev-field_event_right cev-hide-success-message">
							<h5 class="required-filed-email" style="text-align:<?php echo esc_attr( $escaped_content_align ); ?>;">
								<?php echo esc_html( $email_address ); ?>
							</h5>

							<input class="cev_pin_box_email" style="text-align:<?php echo esc_attr( $escaped_content_align ); ?>;" id="cev_pin_email" name="cev_pin_email" type="email" placeholder="<?php esc_attr_e( 'Enter your email address', 'customer-email-verification' ); ?>" required />

							<a href="javascript:;" class="cev_already_verify" style="text-align: <?php echo esc_attr( $escaped_content_align ); ?>;<?php echo esc_attr( $escaped_verify_style ); ?>">
								<?php esc_html_e( 'Already have verification code?', 'customer-email-verification' ); ?>
							</a>

							<?php // Send verification code button. ?>
							<div class="cev_send_email_tag" style="text-align: <?php echo esc_attr( $escaped_content_align ); ?>;">
								<button wp_nonce="<?php echo esc_attr( $escaped_nonce_send ); ?>" class="cev-button cev-button_color_success cev-send-verification-code" type="button" style="background-color:<?php echo esc_attr( $escaped_button_color ); ?>; color:<?php echo esc_attr( $escaped_button_text_color ); ?>; font-size:<?php echo esc_attr( $escaped_button_text_size ); ?>px; width:<?php echo esc_attr( $escaped_width ); ?>; padding: <?php echo esc_attr( $escaped_button_padding ); ?>;">
									<?php
									if ( ! empty( $verify_send ) ) {
										echo esc_html( $verify_send );
									} else {
										esc_html_e( 'Verify Your Email', 'customer-email-verification' );
									}
									?>
								</button>
								<?php // Resend limit message (hidden by default). ?>
								<div class="cev_limit_message" style="display: none;">
									<?php
									$resend_limit_message = apply_filters( 'cev_resend_limit_message', __( 'Too many attempts, please contact us for further assistance', 'customer-email-verification' ) );
									echo esc_html( $resend_limit_message );
									?>
								</div>
							</div>
						</div>

						<?php // Verification code sent confirmation section (hidden by default) - matches preview structure. ?>
						<div class="cev-authorization__heading cev-show-reg-content" style="display:none;">
							<!-- Back Button -->
							<div class="back_btn" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Close popup', 'customer-email-verification' ); ?>">
								<svg fill="#000000" version="1.1" viewBox="-6.07 -6.07 72.87 72.87" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
									<polygon points="0,30.365 29.737,60.105 29.737,42.733 60.731,42.729 60.731,18.001 29.737,17.999 29.737,0.625"></polygon>
								</svg>
							</div>

							<span class="cev-authorization__title" style="font-size: <?php echo esc_attr( $escaped_font_size ); ?>px;">
								<?php
								// Use checkout OTP popup header text.
								$verification_header = cev_pro()->function->cev_pro_customizer_settings( 'cev_header_text_checkout_otp', $heading_default );
								if ( empty( $verification_header ) ) {
									$verification_header = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_header', $heading_default );
								}
								echo wp_kses_post( $verification_header );
								?>
							</span>
							<span class="cev-authorization__description" style="text-align:<?php echo esc_attr( $escaped_content_align ); ?>;">
								<?php
								// Get verification message from checkout OTP popup customizer settings.
								$otp_verification_message = cev_pro()->function->cev_pro_customizer_settings(
									'cev_verification_widget_message_checkout_otp',
									$CEV_Customizer_Options->defaults['cev_verification_widget_message_checkout_otp']
								);
								// Parse other merge tags (like {site_title}).
								$otp_verification_message = cev_pro()->function->maybe_parse_merge_tags( $otp_verification_message, cev_pro()->function );
								// Replace {customer_email} with a span that JavaScript can update.
								// Use session email as initial value, but JavaScript will update it with the actual entered email.
								$initial_email = ( ! empty( $email ) && 'youremail@gmail.com' !== $email ) ? esc_html( $email ) : '';
								if ( ! empty( $initial_email ) ) {
									$otp_verification_message = str_replace( '{customer_email}', '<strong><span class="cev-customer-email-display">' . $initial_email . '</span></strong>', $otp_verification_message );
								} else {
									// Add placeholder span for JavaScript to populate.
									$otp_verification_message = str_replace( '{customer_email}', '<strong><span class="cev-customer-email-display"></span></strong>', $otp_verification_message );
								}
								// Allow filtering of verification message.
								$otp_verification_message = apply_filters( 'cev_verification_popup_message', $otp_verification_message, $email );
								echo wp_kses_post( $otp_verification_message );
								?>
							</span>
						</div>

						<?php // OTP verification section (only shown if not hidden) - simplified to match preview. ?>
						<?php if ( 0 === (int) $cev_verification_widget_hide_otp_section ) : ?>
							<?php
							// Determine number of OTP digits based on setting.
							$code_length = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_length', 4 );
							if ( '1' === $code_length ) {
								$otp_digits = 4;
							} elseif ( '2' === $code_length ) {
								$otp_digits = 6;
							} else {
								// Default to 4 digits if setting is invalid.
								$otp_digits = 4;
							}
							?>
							<div class="otp-container" role="group" aria-label="<?php esc_attr_e( 'Enter verification code', 'customer-email-verification' ); ?>" style="display:none;">
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
							<div class="error_mesg" role="alert" aria-live="polite" style="display: none;"></div>

							<!-- Verify OTP Button -->
							<div class="cev_send_email_tag" style="text-align: <?php echo esc_attr( $escaped_content_align ); ?>; display:none;">
								<button id="cev_submit_pin_button" class="cev-button cev-button_color_success" type="button" style="background-color:<?php echo esc_attr( $escaped_button_color ); ?>; color:<?php echo esc_attr( $escaped_button_text_color ); ?>; font-size:<?php echo esc_attr( $escaped_button_text_size ); ?>px; width:<?php echo esc_attr( $escaped_width ); ?>; padding: <?php echo esc_attr( $escaped_button_padding ); ?>;">
									<?php esc_html_e( 'Verify Code', 'customer-email-verification' ); ?>
								</button>
							</div>

							<!-- Footer Message for OTP popup -->
							<?php
							$footer_message_otp = cev_pro()->function->cev_pro_customizer_settings(
								'cev_verification_widget_footer_checkout_otp',
								isset( $CEV_Customizer_Options->defaults['cev_verification_widget_footer_checkout_otp'] ) ? $CEV_Customizer_Options->defaults['cev_verification_widget_footer_checkout_otp'] : ''
							);
							// Parse merge tags in footer message if method exists.
							if ( ! empty( $footer_message_otp ) ) {
								$footer_message_otp = cev_pro()->function->maybe_parse_merge_tags( $footer_message_otp, cev_pro()->function );
							}
							?>
							<?php if ( ! empty( $footer_message_otp ) ) : ?>
								<footer class="cev-authorization__footer cev-rge-content" style="text-align: <?php echo esc_attr( $escaped_content_align ); ?>; display:none;">
									<?php echo wp_kses_post( $footer_message_otp ); ?>
								</footer>
							<?php endif; ?>
						<?php endif; ?>

						<?php // Footer message (shown initially) — only for guests; logged-in users do not need Login now. ?>
					<?php if ( ! is_user_logged_in() ) : ?>
						<footer class="cev-authorization__footer cev_send_email_span_tag" style="text-align: <?php echo esc_attr( $escaped_content_align ); ?>;">
							<?php
							$footer_message_checkout = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_widget_message_footer_checkout_pro', $CEV_Customizer_Options->defaults['cev_verification_widget_message_footer_checkout_pro'] );
							echo wp_kses_post( $footer_message_checkout );
							?>
						</footer>
					<?php endif; ?>

						<?php // Footer message with resend link (hidden by default). ?>
						<footer class="cev-authorization__footer cev-rge-content cev_resend_link_front" style="text-align: <?php echo esc_attr( $escaped_content_align ); ?>;display:none">
							<?php
							$footer_message = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_widget_footer', $CEV_Customizer_Options->defaults['cev_verification_widget_footer'] );
							$footer_message = cev_pro()->function->maybe_parse_merge_tags( $footer_message, cev_pro()->function );
							echo wp_kses_post( $footer_message );
							?>
						</footer>

						<?php // Resend timer display (hidden by default). ?>
						<span class="cev_resend_timer cev_resend_front" style="display:none; padding-left:0.5rem; font-size:0.75rem; color:#6b7280;"></span>
					</section>
				</form>
			</div>
		</div>
	</div>
</div>

<?php
// Get overlay color for popup background.
$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_overlay_background_color', '#e0e0e0' );
$overlay_background             = cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' );
?>
<style>
	.cev-authorization-grid__visual {
		background-color: <?php echo esc_attr( $overlay_background ); ?> !important;
	}
	html {
		background: none;
	}
	.customize-partial-edit-shortcut-button {
		display: none;
	}
</style>
