<?php
/**
 * Badge helper functions
 *
 * @package     AffiliateWP
 * @subpackage  Utils/Helpers
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a badge component
 *
 * @since 2.29.0
 *
 * @param array $args Badge arguments. See trait-badges.php for full documentation.
 * @return void
 */
function affwp_badge( $args = [] ) {
	echo affwp_get_badge( $args );
}

/**
 * Get a badge component HTML
 *
 * @since 2.29.0
 *
 * @param array $args Badge arguments. See trait-badges.php for full documentation.
 * @return string The badge HTML.
 */
function affwp_get_badge( $args = [] ) {
	// Use a static instance of a class that uses the trait.
	static $badge_renderer = null;
	
	if ( null === $badge_renderer ) {
		// Create an anonymous class that uses the trait.
		$badge_renderer = new class {
			use \AffiliateWP\Utils\Traits\Badges;
		};
	}
	
	return $badge_renderer->render_badge( $args );
}

/**
 * Render a status badge with predefined styles
 *
 * @since 2.29.0
 *
 * @param string $status Status type (active, inactive, sandbox, live, connected, etc.).
 * @param array  $args   Additional badge arguments.
 * @return void
 */
function affwp_status_badge( $status, $args = [] ) {
	echo affwp_get_status_badge( $status, $args );
}

/**
 * Get a status badge HTML with predefined styles
 *
 * @since 2.29.0
 *
 * @param string $status Status type (active, inactive, sandbox, live, connected, etc.).
 * @param array  $args   Additional badge arguments.
 * @return string The badge HTML.
 */
function affwp_get_status_badge( $status, $args = [] ) {
	// Map status types to badge configurations.
	$status_configs = [
		'active' => [
			'text'    => __( 'Active', 'affiliate-wp' ),
			'variant' => 'success',
		],
		'inactive' => [
			'text'    => __( 'Inactive', 'affiliate-wp' ),
			'variant' => 'neutral',
		],
		'sandbox' => [
			'text'    => __( 'Sandbox Mode', 'affiliate-wp' ),
			'variant' => 'warning',
		],
		'live' => [
			'text'    => __( 'Live Mode', 'affiliate-wp' ),
			'variant' => 'success',
		],
		'connected' => [
			'text'    => __( 'Connected', 'affiliate-wp' ),
			'variant' => 'success',
		],
		'setup_required' => [
			'text'    => __( 'Setup Required', 'affiliate-wp' ),
			'variant' => 'warning',
		],
		'coming_soon' => [
			'text'    => __( 'Coming Soon', 'affiliate-wp' ),
			'variant' => 'neutral',
		],
		'deprecated' => [
			'text'    => __( 'Deprecated', 'affiliate-wp' ),
			'variant' => 'danger',
		],
		'pro' => [
			'text'    => __( 'Pro', 'affiliate-wp' ),
			'variant' => 'pro',
			'size'    => 'xs',
			'border'  => false,
			'icon'    => [
				'name'     => 'lock',
				'position' => 'left',
			],
		],
		'premium' => [
			'text'    => __( 'Premium', 'affiliate-wp' ),
			'variant' => 'premium',
			'size'    => 'xs',
			'border'  => false,
			'icon'    => [
				'name'     => 'sparkles',
				'position' => 'left',
			],
		],
	];
	
	// Get the status configuration.
	if ( isset( $status_configs[ $status ] ) ) {
		$config = $status_configs[ $status ];
	} else {
		// Default configuration for unknown status.
		$config = [
			'text'    => ucfirst( str_replace( '_', ' ', $status ) ),
			'variant' => 'neutral',
		];
	}
	
	// Merge with provided args.
	$badge_args = wp_parse_args( $args, $config );
	
	return affwp_get_badge( $badge_args );
}

/**
 * Render a Pro/Premium badge with icon
 *
 * @since 2.29.0
 *
 * @param array $args Badge arguments.
 * @return void
 */
function affwp_pro_badge( $args = [] ) {
	echo affwp_get_pro_badge( $args );
}

/**
 * Get a Pro/Premium badge HTML with icon
 *
 * @since 2.29.0
 *
 * @param array $args Badge arguments.
 * @return string The badge HTML.
 */
function affwp_get_pro_badge( $args = [] ) {
	$defaults = [
		'text'    => __( 'Pro', 'affiliate-wp' ),
		'variant' => 'pro',
		'size'    => 'xs',
		'border'  => false,
		'icon'    => [
			'name'     => 'lock',
			'position' => 'left',
		],
	];
	
	$badge_args = wp_parse_args( $args, $defaults );
	
	return affwp_get_badge( $badge_args );
}

/**
 * Render an affiliate status badge
 *
 * @since 2.29.0
 *
 * @param string $status Affiliate status (active, pending, inactive, rejected).
 * @param array  $args   Additional badge arguments.
 * @return void
 */
function affwp_affiliate_status_badge( $status, $args = [] ) {
	echo affwp_get_affiliate_status_badge( $status, $args );
}

/**
 * Get an affiliate status badge HTML
 *
 * @since 2.29.0
 *
 * @param string $status Affiliate status (active, pending, inactive, rejected).
 * @param array  $args   Additional badge arguments.
 * @return string The badge HTML.
 */
function affwp_get_affiliate_status_badge( $status, $args = [] ) {
	// Map affiliate status types to badge configurations.
	$status_configs = [
		'active' => [
			'text'    => __( 'Active', 'affiliate-wp' ),
			'variant' => 'success',
			'size'    => 'sm',
		],
		'pending' => [
			'text'    => __( 'Pending', 'affiliate-wp' ),
			'variant' => 'info', // Blue instead of yellow
			'size'    => 'sm',
		],
		'inactive' => [
			'text'    => __( 'Inactive', 'affiliate-wp' ),
			'variant' => 'neutral',
			'size'    => 'sm',
		],
		'rejected' => [
			'text'    => __( 'Rejected', 'affiliate-wp' ),
			'variant' => 'danger',
			'size'    => 'sm',
		],
	];
	
	// Get the status configuration.
	if ( isset( $status_configs[ $status ] ) ) {
		$config = $status_configs[ $status ];
	} else {
		// Default configuration for unknown status.
		$config = [
			'text'    => ucfirst( str_replace( '_', ' ', $status ) ),
			'variant' => 'neutral',
			'size'    => 'sm',
		];
	}
	
	// Merge with provided args.
	$badge_args = wp_parse_args( $args, $config );
	
	return affwp_get_badge( $badge_args );
}

/**
 * Render a referral status badge
 *
 * @since 2.29.0
 *
 * @param string $status Referral status (paid, unpaid, pending, rejected).
 * @param array  $args   Additional badge arguments.
 * @return void
 */
function affwp_referral_status_badge( $status, $args = [] ) {
	echo affwp_get_referral_status_badge( $status, $args );
}

/**
 * Get a referral status badge HTML
 *
 * @since 2.29.0
 *
 * @param string $status Referral status (paid, unpaid, pending, rejected).
 * @param array  $args   Additional badge arguments.
 * @return string The badge HTML.
 */
function affwp_get_referral_status_badge( $status, $args = [] ) {
	// Map referral status types to badge configurations.
	$status_configs = [
		'paid' => [
			'text'    => __( 'Paid', 'affiliate-wp' ),
			'variant' => 'success',
			'size'    => 'sm',
		],
		'unpaid' => [
			'text'    => __( 'Unpaid', 'affiliate-wp' ),
			'variant' => 'warning',
			'size'    => 'sm',
		],
		'pending' => [
			'text'    => __( 'Pending', 'affiliate-wp' ),
			'variant' => 'info', // Blue instead of yellow
			'size'    => 'sm',
		],
		'rejected' => [
			'text'    => __( 'Rejected', 'affiliate-wp' ),
			'variant' => 'danger',
			'size'    => 'sm',
		],
	];
	
	// Get the status configuration.
	if ( isset( $status_configs[ $status ] ) ) {
		$config = $status_configs[ $status ];
	} else {
		// Default configuration for unknown status.
		$config = [
			'text'    => ucfirst( str_replace( '_', ' ', $status ) ),
			'variant' => 'neutral',
			'size'    => 'sm',
		];
	}
	
	// Merge with provided args.
	$badge_args = wp_parse_args( $args, $config );
	
	return affwp_get_badge( $badge_args );
}

/**
 * Render a payout status badge
 *
 * @since 2.29.0
 *
 * @param string $status Payout status (processing, paid, failed).
 * @param array  $args   Additional badge arguments.
 * @return void
 */
function affwp_payout_status_badge( $status, $args = [] ) {
	echo affwp_get_payout_status_badge( $status, $args );
}

/**
 * Get a payout status badge HTML
 *
 * @since 2.29.0
 *
 * @param string $status Payout status (processing, paid, failed).
 * @param array  $args   Additional badge arguments.
 * @return string The badge HTML.
 */
function affwp_get_payout_status_badge( $status, $args = [] ) {
	// Map payout status types to badge configurations.
	$status_configs = [
		'processing' => [
			'text'    => _x( 'Processing', 'payout', 'affiliate-wp' ),
			'variant' => 'info', // Blue for processing
			'size'    => 'sm',
		],
		'paid' => [
			'text'    => _x( 'Paid', 'payout', 'affiliate-wp' ),
			'variant' => 'success',
			'size'    => 'sm',
		],
		'failed' => [
			'text'    => __( 'Failed', 'affiliate-wp' ),
			'variant' => 'danger',
			'size'    => 'sm',
		],
	];
	
	// Get the status configuration.
	if ( isset( $status_configs[ $status ] ) ) {
		$config = $status_configs[ $status ];
	} else {
		// Default configuration for unknown status.
		$config = [
			'text'    => ucfirst( str_replace( '_', ' ', $status ) ),
			'variant' => 'neutral',
			'size'    => 'sm',
		];
	}
	
	// Merge with provided args.
	$badge_args = wp_parse_args( $args, $config );
	
	return affwp_get_badge( $badge_args );
}

/**
 * Render a creative status badge
 *
 * @since 2.29.0
 *
 * @param string $status Creative status (active, inactive, scheduled).
 * @param array  $args   Additional badge arguments.
 * @return void
 */
function affwp_creative_status_badge( $status, $args = [] ) {
	echo affwp_get_creative_status_badge( $status, $args );
}

/**
 * Get a creative status badge HTML
 *
 * @since 2.29.0
 *
 * @param string $status Creative status (active, inactive, scheduled).
 * @param array  $args   Additional badge arguments.
 * @return string The badge HTML.
 */
function affwp_get_creative_status_badge( $status, $args = [] ) {
	// Map creative status types to badge configurations.
	$status_configs = [
		'active' => [
			'text'    => __( 'Active', 'affiliate-wp' ),
			'variant' => 'success',
			'size'    => 'sm',
		],
		'inactive' => [
			'text'    => __( 'Inactive', 'affiliate-wp' ),
			'variant' => 'neutral',
			'size'    => 'sm',
		],
		'scheduled' => [
			'text'    => __( 'Scheduled', 'affiliate-wp' ),
			'variant' => 'info', // Blue for scheduled
			'size'    => 'sm',
		],
	];
	
	// Get the status configuration.
	if ( isset( $status_configs[ $status ] ) ) {
		$config = $status_configs[ $status ];
	} else {
		// Default configuration for unknown status.
		$config = [
			'text'    => ucfirst( str_replace( '_', ' ', $status ) ),
			'variant' => 'neutral',
			'size'    => 'sm',
		];
	}
	
	// Merge with provided args.
	$badge_args = wp_parse_args( $args, $config );
	
	return affwp_get_badge( $badge_args );
}