<?php
/**
 * Affiliate Portal Integration
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Integrations/Affiliate Portal
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Payouts_Affiliate_Portal Class
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Payouts_Affiliate_Portal {

	/**
	 * Setup the affiliate portal integration
	 *
	 * @since 2.29.0
	 */
	public function __construct() {

		// Add Stripe section to Portal settings page.
		add_action( 'affwp_portal_settings_payment_methods', [ $this, 'render_stripe_section' ] );

		// Handle Stripe connect action.
		add_action( 'init', [ $this, 'process_stripe_connect_request' ] );

		// Handle Stripe Express Dashboard access.
		add_action( 'init', [ $this, 'process_stripe_dashboard_request' ] );

		// Handle Stripe account management link generation (for onboarding updates).
		add_action( 'init', [ $this, 'process_stripe_manage_request' ] );

		// Handle OAuth return from Stripe.
		add_action( 'init', [ $this, 'process_stripe_oauth_return' ] );

		// Add notices to portal.
		add_action( 'affwp_portal_notices', [ $this, 'display_notices' ] );
	}

	/**
	 * Render the Stripe section in the Portal.
	 *
	 * @since 2.29.0
	 * @param int $affiliate_id The affiliate ID
	 * @return void
	 */
	public function render_stripe_section( $affiliate_id ) {
		// Check if Stripe is configured.
		if ( ! affwp_stripe_payouts_is_configured() ) {
			return;
		}

		// Include the template.
		$template_path = dirname( __DIR__ ) . '/templates/portal-stripe-section.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Display notices in the affiliate portal
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function display_notices() {
		// Check for Stripe connection success/error.
		if ( isset( $_GET['stripe_connected'] ) && 'true' === $_GET['stripe_connected'] ) {
			?>
			<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
						</svg>
					</div>
					<div class="ml-3">
						<p class="text-sm text-green-700">
							<?php esc_html_e( 'Your Stripe account has been connected successfully!', 'affiliate-wp' ); ?>
						</p>
					</div>
				</div>
			</div>
			<?php
		}

		if ( isset( $_GET['stripe_manage_complete'] ) && 'true' === $_GET['stripe_manage_complete'] ) {
			?>
			<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
						</svg>
					</div>
					<div class="ml-3">
						<p class="text-sm text-green-700">
							<?php esc_html_e( 'Your Stripe account settings have been updated successfully!', 'affiliate-wp' ); ?>
						</p>
					</div>
				</div>
			</div>
			<?php
		}

		if ( isset( $_GET['stripe_error'] ) ) {
			$error = affwp_stripe_payouts_sanitize_error_message( urldecode( $_GET['stripe_error'] ) );
			?>
			<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
						</svg>
					</div>
					<div class="ml-3">
						<p class="text-sm text-red-700">
							<?php
							/* translators: %s: Error message */
							printf( esc_html__( 'Error connecting to Stripe: %s', 'affiliate-wp' ), esc_html( $error ) );
							?>
						</p>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Handle Stripe connect request
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_stripe_connect_request() {
		if ( ! isset( $_GET['affwp_action'] ) || 'stripe_payouts_connect' !== $_GET['affwp_action'] ) {
			return;
		}

		if ( ! isset( $_GET['affiliate_id'] ) ) {
			return;
		}

		$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_GET['affiliate_id'] );
		if ( is_wp_error( $affiliate_id ) ) {
			return;
		}

		// Generate OAuth link for Stripe Connect.
		$oauth_link = affwp_stripe_payouts_generate_oauth_link( $affiliate_id );

		if ( is_wp_error( $oauth_link ) ) {
			$portal_url = function_exists( 'affwp_portal_get_page_url' )
				? \AffiliateWP_Affiliate_Portal\Core\Components\Portal::get_page_url( 'settings' )
				: home_url();
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $oauth_link->get_error_message() ), $portal_url ) );
			exit;
		}

		// Redirect to Stripe.
		wp_redirect( $oauth_link );
		exit;
	}

	/**
	 * Handle Stripe Express Dashboard access request
	 *
	 * This method generates a direct login link to the Stripe Express Dashboard
	 * for affiliates to manage their daily operations like viewing balances and payouts.
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_stripe_dashboard_request() {
		if ( ! isset( $_GET['affwp_action'] ) || 'stripe_payouts_dashboard' !== $_GET['affwp_action'] ) {
			return;
		}

		if ( ! isset( $_GET['affiliate_id'] ) ) {
			return;
		}

		$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_GET['affiliate_id'] );
		if ( is_wp_error( $affiliate_id ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'affwp_stripe_payouts_dashboard_' . $affiliate_id ) ) {
			wp_die( esc_html__( 'Security check failed', 'affiliate-wp' ) );
		}

		// Generate and redirect to the Stripe Express Dashboard.
		$dashboard_link = affwp_stripe_payouts_generate_express_dashboard_link( $affiliate_id );

		$portal_url = function_exists( 'affwp_portal_get_page_url' )
			? \AffiliateWP_Affiliate_Portal\Core\Components\Portal::get_page_url( 'settings' )
			: home_url();

		if ( is_wp_error( $dashboard_link ) ) {
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $dashboard_link->get_error_message() ), $portal_url ) );
			exit;
		}

		// Redirect to Stripe Express Dashboard.
		wp_redirect( $dashboard_link );
		exit;
	}

	/**
	 * Handle Stripe account management link generation
	 *
	 * This method generates a Stripe Account Link for onboarding updates and compliance.
	 * Used when affiliates need to update verification info or fix account issues.
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_stripe_manage_request() {
		if ( ! isset( $_GET['affwp_action'] ) || 'stripe_payouts_manage' !== $_GET['affwp_action'] ) {
			return;
		}

		if ( ! isset( $_GET['affiliate_id'] ) ) {
			return;
		}

		$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_GET['affiliate_id'] );
		if ( is_wp_error( $affiliate_id ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'affwp_stripe_payouts_manage_' . $affiliate_id ) ) {
			wp_die( esc_html__( 'Security check failed', 'affiliate-wp' ) );
		}

		// Generate and redirect to the Stripe Account Link.
		$account_link = affwp_stripe_payouts_generate_account_link( $affiliate_id );

		$portal_url = function_exists( 'affwp_portal_get_page_url' )
			? \AffiliateWP_Affiliate_Portal\Core\Components\Portal::get_page_url( 'settings' )
			: home_url();

		if ( is_wp_error( $account_link ) ) {
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $account_link->get_error_message() ), $portal_url ) );
			exit;
		}

		wp_redirect( $account_link );
		exit;
	}


	/**
	 * Handle OAuth return from Stripe
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_stripe_oauth_return() {
		if ( ! isset( $_GET['affwp_action'] ) || 'stripe_payouts_oauth_return' !== $_GET['affwp_action'] ) {
			return;
		}

		// Check for required parameters.
		if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) || ! isset( $_GET['affiliate_id'] ) ) {
			return;
		}

		$code  = sanitize_text_field( $_GET['code'] );
		$state = sanitize_text_field( $_GET['state'] );

		// Process the OAuth callback.
		$result = affwp_stripe_payouts_process_oauth_callback( $code, $state );

		$portal_url = function_exists( 'affwp_portal_get_page_url' )
			? \AffiliateWP_Affiliate_Portal\Core\Components\Portal::get_page_url( 'settings' )
			: home_url();

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $result->get_error_message() ), $portal_url ) );
		} else {
			wp_safe_redirect( add_query_arg( 'stripe_connected', 'true', $portal_url ) );
		}
		exit;
	}
}
