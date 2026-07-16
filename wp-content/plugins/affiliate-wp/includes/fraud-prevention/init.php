<?php
/**
 * Fraud Prevention Initialization
 *
 * Handles initialization of fraud prevention features and auto-deactivation of legacy addons.
 *
 * @package     AffiliateWP
 * @subpackage  Fraud Prevention
 * @copyright   Copyright (c) 2025, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.31.0
 */

namespace AffiliateWP\Fraud_Prevention;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auto-deactivate legacy fraud prevention addons.
 *
 * Since fraud prevention is now a core feature, we automatically deactivate:
 * 1. AffiliateWP - Fraud Prevention addon
 * 2. AffiliateWP - Allow Own Referrals addon
 *
 * This follows the same pattern used for PayPal Payouts addon migration.
 *
 * @since 2.31.0
 */
add_action(
	'admin_init',
	function () {
		$addons_to_deactivate = [
			'affiliatewp-fraud-prevention/affiliatewp-fraud-prevention.php' => [
				'name'         => __( 'Fraud Prevention', 'affiliate-wp' ),
				'settings_url' => admin_url( 'admin.php?page=affiliate-wp-settings&tab=fraud-prevention' ),
				'notice_title' => __( 'Fraud Prevention Addon Deactivated', 'affiliate-wp' ),
				'notice_text'  => __( 'The Fraud Prevention addon has been automatically deactivated because all fraud prevention features are now included directly in AffiliateWP. You can safely delete the addon.', 'affiliate-wp' ),
				'button_text'  => __( 'Configure Anti-Fraud Settings', 'affiliate-wp' ),
			],
			'affiliatewp-allow-own-referrals/affiliatewp-allow-own-referrals.php' => [
				'name'         => __( 'Allow Own Referrals', 'affiliate-wp' ),
				'settings_url' => admin_url( 'admin.php?page=affiliate-wp-settings&tab=fraud-prevention#fraud-prevention-self-referral' ),
				'notice_title' => __( 'Allow Own Referrals Addon Deactivated', 'affiliate-wp' ),
				'notice_text'  => __( 'The Allow Own Referrals addon has been automatically deactivated because self-referral settings are now included directly in AffiliateWP. You can safely delete the addon.', 'affiliate-wp' ),
				'button_text'  => __( 'Configure Self-Referral Settings', 'affiliate-wp' ),
			],
		];

		foreach ( $addons_to_deactivate as $addon_plugin => $addon_data ) {
			if ( is_plugin_active( $addon_plugin ) ) {
				deactivate_plugins( $addon_plugin );

				add_action(
					'admin_notices',
					function () use ( $addon_data ) {
						?>
						<div class="notice notice-success is-dismissible">
							<p><strong><?php echo esc_html( $addon_data['notice_title'] ); ?></strong></p>
							<p><?php echo esc_html( $addon_data['notice_text'] ); ?></p>
							<p>
								<a href="<?php echo esc_url( $addon_data['settings_url'] ); ?>" class="button button-primary">
									<?php echo esc_html( $addon_data['button_text'] ); ?>
								</a>
							</p>
						</div>
						<?php
					}
				);
			}
		}
	},
	1
);

/**
 * Include required fraud prevention files.
 *
 * @since 2.31.0
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/class-fraud-detector.php';
require_once __DIR__ . '/checks/class-self-referral-check.php';
require_once __DIR__ . '/checks/class-ppc-detection.php';
require_once __DIR__ . '/checks/class-referring-site-check.php';
require_once __DIR__ . '/checks/class-conversion-rate-check.php';
require_once __DIR__ . '/checks/class-ip-velocity-check.php';

/**
 * Initialize fraud prevention system.
 *
 * @since 2.31.0
 */
add_action(
	'affwp_plugins_loaded',
	function () {
		// Initialize the fraud detector.
		Fraud_Detector::instance();
	},
	10
);
