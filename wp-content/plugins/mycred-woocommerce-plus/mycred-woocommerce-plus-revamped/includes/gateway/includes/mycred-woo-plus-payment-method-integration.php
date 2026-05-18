<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

final class MyCred_Woo_Plus_Payment_Method extends AbstractPaymentMethodType {

	protected $name = 'mycred_plus';
	private $gateway;

	public function initialize() {
		$this->settings = get_option( 'woocommerce_mycred_settings', array() );
		$point_types = mycred_get_types( true );
		foreach ( $point_types as $point_type_key => $label ) { 
			$this->gateway[$point_type_key] = new MyCRED_WooCommerce_Plus_Gateway( $point_type_key, $label );
		}
		add_action( 'woocommerce_thankyou_mycred', array( $this, 'thankyou_page' ) );
	}

	public function thankyou_page() {
		$thankyou_msg = apply_filters( 'mycred_woo_thank_you_message', '<p>' . __( 'Your account has successfully been charged.', 'mycred' ) . '</p>' );
		echo wp_kses_post( $thankyou_msg );
	}

	public function is_active() {
		
		$point_types = mycred_get_types(true);
		foreach ( $point_types as $point_type_key => $label ) {
			if ( isset( $this->gateway[$point_type_key] ) && ! $this->gateway[$point_type_key]->is_available() ) {
				unset( $this->gateway[$point_type_key] );
			}
		}
		return $this->gateway;
	}

	public function get_payment_method_script_handles() {
		$script_asset_path = plugins_url( 'includes/gateway/build/payment/payment-block.asset.php', MYCRED_WOOPLUS_THIS );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => MYCRED_WOOPLUS_VERSION,
			);

		$script_url = plugins_url( 'includes/gateway/build/payment/payment-block.js', MYCRED_WOOPLUS_THIS );

		wp_register_script(
			'mycred-wooplus-payment-method',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mycred-wooplus-payment-method', 'woo-paystack', );
		}

		return array( 'mycred-wooplus-payment-method' );
	}

	public function get_payment_method_data() {
		$payment_methods_data = [];
		
		foreach ( $this->gateway as $point_type_key => $gateway ) {
			
			$point_type = $gateway->get_option('point_type');
			$order_total_label = $gateway->get_option('total_label');
			$balance_label = $gateway->get_option('balance_format');
			$mycred = mycred( $point_type );
	
			$attachment_id = mycred_get_default_point_image_id();
			$image_url = wp_get_attachment_url( $attachment_id );
			
			$payment_methods_data[$point_type_key] = [
				'title'            => $gateway->settings['title'],
				'description'      => $gateway->settings['description'],
				'supports'         => array_filter($gateway->supports, [$gateway, 'supports']),
				'order_total'      => $mycred->format_creds("49.20"),
				'order_total_label'=> $order_total_label,
				'balance'          => $mycred->format_creds("550"),
				'balance_label'    => $balance_label,
				'icon'             => $image_url,
			];
		}
	
		return $payment_methods_data;
	}
}