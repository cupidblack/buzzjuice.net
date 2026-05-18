<?php
// No dirrect access
if ( ! defined( 'MYCRED_WOOPLUS_VERSION' ) ) {
	exit;
}

/**
 * Before Order Review
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_before_order_review' ) ) {
	function mycred_part_woo_before_order_review() {

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
		$checkout_position	= $mycred_partial_payment['position'];
		$min				= isset( $mycred_partial_payment['min'] ) && ! empty( $mycred_partial_payment['min'] ) ? $mycred_partial_payment['min'] : 0;
		
		if ( ( 'both' == $change_position ) || ( 'cart' == $change_position && is_cart() ) || ( 'checkout' == $change_position && is_checkout() ) ) {

			if ( 'enable_partial_payment' == $toggle_module && 'before' === $checkout_position ) {

				if ( ! mycred_partial_payment_possible() ) {
					return;
				}
				include_once MYCRED_WOOPLUS_TEMPLATES_DIR . 'mycred-partial-payments.php';
			}
		}
	}
}
add_action( 'woocommerce_checkout_order_review', 'mycred_part_woo_before_order_review', 9 );

/**
 * After Order Review
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_after_order_review' ) ) {
	function mycred_part_woo_after_order_review() {

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
		$checkout_position	= $mycred_partial_payment['position'];
		$min				= isset( $mycred_partial_payment['min'] ) && ! empty( $mycred_partial_payment['min'] ) ? $mycred_partial_payment['min'] : 0;
		
		if ( ( 'both' == $change_position ) || ( 'cart' == $change_position && is_cart() ) || ( 'checkout' == $change_position && is_checkout() ) ) {

			if ( 'enable_partial_payment' == $toggle_module && 'after' === $checkout_position ) {

				if ( ! mycred_partial_payment_possible() ) {
					return;
				}
				include_once MYCRED_WOOPLUS_TEMPLATES_DIR . 'mycred-partial-payments.php';
			}
		}
	}
}
add_action( 'woocommerce_review_order_before_payment', 'mycred_part_woo_after_order_review', 99 );

/**
 * Insert Total Cost
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_total_cost' ) ) {
	function mycred_part_woo_insert_total_cost() {

		global $mycred_partial_payment;

		$mycred = mycred( $mycred_partial_payment['point_type'] );

		$show_total = $mycred_partial_payment['checkout_total'];

		if ( ( 'both' == $show_total )
			|| ( 'cart' == $show_total && is_cart() )
			|| ( 'checkout' == $show_total && is_checkout() )
		) {

		$the_cart       = WC()->cart;
		$the_cart_total = $the_cart->total;
		$balance        = ( is_user_logged_in() ) ? $mycred->get_users_balance( get_current_user_id() ) : 0;

		$cost = $mycred->number( $the_cart_total );
		if ( 1 != $mycred_partial_payment['exchange'] ) {
			$cost = $mycred->number( ( $the_cart_total / $mycred_partial_payment['exchange'] ) );

				/**
				* Filter mycred_woo_order_cost
				* 
				* @since 1.0
				**/
				$cost = apply_filters( 'mycred_woo_order_cost', $cost, $the_cart, true, $mycred );
			}

			if ( 'yes' == $mycred_partial_payment['free_shipping'] ) {
				$payment_made = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
				$amount_paid  = 0;
				if ( count( WC()->cart->get_applied_coupons() ) > 1 ) {

					foreach ( WC()->cart->get_applied_coupons() as $code ) {
						$coupon       = new WC_Coupon( $code );
						$amount_paid += $coupon->amount;
					}

				} else {
					
					$amount_paid = 0;

					if ( ! empty( $payment_made->creds ) ) {
						$amount_paid = abs( (float) $payment_made->creds );
					}

				}
				// if ( false != $payment_made && $amount_paid >= ( (float) $the_cart->subtotal + $the_cart->shipping_total ) ) {
				// 	$cost = $amount_paid - ( $the_cart->subtotal + $the_cart->shipping_total );
				// }

				// if ( false != $payment_made && $amount_paid <= ( (float) $the_cart->subtotal + $the_cart->shipping_total ) ) {
				// 	$cost = ( $the_cart->subtotal + $the_cart->shipping_total ) - $amount_paid;
				// }
			}

			?>
			<tr class="total">
				<th><strong><?php echo wp_kses_post( mycred_wcp_kses_html( $mycred->template_tags_general( $mycred_partial_payment['checkout_total_label'] ) ) ); ?></strong></th>
				<td>
					<div class="current-balance order-total-in-points">
						<strong class="
						<?php
						if ( $balance < $cost ) {
							echo 'mycred-low-funds';
						} else {
							echo 'mycred-funds';
						}
						?>
						"
						<?php
						if ( $balance < $cost ) {
							echo ' style="color:red;"';
						}
						?>
						><?php echo wp_kses_post( mycred_wcp_kses_html($mycred->format_creds( $cost ) ) ); ?></strong> 
					</div>
				</td>
			</tr>
			<?php

		}

	}
}
add_action( 'woocommerce_review_order_after_order_total', 'mycred_part_woo_insert_total_cost', 40 );
add_action( 'woocommerce_cart_totals_after_order_total', 'mycred_part_woo_insert_total_cost', 40 );


/**
 * Insert shipping cost in order view and cart if paid
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_shipping_price' ) ) {
	function mycred_part_woo_insert_shipping_price() {

		global $mycred_partial_payment;

		$mycred = mycred( $mycred_partial_payment['point_type'] );

		$show_total = $mycred_partial_payment['checkout_total'];

		if ( ( 'both' == $show_total )
			|| ( 'cart' == $show_total && is_cart() )
			|| ( 'checkout' == $show_total && is_checkout() )
		) {

			$the_cart = WC()->cart;

		if ( 'yes' == $mycred_partial_payment['free_shipping'] ) {

			$payment_made  = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
			$shipping_cost = $the_cart->shipping_total;
			$amount_paid   = 0;

			if ( count( WC()->cart->get_applied_coupons() ) > 1 ) {
				
				foreach ( WC()->cart->get_applied_coupons() as $code ) {

					$coupon       = new WC_Coupon( $code );
					$amount_paid += $coupon->amount;
					
				}
				
			} else {

				$amount_paid = 0;

				if ( ! empty( $payment_made->creds ) ) {
					$amount_paid = abs( (float) $payment_made->creds );
				}

			}

			if ( false != $payment_made && $amount_paid >= (float) $the_cart->subtotal ) {

				$amount_left = $amount_paid - ( $the_cart->subtotal + $the_cart->shipping_total );

				if ( 0 == $amount_left ) {
					$shipping_cost = 0 - $the_cart->shipping_total;
				} else {
					$shipping_cost = abs( $amount_left ) - $the_cart->shipping_total;
				}
				
			}

			if ( $shipping_cost > 0 ) {
				return false;
			}
			?>
			<tr class="total">
				<th><strong><?php echo esc_html__( 'Shipping Payment Paid', 'mycredpartwoo' ); ?></strong></th>
				<td>
					<div class="current-balance order-total-in-points">
						<strong ><?php echo esc_attr($shipping_cost); ?></strong> 
					</div>
				</td>
			</tr>
			<?php

		}
	}

}
}

add_action( 'woocommerce_review_order_before_order_total', 'mycred_part_woo_insert_shipping_price', 40 );
add_action( 'woocommerce_cart_totals_before_order_total', 'mycred_part_woo_insert_shipping_price', 40 );


/**
 * Insert Total Cost
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_total_order_cost' ) ) {
	function mycred_part_woo_insert_total_order_cost() {

		global $mycred_partial_payment;

		$mycred = mycred( $mycred_partial_payment['point_type'] );

		$show_total = $mycred_partial_payment['checkout_total'];

		if ( ( 'both' == $show_total )
			|| ( 'cart' == $show_total && is_cart() )
			|| ( 'checkout' == $show_total && is_checkout() )
		) {

			$the_cart       = WC()->cart;
		$the_cart_total = $the_cart->total;

		$balance = ( is_user_logged_in() ) ? $mycred->get_users_balance( get_current_user_id() ) : 0;

		$cost = $mycred->number( $the_cart_total );

		if ( 1 != $mycred_partial_payment['exchange'] ) {
			$cost = $mycred->number( ( $the_cart_total / $mycred_partial_payment['exchange'] ) );

				/**
				* Filter mycred_woo_order_cost
				* 
				* @since 1.0
				**/
				$cost = apply_filters( 'mycred_woo_order_cost', $cost, $the_cart, true, $mycred );
			}
			
			if ( 'yes' == $mycred_partial_payment['free_shipping'] ) {
				$payment_made = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
				$amount_paid  = 0;
				if ( count( WC()->cart->get_applied_coupons() ) > 1 ) {

					foreach ( WC()->cart->get_applied_coupons() as $code ) {
						$coupon       = new WC_Coupon( $code );
						$amount_paid += $coupon->amount;
					}

				} else {
					
					$amount_paid = 0;

					if ( ! empty( $payment_made->creds ) ) {
						$amount_paid = abs( (float) $payment_made->creds );
					}

				}

				if ( false != $payment_made && $amount_paid >= ( (int) $the_cart->subtotal + $the_cart->shipping_total ) ) {
					$cost = $amount_paid - ( $the_cart->subtotal + $the_cart->shipping_total );
				} else {
					$cost = ( $the_cart->subtotal + $the_cart->shipping_total ) - $amount_paid;
				}
			}
			$installed_payment_methods = WC()->payment_gateways->payment_gateways();
			if ( isset( $installed_payment_methods['mycred']->enabled ) && 'yes' == $installed_payment_methods['mycred']->enabled ) {

				?>
				<style type="text/css">
					.order-total {
						display: none;
					}
				</style>
				<tr class="total">
					<th><strong><?php echo esc_html__( 'Total', 'mycredpartwoo' ); ?></strong></th>
					<td>
						<div class="current-balance order-total-in-points">
							<strong ><?php echo wp_kses_post( mycred_wcp_kses_html($mycred->format_creds( $cost ) ) ); ?></strong> 
						</div>
					</td>
				</tr>
				<?php
			}
		}
	}
}
// add_action( 'woocommerce_review_order_before_order_total', 'mycred_part_woo_insert_total_order_cost', 40 );
// add_action( 'woocommerce_cart_totals_before_order_total', 'mycred_part_woo_insert_total_order_cost', 40 );

/**
 * Insert Total Balance
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_insert_total_balance' ) ) {
	function mycred_part_woo_insert_total_balance() {

		if ( ! is_user_logged_in() ) {
			return;
		}

		global $mycred_partial_payment;

		$user_id = get_current_user_id();
		$mycred  = mycred( $mycred_partial_payment['point_type'] );

		if ( $mycred->exclude_user( $user_id ) ) {
			return;
		}

		$show_balance = $mycred_partial_payment['checkout_balance'];

		if ( ( 'both' == $show_balance )
			|| ( 'cart' == $show_balance && is_cart() )
			|| ( 'checkout' == $show_balance && is_checkout() )
		) {

			$balance = $mycred->get_users_balance( $user_id );

			?>
			<tr class="total">
				<th><?php echo wp_kses_post( mycred_wcp_kses_html($mycred->template_tags_general( $mycred_partial_payment['checkout_balance_label'] ) ) ); ?></th>
				<td>
					<div class="current-balance order-total-in-points">
						<?php echo wp_kses_post( mycred_wcp_kses_html($mycred->format_creds( $balance ) ) ); ?> 
					</div>
				</td>
			</tr>
			<?php

		}

	}
}
add_action( 'woocommerce_review_order_after_order_total', 'mycred_part_woo_insert_total_balance', 50 );
add_action( 'woocommerce_cart_totals_after_order_total', 'mycred_part_woo_insert_total_balance', 50 );

/**
 * WooCommerce Coupon Label
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_coupon_label' ) ) {
	function mycred_part_woo_coupon_label( $label, $coupon ) {

		global $mycred_partial_payment;

		$partial_payment = mycred_get_partial_payment( $coupon->get_id() );

		if ( isset( $partial_payment->user_id ) ) {

			$mycred = mycred( $mycred_partial_payment['point_type'] );
			/* translators: %singular% for payment*/
			$label  = $mycred->template_tags_general( _x( '%singular% Payment', 'Discount applied to cart label', 'mycredpartwoo' ) );

		}

		return $label;

	}
}
add_action( 'woocommerce_cart_totals_coupon_label', 'mycred_part_woo_coupon_label', 10, 2 );

/**
 * Remove Coupon Option
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_remove_coupon_option' ) ) {
	function mycred_part_woo_remove_coupon_option( $html, $coupon ) {

		

		global $mycred_partial_payment;

		$partial_payment = mycred_get_partial_payment( $coupon->get_id() );
		if ( isset( $partial_payment->user_id ) && 'no' === $mycred_partial_payment['undo'] ) {

			// Mimic what WooCommerce does but without the removal link
			$value = array();
			$amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax );
			if ( !empty($amount) ) {
				$discount_html = '-' . wc_price( $amount );
			} else {
				$discount_html = '';
			}

			/**
			* Filter woocommerce_coupon_discount_amount_html
			* 
			* @since 1.0
			**/
			$value[] = apply_filters( 'woocommerce_coupon_discount_amount_html', $discount_html, $coupon );

			if ( $coupon->get_free_shipping() ) {
				$value[] = __( 'Free shipping coupon', 'mycredpartwoo' );
			}

			// get rid of empty array elements
			$value = array_filter( $value );
			$html  = implode( ', ', $value );

		}
		
		return $html;

	}
}
add_filter( 'woocommerce_cart_totals_coupon_html', 'mycred_part_woo_remove_coupon_option', 10, 2 );

/**
 * Remove Coupon Action
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_remove_coupon_action' ) ) {
	function mycred_part_woo_remove_coupon_action( $coupon = '' ) {

		$settings = mycred_part_woo_settings();
		if ( 'yes' != $settings['undo'] ) {
			return;
		}

		// $coupon = get_page_by_title( $coupon, OBJECT, 'shop_coupon' );
		$coupon = new WP_Query(
			array(
				'post_type'	=> 'shop_coupon',
				'title'		=> $coupon,
			)
		);

		if ( null === $coupon->post ) {
			return;
		}

		$coupon_post_id = $coupon->post->ID;

		$coupon_object 	= new WC_Coupon( $coupon_post_id );
		
		$coupon_usage 	= $coupon_object->get_usage_count('edit');		
		
		if ($coupon_usage > 0) {
			return;
		}

		global $wpdb, $mycred_partial_payment;

		$partial_payment = mycred_get_partial_payment( $coupon_post_id );
		if ( false !== $partial_payment ) {

			$mycred = mycred( $partial_payment->ctype );

			// Refund payment
			$mycred->add_creds(
				'partial_payment_refund',
				$partial_payment->user_id,
				abs( $partial_payment->creds ),
				$settings['log_refund'],
				$partial_payment->ref_id,
				'',
				$partial_payment->ctype
			);

			// Update partial payment in log to prevent re-use
			$wpdb->update(
				$mycred->log_table,
				array( 'ref_id' => 0 ),
				array( 'id' => $partial_payment->id ),
				array( '%d' ),
				array( '%d' )
			);

			// Trash coupon post object
			wp_trash_post( $coupon_post_id );
			// DEBUG _doing_it_wrong( 'mycred_part_woo_remove_coupon_action', 'Removed partial payment since coupon was removed.', '1.0' );

			$multiple_payment_restricted = false;
		}

	}
}
add_action( 'woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action' );

/**
 * Remove Cart Items
 *
 * @since   1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_part_woo_remove_cart_items' ) ) {
	function mycred_part_woo_remove_cart_items( $cart_item_key, $cart ) {

		if ( 0 == $cart->get_cart_contents_count() && is_user_logged_in() ) {

			$settings = mycred_part_woo_settings();
			if ( 'yes' != $settings['undo'] ) {
				return;
			}

			$partial_payment = mycred_get_users_incomplete_partial_payment( get_current_user_id() );
			
			if ( false !== $partial_payment ) {

				$mycred = mycred( $partial_payment->ctype );

				// Refund payment
				$mycred->add_creds(
					'partial_payment_refund',
					$partial_payment->user_id,
					abs( $partial_payment->creds ),
					$settings['log_refund'],
					0,
					$partial_payment->ref_id,
					$partial_payment->ctype
				);

				global $wpdb;

				// Update partial payment in log to prevent re-use
				$wpdb->update(
					$mycred->log_table,
					array( 'ref_id' => 0 ),
					array( 'id' => $partial_payment->id ),
					array( '%d' ),
					array( '%d' )
				);

				// Remove coupon
				// Prevent our own function from running
				remove_action( 'woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action' );

				$cart->remove_coupon( get_the_title( $partial_payment->ref_id ) );

				add_action( 'woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action' );

				// Trash coupon post object
				wp_trash_post( $partial_payment->ref_id );

				// DEBUG _doing_it_wrong( 'mycred_part_woo_remove_cart_items', 'Removed partial payment since cart is now empty.', '1.0' );

				if ( '' != $settings['refund_message'] ) {

					$message = $mycred->template_tags_amount( $settings['refund_message'], abs( $partial_payment->creds ) );
					wc_add_notice( $message, 'success' );

				}
			}
		}

	}
}
add_action( 'woocommerce_cart_item_removed', 'mycred_part_woo_remove_cart_items', 10, 2 );

/**
 * Prevent to increase total balance for partial payment refund
 *
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_prevent_increase_total_balance' ) ) {
	function mycred_prevent_increase_total_balance( $value, $data ) {

		if ( 'partial_payment_refund' == $data['ref'] ) {
			return false;
		}

		return $value;
	}
}
add_filter( 'mycred_update_total_balance', 'mycred_prevent_increase_total_balance', 10, 2 );
