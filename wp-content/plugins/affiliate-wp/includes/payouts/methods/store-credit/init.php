<?php
/**
 * Store Credit Module Initialization
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/StoreCredit
 * @since       2.29.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Early hooks to handle addon compatibility before anything else loads
// This runs as soon as this file is included, before any Store Credit code initializes

// Check if addon exists and handle deactivation
add_action(
	'admin_init',
	function () {
		$addon_plugin = 'affiliatewp-store-credit/affiliatewp-store-credit.php';

		// If addon is active, deactivate it and show notice
		if ( is_plugin_active( $addon_plugin ) ) {
			deactivate_plugins( $addon_plugin );

			// Add notice about automatic deactivation
			add_action(
				'admin_notices',
				function () {
					?>
			<div class="notice notice-success is-dismissible">
					<p><strong><?php _e( 'Store Credit Addon Deactivated', 'affiliate-wp' ); ?></strong></p>
					<p><?php _e( 'The Store Credit addon has been automatically deactivated because all Store Credit features are now included directly in AffiliateWP. You can safely delete the addon.', 'affiliate-wp' ); ?></p>
			</div>
					<?php
				}
			);
		}
	},
	1
);

// Handle Store Credit addon plugin action links
$addon_plugin = 'affiliatewp-store-credit/affiliatewp-store-credit.php';

// Modify plugin action links to show clear messaging
add_filter(
	'plugin_action_links_' . $addon_plugin,
	function ( $links ) {
		// Replace activate link with notice (addon shouldn't be activated)
		if ( isset( $links['activate'] ) ) {
			$links['activate'] = sprintf(
				'<span style="color: #666;">%s</span>',
				__( 'Now included in AffiliateWP.', 'affiliate-wp' )
			);
		}
		// Note: Deactivate link not shown since we auto-deactivate above
		return $links;
	},
	10,
	1
);

// Add JavaScript to plugins page to style the Store Credit row
add_action(
	'admin_footer',
	function () {
		$screen = get_current_screen();

		// Only run on plugins.php page.
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		?>
		<script>
			(function() {
				// Add update class to the plugin row to remove border.
				var pluginRow = document.querySelector('tr[data-slug="affiliatewp-store-credit"]');
				if (!pluginRow) {
					// Try alternative selector.
					pluginRow = document.querySelector('tr[data-plugin="affiliatewp-store-credit/affiliatewp-store-credit.php"]');
				}
				if (pluginRow && pluginRow.classList.contains('inactive')) {
					pluginRow.classList.add('update');
				}
			})();
		</script>
		<?php
	}
);

// Add a prominent notice row under the plugin to make it clear it can be deleted.
add_action(
	'after_plugin_row_' . $addon_plugin,
	function ( $plugin_file, $plugin_data, $status ) {
		$store_credit_settings_url = admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts#store-credit' );

		// Get the current WP_List_Table instance to determine column count.
		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		$columns       = $wp_list_table->get_columns();
		$colspan       = count( $columns );
		?>
		<tr class="plugin-update-tr" id="affwp-store-credit-deprecated-notice">
			<td colspan="<?php echo esc_attr( $colspan ); ?>" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-warning notice-alt">
					<p>
						<span class="dashicons dashicons-info" aria-hidden="true"></span>
						<?php _e( 'Store Credit is now included directly in AffiliateWP. This plugin can be safely deleted.', 'affiliate-wp' ); ?>
						<a href="<?php echo esc_url( $store_credit_settings_url ); ?>">
							<?php _e( 'Configure Store Credit', 'affiliate-wp' ); ?>
						</a>
					</p>
				</div>
			</td>
		</tr>
		<style>
			#affwp-store-credit-deprecated-notice .update-message {
				background-color: #fcf9e8;
				border-left: 4px solid #dba617;
			}
			#affwp-store-credit-deprecated-notice .update-message p::before {
				content: none;
			}
			#affwp-store-credit-deprecated-notice .dashicons-info {
				color: #d63638;
			}
		</style>
		<?php
	},
	10,
	3
);


// Allow addons to prevent core Store Credit from loading
if ( apply_filters( 'affwp_disable_core_store_credit', false ) ) {
	return;
}

// Always load core Store Credit for settings panel functionality
// Even if addon is active, we need core for the Payouts tab settings

// Include the main Store Credit class
require_once __DIR__ . '/class-store-credit.php';

// Create the main Store Credit function with a non-conflicting name
if ( ! function_exists( 'affwp_store_credit' ) ) {
	/**
	 * Returns the Store Credit instance.
	 *
	 * This function name is different from the addon's `affiliatewp_store_credit()`
	 * to avoid conflicts when both are present.
	 *
	 * @since 2.29.0
	 * @return object|null The active Store Credit instance
	 */
	function affwp_store_credit() {
		// If addon is active, use it for backward compatibility
		if ( function_exists( 'affiliatewp_store_credit' ) && class_exists( '\AffiliateWP_Store_Credit' ) ) {
			return affiliatewp_store_credit();
		}

		// Otherwise use core
		if ( class_exists( '\AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit' ) ) {
			return \AffiliateWP\Core\Payouts\Methods\StoreCredit\AffiliateWP_Store_Credit::instance();
		}

		return null;
	}
}


// Initialize the Store Credit core module after AffiliateWP is loaded.
add_action(
	'plugins_loaded',
	function () {

		// Only initialize if AffiliateWP is available
		if ( ! function_exists( 'affiliate_wp' ) ) {
			return;
		}

		// Fire action to indicate core Store Credit is initializing
		do_action( 'affwp_store_credit_core_init' );

		// Initialize Store Credit
		if ( function_exists( 'affwp_store_credit' ) ) {
			affwp_store_credit();
		}
	},
	20
); // Run after AffiliateWP is loaded (priority 10)
