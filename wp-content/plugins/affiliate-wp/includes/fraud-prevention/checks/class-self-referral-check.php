<?php
/**
 * Self-Referral Detection
 *
 * Detects when an affiliate attempts to earn commissions on their own purchases
 * by comparing customer email with affiliate email.
 *
 * @package     AffiliateWP
 * @subpackage  Fraud Prevention
 * @copyright   Copyright (c) 2025, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.28.0
 */

namespace AffiliateWP\Fraud_Prevention\Checks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Self-Referral Check Class.
 *
 * @since 2.31.0
 */
class Self_Referral_Check {

	/**
	 * Check if customer email matches affiliate email (self-referral).
	 *
	 * This is the primary self-referral detection method available to all license tiers.
	 *
	 * @since 2.31.0
	 *
	 * @param bool $is_affiliate_email Whether the email is the affiliate email.
	 * @return bool Whether to block the referral.
	 */
	public function check_email_match( $is_affiliate_email ) {
		// If not a self-referral, no action needed.
		if ( ! $is_affiliate_email ) {
			return $is_affiliate_email;
		}

		// Get setting for self-referral handling.
		$setting = affiliate_wp()->settings->get( 'fraud_prevention_self_referrals', 'reject' );

		// If set to reject (default), block the referral and flag it so admins can see the reason.
		if ( 'reject' === $setting ) {
			add_filter( 'affwp_fraud_prevention_flag_self_referral', '__return_true' );
			return $is_affiliate_email; // Return true to block.
		}

		// If set to flag, allow but mark for flagging.
		if ( 'flag' === $setting ) {
			add_filter( 'affwp_fraud_prevention_flag_self_referral', '__return_true' );
			return false; // Return false to allow the referral.
		}

		// If set to allow, permit the self-referral.
		if ( 'allow' === $setting ) {
			return false; // Return false to allow the referral.
		}

		// Default: reject.
		return $is_affiliate_email;
	}

	/**
	 * Maybe override affiliate tracking validation for self-referrals.
	 *
	 * Allows valid affiliates to track even if they're logged in, based on settings.
	 *
	 * @since 2.31.0
	 *
	 * @param bool $ret          Whether the current affiliate is valid.
	 * @param int  $affiliate_id Tracked affiliate ID.
	 * @return bool Whether the current tracked affiliate is valid.
	 */
	public function maybe_override_tracking( $ret, $affiliate_id ) {
		// Always allow active affiliates to proceed through referral creation.
		// Integration-level email checks will handle rejection/flagging with correct amounts.
		if ( 'active' === affwp_get_affiliate_status( $affiliate_id ) ) {
			return true;
		}

		return $ret;
	}
}
