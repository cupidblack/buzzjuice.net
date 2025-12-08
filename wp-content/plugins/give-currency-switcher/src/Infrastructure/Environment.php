<?php
namespace GiveCurrencySwitcher\Infrastructure;

/**
 * Helper class responsible for checking the add-on environment.
 *
 * @package     GiveCurrencySwitcher\Infrastructure
 * @copyright   Copyright (c) 2020, GiveWP
 *
 * @since 1.3.12
 */
class Environment {

	/**
	 * Check environment.
	 *
	 * @since 1.3.12
	 * @return void
	 */
	public static function checkEnvironment() {
		// Check is GiveWP active
		if ( ! static::isGiveActive() ) {
			add_action( 'admin_notices', [ Notices::class, 'giveInactive' ] );
			return;
		}
		// Check min required version
		if ( ! static::giveMinRequiredVersionCheck() ) {
			add_action( 'admin_notices', [ Notices::class, 'giveVersionError' ] );
		}
	}

	/**
	 * Check min required version of GiveWP.
	 *
	 * @since 1.3.12
	 * @return bool
	 */
	public static function giveMinRequiredVersionCheck() {
		return defined( 'GIVE_VERSION' ) && version_compare( GIVE_VERSION, GIVE_CURRENCY_SWITCHER_MIN_GIVE_VER, '>=' );
	}

	/**
	 * Check if GiveWP is active.
	 *
	 * @since 1.3.12
	 * @return bool
	 */
	public static function isGiveActive() {
		return defined( 'GIVE_VERSION' );
	}
}
