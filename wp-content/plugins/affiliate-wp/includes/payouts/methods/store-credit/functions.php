<?php

namespace AffiliateWP\Core\Payouts\Methods\StoreCredit;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the affiliate's WooCommerce store credit balance.
 *
 * @access public
 * @since 2.1.0
 * @since 2.6.0 Added `$formatted` parameter so you can remove formatting if needed.
 *
 * @param array $args      Arguments.
 * @param bool  $formatted Set to `true` to return formatted.
 *
 * @return string $current_balance The affiliate's current store credit balance.
 */
function affwp_store_credit_balance( $args = [], bool $formatted = true ) {

	// Get the User ID.
	$user_id = isset( $args['user_id'] )

		// Use the User ID passed by the user.
		? intval( $args['user_id'] )

		// Fallback to the Affiliate ID.
		: affwp_get_affiliate_user_id(
			! empty( $args['affiliate_id'] )
				? $args['affiliate_id']
				: affwp_get_affiliate_id()
		);

	$integration = affiliatewp_store_credit_get_active_integration();

	if ( empty( $integration ) ) {
		return false;
	}

	switch ( $integration ) {

		case 'woocommerce':
			$current_balance = get_user_meta( $user_id, 'affwp_wc_credit_balance', true );
			break;

		case 'edd':
			$current_balance = \edd_wallet()->wallet->balance( $user_id );
			break;
	}

	$current_balance = $formatted
		? \affwp_currency_filter( \affwp_format_amount( $current_balance ) )
		: floatval( $current_balance );

	return $current_balance;
}

/**
 * Get the Active Integration
 *
 * Prioritizes WooCommerce over EDD.
 *
 * @since 2.29.0
 *
 * @return string Empty if none.
 */
function affiliatewp_store_credit_get_active_integration() : string {

	$enabled_integrations = array_keys( affiliate_wp()->integrations->get_enabled_integrations() );

	if (
		// Check for both namespaced (core) and non-namespaced (addon) versions
		( class_exists( 'AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_WooCommerce' )
			|| class_exists( 'AffiliateWP_Store_Credit_WooCommerce' ) )

			// And they actually enabled the WooCommerce integration.
			&& in_array( 'woocommerce', $enabled_integrations, true )
	) {
		return 'woocommerce';
	} elseif (
		// Check for both namespaced (core) and non-namespaced (addon) versions
		( class_exists( 'AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit_EDD' )
			|| class_exists( 'AffiliateWP_Store_Credit_EDD' ) )
			&& class_exists( 'EDD_Wallet' )

			// And they actually enabled the EDD integration.
			&& in_array( 'edd', $enabled_integrations, true )
	) {
		return 'edd';
	}

	return '';
}


/**
 * Get Store Credit settings for the payouts tab.
 *
 * @since 2.29.0
 *
 * @return array Settings array.
 */
function affwp_store_credit_get_settings() {
	$settings = [
		'store-credit'                       => [
			'name' => __( 'Enable Store Credit', 'affiliate-wp' ),
			'desc' => __( 'Check this box to enable store credit for referrals.', 'affiliate-wp' ),
			'type' => 'checkbox',
		],
		'store-credit-all-affiliates'        => [
			'name' => __( 'Enable For All Affiliates?', 'affiliate-wp' ),
			'desc' => __( 'Check this box to allow all affiliates to receive store credit.', 'affiliate-wp' ),
			'type' => 'checkbox',
		],
		'store-credit-change-payment-method' => [
			'name' => __( 'Enable Store Credit Opt-In', 'affiliate-wp' ),
			'desc' => __( 'Check this box to allow affiliates to enable payout via store credit from their affiliate dashboard.', 'affiliate-wp' ),
			'type' => 'checkbox',
		],
	];

	// Add WooCommerce Subscriptions setting if available
	if ( class_exists( 'WC_Subscriptions' ) ) {
		$settings['store-credit-woocommerce-subscriptions'] = [
			'name' => __( 'Apply Store Credit To WooCommerce Subscriptions Renewal Orders', 'affiliate-wp' ),
			'desc' => __( 'Check this box to automatically apply the affiliate store credit to WooCommerce Subscriptions renewal orders.', 'affiliate-wp' ),
			'type' => 'checkbox',
		];
	}

	return $settings;
}
