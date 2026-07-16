<?php
/**
 * Admin Views: Payouts Tab
 *
 * Alternative design using the new card layout
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Payouts
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payment_methods = AffiliateWP_Payment_Methods::get_all();
?>


<div class="relative z-10 py-8 max-w-4xl affwp-ui" x-data="{
	activeMethod: null,
	message: '',
	messageType: 'success',
	getActiveMethodTitle() {
		if (!this.activeMethod) return '';
		const methods = window.affwp_payouts?.methods || {};
		return methods[this.activeMethod]?.name || '';
	}
}">

	<!-- Skip Links -->
	<a href="#payment-methods" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-white focus:border focus:border-gray-300 focus:rounded-md focus:shadow-sm">
		<?php esc_html_e( 'Skip to payment methods', 'affiliate-wp' ); ?>
	</a>

	<!-- Header -->
	<div class="mb-4 sm:mb-6">
		<h2 class="mb-1 text-lg font-semibold text-gray-900 sm:text-xl">
			<?php esc_html_e( 'Payout Methods', 'affiliate-wp' ); ?>
		</h2>
		<p class="text-sm text-gray-600 sm:text-base">
			<?php esc_html_e( 'Configure how you pay your affiliates.', 'affiliate-wp' ); ?>
		</p>
	</div>


	<!-- Payment Methods List -->
	<div id="payment-methods" class="space-y-4" role="region" aria-label="<?php esc_attr_e( 'Payment Methods', 'affiliate-wp' ); ?>">
		<?php foreach ( $payment_methods as $method_id => $method ) : ?>
			<?php include 'payment-method-card.php'; ?>
		<?php endforeach; ?>
	</div>


	<!-- Success/Error Messages -->
	<div x-show="message"
		x-cloak
		x-transition:enter="transition ease-out duration-300"
		x-transition:enter-start="opacity-0 transform translate-y-2"
		x-transition:enter-end="opacity-100 transform translate-y-0"
		x-transition:leave="transition ease-in duration-200"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		class="fixed right-4 bottom-4 left-4 z-50 mx-auto max-w-sm sm:left-auto sm:mx-0"
		role="status"
		aria-live="polite"
		aria-atomic="true">
		<div class="p-4 rounded-md shadow-lg"
			:class="messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
			<div class="flex">
				<div class="flex-shrink-0">
					<svg x-show="messageType === 'success'" class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
					</svg>
					<svg x-show="messageType === 'error'" class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
					</svg>
				</div>
				<div class="ml-3">
					<p class="text-sm font-medium"
						:class="messageType === 'success' ? 'text-green-800' : 'text-red-800'"
						x-text="message"></p>
				</div>
			</div>
		</div>
	</div>

</div>
