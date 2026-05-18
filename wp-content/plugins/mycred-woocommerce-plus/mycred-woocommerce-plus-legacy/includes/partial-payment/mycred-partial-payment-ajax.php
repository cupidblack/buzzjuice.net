<?php
// No dirrect access
if ( ! defined( 'MYCRED_WOOPLUS_VERSION' ) ) {
	exit;
}

function mycred_new_partial_payment() {

	if ( ! isset( $_POST['token'] ) || ( isset( $_POST['token'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['token'] ), 'mycred-partial-payment-new' ) ) ) {
		exit( 'Not Authorized' );
	}

	$mycred_partial_payment = mycred_part_woo_settings();

	wc_clear_notices();

	if ( $mycred_partial_payment['max'] < 100 && count( WC()->cart->get_coupons() ) >= 1 ) {
		wc_add_notice( __( 'Please remove previous coupon to apply new discount.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	$mycred = mycred( $mycred_partial_payment['point_type'] );
	$user_id = get_current_user_id();

	if ( $mycred->exclude_user( $user_id ) ) {
		wc_add_notice( __( 'You are not allowed to use this feature.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	$balance = $mycred->get_users_balance( $user_id );

	if ( isset( $_POST['amount'] ) ) {
		$amount  = $mycred->number( abs( sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) );
	}

	if ( $amount == $mycred->zero() ) {
		wc_add_notice( __( 'Amount can not be zero.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	if ( $balance < $amount ) {
		wc_add_notice( __( 'Insufficient Funds. Please try a lower amount.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	$total = mycred_part_woo_get_total();
	
	$value = $mycred_partial_payment['exchange'] *  $amount ;

	if ( $value > ( ceil( $total / 100 ) * $mycred_partial_payment['max'] ) ) {
		wc_add_notice( __( 'The amount can not be greater than the maximum amount.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	if ( $value < $mycred_partial_payment['min'] ) {
		wc_add_notice( __( 'The amount can not be less than the minimum amount.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	// Create a Woo Coupon
	$coupon_code   = $user_id . time();
	$new_coupon_id = wp_insert_post(
		array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon',
		)
	);

	if ( null === $new_coupon_id || is_wp_error( $new_coupon_id ) ) {
		wc_add_notice( __( 'Failed to complete transaction. Error 1. Please contact support.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	// Update Coupon details
	update_post_meta( $new_coupon_id, 'discount_type', 'fixed_cart' );
	update_post_meta( $new_coupon_id, 'coupon_amount', $value );
	update_post_meta( $new_coupon_id, 'individual_use', 'no' );
	update_post_meta( $new_coupon_id, 'product_ids', '' );
	update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
	update_post_meta( $new_coupon_id, 'usage_limit', 1 );
	update_post_meta( $new_coupon_id, 'usage_limit_per_user', 1 );
	update_post_meta( $new_coupon_id, 'limit_usage_to_x_items', '' );
	update_post_meta( $new_coupon_id, 'usage_count', '' );
	update_post_meta( $new_coupon_id, 'expiry_date', '' );
	update_post_meta( $new_coupon_id, 'free_shipping', ( ( 'no' == $mycred_partial_payment['free_shipping'] ) ? 'no' : 'yes' ) );
	update_post_meta( $new_coupon_id, 'product_categories', array() );
	update_post_meta( $new_coupon_id, 'exclude_product_categories', array() );
	update_post_meta( $new_coupon_id, 'exclude_sale_items', ( ( 'no' == $mycred_partial_payment['sale_items'] ) ? 'yes' : 'no' ) );
	update_post_meta( $new_coupon_id, 'minimum_amount', '' );
	update_post_meta( $new_coupon_id, 'customer_email', array() );

	/**
	* Action mycred_woo_partial_after_coupon_generation
	* 
	* @since 1.0
	**/
	do_action( 'mycred_woo_partial_after_coupon_generation', $new_coupon_id, $_POST );

	$applied = WC()->cart->add_discount( $coupon_code );

	if ( true === $applied ) {

		if ( '' == $mycred_partial_payment['log'] ) {
			$mycred_partial_payment['log'] = __( 'Partial Payment', 'mycredpartwoo' );
		}

		update_post_meta( $new_coupon_id, 'mycred_partial_coupon', true );

		// Deduct amount only if coupon was successfully applied
		$mycred->add_creds(
			'partial_payment',
			$user_id,
			0 - $amount,
			$mycred_partial_payment['log'],
			$new_coupon_id,
			'',
			$mycred_partial_payment['point_type']
		);
		
		global $multiple_payment_restricted;

		if ( 'no' === $mycred_partial_payment['multiple'] ) {
			$multiple_payment_restricted = true;
		} else {
			$multiple_payment_restricted = false;
		}
		
		wc_clear_notices();
		wc_add_notice( __( 'Payment Successfully Applied.', 'mycredpartwoo' ) );
		wp_send_json_success( $multiple_payment_restricted );

	}

	// Delete the coupon
	wp_trash_post( $new_coupon_id );

	wc_add_notice( __( 'Failed to complete transaction. Error 2. Please contact support.', 'mycredpartwoo' ), 'error' );
	wp_send_json_error();

}
add_action( 'wp_ajax_mycred_new_partial_payment', 'mycred_new_partial_payment' );
add_action( 'wp_ajax_nopriv_mycred_new_partial_payment', 'mycred_new_partial_payment' );

function mycred_coupon_ajax() {

	if ( ! isset( $_POST['token'] ) || ( isset( $_POST['token'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['token'] ), 'mycred-partial-payment-new' ) ) ) {
		exit( 'Not Authorized' );
	}

	$mycred_partial_payment = mycred_part_woo_settings();

	$mycred = mycred( $mycred_partial_payment['point_type'] );
	$user_id = get_current_user_id();
	$type = $mycred_partial_payment['point_type'];

	if ( $mycred->exclude_user( $user_id ) ) {
		wc_add_notice( __( 'You are not allowed to use this feature.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	$balance = $mycred->get_users_balance( $user_id );

	wc_clear_notices();

	if ( isset( $_POST['amount'] ) ) {
		$amount = abs( sanitize_text_field( wp_unslash( (float) $_POST['amount'] ) ) );
	}
	
	$value = $amount * (int) $mycred_partial_payment['exchange'];

	if ( $value == $mycred->zero() ) {
		wc_add_notice( __( 'Amount can not be zero.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	if ( $value > $balance ) {
		wc_add_notice( __( 'Insufficient Funds. Please try a lower amount.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	if ( $value > $mycred_partial_payment['coupon_max'] ) {
		wc_add_notice( __( 'The amount can not be greater than the maximum amount.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	if ( $value < $mycred_partial_payment['min'] ) {
		wc_add_notice( __( 'The amount can not be less than the minimum amount.', 'mycredpartwoo' ), 'error' );
		wp_send_json_error();
	}

	$code   = strtolower( wp_generate_password( 12, false, false ) );
	$new_coupon_id = wp_insert_post(
		array(
			'post_title'   => $code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon',
		)
	);

	$mycred->add_creds(
		'points_to_coupon',
		$user_id,
		0 - $amount,
		'%plural% conversion into store coupon: ' . $code, 
		$new_coupon_id,
		array(
			'ref_type' => 'post',
			'code'     => $code,
		),
		$type
	);

	$balance = $mycred->number( $balance - $value );

	$sale_items = $mycred_partial_payment['sale_items'];

	// Update Coupon details
	update_post_meta( $new_coupon_id, 'discount_type', 'fixed_cart' );
	update_post_meta( $new_coupon_id, 'coupon_amount', $value );
	update_post_meta( $new_coupon_id, 'individual_use', 'no' );
	update_post_meta( $new_coupon_id, 'product_ids', '' );
	update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
	update_post_meta( $new_coupon_id, 'usage_limit', 1 );
	update_post_meta( $new_coupon_id, 'usage_limit_per_user', 1 );
	update_post_meta( $new_coupon_id, 'limit_usage_to_x_items', '' );
	update_post_meta( $new_coupon_id, 'usage_count', '' );
	update_post_meta( $new_coupon_id, 'expiry_date', '' );
	update_post_meta( $new_coupon_id, 'product_categories', array() );
	update_post_meta( $new_coupon_id, 'exclude_product_categories', array() );
	update_post_meta( $new_coupon_id, 'exclude_sale_items', ( ( 'no' == $mycred_partial_payment['sale_items'] ) ? 'yes' : 'no' ) );
	update_post_meta( $new_coupon_id, 'minimum_amount', '' );
	update_post_meta( $new_coupon_id, 'customer_email', array() );

	if ( class_exists( 'Dokan_Pro' ) ) {
		update_post_meta( $new_coupon_id, 'discount_type', 'percent' );

		$coupon = new WC_Coupon( $new_coupon_id );
		$coupon_options = array(
			'admin_coupons_enabled_for_vendor' => 'yes',
			'coupon_commissions_type'          => 'from_admin',
			'is_admin_coupon'                  => 'yes'
		);

		foreach ( $coupon_options as $option_key => $option_value ) {
			$coupon->update_meta_data( $option_key, $option_value );
		}

		$coupon->save();
	}



	wp_send_json_success( array(
		'balance'       => $balance,
		'coupon_code'   => $code,
	) );
	
}
add_action( 'wp_ajax_mycred_coupon_ajax', 'mycred_coupon_ajax' );
add_action( 'wp_ajax_nopriv_mycred_coupon_ajax', 'mycred_coupon_ajax' );