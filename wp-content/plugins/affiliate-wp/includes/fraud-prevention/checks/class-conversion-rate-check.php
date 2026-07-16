<?php
/**
 * Conversion Rate Detection
 *
 * Detects abnormally high or low conversion rates that may indicate fraud.
 * Compares an affiliate's conversion rate against configurable thresholds.
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
 * Conversion Rate Check Class.
 *
 * @since 2.31.0
 */
class Conversion_Rate_Check {

	/**
	 * Minimum referrals required before checking conversion rate.
	 *
	 * @since 2.31.0
	 * @var   int
	 */
	const MIN_REFERRALS = 10;

	/**
	 * Check if affiliate's conversion rate is abnormal.
	 *
	 * This is a Pro feature that helps identify suspicious conversion patterns.
	 *
	 * @since 2.31.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 * @return array {
	 *     Check results.
	 *
	 *     @type bool   $is_violation     Whether this is a violation.
	 *     @type string $action           Action to take: 'allow' or 'flag'.
	 *     @type string $message          Human-readable message.
	 *     @type float  $conversion_rate  Actual conversion rate.
	 *     @type int    $referral_count   Number of referrals.
	 *     @type int    $visit_count      Number of visits.
	 * }
	 */
	public function check( $affiliate_id ) {
		$result = array(
			'is_violation'    => false,
			'action'          => 'allow',
			'message'         => '',
			'conversion_rate' => 0,
			'referral_count'  => 0,
			'visit_count'     => 0,
		);

		// Check if Pro features are available.
		if ( ! function_exists( 'affwp_can_access_pro_features' ) || ! affwp_can_access_pro_features() ) {
			return $result;
		}

		// Get setting for conversion rate handling.
		$setting = affiliate_wp()->settings->get( 'fraud_prevention_conversion_rate', 'allow' );

		// If set to allow, no checking needed.
		if ( 'allow' === $setting ) {
			return $result;
		}

		// Get min and max conversion rate thresholds.
		$min_rate = (float) affiliate_wp()->settings->get( 'fraud_prevention_min_conversion_rate', 2 );
		$max_rate = (float) affiliate_wp()->settings->get( 'fraud_prevention_max_conversion_rate', 20 );

		// Get affiliate's conversion rate.
		$stats = $this->get_affiliate_conversion_stats( $affiliate_id );

		$result['conversion_rate'] = $stats['conversion_rate'];
		$result['referral_count']  = $stats['referral_count'];
		$result['visit_count']     = $stats['visit_count'];

		// Need minimum referrals to make an accurate assessment.
		if ( $stats['referral_count'] < self::MIN_REFERRALS ) {
			return $result;
		}

		// Check if conversion rate is outside thresholds.
		if ( $stats['conversion_rate'] < $min_rate || $stats['conversion_rate'] > $max_rate ) {
			$result['is_violation'] = true;
			$result['action']       = 'flag'; // Conversion rate checks only flag, never reject.

			if ( $stats['conversion_rate'] < $min_rate ) {
				$result['message'] = sprintf(
					/* translators: 1: actual conversion rate, 2: minimum threshold */
					__( 'Conversion rate (%1$.2f%%) is below minimum threshold (%2$.2f%%)', 'affiliate-wp' ),
					$stats['conversion_rate'],
					$min_rate
				);
			} else {
				$result['message'] = sprintf(
					/* translators: 1: actual conversion rate, 2: maximum threshold */
					__( 'Conversion rate (%1$.2f%%) exceeds maximum threshold (%2$.2f%%)', 'affiliate-wp' ),
					$stats['conversion_rate'],
					$max_rate
				);
			}
		}

		return $result;
	}

	/**
	 * Get affiliate's conversion statistics.
	 *
	 * @since 2.31.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 * @return array {
	 *     Conversion statistics.
	 *
	 *     @type int   $referral_count   Number of referrals.
	 *     @type int   $visit_count      Number of visits.
	 *     @type float $conversion_rate  Conversion rate percentage.
	 * }
	 */
	private function get_affiliate_conversion_stats( $affiliate_id ) {
		$stats = array(
			'referral_count'  => 0,
			'visit_count'     => 0,
			'conversion_rate' => 0,
		);

		// Get total referral count (all statuses).
		$stats['referral_count'] = affiliate_wp()->affiliates->count(
			array(
				'affiliate_id' => $affiliate_id,
			)
		);

		// Get total visit count.
		$stats['visit_count'] = affiliate_wp()->visits->count(
			array(
				'affiliate_id' => $affiliate_id,
			)
		);

		// Calculate conversion rate.
		if ( $stats['visit_count'] > 0 ) {
			$stats['conversion_rate'] = ( $stats['referral_count'] / $stats['visit_count'] ) * 100;
		}

		return $stats;
	}

	/**
	 * Check a specific referral for conversion rate anomalies.
	 *
	 * This is called when a new referral is created to check if it should be flagged.
	 *
	 * @since 2.31.0
	 *
	 * @param int $referral_id Referral ID.
	 * @return bool Whether to flag this referral.
	 */
	public function check_referral( $referral_id ) {
		$referral = affwp_get_referral( $referral_id );

		if ( ! $referral ) {
			return false;
		}

		$result = $this->check( $referral->affiliate_id );

		return $result['is_violation'];
	}
}
