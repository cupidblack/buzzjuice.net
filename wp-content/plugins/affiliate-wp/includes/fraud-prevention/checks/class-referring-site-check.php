<?php
/**
 * Referring Site Detection
 *
 * Detects when affiliate traffic comes from unauthorized referring URLs by comparing
 * the referrer against the affiliate's registered websites.
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
 * Referring Site Check Class.
 *
 * @since 2.31.0
 */
class Referring_Site_Check {

	/**
	 * Check if the referrer URL matches affiliate's registered websites.
	 *
	 * This is a Pro feature that helps prevent affiliates from driving traffic
	 * from unauthorized sources.
	 *
	 * @since 2.31.0
	 *
	 * @param int    $affiliate_id Affiliate ID.
	 * @param string $referrer     Referrer URL.
	 * @return array {
	 *     Check results.
	 *
	 *     @type bool   $is_violation Whether this is a violation.
	 *     @type string $action       Action to take: 'allow', 'flag', 'reject'.
	 *     @type string $message      Human-readable message.
	 * }
	 */
	public function check( $affiliate_id, $referrer ) {
		$result = [
			'is_violation' => false,
			'action'       => 'allow',
			'message'      => '',
		];

		// Check if Pro features are available.
		if ( ! function_exists( 'affwp_can_access_pro_features' ) || ! affwp_can_access_pro_features() ) {
			return $result;
		}

		// Get setting for referring sites handling.
		$setting = affiliate_wp()->settings->get( 'fraud_prevention_referring_sites', 'allow' );

		// If set to allow, no checking needed.
		if ( 'allow' === $setting ) {
			return $result;
		}

		// Get affiliate's registered websites.
		$registered_sites = $this->get_affiliate_websites( $affiliate_id );

		// If no websites registered but traffic has a referrer, flag it.
		if ( empty( $registered_sites ) ) {
			if ( ! empty( $referrer ) ) {
				$result['is_violation'] = true;
				$result['action']       = $setting;
				$result['message']      = __( 'No registered website(s) found for affiliate', 'affiliate-wp' );
			}
			return $result;
		}

		// Check if referrer matches any registered website.
		$is_match = $this->is_referrer_match( $referrer, $registered_sites );

		// If referrer matches, allow.
		if ( $is_match ) {
			return $result;
		}

		// Referrer does not match - this is a violation.
		$result['is_violation'] = true;
		$result['action']       = $setting; // 'flag' or 'reject'.
		$result['message']      = __( 'Traffic source does not match registered website(s)', 'affiliate-wp' );

		return $result;
	}

	/**
	 * Get affiliate's registered websites.
	 *
	 * Retrieves websites from:
	 * - user_url field (Website field in profile)
	 * - Legacy website_url affiliate meta field (for backwards compatibility)
	 * - Custom registration fields from block-based forms (filtered by type 'website')
	 *
	 * @since 2.31.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 * @return array Array of registered website URLs.
	 */
	private function get_affiliate_websites( $affiliate_id ) {
		$websites = [];

		// Get user website from WordPress profile.
		$user = get_user_by( 'ID', affwp_get_affiliate_user_id( $affiliate_id ) );

		if ( $user && ! empty( $user->user_url ) ) {
			$websites[] = $user->user_url;
		}

		// Check legacy website_url meta field (for backwards compatibility).
		$website_url = affwp_get_affiliate_meta( $affiliate_id, 'website_url', true );
		if ( ! empty( $website_url ) ) {
			$websites[] = $website_url;
		}

		// Get custom registration fields from block-based form.
		$custom_fields = affwp_get_custom_registration_fields( $affiliate_id, true );

		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $custom_field ) {
				if ( isset( $custom_field['type'] ) && 'website' === $custom_field['type'] ) {
					if ( ! empty( $custom_field['meta_value'] ) ) {
						$websites[] = $custom_field['meta_value'];
					}
				}
			}
		}

		return $websites;
	}

	/**
	 * Check if referrer URL matches any registered website.
	 *
	 * Compares the domain/host of the referrer with registered websites.
	 *
	 * @since 2.31.0
	 *
	 * @param string $referrer         Referrer URL.
	 * @param array  $registered_sites Array of registered website URLs.
	 * @return bool Whether the referrer matches.
	 */
	private function is_referrer_match( $referrer, $registered_sites ) {
		if ( empty( $referrer ) ) {
			return false;
		}

		// Parse referrer URL.
		$referrer_parsed = wp_parse_url( $referrer );

		if ( empty( $referrer_parsed['host'] ) ) {
			return false;
		}

		$referrer_host = strtolower( $referrer_parsed['host'] );

		// Remove 'www.' prefix for comparison.
		$referrer_host = preg_replace( '/^www\./', '', $referrer_host );

		// Check against each registered site.
		foreach ( $registered_sites as $site ) {
			$site_parsed = wp_parse_url( $site );

			if ( empty( $site_parsed['host'] ) ) {
				continue;
			}

			$site_host = strtolower( $site_parsed['host'] );

			// Remove 'www.' prefix for comparison.
			$site_host = preg_replace( '/^www\./', '', $site_host );

			// Direct host match.
			if ( $referrer_host === $site_host ) {
				return true;
			}

			// Subdomain match (e.g., blog.example.com matches example.com).
			if ( str_ends_with( $referrer_host, '.' . $site_host ) ) {
				return true;
			}

			// Parent domain match (e.g., example.com matches blog.example.com).
			if ( str_ends_with( $site_host, '.' . $referrer_host ) ) {
				return true;
			}
		}

		return false;
	}
}
