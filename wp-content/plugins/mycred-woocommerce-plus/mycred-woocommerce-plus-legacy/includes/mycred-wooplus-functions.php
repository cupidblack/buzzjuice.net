<?php 

/**
 * Get URL's Last Segment
 *
 * @since 1.7.3
 * @version 1.0
 */
if ( ! function_exists( 'woo_partial_get_url_lat_segment' ) ) :
	function woo_partial_get_url_lat_segment() {
		
		global $wp;

		$current_url = home_url( $wp->request );

		$url = explode( '/', $current_url );

		$end_url = end( $url );

		$end_url = (int) $end_url;

		return $end_url;
	
	}
endif;

/**
 * Filter callback
 * 
 * @since 1.7.6
 * @version 1.0
 */
if ( !function_exists( 'mycred_wcp_filter_wp_kses_allowed_html' ) ) :
	function mycred_wcp_filter_wp_kses_allowed_html( $allowedposttags ) {
		
		$allowed_atts                = array(
		'align'      => array(),
		'class'      => array(),
		'type'       => array(),
		'id'         => array(),
		'dir'        => array(),
		'lang'       => array(),
		'style'      => array(),
		'xml:lang'   => array(),
		'src'        => array(),
		'alt'        => array(),
		'href'       => array(),
		'rel'        => array(),
		'rev'        => array(),
		'target'     => array(),
		'novalidate' => array(),
		'name'       => array(),
		'tabindex'   => array(),
		'action'     => array(),
		'method'     => array(),
		'for'        => array(),
		'width'      => array(),
		'height'     => array(),
		'data'       => array(),
		'title'      => array(),
		'value'      => array(),
		'selected'   => array(),
		'enctype'    => array(),
		'disable'    => array(),
		'disabled'   => array(),
		'size'		 => array(),
		'colspan'	 => array(),
		);
	
		$allowedposttags['size']     = $allowed_atts;
		$allowedposttags['form']     = $allowed_atts;
		$allowedposttags['label']    = $allowed_atts;
		$allowedposttags['select']   = $allowed_atts;
		$allowedposttags['option']   = $allowed_atts;
		$allowedposttags['input']    = $allowed_atts;
		$allowedposttags['textarea'] = $allowed_atts;
		$allowedposttags['iframe']   = $allowed_atts;
		$allowedposttags['script']   = $allowed_atts;
		$allowedposttags['style']    = $allowed_atts;
		$allowedposttags['strong']   = $allowed_atts;
		$allowedposttags['small']    = $allowed_atts;
		$allowedposttags['table']    = $allowed_atts;
		$allowedposttags['span']     = $allowed_atts;
		$allowedposttags['abbr']     = $allowed_atts;
		$allowedposttags['code']     = $allowed_atts;
		$allowedposttags['pre']      = $allowed_atts;
		$allowedposttags['div']      = $allowed_atts;
		$allowedposttags['img']      = $allowed_atts;
		$allowedposttags['h1']       = $allowed_atts;
		$allowedposttags['h2']       = $allowed_atts;
		$allowedposttags['h3']       = $allowed_atts;
		$allowedposttags['h4']       = $allowed_atts;
		$allowedposttags['h5']       = $allowed_atts;
		$allowedposttags['h6']       = $allowed_atts;
		$allowedposttags['ol']       = $allowed_atts;
		$allowedposttags['ul']       = $allowed_atts;
		$allowedposttags['li']       = $allowed_atts;
		$allowedposttags['em']       = $allowed_atts;
		$allowedposttags['hr']       = $allowed_atts;
		$allowedposttags['br']       = $allowed_atts;
		$allowedposttags['tr']       = $allowed_atts;
		$allowedposttags['td']       = $allowed_atts;
		$allowedposttags['p']        = $allowed_atts;
		$allowedposttags['a']        = $allowed_atts;
		$allowedposttags['b']        = $allowed_atts;
		$allowedposttags['i']        = $allowed_atts;

		return $allowedposttags;
	}
endif;

/**
 * Filters the default `wp_kses_post`
 *
 * @since 1.7.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_wcp_kses_html' ) ) {

	function mycred_wcp_kses_html( $content ) {

		add_filter( 'wp_kses_allowed_html', 'mycred_wcp_filter_wp_kses_allowed_html', 10, 2 );

		$content = (string) $content;

		$content = wp_kses_post( $content );
		
		remove_filter( 'wp_kses_allowed_html', 'mycred_wcp_filter_wp_kses_allowed_html' );
		
		return $content;
	
	}

}

/**
 * Get Plugin Settings
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_account_settings' ) ) :
	function mycred_part_woo_account_settings() {

		$default = array(
			'title'      => __( 'Points History', 'mycredpartwoo' ),
			'desc'       => '',
			'slug'       => 'points',
			'point_type' => MYCRED_DEFAULT_TYPE_KEY,
			'number'     => 10,
			'nav'        => 0,
			'nav_size'   => 10,
			'show'       => '',
		);
		$saved   = get_option( 'mycred_partial_payments_account_woo', $default );

		return shortcode_atts( $default, $saved );

	}
endif;

if ( ! function_exists( 'mpp_remove_coupon_and_refund' ) ) :
	function mpp_remove_coupon_and_refund( $settings = null ) {

		$coupons = WC()->cart->get_coupons();

		if ( ! empty( $coupons ) ) {

			foreach ( $coupons as $code => $coupon ) {

				$is_partial_coupon = get_post_meta( $coupon->id, 'mycred_partial_coupon', true );

				if ( $is_partial_coupon ) {

					global $wpdb, $mycred;

					$user_id = get_current_user_id();

					$log = new myCRED_Query_Log( 
						array( 
							'user_id' => $user_id,
							'ref'     => 'partial_payment',
							'ref_id'  => $coupon->id,
							'fields'  => array( 'id', 'creds', 'ctype' )
						) 
					);

					if ( ! empty( $log->results[0]->id ) ) {

						if ( empty( $settings ) ) {
							$settings = mycred_part_woo_settings();
						}
						
						// Refund payment
						$mycred->add_creds(
							'partial_payment_refund',
							$user_id,
							abs( $log->results[0]->creds ),
							$settings['log_refund'],
							$coupon->id,
							'',
							$log->results[0]->ctype
						);

						// Update partial payment in log to prevent re-use
						$wpdb->update(
							$mycred->log_table,
							array( 'ref_id' => 0 ),
							array( 'id' => $log->results[0]->id ),
							array( '%d' ),
							array( '%d' )
						);

						WC()->cart->remove_coupon( $code );

						// Trash coupon post object
						wp_trash_post( $coupon->id );

					}

				}

			}

		}

	}
endif;

if ( ! function_exists( 'mycred_woo_is_checkout_page' ) ) :
	function mycred_woo_is_checkout_page() {

		$checkout_page_id  = get_option( 'woocommerce_checkout_page_id' );
		/**
		 * Filter.
		 * 
		 * @since 1.0
		 */
		$is_page = apply_filters( 'mycred_woo_is_checkout_page', is_page( (int) $checkout_page_id ), $checkout_page_id );
		
		return $is_page;
	}
endif;
