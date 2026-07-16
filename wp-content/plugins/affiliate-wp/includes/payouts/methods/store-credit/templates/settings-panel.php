<?php
/**
 * Store Credit Settings Panel Template
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/StoreCredit
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$store_credit_enabled   = affiliate_wp()->settings->get( 'store-credit', false );
$all_affiliates_enabled = affiliate_wp()->settings->get( 'store-credit-all-affiliates', false );
$opt_in_enabled         = affiliate_wp()->settings->get( 'store-credit-change-payment-method', false );
$subscriptions_enabled  = affiliate_wp()->settings->get( 'store-credit-woocommerce-subscriptions', false );

?>





<div class="max-w-3xl">

	<!-- Header Section -->
	<div class="mb-8">
		<h3 class="mb-2 text-xl font-semibold text-gray-900"><?php _e( 'Store Credit Configuration', 'affiliate-wp' ); ?></h3>
		<p class="text-sm text-gray-600">
			<?php _e( 'Configure how affiliates receive and use store credit as their payout method.', 'affiliate-wp' ); ?>
		</p>
	</div>

	<!-- Main Settings -->
	<div class="overflow-hidden bg-white rounded-lg border border-gray-200">




		<!-- Enable For All Affiliates -->
		<div class="border-b border-gray-200 last:border-b-0" x-data="{ enabled: <?php echo $all_affiliates_enabled ? 'true' : 'false'; ?> }">
			<div class="flex overflow-hidden gap-6 justify-between items-center p-5 transition-colors cursor-pointer hover:bg-gray-50"
				@click="enabled = !enabled; $refs.toggle_all_affiliates.querySelector('input[type=checkbox]').click()">
				<div class="flex-1 min-w-0">
					<span class="block mb-1 text-base font-medium text-gray-900"><?php _e( 'Enable For All Affiliates', 'affiliate-wp' ); ?></span>
					<p class="text-sm text-gray-600">
						<?php _e( 'Automatically enable store credit for all existing and new affiliates.', 'affiliate-wp' ); ?>
					</p>
				</div>
				<div class="flex-shrink-0" @click.stop x-ref="toggle_all_affiliates">
					<?php
					// Use the new toggle component
					affwp_toggle(
						[
							'name'         => 'affwp_settings[store-credit-all-affiliates]',
							'label'        => __( 'Enable for all affiliates', 'affiliate-wp' ),
							'checked'      => $all_affiliates_enabled,
							'size'         => 'md',
							'color'        => 'blue',
							'alpine_model' => 'enabled',
						]
					);
					?>
				</div>
			</div>
		</div>

		<!-- Allow Affiliate Opt-In -->
		<div class="border-b border-gray-200 last:border-b-0" x-data="{ enabled: <?php echo $opt_in_enabled ? 'true' : 'false'; ?> }">
			<div class="flex overflow-hidden gap-6 justify-between items-center p-5 transition-colors cursor-pointer hover:bg-gray-50"
				@click="enabled = !enabled; $refs.toggle_opt_in.querySelector('input[type=checkbox]').click()">
				<div class="flex-1 min-w-0">
					<span class="block mb-1 text-base font-medium text-gray-900"><?php _e( 'Allow Affiliate Opt-In', 'affiliate-wp' ); ?></span>
					<p class="text-sm text-gray-600">
						<?php _e( 'Let affiliates choose store credit as their payout method from their dashboard.', 'affiliate-wp' ); ?>
					</p>
				</div>
				<div class="flex-shrink-0" @click.stop x-ref="toggle_opt_in">
					<?php
					// Use the new toggle component
					affwp_toggle(
						[
							'name'         => 'affwp_settings[store-credit-change-payment-method]',
							'label'        => __( 'Allow affiliate opt-in', 'affiliate-wp' ),
							'checked'      => $opt_in_enabled,
							'size'         => 'md',
							'color'        => 'blue',
							'alpine_model' => 'enabled',
						]
					);
					?>
				</div>
			</div>
		</div>

		<!-- WooCommerce Subscriptions -->
		<?php
		$wc_subscriptions_active = class_exists( 'WC_Subscriptions' );

		// Check if WooCommerce Subscriptions is installed but not active
		$wc_subscriptions_installed   = false;
		$wc_subscriptions_plugin_file = '';

		if ( ! $wc_subscriptions_active ) {
			// Get all plugins to check if WooCommerce Subscriptions is installed
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins = get_plugins();
			foreach ( $all_plugins as $plugin_file => $plugin_data ) {
				if ( strpos( $plugin_file, 'woocommerce-subscriptions' ) !== false ||
					( isset( $plugin_data['Name'] ) && strpos( $plugin_data['Name'], 'WooCommerce Subscriptions' ) !== false ) ) {
					$wc_subscriptions_installed   = true;
					$wc_subscriptions_plugin_file = $plugin_file;
					break;
				}
			}
		}
		?>
		<div class="border-b border-gray-200 last:border-b-0"
			x-data="{
				enabled: <?php echo $subscriptions_enabled ? 'true' : 'false'; ?>,
				isActive: <?php echo $wc_subscriptions_active ? 'true' : 'false'; ?>,
				isInstalled: <?php echo $wc_subscriptions_installed ? 'true' : 'false'; ?>
			}">
			<div class="flex overflow-hidden gap-6 justify-between items-center p-5 transition-colors"
				:class="{ 'cursor-pointer hover:bg-gray-50': isActive }"
				@click="isActive && (enabled = !enabled, $refs.toggle_subscriptions?.querySelector('input[type=checkbox]')?.click())">
				<div class="flex-1 min-w-0">
					<span class="block mb-1 text-base font-medium text-gray-900"><?php _e( 'Apply to Subscription Renewals', 'affiliate-wp' ); ?></span>
					<p class="text-sm text-gray-600">
						<template x-if="isActive">
							<span><?php _e( 'Automatically apply store credit to WooCommerce subscription renewal payments.', 'affiliate-wp' ); ?></span>
						</template>
						<template x-if="!isActive && isInstalled">
							<span><?php _e( 'WooCommerce Subscriptions is installed but not active. Activate it to enable this feature.', 'affiliate-wp' ); ?></span>
						</template>
						<template x-if="!isActive && !isInstalled">
							<span><?php _e( 'Requires WooCommerce Subscriptions (paid plugin) to be installed and activated.', 'affiliate-wp' ); ?></span>
						</template>
					</p>
				</div>
				<div class="flex-shrink-0" @click.stop x-ref="toggle_subscriptions">
					<template x-if="isActive">
						<?php
						// Use the new toggle component
						affwp_toggle(
							[
								'name'         => 'affwp_settings[store-credit-woocommerce-subscriptions]',
								'label'        => __( 'Apply to subscription renewals', 'affiliate-wp' ),
								'checked'      => $subscriptions_enabled,
								'size'         => 'md',
								'color'        => 'blue',
								'alpine_model' => 'enabled',
							]
						);
						?>
					</template>
					<template x-if="!isActive && isInstalled">
						<?php
						if ( $wc_subscriptions_installed && ! $wc_subscriptions_active && current_user_can( 'activate_plugins' ) ) {
							// Use the new plugin activation button component
							affwp_plugin_activation_button(
								[
									'plugin_file'      => $wc_subscriptions_plugin_file,
									'button_text'      => __( 'Activate Now', 'affiliate-wp' ),
									'activating_text'  => __( 'Activating...', 'affiliate-wp' ),
									'success_text'     => __( 'Activated', 'affiliate-wp' ),
									'variant'          => 'secondary',
									'size'             => 'sm',
									'success_callback' => 'setTimeout(() => { this.isActive = true; }, 500);',
								]
							);
						}
						?>
					</template>
				</div>
			</div>
		</div>
	</div>

	<?php
	// Check plugin installation and activation status
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Check if plugins are installed (regardless of activation)
	$all_plugins     = get_plugins();
	$woo_installed   = false;
	$edd_installed   = false;
	$woo_plugin_file = '';
	$edd_plugin_file = '';

	foreach ( $all_plugins as $plugin_file => $plugin_data ) {
		if ( strpos( $plugin_file, 'woocommerce/woocommerce.php' ) !== false ) {
			$woo_installed   = true;
			$woo_plugin_file = $plugin_file;
		}
		if ( strpos( $plugin_file, 'easy-digital-downloads' ) !== false ) {
			$edd_installed   = true;
			$edd_plugin_file = $plugin_file;
		}
	}

	// Check if plugins are active
	$woo_active = $woo_installed && is_plugin_active( $woo_plugin_file );
	$edd_active = $edd_installed && is_plugin_active( $edd_plugin_file );

	// Check if integrations are enabled in AffiliateWP
	$woo_integration_enabled = in_array( 'woocommerce', array_keys( affiliate_wp()->integrations->get_enabled_integrations() ) );
	$edd_integration_enabled = in_array( 'edd', array_keys( affiliate_wp()->integrations->get_enabled_integrations() ) );

	// Check if we have a working integration
	$has_integration = ( $woo_active && $woo_integration_enabled ) || ( $edd_active && $edd_integration_enabled );

	// Only show requirements box if requirements are NOT met
	if ( ! $has_integration ) :
		?>
	<!-- Integration Requirements - Minimalist Design -->
	<div class="mt-6">

		<h4 class="mb-1 text-base font-medium text-gray-900">
			<?php _e( 'Connect an e-commerce platform', 'affiliate-wp' ); ?>
		</h4>
		<p class="mb-4 text-sm text-gray-600">
			<?php _e( 'Store Credit requires WooCommerce or Easy Digital Downloads', 'affiliate-wp' ); ?>
		</p>

		<!-- Platform Options -->
		<div class="space-y-3">

			<!-- WooCommerce Option -->
			<div class="p-4 rounded-lg border border-gray-200">
				<div class="flex justify-between items-center">
					<div>
						<h5 class="text-base font-medium text-gray-900"><?php _e( 'WooCommerce', 'affiliate-wp' ); ?></h5>
						<p class="mt-0.5 text-sm text-gray-500">
							<?php
							// Show plugin status
							if ( ! $woo_installed ) {
								echo '<span class="text-gray-600">' . __( 'Plugin not installed', 'affiliate-wp' ) . '</span>';
							} elseif ( ! $woo_active ) {
								echo '<span class="text-orange-600">' . __( 'Plugin inactive', 'affiliate-wp' ) . '</span>';
							} elseif ( ! $woo_integration_enabled ) {
								echo affwp_render_link(
									[
										'text'       => __( 'Plugin active', 'affiliate-wp' ),
										'href'       => '#',
										'class'      => 'text-blue-600 hover:text-blue-700 no-underline cursor-default',
										'attributes' => [ 'onclick' => 'return false;' ],
									]
								) . ' • <span class="text-gray-600">' . __( 'Integration disabled', 'affiliate-wp' ) . '</span>';
							} else {
								// This shouldn't show since we check $has_integration above
								echo '<span class="text-green-600">' . __( 'Ready', 'affiliate-wp' ) . '</span>';
							}
							?>
						</p>
					</div>
					<div>
						<?php if ( ! $woo_installed && current_user_can( 'install_plugins' ) ) : ?>
							<?php
							affwp_button(
								[
									'text'    => __( 'Install Plugin', 'affiliate-wp' ),
									'href'    => admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ),
									'variant' => 'secondary',
									'size'    => 'sm',
								]
							);
							?>
						<?php elseif ( $woo_installed && ! $woo_active && current_user_can( 'activate_plugins' ) ) : ?>
							<?php
							affwp_button(
								[
									'text'    => __( 'Activate Plugin', 'affiliate-wp' ),
									'href'    => wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $woo_plugin_file ), 'activate-plugin_' . $woo_plugin_file ),
									'variant' => 'secondary',
									'size'    => 'sm',
								]
							);
							?>
						<?php elseif ( $woo_active && ! $woo_integration_enabled ) : ?>
							<?php
							affwp_button(
								[
									'text'    => __( 'Enable Integration', 'affiliate-wp' ),
									'href'    => admin_url( 'admin.php?page=affiliate-wp-settings&tab=integrations' ),
									'variant' => 'primary',
									'size'    => 'sm',
								]
							);
							?>
						<?php endif; ?>
					</div>
				</div>
			</div>

					<!-- Easy Digital Downloads Option -->
					<div class="p-4 rounded-lg border border-gray-200 transition-colors hover:border-gray-300">
						<div class="flex justify-between items-center">
							<div>
								<h5 class="text-base font-medium text-gray-900"><?php _e( 'Easy Digital Downloads', 'affiliate-wp' ); ?></h5>
								<p class="mt-0.5 text-sm text-gray-500">
									<?php
									// Show plugin status
									if ( ! $edd_installed ) {
										echo '<span class="text-gray-600">' . __( 'Plugin not installed', 'affiliate-wp' ) . '</span>';
									} elseif ( ! $edd_active ) {
										echo '<span class="text-orange-600">' . __( 'Plugin inactive', 'affiliate-wp' ) . '</span>';
									} elseif ( ! $edd_integration_enabled ) {
										echo affwp_render_link(
											[
												'text'  => __( 'Plugin active', 'affiliate-wp' ),
												'href'  => '#',
												'class' => 'text-blue-600 hover:text-blue-700 no-underline cursor-default',
												'attributes' => [ 'onclick' => 'return false;' ],
											]
										) . ' • <span class="text-gray-600">' . __( 'Integration disabled', 'affiliate-wp' ) . '</span>';
									} else {
										// This shouldn't show since we check $has_integration above
										echo '<span class="text-green-600">' . __( 'Ready', 'affiliate-wp' ) . '</span>';
									}
									?>
								</p>
							</div>
							<div>
								<?php if ( ! $edd_installed && current_user_can( 'install_plugins' ) ) : ?>
									<?php
									affwp_button(
										[
											'text'    => __( 'Install Plugin', 'affiliate-wp' ),
											'href'    => admin_url( 'plugin-install.php?s=easy+digital+downloads&tab=search&type=term' ),
											'variant' => 'secondary',
											'size'    => 'sm',
										]
									);
									?>
								<?php elseif ( $edd_installed && ! $edd_active && current_user_can( 'activate_plugins' ) ) : ?>
									<?php
									affwp_button(
										[
											'text'    => __( 'Activate Plugin', 'affiliate-wp' ),
											'href'    => wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $edd_plugin_file ), 'activate-plugin_' . $edd_plugin_file ),
											'variant' => 'secondary',
											'size'    => 'sm',
										]
									);
									?>
								<?php elseif ( $edd_active && ! $edd_integration_enabled ) : ?>
									<?php
									affwp_button(
										[
											'text'    => __( 'Enable Integration', 'affiliate-wp' ),
											'href'    => admin_url( 'admin.php?page=affiliate-wp-settings&tab=integrations' ),
											'variant' => 'primary',
											'size'    => 'sm',
										]
									);
									?>
								<?php endif; ?>
							</div>
						</div>
					</div>

		</div>
	</div>
	<?php endif; ?>

</div>
