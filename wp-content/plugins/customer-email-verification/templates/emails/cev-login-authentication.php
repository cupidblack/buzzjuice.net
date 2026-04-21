<?php
$cev_verification_image_content            = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_image_content', '' );
$cev_new_email_image_width                 = cev_pro()->function->cev_pro_customizer_settings( 'cev_email_content_widget_header_image_width', cev_pro()->customizer_options->defaults['cev_email_content_widget_header_image_width'] );
$cev_content_align_style                   = cev_pro()->function->cev_pro_customizer_settings( 'cev_content_align_style', cev_pro()->customizer_options->defaults['cev_content_align_style'] );
$cev_verification_content_background_color = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_content_background_color', cev_pro()->customizer_options->defaults['cev_verification_content_background_color'] );
$cev_verification_content_border_color     = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_content_border_color', cev_pro()->customizer_options->defaults['cev_verification_content_border_color'] );
$cev_header_content_font_size              = cev_pro()->function->cev_pro_customizer_settings( 'cev_header_content_font_size', cev_pro()->customizer_options->defaults['cev_header_content_font_size'] );
$cev_verification_content_font_color       = cev_pro()->function->cev_pro_customizer_settings( 'cev_verification_content_font_color', cev_pro()->customizer_options->defaults['cev_verification_content_font_color'] );

?>
<style>
.cev_devie_details {
	padding: 0;
}
.cev_devie_details li {
	margin-left: 0;
}
#template_header {
	display: none;
}
#template_header_image {
	display: none;
}
#template_container, #template_body {
	width: 600px !important;
	border: 1px solid <?php echo esc_attr( $cev_verification_content_border_color ); ?> !important;
}
#template_footer td {
	border-top: 0 !important;
}
#body_content {
	background-color: <?php echo esc_attr( $cev_verification_content_background_color ); ?> !important;
	padding: 0 !important;
}
div#wrapper {
	padding-top: 40px;
}
</style>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; border-collapse:collapse;">

	<?php if ( $cev_verification_image_content ) : ?>
	<tr>
		<td align="center" style="padding:14px 0; text-align:center;">
			<img src="<?php echo esc_url( $cev_verification_image_content ); ?>"
				width="<?php echo esc_attr( $cev_new_email_image_width ); ?>"
				alt="<?php esc_attr_e( 'Email verification', 'customer-email-verification' ); ?>"
				style="display:inline-block; max-height:60px; width:auto; max-width:<?php echo esc_attr( $cev_new_email_image_width ); ?>px; height:auto;">
		</td>
	</tr>
	<?php endif; ?>

	<tr>
		<td align="<?php echo esc_attr( $cev_content_align_style ); ?>"
			style="padding:14px 0;
				   text-align:<?php echo esc_attr( $cev_content_align_style ); ?>;">
			<h1 style="margin:0;font-size:<?php echo esc_attr( $cev_header_content_font_size ); ?>px;font-weight:700;color:#1a1a2e;text-align:<?php echo esc_attr( $cev_content_align_style ); ?>;line-height:1.3;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,Helvetica,sans-serif;"><?php echo wp_kses_post( $email_heading ); ?></h1>
		</td>
	</tr>

	<tr>
		<td align="<?php echo esc_attr( $cev_content_align_style ); ?>"
			style="padding:14px 0;
				   text-align:<?php echo esc_attr( $cev_content_align_style ); ?>;
				   color:<?php echo esc_attr( $cev_verification_content_font_color ); ?>;
				   font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, Helvetica, sans-serif;
				   font-size:15px;
				   line-height:1.7;">
			<?php echo wp_kses_post( wpautop( $content ) ); ?>
		</td>
	</tr>

	<?php if ( ! empty( $footer_content ) ) : ?>
	<tr>
		<td align="<?php echo esc_attr( $cev_content_align_style ); ?>"
			style="padding:14px 0;
				   text-align:<?php echo esc_attr( $cev_content_align_style ); ?>;
				   color:#6b7280;
				   font-size:13px;
				   font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, Helvetica, sans-serif;
				   line-height:1.6;">
			<?php echo wp_kses_post( $footer_content ); ?>
		</td>
	</tr>
	<?php endif; ?>

</table>
