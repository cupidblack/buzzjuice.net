<?php
/**
 * PPC Traffic Detection
 *
 * Detects paid advertising traffic (Google Ads, Facebook Ads, etc.) by analyzing
 * click IDs and UTM parameters.
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
 * PPC Detection Class.
 *
 * @since 2.31.0
 */
class PPC_Detection {

	/**
	 * Click ID parameter mappings.
	 *
	 * Maps click ID parameters to their respective platforms.
	 *
	 * @since 2.31.0
	 * @var   array
	 */
	private $click_id_params = array(
		'gclid'     => 'Google Ads',
		'gbraid'    => 'Google Ads (iOS 14.5+)',
		'wbraid'    => 'Google Ads (iOS 14.5+)',
		'fbclid'    => 'Facebook Ads',
		'msclkid'   => 'Microsoft Advertising',
		'ttclid'    => 'TikTok Ads',
		'epik'      => 'Pinterest Ads',
		'ScCid'     => 'Snapchat Ads',
		'rdt_cid'   => 'Reddit Ads',
		'twclid'    => 'Twitter Ads',
		'li_fat_id' => 'LinkedIn Ads',
	);

	/**
	 * UTM medium values that indicate PPC traffic.
	 *
	 * @since 2.31.0
	 * @var   array
	 */
	private $ppc_utm_mediums = array(
		'cpc',
		'ppc',
		'paid',
		'paidsearch',
		'paid_search',
		'paid-search',
		'paidsocial',
		'paid_social',
		'paid-social',
	);

	/**
	 * Detect PPC traffic from a referrer URL.
	 *
	 * @since 2.31.0
	 *
	 * @param string $referrer Referrer URL.
	 * @return array {
	 *     PPC detection results.
	 *
	 *     @type bool   $is_ppc   Whether PPC traffic was detected.
	 *     @type string $platform Platform name (e.g., 'Google Ads', 'Facebook Ads').
	 *     @type string $method   Detection method ('click_id' or 'utm_parameters').
	 *     @type string $param    The specific parameter that triggered detection.
	 * }
	 */
	public function detect( $referrer ) {
		$result = array(
			'is_ppc'   => false,
			'platform' => '',
			'method'   => '',
			'param'    => '',
		);

		if ( empty( $referrer ) ) {
			return $result;
		}

		// First, check for click IDs (most reliable method).
		$click_id_detection = $this->detect_via_click_id( $referrer );
		if ( $click_id_detection['is_ppc'] ) {
			return $click_id_detection;
		}

		// Second, check UTM parameters.
		$utm_detection = $this->detect_via_utm( $referrer );
		if ( $utm_detection['is_ppc'] ) {
			return $utm_detection;
		}

		return $result;
	}

	/**
	 * Detect PPC traffic from the current request parameters.
	 *
	 * Checks $_GET and $_REQUEST for PPC indicators (click IDs and UTM parameters).
	 * This is used to detect PPC traffic on the landing page itself.
	 *
	 * @since 2.31.0
	 *
	 * @return array {
	 *     PPC detection results.
	 *
	 *     @type bool   $is_ppc   Whether PPC traffic was detected.
	 *     @type string $platform Platform name (e.g., 'Google Ads', 'Facebook Ads').
	 *     @type string $method   Detection method ('click_id' or 'utm_parameters').
	 *     @type string $param    The specific parameter that triggered detection.
	 * }
	 */
	public function detect_from_request() {
		$result = array(
			'is_ppc'   => false,
			'platform' => '',
			'method'   => '',
			'param'    => '',
		);

		// First, check for click IDs (most reliable method).
		foreach ( $this->click_id_params as $param => $platform ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Not processing form data.
			if ( isset( $_REQUEST[ $param ] ) && ! empty( $_REQUEST[ $param ] ) ) {
				$result['is_ppc']   = true;
				$result['platform'] = $platform;
				$result['method']   = 'click_id';
				$result['param']    = $param;
				return $result;
			}
		}

		// Second, check UTM parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Not processing form data.
		if ( isset( $_REQUEST['utm_medium'] ) && ! empty( $_REQUEST['utm_medium'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Not processing form data.
			$utm_medium = strtolower( trim( sanitize_text_field( wp_unslash( $_REQUEST['utm_medium'] ) ) ) );

			if ( in_array( $utm_medium, $this->ppc_utm_mediums, true ) ) {
				$result['is_ppc']  = true;
				$result['method']  = 'utm_parameters';
				$result['param']   = 'utm_medium=' . $utm_medium;

				// Try to determine platform from utm_source.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Not processing form data.
				if ( isset( $_REQUEST['utm_source'] ) && ! empty( $_REQUEST['utm_source'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Not processing form data.
					$result['platform'] = $this->guess_platform_from_source( sanitize_text_field( wp_unslash( $_REQUEST['utm_source'] ) ) );
				} else {
					$result['platform'] = __( 'Unknown PPC Platform', 'affiliate-wp' );
				}
			}
		}

		return $result;
	}

	/**
	 * Detect PPC traffic via click ID parameters.
	 *
	 * This is the most reliable method as click IDs are unique to paid advertising.
	 *
	 * @since 2.31.0
	 *
	 * @param string $url URL to check.
	 * @return array Detection results.
	 */
	private function detect_via_click_id( $url ) {
		$result = array(
			'is_ppc'   => false,
			'platform' => '',
			'method'   => 'click_id',
			'param'    => '',
		);

		// Parse the URL to get query parameters.
		$parsed_url = wp_parse_url( $url );

		if ( empty( $parsed_url['query'] ) ) {
			return $result;
		}

		parse_str( $parsed_url['query'], $query_params );

		// Check for known click ID parameters.
		foreach ( $this->click_id_params as $param => $platform ) {
			if ( isset( $query_params[ $param ] ) && ! empty( $query_params[ $param ] ) ) {
				$result['is_ppc']   = true;
				$result['platform'] = $platform;
				$result['param']    = $param;
				break;
			}
		}

		return $result;
	}

	/**
	 * Detect PPC traffic via UTM parameters.
	 *
	 * Checks if utm_medium indicates paid traffic.
	 *
	 * @since 2.31.0
	 *
	 * @param string $url URL to check.
	 * @return array Detection results.
	 */
	private function detect_via_utm( $url ) {
		$result = array(
			'is_ppc'   => false,
			'platform' => '',
			'method'   => 'utm_parameters',
			'param'    => '',
		);

		// Parse the URL to get query parameters.
		$parsed_url = wp_parse_url( $url );

		if ( empty( $parsed_url['query'] ) ) {
			return $result;
		}

		parse_str( $parsed_url['query'], $query_params );

		// Check utm_medium for PPC indicators.
		if ( isset( $query_params['utm_medium'] ) && ! empty( $query_params['utm_medium'] ) ) {
			$utm_medium = strtolower( trim( $query_params['utm_medium'] ) );

			if ( in_array( $utm_medium, $this->ppc_utm_mediums, true ) ) {
				$result['is_ppc'] = true;
				$result['param']  = 'utm_medium=' . $utm_medium;

				// Try to determine platform from utm_source.
				if ( isset( $query_params['utm_source'] ) && ! empty( $query_params['utm_source'] ) ) {
					$result['platform'] = $this->guess_platform_from_source( $query_params['utm_source'] );
				} else {
					$result['platform'] = __( 'Unknown PPC Platform', 'affiliate-wp' );
				}
			}
		}

		return $result;
	}

	/**
	 * Guess the advertising platform from UTM source.
	 *
	 * @since 2.31.0
	 *
	 * @param string $utm_source UTM source value.
	 * @return string Platform name.
	 */
	private function guess_platform_from_source( $utm_source ) {
		$utm_source = strtolower( trim( $utm_source ) );

		$source_mappings = array(
			'google'    => 'Google Ads',
			'bing'      => 'Microsoft Advertising',
			'facebook'  => 'Facebook Ads',
			'fb'        => 'Facebook Ads',
			'instagram' => 'Instagram Ads',
			'ig'        => 'Instagram Ads',
			'tiktok'    => 'TikTok Ads',
			'pinterest' => 'Pinterest Ads',
			'snapchat'  => 'Snapchat Ads',
			'twitter'   => 'Twitter Ads',
			'linkedin'  => 'LinkedIn Ads',
			'reddit'    => 'Reddit Ads',
		);

		foreach ( $source_mappings as $source_keyword => $platform_name ) {
			if ( false !== stripos( $utm_source, $source_keyword ) ) {
				return $platform_name;
			}
		}

		// If we can't determine the platform, return the utm_source as-is.
		return sprintf(
			/* translators: %s: UTM source value */
			__( 'PPC (%s)', 'affiliate-wp' ),
			ucfirst( $utm_source )
		);
	}

	/**
	 * Get all supported click ID parameters.
	 *
	 * @since 2.31.0
	 *
	 * @return array Click ID parameters and their platforms.
	 */
	public function get_click_id_params() {
		return $this->click_id_params;
	}

	/**
	 * Get all PPC UTM medium values.
	 *
	 * @since 2.31.0
	 *
	 * @return array PPC UTM medium values.
	 */
	public function get_ppc_utm_mediums() {
		return $this->ppc_utm_mediums;
	}
}
