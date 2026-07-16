<?php
/**
 * Stripe Payouts Module Initialization
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/StripePayouts
 * @since       2.29.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Always load minimal files needed for registration
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/class-stripe-payouts.php';

// Load and initialize Stripe Payouts module
add_action(
	'plugins_loaded',
	function () {
		// Only proceed if AffiliateWP is available
		if ( ! function_exists( 'affiliate_wp' ) ) {
			return;
		}

		// Only load additional files if Stripe might be used
		if ( should_load_stripe_payouts_files() ) {
			require_once __DIR__ . '/class-input-validator.php';
			require_once __DIR__ . '/class-stripe-connect.php';
			require_once __DIR__ . '/class-rate-limiter.php';
			require_once __DIR__ . '/class-rest-controller.php';

			// Initialize REST controller
			add_action( 'rest_api_init', function() {
				$controller = new AffiliateWP_Stripe_Payouts_REST_Controller();
				$controller->register_routes();
			} );

			// Include email files
			// The functions.php file loads all other email files
			if ( file_exists( __DIR__ . '/emails/functions.php' ) ) {
				require_once __DIR__ . '/emails/functions.php';
			}

			// Include admin files when in admin
			if ( is_admin() ) {
				require_once __DIR__ . '/admin/class-admin.php';

				// Initialize admin class
				if ( class_exists( 'AffiliateWP_Stripe_Payouts_Admin' ) ) {
					new AffiliateWP_Stripe_Payouts_Admin();
				}
			}

			// Include integrations
			require_once __DIR__ . '/integrations/class-affiliate-area.php';

			// Initialize Affiliate Area integration
			if ( class_exists( 'AffiliateWP_Stripe_Payouts_Affiliate_Area' ) ) {
				new AffiliateWP_Stripe_Payouts_Affiliate_Area();
			}

			// Include Affiliate Portal integration if available
			if ( class_exists( 'AffiliateWP_Affiliate_Portal' ) ) {
				require_once __DIR__ . '/integrations/class-affiliate-portal.php';

				// Initialize Affiliate Portal integration
				if ( class_exists( 'AffiliateWP_Stripe_Payouts_Affiliate_Portal' ) ) {
					new AffiliateWP_Stripe_Payouts_Affiliate_Portal();
				}
			}
		}
	},
	15
);

/**
 * Check if Stripe Payouts files should be loaded
 *
 * @since 2.29.0
 * @return bool
 */
function should_load_stripe_payouts_files() {
	// Check if Stripe is enabled
	$is_enabled      = false;
	$has_credentials = false;

	if ( function_exists( 'affiliate_wp' ) ) {
		$is_enabled      = affiliate_wp()->settings->get( 'stripe_payouts', false );
		$test_key        = affiliate_wp()->settings->get( 'stripe_test_secret_key', '' );
		$live_key        = affiliate_wp()->settings->get( 'stripe_live_secret_key', '' );
		$has_credentials = ! empty( $test_key ) || ! empty( $live_key );
	}

	// On settings page, only load if enabled or no credentials yet (for initial setup)
	if ( is_admin() && isset( $_GET['page'] ) ) {
		$page = is_string( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'affiliate-wp-settings' === $page ) {
			// If not enabled and has credentials, don't load heavy files (settings UI still works)
			if ( ! $is_enabled && $has_credentials ) {
				return false;
			}
			// Load if enabled or if no credentials (need to set up)
			return $is_enabled || ! $has_credentials;
		}

		// On payouts pages, always load so Stripe shows in the list (greyed out if disabled)
		if ( false !== strpos( $page, 'affiliate-wp-payouts' ) ) {
			return true;
		}

		// On referrals page, only load if enabled
		if ( 'affiliate-wp-referrals' === $page ) {
			return $is_enabled;
		}
	}

	// Load if Stripe is enabled AND has credentials configured
	if ( $is_enabled && $has_credentials ) {
		return true;
	}

	// Load for AJAX requests related to Stripe
	if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
		if ( false !== strpos( $action, 'stripe' ) ) {
			// For balance checking and test charge creation, always allow
			// These operations will validate credentials and show appropriate errors
			if ( in_array( $action, [ 'affwp_stripe_check_balance', 'affwp_stripe_create_test_charge' ], true ) ) {
				return true;
			}
			return $is_enabled;
		}
	}

	return false;
}

// Create the main Stripe Payouts function
if ( ! function_exists( 'affwp_stripe_payouts' ) ) {
	/**
	 * Returns the Stripe Payouts instance.
	 *
	 * @since 2.29.0
	 * @return object|null The active Stripe Payouts instance
	 */
	function affwp_stripe_payouts() {
		if ( class_exists( 'AffiliateWP_Stripe_Payouts_Processor' ) ) {
			return AffiliateWP_Stripe_Payouts_Processor::instance();
		}

		return null;
	}
}
