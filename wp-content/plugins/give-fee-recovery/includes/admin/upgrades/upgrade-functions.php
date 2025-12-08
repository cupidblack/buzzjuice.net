<?php
/**
 * Upgrade Functions
 *
 * @package     Give-Fee-Recovery
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2018, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.5.1
 *
 * NOTICE: When adding new upgrade notices, please be sure to put the action into the upgrades array during install: /includes/install.php @ Appox Line 156
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Upgrade Notices
 *
 * @param Give_Updates $give_updates Object of Give Updates Class.
 *
 * @since 1.5.1
 *
 * @return void
 */
function give_fee_recovery_show_upgrade_notices( $give_updates ) {

	$give_updates->register(
		array(
			'id'       => 'give_fee_recovery_v151_form_fee_earnings',
			'version'  => '1.5.1',
			'callback' => 'give_fee_recovery_v151_form_fee_earnings_callback',
		)
	);

}

add_action( 'give_register_updates', 'give_fee_recovery_show_upgrade_notices' );

/**
 * Store form fee earnings.
 *
 * @since 1.5.1
 */
function give_fee_recovery_v151_form_fee_earnings_callback() {

	$give_updates = Give_Updates::get_instance();

	// form query
	$donations = new WP_Query( array(
			'paged'          => $give_updates->step,
			'status'         => array( 'publish', 'give_subscription' ),
			'order'          => 'ASC',
			'post_type'      => array( 'give_payment' ),
			'posts_per_page' => 20,
			'fields'         => 'ids',
		)
	);

	if ( $donations->have_posts() ) {

		$give_updates->set_percentage( $donations->found_posts, ( $give_updates->step * 20 ) );

		while ( $donations->have_posts() ) {
			$donations->the_post();

			$payment_id = get_the_ID();

			$status = get_post_status( $payment_id );

			// Recurring donation.
			if ( 'give_subscription' === $status ) {
				$payment_id = wp_get_post_parent_id( $payment_id );
			}

			$fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );
			$fee_amount = ! empty( $fee_amount ) ? $fee_amount : 0;

			give_fee_increase_form_fee_amount( $payment_id, $fee_amount );
		}

		/* Restore original Post Data */
		wp_reset_postdata();

	} else {
		// The Update Ran.
		give_set_upgrade_complete( 'give_fee_recovery_v151_form_fee_earnings' );
	}

}



