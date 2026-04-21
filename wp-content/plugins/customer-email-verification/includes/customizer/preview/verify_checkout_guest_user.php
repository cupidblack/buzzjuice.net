<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// For preview, use sample email for checkout email popup.
$email = 'johny@example.com';

$CEV_Customizer_Options = new CEV_Customizer_Options();
$cev_button_color_widget_header =  cev_pro()->function->cev_pro_customizer_settings('cev_button_color_widget_header', '#212121');
$cev_button_text_color_widget_header =  cev_pro()->function->cev_pro_customizer_settings('cev_button_text_color_widget_header', '#ffffff');
$cev_widget_header_image_width =  cev_pro()->function->cev_pro_customizer_settings('cev_widget_header_image_width', '80');

$cev_button_text_header_font_size = cev_pro()->function->cev_pro_customizer_settings('cev_button_text_header_font_size', '22');

$verification_popup_button_size = cev_pro()->function->cev_pro_customizer_settings('cev_popup_button_size', $CEV_Customizer_Options->defaults['cev_popup_button_size']);
$cev_button_text_size_widget_header = ( 'large' == $verification_popup_button_size ) ? 18 : 16 ;
$cev_button_padding_size_widget_header = ( 'large' == $verification_popup_button_size ) ? '15px 25px' : '12px 20px';

// Get header text - use checkout email popup header.
$heading_default = __('Verify its you.', 'customer-email-verification');
$heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_header_text_checkout', $heading_default );
if ( empty( $heading ) ) {
	$heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_header', $heading_default );
}

// Get verification image.
$image = cev_pro()->function->cev_pro_customizer_settings('cev_verification_image', cev_pro()->plugin_dir_url() . 'assets/css/images/email-verification-icon.svg');

// Get verification message from checkout email popup customizer settings.
$verification_message = cev_pro()->function->cev_pro_customizer_settings(
	'cev_verification_widget_message_checkout',
	$CEV_Customizer_Options->defaults['cev_verification_widget_message_checkout']
);
// Parse other merge tags (like {site_title}).
$verification_message = cev_pro()->function->maybe_parse_merge_tags( $verification_message, cev_pro()->function );

$cev_content_align = cev_pro()->function->cev_pro_customizer_settings( 'cev_content_align', 'center' );
$widget_content_width = cev_pro()->function->cev_pro_customizer_settings('cev_widget_content_width', '460');
$widget_content_padding = cev_pro()->function->cev_pro_customizer_settings('cev_widget_content_padding', '40');
$popup_background_color = cev_pro()->function->cev_pro_customizer_settings('cev_verification_popup_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_background_color']);

// Get button text and width settings.
// Guard against missing default array key to avoid PHP warnings.
$verify_send_default = isset( $CEV_Customizer_Options->defaults['cev_verification_header_send_verify_button_text'] )
	? $CEV_Customizer_Options->defaults['cev_verification_header_send_verify_button_text']
	: __( 'Verify Your Email', 'customer-email-verification' );

$verify_send = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_header_send_verify_button_text', $verify_send_default );
$sample_toggle_switch_cev = cev_pro()->function->cev_pro_customizer_settings('sample_toggle_switch_cev', $CEV_Customizer_Options->defaults['sample_toggle_switch_cev']  );
$width = ( '1' == $sample_toggle_switch_cev ) ? '100%' : 'auto';
$email_address = __( 'Email address', 'customer-email-verification');

// Get footer message for checkout email popup.
$footer_message_checkout = cev_pro()->function->cev_pro_customizer_settings(
	'cev_verification_widget_message_footer_checkout_pro',
	$CEV_Customizer_Options->defaults['cev_verification_widget_message_footer_checkout_pro']
);
// Parse merge tags in footer message.
if ( ! empty( $footer_message_checkout ) ) {
	$footer_message_checkout = cev_pro()->function->maybe_parse_merge_tags( $footer_message_checkout, cev_pro()->function );
}

// Get overlay background color for inline style.
$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_overlay_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color'] );
$overlay_rgba = cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' );
?>

<div class="cev-authorization-grid__visual" style="display:block; background-color: <?php echo esc_attr( $overlay_rgba ); ?> !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; z-index: 5000 !important;">
	<div class="cev-authorization-grid__holder">
		<div class="cev-authorization-grid__inner" style="max-width: <?php echo esc_attr( $widget_content_width ); ?>px;">
			<div class="cev-authorization" style="background: <?php echo esc_attr( $popup_background_color ); ?>;">
				<form id="cev_verify_email" class="cev_pin_verification_form_pro" method="post">
					<section class="cev-authorization__holder" style="text-align: <?php echo esc_attr( $cev_content_align ); ?>; padding: <?php echo esc_attr( $widget_content_padding ); ?>px;">
						<!-- Verification Image -->
						<?php if ( ! empty( $image ) ) : ?>
							<div class="popup_image">
								<img src="<?php echo esc_url( $image ); ?>" style="width:<?php echo esc_attr( $cev_widget_header_image_width ); ?>px;" alt="<?php esc_attr_e( 'Email verification', 'customer-email-verification' ); ?>">
							</div>
						<?php endif; ?>

						<!-- Popup Heading and Description -->
						<div class="cev-authorization__heading">
							<span class="cev-authorization__title" style="font-size: <?php echo esc_attr( $cev_button_text_header_font_size ); ?>px;">
								<?php echo wp_kses_post( $heading ); ?>
							</span>
							<span class="cev-authorization__description" style="text-align:<?php echo esc_attr( $cev_content_align ); ?>;">
								<?php echo wp_kses_post( $verification_message ); ?>
							</span>
						</div>

						<!-- Email Input Section -->
						<div class="cev-field cev-field_size_extra-large cev-field_icon_left cev-field_event_right cev-hide-success-message">
							<h5 class="required-filed-email" style="text-align:<?php echo esc_attr( $cev_content_align ); ?>;">
								<?php echo esc_html( $email_address ); ?>
							</h5>
							
							<input class="cev_pin_box_email" style="text-align:<?php echo esc_attr( $cev_content_align ); ?>;" id="cev_pin_email" name="cev_pin_email" type="email" placeholder="<?php esc_attr_e('Enter your email address', 'customer-email-verification'); ?>" required />
							
							<a href="javascript:;" class="cev_already_verify" style="text-align: <?php echo esc_attr( $cev_content_align ); ?>; pointer-events: all;" >
								<?php esc_html_e( 'Already have verification code?', 'customer-email-verification' ); ?>
							</a>
							
							<div class="cev_send_email_tag" style="text-align: <?php echo esc_attr( $cev_content_align ); ?>;">
								<button wp_nonce="<?php echo esc_attr( wp_create_nonce( 'wc_cev_email_guest_user' ) ); ?>" class="cev-button cev-button_color_success cev-send-verification-code" type="button" style="background-color:<?php echo esc_attr( $cev_button_color_widget_header ); ?>; color:<?php echo esc_attr($cev_button_text_color_widget_header); ?>; font-size:<?php echo esc_attr( $cev_button_text_size_widget_header ); ?>px; width:<?php echo esc_attr( $width ); ?>; padding: <?php echo esc_attr( $cev_button_padding_size_widget_header ); ?>;">
									<?php 
									if ( !empty( $verify_send ) ) { 
										echo esc_html( $verify_send ); 
									} else {
										esc_html_e('Verify Your Email', 'customer-email-verification');
									} 
									?>
								</button>
								<div class="cev_limit_message" style="display: none;">
									<?php
									$resend_limit_message = apply_filters( 'cev_resend_limit_message', __( 'Too many attempts, please contact us for further assistance', 'customer-email-verification' ) );
									echo esc_html( $resend_limit_message );
									?>
								</div>
							</div>
						</div>

						<!-- Footer Message -->
						<?php if ( ! empty( $footer_message_checkout ) ) : ?>
							<footer class="cev-authorization__footer cev_send_email_span_tag" style="text-align: <?php echo esc_attr( $cev_content_align ); ?>;">
								<?php echo wp_kses_post( $footer_message_checkout ); ?>
							</footer>
						<?php endif; ?>
					</section>
				</form>
			</div>
		</div>
	</div>
</div>
