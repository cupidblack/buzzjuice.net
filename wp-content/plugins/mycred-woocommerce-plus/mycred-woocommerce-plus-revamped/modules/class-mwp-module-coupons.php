<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Coupons_Module' ) ) :
	class MWP_Coupons_Module extends MWP_Module {
		
		public function __construct() {

			parent::__construct( 'MWP_Coupons_Module', array(
				'module_name' => 'mwp_coupons',
				'defaults'    => array(
					'rank' => 0,
					'badge' => 0
				),
				'add_tab'     => true,
				'title'       => __('Coupons','mycred-woocommerce-plus'),
				'icon'        => 'dashicons-tickets-alt',
				'tab_pos'     => 10
			) );
			
		}

		public function module_init() {
			
			if ( ! isset( $this->prefs['module_enable'] ) ) {
				return;
			}

			add_action( 'mycred_admin_enqueue',    		  		   array( $this, 'register_admin_assets' ) );
			add_action( 'mycred_front_enqueue', 					array( $this, 'register_front_assets' ) );
			add_action( 'admin_init', 				  		   array( $this, 'add_metabox' ) );
			
			if ( $this->prefs['badge'] && defined( 'MYCRED_BADGE_KEY' ) ) {
				add_action( 'save_post_' . MYCRED_BADGE_KEY , 		   array( $this, 'save_badge_coupon_settings' ), 10, 2 );
				add_action( 'mycred_after_badge_assign',      		   array( $this, 'badge_reward' ), 10, 3 );
				
			}	
			if ( $this->prefs['rank'] && defined( 'MYCRED_RANK_KEY' ) ) {
				add_action( 'save_post_' . MYCRED_RANK_KEY ,  		   array( $this, 'save_rank_coupon_settings' ), 10, 2 );
				add_action( 'mycred_user_got_promoted',       		   array( $this, 'rank_reward' ), 10, 4 );
			}
			
			add_action( 'woocommerce_account_my-coupons_endpoint', array( $this, 'render_coupons_shortcode' ) );
			add_filter( 'woocommerce_account_menu_items', 		   array( $this, 'add_coupons_tab' ) );
			add_action( 'init',									   array( $this, 'url_rewrite_for_coupons_tab' ) );
			add_filter( 'query_vars', 						       array( $this, 'coupons_tab_query_vars' ) );
			add_shortcode( 'mycred_badges_ranks_coupons', 		   array( $this, 'render_badges_ranks_coupons' ) );
			add_shortcode( 'mycred_coupon_code_generator', 		   array( $this, 'coupon_code_generator' ) );

			add_action( 'wp_ajax_mycred_coupon_ajax',array( $this, 'mycred_coupon_ajax' ) );
			add_action( 'wp_ajax_nopriv_mycred_coupon_ajax',array( $this, 'mycred_coupon_ajax' ) );

			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'mycred_rank_apply_discount_in_cart' ) );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'mycred_bypass_dokan_fixed_cart_validation'), 10, 2 );
			add_filter( 'woocommerce_coupon_error', array( $this, 'mycred_override_dokan_coupon_error'), 10, 3 );



		}
		
		public function mycred_override_dokan_coupon_error( $err, $err_code, $coupon ) {
			// Check if it's our myCred coupon and Dokan error
			if ( get_post_meta( $coupon->get_id(), 'mycred_coupon_generator', true ) && 
				 $err_code === WC_Coupon::E_WC_COUPON_INVALID_FIXED_CART ) {

				// Force apply the coupon by returning false (no error)
				return false;
			}

			return $err;
		}
		
		public function mycred_bypass_dokan_fixed_cart_validation( $valid, $coupon ) {
			// Only bypass for myCred generated coupons
			if ( get_post_meta( $coupon->get_id(), 'mycred_coupon_generator', true ) ) {
				// Remove Dokan's validation hook temporarily
				if ( class_exists( 'Dokan_Pro' ) ) {
					remove_filter( 'woocommerce_coupon_is_valid', array( 'Dokan_Pro_Coupon', 'validate_coupon' ), 10 );
				}
				return true;
			}

			return $valid;
		}

		public function mycred_rank_apply_discount_in_cart() {

			global $woocommerce;
			if( ! $this->prefs['rank'] && ! class_exists( 'myCRED_Ranks_Module' ) ) {
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

		public function mycred_coupon_ajax() {
    
			if ( ! isset( $_POST['token'] ) || ( isset( $_POST['token'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['token'] ), 'mycred-coupon-new' ) ) ) {
				exit( 'Not Authorized' );
			}

			$options = get_option('mycred_pref_woo');
			$mwp_coupons = $options['mwp_coupons'];
			$exchange_rate = isset($mwp_coupons['exchange_rate']) ? floatval($mwp_coupons['exchange_rate']) : 1; 

			$mycred = mycred( $mwp_coupons['point_type'] );
			$user_id = get_current_user_id();
			$type = $mwp_coupons['point_type'];

			if ( $mycred->exclude_user( $user_id ) ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to use this feature.', 'mycred-woocommerce-plus' ) ) );
			}

			$balance = $mycred->get_users_balance( $user_id );

			if ( isset( $_POST['amount'] ) ) {
				$amount = abs( sanitize_text_field( wp_unslash( (float) $_POST['amount'] ) ) );
			}

			if ( $amount == $mycred->zero() ) {
				wp_send_json_error( array( 'message' => __( 'Amount can not be zero.', 'mycred-woocommerce-plus' ) ) );
			}

			if ( $amount > $balance ) {
				wp_send_json_error( array( 'message' => __( 'Insufficient funds. Please try a lower amount.', 'mycred-woocommerce-plus' ) ) );
			}

			if (empty($mwp_coupons['coupon_max']) || $mwp_coupons['coupon_max'] == 0) {
				$mwp_coupons['coupon_max'] = $balance ;
			}

			if ( $amount > $mwp_coupons['coupon_max'] ) {
				wp_send_json_error( array( 'message' => __( 'The amount can not be greater than the maximum amount.', 'mycred-woocommerce-plus' ) ) );
			}

			if ( $amount < $mwp_coupons['min'] ) {
				wp_send_json_error( array( 'message' => __( 'The amount can not be less than the minimum amount.', 'mycred-woocommerce-plus' ) ) );
			}

			// Apply exchange rate
			$coupon_value = $amount * $exchange_rate;

			$code = strtolower( wp_generate_password( 12, false, false ) );
			$new_coupon_id = wp_insert_post(
				array(
					'post_title'   => $code,
					'post_content' => '',
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'shop_coupon',
				)
			);

			// Deduct the original points amount
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

			$balance = $mycred->number( $balance - $amount );

			// ALWAYS CREATE FIXED CART COUPON (regardless of Dokan)
			update_post_meta( $new_coupon_id, 'discount_type', 'fixed_cart' );
			update_post_meta( $new_coupon_id, 'coupon_amount', $coupon_value );
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
			update_post_meta( $new_coupon_id, 'minimum_amount', '' );
			update_post_meta( $new_coupon_id, 'customer_email', array() );
			update_post_meta( $new_coupon_id, 'user_id', $user_id );

			// Mark as myCred coupon for hook identification
			update_post_meta( $new_coupon_id, 'mycred_coupon_generator', true );

			// For Dokan compatibility - add required meta data
			if ( class_exists( 'Dokan_Pro' ) ) {
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

			if ( ! empty( $mwp_coupons['email_field'] ) && ! empty( $_POST['email'] ) ) {
				$customer_email = sanitize_email( $_POST['email'] );
				update_post_meta( $new_coupon_id, 'customer_email', array ( $customer_email ) );
			}

			wp_send_json_success( array(
				'balance'       => $balance,
				'coupon_code'   => $code,
			) );
		}

		public function coupon_code_generator( $atts, $content = '' ){
			wp_enqueue_style( 'mwp-style' );	
			$options = get_option('mycred_pref_woo');
			$mwp_coupons = $options['mwp_coupons'];

			$coupons_settings = shortcode_atts(
				array(
					'point_type'    => $mwp_coupons['point_type'],
					'min'           => $mwp_coupons['min'],
					'exchange_rate'           => $mwp_coupons['exchange_rate'],
					'coupon_max'    => $mwp_coupons['coupon_max'],
					'coupon_title'  => $mwp_coupons['coupon_title'],
					'coupon_desc'   => $mwp_coupons['coupon_desc'],
					'coupon_button' => $mwp_coupons['coupon_button'],
					'email_field'   => $mwp_coupons['email_field'],
					'required_email'   => $mwp_coupons['required_email']
				),
				$atts
			);
			if ( 0 !== $mwp_coupons['enable'] ) {
				ob_start();
				$this->load_template( 
					'coupon_generator', 
					'coupons-module/coupon-generator.php',
					array( 'settings' => $coupons_settings ) 
				);
				return ob_get_clean();
			}
		}

		public function register_admin_assets() {

			wp_register_script( 'mwp-coupon-reward-script', plugins_url( 'assets/js/coupon-reward-script.js', MYCRED_WOOPLUS_THIS ), array( 'jquery', 'wp-i18n' ), MYCRED_WOOPLUS_VERSION );
		}

		public function register_front_assets() {

			wp_register_script( 'mwp-coupon-reward-script', plugins_url( 'assets/js/coupon-reward-script.js', MYCRED_WOOPLUS_THIS ), array( 'jquery', 'wp-i18n' ), MYCRED_WOOPLUS_VERSION );
			$post_id = get_the_ID();
			$post_content = isset(get_post($post_id)->post_content) ? get_post($post_id)->post_content : '';
			$check = has_shortcode($post_content, 'mycred_coupon_code_generator');


			if ( true == $check || has_shortcode($post_content, 'woocommerce_my_account') ) {

				$user_id = get_current_user_id();

				$options = get_option('mycred_pref_woo');
				$mwp_coupons = $options['mwp_coupons'];

				$mycred  = mycred( $mwp_coupons['point_type'] );

				if ( $mycred->exclude_user( $user_id ) ) {
					return;
				}

				wp_localize_script(
					'mwp-coupon-reward-script',
					'myCREDCoupon',
					array(
						'ajaxurl'  => admin_url( 'admin-ajax.php' ),
						'token'    => wp_create_nonce( 'mycred-coupon-new' )
					)
				);

				wp_enqueue_script( 'mwp-coupon-reward-script' );

			}
		}

		public function admin_settings( $core ) {
			wp_enqueue_style( 'mwp-admin-style' );
			$settings = $core->woocommerce[ $this->module_name ];

			$this->load_template( 
				'coupon_reward_settings', 
				'coupons-module/admin-settings.php',
				array( 'settings' => $settings ) 
			);
		}

		public function sanitize_settings( $new_data, $data, $core ) {
			$new_data[ $this->module_name ]['enable'] = !empty( $data[ $this->module_name ]['enable'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['module_enable'] = !empty( $data[ $this->module_name ]['module_enable'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['badge'] = !empty( $data[ $this->module_name ]['badge'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['rank'] = !empty( $data[ $this->module_name ]['rank'] ) ? 1 : 0;
			$new_data[ $this->module_name ]['point_type'] = !empty( $data[ $this->module_name ]['point_type'] ) ? $data[ $this->module_name ]['point_type'] : 'mycred_default';
			$new_data[ $this->module_name ]['min'] = !empty( $data[ $this->module_name ]['min'] ) ? $data[ $this->module_name ]['min'] : 1;
			$new_data[ $this->module_name ]['exchange_rate'] = !empty( $data[ $this->module_name ]['exchange_rate'] ) ? $data[ $this->module_name ]['exchange_rate'] : 1;

			$new_data[ $this->module_name ]['coupon_max'] = !empty( $data[ $this->module_name ]['coupon_max'] ) ? $data[ $this->module_name ]['coupon_max'] : '';
			$new_data[ $this->module_name ]['coupon_title'] = !empty( $data[ $this->module_name ]['coupon_title'] ) ? $data[ $this->module_name ]['coupon_title'] : 'Coupon';
			$new_data[ $this->module_name ]['coupon_desc'] = !empty( $data[ $this->module_name ]['coupon_desc'] ) ? $data[ $this->module_name ]['coupon_desc'] : '';
			$new_data[ $this->module_name ]['coupon_button'] = !empty( $data[ $this->module_name ]['coupon_button'] ) ? $data[ $this->module_name ]['coupon_button'] : 'Create Coupon';
			$new_data[ $this->module_name ]['email_field'] = !empty( $data[ $this->module_name ]['email_field'] ) ? $data[ $this->module_name ]['email_field'] : '';
			$new_data[ $this->module_name ]['required_email'] = !empty( $data[ $this->module_name ]['required_email'] ) ? $data[ $this->module_name ]['required_email'] : '';
			return $new_data;
		}

		public function add_metabox() {

			// Ranks metabox
			if ( $this->prefs['rank'] && class_exists( 'myCRED_Ranks_Module' ) ) {

				add_meta_box(
					'mycred_ranks_coupons',
					__( 'Reward Coupon', 'mycred-woocommerce-plus' ),
					array( $this, 'rank_metabox' ),
					'mycred_rank',
					'normal',
					'low'
				);

				add_meta_box(
					'mycred_ranks_product_discount',
					__( 'Fixed Discount for each product', 'mycredpartwoo' ),
					array( $this, 'mycred_ranks_product_discount_callback' ),
					'mycred_rank',
					'normal',
					'low'
				);

			}

			// Badges metabox
			if ( $this->prefs['badge'] && class_exists( 'myCRED_Badge_Module' ) ) {

				add_meta_box(
					'mycred_badge_coupons',
					__( 'Reward Coupon', 'mycred-woocommerce-plus' ),
					array( $this, 'badge_metabox' ),
					'mycred_badge',
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
						<select style="width:425px;" name="mycred_rank[mycred_product_discount_type]" >
							<option value='percent'<?php if ( 'percent' == $discount_type ) { echo 'selected'; } ?>>
								<?php echo esc_html__( 'Percentage  Discount', 'mycredpartwoo' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'Percentage', 'mycredpartwoo' ); ?></td>
						<td><input type="number" style="width:425px;" name="mycred_rank[mycred_product_discount_amount]" value="<?php echo esc_html( get_post_meta( $rank->ID, 'mycred_product_discount_amount', true ) ); ?>" />
						</td>
					</tr>
				</table>
				<?php
			}

			public function rank_metabox( $rank ) {

				$settings = array(
					'type'   => mycred_get_post_meta( $rank->ID, 'mycred_discount_type', true ),
					'code'   => mycred_get_post_meta( $rank->ID, 'mycred_coupon_code_rank', true ),
					'amount' => mycred_get_post_meta( $rank->ID, 'mycred_discount_bagde_rank', true ),
					'mycred_product_discount_type' => mycred_get_post_meta( $rank->ID, 'mycred_product_discount_type', true ),
					'mycred_product_discount_amount' => mycred_get_post_meta( $rank->ID, 'mycred_product_discount_amount', true )
				);

				if ( empty( $settings['type'] ) ) {
					$settings['type'] = 'fixed';
				}

				if ( empty( $settings['amount'] ) ) {
					$settings['amount'] = '0';
				}

				$this->load_template( 
					'rank_coupon_reward_metabox', 
					'coupons-module/rank-metabox.php',
					array( 
						'settings' => $settings,
						'rank'     => $rank 
					) 
				);

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

			public function badge_metabox( $badge ) {

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

				$this->load_template( 
					'badge_coupon_reward_metabox',
					'coupons-module/badge-metabox.php',
					array(
						'settings'     => $settings,
						'badge'		   => $badge,
						'badge_levels' => $badge_levels
					)
				);

			}

			public function save_badge_coupon_settings( $post_id, $post ) {

				if ( isset( $_POST['mwp-coupon-reward-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mwp-coupon-reward-nonce'] ) ), 'mwp_module_coupons_nonce' ) ) {

					if ( ! empty( $_POST['woo_discount'] ) ) {

						$badge_coupon_rewards = mycred_sanitize_array( wp_unslash( $_POST['woo_discount'] ) );

						mycred_update_post_meta( $post_id, 'woo_discount', array_values( $badge_coupon_rewards ) );

					}

				}

			}

			public function rank_reward( $user_id, $rank_id, $results, $point_type ) {

				if ( ! $this->prefs['rank'] ) {
					return;
				}

				$users_ranks_coupons = get_user_meta( $user_id, 'mycred_ranks_coupons', true );

				if ( empty( $users_ranks_coupons ) ) {

					$users_ranks_coupons = array();

				}

				if ( $this->has_rewarded_rank_reward( $user_id, $rank_id, $users_ranks_coupons ) ) {
					return;
				}

				$discount_type = get_post_meta( $rank_id, 'mycred_discount_type', true );
				$coupon_code   = get_post_meta( $rank_id, 'mycred_coupon_code_rank', true );
				$coupon_amount = get_post_meta( $rank_id, 'mycred_discount_bagde_rank', true );

				if ( empty( $discount_type ) || empty( $coupon_code ) || empty( $coupon_amount ) ) {
					return;
				}

				$dispaly_amount = $coupon_amount . '%';

				if ( $discount_type == 'fixed' ) {

					$currency 		= get_woocommerce_currency_symbol();
					$dispaly_amount = $currency . $coupon_amount;

				}

				$args = array(
					'rank_or_badge_id'    => $rank_id,
					'coupon_code'         => $coupon_code,
					'amount'              => $coupon_amount,
					'discount_type'       => $discount_type,
					'customer_email'      => get_userdata( $user_id )->user_email,
					'description'         => sprintf( __( 'You have Won a coupon for %1$s off for achieving %2$s Rank.', 'mycred-woocommerce-plus' ), $dispaly_amount, get_the_title( $rank_id ) ),
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

				$coupon_id = $this->create_reward_coupon( $args, $user_id );

				if ( $coupon_id ) {

					array_push(
						$users_ranks_coupons,
						array(
							'rank_id'   => $rank_id,
							'coupon_id' => $coupon_id,
						)
					);

					update_user_meta( $user_id, 'mycred_ranks_coupons', $users_ranks_coupons );

				}

			}

			public function badge_reward( $user_id, $badge_id, $new_level_id ) {

				if ( ! $this->prefs['badge'] ) {
					return;
				}

				$users_badges_coupons = get_user_meta( $user_id, 'mycred_badges_coupons', true );

				if ( empty( $users_badges_coupons ) ) {

					$users_badges_coupons = array();

				}

				if ( $this->has_rewarded_badge_level_reward( $user_id, $badge_id, $new_level_id, $users_badges_coupons ) ) {
					return;
				}

				$badge_coupon_reward = get_post_meta( $badge_id, 'woo_discount', true );

				if ( 
					empty( $badge_coupon_reward[ $new_level_id ] ) || 
					empty( $badge_coupon_reward[ $new_level_id ]['discount_amount'] ) || 
					empty( $badge_coupon_reward[ $new_level_id ]['discount_type'] ) ||
					empty( $badge_coupon_reward[ $new_level_id ]['mycred_coupon_code_badge'] )
				) {
					return;
				}

				$discount_type = $badge_coupon_reward[ $new_level_id ]['discount_type'];
				$coupon_code   = $badge_coupon_reward[ $new_level_id ]['mycred_coupon_code_badge'];
				$coupon_amount = $badge_coupon_reward[ $new_level_id ]['discount_amount'];

				$dispaly_amount = $coupon_amount . '%';

				if ( $discount_type == 'fixed' ) {

					$currency 		= get_woocommerce_currency_symbol();
					$dispaly_amount = $currency . $coupon_amount;

				}

				$args = array(
					'rank_or_badge_id'    => $badge_id,
					'coupon_code'         => $coupon_code,
					'amount'              => $coupon_amount,
					'discount_type'       => $discount_type,
					'customer_email'      => get_userdata( $user_id )->user_email,
					'description'         => sprintf( __( 'You have Won a coupon for %1$s off for achieving level %2$s %3$s Badge.', 'mycred-woocommerce-plus' ), $dispaly_amount, ( $new_level_id + 1 ), get_the_title( $badge_id ) ),
					'type'                => 'badge',
					'level'               => $new_level_id,
					'individual_use'      => 'no',
					'product_ids'         => '',
					'exclude_product_ids' => '',
					'usage_limit'         => '1',
					'expiry_date'         => '',
					'apply_before_tax'    => 'yes',
					'free_shipping'       => 'no',
				);

				$coupon_id = $this->create_reward_coupon( $args, $user_id );

				if ( $coupon_id ) {

					array_push(
						$users_badges_coupons,
						array(
							'badge_id'  => $badge_id,
							'coupon_id' => $coupon_id,
							'level'     => $new_level_id,
						)
					);

					update_user_meta( $user_id, 'mycred_badges_coupons', $users_badges_coupons );

				}

			}

			public function has_rewarded_rank_reward( $user_id, $rank_id, $users_ranks_coupons ) {

				$rewarded = false; 

				foreach ( $users_ranks_coupons as $key => $value ) {

					if( $value['rank_id'] == $rank_id ) {

						$rewarded = true; 
						break;

					}

				}

				return $rewarded;

			}

			public function has_rewarded_badge_level_reward( $user_id, $badge_id, $level_id, $users_badges_coupons ) {

				$rewarded = false; 

				foreach ( $users_badges_coupons as $key => $value ) {

					if( $value['badge_id'] == $badge_id && $value['level'] == $level_id ) {

						$rewarded = true; 
						break;

					}

				}

				return $rewarded;

			}

			public function create_reward_coupon( $args, $user_id ) {

			/**
			 * Filter.
			 * 
			 * @since 1.0.3.2
			 */
			$args = apply_filters( 'mycred_wooplus_modify_coupon', $args );

			$rank_or_badge_id    = $args['rank_or_badge_id'];
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

			$coupon = array(
				'post_title'   => $coupon_code . '_' . get_current_user_id(),
				'post_content' => '',
				'post_excerpt' => $description,
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_type'    => 'shop_coupon',
			);

			$new_coupon_id = wp_insert_post( $coupon );

			if ( ! is_wp_error( $new_coupon_id ) && ! empty( $new_coupon_id ) ) {
				
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
				update_post_meta( $new_coupon_id, 'user_id', $user_id );

				if ( ! empty( $level ) ) {
					update_post_meta( $new_coupon_id, 'level_id', $level );
				}

				if (function_exists('wc_add_notice')) {
					wc_add_notice($description, 'notice');
				}
			}
			else {

				$new_coupon_id = 0;

			}
			
			return $new_coupon_id;
		}

		public function render_coupons_shortcode() {

			echo do_shortcode( '[mycred_badges_ranks_coupons]' );
		}

		public function add_coupons_tab( $items ) {

			$new_items = array(
				'my-coupons' => __( 'My Coupons', 'mycred-woocommerce-plus' )
			);

			// Add the new item after `edit-account`.
			return $this->add_menu_after( $items, $new_items, 'edit-account' );
		}

		public function render_badges_ranks_coupons( $atts, $content = '' ) {
			
			$coupons_settings = shortcode_atts(
				array(
					'type' => 'all'
				),
				$atts
			);

			$coupons_type = sanitize_text_field( $coupons_settings['type'] );

			return $this->render_coupons( esc_attr( $coupons_type ) );
		}

		public function render_coupons( $coupon_type ) {
			
			$coupon_type = apply_filters( 'wooplus_badges_ranks_coupons_type', $coupon_type );

			if ( empty( $coupon_type ) || ! in_array( $coupon_type, array( 'all', 'badge', 'rank' ) ) ) {
				$coupon_type = 'all'; 
			}

			$meta_query_args = array(
				array(
					'key'     => 'user_id',
					'value'   => get_current_user_id(),
					'compare' => '='
				)
			);

			if ( $coupon_type != 'all' ) {
				
				array_push(
					$meta_query_args,
					array(
						'key'     => 'reference_type',
						'value'   => $coupon_type,
						'compare' => '='
					)
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
			
			$this->load_template( 
				'badges_ranks_coupons_shortcode',
				'coupons-module/frontend-coupons-table.php',
				array(
					'coupons'     => $coupons,
					'coupon_type' => $coupon_type 
				)
			);
		}

		public function url_rewrite_for_coupons_tab() {
			add_rewrite_endpoint( 'my-coupons', EP_ROOT | EP_PAGES );
		}

		public function coupons_tab_query_vars( $vars ) {

			$vars[] = 'my-coupons';

			return $vars;
		}

	}
endif;

function mwp_load_coupon_module() {

	$module = new MWP_Coupons_Module();
	$module->load();

}
mwp_load_coupon_module();