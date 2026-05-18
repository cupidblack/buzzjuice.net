<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Get mycred_get_custom_point_image_id
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_custom_point_image_id' ) ) {
	function mycred_get_custom_point_image_id ( $type_key ){
		$setting = mycred_get_option( 'mycred_pref_core_'.$type_key );
		$attachment_id = ! empty ( $setting['attachment_id']  ) ? $setting['attachment_id'] :'' ;
		return $attachment_id;
	}
}
/**
 * get_hooks_rewards_for_product for 3.0
 *
 * @since 3.0
 * @version 3.0
 */
if ( ! function_exists( 'get_hooks_rewards_for_product' ) ) {
	function get_hooks_rewards_for_product( $product_id, $mycred_reward_key ) {
		$hooks = mycred_get_option( 'mycred_pref_hooks', false );
		
		if ( is_product() ) {
			$product = wc_get_product( $product_id );
			$price_to_check = $product->get_price();
		} elseif ( is_cart() || is_checkout() ) {
			$price_to_check = WC()->cart->get_total( 'edit' );
		} else {
			$product = wc_get_product( $product_id );
			$price_to_check = $product->get_price();
		}

		if ( $mycred_reward_key != MYCRED_DEFAULT_TYPE_KEY ) { 
			$hooks = mycred_get_option( 'mycred_pref_hooks_' . sanitize_key( $mycred_reward_key ), false );
		}

		if ( ! isset( $hooks['active'] ) || ! is_array( $hooks['active'] ) ) {
			return null;
		}

		$active_hooks = array_intersect_key( $hooks['hook_prefs'], array_flip( $hooks['active'] ) );

		$amount = 0;

		if ( isset( $active_hooks['woocommerce_each_order']['creds'] ) ) {

			$reward_type = $active_hooks['woocommerce_each_order']['reward_on_each'];
			$reward_value = $active_hooks['woocommerce_each_order']['creds'];

			switch ( $reward_type ) {
				case 'fixed_rate':
            	// Fixed rate directly adds the reward_value
				$amount += $reward_value;
				break;

				case 'percentage':
            	// Percentage calculation (e.g., 10% of product price)
				$amount += ( $price_to_check * $reward_value / 100 );
				break;

				case 'exchange_rate':
            	// Exchange rate logic (assuming reward_value as multiplier)
				$amount += ( $price_to_check * $reward_value );
				break;

				default:
            	// Handle unknown reward types (optional)
				break;
			}
		}

		if ( isset( $active_hooks['woocommerce_first_order']['creds'] ) ) {

			$user_id = get_current_user_id(); 

			$woocommerce_setting = mycred_get_woocommerce_settings();
			$reward_setting      = $woocommerce_setting[ 'reward' ];

			$customer_orders = wc_get_orders( array(
				'customer_id' => $user_id,
				'status' => $reward_setting['status']
			));

			
			if ( ! $customer_orders ) {
				$amount += $active_hooks['woocommerce_first_order']['creds'];
			}

		}

		if ( isset( $active_hooks['woocommerce_numbers_of_orders'] ) ) {
			$user_id = get_current_user_id();
			$customer_orders = wc_get_orders( array(
				'customer_id' => $user_id,
				'status'      => 'wc-completed',
			));
			$current_total_orders = count( $customer_orders );
			$num_of_order_thresholds = $active_hooks['woocommerce_numbers_of_orders']['num_of_order'];
			$points_for_orders = $active_hooks['woocommerce_numbers_of_orders']['creds'];
			foreach ( $num_of_order_thresholds as $index => $threshold ) {
				if ( $current_total_orders + 1 == $threshold ) {
					$amount += $points_for_orders[ $index ];
					break;
				}
			}
		}

		if ( isset( $active_hooks['woocommerce_order_range'] ) ) {
			$min_ranges = $active_hooks['woocommerce_order_range']['min'];
			$max_ranges = $active_hooks['woocommerce_order_range']['max'];
			$points_for_ranges = $active_hooks['woocommerce_order_range']['creds'];
			foreach ( $min_ranges as $index => $min ) {
				$max = $max_ranges[ $index ];
				if ( $price_to_check  >= $min && $price_to_check  <= $max ) {
					$amount += $points_for_ranges[ $index ];
					break;
				}
			}
		}

		return $amount;
	}
}

/**
 * apply_backward_compatibility for 3.0
 *
 * @since 3.0
 * @version 3.0
 */
if ( ! function_exists( 'apply_backward_compatibility' ) ) {
	function apply_backward_compatibility() {
		$mycred_pref_woo = array();
		$mycred_wooplus_show_ranks = !empty(get_option('mycred_wooplus_show_ranks')) ? get_option('mycred_wooplus_show_ranks') : '';
		$mycred_partial_payment_switch = !empty(get_option('mycred_partial_payment_switch')) ? get_option('mycred_partial_payment_switch') : '';
		$mycred_wooplus_show_badges = !empty(get_option('mycred_wooplus_show_badges')) ? get_option('mycred_wooplus_show_badges') : '';
		$wooplus_ristrict_product = !empty(get_option('wooplus_ristrict_product')) ? get_option('wooplus_ristrict_product') : '';
		$wooplus_points_history = !empty(get_option('wooplus_points_history')) ? get_option('wooplus_points_history') : '';
		$reward_single_page_product = !empty(get_option('reward_single_page_product')) ? get_option('reward_single_page_product') : '';
		$reward_checkout_product_meta = !empty(get_option('reward_checkout_product_meta')) ? get_option('reward_checkout_product_meta') : '';
		$reward_checkout_product_total = !empty(get_option('reward_checkout_product_total')) ? get_option('reward_checkout_product_total') : '';
		$reward_cart_product_meta = !empty(get_option('reward_cart_product_meta')) ? get_option('reward_cart_product_meta') : '';
		$reward_cart_product_total = !empty(get_option('reward_cart_product_total')) ? get_option('reward_cart_product_total') : '';
		$mycred_wooplus_referral_cookie_name = !empty(get_option('mycred_wooplus_referral_cookie_name')) ? get_option('mycred_wooplus_referral_cookie_name') : '';
		$mycred_wooplus_referral_cookie_is_expire = !empty(get_option('mycred_wooplus_referral_cookie_is_expire')) ? get_option('mycred_wooplus_referral_cookie_is_expire') : '';
		$mycred_wooplus_referral_cookie_expiration = !empty(get_option('mycred_wooplus_referral_cookie_expiration')) ? get_option('mycred_wooplus_referral_cookie_expiration') : '';
		$mycred_partial_payments_woo = !empty(get_option('mycred_partial_payments_woo')) ? get_option('mycred_partial_payments_woo') : array();
		$mycred_wooplus_tab_on = !empty(get_option('mycred_wooplus_tab_on')) ? get_option('mycred_wooplus_tab_on') : '';
		$mycred_myaccount_tab_name = !empty(get_option('mycred_myaccount_tab_name')) ? get_option('mycred_myaccount_tab_name') : '';
		$mycred_wooplus_show_earned_badges = !empty(get_option('mycred_wooplus_show_earned_badges')) ? get_option('mycred_wooplus_show_earned_badges') : '';
		$mycred_wooplus_show_earned_ranks = !empty(get_option('mycred_wooplus_show_earned_ranks')) ? get_option('mycred_wooplus_show_earned_ranks') : '';
		$mycred_wooplus_show_my_balance = !empty(get_option('mycred_wooplus_show_my_balance')) ? get_option('mycred_wooplus_show_my_balance') : '';
		$mycred_wooplus_show_my_level = !empty(get_option('mycred_wooplus_show_my_level')) ? get_option('mycred_wooplus_show_my_level') : '';


		$mycred_pref_woo['mwp_my_account']['enable'] = ($mycred_wooplus_tab_on === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['tab_label'] = $mycred_myaccount_tab_name;
		$mycred_pref_woo['mwp_my_account']['badges'] = ($mycred_wooplus_show_earned_badges === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['ranks'] = ($mycred_wooplus_show_earned_ranks === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['balances'] = ($mycred_wooplus_show_my_balance === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['level'] = ($mycred_wooplus_show_my_level === 'yes') ? 1 : 0;
		
		$mycred_pref_woo['mwp_product_referral']['ref_cookie_name'] = $mycred_wooplus_referral_cookie_name;
		$mycred_pref_woo['mwp_product_referral']['cookie_expiration_days'] = $mycred_wooplus_referral_cookie_expiration;
		$mycred_pref_woo['mwp_product_referral']['can_cookie_expire'] = ($mycred_wooplus_referral_cookie_is_expire === 'yes') ? 1 : 0;

		$mycred_pref_woo['mwp_coupons']['module_enable'] = $mycred_partial_payment_switch == 'enable_coupons' ? 1 : 0  ;
		$mycred_pref_woo['mwp_coupons']['badge'] = ($mycred_wooplus_show_badges === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_coupons']['rank'] = ($mycred_wooplus_show_ranks === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_coupons']['point_type'] = !empty($mycred_partial_payments_woo['point_type']) ? $mycred_partial_payments_woo['point_type'] : '';
		$mycred_pref_woo['mwp_coupons']['min'] = !empty($mycred_partial_payments_woo['min']) ? $mycred_partial_payments_woo['min'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_max'] = !empty($mycred_partial_payments_woo['coupon_max']) ? $mycred_partial_payments_woo['coupon_max'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_title'] = !empty($mycred_partial_payments_woo['coupon_title']) ? $mycred_partial_payments_woo['coupon_title'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_desc'] = !empty($mycred_partial_payments_woo['coupon_desc']) ? $mycred_partial_payments_woo['coupon_desc'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_button'] = !empty($mycred_partial_payments_woo['coupon_button']) ? $mycred_partial_payments_woo['coupon_button'] : '';
		
		$mycred_pref_woo['mwp_restrict_products']['enable'] = ($wooplus_ristrict_product === 'yes') ? 1 : 0;
		
		$mycred_pref_woo['mwp_points_history']['point_history']['types']['mycred_default'] = ($wooplus_points_history === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['single_product'] = ($reward_single_page_product === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['checkout_product_meta'] = ($reward_checkout_product_meta === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['checkout_product_total'] = ($reward_checkout_product_total === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['cart_product_meta'] = ($reward_cart_product_meta === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['cart_product_total'] = ($reward_cart_product_total === 'yes') ? 1 : 0;

		$mycred_pref_woo['mwp_partial_payments']['enable'] = $mycred_partial_payment_switch === 'enable_partial_payment' ? 1 : 0  ;
		$mycred_pref_woo['mwp_partial_payments']['change_position'] = !empty($mycred_partial_payments_woo['change_position']) ? $mycred_partial_payments_woo['change_position'] : 'cart';
		$mycred_pref_woo['mwp_partial_payments']['position'] = !empty($mycred_partial_payments_woo['position']) ? $mycred_partial_payments_woo['position'] : 'after';
		$mycred_pref_woo['mwp_partial_payments']['multiple'] = !empty($mycred_partial_payments_woo['multiple']) ? $mycred_partial_payments_woo['multiple'] : 'no';
		$mycred_pref_woo['mwp_partial_payments']['undo'] = !empty($mycred_partial_payments_woo['undo']) ? $mycred_partial_payments_woo['undo'] : 'no' ;
		$mycred_pref_woo['mwp_partial_payments']['sale_items'] = !empty($mycred_partial_payments_woo['sale_items']) ? $mycred_partial_payments_woo['sale_items'] : 'no' ;
		$mycred_pref_woo['mwp_partial_payments']['title'] = !empty($mycred_partial_payments_woo['title']) ? $mycred_partial_payments_woo['title'] : 'Partial Payment' ;
		$mycred_pref_woo['mwp_partial_payments']['button'] = !empty($mycred_partial_payments_woo['button']) ? $mycred_partial_payments_woo['button'] : 'Apply Discount' ;
		$mycred_pref_woo['mwp_partial_payments']['step'] = !empty($mycred_partial_payments_woo['step']) ? $mycred_partial_payments_woo['step'] : 1 ;
		$mycred_pref_woo['mwp_partial_payments']['free_shipping'] = !empty($mycred_partial_payments_woo['free_shipping']) ? $mycred_partial_payments_woo['free_shipping'] : 'no' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_total'] = !empty($mycred_partial_payments_woo['checkout_total']) ? $mycred_partial_payments_woo['checkout_total'] : 'cart' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_total_label'] = !empty($mycred_partial_payments_woo['checkout_total_label']) ? $mycred_partial_payments_woo['checkout_total_label'] : 'Point Cost' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_balance'] = !empty($mycred_partial_payments_woo['checkout_balance']) ? $mycred_partial_payments_woo['checkout_balance'] : 'cart' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_balance_label'] = !empty($mycred_partial_payments_woo['checkout_balance_label']) ? $mycred_partial_payments_woo['checkout_balance_label'] : 'Your Balance' ;
		$mycred_pref_woo['mwp_partial_payments']['selecttype'] = !empty($mycred_partial_payments_woo['selecttype']) ? $mycred_partial_payments_woo['selecttype'] : 'input' ;
		$mycred_pref_woo['mwp_partial_payments']['desc'] = !empty($mycred_partial_payments_woo['desc']) ? $mycred_partial_payments_woo['desc'] : '' ;

		
		update_option('mycred_pref_woo', $mycred_pref_woo );

		$mycred_options = get_option('mycred_pref_core', []);

		// Update min, max, and exchange rate values
		$mycred_options['partial_payment_settings']['mwp_min'] = ! empty( $mycred_partial_payments_woo['min']) ? $mycred_partial_payments_woo['min'] : 1;
		$mycred_options['partial_payment_settings']['mwp_max'] = ! empty( $mycred_partial_payments_woo['max']) ? $mycred_partial_payments_woo['max'] : 100;
		$mycred_options['partial_payment_settings']['exchange_rate'] = isset($mycred_partial_payments_woo['exchange']) ? $mycred_partial_payments_woo['exchange'] : 1;

		// Save the updated options back to the database
		update_option('mycred_pref_core', $mycred_options);
	}
}

/**
 * Get Incomplete Payment
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_users_incomplete_partial_payment' ) ) {
	function mycred_get_users_incomplete_partial_payment( $user_id = null ) {

		global $wpdb, $mycred;

		$table_name = $mycred->log_table; // Ensure this is properly sanitized if coming from user input
		$payment = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT * FROM {$table_name} WHERE ref = 'partial_payment' AND ref_id != 0 AND user_id = %d AND data = '' ORDER BY time DESC LIMIT 1;", 
				$user_id 
			) 
		);
		
		if ( ! isset( $payment->user_id ) ) {
			$payment = false;
		}

		return $payment;
	}
}

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
 * Partial Payment Possible?
 * Checks if partial payment is possible for a cart
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_partial_payment_possible' ) ) {
	function mycred_partial_payment_possible() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$options = get_option('mycred_pref_woo');
		$mycred_partial_payment = $options['mwp_partial_payments'];
		$user_id = get_current_user_id();

		/**
		* Filter mycred_woo_partial_payment
		* 
		* @since 1.0
		**/
		$possible = apply_filters( 'mycred_woo_partial_payment', true );
		if ( false === $possible ) {
			return false;
		}

		$total = WC()->cart->total;

		// If points cannot be used to pay for shipping
		if ( 'no' == ( ! empty( $mycred_partial_payment['free_shipping'] ) ? $mycred_partial_payment['free_shipping'] : 'no' ) ) {
			$total -= WC()->cart->shipping_total;
		}

		$pointtypes = mycred_get_types( true );
		$balance_check = false;

		foreach ( $pointtypes as $type_key => $type_label ) {
			$mycred = mycred( $type_key );

			// Check if the user is excluded for this point type
			if ( $mycred->exclude_user( $user_id ) ) {
				return false;
			}

			$balance = $mycred->get_users_balance( $user_id, $type_key );
			$min = ( 1 > 0 ) ? 1 : $mycred->get_lowest_value();

			/**
			* Filter mycred_partial_payment_selected_point_balance
			* 
			* @since 1.0
			**/
			$balance = apply_filters( 'mycred_partial_payment_selected_point_balance', $balance, $user_id, $type_key, $min );

			if ( $total > 0 && $balance >= $min ) {
				$balance_check = true;
				break; // Exit the loop if a valid balance is found
			}
		}

		if ( ! $balance_check ) {
			return false;
		}

		$coupons = WC()->cart->get_coupons();
		if ( 'no' === $mycred_partial_payment['multiple'] && ! empty( $coupons ) ) {
			$possible = true;
			foreach ( WC()->cart->applied_coupons as $code ) {
				$coupon = new WC_Coupon( $code );
				$partial_payment = mycred_get_partial_payment( $coupon->get_id() );
				if ( isset( $partial_payment->user_id ) ) {
					$possible = false;
				}
			}
			return $possible;
		}

		return true;
	}
}


/**
 * Get Partial Payment
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_partial_payment' ) ) {
	function mycred_get_partial_payment( $code = null ) {

		global $wpdb, $mycred;

		$table_name = $mycred->log_table; // Ensure this is properly sanitized if needed
		$payment = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT * FROM {$table_name} WHERE ref IN ('partial_payment', 'points_to_coupon') AND ref_id = %d;", 
				$code 
			) 
		);
		if ( ! isset( $payment->user_id ) ) {
			$payment = false;
		}

		return $payment;
	}
}

/**
 * Parial Payment
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_get_total' ) ) {
	function mycred_part_woo_get_total( $mycred_partial_payment, $order_id = null ) {


		$cart  = WC()->cart;
		$total = $cart->total;
		$subtotal = WC()->cart->get_subtotal();

		$free_shipping = ! empty ( $mycred_partial_payment['free_shipping'] ) ? $mycred_partial_payment['free_shipping'] : 'no'; 
		// If points can not be used to pay for shipping
		if ( 'no' == $free_shipping ) {
			$total -= $cart->shipping_total;
		}

		/**
		* Filter mycred_woo_partial_payment_total
		* 
		* @since 1.0
		**/
		return apply_filters( 'mycred_woo_partial_payment_total', $total, $cart );
	}
}
if( ! function_exists( 'mwp_coupon_status' ) ) :
	function mwp_coupon_status( $copoun_id ) {

		$status       = __( 'Expired', 'mycred-woocommerce-plus' );
		$usage_limit  = get_post_meta( $copoun_id, 'usage_limit', true );
		$usage_count  = get_post_meta( $copoun_id, 'usage_count', true );
		$date_expires = get_post_meta( $copoun_id, 'date_expires', true );
		$date_expires = gmdate( 'd/m/Y', ( ! empty( $date_expires ) ? $date_expires : strtotime( '+1 day' ) ) );

		if ( $usage_count < $usage_limit && $date_expires > gmdate( 'd/m/Y' ) ) {

			$status = __( 'Available', 'mycred-woocommerce-plus' );

		} 
		elseif ( $date_expires > gmdate( 'd/m/Y' ) ) {

			$status = __( 'Used', 'mycred-woocommerce-plus' );

		} 

		return $status;
	}
endif;