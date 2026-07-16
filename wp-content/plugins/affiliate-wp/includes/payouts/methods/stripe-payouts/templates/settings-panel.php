<?php
/**
 * Stripe Payouts Settings Panel Template
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/StripePayouts
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings.
$test_mode = affiliate_wp()->settings->get( 'stripe_test_mode', false );

// API Secret keys.
$live_secret_key = affiliate_wp()->settings->get( 'stripe_live_secret_key', '' );
$test_secret_key = affiliate_wp()->settings->get( 'stripe_test_secret_key', '' );

// Webhook secret.
$webhook_secret = affiliate_wp()->settings->get( 'stripe_webhook_secret', '' );

// Check which credentials are configured.
$has_live_creds = ! empty( $live_secret_key );
$has_test_creds = ! empty( $test_secret_key );

// Determine connection status.
$is_connected = $test_mode ? $has_test_creds : $has_live_creds;

// Get Stripe Connect status.
$admin_connected = false;
if ( function_exists( 'affwp_stripe_payouts_is_admin_connected' ) ) {
	$admin_connected = affwp_stripe_payouts_is_admin_connected();
}
?>

<div class="max-w-3xl" x-data="{
	testMode: <?php echo $test_mode ? 'true' : 'false'; ?>,

	init() {
		// Set global state for badge reactivity
		window.affwpStripeTestMode = this.testMode;

		// Watch for testMode changes
		this.$watch('testMode', (value) => {
			window.affwpStripeTestMode = value;
			// Dispatch custom event for other components
			window.dispatchEvent(new CustomEvent('stripe-mode-changed', {
				detail: { testMode: value }
			}));
			// Load balance when switching to test mode
			if (value) {
				this.loadCachedBalance();
			}
		});

		// Load balance on init if in test mode
		if (this.testMode) {
			this.loadCachedBalance();
		}

	},

	// Balance management
	currentBalance: '<?php echo esc_js( affwp_stripe_payouts_format_balance_amount( affwp_stripe_payouts_get_cached_balance() ? affwp_stripe_payouts_get_cached_balance() : [] ) ); ?>',
	balanceData: null,
	lastBalanceCheck: null,
	checkingBalance: false,
	balanceResult: null,
	balanceError: null,

	// Test charge state
	creatingCharge: false,
	chargeResult: null,
	chargeError: null,
	modalAmount: 0,
	modalInput: null,


	loadCachedBalance() {
		// Attempt to load balance from cache first
		const cachedBalance = '<?php echo esc_js( affwp_stripe_payouts_format_balance_amount( affwp_stripe_payouts_get_cached_balance() ? affwp_stripe_payouts_get_cached_balance() : [] ) ); ?>';
		// Always update the balance, even if it's $0.00 (which could be a valid balance).
		this.currentBalance = cachedBalance;
	},

	checkBalance() {
		this.checkingBalance = true;
		this.balanceResult = null;
		this.balanceError = null;

		const formData = new FormData();
		formData.append('action', 'affwp_stripe_check_balance');
		formData.append('nonce', '<?php echo esc_attr( wp_create_nonce( 'affwp_stripe_check_balance' ) ); ?>');

		fetch(ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(response => response.json())
		.then(data => {
			this.checkingBalance = false;
			if (data.success) {
				// Update current balance
				if (data.data.formatted) {
					this.currentBalance = data.data.formatted;
				}
				if (data.data.balance) {
					this.balanceData = data.data.balance;
				}
				this.lastBalanceCheck = new Date();
				// Show success briefly
				this.balanceResult = '<?php esc_html_e( 'Balance updated', 'affiliate-wp' ); ?>';
				setTimeout(() => {
					this.balanceResult = null;
				}, 3000);
			} else {
				this.balanceError = data.data.message || 'Failed to retrieve balance';
				setTimeout(() => {
					this.balanceError = null;
				}, 5000);
			}
		})
		.catch(error => {
			this.checkingBalance = false;
			this.balanceError = 'Network error occurred';
			console.error('Balance check error:', error);
			setTimeout(() => {
				this.balanceError = null;
			}, 5000);
		});
	},

	createTestCharge() {
		const amountInput = document.querySelector('#stripe-test-charge-amount');
		const amount = amountInput ? parseFloat(amountInput.value) : 10;

		if (!amount || isNaN(amount) || amount <= 0) {
			this.chargeError = 'Please enter a valid amount';
			setTimeout(() => {
				this.chargeError = null;
			}, 5000);
			return;
		}

		// Store amount for modal display
		this.modalAmount = amount;
		this.modalInput = amountInput;

		// Store process function in window for modal access
		window.stripeProcessTestCharge = () => {
			this.processTestCharge(this.modalAmount, this.modalInput);
		};

		// Open the modal with data passed through the store
		this.$store.modals.open('stripe-test-charge-modal', {
			chargeAmount: amount
		});
	},

	processTestCharge(amount, amountInput) {
		this.creatingCharge = true;
		this.chargeResult = null;
		this.chargeError = null;

		const formData = new FormData();
		formData.append('action', 'affwp_stripe_create_test_charge');
		formData.append('nonce', '<?php echo esc_attr( wp_create_nonce( 'affwp_stripe_create_test_charge' ) ); ?>');
		formData.append('amount', amount);

		fetch(ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(response => response.json())
		.then(data => {
			this.creatingCharge = false;
			if (data.success) {
				this.chargeResult = data.data.message;
				// Update the balance if provided
				if (data.data.new_balance) {
					this.currentBalance = data.data.new_balance;
				}
				if (data.data.balance_data) {
					this.balanceData = data.data.balance_data;
				}
				// Reset amount to default
				if (amountInput) {
					amountInput.value = '500';
				}
			} else {
				this.chargeError = data.data.message || 'Failed to create test charge';
			}

			// Clear after 10 seconds
			setTimeout(() => {
				this.chargeResult = null;
				this.chargeError = null;
			}, 10000);
		})
		.catch(error => {
			this.creatingCharge = false;
			this.chargeError = 'Network error occurred';
			console.error('Test charge error:', error);
		});
	}
}">

	<!-- Header Section -->
	<div class="mb-6">
		<h3 class="mb-2 text-xl font-semibold text-gray-900"><?php esc_html_e( 'Stripe Payouts Configuration', 'affiliate-wp' ); ?></h3>
		<p class="text-sm text-gray-600">
			<?php esc_html_e( 'Configure Stripe Connect to send instant payments to affiliates via bank transfer.', 'affiliate-wp' ); ?>
		</p>
	</div>

	<!-- Main Settings Container -->
	<div class="space-y-6">

		<!-- Live Credentials Section -->
		<div class="overflow-hidden bg-white rounded-lg border border-gray-200">
			<div class="relative p-6 transition-colors" :class="!testMode ? 'bg-green-50/20 border-l-4 border-green-500' : 'border-l-4 border-transparent'">
				<div class="mb-4">
					<h4 class="flex items-center text-base font-medium text-gray-900">
						<?php esc_html_e( 'Live Mode', 'affiliate-wp' ); ?>
						<?php
						affwp_badge(
							[
								'text'    => __( 'Active', 'affiliate-wp' ),
								'variant' => 'success',
								'size'    => 'sm',
								'class'   => 'ml-2',
								'alpine'  => [
									'show'       => '!testMode && ' . ( $has_live_creds ? 'true' : 'false' ),
									'cloak'      => true,
									'transition' => [
										'enter'       => 'transition ease-out duration-200',
										'enter-start' => 'opacity-0 scale-95 translate-x-2',
										'enter-end'   => 'opacity-100 scale-100 translate-x-0',
										'leave'       => 'transition ease-in duration-150',
										'leave-start' => 'opacity-100 scale-100 translate-x-0',
										'leave-end'   => 'opacity-0 scale-95 translate-x-2',
									],
								],
							]
						);
						?>
					</h4>
					<p class="mt-1 text-sm text-gray-600"><?php esc_html_e( 'Process payments using your live Stripe account', 'affiliate-wp' ); ?></p>
				</div>

				<div class="space-y-5">
					<div>
						<label for="stripe-live-secret" class="block mb-1 text-sm font-medium text-gray-700"><?php esc_html_e( 'Secret Key', 'affiliate-wp' ); ?></label>
						<div class="relative">
							<input
								type="password"
								id="stripe-live-secret"
								name="affwp_settings[stripe_live_secret_key]"
								value="<?php echo esc_attr( $live_secret_key ); ?>"
								autocomplete="new-password"
								data-lpignore="true"
								data-1p-ignore
								class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
								placeholder="<?php esc_attr_e( 'sk_live_...', 'affiliate-wp' ); ?>" />

						</div>
						<p class="mt-2 text-sm text-gray-500">
							<?php
							// affwp_render_link() returns pre-escaped HTML (esc_url for href, esc_attr for attributes).
							/* translators: %s: Link to Stripe Dashboard */
							printf(
								esc_html__( 'Find this in your %s under Developers → API keys.', 'affiliate-wp' ),
								affwp_render_link(
									[
										'text'     => esc_html__( 'Stripe Dashboard', 'affiliate-wp' ),
										'href'     => 'https://dashboard.stripe.com/apikeys',
										'external' => true,
										'variant'  => 'default',
									]
								)
							);
							?>
						</p>
					</div>
				</div>

			</div>
		</div>

		<!-- Sandbox Mode Section -->
		<div class="overflow-hidden bg-white rounded-lg border border-gray-200 transition-all duration-300"
			:class="testMode ? 'border-l-4 border-l-orange-500 bg-orange-50/20' : 'bg-white'">
			<!-- Sandbox Mode Header - Always visible -->
			<div class="p-6 transition-colors cursor-pointer"
				@click="testMode = !testMode">
				<div class="flex justify-between items-center">
					<div class="flex-1">
						<h4 class="flex items-center text-base font-medium text-gray-900">
							<?php esc_html_e( 'Sandbox Mode', 'affiliate-wp' ); ?>
							<?php
							affwp_badge(
								[
									'text'    => __( 'Active', 'affiliate-wp' ),
									'variant' => 'warning',
									'size'    => 'sm',
									'class'   => 'ml-2',
									'alpine'  => [
										'show'       => 'testMode && ' . ( $has_test_creds ? 'true' : 'false' ),
										'cloak'      => true,
										'transition' => [
											'enter'       => 'transition ease-out duration-200',
											'enter-start' => 'opacity-0 scale-95 translate-x-2',
											'enter-end'   => 'opacity-100 scale-100 translate-x-0',
											'leave'       => 'transition ease-in duration-150',
											'leave-start' => 'opacity-100 scale-100 translate-x-0',
											'leave-end'   => 'opacity-0 scale-95 translate-x-2',
										],
									],
								]
							);
							?>
						</h4>
						<p class="mt-1 text-sm text-gray-600"
							x-show="!testMode">
							<?php esc_html_e( 'Enable to test payments without real transactions', 'affiliate-wp' ); ?>
						</p>
						<p class="mt-1 text-sm text-gray-600"
							x-show="testMode">
							<?php esc_html_e( 'Process payments using Stripe Test Mode for testing', 'affiliate-wp' ); ?>
						</p>
					</div>
					<div class="flex items-center ml-4" @click.stop>
						<input type="hidden" name="affwp_settings[stripe_test_mode]" value="0" />
						<?php
						// Use the new toggle component.
						affwp_toggle(
							[
								'name'         => 'affwp_settings[stripe_test_mode]',
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
				<div class="px-6 pb-6 space-y-5">
					<div>
						<label for="stripe-test-secret" class="block mb-1 text-sm font-medium text-gray-700"><?php esc_html_e( 'Test Secret Key', 'affiliate-wp' ); ?></label>
						<div class="relative">
							<input
								type="password"
								id="stripe-test-secret"
								name="affwp_settings[stripe_test_secret_key]"
								value="<?php echo esc_attr( $test_secret_key ); ?>"
								autocomplete="new-password"
								data-lpignore="true"
								data-1p-ignore
								class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
								placeholder="<?php esc_attr_e( 'sk_test_...', 'affiliate-wp' ); ?>" />

						</div>
						<p class="mt-2 text-sm text-gray-500">
							<?php
							/* translators: %s: Link to Stripe Test Dashboard */
							printf(
								esc_html__( 'Find this in your %s under Developers → API keys.', 'affiliate-wp' ),
								affwp_render_link(
									[
										'text'     => esc_html__( 'Stripe Test Dashboard', 'affiliate-wp' ),
										'href'     => 'https://dashboard.stripe.com/test/apikeys',
										'external' => true,
										'variant'  => 'default',
									]
								)
							);
							?>
						</p>
					</div>


					<!-- Test Balance Management Section -->
					<div class="mt-6">
						<label class="block mb-1 text-sm font-medium text-gray-700"><?php esc_html_e( 'Available Test Balance', 'affiliate-wp' ); ?></label>

						<!-- Current Balance Display -->
						<div class="mb-4">
							<div class="flex items-center justify-between p-3 rounded-lg border border-gray-200">
								<div>

									<span class="ml-2 text-lg font-bold text-gray-900" x-html="currentBalance">$0.00</span>
								</div>
								<?php
								affwp_button(
									[
										'text'       => __( 'Refresh', 'affiliate-wp' ),
										'variant'    => 'secondary',
										'size'       => 'sm',
										'type'       => 'button',
										'attributes' => [
											'@click'    => 'checkBalance()',
											':disabled' => 'checkingBalance',
											'x-text'    => "checkingBalance ? '" . esc_html__( 'Refreshing...', 'affiliate-wp' ) . "' : '" . esc_html__( 'Refresh', 'affiliate-wp' ) . "'",
										],
									]
								);
								?>
							</div>

							<!-- Balance notifications -->

							<div x-show="balanceResult" x-transition class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
								<p class="text-sm text-green-700" x-text="balanceResult"></p>
							</div>
							<div x-show="balanceError" x-transition class="mt-2 p-2 bg-red-50 rounded-md border border-red-200">
								<p class="text-sm text-red-700" x-text="balanceError"></p>
							</div>
						</div>

						<!-- Add Test Funds -->
						<div class="mt-4">
							<label for="stripe-test-charge-amount" class="block mb-1 text-sm font-medium text-gray-700"><?php esc_html_e( 'Add Test Funds', 'affiliate-wp' ); ?></label>
							<div class="flex items-center space-x-2">
								<div class="focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 flex items-center rounded-md bg-white pl-3 border-1 border-gray-300">
									<div class="shrink-0 text-base text-gray-500 select-none"><?php echo affwp_get_currency(); ?></div>
									<input
										id="stripe-test-charge-amount"
										type="number"
										name="test-charge-amount"
										value="500"
										min="1"
										max="10000"
										step="1"
										placeholder="500"
										class="block min-w-0 w-20 py-2 pr-3 pl-2 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none" />
								</div>

								<?php
								affwp_button(
									[
										'text'       => esc_html__( 'Add Funds', 'affiliate-wp' ),
										'variant'    => 'secondary',
										'type'       => 'button',
										'size'       => 'md',
										'attributes' => [
											'@click'    => 'createTestCharge()',
											':disabled' => 'creatingCharge',
											'x-text'    => "creatingCharge ? '" . esc_html__( 'Adding...', 'affiliate-wp' ) . "' : '" . esc_html__( 'Add Funds', 'affiliate-wp' ) . "'",
										],
									]
								);
								?>
							</div>

							<!-- Charge Result Messages -->
							<div x-show="chargeResult" x-transition class="mt-3 p-3 bg-green-50 rounded-lg border border-green-200">
								<p class="text-sm text-green-700" x-text="chargeResult"></p>
							</div>
							<div x-show="chargeError" x-transition class="mt-3 p-3 bg-red-50 rounded-lg border border-red-200">
								<p class="text-sm text-red-700" x-text="chargeError"></p>
							</div>

							<p class="mt-3 text-sm text-gray-500">
								<?php esc_html_e( 'Add funds to your test balance to pay referrals in Stripe Test Mode.', 'affiliate-wp' ); ?>
							</p>
						</div>

					</div>


			</div>
		</div>
	</div>

		<!-- Webhook Configuration -->
		<div id="stripe-webhooks" class="bg-white rounded-lg border border-gray-200">
			<div class="p-6">
				<div class="mb-4">
					<div class="flex justify-between items-start">
						<div>
							<div class="flex flex-wrap gap-2 items-center mb-1">
								<h4 class="text-base font-medium text-gray-900"><?php esc_html_e( 'Webhook Configuration', 'affiliate-wp' ); ?></h4>
								<?php
								affwp_badge(
									[
										'text'    => esc_html__( 'Recommended', 'affiliate-wp' ),
										'variant' => 'success',
										'size'    => 'sm',
									]
								);
								?>
							</div>
							<p class="mt-1 text-sm text-gray-600">
								<?php esc_html_e( 'Enable real-time updates for affiliate account requirements and payout tracking', 'affiliate-wp' ); ?>
							</p>
						</div>

					</div>
				</div>


				<div class="space-y-5">
					<!-- Webhook URL with Copy Button -->
					<div>
						<label class="block mb-1 text-sm font-medium text-gray-700">
							<?php esc_html_e( 'Webhook Endpoint URL', 'affiliate-wp' ); ?>
						</label>
						<div class="flex items-center space-x-2">
							<input
								type="text"
								readonly
								value="<?php echo esc_attr( home_url( '/wp-json/affwp/v1/stripe/webhook' ) ); ?>"
								class="flex-1 px-4 py-2 font-mono text-sm bg-gray-50 rounded-lg border-1 border-gray-300"
								id="webhook-url-field" />
							<?php
							echo affwp_render_copy_button(
								[
									'content'      => home_url( '/wp-json/affwp/v1/stripe/webhook' ),
									'button_text'  => esc_html__( 'Copy', 'affiliate-wp' ),
									'success_text' => esc_html__( 'Copied!', 'affiliate-wp' ),
									'variant'      => 'secondary',
									'size'         => 'md',
								]
							);
							?>
						</div>
						<p class="mt-2 text-sm text-gray-500">
							<?php
							$webhook_url = $test_mode
								? 'https://dashboard.stripe.com/test/webhooks'
								: 'https://dashboard.stripe.com/webhooks';

							/* translators: %s: Link to Stripe account */
							printf(
								esc_html__( 'Copy this URL and add it as a Webhook endpoint in your %s.', 'affiliate-wp' ),
								affwp_render_link(
									[
										'text'     => esc_html__( 'Stripe account', 'affiliate-wp' ),
										'href'     => 'https://dashboard.stripe.com/webhooks',
										'external' => true,
										'variant'  => 'default',
									]
								),
							);
							?>
						</p>
					</div>

					<!-- Webhook Secret -->
					<div>
						<label for="stripe-webhook-secret" class="block mb-1 text-sm font-medium text-gray-700">
							<?php esc_html_e( 'Webhook Signing Secret', 'affiliate-wp' ); ?>
						</label>
						<input
							type="password"
							id="stripe-webhook-secret"
							name="affwp_settings[stripe_webhook_secret]"
							value="<?php echo esc_attr( $webhook_secret ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400"
							placeholder="<?php esc_attr_e( 'whsec_...', 'affiliate-wp' ); ?>" />
							<p class="mt-2 text-sm text-gray-500">
							<?php
							esc_html_e( 'Copy the signing secret from Stripe and add it here.', 'affiliate-wp' );
							?>
						</p>
					</div>



				</div>
			</div>
		</div>

	</div> <!-- End of Main Settings Container -->

	<!-- Documentation Link -->
	<div class="mt-6 text-center">
		<?php
		affwp_link(
			[
				'text'     => esc_html__( 'View Stripe Payouts Documentation', 'affiliate-wp' ),
				'href'     => 'https://affiliatewp.com/docs/stripe-payouts/',
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

<?php
// Prepare the test charge confirmation modal content.
ob_start();
?>
<div x-data="{
	get modalData() {
		return Alpine.store('modals').registry['stripe-test-charge-modal']?.data || {};
	}
}">
	<div class="space-y-5 flex flex-col">
		<p class="text-base text-center  text-gray-700">
			<?php esc_html_e( 'Add', 'affiliate-wp' ); ?>
			<strong class="text-gray-900 font-semibold" x-text="'$' + (modalData.chargeAmount || 0).toFixed(2)"></strong>
			<?php esc_html_e( 'to your Stripe test balance to process test payouts.', 'affiliate-wp' ); ?>
		</p>

		<ul class="space-y-3 text-base max-w-[330px] self-center">
			<li class="flex items-center text-gray-700">
				<span class="mr-2.5 text-green-600 flex-shrink-0">
					<?php \AffiliateWP\Utils\Icons::render( 'check-circle', '', [ 'class' => 'size-6' ] ); ?>
				</span>
				<span>
					<?php esc_html_e( 'Test funds for', 'affiliate-wp' ); ?>
					<strong class="text-gray-900 font-semibold" x-text="'$' + (modalData.chargeAmount || 0).toFixed(2) + ' <?php echo esc_js( affwp_get_currency() ); ?>'"></strong>
				</span>
			</li>
			<li class="flex items-center text-gray-700">
				<span class="mr-2.5 text-green-600 flex-shrink-0">
					<?php \AffiliateWP\Utils\Icons::render( 'check-circle', '', [ 'class' => 'size-6' ] ); ?>
				</span>
				<span><?php esc_html_e( 'Available immediately for test payouts', 'affiliate-wp' ); ?></span>
			</li>
			<li class="flex items-center text-gray-700">
				<span class="mr-2.5 text-green-600 flex-shrink-0">
					<?php \AffiliateWP\Utils\Icons::render( 'check-circle', '', [ 'class' => 'size-6' ] ); ?>
				</span>
				<span><?php esc_html_e( 'No real money involved (test mode only)', 'affiliate-wp' ); ?></span>
			</li>
		</ul>
	</div>
</div>
<?php
$test_charge_content = ob_get_clean();

// Render the test charge confirmation modal.
affwp_modal(
	[
		'id'             => 'stripe-test-charge-modal',
		'icon'           => 'beaker',
		'title'          => esc_html__( 'Add Test Funds to your Stripe Balance', 'affiliate-wp' ),
		'content'        => $test_charge_content,
		'size'           => 'sm',
		'variant'        => 'info',
		'footer_actions' => [
			[
				'text'    => esc_html__( 'Cancel', 'affiliate-wp' ),
				'variant' => 'secondary',
				'action'  => 'close',
			],
			[
				'text'       => esc_html__( 'Add Test Funds', 'affiliate-wp' ),
				'variant'    => 'primary',
				'attributes' => [
					'@click'    => 'window.stripeProcessTestCharge(); close();',
					'autofocus' => true,
				],
			],
		],
	]
);
?>
