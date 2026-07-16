<?php
/**
 * PayPal Payouts Settings Panel Template
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/PayPalPayouts
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$payout_mode = affiliate_wp()->settings->get( 'paypal_payout_mode', 'api' );
$test_mode   = affiliate_wp()->settings->get( 'paypal_test_mode', false );

// API Application credentials
$live_client_id = affiliate_wp()->settings->get( 'paypal_live_client_id', '' );
$live_secret    = affiliate_wp()->settings->get( 'paypal_live_secret', '' );
$test_client_id = affiliate_wp()->settings->get( 'paypal_test_client_id', '' );
$test_secret    = affiliate_wp()->settings->get( 'paypal_test_secret', '' );

// MassPay credentials
$live_username  = affiliate_wp()->settings->get( 'paypal_live_username', '' );
$live_password  = affiliate_wp()->settings->get( 'paypal_live_password', '' );
$live_signature = affiliate_wp()->settings->get( 'paypal_live_signature', '' );
$test_username  = affiliate_wp()->settings->get( 'paypal_test_username', '' );
$test_password  = affiliate_wp()->settings->get( 'paypal_test_password', '' );
$test_signature = affiliate_wp()->settings->get( 'paypal_test_signature', '' );

// Check which credentials are configured
$has_api_live_creds     = ! empty( $live_client_id ) && ! empty( $live_secret );
$has_api_test_creds     = ! empty( $test_client_id ) && ! empty( $test_secret );
$has_masspay_live_creds = ! empty( $live_username ) && ! empty( $live_password ) && ! empty( $live_signature );
$has_masspay_test_creds = ! empty( $test_username ) && ! empty( $test_password ) && ! empty( $test_signature );

// Determine connection status
$is_connected = false;
if ( 'api' === $payout_mode ) {
	$is_connected = $test_mode ? $has_api_test_creds : $has_api_live_creds;
} else {
	$is_connected = $test_mode ? $has_masspay_test_creds : $has_masspay_live_creds;
}

// Determine whether to show MassPay option
$show_masspay = false;

// Show MassPay if user has existing credentials
if ( $has_masspay_live_creds || $has_masspay_test_creds ) {
	$show_masspay = true;
}

// Allow override via filter for specific use cases
$show_masspay = apply_filters( 'affwp_paypal_show_legacy_masspay_option', $show_masspay );
?>

<div class="max-w-4xl" x-data="{
	payoutMode: '<?php echo $show_masspay ? esc_js( $payout_mode ) : 'api'; ?>',
	testMode: <?php echo $test_mode ? 'true' : 'false'; ?>,

	init() {
		// Prevent Select2 from being applied to our dropdown
		const select = document.getElementById('paypal-payout-mode');
		if (select && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
			// Remove any existing Select2 instance
			if (jQuery(select).data('select2')) {
				jQuery(select).select2('destroy');
			}
			// Mark it to be skipped by Select2 initialization
			select.setAttribute('data-select2-skip', 'true');
		}
	}
}">

	<!-- Header Section -->
	<div class="mb-6">
		<h3 class="mb-2 text-xl font-semibold text-gray-900"><?php _e( 'PayPal Payouts Configuration', 'affiliate-wp' ); ?></h3>
		<p class="text-sm text-gray-600">
			<?php _e( 'Configure how to send instant payments to affiliates via PayPal.', 'affiliate-wp' ); ?>
		</p>
	</div>

	<!-- Main Settings Container -->
	<div class="">

		<?php if ( $show_masspay ) : ?>
		<!-- Payout Method Selection (only show dropdown when multiple options available) -->
		<div class="p-6 border-b border-gray-200">
			<label for="paypal-payout-mode" class="block mb-3 text-base font-medium text-gray-900"><?php _e( 'Payout Method', 'affiliate-wp' ); ?></label>
			<div class="relative max-w-md affwp-ignore-select2">
				<select
					id="paypal-payout-mode"
					name="affwp_settings[paypal_payout_mode]"
					x-model="payoutMode"
					class="block px-4 py-2.5 pr-10 w-full text-base text-gray-900 bg-white rounded-lg border-2 border-gray-300 shadow-sm transition-colors appearance-none cursor-pointer  focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400">
					<option value="api"><?php _e( 'API Application (Recommended)', 'affiliate-wp' ); ?></option>
					<option value="masspay"><?php _e( 'MassPay (Legacy - Deprecated)', 'affiliate-wp' ); ?></option>
				</select>
				<div class="flex absolute inset-y-0 right-0 items-center pr-3 pointer-events-none">
					<svg class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
						<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
					</svg>
				</div>
			</div>
			<p class="mt-3 text-sm text-gray-600">
				<span x-show="payoutMode === 'api'"><?php _e( 'Modern PayPal API using OAuth 2.0 authentication. Recommended for all new integrations.', 'affiliate-wp' ); ?></span>
				<span x-show="payoutMode === 'masspay'"><?php _e( 'Legacy MassPay API. Only use if you have an older PayPal account that doesn\'t support the modern API.', 'affiliate-wp' ); ?></span>
				<a href="https://affiliatewp.com/docs/paypal-payouts-installation-and-usage/" target="_blank" class="ml-1 text-blue-600 hover:text-blue-800"><?php _e( 'Learn more', 'affiliate-wp' ); ?></a>
			</p>
			<?php if ( 'masspay' === $payout_mode ) : ?>
			<div class="p-3 mt-3 bg-yellow-50 rounded-md border border-yellow-200">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
						</svg>
					</div>
					<div class="ml-3">
						<p class="text-sm text-yellow-800">
							<strong><?php _e( 'MassPay is deprecated:', 'affiliate-wp' ); ?></strong>
							<?php _e( 'PayPal has deprecated MassPay for new accounts. We recommend migrating to the API Application method for better reliability and continued support.', 'affiliate-wp' ); ?>
						</p>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php else : ?>
		<!-- When only API Application is available, no dropdown needed - set default to API -->
		<input type="hidden" name="affwp_settings[paypal_payout_mode]" value="api" />
		<?php endif; ?>

		<!-- API Application Credentials -->
		<div x-show="payoutMode === 'api'" class="space-y-6">

			<!-- Live Credentials Section -->
			<div class="overflow-hidden bg-white rounded-lg border border-gray-200">
				<div class="relative p-6 transition-colors" :class="!testMode && <?php echo $has_api_live_creds ? 'true' : 'false'; ?> ? 'bg-green-50/20 border-l-4 border-green-500' : 'border-l-4 border-transparent'">
					<div class="mb-4">
						<h4 class="flex items-center text-base font-medium text-gray-900">
							<?php _e( 'Live Credentials', 'affiliate-wp' ); ?>
							<span x-show="!testMode && <?php echo $has_api_live_creds ? 'true' : 'false'; ?>" class="px-2 py-1 ml-2 text-xs text-green-700 bg-green-50 rounded-md border border-green-200"><?php _e( 'Active', 'affiliate-wp' ); ?></span>
						</h4>
						<p class="mt-1 text-sm text-gray-600"><?php _e( 'Process payments using your live PayPal account', 'affiliate-wp' ); ?></p>
					</div>

					<div class="space-y-4">
						<div>
							<label for="paypal-live-client-id" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'Client ID', 'affiliate-wp' ); ?></label>
							<input
								type="text"
								id="paypal-live-client-id"
								name="affwp_settings[paypal_live_client_id]"
								value="<?php echo esc_attr( $live_client_id ); ?>"
								autocomplete="new-password"
								data-lpignore="true"
								data-1p-ignore
								class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
								placeholder="<?php esc_attr_e( 'Enter your PayPal Application Client ID', 'affiliate-wp' ); ?>" />
						</div>

						<div>
							<label for="paypal-live-secret" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'Secret', 'affiliate-wp' ); ?></label>
							<input
								type="password"
								id="paypal-live-secret"
								name="affwp_settings[paypal_live_secret]"
								value="<?php echo esc_attr( $live_secret ); ?>"
								autocomplete="new-password"
								data-lpignore="true"
								data-1p-ignore
								class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
								placeholder="<?php esc_attr_e( 'Enter your PayPal Application Secret', 'affiliate-wp' ); ?>" />
						</div>
					</div>
				</div>
			</div>

			<!-- Test Mode Section (Sandbox) -->
			<div class="overflow-hidden bg-white rounded-lg border border-gray-200 transition-all duration-300"
				:class="testMode ? 'border-l-4 border-l-orange-500 bg-orange-50/20' : 'bg-white'">
				<!-- Sandbox Mode Header - Always visible -->
				<div class="p-6 transition-colors cursor-pointer"
					@click="testMode = !testMode">
					<div class="flex justify-between items-center">
						<div class="flex-1">
							<h4 class="flex items-center text-base font-medium text-gray-900">
								<?php _e( 'Sandbox Mode', 'affiliate-wp' ); ?>
								<?php
								affwp_badge(
									[
										'text'    => __( 'Active', 'affiliate-wp' ),
										'variant' => 'warning',
										'size'    => 'sm',
										'class'   => 'ml-2',
										'alpine'  => [
											'show'       => 'testMode && ' . ( $has_api_test_creds ? 'true' : 'false' ),
											'cloak'      => true,
											'transition' => [
												'enter' => 'transition ease-out duration-200',
												'enter-start' => 'opacity-0 scale-95 translate-x-2',
												'enter-end' => 'opacity-100 scale-100 translate-x-0',
												'leave' => 'transition ease-in duration-150',
												'leave-start' => 'opacity-100 scale-100 translate-x-0',
												'leave-end' => 'opacity-0 scale-95 translate-x-2',
											],
										],
									]
								);
								?>
							</h4>
							<p class="mt-1 text-sm text-gray-600"
								x-show="!testMode">
								<?php _e( 'Enable to test payments without real transactions', 'affiliate-wp' ); ?>
							</p>
							<p class="mt-1 text-sm text-gray-600"
								x-show="testMode">
								<?php _e( 'Process payments using PayPal Sandbox for testing', 'affiliate-wp' ); ?>
							</p>
						</div>
						<div class="flex items-center ml-4" @click.stop>
							<input type="hidden" name="affwp_settings[paypal_test_mode]" value="0" />
							<?php
							// Use the new toggle component
							affwp_toggle(
								[
									'name'         => 'affwp_settings[paypal_test_mode]',
									'label'        => __( 'Enable test mode', 'affiliate-wp' ),
									'checked'      => $test_mode,
									'size'         => 'md',
									'color'        => 'blue',
									'alpine_model' => 'testMode',
								]
							);
							?>
						</div>
					</div>
				</div>

				<!-- Collapsible Content - Only visible when testMode is true -->
				<div x-show="testMode"
					x-collapse>
					<div class="px-6 pb-6 space-y-4">
						<div>
							<label for="paypal-test-client-id" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'Sandbox Client ID', 'affiliate-wp' ); ?></label>
							<input
								type="text"
								id="paypal-test-client-id"
								name="affwp_settings[paypal_test_client_id]"
								value="<?php echo esc_attr( $test_client_id ); ?>"
								autocomplete="new-password"
								data-lpignore="true"
								data-1p-ignore
								class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
								placeholder="<?php esc_attr_e( 'Enter your Sandbox Application Client ID', 'affiliate-wp' ); ?>" />
						</div>

						<div>
							<label for="paypal-test-secret" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'Sandbox Secret', 'affiliate-wp' ); ?></label>
							<input
								type="password"
								id="paypal-test-secret"
								name="affwp_settings[paypal_test_secret]"
								value="<?php echo esc_attr( $test_secret ); ?>"
								autocomplete="new-password"
								data-lpignore="true"
								data-1p-ignore
								class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
								placeholder="<?php esc_attr_e( 'Enter your Sandbox Application Secret', 'affiliate-wp' ); ?>" />
						</div>

					</div>


				</div>
			</div>
		</div>

		<!-- MassPay Credentials -->
		<?php if ( $show_masspay ) : ?>
		<div x-show="payoutMode === 'masspay'" class="divide-y divide-gray-200">

			<!-- Live Credentials -->
			<div class="relative p-6 transition-colors" :class="!testMode && <?php echo $has_masspay_live_creds ? 'true' : 'false'; ?> ? 'bg-green-50/20 border-l-4 border-green-500' : 'border-l-4 border-transparent'">
				<h4 class="flex items-center mb-4 text-base font-medium text-gray-900">
					<?php _e( 'Live Credentials', 'affiliate-wp' ); ?>
					<span x-show="!testMode && <?php echo $has_masspay_live_creds ? 'true' : 'false'; ?>" class="px-2 py-1 ml-2 text-xs text-green-700 bg-green-50 rounded-md border border-green-200"><?php _e( 'Active', 'affiliate-wp' ); ?></span>
				</h4>

				<div class="space-y-4">
					<div>
						<label for="paypal-live-username" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'API Username', 'affiliate-wp' ); ?></label>
						<input
							type="text"
							id="paypal-live-username"
							name="affwp_settings[paypal_live_username]"
							value="<?php echo esc_attr( $live_username ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
							placeholder="<?php esc_attr_e( 'Enter your PayPal API Username', 'affiliate-wp' ); ?>" />
					</div>

					<div>
						<label for="paypal-live-password" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'API Password', 'affiliate-wp' ); ?></label>
						<input
							type="password"
							id="paypal-live-password"
							name="affwp_settings[paypal_live_password]"
							value="<?php echo esc_attr( $live_password ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
							placeholder="<?php esc_attr_e( 'Enter your PayPal API Password', 'affiliate-wp' ); ?>" />
					</div>

					<div>
						<label for="paypal-live-signature" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'API Signature', 'affiliate-wp' ); ?></label>
						<input
							type="text"
							id="paypal-live-signature"
							name="affwp_settings[paypal_live_signature]"
							value="<?php echo esc_attr( $live_signature ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
							placeholder="<?php esc_attr_e( 'Enter your PayPal API Signature', 'affiliate-wp' ); ?>" />
					</div>
				</div>
			</div>

			<!-- Sandbox Credentials -->
			<div class="relative p-6 transition-colors" :class="testMode && <?php echo $has_masspay_test_creds ? 'true' : 'false'; ?> ? 'bg-orange-50/20 border-l-4 border-orange-500' : 'border-l-4 border-transparent'">
				<h4 class="flex items-center mb-4 text-base font-medium text-gray-900">
					<?php _e( 'Sandbox Credentials', 'affiliate-wp' ); ?>
					<span x-show="testMode && <?php echo $has_masspay_test_creds ? 'true' : 'false'; ?>" class="px-2 py-1 ml-2 text-xs text-orange-700 bg-orange-50 rounded-md border border-orange-200"><?php _e( 'Active', 'affiliate-wp' ); ?></span>
				</h4>

				<div class="space-y-4">
					<div>
						<label for="paypal-test-username" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'Sandbox API Username', 'affiliate-wp' ); ?></label>
						<input
							type="text"
							id="paypal-test-username"
							name="affwp_settings[paypal_test_username]"
							value="<?php echo esc_attr( $test_username ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
							placeholder="<?php esc_attr_e( 'Enter your Sandbox API Username', 'affiliate-wp' ); ?>" />
					</div>

					<div>
						<label for="paypal-test-password" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'Sandbox API Password', 'affiliate-wp' ); ?></label>
						<input
							type="password"
							id="paypal-test-password"
							name="affwp_settings[paypal_test_password]"
							value="<?php echo esc_attr( $test_password ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
							placeholder="<?php esc_attr_e( 'Enter your Sandbox API Password', 'affiliate-wp' ); ?>" />
					</div>

					<div>
						<label for="paypal-test-signature" class="block mb-1 text-sm font-medium text-gray-700"><?php _e( 'Sandbox API Signature', 'affiliate-wp' ); ?></label>
						<input
							type="text"
							id="paypal-test-signature"
							name="affwp_settings[paypal_test_signature]"
							value="<?php echo esc_attr( $test_signature ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
							placeholder="<?php esc_attr_e( 'Enter your Sandbox API Signature', 'affiliate-wp' ); ?>" />
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>

	</div> <!-- End of Main Settings Container -->

	<!-- Documentation Link -->
	<div class="mt-6 text-center">
		<?php
		affwp_link(
			[
				'text'     => __( 'View PayPal Payouts Documentation', 'affiliate-wp' ),
				'href'     => 'https://affiliatewp.com/docs/paypal-payouts-installation-and-usage/',
				'external' => true,
				'variant'  => 'primary',
				'icon'     => [
					'name'     => 'book',
					'position' => 'left',
				],
			]
		);
		?>
	</div>

</div> <!-- End of max-w-4xl container -->
