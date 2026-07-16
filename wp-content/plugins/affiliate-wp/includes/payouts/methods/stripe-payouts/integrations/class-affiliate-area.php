<?php
/**
 * Affiliate Area Integration
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Integrations/Affiliate Area
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Payouts_Affiliate_Area Class
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Payouts_Affiliate_Area {

	/**
	 * Setup the affiliate area integration
	 *
	 * @since 2.29.0
	 */
	public function __construct() {

		// Add to settings tab.
		add_action( 'affwp_affiliate_dashboard_before_submit', [ $this, 'add_settings_content' ] );

		// Add admin notices.
		add_action( 'affwp_affiliate_dashboard_notices', [ $this, 'dashboard_notices' ] );

		// Handle Stripe connect action.
		add_action( 'init', [ $this, 'process_stripe_connect_request' ] );

		// Handle Stripe Express Dashboard access.
		add_action( 'init', [ $this, 'process_stripe_dashboard_request' ] );

		// Handle Stripe account management link generation (for onboarding updates).
		add_action( 'init', [ $this, 'process_stripe_manage_request' ] );

		// Handle OAuth return from Stripe.
		add_action( 'init', [ $this, 'process_stripe_oauth_return' ] );
	}

	/**
	 * Add Stripe connect section to the settings tab
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function add_settings_content() {
		// Get the current affiliate.
		$affiliate_id = affwp_get_affiliate_id();

		// Check if Stripe is configured.
		if ( ! affwp_stripe_payouts_is_configured() ) {
			// Don't show anything if Stripe is not configured.
			return;
		}

		// Check platform status first.
		$platform_status = affwp_stripe_payouts_check_affiliate_platform_status( $affiliate_id );

		// Check if affiliate is connected.
		$is_connected = affwp_stripe_payouts_is_affiliate_connected( $affiliate_id );
		$account_id   = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );

		// If connected but platform changed, treat as disconnected.
		if ( $is_connected && 'invalid' === $platform_status ) {
			$is_connected     = false;
			$platform_changed = true;
		} else {
			$platform_changed = false;
		}

		?>
		<div class="affwp-stripe-payouts" id="stripe-payouts">
			<h4 class="affwp-stripe-payouts__title"><?php esc_html_e( 'Stripe Payout Settings', 'affiliate-wp' ); ?></h4>

			<?php if ( $is_connected ) : ?>
				<?php
				// Check if account has completed onboarding.
				$account_status         = affwp_stripe_payouts_get_account_status( $account_id );
				$is_onboarding_complete = $account_status['details_submitted'] ?? false;
				?>
				<div class="affwp-stripe-payouts__status affwp-stripe-payouts__status--connected">
					<span class="affwp-stripe-payouts__account-info">
						<span class="affwp-stripe-payouts__account-info-label">
							<?php esc_html_e( 'Stripe payouts are enabled. Connected Account ID:', 'affiliate-wp' ); ?>
						</span>
						<code class="affwp-stripe-payouts__account-info-value"><?php echo esc_html( $account_id ); ?></code>
					</span>
				</div>
				<p class="affwp-stripe-payouts__description <?php echo $is_onboarding_complete ? 'affwp-stripe-payouts__description--onboarded' : 'affwp-stripe-payouts__description--pending'; ?>">
					<?php if ( $is_onboarding_complete ) : ?>
						<?php esc_html_e( 'Your Stripe account is connected and ready to receive payouts. You can manage your bank account details, tax information, and other settings through your Stripe Dashboard.', 'affiliate-wp' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Your Stripe account is connected but needs to complete the onboarding process. Click "Complete Setup" to finish providing the required information for receiving payouts.', 'affiliate-wp' ); ?>
					<?php endif; ?>
				</p>
				<div class="affwp-stripe-payouts__actions">
					<?php if ( $is_onboarding_complete ) : ?>
						<a href="<?php echo esc_url( add_query_arg( [ 'affwp_action' => 'stripe_payouts_dashboard', 'affiliate_id' => $affiliate_id, '_wpnonce' => wp_create_nonce( 'affwp_stripe_payouts_dashboard_' . $affiliate_id ) ], affwp_get_affiliate_area_page_url( 'settings' ) ) ); ?>" target="_blank" class="affwp-stripe-payouts__link affwp-stripe-payouts__link--dashboard">
							<?php esc_html_e( 'Access Stripe Dashboard', 'affiliate-wp' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( [ 'affwp_action' => 'stripe_payouts_manage', 'affiliate_id' => $affiliate_id, '_wpnonce' => wp_create_nonce( 'affwp_stripe_payouts_manage_' . $affiliate_id ) ], affwp_get_affiliate_area_page_url( 'settings' ) ) ); ?>" class="affwp-stripe-payouts__link affwp-stripe-payouts__link--setup">
							<?php esc_html_e( 'Complete Setup', 'affiliate-wp' ); ?>
						</a>
					<?php endif; ?>
				</div>

			<?php else : ?>

				<?php if ( $platform_changed ) : ?>
					<div class="affwp-stripe-payouts__status affwp-stripe-payouts__status--warning">
						<?php esc_html_e( '⚠️ Your Stripe connection has been reset due to a platform change.', 'affiliate-wp' ); ?>
					</div>
					<p class="affwp-stripe-payouts__description affwp-stripe-payouts__description--platform-changed">
						<?php esc_html_e( 'The site administrator has changed their Stripe account configuration. For security reasons, you\'ll need to reconnect with Stripe to continue receiving payouts. This is a one-time process to ensure your payment information remains secure.', 'affiliate-wp' ); ?>
					</p>
				<?php else : ?>
					<p class="affwp-stripe-payouts__description affwp-stripe-payouts__description--disconnected">
						<?php esc_html_e( 'To receive your referral payouts, we\'ll set up a secure Stripe account for you. This allows us to send payments directly to your bank account. Click below to provide your basic information - Stripe will guide you through a quick verification process to ensure secure payments.', 'affiliate-wp' ); ?>
					</p>
				<?php endif; ?>
				<div class="affwp-stripe-payouts__actions">
					<a href="<?php echo esc_url( add_query_arg( [ 'affwp_action' => 'stripe_payouts_connect', 'affiliate_id' => $affiliate_id ], affwp_get_affiliate_area_page_url( 'settings' ) ) ); ?>" class="affwp-stripe-payouts__link affwp-stripe-payouts__link--connect">
						<?php esc_html_e( 'Set Up Stripe Payouts', 'affiliate-wp' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Show notices in the affiliate dashboard
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function dashboard_notices() {
		// Check for Stripe connection success/error.
		if ( isset( $_GET['stripe_connected'] ) ) {
			?>
			<div class="affwp-stripe-notice affwp-stripe-notice--success">
				<p class="affwp-stripe-notice__message"><?php esc_html_e( 'Your Stripe payout account has been set up successfully!', 'affiliate-wp' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['stripe_manage_complete'] ) ) {
			?>
			<div class="affwp-stripe-notice affwp-stripe-notice--success">
				<p class="affwp-stripe-notice__message"><?php esc_html_e( 'Your Stripe payout settings have been updated successfully!', 'affiliate-wp' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['stripe_error'] ) ) {
			$error = affwp_stripe_payouts_sanitize_error_message( urldecode( $_GET['stripe_error'] ) );
			?>
			<div class="affwp-stripe-notice affwp-stripe-notice--error">
				<p class="affwp-stripe-notice__message"><?php printf( esc_html__( 'Error setting up Stripe payouts: %s', 'affiliate-wp' ), esc_html( $error ) ); ?></p>
			</div>
			<?php
		}

		// Check if Stripe is not connected and add a notice.
		if ( isset( $_GET['tab'] ) && 'payouts' === $_GET['tab'] ) {
			$affiliate_id = affwp_get_affiliate_id();

			// Only show if Stripe is configured.
			if ( affwp_stripe_payouts_is_configured() && ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
				?>
				<div class="affwp-stripe-notice affwp-stripe-notice--info">
					<p class="affwp-stripe-notice__message">
						<?php
						printf(
							__( 'To receive payouts via Stripe, please <a href="%s" class="affwp-stripe-notice__link">set up Stripe payouts</a>.', 'affiliate-wp' ),
							esc_url( affwp_get_affiliate_area_page_url( 'settings' ) . '#stripe-payouts' )
						);
						?>
					</p>
				</div>
				<?php
			}
		}
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

		if ( is_wp_error( $dashboard_link ) ) {
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $dashboard_link->get_error_message() ), affwp_get_affiliate_area_page_url( 'settings' ) ) );
			exit;
		}

		// Redirect to Stripe Express Dashboard.
		wp_redirect( $dashboard_link );
		exit;
	}

	/**
	 * Handle Stripe account management link generation.
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

		if ( is_wp_error( $account_link ) ) {
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $account_link->get_error_message() ), affwp_get_affiliate_area_page_url( 'settings' ) ) );
			exit;
		}

		wp_redirect( $account_link );
		exit;
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
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $oauth_link->get_error_message() ), affwp_get_affiliate_area_page_url( 'settings' ) ) );
			exit;
		}

		// Redirect to Stripe.
		wp_redirect( $oauth_link );
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

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'stripe_error', urlencode( $result->get_error_message() ), affwp_get_affiliate_area_page_url( 'settings' ) ) );
		} else {
			wp_safe_redirect( add_query_arg( 'stripe_connected', 'true', affwp_get_affiliate_area_page_url( 'settings' ) ) );
		}
		exit;
	}
}
