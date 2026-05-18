<?php
if ( ! class_exists( 'MyCred_Woo_Badge_Rank_Copouns' ) ) :
	class MyCred_Woo_Badge_Rank_Copouns {


		public function __construct() {

			if ( 'yes' == get_option( 'mycred_wooplus_show_ranks' ) || ( 'yes' == get_option( 'mycred_wooplus_show_badges' ) ) ) {

				add_action( 'init', 						  		   array( $this, 'run_badges_and_ranks_copouns' ) );
				add_action( 'init', 						  		   array( $this, 'my_account_copouns_url_rewrite' ) );
				add_action( 'admin_init', 					  		   array( $this, 'mycred_add_metabox' ) );
				add_action( 'save_post_mycred_rank',  		           array( $this, 'save_rank_coupon_settings' ), 10, 2 );
				add_action( 'save_post_mycred_badge', 		   		   array( $this, 'save_badge_coupon_settings' ), 10, 2 );
				add_action( 'admin_enqueue_scripts', 		  		   array( $this, 'mycred_badge_rank_coupons_script' ) );
				add_action( 'woocommerce_account_my-coupons_endpoint', array( $this, 'copouns_shortcode_call' ) );
				add_filter( 'the_title', 					  		   array( $this, 'copouns_tab_endpoint_title' ), 10, 2 );
				add_filter( 'woocommerce_account_menu_items', 		   array( $this, 'add_copouns_tab' ) );
				add_filter( 'query_vars', 					  		   array( $this, 'copouns_query_vars' ), 0 );
				add_shortcode( 'mycred_badges_ranks_coupons', 		   array( $this, 'mycred_badges_ranks_coupons_lists' ) );

			}

		}


		public function copouns_tab_endpoint_title( $title, $id ) {

			global $wp_query;

			if ( isset( $wp_query->query_vars['my-coupons'] ) && in_the_loop() ) {

				return __( 'My Coupons', 'mycredpartwoo' );

			}

			return $title;
		}


		public function mycred_badge_rank_coupons_script() {

			wp_register_style(
				'mycred_badge_rank_coupons_style',
				plugins_url( 'assets/css/badge_rank_coupons_style.css', MYCRED_WOOPLUS_THIS ),
				array(),
				'1.7.2'
			);
			wp_enqueue_style( 'mycred_badge_rank_coupons_style', '', '', MYCRED_WOOPLUS_VERSION );

			if ( get_post_type() == 'mycred_badge' ) {
				wp_enqueue_script(
					'mycred_badge_rank_coupons',
					plugins_url( 'assets/js/mycred-badge-rank-coupons.js', MYCRED_WOOPLUS_THIS ),
					array( 'jquery', 'wp-i18n' ),
					MYCRED_WOOPLUS_VERSION
				);
				wp_enqueue_script( 'mycred_badge_rank_coupons', '', '', MYCRED_WOOPLUS_VERSION );

			}

		}



		// mycode for rank metabox
		public function mycred_add_metabox() {

			// ranks meta box add
			if ( 'yes' == get_option( 'mycred_wooplus_show_ranks' ) ) {
				add_meta_box(
					'mycred_ranks_coupons',
					__( 'Reward Coupon', 'mycredpartwoo' ),
					array( $this, 'mycred_ranks_meta_callback' ),
					'mycred_rank',
					'normal',
					'low'
				);
			}

			// badges meta box add
			if ( 'yes' == get_option( 'mycred_wooplus_show_badges' ) ) {
				add_meta_box(
					'mycred_badge_coupons',
					__( 'Reward Coupon', 'mycredpartwoo' ),
					array( $this, 'mycred_badges_meta_callback' ),
					'mycred_badge',
					'normal',
					'low'
				);
			}

		}

		public function mycred_badges_meta_callback( $badge ) {

			wp_enqueue_style( 'mwp-admin-style' );
			wp_enqueue_script( 'mwp-coupon-reward-script' );

			$settings     = mycred_get_post_meta( $badge->ID, 'woo_discount', true );
			$badge_levels = mycred_get_badge_levels( $badge->ID );

			if ( empty( $settings ) ) {
				
				$settings = array(
					array(
						'discount_type' => 'fixed',
						'discount_amount' => '0',
						'mycred_coupon_code_badge' => '',
					)
				);

			}

			wp_nonce_field( 'mwp_module_coupons_nonce', 'mwp-coupon-reward-nonce' );
			?>
			<h4><?php esc_html_e( 'You can use these settings to reward users on achieving this badge.', 'mycred-woocommerce-plus' );?></h4>
			<div class="mwp-badge-coupon-reward-wrapper">
				<?php foreach ( $settings as $key => $level ):?>
				<div class="mwp-badge-coupon-reward-item" data-level="<?php echo esc_attr( $key );?>">
					<div class="mwp-badge-coupon-reward-header">
						<?php echo ( ! empty( $badge_levels[$key]['label'] ) ? esc_html( $badge_levels[$key]['label'] ) : esc_html( sprintf( __( 'Level %s', 'mycred-woocommerce-plus' ), $key + 1 ) ) );?>
					</div>
					<div class="mwp-badge-coupon-reward-body">
						<div class="row">
						    <div class="col-lg-4 col-md-4 col-sm-12">
						        <div class="form-group">
						            <label for="discount-mycred-discount-type"><?php esc_html_e( 'Discount Type', 'mycred-woocommerce-plus' );?></label>
						            <?php 
						            mycred_create_select_field( 
						                array(
						                    'fixed'   => __( 'Fixed Discount', 'mycred-woocommerce-plus' ),
						                    'percent' => __( 'Percentage Discount', 'mycred-woocommerce-plus' )
						                ), 
						                $level['discount_type'],
						                array(
						                    'name'  => 'woo_discount[' . $key . '][discount_type]',
						                    'id'    => 'woo-discount-' . $key . '-discount-type',
						                    'class' => 'form-control'
						                )
						            );
						            ?>
						        </div>
						    </div>
						    <div class="col-lg-4 col-md-4 col-sm-12">
						        <div class="form-group">
						            <label for="discount-mycred-coupon-code-rank"><?php esc_html_e( 'Coupon code', 'mycred-woocommerce-plus' );?></label>
						            <?php  
						            mycred_create_input_field( array(
						                'type'  => 'text',
						                'name'  => 'woo_discount[' . $key . '][mycred_coupon_code_badge]',
						                'id'    => 'woo-discount-' . $key . '-mycred-coupon-code-badge',
						                'class' => 'form-control',
						                'value' => $level['mycred_coupon_code_badge']
						            ) );
						            ?>
						        </div>
						    </div>
						    <div class="col-lg-4 col-md-4 col-sm-12">
						        <div class="form-group">
						            <label for="discount-mycred-discount-bagde-rank"><?php esc_html_e( 'Amount', 'mycred-woocommerce-plus' );?></label>
						            <?php  
						            mycred_create_input_field( array(
						                'type'  => 'number',
						                'name'  => 'woo_discount[' . $key . '][discount_amount]',
						                'id'    => 'woo-discount-' . $key . '-discount_amount',
						                'class' => 'form-control',
						                'min'   => '0',
						                'value' => $level['discount_amount']
						            ) );
						            ?>
						        </div>
						    </div>
						</div>
					</div>
				</div>
				<?php endforeach;?>
			</div>
			<p><i><?php esc_html_e( 'NOTE: Keep amount 0 in order to disable coupons for this rank.', 'mycred-woocommerce-plus' );?></i></p>
			<?php
			
		}

		public function mycred_ranks_meta_callback( $rank ) {

			$settings = array(
				'type'   => mycred_get_post_meta( $rank->ID, 'mycred_discount_type', true ),
				'code'   => mycred_get_post_meta( $rank->ID, 'mycred_coupon_code_rank', true ),
				'amount' => mycred_get_post_meta( $rank->ID, 'mycred_discount_bagde_rank', true )
			);

			if ( empty( $settings['type'] ) ) {
				$settings['type'] = 'fixed';
			}

			if ( empty( $settings['amount'] ) ) {
				$settings['amount'] = '0';
			}

			wp_nonce_field( 'mwp_module_coupons_nonce', 'mwp-coupon-reward-nonce' );

			?>
			<table width="100%">
				<tr>
					<td colspan="10">
						<p style="font-weight: 600;">
							<?php echo esc_html__( 'You can use these settings to reward users on achieving this rank.', 'mycredpartwoo' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<td style="width: 25%"><?php echo esc_html__( 'Discount Type', 'mycredpartwoo' );?></td>
					<td>
					<?php 	
					mycred_create_select_field( 
						array(
		                    'fixed'   => __( 'Fixed Discount', 'mycredpartwoo' ),
		                    'percent' => __( 'Percentage Discount', 'mycredpartwoo' ),
		                ), 
						$settings['type'], 
						array(
		                    'name'  => 'discount[mycred_discount_type]',
		                    'id'    => 'discount-mycred-discount-type',
		                    'class' => 'form-control',
		                    'style' => 'width:425px;'
		                )
					);
					?>
					</td>
				</tr>
				<tr>
					<td><?php echo esc_html__( 'Coupon code', 'mycredpartwoo' ); ?></td>
					<td><input type="text" style="width:425px;" name="discount[mycred_coupon_code_rank]" value="<?php echo esc_html( $settings['code'] ); ?>" />
					</td>
				</tr>
				<tr>
					<td><?php echo esc_html__( 'Amount', 'mycredpartwoo' ); ?></td>
					<td><input type="number" style="width:425px;" name="discount[mycred_discount_bagde_rank]" value="<?php echo esc_html( $settings['amount'] ); ?>" />
					</td>
				</tr>
				<tr>
					<td colspan="10">
						<p style="font-size: 11px;">
							<?php echo esc_html__( 'NOTE: Keep amount 0 or empty in order to disable coupons for this rank.', 'mycredpartwoo' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php
		}

		public function save_rank_coupon_settings( $post_id, $post ) {

			if ( isset( $_POST['mwp-coupon-reward-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mwp-coupon-reward-nonce'] ) ), 'mwp_module_coupons_nonce' ) ) {
				
				if ( ! empty( $_POST['discount'] ) ) {

					$settings = array(
						'mycred_discount_type'       => 'fixed',
						'mycred_coupon_code_rank'    => '',
						'mycred_discount_bagde_rank' => '0'
					);
					
					if ( ! empty( $_POST['discount']['mycred_discount_type'] ) ) {
						$settings['mycred_discount_type'] = sanitize_text_field( wp_unslash( $_POST['discount']['mycred_discount_type'] ) );
					}
					
					if ( ! empty( $_POST['discount']['mycred_coupon_code_rank'] ) ) {
						$settings['mycred_coupon_code_rank'] = sanitize_text_field( wp_unslash( $_POST['discount']['mycred_coupon_code_rank'] ) );
					}
					
					if ( ! empty( $_POST['discount']['mycred_discount_bagde_rank'] ) ) {
						$settings['mycred_discount_bagde_rank'] = absint( $_POST['discount']['mycred_discount_bagde_rank'] );
					}

					foreach ( $settings as $key => $value ) {
						mycred_update_post_meta( $post_id, $key, $value );
					}

				}

			}

		}

		public function save_badge_coupon_settings( $post_id, $post ) {

			if ( isset( $_POST['mwp-coupon-reward-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mwp-coupon-reward-nonce'] ) ), 'mwp_module_coupons_nonce' ) ) {

				if ( ! empty( $_POST['woo_discount'] ) ) {
					
					$badge_coupon_rewards = mycred_sanitize_array( wp_unslash( $_POST['woo_discount'] ) );

					mycred_update_post_meta( $post_id, 'woo_discount', array_values( $badge_coupon_rewards ) );

				}

			}

		}

		public function run_badges_and_ranks_copouns() {

			if ( ! is_user_logged_in() ) {
				return;
			}
			if ( is_admin() ) {
				return;
			}

			// User id and user email.
			$user_id    = get_current_user_id();
			$user_email = get_userdata( $user_id )->user_email;

			if ( function_exists( 'mycred_get_users_badges' ) ) {

				// All badge ids
				$badge_ids = array_keys( mycred_get_users_badges( $user_id ) );

			} else {

				$badge_ids = array();

			}

			if ( function_exists( 'mycred_get_users_rank_id' ) ) {

				// Rank ids
				$rank_ids = mycred_get_users_rank_id( $user_id );

			} else {

				$rank_ids = array();

			}
			// Rank title
			$rank_title = get_the_title( $rank_ids );

			if ( 'yes' == get_option( 'mycred_wooplus_show_ranks' ) ) {

				if ( ! empty( $rank_ids ) ) {

					$args          = array();
					$discount_type = get_post_meta( $rank_ids, 'mycred_discount_type', true );
					$amount        = get_post_meta( $rank_ids, 'mycred_discount_bagde_rank', true );
					$coupon_code   = get_post_meta( $rank_ids, 'mycred_coupon_code_rank', true );

					if ( ! empty( $discount_type ) && ! empty( $amount ) && ! empty( $coupon_code ) && 0 != $amount ) {

						if ( 'percent' ==  $discount_type) {

							$d_amount = $amount . '%';

						} else {

							$currency = get_woocommerce_currency_symbol();
							$d_amount = $currency . $amount;

						}
						/* translators: %s: You have Won a coupon for %s off for achieving %s Rank. */
						$description = sprintf( __( 'You have Won a coupon for %1$s off for achieving %2$s Rank.', 'mycredpartwoo'), $d_amount, $rank_title );

						$mycred_ranks_coupons = get_user_meta( get_current_user_id(), 'mycred_ranks_coupons', true );

						// General info check validation copoun create.
						$create_coupons_run = true;

						$args = array(
							'ranks_or_badges_ids' => $rank_ids,
							'coupon_code'         => $coupon_code,
							'amount'              => $amount,
							'discount_type'       => $discount_type,
							'customer_email'      => $user_email,
							'description'         => $description,
							'type'                => 'rank',
							'level'               => null,
							'individual_use'      => 'no',
							'product_ids'         => '',
							'exclude_product_ids' => '',
							'usage_limit'         => '1',
							'expiry_date'         => '',
							'apply_before_tax'    => 'yes',
							'free_shipping'       => 'no',
						);

						if ( $mycred_ranks_coupons ) {

							foreach ( $mycred_ranks_coupons as $mycred_ranks_coupons_ids ) {

								if ( in_array( $rank_ids, $mycred_ranks_coupons_ids ) ) {

									$create_coupons_run = false;

								}
							}

							if ( true === $create_coupons_run ) {

								// create coupons
								$coupon_id = $this->create_coupons_badges_ranks( $args );

							}
						} else {

							// create coupons
							$coupon_id = $this->create_coupons_badges_ranks( $args );

						}
					}
				}
			}

			if ( 'yes' == get_option( 'mycred_wooplus_show_badges' ) ) {

				if ( 0 != count( $badge_ids ) ) {

					$args = array();

					foreach ( $badge_ids as $badge_id ) {

						// get badge discount array
						$mycred_discount_bagde = get_post_meta( $badge_id, 'woo_discount', true );

						if ( ! $mycred_discount_bagde ) {
							return;
						}

						// get user badge level
						$user_badge_level_reached = get_user_meta( get_current_user_id(), 'mycred_badge' . $badge_id, true );

						// badge object
						$badge = mycred_get_badge( $badge_id );

						// badge level number
						$level_number = $user_badge_level_reached;
						$level_number++;
						// sort user badge level discount

						$mycred_discount_bagde = array_slice( $mycred_discount_bagde, $user_badge_level_reached, 1, true );

						if ( ! empty( $mycred_discount_bagde ) ) {

							// General info check validation copoun create.
							$create_coupons_run = true;

							foreach ( $mycred_discount_bagde as $bagde_value ) {
								$coupon_code   = $bagde_value['mycred_coupon_code_badge'];
								$amount        = $bagde_value['discount_amount'];
								$discount_type = $bagde_value['discount_type'];
							}

							if ( empty( $amount ) || empty( $coupon_code ) && 0 == $amount ) {
								continue; 
							}

							if ( 'percent' == $discount_type ) {

								$d_amount = $amount . '%';

							} else {

								$currency = get_woocommerce_currency_symbol();
								$d_amount = $currency . $amount;

							}
							
							$description = sprintf( 
							/* translators: %1$s: You have Won a coupon for %1$s off for achieving level %2$s %3$s Badge. */
								__( 'You have Won a coupon for %1$s off for achieving level %2$s %3$s Badge.', 'mycredpartwoo'), 
								$d_amount, 
								$level_number, 
								$badge->title 
							);

							$mycred_badges_coupons = get_user_meta( get_current_user_id(), 'mycred_badges_coupons', true );

							$args = array(
								'ranks_or_badges_ids' => $badge_id,
								'coupon_code'         => $coupon_code,
								'amount'              => $amount,
								'discount_type'       => $discount_type,
								'customer_email'      => $user_email,
								'description'         => $description,
								'type'                => 'badge',
								'level'               => $user_badge_level_reached,
								'individual_use'      => 'no',
								'product_ids'         => '',
								'exclude_product_ids' => '',
								'usage_limit'         => '1',
								'expiry_date'         => '',
								'apply_before_tax'    => 'yes',
								'free_shipping'       => 'no',
							);

							if ( $mycred_badges_coupons ) {

								foreach ( $mycred_badges_coupons as $mycred_badges_coupons_ids ) {

									if ( $badge_id == $mycred_badges_coupons_ids['badge_id'] &&
									$user_badge_level_reached == $mycred_badges_coupons_ids['level'] ) {

										$create_coupons_run = false;

									}
								}
								if ( true === $create_coupons_run ) {

									// create_coupons
									$coupon_id = $this->create_coupons_badges_ranks( $args );

								}
							} else {

								// create_coupons
								$coupon_id = $this->create_coupons_badges_ranks( $args );

							}
						}
					}
				}
			}

		}


		public function create_coupons_badges_ranks( $args ) {

			/**
			* Filter mycred_wooplus_modify_coupon
			* 
			* @since 1.0
			**/
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



		public function my_account_copouns_url_rewrite() {

			add_rewrite_endpoint( 'my-coupons', EP_ROOT | EP_PAGES );
		}

		public function copouns_query_vars( $vars ) {

			$vars[] = 'my-coupons';

			return $vars;
		}



		public function add_copouns_tab( $items ) {

			$new_items               = array();
			$new_items['my-coupons'] = esc_html__( 'My coupons', 'mycredpartwoo' );

			// Add the new item after `edit-account`.
			return $this->my_custom_insert_after_helper( $items, $new_items, 'edit-account' );
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



		public function copouns_shortcode_call() {

			// echo '<h3>'. __( 'My Coupons', 'mycredpartwoo' ) . '</h3>';

			echo do_shortcode( '[mycred_badges_ranks_coupons type=""]' );

		}

		public function mycred_badges_ranks_coupons_lists( $atts, $content = '' ) {
			
			$coupons_settings = shortcode_atts(
				array(
					'type' => 'all',
				),
				$atts
			);

			$type['type'] = esc_attr( $coupons_settings['type'] );

			return $this->mycred_badges_ranks_coupons_data( $type );
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

			/**
			* Filter wooplus_badges_ranks_coupons_type
			* 
			* @since 1.0
			**/
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
					<tr class="<?php echo esc_attr( $this->copouns_status( $coupon->ID ) ); ?>">
					
						<td class=""><?php echo esc_attr( $sno ); ?></td>
						
						<td class="coupon_code">
							<span class="copoun_code_style">
							<?php echo esc_attr( $coupon->post_title ); ?>
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
							<?php echo esc_attr( $coupon->post_excerpt ); ?>
						</td>
						
						<td class="">
						<?php

						if ( '' != get_post_meta( $coupon->ID, 'date_expires', true ) ) {

							$date_expires = gmdate( 'd/m/Y', get_post_meta( $coupon->ID, 'date_expires', true ) );
							echo esc_attr( $date_expires );

						} else {
								echo '-';
						}
						?>
						</td>
						
						<td class="">
						<?php echo esc_attr( $this->copouns_status( $coupon->ID ) ); ?>
						</td>
						
					</tr>
					<?php
					$sno++; }
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

	$MyCred_Woo_Badge_Rank_Copouns = new MyCred_Woo_Badge_Rank_Copouns();
endif;
