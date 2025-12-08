<?php

namespace GiveWooCommerceUpsells\Infrastructure;

/**
 * Helper class responsible for checking the add-on environment.
 *
 * @package     GiveTextToGive\Infrastructure
 * @since 1.2.0
 */
class Environment {
	/**
	 * Check environment.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public static function checkEnvironment() {
		if ( ! static::isGiveActive() ) {
			add_action( 'admin_notices', [ Notices::class, 'giveInactive' ] );

			return false;
		}

		if ( ! static::isWoocommerceActive() ) {
			add_action( 'admin_notices', [ Notices::class, 'woocommerceInactive' ] );

			return false;
		}

		if ( ! static::giveMinRequiredVersionCheck() ) {
			add_action( 'admin_notices', [ Notices::class, 'giveVersionError' ] );

			return false;
		}

		return true;
	}

	/**
	 * Check min required version of GiveWP.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public static function giveMinRequiredVersionCheck() {
		return defined( 'GIVE_VERSION' ) &&
					version_compare(
						GIVE_VERSION,
						GIVE_WOOCOMMERCE_MIN_GIVE_VER,
						'>='
					);
	}

	/**
	 * Check if GiveWP is active.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public static function isGiveActive() {
		return defined( 'GIVE_VERSION' );
	}

	/**
	 * Check if GiveWP is active.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public static function isWoocommerceActive() {
		return class_exists( 'Woocommerce' );
	}
}
