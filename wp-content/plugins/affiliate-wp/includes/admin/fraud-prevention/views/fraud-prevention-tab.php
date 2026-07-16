<?php
/**
 * Admin Views: Fraud Prevention Tab
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Fraud Prevention
 * @copyright   Copyright (c) 2025, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.31.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the fraud prevention controller instance.
$fraud_controller = new \AffiliateWP\Admin\Fraud_Prevention\Fraud_Prevention_Tab();
$features         = $fraud_controller->get_features();

// Check if user has Pro features access.
$has_pro_access = function_exists( 'affwp_can_access_pro_features' ) && affwp_can_access_pro_features();
?>

<style>[x-cloak] { display: none !important; }</style>

<div class="relative z-10 py-8 max-w-4xl affwp-ui" x-data="{
	message: '',
	messageType: 'success',
	fraud_prevention_conversion_rate: '<?php echo esc_js( affiliate_wp()->settings->get( 'fraud_prevention_conversion_rate', 'allow' ) ); ?>'
}">

	<!-- Skip Links -->
	<a href="#fraud-prevention-features" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-white focus:border focus:border-gray-300 focus:rounded-md focus:shadow-sm">
		<?php esc_html_e( 'Skip to fraud prevention features', 'affiliate-wp' ); ?>
	</a>

	<!-- Header -->
	<div class="mb-4 sm:mb-6">
		<h2 class="mb-1 text-lg font-semibold text-gray-900 sm:text-xl">
			<?php esc_html_e( 'Anti-Fraud Protection', 'affiliate-wp' ); ?>
		</h2>
		<p class="text-sm text-gray-600 sm:text-base">
			<?php esc_html_e( 'Protect your affiliate program from fraudulent activity with intelligent detection and prevention tools.', 'affiliate-wp' ); ?>
		</p>
	</div>

	<div class="bg-white rounded-lg border border-gray-900/10">
		<?php foreach ( $features as $feature_id => $feature ) : ?>
			<?php
			$is_pro_feature = 'pro' === $feature['required_license'];
			$is_locked      = $is_pro_feature && ! $has_pro_access;
			?>

			<!-- Feature Section -->
			<div id="<?php echo esc_attr( 'fraud-prevention-' . str_replace( '_', '-', $feature_id ) ); ?>" class="p-6 sm:p-8 <?php echo $feature_id !== array_key_first( $features ) ? 'border-t border-[#f0f0f1]' : ''; ?>">
				<!-- Title and Description -->
				<div class="mb-5">
					<div class="flex flex-wrap gap-2 items-center mb-2">
						<h3 class="text-base font-semibold text-gray-900">
							<?php echo esc_html( $feature['title'] ); ?>
						</h3>
						<?php if ( $is_locked ) : ?>
							<?php
							affwp_badge(
								[
									'text'    => __( 'Pro', 'affiliate-wp' ),
									'variant' => 'pro',
									'size'    => 'xs',
								]
							);
							?>
						<?php endif; ?>
					</div>
					<p class="text-sm text-gray-600">
						<?php echo esc_html( $feature['description'] ); ?>
					</p>
				</div>

				<!-- Settings -->
				<?php if ( ! empty( $feature['settings'] ) ) : ?>
					<?php if ( 'conversion_rate' === $feature_id ) : ?>
						<!-- Conversion Rate Layout -->
						<div class="" x-data="{ conversionRateAlt: '<?php echo esc_js( $is_locked ? 'allow' : affiliate_wp()->settings->get( 'fraud_prevention_conversion_rate', 'allow' ) ); ?>' }">
							<?php
							$cr_setting = $feature['settings'][0];
							// Only show the custom tooltip note — option descriptions
							// are already displayed inline below the radio buttons.
							$tooltip_content = ! empty( $cr_setting['tooltip'] ) ? $cr_setting['tooltip'] : '';
							$tooltip_html    = ! empty( $tooltip_content ) ? affwp_tooltip( [ 'content' => $tooltip_content ] ) : '';
							?>

							<div>
								<div class="flex items-center gap-2 mb-3">
									<label class="text-sm font-medium text-gray-900">
										<?php esc_html_e( 'Conversion Rate', 'affiliate-wp' ); ?>
									</label>
									<?php if ( ! empty( $tooltip_content ) ) : ?>
										<?php
										echo \AffiliateWP\Utils\Icons::get(
											'tooltip',
											[
												'class' => 'w-4 h-4 text-gray-400 cursor-help inline-block',
												'data-tooltip-html' => $tooltip_html,
											]
										);
										?>
									<?php endif; ?>
								</div>

								<?php if ( $is_locked ) : ?>
									<p class="text-sm text-gray-600 mb-3">
										<a href="<?php echo esc_url( affwp_admin_upgrade_link( 'settings-fraud-prevention', 'conversion-rate-detection' ) ); ?>"
											target="_blank"
											rel="noopener noreferrer"
											class="inline-flex items-center gap-1.5 text-affwp-brand-500 hover:text-affwp-brand-600 underline underline-offset-2 hover:underline transition-colors duration-200">
											<?php esc_html_e( 'Upgrade to Pro', 'affiliate-wp' ); ?>
										</a>
										<?php esc_html_e( ' to unlock this feature', 'affiliate-wp' ); ?>
									</p>
								<?php endif; ?>

								<div class="inline-flex gap-1 rounded-full bg-gray-950/10 p-1" role="radiogroup" aria-label="<?php esc_attr_e( 'Conversion Rate', 'affiliate-wp' ); ?>">
									<?php
									$cr_options       = [
										'allow' => __( 'Allow', 'affiliate-wp' ),
										'flag'  => __( 'Flag', 'affiliate-wp' ),
									];
									$current_cr_value = $is_locked ? 'allow' : affiliate_wp()->settings->get( 'fraud_prevention_conversion_rate', 'allow' );
									?>
									<?php foreach ( $cr_options as $value => $label ) : ?>
										<label class="group cursor-pointer"
											<?php if ( $is_locked ) : ?>
											@click.prevent="Alpine.store('modals').open('fraud-prevention-conversion-rate-upgrade-modal')"
											<?php endif; ?>>
											<input type="radio"
												name="affwp_settings[fraud_prevention_conversion_rate]"
												value="<?php echo esc_attr( $value ); ?>"
												<?php checked( $current_cr_value, $value ); ?>
												<?php if ( ! $is_locked ) : ?>
												x-model="conversionRateAlt"
												<?php endif; ?>
												class="sr-only peer">
											<span class="flex items-center rounded-full px-4 text-sm/7 font-medium text-gray-700 peer-checked:bg-white peer-checked:ring peer-checked:ring-gray-950/5 peer-checked:text-gray-900 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-2 transition-all">
												<?php echo esc_html( $label ); ?>
											</span>
										</label>
									<?php endforeach; ?>
								</div>

								<?php if ( ! empty( $cr_setting['option_descriptions'] ) ) : ?>
									<div class="mt-2">
										<?php foreach ( $cr_setting['option_descriptions'] as $opt_value => $opt_desc ) : ?>
											<p x-show="conversionRateAlt === '<?php echo esc_js( $opt_value ); ?>'"<?php if ( $opt_value !== $current_cr_value ) echo ' x-cloak'; ?> class="text-sm text-gray-600">
												<?php
												/* translators: %s: option description */
												printf( esc_html__( '%s', 'affiliate-wp' ), esc_html( $opt_desc ) );
												?>
											</p>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>

							<?php if ( ! $is_locked ) : ?>
								<!-- Min/Max Conversion Rate Fields -->
								<div x-show="conversionRateAlt === 'flag'"<?php if ( 'flag' !== $current_cr_value ) echo ' x-cloak'; ?> class="pl-4 space-y-4 border-l-2 border-gray-200 mt-8">
									<div>
										<label for="fraud_prevention_min_conversion_rate_alt" class="block text-sm font-medium text-gray-900 mb-1">
											<?php esc_html_e( 'Minimum Conversion Rate (%)', 'affiliate-wp' ); ?>
										</label>
										<p class="text-sm text-gray-600 mb-2">
											<?php esc_html_e( 'Minimum acceptable conversion rate percentage.', 'affiliate-wp' ); ?>
										</p>
										<input type="number"
												id="fraud_prevention_min_conversion_rate_alt"
												name="affwp_settings[fraud_prevention_min_conversion_rate]"
												value="<?php echo esc_attr( affiliate_wp()->settings->get( 'fraud_prevention_min_conversion_rate', 2 ) ); ?>"
												step="0.01"
												min="0"
												max="100"
												class="px-4 py-2 w-24 text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400">
									</div>

									<div>
										<label for="fraud_prevention_max_conversion_rate_alt" class="block text-sm font-medium text-gray-900 mb-1">
											<?php esc_html_e( 'Maximum Conversion Rate (%)', 'affiliate-wp' ); ?>
										</label>
										<p class="text-sm text-gray-600 mb-2">
											<?php esc_html_e( 'Maximum acceptable conversion rate percentage.', 'affiliate-wp' ); ?>
										</p>
										<input type="number"
												id="fraud_prevention_max_conversion_rate_alt"
												name="affwp_settings[fraud_prevention_max_conversion_rate]"
												value="<?php echo esc_attr( affiliate_wp()->settings->get( 'fraud_prevention_max_conversion_rate', 20 ) ); ?>"
												step="0.01"
												min="0"
												max="100"
												class="px-4 py-2 w-24 text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400">
									</div>
								</div>
							<?php endif; ?>
						</div>

					<?php else : ?>
						<!-- Standard Features Layout -->
						<?php
						$ip_logging_disabled         = ! empty( $feature['ip_logging_disabled'] );
						$is_disabled                 = ( 'ip_velocity' === $feature_id && $ip_logging_disabled && ! $is_locked );
						$require_approval_global     = ( 'ip_velocity' === $feature_id && ! empty( $feature['require_approval_global'] ) );
						?>
						<?php foreach ( $feature['settings'] as $setting ) : ?>
							<?php if ( 'radio' === $setting['type'] ) : ?>
								<?php
								$saved_radio_value   = ( $is_locked || $is_disabled ) ? 'allow' : affiliate_wp()->settings->get( $setting['id'], $setting['default'] );
								$display_radio_value = ( $require_approval_global && 'require_approval' === $saved_radio_value ) ? 'flag' : $saved_radio_value;
								?>
								<div x-data="{ currentValue: '<?php echo esc_js( $display_radio_value ); ?>' }">
									<div class="flex items-center gap-2 mb-3">
										<label class="text-sm font-medium text-gray-900">
											<?php echo esc_html( $setting['name'] ); ?>
										</label>
										<?php
										// Only show the custom tooltip note — option descriptions
										// are already displayed inline below the radio buttons.
										$tooltip_content = ! empty( $setting['tooltip'] ) ? $setting['tooltip'] : '';
										$tooltip_html    = ! empty( $tooltip_content ) ? affwp_tooltip( [ 'content' => $tooltip_content ] ) : '';
										?>
										<?php if ( ! empty( $tooltip_content ) ) : ?>
											<?php
											echo \AffiliateWP\Utils\Icons::get(
												'tooltip',
												[
													'class'             => 'w-4 h-4 text-gray-400 cursor-help inline-block',
													'data-tooltip-html' => $tooltip_html,
												]
											);
											?>
										<?php endif; ?>
									</div>

									<?php if ( $is_locked ) : ?>
										<p class="text-sm text-gray-600 mb-3">
											<a href="<?php echo esc_url( affwp_admin_upgrade_link( 'settings-fraud-prevention', $feature_id ) ); ?>"
												target="_blank"
												rel="noopener noreferrer"
												class="inline-flex items-center gap-1.5 text-affwp-brand-500 hover:text-affwp-brand-600 underline underline-offset-2 hover:underline transition-colors duration-200">
												<?php esc_html_e( 'Upgrade to Pro', 'affiliate-wp' ); ?>
											</a>
											<?php esc_html_e( ' to unlock this feature', 'affiliate-wp' ); ?>
										</p>
									<?php endif; ?>

									<?php if ( $is_disabled ) : ?>
										<div class="p-3 mb-4 text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-md">
											<?php
											printf(
												/* translators: 1: opening anchor tag, 2: closing anchor tag */
												esc_html__( 'IP logging is currently disabled. %1$sEnable IP Address Logging%2$s in the Advanced tab to use this feature.', 'affiliate-wp' ),
												'<a href="' . esc_url( admin_url( 'admin.php?page=affiliate-wp-settings&tab=advanced#disable_ip_logging' ) ) . '" class="inline-flex items-center gap-1.5 text-affwp-brand-500 hover:text-affwp-brand-600 underline underline-offset-2 hover:underline transition-colors duration-200">',
												'</a>'
											);
											?>
										</div>
									<?php endif; ?>

									<div class="<?php echo $is_disabled ? 'opacity-50 pointer-events-none' : ''; ?>">
									<div class="inline-flex gap-1 rounded-full bg-gray-950/10 p-1" role="radiogroup" aria-label="<?php echo esc_attr( $setting['name'] ); ?>">
										<?php foreach ( $setting['options'] as $value => $label ) : ?>
											<?php
											// Hide the Require Approval option when the global Require Approval setting is already enabled.
											if ( 'require_approval' === $value && $require_approval_global ) :
												continue;
											endif;
											$current_value = $is_locked ? 'allow' : $display_radio_value;
											$is_checked    = ( $current_value === $value );
											?>
											<label class="group cursor-pointer"
												<?php if ( $is_locked ) : ?>
												@click.prevent="Alpine.store('modals').open('fraud-prevention-<?php echo esc_js( $feature_id ); ?>-upgrade-modal')"
												<?php endif; ?>>
												<input type="radio"
													name="affwp_settings[<?php echo esc_attr( $setting['id'] ); ?>]"
													value="<?php echo esc_attr( $value ); ?>"
													<?php checked( $is_checked ); ?>
													<?php if ( ! $is_locked && ! $is_disabled ) : ?>
													@change="currentValue = $event.target.value"
													<?php endif; ?>
													<?php if ( $is_disabled ) : ?>disabled<?php endif; ?>
													class="sr-only peer">
												<span class="flex items-center rounded-full px-4 text-sm/7 font-medium text-gray-700 peer-checked:bg-white peer-checked:ring peer-checked:ring-gray-950/5 peer-checked:text-gray-900 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-2 transition-all">
													<?php echo esc_html( $label ); ?>
												</span>
											</label>
										<?php endforeach; ?>
									</div>

									<?php if ( ! empty( $setting['option_descriptions'] ) ) : ?>
										<div class="mt-2">
											<?php foreach ( $setting['option_descriptions'] as $opt_value => $opt_desc ) : ?>
												<p x-show="currentValue === '<?php echo esc_js( $opt_value ); ?>'"<?php if ( $opt_value !== $current_value ) echo ' x-cloak'; ?> class="text-sm text-gray-600">
													<?php
													/* translators: %s: option description */
													printf( esc_html__( '%s', 'affiliate-wp' ), wp_kses( $opt_desc, [ 'br' => [] ] ) );
													?>
												</p>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
									</div>

									<?php
									// IP Velocity Settings Fields (Threshold and Time Window).
									if ( 'ip_velocity' === $feature_id && ! $is_locked && ! $is_disabled ) :
										?>
										<div x-show="currentValue !== 'allow'"<?php if ( 'allow' === $current_value ) echo ' x-cloak'; ?> class="pl-4 space-y-4 border-l-2 border-gray-200 mt-8">
											<div>
												<label for="fraud_prevention_ip_velocity_threshold" class="block text-sm font-medium text-gray-900 mb-1">
													<?php esc_html_e( 'Registration Threshold', 'affiliate-wp' ); ?>
												</label>
												<p class="text-sm text-gray-600 mb-2">
													<?php esc_html_e( 'Maximum registrations from the same IP before triggering detection.', 'affiliate-wp' ); ?>
												</p>
												<input type="number"
														id="fraud_prevention_ip_velocity_threshold"
														name="affwp_settings[fraud_prevention_ip_velocity_threshold]"
														value="<?php echo esc_attr( affiliate_wp()->settings->get( 'fraud_prevention_ip_velocity_threshold', 3 ) ); ?>"
														step="1"
														min="2"
														max="100"
														class="px-4 py-2 w-24 text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400">
											</div>

											<div>
												<label for="fraud_prevention_ip_velocity_window" class="block text-sm font-medium text-gray-900 mb-1">
													<?php esc_html_e( 'Time Window (hours)', 'affiliate-wp' ); ?>
												</label>
												<p class="text-sm text-gray-600 mb-2">
													<?php esc_html_e( 'The time window in hours to check for multiple registrations.', 'affiliate-wp' ); ?>
												</p>
												<input type="number"
														id="fraud_prevention_ip_velocity_window"
														name="affwp_settings[fraud_prevention_ip_velocity_window]"
														value="<?php echo esc_attr( affiliate_wp()->settings->get( 'fraud_prevention_ip_velocity_window', 24 ) ); ?>"
														step="1"
														min="1"
														max="720"
														class="px-4 py-2 w-24 text-base rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400">
											</div>
										</div>
									<?php endif; ?>
								</div>

							<?php elseif ( 'textarea' === $setting['type'] ) : ?>
								<div>
									<div class="flex items-center gap-2 mb-1">
										<label for="<?php echo esc_attr( $setting['id'] ); ?>" class="text-sm font-medium text-gray-900">
											<?php echo esc_html( $setting['name'] ); ?>
										</label>
										<?php if ( ! empty( $setting['tooltip'] ) ) : ?>
											<?php
											$tooltip_html = affwp_tooltip( [ 'content' => $setting['tooltip'] ] );
											echo \AffiliateWP\Utils\Icons::get(
												'tooltip',
												[
													'class'             => 'w-4 h-4 text-gray-400 cursor-help inline-block',
													'data-tooltip-html' => $tooltip_html,
												]
											);
											?>
										<?php endif; ?>
									</div>

									<?php if ( $is_locked ) : ?>
										<p class="text-sm text-gray-600 mb-3">
											<a href="<?php echo esc_url( affwp_admin_upgrade_link( 'settings-fraud-prevention', $feature_id ) ); ?>"
												target="_blank"
												rel="noopener noreferrer"
												class="inline-flex items-center gap-1.5 text-affwp-brand-500 hover:text-affwp-brand-600 underline underline-offset-2 hover:underline transition-colors duration-200">
												<?php esc_html_e( 'Upgrade to Pro', 'affiliate-wp' ); ?>
											</a>
											<?php esc_html_e( ' to unlock this feature', 'affiliate-wp' ); ?>
										</p>
									<?php endif; ?>

									<?php if ( ! empty( $setting['description'] ) ) : ?>
										<p class="text-sm text-gray-600 mb-2">
											<?php echo esc_html( $setting['description'] ); ?>
										</p>
									<?php endif; ?>
									<textarea
										id="<?php echo esc_attr( $setting['id'] ); ?>"
										name="affwp_settings[<?php echo esc_attr( $setting['id'] ); ?>]"
										rows="6"
										class="w-full px-4 py-2 text-sm rounded-lg border-1 border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 font-mono"
										<?php
										if ( $is_locked ) :
											?>
											disabled<?php endif; ?>
									><?php echo esc_textarea( affiliate_wp()->settings->get( $setting['id'], '' ) ); ?></textarea>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>

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

<?php if ( ! affwp_can_access_pro_features() ) : ?>
<?php
	$tab_instance       = new \AffiliateWP\Admin\Fraud_Prevention\Fraud_Prevention_Tab();
	$upgrade_utm_medium = \AffiliateWP\Admin\Education\Non_Pro::get_utm_medium();

	// PPC Traffic Detection Modal
	affwp_modal(
		[
			'id'                => 'fraud-prevention-ppc_detection-upgrade-modal',
			'title'             => __( 'PPC Traffic Detection', 'affiliate-wp' ),
			'variant'           => 'info',
			'icon'              => [
				'svg' => \AffiliateWP\Utils\Icons::get( 'shield', [ 'class' => 'size-12 text-affwp-brand-500' ] ),
			],
			'content'           => $tab_instance->get_upgrade_modal_content( 'ppc_detection' ),
			'show_close'        => true,
			'close_on_backdrop' => true,
			'close_on_escape'   => true,
			'footer_actions'    => [
				[
					'text'       => __( 'Maybe Later', 'affiliate-wp' ),
					'variant'    => 'secondary',
					'attributes' => [
						'@click' => "\$store.modals.close('fraud-prevention-ppc_detection-upgrade-modal')",
					],
				],
				[
					'text'       => __( 'Upgrade to Pro', 'affiliate-wp' ),
					'variant'    => 'primary',
					'href'       => affwp_admin_upgrade_link( $upgrade_utm_medium, 'ppc-traffic-detection' ),
					'attributes' => [
						'target'    => '_blank',
						'rel'       => 'noopener noreferrer',
						'autofocus' => true,
					],
				],
			],
		]
	);

	// Conversion Rate Detection Modal
	affwp_modal(
		[
			'id'                => 'fraud-prevention-conversion-rate-upgrade-modal',
			'title'             => __( 'Conversion Rate Detection', 'affiliate-wp' ),
			'variant'           => 'info',
			'icon'              => [
				'svg' => \AffiliateWP\Utils\Icons::get( 'shield', [ 'class' => 'size-12 text-affwp-brand-500' ] ),
			],
			'content'           => $tab_instance->get_upgrade_modal_content( 'conversion_rate' ),
			'show_close'        => true,
			'close_on_backdrop' => true,
			'close_on_escape'   => true,
			'footer_actions'    => [
				[
					'text'       => __( 'Maybe Later', 'affiliate-wp' ),
					'variant'    => 'secondary',
					'attributes' => [
						'@click' => "\$store.modals.close('fraud-prevention-conversion-rate-upgrade-modal')",
					],
				],
				[
					'text'       => __( 'Upgrade to Pro', 'affiliate-wp' ),
					'variant'    => 'primary',
					'href'       => affwp_admin_upgrade_link( $upgrade_utm_medium, 'conversion-rate-detection' ),
					'attributes' => [
						'target'    => '_blank',
						'rel'       => 'noopener noreferrer',
						'autofocus' => true,
					],
				],
			],
		]
	);

	// Referring Sites Detection Modal (if applicable)
	if ( affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) :
		affwp_modal(
			[
				'id'                => 'fraud-prevention-referring_sites-upgrade-modal',
				'title'             => __( 'Referring Sites Detection', 'affiliate-wp' ),
				'variant'           => 'info',
				'icon'              => [
					'svg' => \AffiliateWP\Utils\Icons::get( 'shield', [ 'class' => 'size-12 text-affwp-brand-500' ] ),
				],
				'content'           => $tab_instance->get_upgrade_modal_content( 'referring_sites' ),
				'show_close'        => true,
				'close_on_backdrop' => true,
				'close_on_escape'   => true,
				'footer_actions'    => [
					[
						'text'       => __( 'Maybe Later', 'affiliate-wp' ),
						'variant'    => 'secondary',
						'attributes' => [
							'@click' => "\$store.modals.close('fraud-prevention-referring_sites-upgrade-modal')",
						],
					],
					[
						'text'       => __( 'Upgrade to Pro', 'affiliate-wp' ),
						'variant'    => 'primary',
						'href'       => affwp_admin_upgrade_link( $upgrade_utm_medium, 'referring-sites-detection' ),
						'attributes' => [
							'target'    => '_blank',
							'rel'       => 'noopener noreferrer',
							'autofocus' => true,
						],
					],
				],
			]
		);
	endif;

	// IP Velocity Detection Modal
	affwp_modal(
		[
			'id'                => 'fraud-prevention-ip_velocity-upgrade-modal',
			'title'             => __( 'IP Velocity Detection', 'affiliate-wp' ),
			'variant'           => 'info',
			'icon'              => [
				'svg' => \AffiliateWP\Utils\Icons::get( 'shield', [ 'class' => 'size-12 text-affwp-brand-500' ] ),
			],
			'content'           => $tab_instance->get_upgrade_modal_content( 'ip_velocity' ),
			'show_close'        => true,
			'close_on_backdrop' => true,
			'close_on_escape'   => true,
			'footer_actions'    => [
				[
					'text'       => __( 'Maybe Later', 'affiliate-wp' ),
					'variant'    => 'secondary',
					'attributes' => [
						'@click' => "\$store.modals.close('fraud-prevention-ip_velocity-upgrade-modal')",
					],
				],
				[
					'text'       => __( 'Upgrade to Pro', 'affiliate-wp' ),
					'variant'    => 'primary',
					'href'       => affwp_admin_upgrade_link( $upgrade_utm_medium, 'ip-velocity-detection' ),
					'attributes' => [
						'target'    => '_blank',
						'rel'       => 'noopener noreferrer',
						'autofocus' => true,
					],
				],
			],
		]
	);
?>
<?php endif; ?>
