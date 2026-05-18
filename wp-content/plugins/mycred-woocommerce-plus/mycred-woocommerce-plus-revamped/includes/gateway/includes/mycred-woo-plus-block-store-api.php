<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

class myCred_Woo_Plus_Extends_Store_Endpoint {

	const IDENTIFIER = 'mycred_plus';
	private $gateway;

	public static function init() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( __CLASS__, 'extend_checkout_block_data' ),
				'schema_callback' => array( __CLASS__, 'extend_checkout_block_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}
	
	public static function extend_checkout_block_data() {

		if ( ! is_user_logged_in() ) {
			return array();
		}
	
		global $woocommerce;
	
		$cart_total = '';
		if ( isset( WC()->session->cart_totals ) && WC()->session->cart_totals ) {
			$cart_total = WC()->session->cart_totals['total'];
		}
	
		$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		if ( empty( $available_gateways ) ) {
			return array();
		}
	
		$item_data = array();
		$formatted_balance_arr = array();
	
		foreach ( $available_gateways as $gateway_id => $gateway ) {
	
			if ( strpos( $gateway_id, 'mycred' ) !== false ) {
	
				$point_types = mycred_get_types( true );
	
				foreach ( $point_types as $point_type => $type_label ) {
	
					$mycred  = mycred( $point_type );
					$user_id = get_current_user_id();
	
					if ( $mycred->exclude_user( $user_id ) ) {
						continue;
					}
	
					$balance = $mycred->get_users_balance( $user_id, $point_type );
					$formatted_balance = $mycred->format_creds( $balance );
					$balance_label = $gateway->get_option( 'balance_format' );
					$cost = '';
					$currency = get_woocommerce_currency();
	
					if ( ! mycred_point_type_exists( $currency ) && $currency != 'MYC' ) {
						if ( $cart_total ) {
	
							$exchange_rate = $gateway->get_option( 'exchange_rate' );
	
							if ( $exchange_rate ) {
								$cost = $cart_total / $exchange_rate;
							}
						}
					}
	
					$formatted_balance_arr[ $point_type ] = $formatted_balance;
	
					$enabled = ( $balance >= $cost ) ? 'yes' : 'no';
	
					$item_data[ $gateway_id ] = array(
						'mycred_woo_total'         => $mycred->format_creds( $cost ),
						'mycred_woo_total_label'   => $gateway->get_option( 'total_label' ),
						'mycred_woo_balance_label' => $balance_label,
						'payment_gateway'          => $enabled,
					);
				}
	
			} else {
				$item_data[ $gateway_id ] = array(
					'title'       => $gateway->get_title(),
					'description' => $gateway->get_description(),
					'enabled'     => $gateway->is_available() ? 'yes' : 'no',
				);
			}
		}
	
		foreach ( $formatted_balance_arr as $key => $value ) {
			$item_data['mycred_' . $key] = isset($item_data['mycred_' . $key]) ? array_merge(
				$item_data['mycred_' . $key],
				array( 'mycred_woo_balance' => $value )
			) : array( 'mycred_woo_balance' => $value );
		}
	
		return $item_data;
	}	
	
	public static function extend_checkout_block_schema() {
		return array(
			'mycred_woo_total'			=> array(
				'description' 			=> __( 'myCred WooCommerce order total.', 'mycred-woocommerce-plus' ),
				'type'        			=> array( 'integer', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
			'mycred_woo_total_label'	=> array(
				'description' 			=> __( 'The label of myCred WooCommece total field', 'mycred-woocommerce-plus' ),
				'type'        			=> array( 'string', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
			'mycred_woo_balance'		=> array(
				'description' 			=> __( 'The balance of myCred points', 'mycred-woocommerce-plus' ),
				'type'					=> array( 'integer', 'null' ),
				'context'				=> array( 'view', 'edit' ),
				'readonly'				=> true,
			),
			'mycred_woo_balance_label'	=> array(
				'description' 			=> __( 'The label of myCred points balance field', 'mycred-woocommerce-plus' ),
				'type'        			=> array( 'string', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
		);
	}
}