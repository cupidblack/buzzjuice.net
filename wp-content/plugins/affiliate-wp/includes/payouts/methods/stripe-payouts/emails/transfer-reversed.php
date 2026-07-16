<?php
/**
 * Stripe Transfer Reversed Email Actions
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Emails/Actions
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include templates file to ensure affwp_get_stripe_email_content is available.
if ( ! function_exists( 'affwp_get_stripe_email_content' ) ) {
	require_once __DIR__ . '/templates.php';
}

/**
 * Send admin email when Stripe transfer is reversed
 *
 * @since 2.29.0
 * @param object $transfer The Stripe transfer object.
 * @return void
 */
function affwp_notify_admin_stripe_transfer_reversed( $transfer ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_transfer_reversed_admin' ) ) {
		return;
	}

	// Try to get affiliate ID from transfer metadata or destination
	$affiliate_id = 0;
	if ( isset( $transfer->metadata->affiliate_id ) ) {
		$affiliate_id = absint( $transfer->metadata->affiliate_id );
	} elseif ( isset( $transfer->destination ) ) {
		// Try to find affiliate by Stripe account ID
		global $wpdb;
		$found_affiliate_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT affiliate_id FROM {$wpdb->prefix}affiliate_wp_affiliatemeta 
				WHERE meta_key = 'stripe_connected_account' 
				AND meta_value = %s 
				LIMIT 1",
				$transfer->destination
			)
		);
		if ( $found_affiliate_id ) {
			$affiliate_id = absint( $found_affiliate_id );
		}
	}

	$referral_id = isset( $transfer->metadata->referral_id ) ? absint( $transfer->metadata->referral_id ) : 0;

	// Get referral if we have an ID.
	$referral = null;
	if ( $referral_id ) {
		$referral = affwp_get_referral( $referral_id );
	}

	// If no referral object, create a basic one for email tags.
	if ( ! $referral ) {
		$referral = (object) [
			'referral_id'  => $referral_id,
			'affiliate_id' => $affiliate_id,
			'amount'       => $transfer->amount / 100, // Convert from cents.
			'currency'     => strtoupper( $transfer->currency ),
		];
	}

	$emails = new Affiliate_WP_Emails();
	if ( $affiliate_id ) {
		$emails->__set( 'affiliate_id', $affiliate_id );
	}
	if ( $referral ) {
		$emails->__set( 'referral', $referral );
	}

	// Set email data for tags to access via filter.
	$transfer_data = [
		'transfer_id'     => $transfer->id,
		'amount'          => $transfer->amount,
		'reversal_reason' => __( 'Transfer reversed by Stripe', 'affiliate-wp' ),
		'recipient_type'  => 'admin',
		'affiliate_id'    => $affiliate_id,
		'destination'     => $transfer->destination,
		'currency'        => isset( $transfer->currency ) ? strtoupper( $transfer->currency ) : 'USD',
	];

	add_filter( 'affwp_stripe_email_tag_data', function() use ( $transfer_data ) {
		return $transfer_data;
	} );

	// Get admin email and settings.
	$admin_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
	$defaults    = affwp_get_stripe_email_content( 'transfer_reversed_admin' );
	$subject     = affiliate_wp()->settings->get( 'stripe_transfer_reversed_admin_subject', $defaults['subject'] );
	$message     = affiliate_wp()->settings->get( 'stripe_transfer_reversed_admin_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id'  => $affiliate_id,
		'referral'      => $referral,
		'transfer'      => $transfer,
		'transfer_data' => $transfer_data,
	];

	/**
	 * Filters the recipient email for the transfer reversed notification.
	 *
	 * @since 2.29.0
	 * @param string $admin_email Recipient email.
	 * @param array  $args        Arguments for sending the email.
	 */
	$to_email = apply_filters( 'affwp_stripe_transfer_reversed_email_to', $admin_email, $args );

	/**
	 * Filters the transfer reversed email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_stripe_transfer_reversed_subject', $subject, $args );

	/**
	 * Filters the transfer reversed email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_stripe_transfer_reversed_email', $message, $args );

	/**
	 * Filters whether to send the transfer reversed notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_on_stripe_transfer_reversed', true, $args ) ) {
		$emails->send( $to_email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_webhook_transfer_reversed', 'affwp_notify_admin_stripe_transfer_reversed', 10, 1 );