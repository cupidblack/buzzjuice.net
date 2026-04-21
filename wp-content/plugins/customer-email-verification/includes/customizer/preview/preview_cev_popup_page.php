<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = ( 'johny@example.com' );
$Try_again = __('Try again', 'customer-email-verification');
$CEV_Customizer_Options = new CEV_Customizer_Options();
$cev_widget_header_image_width =  cev_pro()->function->cev_pro_customizer_settings('cev_widget_header_image_width', '150');
$cev_button_text_header_font_size = cev_pro()->function->cev_pro_customizer_settings('cev_button_text_header_font_size', '22');

$verification_popup_button_size = cev_pro()->function->cev_pro_customizer_settings('cev_popup_button_size', $CEV_Customizer_Options->defaults['cev_popup_button_size']);
$cev_button_text_size_widget_header = ( 'large' == $verification_popup_button_size ) ? 18 : 16 ;
$button_padding = ( 'large' == $verification_popup_button_size ) ? '15px 25px' : '12px 20px' ;
$cev_button_color_widget_header =  cev_pro()->function->cev_pro_customizer_settings('cev_button_color_widget_header', '#212121');
$cev_button_text_color_widget_header =  cev_pro()->function->cev_pro_customizer_settings('cev_button_text_color_widget_header', '#ffffff');
$cev_verification_hide_otp_section =  cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_hide_otp_section', $CEV_Customizer_Options->defaults['cev_verification_hide_otp_section'] );
$sample_toggle_switch_cev = cev_pro()->function->cev_pro_customizer_settings('sample_toggle_switch_cev', $CEV_Customizer_Options->defaults['sample_toggle_switch_cev']  );
$width = ( '1' == $sample_toggle_switch_cev ) ? '100%' : 'auto';
$verification_type = get_option( 'cev_verification_type' );

// Get overlay background color for inline style.
$cev_verification_overlay_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_popup_overlay_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_overlay_background_color'] );
$overlay_rgba = cev_pro()->function->hex2rgba( $cev_verification_overlay_color, '0.7' );
?>

<div class="cev-authorization-grid__visual" style="background-color: <?php echo esc_attr( $overlay_rgba ); ?> !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; z-index: 5000 !important;">
	<div class="cev-authorization-grid__holder">
		<div class="cev-authorization-grid__inner" style="max-width: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_widget_content_width', '460') ); ?>;">
			<div class="cev-authorization" style="background: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_verification_popup_background_color', $CEV_Customizer_Options->defaults['cev_verification_popup_background_color'] ) ); ?>;">				
					<section class="cev-authorization__holder" style="position: relative; text-align: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>;  padding: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_widget_content_padding', '40') ); ?>px; ">
						<!-- Verification Image -->
						<?php 
						$image = cev_pro()->function->cev_pro_customizer_settings('cev_verification_image', cev_pro()->plugin_dir_url() . 'assets/css/images/email-verification-icon.svg'); 
						if ( !empty($image) ) {
							?>
							<div class="popup_image">
								<img src="<?php echo wp_kses_post( $image ); ?> " style="width:<?php echo wp_kses_post( $cev_widget_header_image_width ); ?>px;" alt="<?php esc_attr_e( 'Email verification', 'customer-email-verification' ); ?>">
							</div>
						<?php } ?>
						<div class="cev-authorization__heading">
							<span class="cev-authorization__title" style="font-size: <?php echo wp_kses_post( $cev_button_text_header_font_size); ?>px;">
							<?php
								$heading_default = __('Verify its you.', 'customer-email-verification');
								$heading = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_header', $CEV_Customizer_Options->defaults['cev_verification_header'] ); 
							if ( !empty($heading) ) { 
								echo wp_kses_post( $heading ); 
							} else {
								echo wp_kses_post( $heading_default ); 
							}
							?>
							</span>
							<span class="cev-authorization__description" style="text-align:<?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>;">
								<?php
								// Get verification message from customizer settings (with placeholder for email).
								$verification_message = cev_pro()->function->cev_pro_customizer_settings(
									'cev_verification_message',
									__( 'We sent verification code to {customer_email}. To verify your email address, please check your inbox and enter the code below.', 'customer-email-verification' )
								);
								// Parse other merge tags (like {site_title}) but keep {customer_email} for display.
								$verification_message = cev_pro()->function->maybe_parse_merge_tags( $verification_message, cev_pro()->function );
								// Replace {customer_email} with actual email for preview.
								$verification_message = str_replace( '{customer_email}', '<strong>' . esc_html( $email ) . '</strong>', $verification_message );
								echo wp_kses_post( $verification_message );
								?>
							</span>
						</div>
						<?php if ( 0 == $cev_verification_hide_otp_section ) { ?>
								<!-- OTP Input Fields -->
											<?php 
											$code_length = cev_pro()->function->cev_pro_admin_settings( 'cev_verification_code_length', 4 );
											if ( '1' == $code_length ) {
									$otp_digits = 4;
											} else if ( '2' == $code_length ) {
									$otp_digits = 6;
											} else {
									$otp_digits = 4;
											}
											?>
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

								<!-- Footer Message -->
									<?php
									$CEV_Customizer_Options = new CEV_Customizer_Options();
									$footer_message = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_widget_footer', $CEV_Customizer_Options->defaults['cev_verification_widget_footer']);
								$footer_message = cev_pro()->function->maybe_parse_merge_tags( $footer_message, cev_pro()->function );
									if ( ! empty( $footer_message ) ) :
										?>
									<footer class="cev-authorization__footer" style="text-align: <?php echo wp_kses_post( cev_pro()->function->cev_pro_customizer_settings('cev_content_align', 'center') ); ?>;">
										<?php echo wp_kses_post( $footer_message ); ?>
						</footer>
								<?php endif; ?>
							<?php } ?>
						
					</section>
			</div>
		</div>
	</div>
</div>
