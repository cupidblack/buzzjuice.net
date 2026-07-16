<?php
/**
 * Stripe Transfer Created Email Actions
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

// Removed make_clickable filter - testing if this is causing link issues

/**
 * Send affiliate email when Stripe transfer is created
 *
 * @since 2.29.0
 * @param object $transfer The Stripe transfer object.
 * @return void
 */
function affwp_notify_affiliate_stripe_transfer_created( $transfer ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_transfer_created' ) ) {
		return;
	}

	// Get affiliate ID from transfer metadata.
	if ( ! isset( $transfer->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $transfer->metadata->affiliate_id );
	$referral_id  = isset( $transfer->metadata->referral_id ) ? absint( $transfer->metadata->referral_id ) : 0;

	// Get referral if we have the ID.
	$referral = $referral_id ? affwp_get_referral( $referral_id ) : null;

	// Don't send if affiliate has disabled email notifications.
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( get_user_meta( $user_id, 'affwp_disable_affiliate_email', true ) ) {
		return;
	}

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'heading', __( 'Transfer Created', 'affiliate-wp' ) );
	if ( $referral ) {
		$emails->__set( 'referral', $referral );
	}

	// Set email data for tags to access via filter.
	add_filter(
		'affwp_stripe_email_tag_data',
		function () use ( $transfer ) {
			return [
				'recipient_type' => 'affiliate',
				'transfer_id'    => $transfer->id,
				'amount'         => $transfer->amount,
				'currency'       => $transfer->currency,
				'destination'    => $transfer->destination,
				'created'        => $transfer->created,
			];
		}
	);

	$email   = affwp_get_affiliate_email( $affiliate_id );
	$defaults = affwp_get_stripe_email_content( 'transfer_created_affiliate' );
	$subject = affiliate_wp()->settings->get( 'stripe_transfer_created_subject', $defaults['subject'] );
	$message = affiliate_wp()->settings->get( 'stripe_transfer_created_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id' => $affiliate_id,
		'amount'       => affwp_format_amount( $transfer->amount / 100 ),
		'transfer'     => $transfer,
	];

	/**
	 * Filters the Stripe transfer created email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_stripe_transfer_created_subject', $subject, $args );

	/**
	 * Filters the Stripe transfer created email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_stripe_transfer_created_email', $message, $args );

	/**
	 * Filters whether to send the Stripe transfer created notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_on_stripe_transfer_created', true, $args ) ) {
		$emails->send( $email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_transfer_created', 'affwp_notify_affiliate_stripe_transfer_created', 10, 1 );

/**
 * Send admin email when Stripe transfer is created
 *
 * @since 2.29.0
 * @param object $transfer The Stripe transfer object.
 * @return void
 */
function affwp_notify_admin_stripe_transfer_created( $transfer ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_transfer_created_admin' ) ) {
		return;
	}

	// Get affiliate ID from transfer metadata.
	if ( ! isset( $transfer->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $transfer->metadata->affiliate_id );
	$referral_id  = isset( $transfer->metadata->referral_id ) ? absint( $transfer->metadata->referral_id ) : 0;

	// Get referral if we have the ID.
	$referral = $referral_id ? affwp_get_referral( $referral_id ) : null;

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'heading', __( 'Transfer Created - Admin Notice', 'affiliate-wp' ) );
	if ( $referral ) {
		$emails->__set( 'referral', $referral );
	}

	// Set email data for tags to access via filter.
	add_filter(
		'affwp_stripe_email_tag_data',
		function () use ( $transfer, $referral_id ) {
			return [
				'recipient_type' => 'admin',
				'transfer_id'    => $transfer->id,
				'amount'         => $transfer->amount,
				'currency'       => $transfer->currency,
				'destination'    => $transfer->destination,
				'created'        => $transfer->created,
				'referral_id'    => $referral_id,
			];
		}
	);

	$admin_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
	$defaults    = affwp_get_stripe_email_content( 'transfer_created_admin' );
	$subject     = affiliate_wp()->settings->get( 'stripe_transfer_created_admin_subject', $defaults['subject'] );
	$message     = affiliate_wp()->settings->get( 'stripe_transfer_created_admin_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id' => $affiliate_id,
		'amount'       => affwp_format_amount( $transfer->amount / 100 ),
		'transfer'     => $transfer,
	];

	/**
	 * Filters the recipient email for the admin Stripe transfer created notification.
	 *
	 * @since 2.29.0
	 * @param string $admin_email Recipient email.
	 * @param array  $args        Arguments for sending the email.
	 */
	$to_email = apply_filters( 'affwp_admin_stripe_transfer_created_email_to', $admin_email, $args );

	/**
	 * Filters the admin Stripe transfer created email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_admin_stripe_transfer_created_subject', $subject, $args );

	/**
	 * Filters the admin Stripe transfer created email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_admin_stripe_transfer_created_email', $message, $args );

	/**
	 * Filters whether to send the admin Stripe transfer created notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_admin_on_stripe_transfer_created', true, $args ) ) {
		$emails->send( $to_email, $subject, $message );
	}
}
add_action( 'affwp_stripe_transfer_created', 'affwp_notify_admin_stripe_transfer_created', 10, 1 );
