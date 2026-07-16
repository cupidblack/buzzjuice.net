<?php
/**
 * Admin: Emails Tab V2
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Emails
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include Stripe templates file to ensure affwp_get_stripe_email_content is available.
if ( ! function_exists( 'affwp_get_stripe_email_content' ) ) {
	$stripe_emails_path = AFFILIATEWP_PLUGIN_DIR . 'includes/payouts/methods/stripe-payouts/emails/templates.php';
	if ( file_exists( $stripe_emails_path ) ) {
		require_once $stripe_emails_path;
	}
}

/**
 * Sets up the new Emails tab with accordion UI.
 *
 * @since 2.29.0
 */
class AffiliateWP_Admin_Emails_Tab {

	/**
	 * Constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		// Hook to display custom content.
		add_action( 'affwp_settings_tab_emails_content', [ $this, 'render_emails_content' ] );

		// AJAX handler for test emails.
		add_action( 'wp_ajax_affwp_send_test_email', [ $this, 'handle_test_email' ] );

		// AJAX handler for email previews.
		add_action( 'wp_ajax_affwp_preview_email', [ $this, 'handle_preview_email' ] );

		// Hook into settings save process.
		add_action( 'affwp_settings_save_emails', [ $this, 'save_email_settings' ] );

		// Add sanitization filter for emails tab.
		add_filter( 'affwp_settings_emails_sanitize', [ $this, 'sanitize_emails_settings' ] );

		// Fix the email notification enabled check to respect 0 values.
		add_filter( 'affwp_email_notification_enabled', [ $this, 'fix_email_notification_enabled' ], 10, 2 );
	}

	/**
	 * Render the emails content.
	 *
	 * @since 2.29.0
	 */
	public function render_emails_content() {
		// Enqueue media library scripts for logo upload.
		wp_enqueue_media();

		include AFFILIATEWP_PLUGIN_DIR . 'includes/admin/emails/views/emails-tab.php';
	}

	/**
	 * Get all email configurations grouped by type.
	 *
	 * @since 2.29.0
	 *
	 * @return array Email groups.
	 */
	public function get_email_groups() {
		$groups = [];

		// Core emails (always show).
		$core_emails = $this->get_core_emails();
		if ( ! empty( $core_emails ) ) {
			$groups['core'] = [
				'title'       => __( 'AffiliateWP Emails', 'affiliate-wp' ),
				'description' => __( 'Standard email notifications for affiliate activities', 'affiliate-wp' ),
				'icon'        => '<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',
				'subgroups'   => $core_emails,
			];
		}

		// Stripe emails (always show group for better UX).
		$stripe_emails    = $this->get_stripe_emails();
		$groups['stripe'] = [
			'title'       => __( 'Stripe Payout Emails', 'affiliate-wp' ),
			'description' => __( 'Email notifications for Stripe payout events and transfers', 'affiliate-wp' ),
			'icon'        => '<svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
			'subgroups'   => $stripe_emails,
		];

		// Custom emails added via filters (backwards compatibility).
		$custom_emails = $this->get_custom_emails();
		if ( ! empty( $custom_emails ) ) {
			$groups['custom'] = [
				'title'       => __( 'Custom Emails', 'affiliate-wp' ),
				'description' => __( 'Additional email notifications added by third-party extensions', 'affiliate-wp' ),
				'icon'        => '<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>',
				'subgroups'   => $custom_emails,
			];
		}

		return $groups;
	}

	/**
	 * Get core email definitions grouped by recipient.
	 *
	 * @since 2.29.0
	 *
	 * @return array Core emails grouped by recipient.
	 */
	private function get_core_emails() {
		$grouped = [
			'admin'     => [
				'title'  => __( 'Affiliate Manager Emails', 'affiliate-wp' ),
				'emails' => [],
			],
			'affiliate' => [
				'title'  => __( 'Affiliate Emails', 'affiliate-wp' ),
				'emails' => [],
			],
		];

		// Registration Email for Affiliate Manager.
		$grouped['admin']['emails']['registration'] = [
			'name'             => __( 'New Affiliate Registration', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'admin_affiliate_registration_email' ),
			'subject'          => affiliate_wp()->settings->get(
				'registration_subject',
				__( 'New Affiliate Registration', 'affiliate-wp' )
			),
			'body'             => affiliate_wp()->settings->get(
				'registration_email',
				sprintf( __( 'A new affiliate has registered on your site, %s', 'affiliate-wp' ), home_url() ) . "\n\n" .
				__( 'Name: ', 'affiliate-wp' ) . "{name}\n\n{website}\n\n{promo_method}"
			),
			'body_field'       => 'registration_email',
			'tags'             => '{name}, {username}, {user_email}, {website}, {promo_method}, {affiliate_id}, {affiliate_url}, {login_url}, {site_name}, {review_url}',
			'webhook_required' => false,
			'description'      => __( 'Sent to affiliate manager when a new affiliate registers.', 'affiliate-wp' ),
		];

		// New referral email for Admin.
		$grouped['admin']['emails']['new_admin_referral'] = [
			'name'             => __( 'New Referral', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'admin_new_referral_email' ),
			'subject'          => affiliate_wp()->settings->get( 'new_admin_referral_subject', __( 'New referral on {site_name}', 'affiliate-wp' ) ),
			'body'             => affiliate_wp()->settings->get( 'new_admin_referral_email', $this->get_default_template( 'new_referral' ) ),
			'body_field'       => 'new_admin_referral_email',
			'tags'             => '{name}, {amount}, {referral_url}, {site_name}, {landing_page}, {campaign_name}',
			'webhook_required' => false,
			'description'      => __( 'Sent to affiliate manager when a new referral is created.', 'affiliate-wp' ),
		];

		// Affiliate emails.

		// New Referral Email for Affiliate.
		$grouped['affiliate']['emails']['referral'] = [
			'name'             => __( 'New Referral', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'affiliate_new_referral_email' ),
			'subject'          => affiliate_wp()->settings->get(
				'referral_subject',
				__( 'Referral Awarded!', 'affiliate-wp' )
			),
			'body'             => affiliate_wp()->settings->get(
				'referral_email',
				__( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" .
				__( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' .
				sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" .
				__( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
			),
			'body_field'       => 'referral_email',
			'tags'             => '{name}, {amount}, {referral_url}, {login_url}, {site_name}, {referral_rate}, {landing_page}, {campaign_name}',
			'webhook_required' => false,
			'description'      => __( 'Sent to affiliates when they earn a new referral.', 'affiliate-wp' ),
		];

		// Application Accepted Email for Affiliate.
		$grouped['affiliate']['emails']['accepted'] = [
			'name'             => __( 'Application Accepted', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'affiliate_application_accepted_email' ),
			'subject'          => affiliate_wp()->settings->get(
				'accepted_subject',
				__( 'Affiliate Application Accepted', 'affiliate-wp' )
			),
			'body'             => affiliate_wp()->settings->get(
				'accepted_email',
				__( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" .
				sprintf( __( 'Your affiliate application on %s has been accepted!', 'affiliate-wp' ), home_url() ) . "\n\n" .
				__( 'Log into your affiliate area at', 'affiliate-wp' ) . ' {login_url}'
			),
			'body_field'       => 'accepted_email',
			'tags'             => '{name}, {username}, {user_email}, {affiliate_id}, {referral_url}, {login_url}, {site_name}',
			'webhook_required' => false,
			'description'      => __( 'Sent when an affiliate application is approved.', 'affiliate-wp' ),
		];

		// Application Pending Email for Affiliate (requires approval setting).
		$grouped['affiliate']['emails']['pending'] = [
			'name'              => __( 'Application Pending', 'affiliate-wp' ),
			'recipient'         => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'           => affwp_email_notification_enabled( 'affiliate_application_pending_email' ),
			'subject'           => affiliate_wp()->settings->get(
				'pending_subject',
				__( 'Your Affiliate Application Is Being Reviewed', 'affiliate-wp' )
			),
			'body'              => affiliate_wp()->settings->get(
				'pending_email',
				__( 'Hi {name}!', 'affiliate-wp' ) . "\n\n" .
				__( 'Thanks for your recent affiliate registration on {site_name}.', 'affiliate-wp' ) . "\n\n" .
				__( 'We&#8217;re currently reviewing your affiliate application and will be in touch soon!', 'affiliate-wp' )
			),
			'body_field'        => 'pending_email',
			'tags'              => '{name}, {username}, {user_email}, {site_name}',
			'webhook_required'  => false,
			'approval_required' => true,
			'description'       => __( 'Sent when an affiliate registers and approval is required.', 'affiliate-wp' ),
		];

		// Application Rejected Email for Affiliate (requires approval setting).
		$grouped['affiliate']['emails']['rejection'] = [
			'name'              => __( 'Application Rejected', 'affiliate-wp' ),
			'recipient'         => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'           => affwp_email_notification_enabled( 'affiliate_application_rejected_email' ),
			'subject'           => affiliate_wp()->settings->get(
				'rejection_subject',
				__( 'Your Affiliate Application Has Been Rejected', 'affiliate-wp' )
			),
			'body'              => affiliate_wp()->settings->get(
				'rejection_email',
				__( 'Hi {name},', 'affiliate-wp' ) . "\n\n" .
				__( 'We regret to inform you that your recent affiliate registration on {site_name} was rejected.', 'affiliate-wp' )
			),
			'body_field'        => 'rejection_email',
			'tags'              => '{name}, {username}, {rejection_reason}, {site_name}',
			'webhook_required'  => false,
			'approval_required' => true,
			'description'       => __( 'Sent when an affiliate application is rejected.', 'affiliate-wp' ),
		];

		// Remove empty subgroups.
		foreach ( $grouped as $key => $subgroup ) {
			if ( empty( $subgroup['emails'] ) ) {
				unset( $grouped[ $key ] );
			}
		}

		return $grouped;
	}

	/**
	 * Get Stripe email definitions grouped by recipient.
	 *
	 * @since 2.29.0
	 *
	 * @return array Stripe emails grouped by recipient.
	 */
	private function get_stripe_emails() {
		$grouped = [
			'admin'     => [
				'title'  => __( 'Affiliate Manager Emails', 'affiliate-wp' ),
				'emails' => [],
			],
			'affiliate' => [
				'title'  => __( 'Affiliate Emails', 'affiliate-wp' ),
				'emails' => [],
			],
		];

		// Transfer Created - Affiliate.
		$transfer_created_affiliate                                = affwp_get_stripe_email_content( 'transfer_created_affiliate' );
		$grouped['affiliate']['emails']['stripe_transfer_created'] = [
			'name'             => __( 'Transfer Created', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_transfer_created' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_transfer_created_subject',
				$transfer_created_affiliate['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_transfer_created_body',
				$this->get_default_stripe_template( 'transfer_created_affiliate' )
			),
			'body_field'       => 'stripe_transfer_created_body',
			'tags'             => '{name}, {amount}, {transfer_id}, {payout_date}, {stripe_dashboard_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Notifies affiliates when transfers are sent to their Stripe account.', 'affiliate-wp' ),
		];

		// Transfer Created - Admin.
		$transfer_created_admin                                      = affwp_get_stripe_email_content( 'transfer_created_admin' );
		$grouped['admin']['emails']['stripe_transfer_created_admin'] = [
			'name'             => __( 'Transfer Created', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_transfer_created_admin' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_transfer_created_admin_subject',
				$transfer_created_admin['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_transfer_created_admin_body',
				$this->get_default_stripe_template( 'transfer_created_admin' )
			),
			'body_field'       => 'stripe_transfer_created_admin_body',
			'tags'             => '{name}, {user_email}, {amount}, {transfer_id}, {payout_date}, {stripe_dashboard_url}, {admin_payout_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Notifies affiliate managers when transfers are sent to affiliate Stripe accounts.', 'affiliate-wp' ),
		];

		// Transfer Failed - Affiliate.
		$transfer_failed_affiliate = affwp_get_stripe_email_content( 'transfer_failed_affiliate' );
		$grouped['affiliate']['emails']['stripe_transfer_failed_affiliate'] = [
			'name'             => __( 'Transfer Failed', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_transfer_failed_affiliate' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_transfer_failed_affiliate_subject',
				$transfer_failed_affiliate['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_transfer_failed_affiliate_body',
				$this->get_default_stripe_template( 'transfer_failed_affiliate' )
			),
			'body_field'       => 'stripe_transfer_failed_affiliate_body',
			'tags'             => '{name}, {amount}, {payout_error_message}, {payout_error_code}, {payout_date}, {affiliate_payout_settings_url}, {site_name}',
			'webhook_required' => false,
			'description'      => __( 'Notifies affiliates when transfers to their Stripe account fail.', 'affiliate-wp' ),
		];

		// Transfer Failed - Admin.
		$transfer_failed_admin                                      = affwp_get_stripe_email_content( 'transfer_failed_admin' );
		$grouped['admin']['emails']['stripe_transfer_failed_admin'] = [
			'name'             => __( 'Transfer Failed', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_transfer_failed_admin' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_transfer_failed_admin_subject',
				$transfer_failed_admin['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_transfer_failed_admin_body',
				$this->get_default_stripe_template( 'transfer_failed_admin' )
			),
			'body_field'       => 'stripe_transfer_failed_admin_body',
			'tags'             => '{name}, {amount}, {payout_error_message}, {payout_error_code}, {payout_date}, {stripe_dashboard_url}, {admin_payout_url}, {site_name}',
			'webhook_required' => false,
			'description'      => __( 'Alerts affiliate managers when transfers fail.', 'affiliate-wp' ),
		];

		// Transfer Reversed - Admin Only.
		$transfer_reversed_admin                                      = affwp_get_stripe_email_content( 'transfer_reversed_admin' );
		$grouped['admin']['emails']['stripe_transfer_reversed_admin'] = [
			'name'             => __( 'Transfer Reversed', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_transfer_reversed_admin' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_transfer_reversed_admin_subject',
				$transfer_reversed_admin['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_transfer_reversed_admin_body',
				$this->get_default_stripe_template( 'transfer_reversed_admin' )
			),
			'body_field'       => 'stripe_transfer_reversed_admin_body',
			'tags'             => '{name}, {amount}, {stripe_transfer_id}, {payout_date}, {stripe_dashboard_url}, {admin_payout_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Critical alert when Stripe reverses a transfer.', 'affiliate-wp' ),
		];

		// Payout Completed - Affiliate.
		$payout_completed_affiliate = affwp_get_stripe_email_content( 'payout_completed_affiliate' );
		$grouped['affiliate']['emails']['stripe_payout_completed_affiliate'] = [
			'name'             => __( 'Payout Completed', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_payout_completed_affiliate' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_payout_completed_affiliate_subject',
				$payout_completed_affiliate['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_payout_completed_affiliate_body',
				$this->get_default_stripe_template( 'payout_completed_affiliate' )
			),
			'body_field'       => 'stripe_payout_paid_affiliate_body',
			'tags'             => '{name}, {amount}, {payout_date}, {login_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Notifies affiliates when funds arrive in their bank.', 'affiliate-wp' ),
		];

		// Payout Completed - Admin.
		$payout_completed_admin                                      = affwp_get_stripe_email_content( 'payout_completed_admin' );
		$grouped['admin']['emails']['stripe_payout_completed_admin'] = [
			'name'             => __( 'Payout Completed', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_payout_completed_admin' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_payout_completed_admin_subject',
				$payout_completed_admin['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_payout_completed_admin_body',
				$this->get_default_stripe_template( 'payout_completed_admin' )
			),
			'tags'             => '{name}, {amount}, {payout_date}, {stripe_dashboard_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Notification when payouts reach affiliate banks.', 'affiliate-wp' ),
		];

		// Payout Failed - Affiliate.
		$payout_failed_affiliate = affwp_get_stripe_email_content( 'payout_failed_affiliate' );
		$grouped['affiliate']['emails']['stripe_payout_failed_affiliate'] = [
			'name'             => __( 'Payout Failed', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_payout_failed_affiliate' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_payout_failed_affiliate_subject',
				$payout_failed_affiliate['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_payout_failed_affiliate_body',
				$this->get_default_stripe_template( 'payout_failed_affiliate' )
			),
			'tags'             => '{name}, {amount}, {payout_failure_reason}, {payout_date}, {affiliate_payout_settings_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Notifies affiliates when bank rejects payout.', 'affiliate-wp' ),
		];

		// Payout Failed - Admin.
		$payout_failed_admin                                      = affwp_get_stripe_email_content( 'payout_failed_admin' );
		$grouped['admin']['emails']['stripe_payout_failed_admin'] = [
			'name'             => __( 'Payout Failed', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_payout_failed_admin' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_payout_failed_admin_subject',
				$payout_failed_admin['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_payout_failed_admin_body',
				$this->get_default_stripe_template( 'payout_failed_admin' )
			),
			'tags'             => '{name}, {amount}, {payout_failure_reason}, {payout_date}, {stripe_dashboard_url}, {admin_payout_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Alerts affiliate managers when banks reject payouts.', 'affiliate-wp' ),
		];

		// Account Verification Required - Affiliate.
		$account_verification_required_affiliate = affwp_get_stripe_email_content( 'account_verification_required_affiliate' );
		$grouped['affiliate']['emails']['stripe_account_verification_required_affiliate'] = [
			'name'             => __( 'Account Verification Required', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_account_verification_required_affiliate' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_account_verification_required_affiliate_subject',
				$account_verification_required_affiliate['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_account_verification_required_affiliate_body',
				$this->get_default_stripe_template( 'account_verification_required_affiliate' )
			),
			'tags'             => '{name}, {stripe_requirements}, {stripe_requirements_deadline}, {affiliate_payout_settings_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Notifies affiliates when Stripe needs verification.', 'affiliate-wp' ),
		];

		// Account Verification Required - Admin.
		$account_verification_required_admin                                      = affwp_get_stripe_email_content( 'account_verification_required_admin' );
		$grouped['admin']['emails']['stripe_account_verification_required_admin'] = [
			'name'             => __( 'Account Verification Required', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_account_verification_required_admin' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_account_verification_required_admin_subject',
				$account_verification_required_admin['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_account_verification_required_admin_body',
				$this->get_default_stripe_template( 'account_verification_required_admin' )
			),
			'tags'             => '{name}, {stripe_requirements}, {stripe_requirements_deadline}, {payout_date}, {stripe_dashboard_url}, {admin_affiliate_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Alerts affiliate managers to track affiliate verification requirements.', 'affiliate-wp' ),
		];

		// Account Connected - Affiliate.
		$account_connected_affiliate = affwp_get_stripe_email_content( 'account_connected_affiliate' );
		$grouped['affiliate']['emails']['stripe_account_connected_affiliate'] = [
			'name'             => __( 'Account Connected', 'affiliate-wp' ),
			'recipient'        => __( 'Affiliate', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_account_connected_affiliate' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_account_connected_affiliate_subject',
				$account_connected_affiliate['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_account_connected_affiliate_body',
				$this->get_default_stripe_template( 'account_connected_affiliate' )
			),
			'tags'             => '{name}, {login_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Welcome email after successful Stripe account connection.', 'affiliate-wp' ),
		];

		// Account Connected - Admin.
		$account_connected_admin                                      = affwp_get_stripe_email_content( 'account_connected_admin' );
		$grouped['admin']['emails']['stripe_account_connected_admin'] = [
			'name'             => __( 'Account Connected', 'affiliate-wp' ),
			'recipient'        => __( 'Admin', 'affiliate-wp' ),
			'enabled'          => affwp_email_notification_enabled( 'stripe_account_connected_admin' ),
			'subject'          => affiliate_wp()->settings->get(
				'stripe_account_connected_admin_subject',
				$account_connected_admin['subject']
			),
			'body'             => affiliate_wp()->settings->get(
				'stripe_account_connected_admin_body',
				$this->get_default_stripe_template( 'account_connected_admin' )
			),
			'tags'             => '{name}, {stripe_account_id}, {stripe_verification_status}, {payout_date}, {stripe_dashboard_url}, {admin_affiliate_url}, {site_name}',
			'webhook_required' => true,
			'description'      => __( 'Notification when affiliates connect Stripe accounts.', 'affiliate-wp' ),
		];

		return $grouped;
	}

	/**
	 * Get custom emails added via filters (backwards compatibility).
	 *
	 * @since 2.29.0
	 *
	 * @return array Custom emails grouped by category.
	 */
	private function get_custom_emails() {
		$grouped = [];

		// Get the base email notifications structure.
		$base_settings = [
			'email_notifications' => [
				'options' => affiliate_wp()->settings->email_notifications(),
			],
		];

		// Apply the filter to let extensions add their emails.
		$filtered_settings = apply_filters( 'affwp_settings_emails', $base_settings );

		// Extract all notifications after filtering.
		$custom_notifications = [];
		if ( isset( $filtered_settings['email_notifications']['options'] ) ) {
			$all_notifications = $filtered_settings['email_notifications']['options'];
		} else {
			$all_notifications = [];
		}

		// Core email IDs that we already handle.
		$core_email_ids = [
			'admin_affiliate_registration_email',
			'admin_new_referral_email',
			'affiliate_new_referral_email',
			'affiliate_application_accepted_email',
			'affiliate_application_pending_email',
			'affiliate_application_rejected_email',
		];

		// Stripe email IDs that we already handle.
		$stripe_email_ids = [
			'stripe_transfer_created',
			'stripe_transfer_created_admin',
			'stripe_transfer_failed_affiliate',
			'stripe_transfer_failed_admin',
			'stripe_transfer_reversed_admin',
			'stripe_account_requirements_affiliate',
			'stripe_account_requirements_admin',
			'stripe_payout_paid_affiliate',
			'stripe_payout_completed_affiliate',
			'stripe_payout_completed_admin',
			'stripe_payout_failed_affiliate',
			'stripe_payout_failed_admin',
			'stripe_account_verification_required_affiliate',
			'stripe_account_verification_required_admin',
			'stripe_account_connected_affiliate',
			'stripe_account_connected_admin',
		];

		// Find custom emails that aren't core or Stripe.
		foreach ( $all_notifications as $key => $label ) {
			if ( ! in_array( $key, $core_email_ids, true ) && ! in_array( $key, $stripe_email_ids, true ) ) {
				$custom_notifications[ $key ] = $label;
			}
		}

		// If we have custom emails, organize them.
		if ( ! empty( $custom_notifications ) ) {
			$grouped['custom'] = [
				'title'  => __( 'Third-Party Email Notifications', 'affiliate-wp' ),
				'emails' => [],
			];

			foreach ( $custom_notifications as $email_id => $label ) {
				// Try to get subject and body from settings.
				// For our test emails: custom_milestone_reached_email -> custom_milestone_reached_subject
				$subject_key = str_replace( '_email', '_subject', $email_id );
				// For body: custom_milestone_reached_email -> custom_milestone_reached
				$body_key = str_replace( '_email', '', $email_id );

				// Build the email configuration.
				$grouped['custom']['emails'][ $email_id ] = [
					'name'             => $label,
					'recipient'        => __( 'Custom', 'affiliate-wp' ),
					'enabled'          => affwp_email_notification_enabled( $email_id ),
					'subject'          => affiliate_wp()->settings->get( $subject_key, '' ),
					'body'             => affiliate_wp()->settings->get( $body_key, '' ),
					'tags'             => '',  // We don't know what tags are available.
					'webhook_required' => false,
					'description'      => sprintf( __( 'Custom email added by extension: %s', 'affiliate-wp' ), $label ),
				];
			}
		}

		// Remove empty subgroups.
		foreach ( $grouped as $key => $subgroup ) {
			if ( empty( $subgroup['emails'] ) ) {
				unset( $grouped[ $key ] );
			}
		}

		return $grouped;
	}

	/**
	 * Get default template for core emails.
	 *
	 * @since 2.29.0
	 *
	 * @param string $type Email type.
	 * @return string Default template.
	 */
	private function get_default_template( $type ) {
		$templates = [
			'new_referral' => __( "A new referral has been recorded on {site_name}.\n\nAffiliate: {name}\nAmount: {amount}\nReferral URL: {referral_url}\n\nLog in to your dashboard to review this referral.", 'affiliate-wp' ),
		];

		return isset( $templates[ $type ] ) ? $templates[ $type ] : '';
	}

	/**
	 * Get default template for Stripe emails.
	 *
	 * Note: These templates are NOT translated because they get saved to the database
	 * as static strings when settings are saved. Once saved, they cannot be translated
	 * dynamically. Admins should customize these templates in their preferred language.
	 *
	 * @since 2.29.0
	 *
	 * @param string $type Email type.
	 * @return string Default template body.
	 */
	private function get_default_stripe_template( $type ) {
		// Include the templates file if function doesn't exist yet.
		if ( ! function_exists( 'affwp_get_stripe_email_content' ) ) {
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/payouts/methods/stripe-payouts/emails/templates.php';
		}

		return affwp_get_stripe_email_content( $type, 'body' );
	}

	/**
	 * Handle test email AJAX request.
	 *
	 * @since 2.29.0
	 */
	public function handle_test_email() {
		check_ajax_referer( 'affwp_test_email', 'nonce' );

		if ( ! current_user_can( 'manage_affiliate_options' ) ) {
			wp_die();
		}

		$email_id   = sanitize_text_field( $_POST['email_id'] );
		$test_email = ! empty( $_POST['recipient'] ) ? sanitize_email( $_POST['recipient'] ) : get_option( 'admin_email' );

		// Get all email configurations and flatten the grouped structure.
		$all_emails = [];

		// Flatten core emails.
		$core_groups = $this->get_core_emails();
		foreach ( $core_groups as $subgroup ) {
			if ( isset( $subgroup['emails'] ) ) {
				$all_emails = array_merge( $all_emails, $subgroup['emails'] );
			}
		}

		// Flatten Stripe emails.
		$stripe_groups = $this->get_stripe_emails();
		foreach ( $stripe_groups as $subgroup ) {
			if ( isset( $subgroup['emails'] ) ) {
				$all_emails = array_merge( $all_emails, $subgroup['emails'] );
			}
		}

		if ( ! isset( $all_emails[ $email_id ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid email ID', 'affiliate-wp' ) ] );
		}

		$email_config = $all_emails[ $email_id ];

		// Use submitted values if provided, otherwise use defaults.
		if ( ! empty( $_POST['subject'] ) ) {
			$email_config['subject'] = sanitize_text_field( $_POST['subject'] );
		}
		if ( ! empty( $_POST['body'] ) ) {
			$email_config['body'] = wp_kses_post( $_POST['body'] );
		}

		// Try to get a random active affiliate for test data.
		global $wpdb;
		$affiliate_table  = $wpdb->prefix . 'affiliate_wp_affiliates';
		$random_affiliate = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT affiliate_id FROM %i
				WHERE status = %s
				ORDER BY RAND()
				LIMIT 1',
				$affiliate_table,
				'active'
			)
		);

		// Initialize the email class.
		$emails = new \Affiliate_WP_Emails();

		// Variable to track what test data we're using.
		$test_data_source = '';

		if ( $random_affiliate ) {
			// Use real affiliate data.
			$affiliate_id = $random_affiliate->affiliate_id;
			$emails->__set( 'affiliate_id', $affiliate_id );

			// Get affiliate details.
			$affiliate_name = affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );

			$test_data_source = sprintf(
				__( ' (using data from affiliate: %s)', 'affiliate-wp' ),
				$affiliate_name
			);

			// For referral-related emails, create a mock referral object.
			if ( strpos( $email_id, 'referral' ) !== false || strpos( $email_id, 'stripe' ) !== false ) {
				$mock_referral = (object) [
					'referral_id'  => 99999,
					'affiliate_id' => $affiliate_id,
					'amount'       => 100.00,
					'currency'     => affwp_get_currency(),
					'description'  => __( 'Test Referral', 'affiliate-wp' ),
					'reference'    => 'TEST-' . time(),
					'context'      => 'test',
					'status'       => 'paid',
				];
				$emails->__set( 'referral', $mock_referral );
			}
		} else {
			// Use mock data when no affiliates exist.
			$test_data_source = __( ' (using mock test data)', 'affiliate-wp' );

			// Set a fake affiliate ID for tag parsing.
			$emails->__set( 'affiliate_id', 99999 );

			// Create a mock referral for emails that need it.
			if ( strpos( $email_id, 'referral' ) !== false || strpos( $email_id, 'stripe' ) !== false ) {
				$mock_referral = (object) [
					'referral_id'  => 99999,
					'affiliate_id' => 99999,
					'amount'       => 100.00,
					'currency'     => affwp_get_currency(),
					'description'  => __( 'Test Referral', 'affiliate-wp' ),
					'reference'    => 'TEST-' . time(),
					'context'      => 'test',
					'status'       => 'paid',
				];
				$emails->__set( 'referral', $mock_referral );
			}

			// For emails that use specific tags not covered by the standard parsing,
			// we'll do a manual replacement for Stripe-specific and other custom tags.
			$manual_tags = [
				'{name}'                 => __( 'Test Affiliate', 'affiliate-wp' ),
				'{user_email}'           => 'test@example.com',
				'{transfer_id}'          => 'tr_TEST123456',
				'{payout_id}'            => 'po_TEST789012',
				'{expected_arrival}'     => date_i18n( get_option( 'date_format' ), strtotime( '+3 days' ) ),
				'{payout_date}'          => date_i18n( get_option( 'date_format' ) ),
				'{error_message}'        => __( 'Insufficient funds in platform account', 'affiliate-wp' ),
				'{error_code}'           => 'insufficient_funds',
				'{failure_reason}'       => __( 'Invalid account number', 'affiliate-wp' ),
				'{reversal_reason}'      => __( 'Dispute lost', 'affiliate-wp' ),
				'{requirements_list}'    => '• ' . __( 'Identity verification document', 'affiliate-wp' ) . "\n• " . __( 'Address verification', 'affiliate-wp' ),
				'{deadline}'             => date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) ),
				'{admin_payout_url}'     => admin_url( 'admin.php?page=affiliate-wp-payouts' ),
				'{admin_url}'            => admin_url( 'admin.php?page=affiliate-wp-affiliates' ),
				'{stripe_account_email}' => 'stripe@example.com',
				'{stripe_account_id}'    => 'acct_TEST123456',
				'{verification_status}'  => __( 'Pending', 'affiliate-wp' ),
			];

			// Apply manual tag replacements for custom tags.
			$email_config['subject'] = str_replace( array_keys( $manual_tags ), array_values( $manual_tags ), $email_config['subject'] );
			$email_config['body']    = str_replace( array_keys( $manual_tags ), array_values( $manual_tags ), $email_config['body'] );
		}

		// For Stripe emails, set up the proper context data for tags.
		if ( strpos( $email_id, 'stripe_' ) === 0 ) {
			// Determine recipient type based on email ID.
			$recipient_type = ( strpos( $email_id, '_admin' ) !== false ) ? 'admin' : 'affiliate';

			// Set up Stripe email tag data filter.
			add_filter(
				'affwp_stripe_email_tag_data',
				function () use ( $recipient_type ) {
					return [
						'is_test'             => true, // Flag for test mode
						'recipient_type'      => $recipient_type,
						'transfer_id'         => 'tr_TEST123456',
						'amount'              => 10000, // $100.00 in cents
						'currency'            => 'usd',
						'destination'         => 'ba_TEST123456',
						'created'             => time(),
						'referral_id'         => 99999,
						'error_message'       => __( 'Insufficient funds in platform account', 'affiliate-wp' ),
						'error_code'          => 'insufficient_funds',
						'failure_reason'      => __( 'Invalid account number', 'affiliate-wp' ),
						'reversal_reason'     => __( 'Dispute lost', 'affiliate-wp' ),
						'requirements_list'   => __( 'Identity verification, Bank account information', 'affiliate-wp' ),
						'deadline'            => date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) ),
						'stripe_account_id'   => 'acct_TEST123456',
						'verification_status' => __( 'Pending', 'affiliate-wp' ),
					];
				}
			);
		}

		// Send the test email using the proper email class.
		$sent = $emails->send(
			$test_email,
			'[TEST] ' . $email_config['subject'],
			wp_unslash( $email_config['body'] )
		);

		// Clean up Stripe email tag data filter if it was added.
		if ( strpos( $email_id, 'stripe_' ) === 0 ) {
			remove_all_filters( 'affwp_stripe_email_tag_data' );
		}

		if ( $sent ) {
			wp_send_json_success(
				[
					'message' => sprintf(
						__( 'Test email sent to %1$s%2$s', 'affiliate-wp' ),
						$test_email,
						$test_data_source
					),
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => __( 'Failed to send test email. Please check your WordPress email configuration.', 'affiliate-wp' ),
				]
			);
		}
	}

	/**
	 * Handle email preview AJAX request.
	 *
	 * @since 2.29.0
	 */
	public function handle_preview_email() {
		check_ajax_referer( 'affwp_preview_email', 'nonce' );

		if ( ! current_user_can( 'manage_affiliate_options' ) ) {
			wp_die();
		}

		$email_id = sanitize_text_field( $_POST['email_id'] );

		// Get all email configurations and flatten the grouped structure.
		$all_emails = [];

		// Flatten core emails.
		$core_groups = $this->get_core_emails();
		foreach ( $core_groups as $subgroup ) {
			if ( isset( $subgroup['emails'] ) ) {
				$all_emails = array_merge( $all_emails, $subgroup['emails'] );
			}
		}

		// Flatten Stripe emails.
		$stripe_groups = $this->get_stripe_emails();
		foreach ( $stripe_groups as $subgroup ) {
			if ( isset( $subgroup['emails'] ) ) {
				$all_emails = array_merge( $all_emails, $subgroup['emails'] );
			}
		}

		if ( ! isset( $all_emails[ $email_id ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid email ID', 'affiliate-wp' ) ] );
		}

		$email_config = $all_emails[ $email_id ];

		// Use submitted values if provided, otherwise use defaults.
		if ( ! empty( $_POST['subject'] ) ) {
			$email_config['subject'] = sanitize_text_field( $_POST['subject'] );
		}
		if ( ! empty( $_POST['body'] ) ) {
			$email_config['body'] = wp_kses_post( $_POST['body'] );
		}

		// Try to get a random active affiliate for preview data.
		global $wpdb;
		$affiliate_table  = $wpdb->prefix . 'affiliate_wp_affiliates';
		$random_affiliate = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT affiliate_id FROM %i
				WHERE status = %s
				ORDER BY RAND()
				LIMIT 1',
				$affiliate_table,
				'active'
			)
		);

		// Initialize the email class.
		$emails = new \Affiliate_WP_Emails();

		// Variable to track what preview data we're using.
		$preview_data_source = '';

		if ( $random_affiliate ) {
			// Use real affiliate data.
			$affiliate_id = $random_affiliate->affiliate_id;
			$emails->__set( 'affiliate_id', $affiliate_id );

			// Get affiliate details.
			$affiliate_name = affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );

			$preview_data_source = sprintf(
				__( 'Preview using data from affiliate: %s', 'affiliate-wp' ),
				$affiliate_name
			);

			// For referral-related emails, create a mock referral object.
			if ( strpos( $email_id, 'referral' ) !== false || strpos( $email_id, 'stripe' ) !== false ) {
				$mock_referral = (object) [
					'referral_id'  => 99999,
					'affiliate_id' => $affiliate_id,
					'amount'       => 100.00,
					'currency'     => affwp_get_currency(),
					'description'  => __( 'Preview Referral', 'affiliate-wp' ),
					'reference'    => 'PREVIEW-' . time(),
					'context'      => 'preview',
					'status'       => 'paid',
				];
				$emails->__set( 'referral', $mock_referral );
			}
		} else {
			// Use mock data when no affiliates exist.
			$preview_data_source = __( 'Preview using mock test data', 'affiliate-wp' );

			// Set a fake affiliate ID for tag parsing.
			$emails->__set( 'affiliate_id', 99999 );

			// Create a mock referral for emails that need it.
			if ( strpos( $email_id, 'referral' ) !== false || strpos( $email_id, 'stripe' ) !== false ) {
				$mock_referral = (object) [
					'referral_id'  => 99999,
					'affiliate_id' => 99999,
					'amount'       => 100.00,
					'currency'     => affwp_get_currency(),
					'description'  => __( 'Preview Referral', 'affiliate-wp' ),
					'reference'    => 'PREVIEW-' . time(),
					'context'      => 'preview',
					'status'       => 'paid',
				];
				$emails->__set( 'referral', $mock_referral );
			}

			// For emails that use specific tags not covered by the standard parsing.
			$manual_tags = [
				'{name}'                 => __( 'Test Affiliate', 'affiliate-wp' ),
				'{user_email}'           => 'test@example.com',
				'{transfer_id}'          => 'tr_PREVIEW123456',
				'{payout_id}'            => 'po_PREVIEW789012',
				'{expected_arrival}'     => date_i18n( get_option( 'date_format' ), strtotime( '+3 days' ) ),
				'{payout_date}'          => date_i18n( get_option( 'date_format' ) ),
				'{error_message}'        => __( 'Insufficient funds in platform account', 'affiliate-wp' ),
				'{error_code}'           => 'insufficient_funds',
				'{failure_reason}'       => __( 'Invalid account number', 'affiliate-wp' ),
				'{reversal_reason}'      => __( 'Dispute lost', 'affiliate-wp' ),
				'{requirements_list}'    => '• ' . __( 'Identity verification document', 'affiliate-wp' ) . "\n• " . __( 'Address verification', 'affiliate-wp' ),
				'{deadline}'             => date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) ),
				'{admin_payout_url}'     => admin_url( 'admin.php?page=affiliate-wp-payouts' ),
				'{admin_url}'            => admin_url( 'admin.php?page=affiliate-wp-affiliates' ),
				'{stripe_account_email}' => 'stripe@example.com',
				'{stripe_account_id}'    => 'acct_PREVIEW123456',
				'{verification_status}'  => __( 'Pending', 'affiliate-wp' ),
			];

			// Apply manual tag replacements for custom tags.
			$email_config['subject'] = str_replace( array_keys( $manual_tags ), array_values( $manual_tags ), $email_config['subject'] );
			$email_config['body']    = str_replace( array_keys( $manual_tags ), array_values( $manual_tags ), $email_config['body'] );
		}

		// Build the email HTML using the email class but don't send it.
		// We need to use reflection or a workaround since parse_tags is private.
		// The safest approach is to temporarily hijack the wp_mail function.
		$captured_email = null;

		// Override wp_mail temporarily to capture the email.
		add_filter(
			'wp_mail',
			function ( $args ) use ( &$captured_email ) {
				$captured_email = $args;
				// Return empty array to prevent actual sending.
				return [
					'to'      => '',
					'subject' => '',
					'message' => '',
					'headers' => '',
				];
			},
			999999
		);

		// Set heading for the email template.
		$emails->__set( 'heading', $email_config['subject'] );

		// Call send but it won't actually send due to our filter.
		$emails->send( 'preview@example.com', $email_config['subject'], $email_config['body'] );

		// Remove our filter.
		remove_all_filters( 'wp_mail', 999999 );

		// Extract the formatted content.
		$formatted_body    = isset( $captured_email['message'] ) ? $captured_email['message'] : $email_config['body'];
		$formatted_subject = isset( $captured_email['subject'] ) ? $captured_email['subject'] : $email_config['subject'];

		// Get from information.
		$from_name  = affiliate_wp()->settings->get( 'from_name', get_bloginfo( 'name' ) );
		$from_email = affiliate_wp()->settings->get( 'from_email', get_option( 'admin_email' ) );

		// Return the preview data.
		wp_send_json_success(
			[
				'subject'     => $formatted_subject,
				'from_name'   => $from_name,
				'from_email'  => $from_email,
				'body_html'   => stripslashes( $formatted_body ),
				'data_source' => $preview_data_source,
			]
		);
	}

	/**
	 * Sanitize emails settings.
	 *
	 * @since 2.29.0
	 *
	 * @param array $input The input settings array.
	 * @return array The sanitized settings.
	 */
	public function sanitize_emails_settings( $input ) {
		// Get existing email_notifications from database to preserve other email settings.
		$saved_settings        = get_option( 'affwp_settings', [] );
		$current_notifications = isset( $saved_settings['email_notifications'] ) ? $saved_settings['email_notifications'] : [];

		// Initialize email_notifications array if not present.
		if ( ! isset( $input['email_notifications'] ) || ! is_array( $input['email_notifications'] ) ) {
			$input['email_notifications'] = [];
		}

		// All email notification fields - now using consistent 1/0 format.
		$all_email_fields = [
			// Core emails.
			'admin_affiliate_registration_email',
			'admin_new_referral_email',
			'affiliate_new_referral_email',
			'affiliate_application_accepted_email',
			'affiliate_application_pending_email',
			'affiliate_application_rejected_email',
			// Stripe emails.
			'stripe_transfer_created',
			'stripe_transfer_created_admin',
			'stripe_transfer_failed_affiliate',
			'stripe_transfer_failed_admin',
			'stripe_transfer_reversed_admin',
			'stripe_payout_completed_affiliate',
			'stripe_payout_completed_admin',
			'stripe_payout_failed_affiliate',
			'stripe_payout_failed_admin',
			'stripe_account_verification_required_affiliate',
			'stripe_account_verification_required_admin',
			'stripe_account_connected_affiliate',
			'stripe_account_connected_admin',
		];

		// Start with existing notifications to preserve other email settings.
		$complete_notifications = $current_notifications;

		// Handle all email notifications consistently - store 1 when enabled, remove when disabled.
		foreach ( $all_email_fields as $field ) {
			if ( ! empty( $input['email_notifications'][ $field ] ) ) {
				$complete_notifications[ $field ] = 1;
			} else {
				unset( $complete_notifications[ $field ] );
			}
		}

		$input['email_notifications'] = $complete_notifications;

		// Sanitize text fields.
		$text_fields = [
			'affiliate_manager_email',
			'email_logo',
			'email_template',
			'from_name',
			// Subject fields.
			'registration_subject',
			'new_admin_referral_subject',
			'referral_subject',
			'accepted_subject',
			'pending_subject',
			'rejection_subject',
			'stripe_transfer_created_subject',
			'stripe_transfer_created_admin_subject',
			'stripe_transfer_failed_affiliate_subject',
			'stripe_transfer_failed_admin_subject',
			'stripe_transfer_reversed_admin_subject',
			'stripe_payout_completed_affiliate_subject',
			'stripe_payout_completed_admin_subject',
			'stripe_payout_failed_affiliate_subject',
			'stripe_payout_failed_admin_subject',
			'stripe_account_verification_required_affiliate_subject',
			'stripe_account_verification_required_admin_subject',
			'stripe_account_connected_affiliate_subject',
			'stripe_account_connected_admin_subject',
		];

		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$input[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		// Sanitize email field.
		if ( isset( $input['from_email'] ) ) {
			$input['from_email'] = sanitize_email( $input['from_email'] );
		}

		// Handle affiliate email summaries checkbox.
		$input['affiliate_email_summaries'] = ! empty( $input['affiliate_email_summaries'] ) ? 1 : 0;

		// Body fields use wp_kses_post for HTML content.
		$body_fields = [
			'registration_email',
			'new_admin_referral_email',
			'referral_email',
			'accepted_email',
			'pending_email',
			'rejection_email',
			'stripe_transfer_created_body',
			'stripe_transfer_created_admin_body',
			'stripe_transfer_failed_affiliate_body',
			'stripe_transfer_failed_admin_body',
			'stripe_transfer_reversed_admin_body',
			'stripe_payout_paid_affiliate_body',
			'stripe_payout_completed_admin_body',
			'stripe_payout_failed_affiliate_body',
			'stripe_payout_failed_admin_body',
			'stripe_account_verification_required_affiliate_body',
			'stripe_account_verification_required_admin_body',
			'stripe_account_connected_affiliate_body',
			'stripe_account_connected_admin_body',
		];

		foreach ( $body_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$input[ $field ] = wp_kses_post( $input[ $field ] );
			}
		}

		return $input;
	}

	/**
	 * Fix email notification enabled check to properly handle 0 values.
	 *
	 * The core function uses array_key_exists() which returns true even for 0 values.
	 * We need to check if the value is actually truthy.
	 *
	 * @since 2.29.0
	 *
	 * @param bool   $enabled            Whether the email notification is enabled.
	 * @param string $email_notification Email notification slug.
	 * @return bool
	 */
	public function fix_email_notification_enabled( $enabled, $email_notification ) {
		$notifications = affiliate_wp()->settings->get( 'email_notifications', [] );

		// If the key exists in the array, check if its value is truthy.
		if ( array_key_exists( $email_notification, $notifications ) ) {
			return ! empty( $notifications[ $email_notification ] );
		}

		// For Stripe and PayPal emails, also check with _enabled suffix.
		// These are stored with _enabled suffix but checked without it.
		if ( strpos( $email_notification, 'stripe_' ) === 0 ) {
			$enabled_key = $email_notification . '_enabled';
			if ( array_key_exists( $enabled_key, $notifications ) ) {
				return ! empty( $notifications[ $enabled_key ] );
			}
		}

		// Otherwise, return the original value.
		return $enabled;
	}

	/**
	 * Save email settings.
	 *
	 * @since 2.29.0
	 */
	public function save_email_settings() {
		// Settings are saved through the main settings handler.
		// This is here for any custom processing if needed.
	}
}

// Initialize the class and store in global.
global $affwp_emails_tab;
$affwp_emails_tab = new AffiliateWP_Admin_Emails_Tab();
