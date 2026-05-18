<?php
// No dirrect access
if ( ! defined( 'MYCRED_WOOPLUS_VERSION' ) ) {
	exit;
}

/**
 * Get Plugin Settings
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_settings' ) ) {
	function mycred_part_woo_settings() {

		global $mycred_partial_payment;

		if ( ! is_array( $mycred_partial_payment ) ) {

			$default = array(
				'change_position'        => 'cart',
				'position'               => 'after',
				'multiple'               => 'yes',
				'title'                  => __( 'Partial Payment', 'mycredpartwoo' ),
				'desc'                   => __( 'Pay parts of your order using your points.', 'mycredpartwoo' ),
				'button'                 => __( 'Apply Discount', 'mycredpartwoo' ),
				'min'                    => 1,
				'max'                    => 100,
				'coupon_max'             => 100,
				'step'                   => 1,
				'default'                => 0,
				'point_type'             => MYCRED_DEFAULT_TYPE_KEY,
				'exchange'               => 1,
				'log'                    => __( '%plural% for partial payment.', 'mycredpartwoo' ),
				'log_refund'             => __( '%plural% for partial payment refund.', 'mycredpartwoo' ),
				/* translators: %s: refunded */
				'refund_message'         => __( 'Your partial payment of %cred_f% was refunded to your account.', 'mycredpartwoo' ),
				'undo'                   => 'yes',
				'rewards'                => 1,
				'before_tax'             => 'yes',
				'free_shipping'          => 'no',
				'sale_items'             => 'no',
				'selecttype'             => 'input',
				'checkout_total'         => 'no',
				'checkout_total_label'   => __( 'Point Cost', 'mycredpartwoo' ),
				'checkout_balance'       => 'no',
				'checkout_balance_label' => __( 'Your Balance', 'mycredpartwoo' ),
				'coupon_title'           => __( 'Coupon', 'mycredpartwoo' ),
				'coupon_desc'            => __( 'Description', 'mycredpartwoo' ),
				'coupon_button'          => __( 'Create Coupon', 'mycredpartwoo' ),
			);
			$saved   = get_option( 'mycred_partial_payments_woo', $default );

			/**
			* Filter mycred_woo_partial_settings
			* 
			* @since 1.0
			**/
			return apply_filters('mycred_woo_partial_settings', shortcode_atts( $default, $saved ), $default, $saved, $mycred_partial_payment);

		}

		return $mycred_partial_payment;

	}
}

if ( ! function_exists( 'mycred_part_woo_field_type_separator' ) ) {
	function mycred_part_woo_field_type_separator() {
		echo '<tr valign="top"><th scope="row" class="titledesc"></th><td class="formsep"><hr /></td></tr>';
	}
}
add_action( 'woocommerce_admin_field_separator', 'mycred_part_woo_field_type_separator' );

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

		$mycred_partial_payment = mycred_part_woo_settings();

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

		// Fiscal check
		$mycred = mycred( $mycred_partial_payment['point_type'] );
		if ( $mycred->exclude_user( $user_id ) ) {
			return false;
		}

		$total = WC()->cart->total;

		// If points can not be used to pay for taxes
		if ( 'no' == $mycred_partial_payment['before_tax'] ) {

			$taxes = WC()->cart->get_tax_totals();
			if ( ! empty( $taxes ) ) {
				foreach ( $taxes as $code => $tax ) {
					$total -= $tax->amount;
				}
			}
		}

		// If points can not be used to pay for shipping
		if ( 'no' == $mycred_partial_payment['free_shipping'] ) {
			$total -= WC()->cart->shipping_total;
		}

		$balance = $mycred->get_users_balance( $user_id, $mycred_partial_payment['point_type'] );
		$min     = ( ( $mycred_partial_payment['min'] > 0 ) ? $mycred_partial_payment['min'] : $mycred->get_lowest_value() );
		/**
		* Filter mycred_partial_payment_selected_point_balance
		* 
		* @since 1.0
		**/
		$balance = apply_filters( 'mycred_partial_payment_selected_point_balance', $balance, $user_id, $mycred_partial_payment['point_type'], $min );

		if ( $total > 0 && $balance < $min ) {
			return false;
		}

		$coupons = WC()->cart->get_coupons();
		if ( 'no' === $mycred_partial_payment['multiple'] && ! empty( $coupons ) ) {

			$possible = true;
			foreach ( WC()->cart->applied_coupons as $code ) {

				$coupon          = new WC_Coupon( $code );
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

		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %1s WHERE ref in ('partial_payment' , 'points_to_coupon') AND ref_id = %d;", $mycred->log_table, $code ) );
		if ( ! isset( $payment->user_id ) ) {
			$payment = false;
		}

		return $payment;
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

		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %1s WHERE ref = 'partial_payment' AND ref_id != 0 AND user_id = %d AND data = '' ORDER BY time DESC LIMIT 1;", $mycred->log_table, $user_id ) );
		
		if ( ! isset( $payment->user_id ) ) {
			$payment = false;
		}

		return $payment;
	}
}

/**
 * Delete Parial Payment
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_delete_partial_payment' ) ) {
	function mycred_delete_partial_payment( $payment_id = null ) {

		global $wpdb, $mycred;

		$wpdb->delete(
			$mycred->log_table,
			array( 'id' => $payment_id ),
			array( '%d' )
		);
	}
}

/**
 * Delete Parial Payment
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_get_total' ) ) {
	function mycred_part_woo_get_total( $order_id = null ) {

		global $mycred_partial_payment;

		$cart  = WC()->cart;
		$total = $cart->total;
		$subtotal = WC()->cart->get_subtotal();

		// If points can not be used to pay for taxes
		if ( 'no' == $mycred_partial_payment['before_tax'] ) {

			$taxes = $cart->get_tax_totals();
			if ( ! empty( $taxes ) ) {
				foreach ( $taxes as $code => $tax ) {
					$total -= $tax->amount;
				}
			}
		}

		// If points can not be used to pay for shipping
		if ( 'no' == $mycred_partial_payment['free_shipping'] ) {
			$total -= $cart->shipping_total;
		}

		// if ('yes' == $mycred_partial_payment['free_shipping']) {
		//     $total = $subtotal;
		// }

		/**
		* Filter mycred_woo_partial_payment_total
		* 
		* @since 1.0
		**/
		return apply_filters( 'mycred_woo_partial_payment_total', $total, $cart );
	}
}


if ( ! function_exists( 'mycred_partial_payment_content' ) ) {
	function mycred_partial_payment_content( $fragments ) {

		$mycred_partial_payment = mycred_part_woo_settings();
		$toggle_module			= get_option( 'mycred_partial_payment_switch' );
		$user_id 				= get_current_user_id();
		$type					= $mycred_partial_payment['point_type'];
		$mycred  				= mycred( $type );

		if ( $mycred->exclude_user( $user_id ) ) {
			return;
		}

		$balance			= $mycred->get_users_balance( $user_id );
		$change_position	= $mycred_partial_payment['change_position'];
		$min				= isset( $mycred_partial_payment['min'] ) && ! empty( $mycred_partial_payment['min'] ) ? $mycred_partial_payment['min'] : 0;
		
		if ( ( 'both' == $change_position ) || ( 'cart' == $change_position && is_cart() ) || ( 'checkout' == $change_position && is_checkout() ) ) {

			if ( 'enable_coupons' == $toggle_module ) {

				ob_start();
				?>
				<p class="mycred-user-balance">
				<?php 
				/* translators: %s: user balance */
				printf( esc_html__( 'Your current balance is: %s', 'mycredpartwoo' ), esc_html__( $mycred->format_creds( $balance ) ), 'mycredpartwoo' ); 
				?>
				</p>
				<?php
				$fragments['p.mycred-user-balance'] = ob_get_clean();

			} else if ( 'enable_partial_payment' == $toggle_module ) {

				if ( ! mycred_partial_payment_possible() ) {
					return;
				}

				ob_start();

				include_once MYCRED_WOOPLUS_TEMPLATES_DIR . 'mycred-partial-payments.php';

				$fragments['div#mycred-partial-payment-woo'] = ob_get_clean();
			}
		}

		return $fragments;
	}
}
add_filter( 'woocommerce_add_to_cart_fragments', 'mycred_partial_payment_content' );
