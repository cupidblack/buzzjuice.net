<?php
/**
 * Stripe Account Connected Email Actions
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
 * Send affiliate email when Stripe account is successfully connected
 *
 * @since 2.29.0
 * @param object $account The Stripe account object.
 * @return void
 */
function affwp_notify_affiliate_stripe_account_connected( $account ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_account_connected_affiliate' ) ) {
		return;
	}

	// Get affiliate ID from account metadata.
	if ( ! isset( $account->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $account->metadata->affiliate_id );

	// Don't send if affiliate has disabled email notifications.
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( get_user_meta( $user_id, 'affwp_disable_affiliate_email', true ) ) {
		return;
	}

	// Create a dummy referral for email tags compatibility.
	$referral = (object) [
		'referral_id'  => 0,
		'affiliate_id' => $affiliate_id,
		'amount'       => 0,
		'currency'     => 'USD',
	];

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'referral', $referral );

	// Set email data for tags to access via filter.
	$connection_data = [
		'stripe_account_id' => $account->id,
		'recipient_type'    => 'affiliate',
	];

	add_filter( 'affwp_stripe_email_tag_data', function() use ( $connection_data ) {
		return $connection_data;
	} );

	// Get email settings.
	$email    = affwp_get_affiliate_email( $affiliate_id );
	$defaults = affwp_get_stripe_email_content( 'account_connected_affiliate' );
	$subject  = affiliate_wp()->settings->get( 'stripe_account_connected_affiliate_subject', $defaults['subject'] );
	$message  = affiliate_wp()->settings->get( 'stripe_account_connected_affiliate_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id'    => $affiliate_id,
		'account'         => $account,
		'connection_data' => $connection_data,
	];

	/**
	 * Filters the account connected email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_stripe_account_connected_subject', $subject, $args );

	/**
	 * Filters the account connected email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_stripe_account_connected_email', $message, $args );

	/**
	 * Filters whether to send the account connected notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_on_stripe_account_connected', true, $args ) ) {
		$emails->send( $email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_webhook_account_updated', 'affwp_notify_affiliate_stripe_account_connected', 10, 1 );

/**
 * Send admin email when Stripe account is successfully connected
 *
 * @since 2.29.0
 * @param object $account The Stripe account object.
 * @return void
 */
function affwp_notify_admin_stripe_account_connected( $account ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_account_connected_admin' ) ) {
		return;
	}

	// Get affiliate ID from account metadata.
	if ( ! isset( $account->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $account->metadata->affiliate_id );

	// Create a dummy referral for email tags compatibility.
	$referral = (object) [
		'referral_id'  => 0,
		'affiliate_id' => $affiliate_id,
		'amount'       => 0,
		'currency'     => 'USD',
	];

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'referral', $referral );

	// Set email data for tags to access via filter.
	$connection_data = [
		'stripe_account_id' => $account->id,
		'recipient_type'    => 'admin',
	];

	add_filter( 'affwp_stripe_email_tag_data', function() use ( $connection_data ) {
		return $connection_data;
	} );

	// Get admin email and settings.
	$admin_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
	$defaults    = affwp_get_stripe_email_content( 'account_connected_admin' );
	$subject     = affiliate_wp()->settings->get( 'stripe_account_connected_admin_subject', $defaults['subject'] );
	$message     = affiliate_wp()->settings->get( 'stripe_account_connected_admin_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id'    => $affiliate_id,
		'account'         => $account,
		'connection_data' => $connection_data,
	];

	/**
	 * Filters the recipient email for the admin account connected notification.
	 *
	 * @since 2.29.0
	 * @param string $admin_email Recipient email.
	 * @param array  $args        Arguments for sending the email.
	 */
	$to_email = apply_filters( 'affwp_admin_stripe_account_connected_email_to', $admin_email, $args );

	/**
	 * Filters the admin account connected email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_admin_stripe_account_connected_subject', $subject, $args );

	/**
	 * Filters the admin account connected email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_admin_stripe_account_connected_email', $message, $args );

	/**
	 * Filters whether to send the admin account connected notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_admin_on_stripe_account_connected', true, $args ) ) {
		$emails->send( $to_email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_webhook_account_updated', 'affwp_notify_admin_stripe_account_connected', 10, 1 );