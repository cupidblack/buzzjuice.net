<?php
/**
 * Perform automatic database upgrades when necessary.
 *
 * @since  1.2.2
 * @return void
 */
function give_cs_do_automatic_upgrades() {
	$did_upgrade  = false;
	$plugin_version = preg_replace( '/[^0-9.].*/', '', get_option( 'give_currency_switcher_version' ) );

	if ( ! $plugin_version ) {
		// 1.0.0 is the first version to use this option so we must add it.
		$plugin_version = '1.0.0';
	}

	switch ( true ) {
		case version_compare( $plugin_version, '1.2.2', '<' ) :
			give_cs_v122_upgrades();
			$did_upgrade = true;
	}

	if ( $did_upgrade ) {
		update_option( 'give_currency_switcher_version', preg_replace( '/[^0-9.].*/', '', GIVE_CURRENCY_SWITCHER_VERSION ), false );
	}
}

add_action( 'admin_init', 'give_cs_do_automatic_upgrades' );
add_action( 'give_upgrades', 'give_cs_do_automatic_upgrades' );

/**
 * Display Upgrade Notices.
 *
 * @since 1.1 Update the form earning base on the base amount.
 *
 * @param Give_Updates $give_updates
 *
 * @return void
 */
function give_cs_show_upgrade_notices( $give_updates ) {

	// v1.1 Reset meta data.
	$give_updates->register(
		array(
			'id'       => 'give_cs_v11_reset_form_earning_meta',
			'version'  => '1.1.0',
			'callback' => 'give_cs_v11_reset_form_earning_callback',
		)
	);

	// v1.1 Re-calculate form earnings.
	$give_updates->register(
		array(
			'id'       => 'give_cs_v11_update_form_earnings',
			'version'  => '1.1.0',
			'callback' => 'give_cs_v11_upgrade_give_cs_form_earnings',
			'depend'   => array( 'give_cs_v11_reset_form_earning_meta' ),
		)
	);
}

add_action( 'give_register_updates', 'give_cs_show_upgrade_notices' );

/**
 * Reset form earning meta data.
 *
 * @since 1.1
 */
function give_cs_v11_reset_form_earning_callback() {

	/* @var Give_Updates $give_updates */
	$give_updates = Give_Updates::get_instance();

	$donation_forms = new WP_Query( array(
			'paged'          => $give_updates->step,
			'status'         => 'any',
			'order'          => 'ASC',
			'post_type'      => 'give_forms',
			'posts_per_page' => 20,
			'fields'         => 'ids',
		)
	);

	if ( $donation_forms->have_posts() ) {
		$give_updates->set_percentage( $donation_forms->found_posts, ( $give_updates->step * 20 ) );
		while ( $donation_forms->have_posts() ) {
			$donation_forms->the_post();
			// Delete form earnings.
			give_update_meta( get_the_ID(), '_give_form_earnings', 0 );
		}
		/* Restore original Post Data */
		wp_reset_postdata();
	} else {
		// The Update Ran.
		give_set_upgrade_complete( 'give_cs_v11_reset_form_earning_meta' );
	}
}

/**
 * Update the form earning as per the base amount.
 *
 * @since 1.1
 */
function give_cs_v11_upgrade_give_cs_form_earnings() {

	/* @var Give_Updates $give_updates */
	$give_updates = Give_Updates::get_instance();

	// Give Form Query.
	$donation_forms = new WP_Query( array(
			'paged'          => $give_updates->step,
			'status'         => array( 'publish', 'give_subscription' ),
			'order'          => 'ASC',
			'post_type'      => 'give_payment',
			'posts_per_page' => 20,
			'fields'         => 'ids',
		)
	);

	if ( $donation_forms->have_posts() ) {
		$give_updates->set_percentage( $donation_forms->found_posts, ( $give_updates->step * 20 ) );
		while ( $donation_forms->have_posts() ) {
			$donation_forms->the_post();

			// Get the payment ID.
			$payment_id = get_the_ID();

			// Get the payment form ID.
			$form_id = give_get_payment_form_id( $payment_id );

			$form_earnings = give_get_meta( $form_id, '_give_form_earnings', true );
			$form_earnings = ! empty( $form_earnings ) ? $form_earnings : 0;

			// Get the payment amount.
			$form_earnings += give_cs_get_payment_amount( $payment_id );

			// Update form earning.
			give_update_meta( $form_id, '_give_form_earnings', give_sanitize_amount_for_db( $form_earnings ) );
		}

		/* Restore original Post Data */
		wp_reset_postdata();

	} else {
		// The Update Ran.
		give_set_upgrade_complete( 'give_cs_v11_update_form_earnings' );
	}
}

/**
 * Increase the form earning.
 *
 * @since 1.1
 *
 * @param integer $payment_id Donation ID.
 *
 * @return float
 */
function give_cs_get_payment_amount( $payment_id ) {

	// Check if currency was changed.
	$cs_enabled     = give_get_meta( $payment_id, '_give_cs_enabled', true );
	$payment_status = get_post_status( $payment_id );

	/**
	 * Note: Most of the renew donations has no CS meta data so here in this block.
	 * We're checking if the payment is renew payment, If so, Then we'll check if
	 * the renew's parent or first donation's currency was changed then we'll use the
	 * exchange rate from it, so we can get the base amount of the renew donation.
	 */
	if (
		'give_subscription' === $payment_status
		&& ! give_is_setting_enabled( $cs_enabled )
	) {
		// Get the parent donation.
		$parent_donation_id = wp_get_post_parent_id( $payment_id );

		// Get the parent payment total.
		$parent_payment_total = give_get_meta( $parent_donation_id, '_give_payment_total', true );

		// Get the renew's parent donation CS data.
		$parent_donation_cs_enabled = give_get_meta( $parent_donation_id, '_give_cs_enabled', true );
		$parent_exchange_rates      = give_get_meta( $parent_donation_id, '_give_cs_exchange_rate', true );
		$parent_base_currency       = give_get_meta( $parent_donation_id, '_give_cs_base_currency', true );

		// Check if the subscription's first payment has currency other than the base currency.
		if ( give_is_setting_enabled( $parent_donation_cs_enabled ) ) {

			// Get the renew donation sub total.
			$sub_donation_total = give_get_meta( $payment_id, '_give_payment_total', true );

			// If the renew and first subscription payment's both has same amount.
			if ( $parent_payment_total === $sub_donation_total ) {
				// If So, Get the base amount the parent donation.
				$new_base_amount = give_get_meta( $parent_donation_id, '_give_cs_base_amount', true );
			} else {
				// Otherwise, Calculate the base amount of the renew donation using the exchange rate of the parent donation.
				$new_base_amount = ! empty( $parent_exchange_rates ) ? $sub_donation_total / $parent_exchange_rates : 0;
			}

			// Update the subscription donation currency switcher meta data.
			give_update_meta( $payment_id, '_give_cs_enabled', 'enabled' );
			give_update_meta( $payment_id, '_give_cs_exchange_rate', $parent_exchange_rates );
			give_update_meta( $payment_id, '_give_cs_base_currency', $parent_base_currency );
			give_update_meta( $payment_id, '_give_cs_base_amount', $new_base_amount );

			// Increase
			$form_earning_amount = $new_base_amount;
		} else {
			// Currency wasn't switched for this renew donation.
			$form_earning_amount = give_get_meta( $payment_id, '_give_payment_total', true );
		}
	} else if ( 'give_subscription' === $payment_status || 'publish' === $payment_status ) {
		$form_earning_amount = give_is_setting_enabled( $cs_enabled )
			? give_get_meta( $payment_id, '_give_cs_base_amount', true )
			: give_get_meta( $payment_id, '_give_payment_total', true );
	}

	return isset( $form_earning_amount ) ? $form_earning_amount : 0;
}
