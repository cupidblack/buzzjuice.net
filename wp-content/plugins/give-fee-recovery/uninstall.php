<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 * @author     GiveWP
 *
 * @package    Give_Fee_Recovery
 */

// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
// Get Give core settings.
$give_settings = give_get_settings();

// List of plugin Global settings.
$plugin_settings = array(
	'give_fee_recovery',
	'give_fee_configuration',
	'give_fee_mode',
	'give_fee_checkbox_label',
	'give_fee_explanation',
	'give_fee_percentage',
	'give_fee_base_amount',
	'give_default_donor_choice',
	'give_fee_breakdown',
);

foreach ( $give_settings as $setting_key => $setting ) {
	if ( in_array( $setting_key, $plugin_settings, true )
	     || 'give_fee_gateway_fee' === substr( $setting_key, 0, 20 )
	) {
		unset( $give_settings[ $setting_key ] );
	}
}
// Update settings.
update_option( 'give_settings', $give_settings );

// List of Plugin meta settings.
$meta_settings = array(
	'_form_give_fee_configuration',
	'_form_give_fee_mode',
	'_form_give_fee_checkbox_label',
	'_form_give_fee_explanation',
	'_form_give_fee_percentage',
	'_form_give_fee_base_amount',
	'_form_give_fee_recovery',
	'_form_give_default_donor_choice',
	'_form_breakdown',
);

$items = get_posts( array(
	'post_type'   => 'give_forms',
	'post_status' => 'any',
	'numberposts' => - 1,
	'fields'      => 'ids',
) );

if ( $items ) {
	foreach ( $items as $item ) {

		$per_form_data = give_get_meta( $item,'' );

		if ( $per_form_data ) {
			foreach ( $per_form_data as $key => $meta_value ) {
				if ( in_array( $key, $meta_settings, true ) || false !== strpos( $key, '_form_gateway_fee_' ) ) {
					delete_post_meta( $item, $key );
				}
			}
		}
	}
}

// List of Plugin payment meta settings.
$payment_meta = array(
	'_give_fee_status',
	'_give_fee_donation_amount',
	'_give_fee_amount',
);

$payments = get_posts( array(
	'post_type'   => 'give_payment',
	'post_status' => 'any',
	'numberposts' => - 1,
	'fields'      => 'ids',
) );

if ( $payments ) {
	foreach ( $payments as $payment ) {

		$payment_data = give_get_meta( $payment, '' );

		if ( $payment_data ) {
			foreach ( $payment_data as $key => $meta_value ) {
				if ( in_array( $key, $payment_meta, true ) ) {
					delete_post_meta( $payment, $key );
				}
			}
		}
	}
}
