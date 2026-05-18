<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class myCred_WooCommerce_Plus_Blocks_Compatibility {

	public static function init() {
        
		if ( ! did_action( 'woocommerce_blocks_loaded' ) ) {
			return;
		}

        require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'gateway/includes/mycred-woo-plus-block-store-api.php';
        require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'gateway/includes/mycred-woo-plus-checkout-block-integration.php';
        
        myCred_Woo_Plus_Extends_Store_Endpoint::init();

        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function( $integration_registry ) {
                $integration_registry->register( new myCred_WooCommerce_Plus_Checkout_Blocks_Integration() );
            }
        );

        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            require_once MYCRED_WOOPLUS_INCLUDES_DIR . '/gateway/includes/mycred-woo-plus-payment-method-integration.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                static function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new MyCred_Woo_Plus_Payment_Method() );
                }
            );
        }
	}
}

myCred_WooCommerce_Plus_Blocks_Compatibility::init();
