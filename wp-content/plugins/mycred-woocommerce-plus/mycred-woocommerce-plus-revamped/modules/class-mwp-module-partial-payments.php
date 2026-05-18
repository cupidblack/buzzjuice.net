<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'MWP_Partial_Payments_Module' ) ) :
	class MWP_Partial_Payments_Module extends MWP_Module {
		
		private $shipping_discount = '';

		public function __construct() {
			parent::__construct( 'MWP_Partial_Payments_Module', array(
				'module_name' => 'mwp_partial_payments',
				'defaults'    => array(
					'enable' => 0
				),
				'add_tab'     => true,
				'title'       => __('Partial Payments', 'mycred-woocommerce-plus'),
				'icon'        => 'dashicons-money-alt',
				'tab_pos'     => 15
			) );
			
			
		}

		public function module_init() {
			
			if ( ! $this->prefs['enable'] ) {
				return;
			}
			add_action( 'woocommerce_cart_collaterals', array( $this, 'render_partial_payment' ) );
			if( 'before' == $this->prefs['position'] ) {
				add_action( 'woocommerce_checkout_order_review',array( $this, 'render_partial_payment'), 9 );
			} else {
				add_action( 'woocommerce_review_order_before_payment',array( $this, 'render_partial_payment'), 9 );
			}
			add_action( 'mycred_front_enqueue', array( $this, 'register_front_assets' ) );
			add_filter( 'woocommerce_cart_totals_coupon_html',array( $this, 'mycred_part_woo_remove_coupon_option'), 10, 2 );
			add_action( 'woocommerce_review_order_after_order_total',array( $this, 'mycred_part_woo_insert_total_cost'), 50 );
			add_action( 'woocommerce_cart_totals_after_order_total',array( $this, 'mycred_part_woo_insert_total_cost'), 50 );
			add_action( 'woocommerce_review_order_after_order_total',array( $this, 'mycred_part_woo_insert_total_balance'), 50 );
			add_action( 'woocommerce_cart_totals_after_order_total',array( $this, 'mycred_part_woo_insert_total_balance'), 50 );
			add_filter( 'woocommerce_add_to_cart_fragments',array( $this, 'mycred_partial_payment_content' ) );
			add_action( 'woocommerce_removed_coupon', array( $this, 'mycred_part_woo_remove_coupon_action' ) );
			add_action( 'woocommerce_cart_item_removed', array( $this, 'mycred_part_woo_remove_cart_items' ), 10, 2 );
			add_filter( 'mycred_all_references', array( $this, 'register_partial_references' ) );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_shipping_discount_on_coupon' ) );
			add_action('wp_logout', array( $this, 'mycred_part_woo_logout_refund') );
			add_filter( 'mycred_run_this', array( $this, 'reward_adjustment' ) );
			add_action( 'wp_ajax_mycred_new_partial_payment',array( $this, 'mycred_new_partial_payment' ) );
			add_action( 'wp_ajax_nopriv_mycred_new_partial_payment',array( $this, 'mycred_new_partial_payment' ) );
			add_action('wp_ajax_mycred_get_partial_data', array( $this, 'mycred_get_partial_data' ) );
			add_action('wp_ajax_nopriv_mycred_get_partial_data', array( $this, 'mycred_get_partial_data' ) );

		}

		/**
		 * Reward Adjustments
		 * When you make a partial payment in points AND you set your store to reward
		 * store purchases using points, this partial payment can in certain setups cause
		 * a user to get their points back or get more back due to rewards.
		 * This filter will deduct the amount of points a user made as a partial payment (if they made one)
		 * and deduct this amount from the reward amount to prevent the user to ever gaining more than they paid.
		 *
		 * @since   1.0
		 * @version 1.2
		 */
		public function reward_adjustment( $run_this ) {

			// We need WooCommerce for this
			if ( ! function_exists( 'wc_get_order' ) ) {
				return $run_this;
			}

			extract( $run_this );

			$options = get_option('mycred_pref_woo');
			$prefs = $options['mwp_partial_payments'];
			
			$mycred = mycred( $type );
			$exchange_rate = $mycred->core['partial_payment_settings']['exchange_rate'];
			if ( ! array_key_exists( 'rewards', $prefs ) || 2 != $prefs['rewards'] ) {
				return $run_this;
			}
			

			/**
			* Filter mycred_woo_reward_reference
			* Filter mycred_woo_reward_mycred_payment
			* 
			* @since 1.0
			**/
			if ( apply_filters( 'mycred_woo_reward_reference', 'reward', 0, $type ) == $ref && apply_filters( 'mycred_woo_reward_mycred_payment', false, 0 ) === false ) {

				$order_id = absint( $ref_id );
				$order    = wc_get_order( $order_id );

				$discount = $order->get_discount_total();
				
				// No discount used = nothing for us to do
				if ( $discount <= 0 ) {
					return $run_this;
				}


				if ( 1 != $exchange_rate ) {
					$discount = $discount / $exchange_rate;
				}

				// Stop transaction if the user is getting more than they
				if ( ( $amount - $discount ) <= 0 ) {
					$run_this['amount'] = null;
					$run_this['entry']  = '';
				} else {
					// Deduct the amount the user paid from the reward
					$run_this['amount'] = ( $amount - $discount );
				}
			}

			return $run_this;
		}

		public function mycred_part_woo_logout_refund() {

			global $wpdb, $mycred;

			$options = get_option('mycred_pref_woo');
			$settings = $options['mwp_partial_payments'];

			$user_id = get_transient('mycred_user_id');

			if ('yes' != $settings['undo']) {
				return;
			}

			$table_name = $mycred->log_table;
			$partial_payments = $wpdb->get_results( 
				$wpdb->prepare( 
					"SELECT * FROM {$table_name} WHERE ref = 'partial_payment' AND ref_id != 0 AND user_id = %d AND data = '' ORDER BY time DESC;", 
					$user_id 
				) 
			);

			if (!empty($partial_payments)) {
				foreach ($partial_payments as $payment) {
					$mycred = mycred($payment->ctype);

		            // Refund payment
					$mycred->add_creds(
						'partial_payment_refund',
						$payment->user_id,
						abs($payment->creds),
						'Partial Payment',
						0,
						$payment->ref_id,
						$payment->ctype
					);

		            // Update partial payment in log to prevent re-use
					$wpdb->update(
						$mycred->log_table,
						array('ref_id' => 0),
						array('id' => $payment->id),
						array('%d'),
						array('%d')
					);

		            // Remove coupon
					$cart = WC()->cart;
					if ($cart) {
		                // Prevent the coupon removal hook from running
						remove_action('woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action');

						$cart->remove_coupon(get_the_title($payment->ref_id));

						add_action('woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action');
					}

		            // Move the partial payment to trash
					wp_trash_post($payment->ref_id);

		            // Add a notice for refund (optional, as user is logging out)
					$message = $mycred->template_tags_amount(
						'Your partial payment of %cred_f% was refunded to your account',
						abs($payment->creds)
					);
					wc_add_notice($message, 'success');
				}
			}
		}

		public function mycred_get_partial_data() {
			if ( ! isset( $_POST['selected_pointtype'] ) || empty( $_POST['selected_pointtype'] ) ) {
				wp_send_json_error( [ 'message' => 'Invalid request' ] );
			}

			$options = get_option( 'mycred_pref_woo' );
			$mycred_partial_payment = $options['mwp_partial_payments'];
			$selected_pointtype = sanitize_text_field( $_POST['selected_pointtype'] );
			$mycred = mycred( $selected_pointtype );

			$cart = WC()->cart;
			$user_id = get_current_user_id();
			$applied_coupons = $cart->get_applied_coupons();
			$balance = $mycred->get_users_balance( $user_id );
			$formatted_balance = $mycred->format_creds( $balance );

			$settings = $mycred->core['partial_payment_settings'];
			$min = isset( $settings['mwp_min'] ) ? $settings['mwp_min'] : 1;
			$max = isset( $settings['mwp_max'] ) ? $settings['mwp_max'] : 100;
			$exchange_rate = ( isset( $settings['exchange_rate'] ) && $settings['exchange_rate'] != 0 ) ? $settings['exchange_rate'] : 1;

			if ( $max < $min ) {
				$min = $max;
			}

			$total_coupon_amount = 0;
			$point_cost = 0;

			if ( ! empty( $applied_coupons ) ) {
				foreach ( $applied_coupons as $code ) {
					$total_coupon_amount += $cart->get_coupon_discount_amount( $code );
				}
			}

			$cart_subtotal = $cart->get_subtotal(); //45
			$shipping_total = $cart->get_shipping_total(); //10
			$fee_total = $cart->get_fee_total(); // 0

			if ( $fee_total ) {
				$shipping_total -= abs( $fee_total );
			}

			if ( isset( $mycred_partial_payment['free_shipping'] ) && $mycred_partial_payment['free_shipping'] === 'no' ) {
				$cart_total = $cart_subtotal;
			} else {
				$cart_total = $cart_subtotal + $shipping_total;
			}

			$max_discount = $cart_total * ( $max / 100 );
			$discount_after_coupon = $max_discount - $total_coupon_amount;
			$point_cost = max( 0, $discount_after_coupon / $exchange_rate );

			if ( $point_cost <= 0 ) {
				$min = 0;
			}

			$formatted_point_cost = $mycred->format_creds( $point_cost );

			$data = [
				'min'              => $min,
				'formatted_min'    => $mycred->format_creds( $min ),
				'exchangeRate'     => $exchange_rate,
				'pointCost'        => $formatted_point_cost,
				'userBalance'      => $formatted_balance,
				'pointCosttext'    => $mycred->template_tags_general( $mycred_partial_payment['checkout_total_label'] ),
				'userBalancetext'  => $mycred->template_tags_general( $mycred_partial_payment['checkout_balance_label'] ),
			];

			wp_send_json_success( $data );
		}



		public function mycred_new_partial_payment() {

			if ( ! isset( $_POST['token'] ) || ( isset( $_POST['token'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['token'] ), 'mycred-partial-payment-new' ) ) ) {
				exit( 'Not Authorized' );
			}

			$options = get_option('mycred_pref_woo');
			$mycred_partial_payment = $options['mwp_partial_payments'];

			wc_clear_notices();

			$mycred = mycred( $_POST['type'] );
			$user_id = get_current_user_id();

			$max = ! empty( $mycred->core['partial_payment_settings']['mwp_max'] ) ? $mycred->core['partial_payment_settings']['mwp_max'] : 100 ;

			if ( $mycred->exclude_user( $user_id ) ) {
				wc_add_notice( __( 'You are not allowed to use this feature.', 'mycred-woocommerce-plus
					' ), 'error' );
				wp_send_json_error();
			}

			$balance = $mycred->get_users_balance( $user_id );

			if ( isset( $_POST['amount'] ) ) {
				$amount  = $mycred->number( abs( sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) );
			}

			if ( $amount == $mycred->zero() ) {
				wc_add_notice( __( 'Amount can not be zero.', 'mycred-woocommerce-plus
					' ), 'error' );
				wp_send_json_error();
			}

			if ( $balance < $amount ) {
				wc_add_notice( __( 'Insufficient Funds. Please try a lower amount.', 'mycred-woocommerce-plus
					' ), 'error' );
				wp_send_json_error();
			}

			$total = mycred_part_woo_get_total( $mycred_partial_payment );

			$exchange_rate = !empty($mycred->core['partial_payment_settings']['exchange_rate']) ? $mycred->core['partial_payment_settings']['exchange_rate'] : 1;
			$value = $exchange_rate * $amount;
			

			if ($total > 0 && $value > $total * $max / 100) {
				wc_add_notice( __( 'The amount can not be greater than the maximum amount.', 'mycred-woocommerce-plus
					' ), 'error' );
				wp_send_json_error();
			}

			if ( $amount < $mycred->core['partial_payment_settings']['mwp_min'] ) {
				wc_add_notice( __( 'The amount can not be less than the minimum amount.', 'mycred-woocommerce-plus
					' ), 'error' );
				wp_send_json_error();
			}
			
			$cart_items = WC()->cart->get_cart();
			foreach ( $cart_items as $cart_item ) {
				$product = wc_get_product( $cart_item['product_id'] );
				if ( $product->is_on_sale() && ( 'no' === $mycred_partial_payment['sale_items'] ) ) {
					wc_add_notice( __( 'Discounts cannot be applied to sale items.', 'mycred-woocommerce-plus' ), 'error' );
					wp_send_json_error();
				}
			}

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

			if (null === $new_coupon_id || is_wp_error($new_coupon_id)) {
				wc_add_notice(__('Failed to complete transaction. Error 1. Please contact support.', 'mycred-woocommerce-plus'), 'error');
				wp_send_json_error();
			}

			$applied_coupons = WC()->cart->get_applied_coupons();
			$total_coupon_amount = 0;
			$fee = 0;

			if ( ! empty( $applied_coupons ) ) {
				foreach ( $applied_coupons as $code ) {
					$discount_amount = WC()->cart->get_coupon_discount_amount( $code );
					$total_coupon_amount += $discount_amount;
				}

				$cart_subtotal = WC()->cart->get_subtotal();
				$total_coupon_amount += $value;
				$fee = $cart_subtotal - $total_coupon_amount;
			} else {
				$cart_subtotal = WC()->cart->get_subtotal();
				$fee = $cart_subtotal - $value;
			}


			if ( 'yes' === $mycred_partial_payment['free_shipping'] ) {
				$cart_subtotal_remaining = WC()->cart->get_subtotal() - WC()->cart->get_discount_total();

				if ( $cart_subtotal_remaining < 0 ) {
					$cart_subtotal_remaining = 0;
				}

				$shipping_discount = $value - $cart_subtotal_remaining;

				if ( $shipping_discount < 0 ) {
					$shipping_discount = 0;
				}

				$remaining_coupon_value = $shipping_discount;

				$prev_amount = get_user_meta( $user_id, 'remaining_coupon_amount', true );
				if ( $prev_amount ) {
					$remaining_coupon_value += $prev_amount;
				}

				update_user_meta( $user_id, 'remaining_coupon_amount', $remaining_coupon_value );
			}



			update_post_meta($new_coupon_id, 'discount_type', 'fixed_cart');
			update_post_meta($new_coupon_id, 'coupon_amount', $value );
			update_post_meta($new_coupon_id, 'individual_use', 'no');
			update_post_meta($new_coupon_id, 'product_ids', '');
			update_post_meta($new_coupon_id, 'exclude_product_ids', '');
			update_post_meta($new_coupon_id, 'usage_limit', 1);
			update_post_meta($new_coupon_id, 'usage_limit_per_user', 1);
			update_post_meta($new_coupon_id, 'limit_usage_to_x_items', '');
			update_post_meta($new_coupon_id, 'usage_count', '');
			update_post_meta($new_coupon_id, 'expiry_date', '');
			update_post_meta($new_coupon_id, 'free_shipping', (('no' == $mycred_partial_payment['free_shipping']) ? 'no' : 'yes'));
			update_post_meta($new_coupon_id, 'product_categories', array());
			update_post_meta($new_coupon_id, 'exclude_product_categories', array());
			update_post_meta($new_coupon_id, 'exclude_sale_items', (('no' == $mycred_partial_payment['sale_items']) ? 'yes' : 'no'));
			update_post_meta($new_coupon_id, 'minimum_amount', '');
			update_post_meta($new_coupon_id, 'customer_email', array());
			if ( class_exists( 'Dokan_Pro' ) ) {
				$cart_subtotal = WC()->cart->get_subtotal();

				if ( $cart_subtotal > 0 ) {
					$percent_value = ( $value / $cart_subtotal ) * 100;
				} else {
					$percent_value = 0;
				}

				update_post_meta( $new_coupon_id, 'discount_type', 'percent' );
				update_post_meta( $new_coupon_id, 'coupon_amount', round( $percent_value, 2 ) );

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

			} else {
				update_post_meta( $new_coupon_id, 'discount_type', 'fixed_cart' );
				update_post_meta( $new_coupon_id, 'coupon_amount', $value );
			}
			/**
			* Action mycred_woo_partial_after_coupon_generation
			*
			* @since 1.0
			**/
			do_action('mycred_woo_partial_after_coupon_generation', $new_coupon_id, $_POST);

			$applied = WC()->cart->add_discount( $coupon_code );

			if ( $max && count(WC()->cart->get_applied_coupons() ) > 1) {
				$cart_total = WC()->cart->get_subtotal();
				
				if ( 'yes' === $mycred_partial_payment['free_shipping'] ) {
					$cart_total += WC()->cart->shipping_total;
				}
				
				$max_partial_payment = ( $cart_total * $max ) / 100;

				$existing_discount = 0;
				foreach ( WC()->cart->get_applied_coupons() as $code ) {
					if ( $code !== $coupon_code ) {
						$existing_discount += WC()->cart->get_coupon_discount_amount( $code );
					}
				}
				
				$mycred_coupon_discount = WC()->cart->get_coupon_discount_amount( $coupon_code );
				$total_discount = $existing_discount + $mycred_coupon_discount;

				if ( $total_discount > $max_partial_payment ) {
					WC()->cart->remove_coupon( $coupon_code );
					wc_add_notice( __( 'The maximum allowable discount for this order has been reached due to applied coupons.', 'mycred-woocommerce-plus' ), 'error' );
					wp_send_json_error();
				}
			}

			if ( true === $applied ) {

				if ( '' == $mycred_partial_payment['log'] ) {
					$mycred_partial_payment['log'] = __( 'Partial Payment', 'mycred-woocommerce-plus' );
				}

				update_post_meta( $new_coupon_id, 'mycred_partial_coupon', true );


				$mycred->add_creds(
					'partial_payment',
					$user_id,
					0 - $amount,
					$mycred_partial_payment['log'],
					$new_coupon_id,
					'',
					$_POST['type']
				);

				set_transient('mycred_user_id', $user_id, 36000 );

				global $multiple_payment_restricted;

				if ( 'no' === $mycred_partial_payment['multiple'] ) {
					$multiple_payment_restricted = true;
				} else {
					$multiple_payment_restricted = false;
				}

				wc_clear_notices();
				wc_add_notice( __( 'Payment Successfully Applied.', 'mycred-woocommerce-plus' ) );
				wp_send_json_success( $multiple_payment_restricted );
			}

			// Delete the coupon
			wp_trash_post( $new_coupon_id );
			wc_add_notice( __( 'Failed to complete transaction. Error 2. Please contact support.', 'mycred-woocommerce-plus
				' ), 'error' );
			wp_send_json_error();
		}

		public function mycred_part_woo_remove_cart_items( $cart_item_key, $cart ) {
			global $wpdb, $mycred;
			if ( 0 == $cart->get_cart_contents_count() && is_user_logged_in() ) {

				$options = get_option('mycred_pref_woo');
				$settings = $options['mwp_partial_payments'];
				
				if ( 'yes' != $settings['undo'] ) {
					return;
				}

				$table_name = $mycred->log_table;
				$partial_payments = $wpdb->get_results( 
					$wpdb->prepare( 
						"SELECT * FROM {$table_name} WHERE ref = 'partial_payment' AND ref_id != 0 AND user_id = %d AND data = '' ORDER BY time DESC;", 
						$user_id 
					) 
				);

				if (!empty($partial_payments)) {
					foreach ($partial_payments as $payment) {
						$mycred = mycred($payment->ctype);

		            	// Refund payment
						$mycred->add_creds(
							'partial_payment_refund',
							$payment->user_id,
							abs($payment->creds),
							'Partial Payment',
							0,
							$payment->ref_id,
							$payment->ctype
						);

		            	// Update partial payment in log to prevent re-use
						$wpdb->update(
							$mycred->log_table,
							array('ref_id' => 0),
							array('id' => $payment->id),
							array('%d'),
							array('%d')
						);

		            	// Remove coupon
						$cart = WC()->cart;
						if ($cart) {
		                // Prevent the coupon removal hook from running
							remove_action('woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action');

							$cart->remove_coupon(get_the_title($payment->ref_id));

							add_action('woocommerce_removed_coupon', 'mycred_part_woo_remove_coupon_action');
						}

		            	// Move the partial payment to trash
						wp_trash_post($payment->ref_id);

		            	// Add a notice for refund (optional, as user is logging out)
						$message = $mycred->template_tags_amount(
							'Your partial payment of %cred_f% was refunded to your account',
							abs($payment->creds)
						);
						wc_add_notice($message, 'success');
					}
				}
			}
		}
		
		public function apply_shipping_discount_on_coupon( $cart ) {
			if ( count( WC()->cart->get_applied_coupons() ) > 0 ) {
				$options = get_option('mycred_pref_woo');
				$mycred_partial_payment = $options['mwp_partial_payments'];

				$user_id = get_current_user_id();
				if ( $user_id ) {
					$remaining_coupon_amount = get_user_meta( $user_id, 'remaining_coupon_amount', true );
					$remaining_coupon_amount = floatval( $remaining_coupon_amount ); // Make sure it's numeric

					if ( $remaining_coupon_amount > 0 && 'yes' === $mycred_partial_payment['free_shipping'] ) {
						$cart->add_fee( __( 'Shipping Discount' ), -$remaining_coupon_amount );
						$cart->calculate_fees();
					}
				}
			}
		}

		public function register_partial_references( $references ) {

			$references['partial_payment'] = __( 'Partial Payment', 'mycred-woocommerce-plus' );
			$references['points_to_coupon'] = __( 'Points to Coupon', 'mycred-woocommerce-plus' );
			$references['partial_payment_refund'] = __( 'Partial Payment Refund', 'mycred-woocommerce-plus' );

			return $references;
		}

		public function mycred_part_woo_remove_coupon_action( $coupon = '' ) {

			$options = get_option('mycred_pref_woo');
			$settings = $options['mwp_partial_payments'];

			if ( 'yes' != $settings['undo'] ) {
				return;
			}

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

			if ( $coupon_usage > 0 ) {
				return;
			}

			global $wpdb;

			$partial_payment = mycred_get_partial_payment( $coupon_post_id );

			if ( false !== $partial_payment ) {
				
				$mycred = mycred( $partial_payment->ctype );
				
				$mycred->add_creds(
					'partial_payment_refund',
					$partial_payment->user_id,
					abs( $partial_payment->creds ),
					'Partial Payment',
					$partial_payment->ref_id,
					'',
					$partial_payment->ctype
				);

				$wpdb->update(
					$mycred->log_table,
					array( 'ref_id' => 0 ),
					array( 'id' => $partial_payment->id ),
					array( '%d' ),
					array( '%d' )
				);

				wp_trash_post( $coupon_post_id );
				$applied_coupons = WC()->cart->get_applied_coupons();

				if ( empty( $applied_coupons ) ) {
					$user_id = get_current_user_id();
					if ( $user_id ) {
						delete_user_meta( $user_id, 'remaining_coupon_amount' );
					}
				}

				$multiple_payment_restricted = false;
			}
		}

		public function mycred_partial_payment_content( $fragments ) {

			$options = get_option('mycred_pref_woo');
			$mycred_partial_payment = $options['mwp_partial_payments'];

			$change_position	= $mycred_partial_payment['change_position'];
			
			if ( ( 'both' == $change_position ) || ( 'cart' == $change_position  ) || ( 'checkout' == $change_position  ) ) {

				if ( 'no' === $mycred_partial_payment['multiple'] && ! empty( WC()->cart->get_applied_coupons() ) ) {
					return;
				}


				ob_start();

				$this->load_template( 
					'partial_payment_module', 
					'partial-payment-module/frontend-partial-payment.php',
					array( 
						'settings'         => $this->prefs,
					) 
				);

				$fragments['div#mycred-partial-payment-woo'] = ob_get_clean();
			}

			return $fragments;
		}

		public function mycred_part_woo_insert_total_balance() {

			if ( ! is_user_logged_in() ) {
				return;
			}

			$options = get_option('mycred_pref_woo');
			$mycred_partial_payment = $options['mwp_partial_payments'];
			$mycred             = new myCRED_Settings();
			$show_balance = $mycred_partial_payment['checkout_balance'];

			if ( ( 'both' == $show_balance )
				|| ( 'cart' == $show_balance && is_cart() )
				|| ( 'checkout' == $show_balance && is_checkout() )
			) {
				?>
				<tr class="total user-balance">
					<th><?php echo wp_kses_post( mycred_wcp_kses_html($mycred->template_tags_general( $mycred_partial_payment['checkout_balance_label'] ) ) ); ?></th>
					<td>
						<div class="current-balance order-total-in-points">
							<?php echo wp_kses_post( mycred_wcp_kses_html($mycred->format_creds( 0 ) ) ); ?> 
						</div>
					</td>
				</tr>
				<?php

			}
		}

		public function mycred_part_woo_insert_total_cost() {

			$options = get_option('mycred_pref_woo');
			$mycred_partial_payment = $options['mwp_partial_payments'];
			$mycred             = new myCRED_Settings();
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

				?>
				<tr class="total point-cost">
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
							><?php echo wp_kses_post( mycred_wcp_kses_html($mycred->format_creds( 0 ) ) ); ?></strong> 
						</div>
					</td>
				</tr>
				<?php
			}
		}

		public function mycred_part_woo_remove_coupon_option( $html, $coupon ) {

			$options = get_option('mycred_pref_woo');
			$mycred_partial_payment = $options['mwp_partial_payments'];

			$partial_payment = mycred_get_partial_payment( $coupon->get_id() );
			$coupon_check = get_post_meta( $coupon->get_id() , 'mycred_partial_coupon', true );
			if ( $coupon_check && 'no' == $mycred_partial_payment['undo'] ) {

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

				// if ( $coupon->get_free_shipping() ) {
				// 	$value[] = __( 'Free shipping coupon', 'mycred-woocommerce-plus' );
				// }

				// get rid of empty array elements
				$value = array_filter( $value );
				$html  = implode( ', ', $value );

			}
			
			return $html;
		}

		public function register_front_assets() {
			$mycred_partial_payment = $this->prefs;


			wp_register_script( 'mwp-partial-payment-script', plugins_url( 'assets/js/mycred-partial-payment.js', MYCRED_WOOPLUS_THIS ), array( 'jquery' ), MYCRED_WOOPLUS_VERSION );

			if ( function_exists( 'is_checkout' ) && is_checkout() || function_exists( 'is_cart' ) && is_cart() || isset( $GLOBALS['wp_scripts']->registered[ 'wc-add-to-cart' ] ) ) {

				$total   = mycred_part_woo_get_total($mycred_partial_payment);
				$format  = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), 'COST' );

				wp_localize_script(
					'mwp-partial-payment-script',
					'myCREDPartial',
					array(
						'ajaxurl'  => admin_url( 'admin-ajax.php' ),
						'token'    => wp_create_nonce( 'mycred-partial-payment-new' ),
						'reload'   => wp_create_nonce( 'mycred-partial-payment-reload' ),
						'total'    => $total,
						'step' 	   => ! empty( $mycred_partial_payment['step']) ? $mycred_partial_payment['step'] : 1,
						'format'   => $format,
					)
				);
				$change_position = $this->prefs['change_position']; 
				if ( ( 'both' == $change_position ) || ( 'cart' == $change_position && is_cart() ) || ( 'checkout' == $change_position && is_checkout() ) ) {
					wp_enqueue_script( 'mwp-partial-payment-script' );
					wp_enqueue_script('wc-cart-fragments');
				}

			}
		}

		public function render_partial_payment() {
			if( 1 == $this->prefs['enable'] ) {
				$change_position = $this->prefs['change_position']; 
				if ( ( 'both' == $change_position ) || ( 'cart' == $change_position && is_cart() ) || ( 'checkout' == $change_position && is_checkout() ) ) {

					if ( 'no' === $this->prefs['multiple'] && ! empty( WC()->cart->get_applied_coupons() ) ) {
						return;
					}

					$this->load_template( 
						'partial_payment_module', 
						'partial-payment-module/frontend-partial-payment.php',
						array( 
							'settings'         => $this->prefs,
						) 
					);
				}

			}
		}

		public function admin_settings( $core ) {

			$settings = $core->woocommerce[ $this->module_name ];

			$this->load_template( 
				'partial_payment_settings', 
				'partial-payment-module/admin-settings.php',
				array( 'settings' => $settings ) 
			);
		}

		public function sanitize_settings( $new_data, $data, $core ) {

			$new_data[ $this->module_name ]['enable'] = !empty( $data[ $this->module_name ]['enable'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['change_position'] = !empty( $data[ $this->module_name ]['change_position'] ) ? $data[ $this->module_name ]['change_position'] : 'cart';
			$new_data[ $this->module_name ]['position'] = !empty( $data[ $this->module_name ]['position'] ) ? $data[ $this->module_name ]['position'] : 'after';
			$new_data[ $this->module_name ]['point_type'] = !empty( $data[ $this->module_name ]['point_type'] ) ? $data[ $this->module_name ]['point_type'] : 'mycred_default';

			$new_data[ $this->module_name ]['exchange'] = !empty( $data[ $this->module_name ]['exchange'] ) ? $data[ $this->module_name ]['exchange'] : 1;
			$new_data[ $this->module_name ]['min'] = !empty( $data[ $this->module_name ]['min'] ) ? $data[ $this->module_name ]['min'] : 1;
			$new_data[ $this->module_name ]['max'] = !empty( $data[ $this->module_name ]['max'] ) ? $data[ $this->module_name ]['max'] : '';

			$new_data[ $this->module_name ]['multiple'] = !empty( $data[ $this->module_name ]['multiple'] ) ? $data[ $this->module_name ]['multiple'] : 'yes';
			$new_data[ $this->module_name ]['undo'] = !empty( $data[ $this->module_name ]['undo'] ) ? $data[ $this->module_name ]['undo'] : 'yes';
			$new_data[ $this->module_name ]['rewards'] = !empty( $data[ $this->module_name ]['rewards'] ) ? $data[ $this->module_name ]['rewards'] : 1;

			$new_data[ $this->module_name ]['free_shipping'] = !empty( $data[ $this->module_name ]['free_shipping'] ) ? $data[ $this->module_name ]['free_shipping'] : 'no';

			$new_data[ $this->module_name ]['sale_items'] = !empty( $data[ $this->module_name ]['sale_items'] ) ? $data[ $this->module_name ]['sale_items'] : 'yes';

			$new_data[ $this->module_name ]['selecttype'] = !empty( $data[ $this->module_name ]['selecttype'] ) ? $data[ $this->module_name ]['selecttype'] : 'input';
			$new_data[ $this->module_name ]['step'] = !empty( $data[ $this->module_name ]['step'] ) ? $data[ $this->module_name ]['step'] : 1;

			$new_data[ $this->module_name ]['title'] = !empty( $data[ $this->module_name ]['title'] ) ? $data[ $this->module_name ]['title'] : 'Partial Payment';
			$new_data[ $this->module_name ]['desc'] = !empty( $data[ $this->module_name ]['desc'] ) ? $data[ $this->module_name ]['desc'] : '';
			$new_data[ $this->module_name ]['button'] = !empty( $data[ $this->module_name ]['button'] ) ? $data[ $this->module_name ]['button'] : 'Apply Discount';

			$new_data[ $this->module_name ]['checkout_total'] = !empty( $data[ $this->module_name ]['checkout_total'] ) ? $data[ $this->module_name ]['checkout_total'] : 'cart';

			$new_data[ $this->module_name ]['checkout_total_label'] = !empty( $data[ $this->module_name ]['checkout_total_label'] ) ? $data[ $this->module_name ]['checkout_total_label'] : 'Point Cost';

			$new_data[ $this->module_name ]['checkout_balance'] = !empty( $data[ $this->module_name ]['checkout_balance'] ) ? $data[ $this->module_name ]['checkout_balance'] : 'cart';

			$new_data[ $this->module_name ]['checkout_balance_label'] = !empty( $data[ $this->module_name ]['checkout_balance_label'] ) ? $data[ $this->module_name ]['checkout_balance_label'] : 'Your Balance';

			return $new_data;
		}

	}
endif;

function mwp_load_partial_payments_module() {
	$module = new MWP_Partial_Payments_Module();
	$module->load();
}
mwp_load_partial_payments_module();