<?php
/**
 * Stripe Transfer Failed Email Actions
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
 * Send affiliate email when Stripe transfer fails
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param object $referral     The referral object.
 * @param array  $error_data   The error data.
 * @return void
 */
function affwp_notify_affiliate_stripe_transfer_failed( $affiliate_id, $referral, $error_data ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_transfer_failed_affiliate' ) ) {
		return;
	}

	// Validate we have the necessary data.
	if ( ! $affiliate_id || ! $referral ) {
		return;
	}

	$referral_id = $referral->referral_id;

	// Don't send if affiliate has disabled email notifications.
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( get_user_meta( $user_id, 'affwp_disable_affiliate_email', true ) ) {
		return;
	}

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'heading', __( 'Transfer Failed - Action Required', 'affiliate-wp' ) );
	if ( $referral ) {
		$emails->__set( 'referral', $referral );
	}

	// Set email data for tags to access via filter.
	add_filter( 'affwp_stripe_email_tag_data', function() use ( $error_data, $referral ) {
		// Extract error information from error data.
		$error_message = isset( $error_data['error_message'] ) ? $error_data['error_message'] : __( 'Transfer failed. Please check your Stripe account settings.', 'affiliate-wp' );
		$error_code = isset( $error_data['error_code'] ) ? $error_data['error_code'] : 'transfer_failed';
		
		return [
			'recipient_type' => 'affiliate',
			'transfer_id'    => isset( $error_data['transfer_id'] ) ? $error_data['transfer_id'] : '',
			'amount'         => $referral->amount * 100, // Convert to cents for consistency.
			'currency'       => affwp_get_currency(),
			'destination'    => isset( $error_data['destination'] ) ? $error_data['destination'] : '',
			'created'        => time(),
			'error_message'  => $error_message,
			'error_code'     => $error_code,
		];
	} );

	$email   = affwp_get_affiliate_email( $affiliate_id );
	$defaults = affwp_get_stripe_email_content( 'transfer_failed_affiliate' );
	$subject = affiliate_wp()->settings->get( 'stripe_transfer_failed_affiliate_subject', $defaults['subject'] );
	$message = affiliate_wp()->settings->get( 'stripe_transfer_failed_affiliate_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id' => $affiliate_id,
		'amount'       => affwp_format_amount( $referral->amount ),
		'referral'     => $referral,
		'error_data'   => $error_data,
	];

	/**
	 * Filters the Stripe transfer failed email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_stripe_transfer_failed_affiliate_subject', $subject, $args );

	/**
	 * Filters the Stripe transfer failed email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_stripe_transfer_failed_affiliate_email', $message, $args );

	/**
	 * Filters whether to send the Stripe transfer failed notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_on_stripe_transfer_failed_affiliate', true, $args ) ) {
		$emails->send( $email, $subject, $message );
	}
	
	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_transfer_failed', 'affwp_notify_affiliate_stripe_transfer_failed', 10, 3 );

/**
 * Send admin email when Stripe transfer fails
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param object $referral     The referral object.
 * @param array  $error_data   The error data.
 * @return void
 */
function affwp_notify_admin_stripe_transfer_failed( $affiliate_id, $referral, $error_data ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_transfer_failed_admin' ) ) {
		return;
	}

	// Validate we have the necessary data.
	if ( ! $affiliate_id || ! $referral ) {
		return;
	}

	$referral_id = $referral->referral_id;

	$emails = new Affiliate_WP_Emails();
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'heading', __( 'Transfer Failed - Requires Investigation', 'affiliate-wp' ) );
	if ( $referral ) {
		$emails->__set( 'referral', $referral );
	}

	// Set email data for tags to access via filter.
	add_filter( 'affwp_stripe_email_tag_data', function() use ( $error_data, $referral_id, $referral ) {
		// Extract error information from error data.
		$error_message = isset( $error_data['error_message'] ) ? $error_data['error_message'] : __( 'Transfer failed. Investigation required.', 'affiliate-wp' );
		$error_code = isset( $error_data['error_code'] ) ? $error_data['error_code'] : 'transfer_failed';
		
		return [
			'recipient_type' => 'admin',
			'transfer_id'    => isset( $error_data['transfer_id'] ) ? $error_data['transfer_id'] : '',
			'amount'         => $referral->amount * 100, // Convert to cents for consistency.
			'currency'       => affwp_get_currency(),
			'destination'    => isset( $error_data['destination'] ) ? $error_data['destination'] : '',
			'created'        => time(),
			'referral_id'    => $referral_id,
			'error_message'  => $error_message,
			'error_code'     => $error_code,
		];
	} );

	$admin_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
	$defaults    = affwp_get_stripe_email_content( 'transfer_failed_admin' );
	$subject     = affiliate_wp()->settings->get( 'stripe_transfer_failed_admin_subject', $defaults['subject'] );
	$message     = affiliate_wp()->settings->get( 'stripe_transfer_failed_admin_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id' => $affiliate_id,
		'amount'       => affwp_format_amount( $referral->amount ),
		'referral'     => $referral,
		'error_data'   => $error_data,
	];

	/**
	 * Filters the recipient email for the admin Stripe transfer failed notification.
	 *
	 * @since 2.29.0
	 * @param string $admin_email Recipient email.
	 * @param array  $args        Arguments for sending the email.
	 */
	$to_email = apply_filters( 'affwp_admin_stripe_transfer_failed_email_to', $admin_email, $args );

	/**
	 * Filters the admin Stripe transfer failed email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_admin_stripe_transfer_failed_subject', $subject, $args );

	/**
	 * Filters the admin Stripe transfer failed email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_admin_stripe_transfer_failed_email', $message, $args );

	/**
	 * Filters whether to send the admin Stripe transfer failed notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_admin_on_stripe_transfer_failed', true, $args ) ) {
		$emails->send( $to_email, $subject, $message );
	}
}
add_action( 'affwp_stripe_transfer_failed', 'affwp_notify_admin_stripe_transfer_failed', 10, 3 );