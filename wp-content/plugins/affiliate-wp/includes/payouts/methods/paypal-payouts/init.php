<?php
/**
 * PayPal Payouts Module Initialization
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/PayPalPayouts
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if addon exists and handle deactivation
add_action(
	'admin_init',
	function () {
		$addon_plugin = 'affiliate-wp-paypal-payouts/affiliate-wp-paypal-payouts.php';

		// If addon is active, deactivate it and show notice
		if ( is_plugin_active( $addon_plugin ) ) {
			deactivate_plugins( $addon_plugin );

			// Add notice about automatic deactivation
			add_action(
				'admin_notices',
				function () {
					$paypal_settings_url = admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts#paypal' );
					?>
			<div class="notice notice-success is-dismissible">
					<p><strong><?php _e( 'PayPal Payouts Addon Deactivated', 'affiliate-wp' ); ?></strong></p>
					<p><?php _e( 'The PayPal Payouts addon has been automatically deactivated because all PayPal Payouts features are now included directly in AffiliateWP. You can safely delete the addon.', 'affiliate-wp' ); ?></p>
					<p><a href="<?php echo esc_url( $paypal_settings_url ); ?>" class="button button-primary"><?php _e( 'Configure PayPal Payouts', 'affiliate-wp' ); ?></a></p>
			</div>
					<?php
				}
			);
		}
	},
	1
);

// Handle PayPal Payouts addon plugin action links.
$addon_plugin = 'affiliate-wp-paypal-payouts/affiliate-wp-paypal-payouts.php';

// Modify plugin action links to show clear messaging
add_filter(
	'plugin_action_links_' . $addon_plugin,
	function ( $links ) {
		// Replace activate link with informative message and helpful link to PayPal Payouts settings.
		if ( isset( $links['activate'] ) ) {
			$paypal_settings_url = admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts#paypal' );
			$links['activate']   = sprintf(
				'<span style="color: #666;">%s</span>',
				__( 'Now included in AffiliateWP.', 'affiliate-wp' ),
				esc_url( $paypal_settings_url ),
			);
		}

		return $links;
	},
	10,
	1
);

// Add JavaScript to plugins page to style the PayPal Payouts row
add_action(
	'admin_footer',
	function () {
		$screen = get_current_screen();

		// Only run on plugins.php page
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		?>
		<script>
			(function() {
				// Add update class to the plugin row to remove border.
				var pluginRow = document.querySelector('tr[data-slug="affiliate-wp-paypal-payouts"]');
				if (!pluginRow) {
					// Try alternative selector.
					pluginRow = document.querySelector('tr[data-plugin="affiliate-wp-paypal-payouts/affiliate-wp-paypal-payouts.php"]');
				}
				if (pluginRow && pluginRow.classList.contains('inactive')) {
					pluginRow.classList.add('update');
				}
			})();
		</script>
		<?php
	}
);

// Add a prominent notice row under the plugin to make it clear it can be deleted
add_action(
	'after_plugin_row_' . $addon_plugin,
	function ( $plugin_file, $plugin_data, $status ) {
		$paypal_settings_url = admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts#paypal' );

		// Get the current WP_List_Table instance to determine column count
		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		$columns       = $wp_list_table->get_columns();
		$colspan       = count( $columns );
		?>
		<tr class="plugin-update-tr" id="affwp-paypal-payouts-deprecated-notice">
			<td colspan="<?php echo esc_attr( $colspan ); ?>" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-warning notice-alt">
					<p>
						<span class="dashicons dashicons-info" aria-hidden="true"></span>
						<?php _e( 'PayPal Payouts is now included directly in AffiliateWP. This plugin can be safely deleted.', 'affiliate-wp' ); ?>
						<a href="<?php echo esc_url( $paypal_settings_url ); ?>">
							<?php _e( 'Configure PayPal Payouts', 'affiliate-wp' ); ?>
						</a>
					</p>
				</div>
			</td>
		</tr>
		<style>
			#affwp-paypal-payouts-deprecated-notice .update-message {
				background-color: #fcf9e8;
				border-left: 4px solid #dba617;
			}
			#affwp-paypal-payouts-deprecated-notice .update-message p::before {
				content: none;
			}
			#affwp-paypal-payouts-deprecated-notice .dashicons-info {
				color: #d63638;
			}
		</style>
		<?php
	},
	10,
	3
);

// Include the main PayPal Payouts class.
require_once __DIR__ . '/class-paypal-payouts.php';

// Create the main PayPal Payouts function with a non-conflicting name
if ( ! function_exists( 'affwp_paypal_payouts' ) ) {
	/**
	 * Returns the PayPal Payouts instance.
	 *
	 * This function name is different from the addon's `affiliate_wp_paypal()`
	 * to avoid conflicts when both are present.
	 *
	 * @since 2.29.0
	 * @return object|null The active PayPal Payouts instance
	 */
	function affwp_paypal_payouts() {
		// If addon is active, use it for backward compatibility.
		if ( function_exists( 'affiliate_wp_paypal' ) && class_exists( 'AffiliateWP_PayPal_Payouts' ) ) {
			return affiliate_wp_paypal();
		}

		// Otherwise use core
		if ( class_exists( '\AffiliateWP\Core\Payouts\Methods\PayPalPayouts\AffiliateWP_PayPal_Payouts' ) ) {
			return \AffiliateWP\Core\Payouts\Methods\PayPalPayouts\AffiliateWP_PayPal_Payouts::instance();
		}

		return null;
	}
}

// Create backward compatible function if not already exists
if ( ! function_exists( 'affiliate_wp_paypal' ) ) {
	/**
	 * Backward compatible function for addon references.
	 *
	 * @since 2.29.0
	 * @return object|null The active PayPal Payouts instance
	 */
	function affiliate_wp_paypal() {
		return affwp_paypal_payouts();
	}
}

// Initialize the PayPal Payouts core module after AffiliateWP is loaded.
// Deferred to 'init' to avoid _load_textdomain_just_in_time warnings in WordPress 6.7+.
// The initialization calls affwp_get_payout_methods() which uses __() for translations.
add_action(
	'init',
	function () {

		// Only initialize if AffiliateWP is available
		if ( ! function_exists( 'affiliate_wp' ) ) {
			return;
		}

		// Fire action to indicate core PayPal Payouts is initializing
		do_action( 'affwp_paypal_payouts_core_init' );

		// Initialize PayPal Payouts
		if ( function_exists( 'affwp_paypal_payouts' ) ) {
			affwp_paypal_payouts();
		}
	},
	5
); // Run early in init after translations are loaded

// Hook for settings panel in Payouts tab
add_action(
	'affwp_payouts_tab_paypal_settings',
	function () {
		$instance = affwp_paypal_payouts();
		if ( $instance && method_exists( $instance, 'render_settings_panel' ) ) {
			$instance->render_settings_panel();
		}
	}
);
