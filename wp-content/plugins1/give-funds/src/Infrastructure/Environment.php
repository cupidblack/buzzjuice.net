<?php

namespace GiveFunds\Infrastructure;

use GiveFunds\Admin\OverviewFund;
use GiveFunds\Admin\ReportsPage;

/**
 * Helper class responsible for checking the add-on environment.
 *
 * @package     GiveFunds\Infrastructure
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Environment {
	/**
	 * Check environment.
	 *
	 * @since 1.0.0
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

		if ( ! self::revenueDatabaseTableExists() ) {
			add_action( 'admin_notices', [ Notices::class, 'revenueDatabaseTaleDoesNotExist' ] );
		}
	}

	/**
	 * Check min required version of GiveWP.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function giveMinRequiredVersionCheck() {
		return defined( 'GIVE_VERSION' ) && version_compare( GIVE_VERSION, GIVE_FUNDS_ADDON_MIN_GIVE_VERSION, '>=' );
	}

	/**
	 * Check if GiveWP is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function isGiveActive() {
		return defined( 'GIVE_VERSION' );
	}

	/**
	 * @since 1.0.0
	 * @return bool
	 */
	public static function isFundOverviewPage() {
		return ( isset( $_GET['page'] ) && OverviewFund::SLUG === $_GET['page'] );
	}

	/**
	 * @since 1.0.0
	 * @return bool
	 */
	public static function isFundsReportsPage() {
		return ( isset( $_GET['page'] ) && ReportsPage::SLUG === $_GET['page'] );
	}

	/**
	 * Return whether or not revenue database table exists.
	 *
	 * @since 1.0.2 rename for grammatical improvements
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function revenueDatabaseTableExists() {
		global $wpdb;

		return (bool) $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}give_revenue" ) );
	}

	/**
	 * Return whether or not funds database table exists.
	 *
	 * @since 1.0.2
	 * @return bool
	 */
	public static function fundsDatabaseTableExists() {
		global $wpdb;

		return (bool) $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}give_funds" ) );
	}
}
