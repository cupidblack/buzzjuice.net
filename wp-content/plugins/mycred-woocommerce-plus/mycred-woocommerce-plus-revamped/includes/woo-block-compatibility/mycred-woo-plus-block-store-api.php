<?php
/**
 * MyCred_Woo_Plus_Extend_Store_Endpoint class
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

class MyCred_Woo_Plus_Extend_Store_Endpoint {

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'mycred_wooplus';

	/**
	 * Initialize.
	 */
	public function init() {
		
		add_filter( 'woocommerce_get_item_data', array( $this, 'woocommerce_get_item_data' ), 10, 2 );

		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( $this, 'extend_cart_block_data' ),
				'schema_callback' => array( $this, 'extend_cart_block_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);

	}

	public function woocommerce_get_item_data( $item_data, $cart_item ) {


		$is_cart = false;
		$is_checkout = false;
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$request_url = sanitize_text_field( $_SERVER['HTTP_REFERER'] );
			$is_cart = strpos($request_url, 'cart') > 0 ? true : false; 
			$is_checkout = strpos($request_url, 'checkout') > 0 ? true : false;
		}

		$product = wc_get_product( $cart_item['product_id'] );

		if ( $product->is_type( 'variable' ) ) {
			$mycred_rewards = get_post_meta( $cart_item['variation_id'], '_mycred_reward', true );
		} else {
			$mycred_rewards = get_post_meta( $cart_item['product_id'], 'mycred_reward', true );
		}

		$options = get_option('mycred_pref_woo');
		$settings = $options['mwp_display_reward'];

		if ( $mycred_rewards ) {
			if ( 
			    ( ( $is_cart || is_cart() ) && 1 == $settings['cart_product_meta'] ) || 
			    ( ( $is_checkout || is_checkout() ) && 1 == $settings['checkout_product_meta'] ) 
			) {
				
				foreach ( $mycred_rewards as $mycred_reward_key => $mycred_reward_value ) {
					$is_plural_reward = ( $mycred_reward_value < 2 );
					
					$value = '<span class="reward_span">' . $mycred_reward_value . ' ' . mycred_get_point_type_name( $mycred_reward_key, $is_plural_reward ) . '</span>';

					$item_data[] = array(
						'key'     => '<span class="reward_span">' . __( 'Earn', 'mycred-woocommerce-plus' ) . '</span>',
						'value'   => $value,
						'display' => '',
					);
				}
			}
		} 

		return $item_data;
	}

	/**
	 * Register myCred WooCommerce data into cart/checkout endpoint.
	 *
	 * @return array $item_data Registered data or empty array if condition is not satisfied.
	 */
	public static function extend_cart_block_data() {
		if( ! WC()->session->cart_totals ){
			return;
		}
		$options = get_option('mycred_pref_woo');
		$mycred_partial_payment = $options['mwp_partial_payments'];

		if ( ! isset( $mycred_partial_payment ) || empty( $mycred_partial_payment ) ) {
			return $item_data;
		}

		$user_id  = get_current_user_id();
		$point_types = mycred_get_types(true);

		foreach( $point_types as $type_key => $type_label ) {
			$mycred = mycred( $type_key );
			if ( $mycred->exclude_user( $user_id ) ) {
				return $item_data;
			}

			$total = mycred_part_woo_get_total($mycred_partial_payment);
			$balance = $mycred->get_users_balance( $user_id );

			$item_data['mycred_total_balance'] = $mycred->format_creds( 0 );

			$max     = $mycred->number( $total / ! empty( $mycred->core['partial_payment_settings']['exchange_rate'] ) ? $mycred->core['partial_payment_settings']['exchange_rate'] : 1 );

			$mycred_partial_payment['max'] = $max;
			$mycred_partial_payment['min'] = ( ( $mycred->core['partial_payment_settings']['mwp_min'] > 0 ) ? $mycred->core['partial_payment_settings']['mwp_min']: 0 );

			if ( isset( WC()->session->cart_totals ) && WC()->session->cart_totals ) {
				$cart_total_in_points = $mycred->number( ( WC()->session->cart_totals['total'] / $mycred->core['partial_payment_settings']['exchange_rate'] ) );
			}

			$item_data['low_balance']	= $balance < $cart_total_in_points ? 'low-balance' : '';
			if ( $cart_total_in_points ) {
				$item_data['cart_total'] = $mycred->format_creds( 0 );
			} else {
				$item_data['cart_total'] = $mycred->format_creds( 0 );
			}
			$mycred_partial_payment['step'] = ( isset( $mycred_partial_payment['step'] ) && '' != $mycred_partial_payment['step'] && $mycred_partial_payment['step'] > 0 ) ? $mycred->number( $mycred_partial_payment['step'] )  : $mycred->number( 1 );
			$all_point_types = mycred_get_types(true);
			$enabled_point_types = [];

			foreach ( $all_point_types as $key => $label ) {
				$mycred = mycred( $key );
				if ( isset($mycred->core['partial_payment_settings']['enable_pointtype']) && $mycred->core['partial_payment_settings']['enable_pointtype'] == 1 ) {
					$enabled_point_types[$key] = $label;
				}
			}
			
			$item_data['available_point_types'] 			= $enabled_point_types;
			$item_data['mycred_partial_payment_possible'] 	= mycred_partial_payment_possible();
			$item_data['mycred_partial_payment_check']      = $mycred_partial_payment['enable'];
			$item_data['mycred_partial_payments_woo']   	= $mycred_partial_payment;
			$item_data['spanMin'] 							= $mycred->format_creds( $mycred_partial_payment['min'] );
			$item_data['mycred_info_notice_content']		= self::mycred_wooplus_credit_info_notice();
			
			return $item_data;
			
		}
	}

	/**
	 * Register myCred WooCommerce schema into cart/checkout endpoint.
	 *
	 * @return array Registered schema.
	 */
	public static function extend_cart_block_schema() {
		return array(
			'mycred_total_balance'	=> array(
				'description' 			=> __( 'Total Balance of myCred Points', 'mycred-woocommerce' ),
				'type'        			=> array( 'string', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
			'mycred_partial_payments_woo'	=> array(
				'description' 			=> __( 'An array of settings of myCred WooCommerce Partial Payment', 'mycred-woocommerce' ),
				'type'        			=> array( 'array', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
			'mycred_info_notice_content'	=> array(
				'description' 			=> __( 'Display reward content of notice at Cart & Checkout', 'mycred-woocommerce' ),
				'type'        			=> array( 'string', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			)
			
		);
	}

	public static function mycred_wooplus_credit_info_notice() {

		$mycred         = new myCRED_Settings();
		$decimal_format = $mycred->format['decimals'];

		$total_reward_point = array();
		$message            = '';
		
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			// var_dump($cart_item);

			$product = wc_get_product( $cart_item['product_id'] );
			if ( $product->is_type( 'variable' ) ) {
				$mycred_rewards = get_post_meta( $cart_item['variation_id'], '_mycred_reward', true );
			} else {
				$mycred_rewards = get_post_meta( $cart_item['product_id'], 'mycred_reward', true );
			}
			if ( $mycred_rewards ) {

				foreach ( $mycred_rewards as $mycred_reward_key => $mycred_reward_value ) {

					$mycred_hook_reward_value = get_hooks_rewards_for_product( $product->get_id(), $mycred_reward_key );

					$calculated_reward_value = $mycred_reward_value * $cart_item['quantity'];

					$total_reward_value = $calculated_reward_value + $mycred_hook_reward_value;

					if ( isset( $total_reward_point[ $mycred_reward_key ] ) ) {

						$total_reward_point[ $mycred_reward_key ]['total'] = $total_reward_point[ $mycred_reward_key ]['total'] + $mycred_reward_value * $cart_item['quantity'];

					} else {
						
						$total_reward_point[ $mycred_reward_key ] = array(
							'name'  => $mycred_reward_key,
							'total' => $total_reward_value,
						);

					}

				}

			} else {
				$point_types = mycred_get_types(true);
				foreach( $point_types as $type_key => $type_label ) {
					$mycred_hook_reward_value = get_hooks_rewards_for_product( $product->get_id(), $type_key ); 
					if( $mycred_hook_reward_value > 0 ) {
						$total_reward_point[ $type_key ] = array(
							'name'  => $type_key,
							'total' => $mycred_hook_reward_value,
						);	
					}
				}
			}
		}


		

		$message .= __( 'Earn ', 'mycred-woocommerce-plus
			' );
		$i        = 1;
		$count    = count( $total_reward_point );

		if ( ! empty( $total_reward_point ) ) {
			foreach ( $total_reward_point as $mycred_reward_key => $mycred_reward_value ) {

				$mycred = mycred( $mycred_reward_key );

				if ( 1 == $count ) {
					$message .= $mycred->format_creds( $mycred_reward_value['total'] ) . ' ' . $mycred->plural();
				} else {
					if ( $i < $count ) {
						$message .= $mycred->format_creds( $mycred_reward_value['total'] ) . ' ' . $mycred->plural() . ', ';
					} else {
						$message .= ' and ' . $mycred->format_creds( $mycred_reward_value['total'] ) . ' ' . $mycred->plural();
					}
				}

				$i++;

			}
		}

		wc_clear_notices();

		if ( ! empty( $total_reward_point ) ) {
			return $message;
		}
	}
}
