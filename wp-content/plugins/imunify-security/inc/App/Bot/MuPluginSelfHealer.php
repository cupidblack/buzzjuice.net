<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Bot;

use CloudLinux\Imunify\App\Model\PluginConfig;

/**
 * Self-healing check that deploys the mu-plugin on upgraded sites.
 *
 * Sites that upgrade from a pre-4.0 plugin version to 4.0+ receive the
 * new code but never trigger register_activation_hook, so the mu-plugin
 * is never written. This class runs on every request via plugins_loaded
 * and conditionally installs the mu-plugin when the feature gate is on
 * but the file is missing.
 *
 * Install-only — disabling the feature does not trigger removal at
 * runtime. Removal is handled by the existing deactivation and
 * uninstall hooks in LifecycleHooks.
 *
 * @since 4.0.1
 */
class MuPluginSelfHealer {

	/**
	 * Autoloaded option that stores the timestamp of the last check.
	 */
	const OPTION_NAME = 'imunify_bots_mu_checked';

	/**
	 * Minimum seconds between filesystem checks.
	 */
	const TTL_SECONDS = 3600;

	/**
	 * Production entry point — wired to plugins_loaded.
	 *
	 * Reads the autoloaded TTL option (zero overhead — already in
	 * memory from wp_load_alloptions). When the TTL is fresh, returns
	 * immediately. Otherwise delegates to checkWith() and refreshes
	 * the timestamp.
	 *
	 * @since 4.0.1
	 * @return void
	 */
	public static function check() {
		$last_check = get_option( self::OPTION_NAME );
		$now        = time();
		if ( false !== $last_check && ( $now - (int) $last_check ) < self::TTL_SECONDS ) {
			return;
		}

		$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) && '' !== WPMU_PLUGIN_DIR
			? (string) WPMU_PLUGIN_DIR
			: '';
		if ( '' === $mu_dir ) {
			return;
		}
		$wp_content_dir = defined( 'WP_CONTENT_DIR' ) ? (string) WP_CONTENT_DIR : '';
		if ( '' === $wp_content_dir ) {
			return;
		}

		$cfg = MuLoader::loadPluginConfig( $wp_content_dir );
		self::checkWith( new MuPluginInstaller( $mu_dir ), $cfg );
		// Refresh TTL even when install() fails to avoid hammering a broken filesystem every request.
		update_option( self::OPTION_NAME, $now, true );
	}

	/**
	 * Deterministic entry point for unit tests.
	 *
	 * @since 4.0.1
	 * @param MuPluginInstaller $installer Installer collaborator.
	 * @param PluginConfig      $cfg       Decoded plugin_config.php.
	 * @return void
	 */
	public static function checkWith( $installer, PluginConfig $cfg ) {
		if ( $installer->isInstalled() ) {
			return;
		}
		if ( ! $cfg->isAiBotProtectionEnabled() ) {
			return;
		}
		$installer->install();
	}

}
