<?php
/**
 * Template: Stripe Payouts Section for Affiliate Portal
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Templates
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the current affiliate.
$affiliate_id = affwp_get_affiliate_id();

// Check if Stripe is configured.
if ( ! affwp_stripe_payouts_is_configured() ) {
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

// Get portal page URL for redirect.
$portal_url = class_exists( 'AffiliateWP_Affiliate_Portal\Core\Components\Portal' )
	? AffiliateWP_Affiliate_Portal\Core\Components\Portal::get_page_url( 'settings' )
	: home_url();

?>

<div class="my-8">
	<div class="md:grid md:grid-cols-3 md:gap-6">
		<div class="md:col-span-1">
			<div class="px-4 sm:px-0">
				<h3 class="text-lg font-medium leading-6 text-gray-900">
					<?php esc_html_e( 'Receive Payouts via Stripe', 'affiliate-wp' ); ?>
				</h3>
				<p class="mt-1 text-sm text-gray-600">
					<?php esc_html_e( 'Connect with Stripe to receive secure, automated payouts directly to your bank account.', 'affiliate-wp' ); ?>
				</p>
			</div>
		</div>

		<div class="mt-5 md:mt-0 md:col-span-2">
			<div class="shadow overflow-hidden sm:rounded-md">
				<div class="px-4 py-5 bg-white sm:p-6">
					<?php if ( $is_connected ) : ?>
						<?php
						// Check if account has completed onboarding.
						$account_status         = affwp_stripe_payouts_get_account_status( $account_id );
						$is_onboarding_complete = isset( $account_status['details_submitted'] ) && $account_status['details_submitted'];
						?>

						<div class="mb-4">
							<div class="flex items-center">
								<svg class="h-5 w-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
								</svg>
								<span class="text-sm font-medium text-gray-900">
									<?php esc_html_e( 'Stripe account connected', 'affiliate-wp' ); ?>
								</span>
							</div>
							<p class="mt-2 text-sm text-gray-500">
								<?php
								/* translators: %s: Account ID */
								printf( esc_html__( 'Account ID: %s', 'affiliate-wp' ), '<code class="px-2 py-1 bg-gray-100 rounded text-xs">' . esc_html( $account_id ) . '</code>' );
								?>
							</p>
						</div>

						<div class="bg-gray-50 rounded-lg p-4 mb-4">
							<p class="text-sm text-gray-600">
								<?php if ( $is_onboarding_complete ) : ?>
									<?php esc_html_e( 'Your Stripe account is connected and ready to receive payouts. You can manage your bank account details, tax information, and other settings through your Stripe Dashboard.', 'affiliate-wp' ); ?>
								<?php else : ?>
									<?php esc_html_e( 'Your Stripe account is connected but needs to complete the onboarding process. Click "Complete Setup" to finish providing the required information for receiving payouts.', 'affiliate-wp' ); ?>
								<?php endif; ?>
							</p>
						</div>

						<div class="flex gap-3">
							<?php if ( $is_onboarding_complete ) : ?>
								<a href="<?php echo esc_url( add_query_arg( [ 'affwp_action' => 'stripe_payouts_dashboard', 'affiliate_id' => $affiliate_id, '_wpnonce' => wp_create_nonce( 'affwp_stripe_payouts_dashboard_' . $affiliate_id ) ], $portal_url ) ); ?>"
									target="_blank"
									class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">

									<?php esc_html_e( 'Access Stripe Dashboard', 'affiliate-wp' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( add_query_arg( [ 'affwp_action' => 'stripe_payouts_manage', 'affiliate_id' => $affiliate_id, '_wpnonce' => wp_create_nonce( 'affwp_stripe_payouts_manage_' . $affiliate_id ) ], $portal_url ) ); ?>"
									class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
									<?php esc_html_e( 'Complete Setup', 'affiliate-wp' ); ?>
								</a>
							<?php endif; ?>

						</div>

					<?php else : ?>

						<div class="mb-4">
							<div class="flex items-center">
								<?php if ( $platform_changed ) : ?>
									<svg class="h-5 w-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
										<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
									</svg>
									<span class="text-sm font-medium text-gray-900">
										<?php esc_html_e( 'Reconnection required due to platform change', 'affiliate-wp' ); ?>
									</span>

								<?php endif; ?>
							</div>
						</div>

						<?php if ( $platform_changed ) : ?>
							<div class="bg-yellow-50 rounded-lg p-4 mb-6">
								<div class="flex">
									<div class="flex-shrink-0">
										<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
										</svg>
									</div>
									<div class="ml-3">
										<p class="text-sm text-yellow-700">
											<?php esc_html_e( 'The site administrator has changed their Stripe account configuration. For security reasons, you need to reconnect with Stripe to continue receiving payouts. This is a one-time process to ensure your payment information remains secure.', 'affiliate-wp' ); ?>
										</p>
									</div>
								</div>
							</div>
						<?php else : ?>
							<div class="bg-blue-50 rounded-lg p-4 mb-6">
								<div class="flex">
									<div class="flex-shrink-0">
										<svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
										</svg>
									</div>
									<div class="ml-3">
										<p class="text-sm text-blue-700">
											<?php esc_html_e( 'To receive your referral payouts, we\'ll set up a secure Stripe account for you. This allows us to send payments directly to your bank account. Stripe will guide you through a quick verification process to ensure secure payments.', 'affiliate-wp' ); ?>
										</p>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<div>
							<a href="<?php echo esc_url( add_query_arg( [ 'affwp_action' => 'stripe_payouts_connect', 'affiliate_id' => $affiliate_id ], $portal_url ) ); ?>"
								class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
								<svg class="mr-3 h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
									<path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.274 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
								</svg>
								<?php esc_html_e( 'Set Up Stripe Payouts', 'affiliate-wp' ); ?>
							</a>
						</div>

					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="border-t border-gray-200 my-8"></div>
