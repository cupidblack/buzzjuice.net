<?php
/**
 * Stripe Payouts Email Templates
 *
 * Centralized email templates for Stripe payouts.
 * These templates are used as defaults when no custom template has been saved in the database.
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Emails/Templates
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all default Stripe email templates.
 *
 * @since 2.29.0
 * @return array Array of email templates keyed by type, each containing 'subject' and 'body'.
 */
function affwp_get_stripe_email_templates() {
	return [
		// Affiliate Emails - Friendly and supportive tone.
		'transfer_created_affiliate'              => [
			'subject' => __( '{site_name}: Your {amount} payout is on the way', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Hi {name},

Great news, your payout is on the way!

Amount: <strong>{amount}</strong>
Transfer ID: {stripe_transfer_id}
Date: {payout_date}

Your funds will arrive in <strong>2-5 business days</strong>, depending on your bank.

Track this payout in your Affiliate Area: {affiliate_payout_settings_url}
TEMPLATE
			,
		],
		'transfer_created_admin'                  => [
			'subject' => __( 'Transfer for {amount} created - #{stripe_transfer_id}', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Transfer created <strong>successfully</strong>.

Affiliate: {name}
Email: {user_email}
Amount: <strong>{amount}</strong>
Transfer ID: {stripe_transfer_id}
Date: {payout_date}

Expected arrival: <strong>2-5 business days</strong>

View in Stripe: {stripe_dashboard_url}
View payout: {admin_payout_url}
TEMPLATE
			,
		],
		'transfer_failed_affiliate'               => [
			'subject' => __( '{site_name}: Payout issue - Action needed', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Hi {name},

We couldn't process your payout of <strong>{amount}</strong>.

Date: {payout_date}

<strong>What to do:</strong>
1. Log in to your Affiliate Area below
2. Click the link to access your Stripe Dashboard
3. Check for any required actions or verifications
4. Verify your bank details are correct

Once any issues are resolved, we'll automatically retry your payout.

<strong>Access your Stripe Dashboard:</strong> {affiliate_payout_settings_url}

Need help? Reply to this email.
TEMPLATE
			,
		],
		'transfer_failed_admin'                   => [
			'subject' => __( '[ACTION] Transfer failed - {amount}', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
<strong>FAILED</strong>: Transfer could not be created.

Affiliate: {name}
Amount: <strong>{amount}</strong>
Error: <strong>{payout_error_message}</strong>
Code: {payout_error_code}
Date: {payout_date}

<strong>Action required:</strong>
• Check Stripe Dashboard for details
• Verify affiliate's account status
• Review error message above

Stripe Dashboard: {stripe_dashboard_url}
View payout: {admin_payout_url}
TEMPLATE
			,
		],
		'transfer_reversed_admin'                 => [
			'subject' => __( '[URGENT] Transfer reversed - {amount}', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
<strong>URGENT</strong>: A transfer has been <strong>reversed</strong> by Stripe.

Affiliate: {name}
Transfer ID: {stripe_transfer_id}
Date: {payout_date}

<strong>What happened:</strong> The {amount} payment was taken back from the affiliate's account.

<strong>Immediate actions:</strong>
• Check Stripe Dashboard for details
• Contact affiliate if necessary

Stripe Dashboard: {stripe_dashboard_url}
View payout: {admin_payout_url}
TEMPLATE
			,
		],
		'payout_completed_affiliate'              => [
			'subject' => __( '{site_name}: Your {amount} payout has arrived!', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Hi {name},

Good news, your payout has arrived!

Amount: <strong>{amount}</strong>
Completed: {payout_date}

The funds are now in your bank account.

Thanks for being an amazing affiliate!
TEMPLATE
			,
		],
		'payout_completed_admin'                  => [
			'subject' => __( 'Payout completed - {amount}', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Payout <strong>completed</strong> to bank.

Affiliate: {name}
Amount: <strong>{amount}</strong>
Completed: {payout_date}

Funds have been deposited to affiliate's bank account.

View in Stripe: {stripe_dashboard_url}
TEMPLATE
			,
		],
		'payout_failed_affiliate'                 => [
			'subject' => __( '{site_name}: Payout failed - Action needed', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Hi {name},

Your payout of <strong>{amount}</strong> could not be completed.

Reason: <strong>{payout_failure_reason}</strong>

<strong>What to do:</strong>
1. Log in to your Affiliate Area below
2. Click the link to access your Stripe Dashboard
3. Check for any required actions or verifications
4. Verify your bank details are correct

Once resolved, we'll automatically retry your payout.

Access your Stripe Dashboard through your Affiliate Area: {affiliate_payout_settings_url}

Need help? Reply to this email.
TEMPLATE
			,
		],
		'payout_failed_admin'                     => [
			'subject' => __( '[FAILED] Payout failed - {amount}', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
<strong>FAILED</strong>: Payout could not be completed.

Affiliate: {name}
Amount: <strong>{amount}</strong>
Failure reason: <strong>{payout_failure_reason}</strong>
Date: {payout_date}

<strong>Action needed:</strong>
• Review failure reason
• Contact affiliate if necessary
• Manually retry after issue resolved

Stripe Dashboard: {stripe_dashboard_url}
View payout: {admin_payout_url}
TEMPLATE
			,
		],
		'account_verification_required_affiliate' => [
			'subject' => __( '{site_name}: Action required - Verify your account', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Hi {name},

Stripe needs <strong>additional information</strong> to continue your payouts.

Deadline: <strong>{stripe_requirements_deadline}</strong>

<strong>What to do:</strong>
1. Log in to your Affiliate Area below
2. Click the link to access your Stripe Dashboard
3. Complete the verification requirements
4. Submit the requested information

<strong>Important:</strong> Payouts are paused until verification is complete.

Access your Stripe Dashboard through your Affiliate Area: {affiliate_payout_settings_url}

Questions? Reply to this email.
TEMPLATE
			,
		],
		'account_verification_required_admin'     => [
			'subject' => __( '[ACTION] {name} verification due {stripe_requirements_deadline}', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
<strong>Verification required</strong> for affiliate account.

Affiliate: {name}
Requirements: {stripe_requirements}
Deadline: <strong>{stripe_requirements_deadline}</strong>

Status: Payouts are <strong>paused</strong> until complete.

Action needed:
	• Monitor verification progress
	• Contact affiliate if approaching deadline
	• Follow up if requirements unclear

Monitor status: {stripe_dashboard_url}
View affiliate: {admin_affiliate_url}
TEMPLATE
			,
		],
		'account_connected_affiliate'             => [
			'subject' => __( '{site_name}: Your Stripe account is connected!', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
Hi {name},

Success! Your Stripe account is <strong>connected</strong>.

You're all set to receive payouts for your referrals.

<strong>What happens next:</strong>
• Earn commissions as usual
• Receive payouts when processed by the site
• Track payments in your Affiliate Area

Access your Affiliate Area: {login_url}

Welcome aboard!
TEMPLATE
			,
		],
		'account_connected_admin'                 => [
			'subject' => __( '✓ {name} ready for Stripe payouts', 'affiliate-wp' ),
			'body'    => <<<TEMPLATE
<strong>Success:</strong> Stripe account connected.

Affiliate: {name}
Account ID: {stripe_account_id}
Connected: {payout_date}

The affiliate is now <strong>ready to receive</strong> Stripe payouts.

View account: {stripe_dashboard_url}
View affiliate: {admin_affiliate_url}
TEMPLATE
			,
		],
	];
}

/**
 * Get Stripe email content (subject and/or body).
 *
 * @since 2.29.0
 * @param string      $type The email type.
 * @param string|null $part Optional. The part to retrieve ('subject' or 'body'). If null, returns full array.
 * @return mixed Array with 'subject' and 'body' if $part is null, string if $part specified, or empty string/array on failure.
 */
function affwp_get_stripe_email_content( $type, $part = null ) {
	$templates = affwp_get_stripe_email_templates();

	if ( ! isset( $templates[ $type ] ) ) {
		return $part ? '' : [];
	}

	if ( null === $part ) {
		return $templates[ $type ];
	}

	return isset( $templates[ $type ][ $part ] ) ? $templates[ $type ][ $part ] : '';
}
