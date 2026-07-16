<?php
/**
 * Rate Limiter for Stripe Payouts
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Classes/Rate Limiter
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Payouts_Rate_Limiter Class
 *
 * Provides rate limiting functionality for Stripe Payouts endpoints
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Payouts_Rate_Limiter {

	/**
	 * Rate limit configurations.
	 *
	 * @since 2.29.0
	 * @var array
	 */
	private static $limits = [
		'webhook' => [
			'max'    => 100,  // Maximum requests.
			'window' => 60,   // Time window in seconds (1 minute).
		],
		'oauth' => [
			'max'    => 10,   // Maximum requests.
			'window' => 60,   // Time window in seconds (1 minute).
		],
		'batch' => [
			'max'    => 5,    // Maximum requests.
			'window' => 300,  // Time window in seconds (5 minutes).
		],
		'connect' => [
			'max'    => 20,   // Maximum requests.
			'window' => 3600, // Time window in seconds (1 hour).
		],
		'admin' => [
			'max'    => 50,   // Maximum requests.
			'window' => 60,   // Time window in seconds (1 minute).
		],
	];

	/**
	 * Transient key prefix.
	 *
	 * @since 2.29.0
	 * @var string
	 */
	private const KEY_PREFIX = 'affwp_stripe_rate_';

	/**
	 * Check if a request is within rate limits.
	 *
	 * @since 2.29.0
	 * @param string $operation  The operation type (webhook, oauth, batch, etc).
	 * @param string $identifier Optional. Unique identifier (IP, user ID, etc). Defaults to IP.
	 * @return true|WP_Error True if within limits, WP_Error if rate limited.
	 */
	public function check( $operation, $identifier = null ) {
		// Check if operation has defined limits.
		if ( ! isset( self::$limits[ $operation ] ) ) {
			// No limits defined for this operation, allow it.
			return true;
		}

		// Allow admins to bypass rate limits in development.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) {
			/**
			 * Filter to disable rate limiting for admins in debug mode.
			 *
			 * @since 2.29.0
			 * @param bool $bypass Whether to bypass rate limiting.
			 */
			$bypass = apply_filters( 'affwp_stripe_payouts_bypass_rate_limit_debug', true );
			if ( $bypass ) {
				return true;
			}
		}

		// Get identifier if not provided.
		if ( null === $identifier ) {
			$identifier = $this->get_client_identifier();
		}

		// Get the limits for this operation.
		$limits = self::$limits[ $operation ];

		// Generate cache key.
		$cache_key = $this->get_cache_key( $operation, $identifier );

		// Get current count from transient.
		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			// First request in this window.
			set_transient( $cache_key, 1, $limits['window'] );
			return true;
		}

		// Check if limit exceeded.
		if ( $current_count >= $limits['max'] ) {
			// Calculate remaining time.
			$remaining_time = $this->get_remaining_time( $cache_key );

			return new WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: 1: Maximum requests allowed, 2: Time window in seconds, 3: Remaining time in seconds */
					__( 'Rate limit exceeded. Maximum %1$d requests per %2$d seconds allowed. Please try again in %3$d seconds.', 'affiliate-wp' ),
					$limits['max'],
					$limits['window'],
					$remaining_time
				),
				[
					'status'         => 429,
					'retry_after'    => $remaining_time,
					'x_ratelimit_limit'     => $limits['max'],
					'x_ratelimit_remaining' => 0,
					'x_ratelimit_reset'     => time() + $remaining_time,
				]
			);
		}

		// Increment counter.
		set_transient( $cache_key, $current_count + 1, $limits['window'] );

		return true;
	}

	/**
	 * Reset rate limit for a specific operation and identifier.
	 *
	 * @since 2.29.0
	 * @param string $operation  The operation type.
	 * @param string $identifier Optional. Unique identifier.
	 * @return bool True on success.
	 */
	public function reset( $operation, $identifier = null ) {
		if ( null === $identifier ) {
			$identifier = $this->get_client_identifier();
		}

		$cache_key = $this->get_cache_key( $operation, $identifier );
		return delete_transient( $cache_key );
	}

	/**
	 * Get remaining requests for an operation.
	 *
	 * @since 2.29.0
	 * @param string $operation  The operation type.
	 * @param string $identifier Optional. Unique identifier.
	 * @return int Number of remaining requests.
	 */
	public function get_remaining( $operation, $identifier = null ) {
		if ( ! isset( self::$limits[ $operation ] ) ) {
			return PHP_INT_MAX; // No limit.
		}

		if ( null === $identifier ) {
			$identifier = $this->get_client_identifier();
		}

		$limits    = self::$limits[ $operation ];
		$cache_key = $this->get_cache_key( $operation, $identifier );
		$current   = get_transient( $cache_key );

		if ( false === $current ) {
			return $limits['max'];
		}

		$remaining = $limits['max'] - $current;
		return max( 0, $remaining );
	}

	/**
	 * Get rate limit headers for response.
	 *
	 * @since 2.29.0
	 * @param string $operation  The operation type.
	 * @param string $identifier Optional. Unique identifier.
	 * @return array Headers array.
	 */
	public function get_headers( $operation, $identifier = null ) {
		if ( ! isset( self::$limits[ $operation ] ) ) {
			return [];
		}

		if ( null === $identifier ) {
			$identifier = $this->get_client_identifier();
		}

		$limits    = self::$limits[ $operation ];
		$remaining = $this->get_remaining( $operation, $identifier );
		$cache_key = $this->get_cache_key( $operation, $identifier );
		$reset_time = time() + $this->get_remaining_time( $cache_key );

		return [
			'X-RateLimit-Limit'     => $limits['max'],
			'X-RateLimit-Remaining' => $remaining,
			'X-RateLimit-Reset'     => $reset_time,
		];
	}

	/**
	 * Get client identifier (IP address by default).
	 *
	 * @since 2.29.0
	 * @return string
	 */
	private function get_client_identifier() {
		// Check for CloudFlare IP.
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}

		// Check for forwarded IP.
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			return trim( $ips[0] );
		}

		// Check for client IP.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return $_SERVER['HTTP_CLIENT_IP'];
		}

		// Default to remote address.
		return ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}

	/**
	 * Generate cache key for rate limiting.
	 *
	 * @since 2.29.0
	 * @param string $operation  The operation type.
	 * @param string $identifier The unique identifier.
	 * @return string
	 */
	private function get_cache_key( $operation, $identifier ) {
		// Use MD5 to ensure consistent key length.
		return self::KEY_PREFIX . $operation . '_' . md5( $identifier );
	}

	/**
	 * Get remaining time for a transient.
	 *
	 * @since 2.29.0
	 * @param string $cache_key The transient key.
	 * @return int Remaining seconds.
	 */
	private function get_remaining_time( $cache_key ) {
		// WordPress doesn't provide a way to get transient expiry time directly.
		// We'll estimate based on the window size.
		// In production, you might want to store expiry time as part of the transient value.
		
		// Get the operation from the cache key.
		$parts = explode( '_', str_replace( self::KEY_PREFIX, '', $cache_key ) );
		$operation = $parts[0];

		if ( isset( self::$limits[ $operation ] ) ) {
			// Return a conservative estimate (half the window).
			return intval( self::$limits[ $operation ]['window'] / 2 );
		}

		return 60; // Default to 60 seconds.
	}

	/**
	 * Clean up expired rate limit transients.
	 *
	 * @since 2.29.0
	 * @return int Number of transients cleaned.
	 */
	public function cleanup() {
		global $wpdb;

		// This is a maintenance task that should be run periodically.
		// Delete transients that match our pattern and are expired.
		$pattern = '_transient_timeout_' . self::KEY_PREFIX . '%';
		$now = time();

		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s 
			AND option_value < %d",
			$pattern,
			$now
		);

		return $wpdb->query( $sql );
	}

	/**
	 * Get all current rate limits for debugging.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	public function get_all_limits() {
		return self::$limits;
	}

	/**
	 * Update rate limits (for testing or dynamic configuration).
	 *
	 * @since 2.29.0
	 * @param string $operation The operation type.
	 * @param int    $max       Maximum requests.
	 * @param int    $window    Time window in seconds.
	 * @return bool
	 */
	public function update_limit( $operation, $max, $window ) {
		if ( ! is_string( $operation ) || ! is_numeric( $max ) || ! is_numeric( $window ) ) {
			return false;
		}

		self::$limits[ $operation ] = [
			'max'    => intval( $max ),
			'window' => intval( $window ),
		];

		return true;
	}
}