<?php
if ( ! class_exists( 'MyCred_Woo_Rank_Discount' ) ) :
	class MyCred_Woo_Rank_Discount {


		public function __construct() {

			if ( 'yes' == get_option( 'mycred_wooplus_show_ranks' ) ) {

				add_action( 'admin_init', array( $this, 'mycred_ranks_add_metabox' ) );
				add_action( 'save_post', array( $this, 'mycred_add_badge_rank_fields' ), 10, 2 );
				add_action( 'woocommerce_cart_calculate_fees', array( $this, 'mycred_rank_apply_discount_in_cart' ) );

			}

		}

		public function mycred_rank_apply_discount_in_cart() {

			global $woocommerce;
			if ( 'yes' != get_option( 'mycred_wooplus_show_ranks' ) ) {
				return;
			}

			$rank_id = 0;
			if ( function_exists( 'mycred_get_users_rank_id' ) ) {

				$rank_id = mycred_get_users_rank_id( get_current_user_id() );

			}
			if ( empty( $rank_id ) ) {
				return;
			}

			$discount_percentage = (int) get_post_meta( $rank_id, 'mycred_product_discount_amount', true );

			if ( $discount_percentage < 0 || $discount_percentage > 100 ) {
				return;
			}

			$discount = 0;

			$items = $woocommerce->cart->get_cart();

			foreach ( $items as $cart_item_key => $cart_item ) {
				$product    	  = $cart_item['data'];
				$product_id 	  = $cart_item['product_id'];
				$quantity  		  = $cart_item['quantity'];
				$price 			  = $cart_item['data']->get_price();
				$product_subtotal = $price * $quantity;

				if ( $price <= 0 ) {
					continue;
				}

				$discount_for_product_quantity1 = ( $discount_percentage / 100 ) * $price;

				$product_total_discount = $discount_for_product_quantity1 * $quantity;

				if ( ( $product_subtotal ) >= $product_total_discount ) {
					$discount += $product_total_discount;
				}
			}
			$discount = round( $discount, 2 );
			$discount = $discount * -1;

			$rank_title = ' (' . get_the_title( $rank_id ) . ')';
			$woocommerce->cart->add_fee( esc_html__( 'Rank Discount', 'mycredpartwoo' ) . $rank_title, $discount ); // , true, ''
		}


		// mycode for rank metabox
		public function mycred_ranks_add_metabox() {

			// ranks meta box add
			if ( 'yes' == get_option( 'mycred_wooplus_show_ranks' ) ) {
				add_meta_box(
					'mycred_ranks_product_discount',
					__( 'Fixed Discount for each product', 'mycredpartwoo' ),
					array( $this, 'mycred_ranks_product_discount_callback' ),
					'mycred_rank',
					'normal',
					'low'
				);
			}

		}

		public function mycred_ranks_product_discount_callback( $rank ) { ?>
			
			
			<table width="100%">
				
				<tr>
					<td colspan="10" >
						<p style="font-weight: 600;">
							
							<?php echo esc_html__( 'You can use these settings to reward users on achieving this rank.', 'mycredpartwoo' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<td style="width: 25%"><?php echo esc_html__( 'Discount Type', 'mycredpartwoo' ); ?></td>
					<td>
						<?php $discount_type = get_post_meta( $rank->ID, 'mycred_product_discount_type', true ); ?>
						<select style="width:425px;" name="product_discount[mycred_product_discount_type]" >
							<option value='percent' 
							<?php
							if ( 'percent' == $discount_type ) {
								echo 'selected';
							}
							?>
							>
							<?php echo esc_html__( 'Percentage  Discount', 'mycredpartwoo' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Percentage', 'mycredpartwoo' ); ?></td>
				<td><input type="number" style="width:425px;" name="product_discount[mycred_product_discount_amount]" value="<?php echo esc_html( get_post_meta( $rank->ID, 'mycred_product_discount_amount', true ) ); ?>" />
				</td>
			</tr>
			</table>
			<?php
		}

		public function mycred_add_badge_rank_fields( $save_badge_rank_id, $post ) {
			
			if ( isset( $_POST['custom_nonce'] ) && wp_verify_nonce(sanitize_text_field($_POST['custom_nonce']), 'custom_nonce' )) {
				$data = $_POST;
			}
			$data = $_POST;

			if ( 'mycred_rank' == $post->post_type ) {
				if ( isset( $data['product_discount'] ) ) {
					foreach ( $data['product_discount'] as $key => $value ) {
						update_post_meta( $save_badge_rank_id, $key, $value );
					}
				}
			}

		}


	public function create_coupons_badges_ranks( $args ) {

		$args = apply_filters( 'mycred_wooplus_modify_coupon', $args );

		$ranks_or_badges_ids = $args['ranks_or_badges_ids'];
		$coupon_code         = $args['coupon_code'];
		$amount              = $args['amount'];
		$discount_type       = $args['discount_type'];
		$customer_email      = $args['customer_email'];
		$description         = $args['description'];
		$type                = $args['type'];
		$level               = $args['level'];
		$individual_use      = $args['individual_use'];
		$product_ids         = $args['product_ids'];
		$exclude_product_ids = $args['exclude_product_ids'];
		$usage_limit         = $args['usage_limit'];
		$expiry_date         = $args['expiry_date'];
		$apply_before_tax    = $args['apply_before_tax'];
		$free_shipping       = $args['free_shipping'];

		global $woocommerce;

		$coupon = array(
			'post_title'   => $coupon_code . '_' . get_current_user_id(),
			'post_content' => '',
			'post_excerpt' => $description,
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon',
		);

		$new_coupon_id = wp_insert_post( $coupon );

			// Add meta coupons
		update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
		update_post_meta( $new_coupon_id, 'individual_use', $individual_use );
		update_post_meta( $new_coupon_id, 'product_ids', $product_ids );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', $exclude_product_ids );
		update_post_meta( $new_coupon_id, 'usage_limit', $usage_limit );
		update_post_meta( $new_coupon_id, 'date_expires', $expiry_date );
		update_post_meta( $new_coupon_id, 'apply_before_tax', $apply_before_tax );
		update_post_meta( $new_coupon_id, 'free_shipping', $free_shipping );
		update_post_meta( $new_coupon_id, 'customer_email', $customer_email );
		update_post_meta( $new_coupon_id, 'reference_type', $type );
		update_post_meta( $new_coupon_id, 'user_id', get_current_user_id() );

		if ( 'rank' == $type ) {

			$mycred_ranks_coupons = get_user_meta( get_current_user_id(), 'mycred_ranks_coupons', true );

			if ( '' == get_user_meta( get_current_user_id(), 'mycred_ranks_coupons', true ) ) {

				$mycred_ranks_coupons = array();
			}

			array_push(
				$mycred_ranks_coupons,
				array(
					'rank_id'   => $ranks_or_badges_ids,
					'coupon_id' => $new_coupon_id,
				)
			);

			update_user_meta( get_current_user_id(), 'mycred_ranks_coupons', $mycred_ranks_coupons );

			$response_coupon_code = get_the_title( $new_coupon_id );

		}

		if ( 'badge' == $type ) {

			$mycred_badges_coupons = get_user_meta( get_current_user_id(), 'mycred_badges_coupons', true );

			if ( ! $mycred_badges_coupons ) {
				$mycred_badges_coupons = array();
			}

			array_push(
				$mycred_badges_coupons,
				array(
					'badge_id'  => $ranks_or_badges_ids,
					'coupon_id' => $new_coupon_id,
					'level'     => $level,
				)
			);

			update_user_meta( get_current_user_id(), 'mycred_badges_coupons', $mycred_badges_coupons );

		}
		wc_add_notice( $description, 'notice' );

			// return coupon id
		return $new_coupon_id;

	}


	public function my_custom_insert_after_helper( $items, $new_items, $after ) {
			// Search for the item position and +1 since is after the selected item key.
		$position = array_search( $after, array_keys( $items ) ) + 1;

			// Insert the new item.
		$array  = array_slice( $items, 0, $position, true );
		$array += $new_items;
		$array += array_slice( $items, $position, count( $items ) - $position, true );

		return $array;
	}


	public function copouns_status( $copoun_id ) {

		$usage_limit = get_post_meta( $copoun_id, 'usage_limit', true );
		$usage_count = get_post_meta( $copoun_id, 'usage_count', true );

		if ( get_post_meta( $copoun_id, 'date_expires', true ) ) {

			$date_expires = gmdate( 'd/m/Y', get_post_meta( $copoun_id, 'date_expires', true ) );

		} else {

			$date_expires = gmdate( 'd/m/Y', strtotime( '+1 day' ) );

		}

		if ( $usage_count < $usage_limit && $date_expires > gmdate( 'd/m/Y' ) ) {

			echo esc_html__( 'Available', 'mycredpartwoo' );

		} else {

			if ( $date_expires > gmdate( 'd/m/Y' ) ) {

				echo esc_html__( 'Used', 'mycredpartwoo' );

			} else {

				echo esc_html__( 'Expired', 'mycredpartwoo' );

			}
		}
	}


	public function mycred_badges_ranks_coupons_data( $coupons_settings ) {

		$coupons_settings = apply_filters( 'wooplus_badges_ranks_coupons_type', $coupons_settings );

		if ( ! isset( $coupons_settings['type'] ) ) {
			$coupons_settings = array( 'type' => 'all' ); }

			switch ( $coupons_settings['type'] ) {
				case 'badge':
				$meta_query_args = array(
					array(
						'key'     => 'reference_type',
						'value'   => 'badge',
						'compare' => '=',
					),
					array(
						'key'     => 'user_id',
						'value'   => get_current_user_id(),
						'compare' => '=',
					),
				);

				break;
				case 'rank':
				$meta_query_args = array(
					array(
						'key'     => 'reference_type',
						'value'   => 'rank',
						'compare' => '=',
					),
					array(
						'key'     => 'user_id',
						'value'   => get_current_user_id(),
						'compare' => '=',
					),
				);

				break;
				default:
				$meta_query_args = array(
					array(
						'key'     => 'user_id',
						'value'   => get_current_user_id(),
						'compare' => '=',
					),
				);
			}

			$args = array(
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'meta_query'     => array( $meta_query_args ),
			);

			$coupons = get_posts( $args );

			ob_start();
			?>
			
			<div class="mycred_coupons_badge_rank_container">
				
				<table class="mycred_coupons_badge_rank">
					
					<thead>
						<tr> 
							<th class=""><span><?php echo esc_html__( 'Sno', 'mycredpartwoo' ); ?></span></th>
							<th class=""><span><?php echo esc_html__( 'Coupon Code', 'mycredpartwoo' ); ?></span></th>
							<th class=""><span><?php echo esc_html__( 'Amount', 'mycredpartwoo' ); ?></span></th>
							<th class="coupon_description" ><span><?php echo esc_html__( 'Description', 'mycredpartwoo' ); ?></span></th>
							<th class=""><span><?php echo esc_html__( 'Expiry date', 'mycredpartwoo' ); ?></span></th>
							<th class=""><span><?php echo esc_html__( 'Status', 'mycredpartwoo' ); ?></span></th>
						</tr>
					</thead>

					<tbody>
						<?php
						if ( count( $coupons ) >= 1 ) {
							$sno = 1;

							foreach ( $coupons as $coupon ) {
								?>
								<tr class="<?php echo esc_attr( $this->copouns_status( $coupon->ID ) ) ; ?>">
									
									<td class=""><?php echo esc_attr( $sno ); ?></td>
									
									<td class="coupon_code">
										<span class="copoun_code_style">
											<?php echo wp_kses_post( $coupon->post_title ) ; ?>
										</span>
									</td>
									
									<td class="">
										<?php

										if ( 'percent' == get_post_meta( $coupon->ID, 'discount_type', true ) ) {

											echo esc_attr( get_post_meta( $coupon->ID, 'coupon_amount', true ) ) . '% Off';

										} else {

											$currency = get_woocommerce_currency_symbol();
											echo esc_attr( $currency ) . esc_attr( get_post_meta( $coupon->ID, 'coupon_amount', true ) ) . ' Off';

										}

										?>
										
									</td>
									
									<td class="">
										<?php echo wp_kses_post( $coupon->post_excerpt ) ; ?>
									</td>
									
									<td class="">
										<?php

										if ( '' != get_post_meta( $coupon->ID, 'date_expires', true ) ) {

											$date_expires = gmdate( 'd/m/Y', get_post_meta( $coupon->ID, 'date_expires', true ) );
											echo wp_kses_post( $date_expires ) ;

										} else {
											echo '-';
										}
										?>
									</td>
									
									<td class="">
										<?php echo wp_kses_post( $this->copouns_status( $coupon->ID ) ) ; ?>
									</td>
									
								</tr>
								<?php
								$sno++; 
							}
						} else {
							?>
							
							<tr class="">
								<td class="no_copouns_found" colspan="10">
									<?php echo esc_html__( 'No copouns found.', 'mycredpartwoo' ); ?>
								</td>
							</tr>
							
						<?php } ?>
					</tbody>
				</table>
			</div>
			<?php
			return ob_get_clean();
		}


	}

	$MyCred_Woo_Rank_Discount = new MyCred_Woo_Rank_Discount();
endif;
