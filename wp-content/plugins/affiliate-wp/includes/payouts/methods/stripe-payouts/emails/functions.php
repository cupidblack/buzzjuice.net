<?php
/**
 * Stripe Payouts Email Functions
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Emails/Functions
 * @copyright   Copyright (c) 2024, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email template tag: payout_id
 * The Stripe payout/transfer ID (kept for backward compatibility)
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The payout ID.
 */
function affwp_email_tag_payout_id( $affiliate_id = 0, $referral = null, $tag = '' ) {
	return affwp_email_tag_stripe_transfer_id( $affiliate_id, $referral, $tag );
}

/**
 * Email template tag: stripe_transfer_id
 * The Stripe transfer ID
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The transfer ID.
 */
function affwp_email_tag_stripe_transfer_id( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	// Check for test mode first
	if ( isset( $email_data['is_test'] ) && $email_data['is_test'] ) {
		return isset( $email_data['transfer_id'] ) ? $email_data['transfer_id'] : 'tr_1ABC123DEF456GHI';
	}

	// Check for real transfer ID
	if ( isset( $email_data['transfer_id'] ) ) {
		return esc_html( $email_data['transfer_id'] );
	}

	// Check referral meta as fallback
	if ( $referral && isset( $referral->referral_id ) ) {
		$transfer_id = affwp_get_referral_meta( $referral->referral_id, 'stripe_transfer_id', true );
		if ( $transfer_id ) {
			return esc_html( $transfer_id );
		}
	}

	return '';
}

/**
 * Email template tag: stripe_error_message
 * The error message from Stripe
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The error message.
 */
function affwp_email_tag_stripe_error_message( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['error_message'] ) ) {
		return esc_html( $email_data['error_message'] );
	}
	return __( 'An unknown error occurred', 'affiliate-wp' );
}

/**
 * Email template tag: payout_error_message
 * The error message for payout failures
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The error message.
 */
function affwp_email_tag_payout_error_message( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['error_message'] ) ) {
		return esc_html( $email_data['error_message'] );
	}
	return __( 'An unknown error occurred', 'affiliate-wp' );
}

/**
 * Email template tag: stripe_error_code
 * The error code from Stripe
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The error code.
 */
function affwp_email_tag_stripe_error_code( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['error_code'] ) ) {
		return esc_html( $email_data['error_code'] );
	}
	return '';
}

/**
 * Email template tag: payout_error_code
 * The error code for payout failures
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The error code.
 */
function affwp_email_tag_payout_error_code( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['error_code'] ) ) {
		return esc_html( $email_data['error_code'] );
	}
	return '';
}

/**
 * Email template tag: payout_date
 * The date the payout was initiated
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The formatted date.
 */
function affwp_email_tag_stripe_payout_date( $affiliate_id = 0, $referral = null, $tag = '' ) {
	return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), current_time( 'timestamp' ) );
}

/**
 * Email template tag: payout_date (alias)
 * The date alias
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The formatted date.
 */
function affwp_email_tag_payout_date( $affiliate_id = 0, $referral = null, $tag = '' ) {
	return affwp_email_tag_stripe_payout_date( $affiliate_id, $referral, $tag );
}

/**
 * Email template tag: stripe_dashboard_url
 * Link to the Stripe dashboard
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The Stripe dashboard URL.
 */
function affwp_email_tag_stripe_dashboard_url( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter to determine context
	$email_data     = apply_filters( 'affwp_stripe_email_tag_data', [] );
	$recipient_type = isset( $email_data['recipient_type'] ) ? $email_data['recipient_type'] : 'affiliate';

	// Admin gets Stripe dashboard URL
	if ( 'admin' === $recipient_type ) {
		$test_mode = affwp_stripe_payouts_is_test_api_mode();
		if ( $test_mode ) {
			return 'https://dashboard.stripe.com/test/';
		}
		return 'https://dashboard.stripe.com/';
	}

	// Affiliate gets affiliate area settings URL
	// Check if portal is enabled
	$portal_enabled = affiliate_wp()->settings->get( 'portal_enabled', false );

	if ( $portal_enabled ) {
		// Get the portal page URL
		$portal_page_id = affiliate_wp()->settings->get( 'affiliates_portal_page' );
		if ( $portal_page_id ) {
			$portal_url = get_permalink( $portal_page_id );
			if ( $portal_url ) {
				return trailingslashit( $portal_url ) . 'settings/';
			}
		}
	}

	// Fall back to classic affiliate area
	$affiliate_area_page_id = affiliate_wp()->settings->get( 'affiliates_page' );
	if ( $affiliate_area_page_id ) {
		$affiliate_area_url = get_permalink( $affiliate_area_page_id );
		if ( $affiliate_area_url ) {
			return add_query_arg( 'tab', 'settings', $affiliate_area_url );
		}
	}

	// Last resort: just return the home URL with a note
	return home_url( '/affiliate-area/settings/' );
}

/**
 * Email template tag: admin_payout_url
 * Link to the admin payout management page
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The admin URL.
 */
function affwp_email_tag_admin_payout_url( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	// Try to get from email data first
	if ( isset( $email_data['referral_id'] ) ) {
		$referral_id = $email_data['referral_id'];
	} elseif ( $referral && isset( $referral->referral_id ) ) {
		$referral_id = $referral->referral_id;
	} else {
		$referral_id = 0;
	}

	if ( $referral_id ) {
		return affwp_admin_url( 'referrals', [ 'referral_id' => $referral_id, 'action' => 'edit_referral' ] );
	}
	return affwp_admin_url( 'referrals' );
}

/**
 * Email template tag: payout_failure_reason
 * The failure reason for a payout failure
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The failure reason.
 */
function affwp_email_tag_payout_failure_reason( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['failure_reason'] ) ) {
		return esc_html( $email_data['failure_reason'] );
	}
	return __( 'Unknown reason', 'affiliate-wp' );
}

/**
 * Email template tag: stripe_requirements
 * The list of Stripe verification requirements
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The requirements list.
 */
function affwp_email_tag_stripe_requirements( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	// Check for test mode first
	if ( isset( $email_data['is_test'] ) && $email_data['is_test'] ) {
		return isset( $email_data['requirements_list'] ) ? $email_data['requirements_list'] : __( 'Identity verification, Bank account information', 'affiliate-wp' );
	}

	// Check for real requirements
	if ( isset( $email_data['requirements_list'] ) ) {
		return esc_html( $email_data['requirements_list'] );
	}

	// Return generic fallback for production
	return __( 'Additional verification required', 'affiliate-wp' );
}

/**
 * Email template tag: stripe_requirements_deadline
 * The deadline for Stripe verification requirements
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The deadline.
 */
function affwp_email_tag_stripe_requirements_deadline( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['deadline'] ) ) {
		return esc_html( $email_data['deadline'] );
	}
	return date_i18n( get_option( 'date_format' ), strtotime( '+30 days' ) );
}

/**
 * Email template tag: stripe_account_email
 * The Stripe connected account email
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The Stripe account email.
 */
function affwp_email_tag_stripe_account_email( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['stripe_account_email'] ) ) {
		return esc_html( $email_data['stripe_account_email'] );
	}
	return '';
}

/**
 * Email template tag: stripe_account_id
 * The Stripe connected account ID
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The Stripe account ID.
 */
function affwp_email_tag_stripe_account_id( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	// Check for test mode first
	if ( isset( $email_data['is_test'] ) && $email_data['is_test'] ) {
		return isset( $email_data['stripe_account_id'] ) ? $email_data['stripe_account_id'] : 'acct_1AbCdEfGhIjKlMnO';
	}

	// Check for real stripe account ID
	if ( isset( $email_data['stripe_account_id'] ) ) {
		return esc_html( $email_data['stripe_account_id'] );
	}

	// Return empty for production (no test data in production)
	return '';
}

/**
 * Email template tag: verification_status
 * The verification status
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The verification status.
 */
function affwp_email_tag_verification_status( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Get email data from filter
	$email_data = apply_filters( 'affwp_stripe_email_tag_data', [] );

	if ( isset( $email_data['verification_status'] ) ) {
		return esc_html( $email_data['verification_status'] );
	}
	// Default to 'Verified' for test emails (more appropriate for success emails)
	return __( 'Verified', 'affiliate-wp' );
}

/**
 * Email template tag: admin_affiliate_url
 * Link to edit the affiliate in the admin
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The affiliate edit URL.
 */
function affwp_email_tag_admin_affiliate_url( $affiliate_id = 0, $referral = null, $tag = '' ) {
	if ( $affiliate_id ) {
		return affwp_admin_url( 'affiliates', [ 'affiliate_id' => $affiliate_id, 'action' => 'edit_affiliate' ] );
	}
	return affwp_admin_url( 'affiliates' );
}

/**
 * Email template tag: affiliate_payout_settings_url
 * Link to the affiliate's payout settings page
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID.
 * @param mixed  $referral The referral object.
 * @param string $tag The tag being processed.
 * @return string The payout settings URL.
 */
function affwp_email_tag_affiliate_payout_settings_url( $affiliate_id = 0, $referral = null, $tag = '' ) {
	// Check if portal is enabled
	$portal_enabled = affiliate_wp()->settings->get( 'portal_enabled', false );

	if ( $portal_enabled ) {
		// Get the portal page URL
		$portal_page_id = affiliate_wp()->settings->get( 'affiliates_portal_page' );
		if ( $portal_page_id ) {
			$portal_url = get_permalink( $portal_page_id );
			if ( $portal_url ) {
				return trailingslashit( $portal_url ) . 'settings/';
			}
		}
	}

	// Fall back to classic affiliate area
	$affiliate_area_page_id = affiliate_wp()->settings->get( 'affiliates_page' );
	if ( $affiliate_area_page_id ) {
		$affiliate_area_url = get_permalink( $affiliate_area_page_id );
		if ( $affiliate_area_url ) {
			return add_query_arg( 'tab', 'settings', $affiliate_area_url );
		}
	}

	// Last resort: just return the home URL with a note
	return home_url( '/affiliate-area/settings/' );
}

/**
 * Register Stripe Payout email tags
 *
 * @since 2.29.0
 * @param array $email_tags The existing email tags.
 * @return array Modified email tags.
 */
function affwp_stripe_payouts_register_email_tags( $email_tags ) {
	$stripe_tags = [
		// Stripe-specific tags
		[
			'tag'         => 'stripe_transfer_id',
			'description' => __( 'The Stripe transfer ID', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_stripe_transfer_id',
		],
		[
			'tag'         => 'stripe_requirements',
			'description' => __( 'The list of Stripe verification requirements', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_stripe_requirements',
		],
		[
			'tag'         => 'stripe_requirements_deadline',
			'description' => __( 'The deadline for Stripe verification requirements', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_stripe_requirements_deadline',
		],
		[
			'tag'         => 'stripe_verification_status',
			'description' => __( 'The Stripe verification status', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_stripe_verification_status',
		],
		[
			'tag'         => 'stripe_account_email',
			'description' => __( 'The Stripe connected account email', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_stripe_account_email',
		],
		[
			'tag'         => 'stripe_account_id',
			'description' => __( 'The Stripe connected account ID', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_stripe_account_id',
		],
		[
			'tag'         => 'stripe_dashboard_url',
			'description' => __( 'Link to the Stripe Dashboard', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_stripe_dashboard_url',
		],
		// Payout-generic tags (work across all payout methods)
		[
			'tag'         => 'payout_id',
			'description' => __( 'The payout/transfer ID', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_payout_id',
		],
		[
			'tag'         => 'payout_date',
			'description' => __( 'The date the payout was initiated', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_payout_date',
		],
		[
			'tag'         => 'payout_error_message',
			'description' => __( 'The error message for payout failures', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_payout_error_message',
		],
		[
			'tag'         => 'payout_error_code',
			'description' => __( 'The error code for payout failures', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_payout_error_code',
		],
		[
			'tag'         => 'payout_failure_reason',
			'description' => __( 'The failure reason for a payout failure', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_payout_failure_reason',
		],
		// Admin URL tags
		[
			'tag'         => 'admin_payout_url',
			'description' => __( 'Link to the admin payout details page', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_admin_payout_url',
		],
		[
			'tag'         => 'admin_affiliate_url',
			'description' => __( 'Link to edit the affiliate in the admin', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_admin_affiliate_url',
		],
		// Affiliate URL tags
		[
			'tag'         => 'affiliate_payout_settings_url',
			'description' => __( 'Link to the affiliate payout settings page', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_affiliate_payout_settings_url',
		],
	];

	return array_merge( $email_tags, $stripe_tags );
}
add_filter( 'affwp_email_tags', 'affwp_stripe_payouts_register_email_tags' );

/**
 * Check if Stripe payout email notifications are enabled
 *
 * @since 2.29.0
 * @param string $notification_type The notification type.
 * @return bool Whether the notification is enabled.
 */
function affwp_stripe_payouts_email_enabled( $notification_type ) {
	return affwp_email_notification_enabled( $notification_type );
}

/**
 * Get default Stripe email template body.
 *
 * Provides access to the centralized default email template bodies for Stripe payouts.
 * These templates are used when no custom template has been saved in the database.
 *
 * @since 2.29.0
 * @param string $type The email template type.
 * @return string The default template body content, or empty string if type not found.
 */
function affwp_get_default_stripe_template( $type ) {
	// Include the templates file if function doesn't exist yet.
	if ( ! function_exists( 'affwp_get_stripe_email_content' ) ) {
		require_once __DIR__ . '/templates.php';
	}

	return affwp_get_stripe_email_content( $type, 'body' );
}

// Include email action files.
require_once __DIR__ . '/transfer-created.php';
require_once __DIR__ . '/transfer-failed.php';
require_once __DIR__ . '/transfer-reversed.php';
require_once __DIR__ . '/payout-completed.php';
require_once __DIR__ . '/payout-failed.php';
require_once __DIR__ . '/account-verification-required.php';
require_once __DIR__ . '/account-connected.php';
