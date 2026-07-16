<?php
/**
 * Admin Views: Payment Method Card with Collapsible Panels
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

// Status colors and labels.
$status_colors = [
	'active'         => 'bg-green-50 text-green-700 border-green-200',
	'connected'      => 'bg-green-50 text-green-700 border-green-200',
	'setup_required' => 'bg-orange-50 text-orange-700 border-orange-200',
	'installed'      => 'bg-orange-50 text-orange-700 border-orange-200',
	'available'      => 'bg-blue-50 text-blue-700 border-blue-200',
	'retired'        => 'bg-gray-100 text-gray-500 border-gray-200',
];

$status_labels = [
	'active'         => __( 'Active', 'affiliate-wp' ),
	'connected'      => __( 'Connected', 'affiliate-wp' ),
	'setup_required' => __( 'Setup Required', 'affiliate-wp' ),
	'installed'      => __( 'Setup Required', 'affiliate-wp' ),
	'available'      => __( 'Available', 'affiliate-wp' ),
	'retired'        => __( 'Retired', 'affiliate-wp' ),
];

$status       = $method['status'];
$status_color = isset( $status_colors[ $status ] ) ? $status_colors[ $status ] : $status_colors['available'];

// Check for custom status label from method, otherwise use default
if ( ! empty( $method['status_label'] ) ) {
	$status_label = $method['status_label'];
} else {
	$status_label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status_labels['available'];
}

// Determine if method can be toggled.
$has_new_settings = ! empty( $method['settings_callback'] ) || ( ! empty( $method['has_new_settings'] ) && $method['has_new_settings'] );
$is_retired       = 'retired' === $status;
$can_toggle       = ( in_array( $status, [ 'active', 'available' ], true ) && in_array( $method['type'], [ 'core' ], true ) ) ||
			( in_array( $status, [ 'setup_required', 'installed', 'active', 'connected', 'retired' ], true ) && $has_new_settings );
?>

<div class="overflow-hidden bg-white rounded-lg border transition-all duration-200 affwp-payment-card border-gray-900/10 hover:border-gray-300"
	x-data="{
		toggleEnabled:
		<?php
		// Initialize toggle state based on method settings
		if ( 'store_credit' === $method_id ) {
			echo wp_json_encode( (bool) affiliate_wp()->settings->get( 'store-credit' ) );
		} elseif ( 'manual' === $method_id ) {
			echo wp_json_encode( (bool) affiliate_wp()->settings->get( 'manual_payouts' ) );
		} elseif ( 'payouts_service' === $method_id ) {
			echo wp_json_encode( (bool) affiliate_wp()->settings->get( 'enable_payouts_service' ) );
		} elseif ( 'paypal' === $method_id ) {
			echo wp_json_encode( (bool) affiliate_wp()->settings->get( 'paypal_payouts' ) );
		} elseif ( 'stripe' === $method_id ) {
			echo wp_json_encode( (bool) affiliate_wp()->settings->get( 'stripe_payouts' ) );
		} else {
			echo wp_json_encode( 'active' === $status );
		}
		?>
		,
		<?php if ( 'paypal' === $method_id ) : ?>
		testModeEnabled: <?php echo wp_json_encode( (bool) affiliate_wp()->settings->get( 'paypal_test_mode', false ) ); ?>,
		<?php endif; ?>
		panelOpen: $persist(false).as('payment_<?php echo esc_attr( $method_id ); ?>_panel'),

		init() {
			// Check for deep link on load
			this.checkDeepLink();

			// Listen for hash changes
			window.addEventListener('hashchange', () => this.checkDeepLink());
		},

		checkDeepLink() {
			const hash = window.location.hash;
			const methodId = '<?php echo esc_js( $method_id ); ?>';

			// Check if URL hash matches this method (handle both _ and - formats)
			if (hash === '#' + methodId || hash === '#' + methodId.replace('_', '-')) {
				this.panelOpen = true;

				// Scroll to this card after render
				this.$nextTick(() => {
					this.$el.scrollIntoView({
						behavior: 'smooth',
						block: 'start'
					});

					// Add visual highlight
					this.$el.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');

					// Remove highlight after 2 seconds
					setTimeout(() => {
						this.$el.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
					}, 2000);
				});
			}
		}
	}"
	data-method="<?php echo esc_attr( $method_id ); ?>">

	<!-- Card Header -->
	<div class="p-4 sm:p-5 lg:p-6 cursor-pointer"
		<?php if ( ! empty( $method['settings_callback'] ) ) : ?>
		@click="if ($event.target.closest('input, button, a')) return; panelOpen = !panelOpen"
		<?php endif; ?>>
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<!-- Left Content -->
			<div class="flex flex-1 items-center space-x-3 sm:space-x-4">
				<!-- Icon -->
				<?php if ( ! empty( $method['icon'] ) ) : ?>
					<div class="flex-shrink-0">
						<?php
						// Check if icon is a URL or an icon name
						if ( filter_var( $method['icon'], FILTER_VALIDATE_URL ) ) :
							?>
							<img src="<?php echo esc_url( $method['icon'] ); ?>"
								alt="<?php echo esc_attr( $method['name'] ); ?>"
								class="object-contain w-10 h-10 sm:h-12 sm:w-12">
							<?php
						else :
							// Use AffiliateWP Icons class for icon names
							// Special styling for branded icons
							$icon_container_class = 'h-10 w-10 sm:h-12 sm:w-12 flex items-center justify-center';
							$icon_svg_class       = 'h-10 w-10 sm:h-12 sm:w-12';

							if ( 'store_credit' === $method_id ) {
								$icon_container_class .= ' bg-blue-600 rounded-lg text-white';
								$icon_svg_class        = 'h-6 w-6 sm:h-7 sm:w-7'; // Slightly smaller for better visual balance
							} else {
								$icon_container_class .= ' text-gray-600';
							}
							?>
							<div class="<?php echo esc_attr( $icon_container_class ); ?>">
								<?php
								if ( class_exists( '\AffiliateWP\Utils\Icons' ) ) {
									\AffiliateWP\Utils\Icons::render( $method['icon'], $method['name'], [ 'class' => $icon_svg_class ] );
								} else {
									// Fallback to a generic icon
									?>
									<svg class="w-8 h-8 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
										<path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
									</svg>
									<?php
								}
								?>
							</div>
						<?php endif; ?>
					</div>
				<?php elseif ( 'manual' === $method_id ) : ?>
					<div class="flex-shrink-0">
						<div class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-lg sm:h-12 sm:w-12">
							<svg class="w-5 h-5 text-gray-500 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
							</svg>
						</div>
					</div>
				<?php else : ?>
					<div class="flex-shrink-0">
						<div class="flex justify-center items-center w-10 h-10 bg-gray-100 rounded-lg sm:h-12 sm:w-12">
							<svg class="w-5 h-5 text-gray-400 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
							</svg>
						</div>
					</div>
				<?php endif; ?>

				<!-- Content -->
				<div class="flex-1 min-w-0">
					<div class="flex flex-wrap gap-2 items-center mb-1">
						<h3 class="text-sm font-semibold text-gray-900 sm:text-base">
							<?php echo esc_html( $method['name'] ); ?>
						</h3>
						<?php
						// Special handling for PayPal Payouts - show environment status
						if ( 'paypal' === $method_id ) {
							// Get PayPal settings
							$paypal_enabled = affiliate_wp()->settings->get( 'paypal_payouts', false );
							$test_mode      = affiliate_wp()->settings->get( 'paypal_test_mode', false );
							$payout_mode    = affiliate_wp()->settings->get( 'paypal_payout_mode', 'api' );

							// Only show badges if PayPal is enabled.
							if ( $paypal_enabled ) {
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
								?>
								<!-- PayPal Badges Container -->
								<div class="flex gap-2" x-cloak>
									<!-- Live Mode Badge (hidden when panel open) -->
									<div x-show="!panelOpen && !testModeEnabled && <?php echo $has_api_live_creds || $has_masspay_live_creds ? 'true' : 'false'; ?>"
										x-transition:enter="transition ease-out duration-300"
										x-transition:enter-start="opacity-0 translate-x-2"
										x-transition:enter-end="opacity-100 translate-x-0"
										x-transition:leave="transition ease-in duration-200"
										x-transition:leave-start="opacity-100 translate-x-0"
										x-transition:leave-end="opacity-0 translate-x-2">
										<?php
										affwp_badge(
											[
												'text'    => __( 'Live Mode', 'affiliate-wp' ),
												'variant' => 'success',
												'size'    => 'xs',
											]
										);
										?>
									</div>

									<!-- Sandbox Mode Badge (hidden when panel open) -->
									<div x-show="!panelOpen && testModeEnabled && <?php echo $has_api_test_creds || $has_masspay_test_creds ? 'true' : 'false'; ?>"
										x-transition:enter="transition ease-out duration-300"
										x-transition:enter-start="opacity-0 translate-x-2"
										x-transition:enter-end="opacity-100 translate-x-0"
										x-transition:leave="transition ease-in duration-200"
										x-transition:leave-start="opacity-100 translate-x-0"
										x-transition:leave-end="opacity-0 translate-x-2">
										<?php
										affwp_badge(
											[
												'text'    => __( 'Sandbox Mode', 'affiliate-wp' ),
												'variant' => 'warning',
												'size'    => 'xs',
											]
										);
										?>
									</div>

									<!-- Setup Required Badge (always visible, no transitions) -->
									<div x-show="(!testModeEnabled && !<?php echo $has_api_live_creds || $has_masspay_live_creds ? 'true' : 'false'; ?>) || (testModeEnabled && !<?php echo $has_api_test_creds || $has_masspay_test_creds ? 'true' : 'false'; ?>)">
										<?php
										affwp_badge(
											[
												'text'    => __( 'Setup Required', 'affiliate-wp' ),
												'variant' => 'warning',
												'size'    => 'xs',
											]
										);
										?>
									</div>
								</div>
								<?php
							}
							// Special handling for payouts_service - only show badge if enabled
						} elseif ( 'payouts_service' === $method_id ) {
							// Check if Payouts Service is enabled
							$payouts_service_enabled = affiliate_wp()->settings->get( 'enable_payouts_service', false );

							if ( $payouts_service_enabled ) {
								// Convert status color class to variant
								$variant = 'neutral';
								if ( strpos( $status_color, 'green' ) !== false ) {
									$variant = 'success';
								} elseif ( strpos( $status_color, 'orange' ) !== false ) {
									$variant = 'warning';
								} elseif ( strpos( $status_color, 'blue' ) !== false ) {
									$variant = 'info';
								} elseif ( strpos( $status_color, 'red' ) !== false ) {
									$variant = 'danger';
								}

								// Show different badges based on status
								if ( 'setup_required' === $status ) {
									// Setup Required badge - always visible even when panel is open
									?>
									<div x-cloak>
										<?php
										affwp_badge(
											[
												'text'    => $status_label,
												'variant' => $variant,
												'size'    => 'xs',
											]
										);
										?>
									</div>
									<?php
								} elseif ( in_array( $status, [ 'active', 'connected' ], true ) ) {
									// Connected badge - hide when panel is open
									?>
									<div x-show="!panelOpen"
										x-cloak
										x-transition:enter="transition ease-out duration-300"
										x-transition:enter-start="opacity-0 translate-x-2"
										x-transition:enter-end="opacity-100 translate-x-0"
										x-transition:leave="transition ease-in duration-200"
										x-transition:leave-start="opacity-100 translate-x-0"
										x-transition:leave-end="opacity-0 translate-x-2">
										<?php
										affwp_badge(
											[
												'text'    => $status_label,
												'variant' => $variant,
												'size'    => 'xs',
											]
										);
										?>
									</div>
									<?php
								}
							}
						} elseif ( 'stripe' === $method_id ) {
							// Special handling for Stripe
							$stripe_enabled = affiliate_wp()->settings->get( 'stripe_payouts', false );

							if ( $stripe_enabled ) {
								// Check credentials
								$test_mode      = affiliate_wp()->settings->get( 'stripe_test_mode', false );
								$has_test_creds = ! empty( affiliate_wp()->settings->get( 'stripe_test_secret_key' ) );
								$has_live_creds = ! empty( affiliate_wp()->settings->get( 'stripe_live_secret_key' ) );
								?>
								<!-- Stripe Badges Container -->
								<div class="flex gap-2" x-cloak>
									<?php if ( 'active' === $status ) : ?>
										<!-- Mode badges (hidden when panel open) -->
										<div x-show="!panelOpen"
											x-transition:enter="transition ease-out duration-300"
											x-transition:enter-start="opacity-0 translate-x-2"
											x-transition:enter-end="opacity-100 translate-x-0"
											x-transition:leave="transition ease-in duration-200"
											x-transition:leave-start="opacity-100 translate-x-0"
											x-transition:leave-end="opacity-0 translate-x-2"
											x-data="{
												stripeTestMode: <?php echo $test_mode ? 'true' : 'false'; ?>
											}"
											x-init="
												// Listen for Stripe mode changes
												window.addEventListener('stripe-mode-changed', (e) => {
													stripeTestMode = e.detail.testMode;
												});
												// Also check global state on init
												if (typeof window.affwpStripeTestMode !== 'undefined') {
													stripeTestMode = window.affwpStripeTestMode;
												}
											">
											<?php
											// Sandbox Mode badge
											affwp_badge(
												[
													'text' => __( 'Sandbox Mode', 'affiliate-wp' ),
													'variant' => 'warning',
													'size' => 'xs',
													'alpine' => [
														'show'       => 'stripeTestMode',
														'cloak'      => true,
														'transition' => [
															'enter' => 'transition ease-out duration-200',
															'enter-start' => 'opacity-0 scale-95',
															'enter-end' => 'opacity-100 scale-100',
															'leave' => 'transition ease-in duration-150',
															'leave-start' => 'opacity-100 scale-100',
															'leave-end' => 'opacity-0 scale-95',
														],
													],
												]
											);

											// Live Mode badge
											affwp_badge(
												[
													'text' => __( 'Live Mode', 'affiliate-wp' ),
													'variant' => 'success',
													'size' => 'xs',
													'alpine' => [
														'show'       => '!stripeTestMode',
														'cloak'      => true,
														'transition' => [
															'enter' => 'transition ease-out duration-200',
															'enter-start' => 'opacity-0 scale-95',
															'enter-end' => 'opacity-100 scale-100',
															'leave' => 'transition ease-in duration-150',
															'leave-start' => 'opacity-100 scale-100',
															'leave-end' => 'opacity-0 scale-95',
														],
													],
												]
											);
											?>
										</div>
									<?php elseif ( 'setup_required' === $status ) : ?>
										<!-- Setup Required badge (always visible, no transitions) -->
										<div>
											<?php
											affwp_badge(
												[
													'text' => __( 'Setup Required', 'affiliate-wp' ),
													'variant' => 'warning',
													'size' => 'xs',
												]
											);
											?>
										</div>
									<?php endif; ?>
								</div>
								<?php
							}
						} else {
							// Default badge logic for other payment methods
							// Never show "Available" as a badge - it's just internal status
							// Only show badges for meaningful states
							$show_badge = false; // Default to not showing

							if ( 'available' === $status ) {
								// Never show a badge for available status
								$show_badge = false;
							} elseif ( in_array( $status, [ 'setup_required', 'installed' ], true ) ) {
								// For setup required states, only show if method is enabled
								$is_enabled = false;

								// Check if this specific method is enabled
								if ( 'store_credit' === $method_id ) {
									$is_enabled = affiliate_wp()->settings->get( 'store-credit', false );
								} elseif ( 'paypal' === $method_id ) {
									$is_enabled = affiliate_wp()->settings->get( 'paypal_payouts', false );
								} elseif ( 'payouts_service' === $method_id ) {
									$is_enabled = affiliate_wp()->settings->get( 'enable_payouts_service', false );
								}

								// Only show badge if toggle is ON
								$show_badge = $is_enabled;
							} elseif ( in_array( $status, [ 'active', 'connected' ], true ) ) {
								// Don't show 'active' badge for Store Credit.
								if ( 'store_credit' === $method_id && 'active' === $status ) {
									$show_badge = false;
								} else {
									// Show badges for these meaningful states
									$show_badge = true;
								}
							}

							if ( $show_badge ) :
								?>
							<!-- Status Badge -->
								<?php
								// Convert status color class to variant
								$variant = 'neutral';
								if ( strpos( $status_color, 'green' ) !== false ) {
									$variant = 'success';
								} elseif ( strpos( $status_color, 'orange' ) !== false ) {
									$variant = 'warning';
								} elseif ( strpos( $status_color, 'blue' ) !== false ) {
									$variant = 'info';
								} elseif ( strpos( $status_color, 'red' ) !== false ) {
									$variant = 'danger';
								}

								affwp_badge(
									[
										'text'    => $status_label,
										'variant' => $variant,
										'size'    => 'xs',
									]
								);
								?>
								<?php
							endif;
						}
						?>
					</div>
					<p class="text-xs text-gray-600 sm:text-sm">
						<?php echo esc_html( $method['description'] ); ?>
					</p>
				</div>
			</div>

			<!-- Right Actions -->
			<div class="flex flex-row gap-3 items-center self-start sm:flex-row sm:ml-6 sm:self-center">
				<?php if ( $can_toggle ) : ?>
					<!-- Configure Button -->
					<?php if ( ! empty( $method['settings_callback'] ) ) : ?>
						<?php
						affwp_configure_button(
							[
								'type'        => 'button',
								'panel_id'    => $method_id . '-settings-panel',
								'method_name' => $method['name'],
							]
						);
						?>
					<?php endif; ?>

					<?php
					// Determine the setting key and current value for each method
					$setting_key = '';
					$is_enabled  = false;

					if ( 'store_credit' === $method_id ) {
						$setting_key = 'store-credit';
						$is_enabled  = affiliate_wp()->settings->get( 'store-credit', false );
					} elseif ( 'manual' === $method_id ) {
						$setting_key = 'manual_payouts';
						$is_enabled  = affiliate_wp()->settings->get( 'manual_payouts', false );
					} elseif ( 'payouts_service' === $method_id ) {
						$setting_key = 'enable_payouts_service';
						$is_enabled  = affiliate_wp()->settings->get( 'enable_payouts_service', false );
					} elseif ( 'paypal' === $method_id ) {
						$setting_key = 'paypal_payouts';
						$is_enabled  = affiliate_wp()->settings->get( 'paypal_payouts', false );
					} elseif ( 'stripe' === $method_id ) {
						$setting_key = 'stripe_payouts';
						$is_enabled  = affiliate_wp()->settings->get( 'stripe_payouts', false );
					}

					// Show toggle for methods that have a setting key
					if ( $setting_key ) :
						$checked = checked( 1, $is_enabled, false );

						// Use the new toggle component.
						$toggle_id       = $method_id . '-toggle';
						$toggle_attrs    = [
							'@change' => 'toggleEnabled = $event.target.checked',
							'aria-describedby' => $toggle_id . '-description',
						];

						affwp_toggle(
							[
								'name'         => sprintf( 'affwp_settings[%s]', $setting_key ),
								'id'           => $toggle_id,
								'label'        => sprintf( __( 'Enable %s', 'affiliate-wp' ), $method['name'] ),
								'checked'      => $is_enabled,
								'size'         => 'md',
								'color'        => 'blue',
								'alpine_model' => 'toggleEnabled',
								'attributes'   => $toggle_attrs,
							]
						);

						// Add screen reader description.
						if ( $setting_key ) : ?>
							<span id="<?php echo esc_attr( $toggle_id . '-description' ); ?>" class="sr-only">
								<?php printf( __( 'Toggle to enable or disable %s payment method for affiliates', 'affiliate-wp' ), $method['name'] ); ?>
							</span>
						<?php endif;
					endif;
					?>

				<?php else : ?>
					<!-- Other Status Actions -->
					<?php
					switch ( $status ) {
						case 'installed':
							if ( ! empty( $method['settings_url'] ) && '#' !== substr( $method['settings_url'], 0, 1 ) ) {
								?>
								<?php
								affwp_button(
									[
										'text'    => __( 'Configure', 'affiliate-wp' ),
										'variant' => 'primary',
										'href'    => $method['settings_url'],
									]
								);
								?>
								<?php
							} else {
								?>
								<?php
								affwp_configure_button(
									[
										'type'    => 'button',
										'variant' => 'primary',
									]
								);
								?>
								<?php
							}
							break;

						case 'available':
							if ( 'addon' === $method['type'] ) {
								?>
								<?php
								affwp_button(
									[
										'text'    => __( 'Install', 'affiliate-wp' ),
										'variant' => 'success',
										'href'    => $method['install_url'],
										'icon'    => [
											'name'     => 'plus',
											'position' => 'left',
										],
									]
								);
								?>
								<?php
							}
							break;
					}
					?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Collapsible Settings Panel -->
	<?php if ( ! empty( $method['settings_callback'] ) ) : ?>
	<div id="<?php echo esc_attr( $method_id . '-settings-panel' ); ?>"
		x-show="panelOpen"
		x-collapse.duration.300ms
		x-cloak
		class="overflow-hidden bg-white border-t border-gray-200"
		@keydown.escape="panelOpen = false"
		<?php if ( 'paypal' === $method_id ) : ?>
		@test-mode-changed.window="testModeEnabled = $event.detail.enabled"
		<?php endif; ?>>
		<div class="p-4 sm:p-5 lg:p-6">
			<?php call_user_func( $method['settings_callback'] ); ?>
		</div>
	</div>
	<?php elseif ( ! empty( $method['settings_callback'] ) || ( 'installed' === $status && '#' !== substr( $method['settings_url'] ?? '', 0, 1 ) ) ) : ?>
	<!-- Settings panel available for installed addons -->
	<div x-show="panelOpen"
		x-collapse.duration.300ms
		x-cloak
		class="overflow-hidden bg-white border-t border-gray-200">
		<div class="p-4 sm:p-5 lg:p-6">
			<div class="overflow-hidden">
				<?php
				if ( ! empty( $method['settings_callback'] ) ) {
					call_user_func( $method['settings_callback'] );
				} else {
					// Fallback content for installed addons without callback.
					?>
					<div class="py-4 text-center">
						<p class="text-gray-600"><?php esc_html_e( 'Settings are available in the addon settings page.', 'affiliate-wp' ); ?></p>
						<a href="<?php echo esc_url( $method['settings_url'] ); ?>" class="inline-flex items-center mt-2 text-blue-600 hover:text-blue-500">
							<?php esc_html_e( 'Go to Settings', 'affiliate-wp' ); ?>
							<svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
							</svg>
						</a>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php endif; ?>


</div>
