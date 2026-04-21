<?php
/**
 * Login Authentication Popup Template
 *
 * Template for displaying the login authentication OTP verification popup.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$CEV_Customizer_Options    = new CEV_Customizer_Options();
$sample_toggle_switch_cev  = cev_pro()->function->cev_pro_customizer_settings( 'sample_toggle_switch_cev', $CEV_Customizer_Options->defaults['sample_toggle_switch_cev'] );
$width                     = ( '1' == $sample_toggle_switch_cev ) ? '100%' : 'auto';
$cev_login_hide_otp_section = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_hide_otp_section', $CEV_Customizer_Options->defaults['cev_login_hide_otp_section'] );

// Safely initialize variables used in the template to avoid undefined variable notices.
$cev_widget_header_image_width    = cev_pro()->function->cev_pro_customizer_settings( 'cev_widget_header_image_width', isset( $CEV_Customizer_Options->defaults['cev_widget_header_image_width'] ) ? $CEV_Customizer_Options->defaults['cev_widget_header_image_width'] : '80' );
$cev_button_text_header_font_size = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_text_header_font_size', isset( $CEV_Customizer_Options->defaults['cev_button_text_header_font_size'] ) ? $CEV_Customizer_Options->defaults['cev_button_text_header_font_size'] : '22' );

$verification_popup_button_size      = cev_pro()->function->cev_pro_customizer_settings( 'cev_popup_button_size', isset( $CEV_Customizer_Options->defaults['cev_popup_button_size'] ) ? $CEV_Customizer_Options->defaults['cev_popup_button_size'] : 'normal' );
$cev_button_text_size_widget_header  = ( 'large' === $verification_popup_button_size ) ? 18 : 16;
$button_padding                      = ( 'large' === $verification_popup_button_size ) ? '15px 25px' : '12px 20px';
$cev_button_color_widget_header      = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_color_widget_header', '#212121' );
$cev_button_text_color_widget_header = cev_pro()->function->cev_pro_customizer_settings( 'cev_button_text_color_widget_header', '#ffffff' );

// Ensure $email is defined to avoid undefined variable notices in the message.
if ( ! isset( $email ) ) {
	$user = wp_get_current_user();
	$email = ( $user && $user->exists() ) ? $user->user_email : '';
}
?>
<div class="cev-authorization-grid__visual">
	<div class="cev-authorization-grid__holder ">
		<div class="cev-authorization-grid__inner" style="max-width: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_widget_content_width', '460px') ); ?>px;">
			<div class="cev-authorization" style="background: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_verification_popup_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_background_color']) ); ?>;">
				<form class="cev_login_authentication_form"  method="post">
					<section class="cev-authorization__holder" style="text-align: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>;  padding: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_widget_content_padding', '40') ); ?>px; ">
						<div class="popup_image">
							<?php
							$image = cev_pro()->function->cev_pro_customizer_settings('cev_verification_image', cev_pro()->plugin_dir_url() . 'assets/css/images/email-verification-icon.svg'); 
							if ( !empty($image) ) {
								?>
								<img src="<?php echo wp_kses_post( $image ); ?>" style="width:<?php echo wp_kses_post( $cev_widget_header_image_width ); ?>px;">
							<?php } ?>
						</div>
						<div class="cev-authorization__heading">
							<span class="cev-authorization__title" style="font-size: <?php echo wp_kses_post( $cev_button_text_header_font_size ); ?>px;">
								<?php
								$heading_default = __('Verify its you.', 'customer-email-verification');
								$heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_auth_header', $CEV_Customizer_Options->defaults['cev_login_auth_header']); 
								if ( !empty($heading) ) { 
									echo wp_kses_post( $heading ); 
								} else {
									echo wp_kses_post( $heading_default ); 
								}
								?>
							</span>
							<span class="cev-authorization__description" style="text-align:<?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>;">
								<?php
								/* translators: %s: search send verification code */
								$message = sprintf(__('We sent verification code to <strong>%s</strong>. To verify your email address, please check your inbox and enter the code below.', 'customer-email-verification'), $email);
								$message = apply_filters( 'cev_login_auth_message', $message, $email );
								echo wp_kses_post( $message ); 
								?>
							</span>
						</div>
						<?php if ( 0 == $cev_login_hide_otp_section ) { ?>
								<div class="cev-pin-verification">
									<div class="cev-pin-verification__row">
										<div class="cev-field cev-field_size_extra-large cev-field_icon_left cev-field_event_right cev-field_text_center">
											<h5 class="required-filed" style="text-align:<?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>;">
												<?php
												// Determine number of digits from setting: '1' => 4, '2' => 6 (default 4).
												$code_length = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_length', 4 );

												if ( '2' === $code_length ) {
													$code_label = __( '6-digit code', 'customer-email-verification' );
												} else {
													$code_label = __( '4-digit code', 'customer-email-verification' );
												}

												/**
												 * Filter the OTP code length label shown above the inputs.
												 *
												 * @since 2.8.13
												 *
												 * @param string $code_label  Default label based on setting (e.g. "4-digit code").
												 * @param mixed  $code_length Raw setting value for code length.
												 */
												$codelength = apply_filters( 'cev_verification_code_length', $code_label, $code_length );
												echo wp_kses_post( $codelength );
												?>
												*
											</h5>
											<?php
											if ( '1' == $code_length ) {
												$digits = 4;
											} elseif ( '2' == $code_length ) {
												$digits = 6;
											} else {
												// Default value in case $code_length is neither '1' nor '2'.
												$digits = 4;
											}
											?>
											<div class="otp-container">
												<?php for ( $i = 1; $i <= $digits; $i++ ) : ?>
													<input type="text" maxlength="1" class="otp-input-login" id="otp_input_<?php echo esc_attr('otp_input_' . $i); ?>" />
												<?php endfor; ?>
											</div>
										</div>
									</div>
									<div class="cev-pin-verification__failure js-pincode-invalid" style="display: none;">
										<div class="cev-alert cev-alert_theme_red">										
											<span class="js-pincode-error-message">
											<?php
											esc_html_e( 'Login OTP does not match', 'customer-email-verification' ); 
											?>
											</span>
										</div>
									</div>
									<div class="cev-pin-verification__success js-pincode-success" style="display: none;">
										<div class="cev-alert" style="background-color: #d4edda; border-color: #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0;">										
											<span class="js-pincode-success-message">
											</span>
										</div>
									</div>
									<div class="cev-pin-verification__events">
										<input type="hidden" name="cev_user_id" value="<?php echo esc_attr( get_current_user_id() ); ?>">
										<?php wp_nonce_field( 'cev_login_auth_with_otp', 'cev_login_auth_with_otp' ); ?>
										<input type="hidden" name="action" value="cev_login_auth_with_otp">
										<button class="cev-button  cev-button_size_promo cev-button_type_block cev-pin-verification__button is-disabled cev_login_btn" id="loginSubmitPinButton" type="submit" style="background-color:<?php echo wp_kses_post( $cev_button_color_widget_header ); ?>; color:<?php esc_html_e( $cev_button_text_color_widget_header ); ?>; font-size:<?php esc_html_e( $cev_button_text_size_widget_header ); ?>px; width: <?php esc_html_e( $width ); ?>; padding: <?php echo wp_kses_post( $button_padding ); ?>;display:none" >
										<?php 
										$verify = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_auth_button_text', $CEV_Customizer_Options->defaults['cev_login_auth_button_text'] );
										if ( !empty($verify) ) { 
											echo wp_kses_post( $verify ); 
										} else {
											esc_html_e('Verify Code', 'customer-email-verification');
										} 
										?>
										</button>
									</div>
								</div>
							<?php } ?>
						<footer class="cev-authorization__footer" style="text-align: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>;  display:none;">
						<?php 
							$CEV_Customizer_Options = new CEV_Customizer_Options();
							$footer_message = cev_pro()->function->cev_pro_customizer_settings( 'cev_login_auth_widget_footer', $CEV_Customizer_Options->defaults['cev_login_auth_widget_footer']);
							$footer_message = cev_pro()->function->maybe_parse_merge_tags( $footer_message, cev_pro()->function );			
							echo wp_kses_post( $footer_message );
						?>
						</footer>
						<div class="cev-resend-section" style="text-align: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>; margin-top: 15px;">
							<a href="#" class="cev_resend_link_front" style="display:none; color: <?php echo wp_kses_post( $cev_button_color_widget_header ); ?>; text-decoration: underline; cursor: pointer;">
								<?php esc_html_e( 'Resend verification code', 'customer-email-verification' ); ?>
							</a>
							<span class='cev_resend_timer' style='display:none; padding-left:0.5rem; font-size:0.75rem; color:#6b7280;'></span>
						</div>
					</section>
				</form>            
			</div>
		</div>
	</div>
</div>
<div class="cev_loading_overlay"></div>
<?php 
$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings('cev_verification_popup_overlay_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color']);
?>
<style>
	.cev-authorization-grid__visual{
		background-color: <?php echo wp_kses_post( cev_pro()->function->hex2rgba( $cev_verification_overlay_color ) ); ?> !important;	
	}	
	html { background: none;}
	.customize-partial-edit-shortcut-button {
		display: none;
	}
</style>

