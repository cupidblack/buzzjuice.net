<?php
/**
 * Stripe Payout Completed Email Actions
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
 * Send affiliate email when Stripe payout is completed (money arrives in bank)
 *
 * @since 2.29.0
 * @param object $payout The Stripe payout object.
 * @return void
 */
function affwp_notify_affiliate_stripe_payout_completed( $payout ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_payout_completed_affiliate' ) ) {
		return;
	}

	// Get affiliate ID from payout metadata.
	if ( ! isset( $payout->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $payout->metadata->affiliate_id );
	$referral_id  = isset( $payout->metadata->referral_id ) ? absint( $payout->metadata->referral_id ) : 0;

	// Don't send if affiliate has disabled email notifications.
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( get_user_meta( $user_id, 'affwp_disable_affiliate_email', true ) ) {
		return;
	}

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
			'amount'       => $payout->amount / 100, // Convert from cents.
			'currency'     => strtoupper( $payout->currency ),
		];
	}

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'referral', $referral );

	// Set email data for tags to access via filter.
	$payout_data = [
		'payout_id'      => $payout->id,
		'amount'         => $payout->amount,
		'recipient_type' => 'affiliate',
	];

	add_filter( 'affwp_stripe_email_tag_data', function() use ( $payout_data ) {
		return $payout_data;
	} );

	// Get email settings.
	$email    = affwp_get_affiliate_email( $affiliate_id );
	$defaults = affwp_get_stripe_email_content( 'payout_completed_affiliate' );
	$subject  = affiliate_wp()->settings->get( 'stripe_payout_completed_affiliate_subject', $defaults['subject'] );
	$message  = affiliate_wp()->settings->get( 'stripe_payout_completed_affiliate_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id' => $affiliate_id,
		'referral'     => $referral,
		'payout'       => $payout,
		'payout_data'  => $payout_data,
	];

	/**
	 * Filters the payout completed email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_stripe_payout_completed_subject', $subject, $args );

	/**
	 * Filters the payout completed email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_stripe_payout_completed_email', $message, $args );

	/**
	 * Filters whether to send the payout completed notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_on_stripe_payout_completed', true, $args ) ) {
		$emails->send( $email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_webhook_payout_paid', 'affwp_notify_affiliate_stripe_payout_completed', 10, 1 );

/**
 * Send admin email when Stripe payout is completed (optional)
 *
 * @since 2.29.0
 * @param object $payout The Stripe payout object.
 * @return void
 */
function affwp_notify_admin_stripe_payout_completed( $payout ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_payout_completed_admin' ) ) {
		return;
	}

	// Get affiliate ID from payout metadata.
	if ( ! isset( $payout->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $payout->metadata->affiliate_id );
	$referral_id  = isset( $payout->metadata->referral_id ) ? absint( $payout->metadata->referral_id ) : 0;

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
			'amount'       => $payout->amount / 100, // Convert from cents.
			'currency'     => strtoupper( $payout->currency ),
		];
	}

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'referral', $referral );

	// Set email data for tags to access via filter.
	$payout_data = [
		'payout_id'      => $payout->id,
		'amount'         => $payout->amount,
		'recipient_type' => 'admin',
	];

	add_filter( 'affwp_stripe_email_tag_data', function() use ( $payout_data ) {
		return $payout_data;
	} );

	// Get admin email and settings.
	$admin_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
	$defaults    = affwp_get_stripe_email_content( 'payout_completed_admin' );
	$subject     = affiliate_wp()->settings->get( 'stripe_payout_completed_admin_subject', $defaults['subject'] );
	$message     = affiliate_wp()->settings->get( 'stripe_payout_completed_admin_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id' => $affiliate_id,
		'referral'     => $referral,
		'payout'       => $payout,
		'payout_data'  => $payout_data,
	];

	/**
	 * Filters the recipient email for the admin payout completed notification.
	 *
	 * @since 2.29.0
	 * @param string $admin_email Recipient email.
	 * @param array  $args        Arguments for sending the email.
	 */
	$to_email = apply_filters( 'affwp_admin_stripe_payout_completed_email_to', $admin_email, $args );

	/**
	 * Filters the admin payout completed email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_admin_stripe_payout_completed_subject', $subject, $args );

	/**
	 * Filters the admin payout completed email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_admin_stripe_payout_completed_email', $message, $args );

	/**
	 * Filters whether to send the admin payout completed notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_admin_on_stripe_payout_completed', true, $args ) ) {
		$emails->send( $to_email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_webhook_payout_paid', 'affwp_notify_admin_stripe_payout_completed', 10, 1 );