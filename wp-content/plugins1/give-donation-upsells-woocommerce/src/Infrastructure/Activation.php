<?php

namespace GiveWooCommerceUpsells\Infrastructure;

/**
 * Example of a helper class responsible for registering and handling add-on activation hooks.
 *
 * @package     GiveAddon\Addon
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Activation {
	/**
	 * Activate add-on action hook.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	public static function activateAddon() {
		give( PluginUpgrade::class )->completeAllMigrationsOnFreshInstall();
	}

	/**
	 * Deactivate add-on action hook.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	public static function deactivateAddon() {
	}

	/**
	 * Uninstall add-on action hook.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	public static function uninstallAddon() {
	}
}
