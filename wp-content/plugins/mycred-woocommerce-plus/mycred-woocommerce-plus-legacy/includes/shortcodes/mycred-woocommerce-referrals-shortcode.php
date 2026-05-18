<?php
if ( ! function_exists( 'mycred_render_woo_product_referral_link' ) ) :
	function mycred_render_woo_product_referral_link( $atts, $content = '' ) {

		$attr = shortcode_atts(
			array(
				'type' 				=> MYCRED_DEFAULT_TYPE_KEY,
				'login_url' 		=> wp_login_url(),
				'registration_url' 	=> wp_registration_url()
			),
			$atts,
			'mycred_woocommerce_referral'
		);

		$shortcode_attr = array(
			'type' 				=> sanitize_textarea_field( $attr['type'] ),
			'login_url' 		=> sanitize_textarea_field( $attr['login_url'] ),
			'registration_url' 	=> sanitize_textarea_field( $attr['registration_url'] ),
		);

		if ( ! is_user_logged_in() ) {
			echo wp_kses_post( "<a href='" . esc_url( $shortcode_attr['login_url'] ) . "'>Login</a> or <a href='" . esc_url( $shortcode_attr['registration_url'] ) . "'>Register</a>" );
			return;
		}

		/**
		* Filter mycred_woocommerce_referral_
		* 
		* @since 1.0
		**/
		return apply_filters( 'mycred_woocommerce_referral_' . esc_attr( $shortcode_attr['type'] ), 
			'', 
			array(
				'type' 				=> esc_attr( $shortcode_attr['type'] ),
				'login_url' 		=> esc_url( $shortcode_attr['login_url'] ),
				'registration_url' 	=> esc_url( $shortcode_attr['registration_url'] ),
			), 
			$content 
		);

	}
endif;
add_shortcode( 'mycred_woocommerce_referral', 'mycred_render_woo_product_referral_link' );
