<?php
/**
 * Stripe Account Verification Required Email Actions
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
 * Send affiliate email when Stripe requires additional verification
 *
 * @since 2.29.0
 * @param object $account The Stripe account object.
 * @return void
 */
function affwp_notify_affiliate_stripe_verification_required( $account ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_account_verification_required_affiliate' ) ) {
		return;
	}

	// Get affiliate ID from account metadata.
	if ( ! isset( $account->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $account->metadata->affiliate_id );

	// Only send notification if there are requirements currently due.
	if ( empty( $account->requirements->currently_due ) ) {
		return;
	}

	// Don't send if affiliate has disabled email notifications.
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( get_user_meta( $user_id, 'affwp_disable_affiliate_email', true ) ) {
		return;
	}

	// Build requirements list.
	$requirements_list = '';
	if ( isset( $account->requirements->currently_due ) && is_array( $account->requirements->currently_due ) ) {
		foreach ( $account->requirements->currently_due as $requirement ) {
			$requirements_list .= '• ' . ucwords( str_replace( '_', ' ', $requirement ) ) . "\n";
		}
	}

	// Get deadline.
	$deadline = '';
	if ( isset( $account->requirements->current_deadline ) ) {
		$deadline = date_i18n( get_option( 'date_format' ), $account->requirements->current_deadline );
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
	$verification_data = [
		'requirements_list' => $requirements_list,
		'deadline'          => $deadline,
		'recipient_type'    => 'affiliate',
	];

	add_filter( 'affwp_stripe_email_tag_data', function() use ( $verification_data ) {
		return $verification_data;
	} );

	// Get email settings.
	$email    = affwp_get_affiliate_email( $affiliate_id );
	$defaults = affwp_get_stripe_email_content( 'account_verification_required_affiliate' );
	$subject  = affiliate_wp()->settings->get( 'stripe_account_verification_required_affiliate_subject', $defaults['subject'] );
	$message  = affiliate_wp()->settings->get( 'stripe_account_verification_required_affiliate_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id'      => $affiliate_id,
		'account'           => $account,
		'verification_data' => $verification_data,
	];

	/**
	 * Filters the verification required email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_stripe_verification_required_subject', $subject, $args );

	/**
	 * Filters the verification required email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_stripe_verification_required_email', $message, $args );

	/**
	 * Filters whether to send the verification required notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_on_stripe_verification_required', true, $args ) ) {
		$emails->send( $email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_webhook_account_updated', 'affwp_notify_affiliate_stripe_verification_required', 10, 1 );

/**
 * Send admin email when Stripe requires additional verification
 *
 * @since 2.29.0
 * @param object $account The Stripe account object.
 * @return void
 */
function affwp_notify_admin_stripe_verification_required( $account ) {

	// Check if this notification is enabled.
	if ( ! affwp_email_notification_enabled( 'stripe_account_verification_required_admin' ) ) {
		return;
	}

	// Get affiliate ID from account metadata.
	if ( ! isset( $account->metadata->affiliate_id ) ) {
		return;
	}

	$affiliate_id = absint( $account->metadata->affiliate_id );

	// Only send notification if there are requirements currently due.
	if ( empty( $account->requirements->currently_due ) ) {
		return;
	}

	// Build requirements list.
	$requirements_list = '';
	if ( isset( $account->requirements->currently_due ) && is_array( $account->requirements->currently_due ) ) {
		foreach ( $account->requirements->currently_due as $requirement ) {
			$requirements_list .= '• ' . ucwords( str_replace( '_', ' ', $requirement ) ) . "\n";
		}
	}

	// Get deadline.
	$deadline = '';
	if ( isset( $account->requirements->current_deadline ) ) {
		$deadline = date_i18n( get_option( 'date_format' ), $account->requirements->current_deadline );
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
	$verification_data = [
		'requirements_list' => $requirements_list,
		'deadline'          => $deadline,
		'recipient_type'    => 'admin',
	];

	add_filter( 'affwp_stripe_email_tag_data', function() use ( $verification_data ) {
		return $verification_data;
	} );

	// Get admin email and settings.
	$admin_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
	$defaults    = affwp_get_stripe_email_content( 'account_verification_required_admin' );
	$subject     = affiliate_wp()->settings->get( 'stripe_account_verification_required_admin_subject', $defaults['subject'] );
	$message     = affiliate_wp()->settings->get( 'stripe_account_verification_required_admin_body', $defaults['body'] );

	// Prepare args for filters.
	$args = [
		'affiliate_id'      => $affiliate_id,
		'account'           => $account,
		'verification_data' => $verification_data,
	];

	/**
	 * Filters the recipient email for the admin verification required notification.
	 *
	 * @since 2.29.0
	 * @param string $admin_email Recipient email.
	 * @param array  $args        Arguments for sending the email.
	 */
	$to_email = apply_filters( 'affwp_admin_stripe_verification_required_email_to', $admin_email, $args );

	/**
	 * Filters the admin verification required email subject.
	 *
	 * @since 2.29.0
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_admin_stripe_verification_required_subject', $subject, $args );

	/**
	 * Filters the admin verification required email message.
	 *
	 * @since 2.29.0
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_admin_stripe_verification_required_email', $message, $args );

	/**
	 * Filters whether to send the admin verification required notification.
	 *
	 * @since 2.29.0
	 * @param bool   $send Whether to send the email.
	 * @param array  $args Arguments for sending the email.
	 */
	if ( apply_filters( 'affwp_notify_admin_on_stripe_verification_required', true, $args ) ) {
		$emails->send( $to_email, $subject, $message );
	}

	// Clean up the filter.
	remove_all_filters( 'affwp_stripe_email_tag_data' );
}
add_action( 'affwp_stripe_webhook_account_updated', 'affwp_notify_admin_stripe_verification_required', 10, 1 );