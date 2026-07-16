<?php
/**
 * IP Velocity Detection
 *
 * Detects when multiple affiliate registrations occur from the same IP address
 * within a specified time window, which may indicate fraudulent activity.
 *
 * @package     AffiliateWP
 * @subpackage  Fraud Prevention
 * @copyright   Copyright (c) 2025, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.31.0
 */

namespace AffiliateWP\Fraud_Prevention\Checks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IP Velocity Check Class.
 *
 * Monitors affiliate registrations from the same IP address to detect
 * potential fraud patterns like mass account creation.
 *
 * @since 2.31.0
 */
class IP_Velocity_Check {

	/**
	 * Check if the current registration IP has exceeded the velocity threshold.
	 *
	 * @since 2.31.0
	 *
	 * @param string $registration_ip The IP address of the current registration.
	 * @return array {
	 *     Check result.
	 *
	 *     @type bool   $is_violation  Whether the IP velocity threshold was exceeded.
	 *     @type string $action        The action to take: 'allow', 'flag', or 'reject'.
	 *     @type int    $count         Number of registrations from this IP in the time window.
	 *     @type int    $threshold     The configured threshold.
	 *     @type int    $window_hours  The configured time window in hours.
	 *     @type string $message       Human-readable message about the check result.
	 * }
	 */
	public function check( $registration_ip ) {
		$result = [
			'is_violation' => false,
			'action'       => 'allow',
			'count'        => 0,
			'threshold'    => 0,
			'window_hours' => 0,
			'message'      => '',
		];

		// Check if Pro features are available.
		if ( ! function_exists( 'affwp_can_access_pro_features' ) || ! affwp_can_access_pro_features() ) {
			return $result;
		}

		// Get the setting.
		$setting = affiliate_wp()->settings->get( 'fraud_prevention_ip_velocity', 'allow' );

		// Backwards compatibility: normalize 'reject' to 'require_approval'.
		if ( 'reject' === $setting ) {
			$setting = 'require_approval';
		}

		// If set to allow, skip the check.
		if ( 'allow' === $setting ) {
			return $result;
		}

		// Skip if IP is empty (GDPR setting may disable IP logging).
		if ( empty( $registration_ip ) ) {
			return $result;
		}

		// Get threshold and time window settings.
		$threshold    = (int) affiliate_wp()->settings->get( 'fraud_prevention_ip_velocity_threshold', 3 );
		$window_hours = (int) affiliate_wp()->settings->get( 'fraud_prevention_ip_velocity_window', 24 );

		$result['threshold']    = $threshold;
		$result['window_hours'] = $window_hours;

		// Count registrations from this IP within the time window.
		$count = $this->count_registrations_from_ip( $registration_ip, $window_hours );

		$result['count'] = $count;

		// Check if threshold is exceeded.
		// Note: $count already includes the current registration because
		// capture_registration_ip() stores the IP before this check runs.
		if ( $count >= $threshold ) {
			$result['is_violation'] = true;
			$result['action']       = $setting;
			$result['message']      = sprintf(
				/* translators: 1: Number of registrations, 2: Threshold, 3: Time window in hours */
				__( 'IP velocity exceeded: %1$d registrations from this IP in the last %3$d hours (threshold: %2$d).', 'affiliate-wp' ),
				$count,
				$threshold,
				$window_hours
			);
		}

		return $result;
	}

	/**
	 * Count affiliate registrations from a specific IP within a time window.
	 *
	 * @since 2.31.0
	 *
	 * @param string $ip           The IP address to check.
	 * @param int    $window_hours The time window in hours.
	 * @return int Number of registrations from this IP in the time window.
	 */
	private function count_registrations_from_ip( $ip, $window_hours ) {
		global $wpdb;

		$affiliate_meta_table = affiliate_wp()->affiliate_meta->table_name;
		$affiliates_table     = affiliate_wp()->affiliates->table_name;

		// Calculate the cutoff time.
		$cutoff_time = gmdate( 'Y-m-d H:i:s', strtotime( "-{$window_hours} hours" ) );

		// Query to count affiliates with matching registration IP within the time window.
		// We join with affiliates table to filter by registration date.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT am.affiliate_id)
				FROM {$affiliate_meta_table} am
				INNER JOIN {$affiliates_table} a ON am.affiliate_id = a.affiliate_id
				WHERE am.meta_key = 'registration_ip'
				AND am.meta_value = %s
				AND a.date_registered >= %s",
				$ip,
				$cutoff_time
			)
		);

		return (int) $count;
	}

	/**
	 * Get all affiliate IDs registered from a specific IP within a time window.
	 *
	 * Useful for admin reporting and investigation.
	 *
	 * @since 2.31.0
	 *
	 * @param string $ip           The IP address to check.
	 * @param int    $window_hours The time window in hours. Default 24.
	 * @return array Array of affiliate IDs.
	 */
	public function get_affiliates_from_ip( $ip, $window_hours = 24 ) {
		global $wpdb;

		$affiliate_meta_table = affiliate_wp()->affiliate_meta->table_name;
		$affiliates_table     = affiliate_wp()->affiliates->table_name;

		$cutoff_time = gmdate( 'Y-m-d H:i:s', strtotime( "-{$window_hours} hours" ) );

		$affiliate_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT am.affiliate_id
				FROM {$affiliate_meta_table} am
				INNER JOIN {$affiliates_table} a ON am.affiliate_id = a.affiliate_id
				WHERE am.meta_key = 'registration_ip'
				AND am.meta_value = %s
				AND a.date_registered >= %s
				ORDER BY a.date_registered DESC",
				$ip,
				$cutoff_time
			)
		);

		return array_map( 'intval', $affiliate_ids );
	}
}
