<?php
/**
 * Fraud Detection Helper Functions
 *
 * @package     AffiliateWP
 * @subpackage  Fraud Detection
 * @copyright   Copyright (c) 2025, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.28.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the IP address of the current visitor.
 *
 * Respects the 'disable_ip_logging' setting for GDPR compliance.
 *
 * @since 2.28.0
 *
 * @return string IP address or empty string if IP logging is disabled.
 */
function affwp_fraud_get_ip() {
	// Respect GDPR setting.
	if ( affiliate_wp()->settings->get( 'disable_ip_logging' ) ) {
		return '';
	}

	$ip = '';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = filter_var( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ), FILTER_VALIDATE_IP );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ips = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		if ( is_array( $ips ) ) {
			$ip = filter_var( trim( $ips[0] ), FILTER_VALIDATE_IP );
		}
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
	}

	return $ip ?: '';
}

/**
 * Calculate time delta between two timestamps.
 *
 * @since 2.28.0
 *
 * @param int $timestamp1 First timestamp.
 * @param int $timestamp2 Second timestamp.
 * @return array {
 *     Time delta information.
 *
 *     @type int    $seconds Time difference in seconds.
 *     @type string $human   Human-readable time difference.
 * }
 */
function affwp_fraud_calculate_time_delta( $timestamp1, $timestamp2 ) {
	$seconds = abs( $timestamp2 - $timestamp1 );

	return array(
		'seconds' => $seconds,
		'human'   => human_time_diff( $timestamp1, $timestamp2 ),
	);
}

/**
 * Get human-readable label for a fraud flag type.
 *
 * @since 2.28.0
 *
 * @param string $flag_type Flag type (e.g., 'self_referral', 'ppc_traffic').
 * @return string Human-readable label.
 */
function affwp_fraud_get_flag_label( $flag_type ) {
	$labels = array(
		'self_referral'   => __( 'Self-Referral (Email Match)', 'affiliate-wp' ),
		'self_referral_ip' => __( 'Self-Referral (IP Address Match)', 'affiliate-wp' ),
		'ppc_traffic'     => __( 'PPC Traffic Detected', 'affiliate-wp' ),
		'referring_site'  => __( 'Referring Site Mismatch', 'affiliate-wp' ),
		'conversion_rate' => __( 'Conversion Rate Out of Range', 'affiliate-wp' ),
		'ip_velocity'     => __( 'IP Velocity Exceeded', 'affiliate-wp' ),
	);

	return isset( $labels[ $flag_type ] ) ? $labels[ $flag_type ] : $flag_type;
}

/**
 * Get detailed description for a fraud flag type.
 *
 * @since 2.28.0
 *
 * @param string $flag_type Flag type.
 * @param array  $meta      Optional. Additional metadata about the flag.
 * @return string Detailed description.
 */
function affwp_fraud_get_flag_description( $flag_type, $meta = array() ) {
	switch ( $flag_type ) {
		case 'self_referral':
			return __( 'This referral was flagged because the customer email matches the affiliate email.', 'affiliate-wp' );

		case 'self_referral_ip':
			if ( ! empty( $meta['registration_ip'] ) && ! empty( $meta['purchase_ip'] ) ) {
				return sprintf(
					/* translators: 1: Registration IP, 2: Purchase IP, 3: Time delta */
					__( 'This referral was flagged because the purchase IP (%1$s) matches the affiliate registration IP (%2$s). Time since registration: %3$s', 'affiliate-wp' ),
					$meta['purchase_ip'],
					$meta['registration_ip'],
					$meta['time_delta_human'] ?? __( 'unknown', 'affiliate-wp' )
				);
			}
			return __( 'This referral was flagged because the purchase IP matches the affiliate registration IP.', 'affiliate-wp' );

		case 'ppc_traffic':
			if ( ! empty( $meta['platform'] ) ) {
				return sprintf(
					/* translators: %s: PPC platform name */
					__( 'This referral was flagged because it originated from paid advertising traffic (%s).', 'affiliate-wp' ),
					$meta['platform']
				);
			}
			return __( 'This referral was flagged because it originated from paid advertising traffic.', 'affiliate-wp' );

		case 'referring_site':
			return __( 'This referral was flagged because the referring site does not match the URL listed in the affiliate\'s application.', 'affiliate-wp' );

		case 'conversion_rate':
			return __( 'This referral was flagged because the affiliate conversion rate was outside of the defined limits.', 'affiliate-wp' );

		case 'ip_velocity':
			if ( ! empty( $meta['count'] ) && ! empty( $meta['threshold'] ) ) {
				return sprintf(
					/* translators: 1: Number of registrations, 2: Threshold count */
					__( 'This affiliate was flagged because %1$d registrations from the same IP were detected (threshold: %2$d).', 'affiliate-wp' ),
					$meta['count'],
					$meta['threshold']
				);
			}
			return __( 'This affiliate was flagged due to multiple registrations from the same IP address.', 'affiliate-wp' );

		default:
			return $flag_type;
	}
}
