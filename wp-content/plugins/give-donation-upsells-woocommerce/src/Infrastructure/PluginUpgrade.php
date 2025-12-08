<?php

namespace GiveWooCommerceUpsells\Infrastructure;

use GiveWooCommerceUpsells\Revenue\Migrations\AddMissingDonationRevenue;

/**
 *  Class PluginUpgrade
 * @package GiveWooCommerceUpsells\Infrastructure
 * @since 1.2.0
 */
class PluginUpgrade {
	/**
	 * @since 1.2.0
	 */
	public function storePluginUpgradeVersion(){
		$pluginVersion = preg_replace( '/[^0-9.].*/', '', get_option( 'give_woocommerce_version' ) );

		// Is Fresh install?
		if ( ! $pluginVersion ) {
			$pluginVersion = '1.0.0';
		}

		if ( version_compare( $pluginVersion, GIVE_WOOCOMMERCE_VERSION, '<' ) ) {
			update_option(
				'give_woocommerce_version',
				preg_replace( '/[^0-9.].*/', '',
					GIVE_WOOCOMMERCE_VERSION
				)
			);

			update_option(
				'give_woocommerce_version_upgraded_from',
				$pluginVersion,
				false
			);
		}

		return $this;
	}

	/**
	 * @since 1.2.0
	 */
	public function completeAllMigrationsOnFreshInstall(){
		$pluginVersion = preg_replace( '/[^0-9.].*/', '', get_option( 'give_woocommerce_version' ) );

		// Is fresh install?
		if ( ! $pluginVersion ) {
			$updates = [
				AddMissingDonationRevenue::id()
			];

			foreach ( $updates as $update ) {
				give_set_upgrade_complete( $update );
			}
		}

		return $this;
	}
}
