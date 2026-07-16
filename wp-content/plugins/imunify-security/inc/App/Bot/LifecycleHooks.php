<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Bot;

/**
 * Activation / deactivation entry points for the bot-protection mu-plugin.
 *
 * Two seams:
 *   - onActivate() / onDeactivate() — production entry points called
 *     from the activation and deactivation callbacks registered in
 *     imunify-security.php. They resolve WPMU_PLUGIN_DIR and home_url()
 *     at runtime and delegate to the *With() variants.
 *   - onActivateWith() / onDeactivateWith() — deterministic entry
 *     points for unit tests, taking the installer, loopback tester,
 *     and home URL as arguments.
 *
 * Activation policy: install → loopback. If loopback fails, uninstall
 * and return false so the caller can surface the failure to the admin.
 * Refusing to keep a shim that breaks the site is the core of the
 * fail-open promise.
 *
 * @since 4.0.0
 */
class LifecycleHooks {

	/**
	 * WP-Cron hook name for periodic storage cleanup.
	 */
	const CRON_HOOK_STORAGE_CLEANUP = 'imunify_security_bot_storage_cleanup';

	/**
	 * Custom WP-Cron recurrence: every 6 hours (4x/day).
	 */
	const CLEANUP_INTERVAL_SECONDS = 21600;

	/**
	 * Production activation entry point.
	 *
	 * @return bool Whether the shim is installed and passed the safety test.
	 */
	public static function onActivate() {
		$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) && '' !== WPMU_PLUGIN_DIR
			? (string) WPMU_PLUGIN_DIR
			: '';
		if ( '' === $mu_dir ) {
			return false;
		}
		// Phase 1 ships bundled data only — SignatureRefresher cron events
		// are deliberately never registered (see SignatureRefresher class
		// docblock). Defensive: if a previous
		// dev install temporarily wired the hooks, drop the schedule now
		// so the upstream-fetcher path stays cold. No-op when no event is
		// scheduled.
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( SignatureRefresher::CRON_HOOK_IP_RANGES );
			wp_clear_scheduled_hook( SignatureRefresher::CRON_HOOK_SIGNATURES );
		}
		self::scheduleStorageCleanup();
		$home_url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '';
		return self::onActivateWith(
			new MuPluginInstaller( $mu_dir ),
			new LoopbackSafetyTest(),
			$home_url
		);
	}

	/**
	 * Deterministic activation path used by tests.
	 *
	 * @param MuPluginInstaller  $installer Installer collaborator.
	 * @param LoopbackSafetyTest $loopback  Safety test collaborator.
	 * @param string             $home_url  Site home URL for the loopback probe.
	 * @return bool
	 */
	public static function onActivateWith( $installer, $loopback, $home_url ) {
		if ( ! $installer->install() ) {
			return false;
		}
		if ( ! $loopback->run( $home_url ) ) {
			$installer->uninstall();
			return false;
		}
		return true;
	}

	/**
	 * Production deactivation entry point.
	 *
	 * @return bool
	 */
	public static function onDeactivate() {
		self::unscheduleStorageCleanup();
		$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) && '' !== WPMU_PLUGIN_DIR
			? (string) WPMU_PLUGIN_DIR
			: '';
		if ( '' === $mu_dir ) {
			return true;
		}
		return self::onDeactivateWith( new MuPluginInstaller( $mu_dir ) );
	}

	/**
	 * Deterministic deactivation path used by tests.
	 *
	 * @param MuPluginInstaller $installer Installer collaborator.
	 * @return bool
	 */
	public static function onDeactivateWith( $installer ) {
		return $installer->uninstall();
	}

	/**
	 * Production uninstall entry point. Wired via register_uninstall_hook.
	 *
	 * Removes the mu-plugin shim, drops all bot-protection database
	 * tables, and clears the storage cleanup cron schedule.
	 *
	 * @return bool
	 */
	public static function onUninstall() {
		self::unscheduleStorageCleanup();
		self::dropBotTables();
		delete_option( MuPluginSelfHealer::OPTION_NAME );
		$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) && '' !== WPMU_PLUGIN_DIR
			? (string) WPMU_PLUGIN_DIR
			: '';
		if ( '' === $mu_dir ) {
			return true;
		}
		return self::onUninstallWith( new MuPluginInstaller( $mu_dir ) );
	}

	/**
	 * Deterministic uninstall path used by tests.
	 *
	 * @param MuPluginInstaller $installer Installer collaborator.
	 * @return bool
	 */
	public static function onUninstallWith( $installer ) {
		return $installer->uninstall();
	}

	// -------------------------------------------------------------------------
	// Storage cleanup cron
	// -------------------------------------------------------------------------

	/**
	 * Register the cron_schedules filter and the cleanup action hook.
	 *
	 * Called from the mu-plugin bootstrap (MuLoader) on every request
	 * so the custom interval and action handler are always available
	 * to WP-Cron, even on requests where the main plugin file is not
	 * loaded yet.
	 *
	 * @return void
	 */
	public static function registerCleanupHooks() {
		if ( ! function_exists( 'add_filter' ) ) {
			return;
		}
		add_filter( 'cron_schedules', array( __CLASS__, 'addCleanupSchedule' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_action( self::CRON_HOOK_STORAGE_CLEANUP, array( __CLASS__, 'runStorageCleanup' ) );
	}

	/**
	 * Add the 'imunify_six_hours' recurrence to WP-Cron schedules.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public static function addCleanupSchedule( $schedules ) {
		if ( ! isset( $schedules['imunify_six_hours'] ) ) {
			$schedules['imunify_six_hours'] = array(
				'interval' => self::CLEANUP_INTERVAL_SECONDS,
				'display'  => 'Every 6 hours',
			);
		}
		return $schedules;
	}

	/**
	 * WP-Cron callback: run storage cleanup in a fail-safe wrapper.
	 *
	 * @return void
	 */
	public static function runStorageCleanup() {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return;
		}
		try {
			$storage = DbStorageFactory::detect( $wpdb );
			$storage['block']->cleanup();
			$storage['counter']->cleanup();
		} catch ( \Exception $e ) {
			$transient_key = 'imunify_security_error_bot_cleanup_failed';
			if ( function_exists( 'get_transient' ) && ! get_transient( $transient_key ) ) {
				if ( function_exists( 'set_transient' ) ) {
					set_transient( $transient_key, true, 3600 );
				}
				do_action(
					'imunify_security_set_error',
					E_WARNING,
					'Bot storage cleanup failed: ' . $e->getMessage(),
					__FILE__,
					__LINE__,
					array(
						'fingerprint' => array( 'bot_storage_cleanup_failed', get_class( $e ) ),
					)
				);
			}
		}
	}

	/**
	 * Schedule the cleanup cron event if not already scheduled.
	 *
	 * @return void
	 */
	private static function scheduleStorageCleanup() {
		if ( ! function_exists( 'wp_next_scheduled' ) ) {
			return;
		}
		// Ensure the custom recurrence is registered before scheduling.
		// onActivate() runs from the main plugin hooks (not MuLoader),
		// so registerCleanupHooks() hasn't fired yet for this request.
		if ( function_exists( 'add_filter' ) ) {
			add_filter( 'cron_schedules', array( __CLASS__, 'addCleanupSchedule' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK_STORAGE_CLEANUP ) ) {
			wp_schedule_event( time(), 'imunify_six_hours', self::CRON_HOOK_STORAGE_CLEANUP );
		}
	}

	/**
	 * Remove the cleanup cron event.
	 *
	 * @return void
	 */
	private static function unscheduleStorageCleanup() {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( self::CRON_HOOK_STORAGE_CLEANUP );
		}
	}

	// -------------------------------------------------------------------------
	// Table cleanup on uninstall
	// -------------------------------------------------------------------------

	/**
	 * Drop all bot-protection database tables.
	 *
	 * Suppresses errors so a missing table or DB permission issue never
	 * prevents the rest of the uninstall from completing.
	 *
	 * @return void
	 */
	private static function dropBotTables() {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return;
		}
		$suppress = $wpdb->suppress_errors( true );
		$prefix   = $wpdb->prefix;
		$tables   = array(
			$prefix . 'imunify_bot_rl',
			$prefix . 'imunify_bot_blocks',
			$prefix . 'imunify_bot_blocks_active',
			$prefix . 'imunify_bot_violations',
		);
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		}
		$wpdb->suppress_errors( $suppress );
	}
}
