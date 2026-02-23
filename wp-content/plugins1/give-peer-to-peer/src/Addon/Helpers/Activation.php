<?php

namespace GiveP2P\Addon\Helpers;

use GiveP2P\Routing\RouteProxy;
use GiveP2P\Routing\ServiceProvider;

/**
 * Example of a helper class responsible for registering and handling add-on activation hooks.
 *
 * @package     GiveP2P\Addon
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Activation {
	/**
	 * Activate add-on action hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activateAddon() {

		// Activation requires that GiveWP is active and that the container is available.
		if( ! function_exists( 'give' ) ) return;

		// Manually register Routing Service Provider to setup Dependency Injection.
		give( ServiceProvider::class )->register();

		// Manually register routes.
		include GIVE_P2P_DIR . 'src/P2P/config/routes.php';

		// Manually register rewrite tags and rules.
		give( RouteProxy::class )->registerRewriteTags();
		give( RouteProxy::class )->registerRewriteRules();

		flush_rewrite_rules();
	}

	/**
	 * Deactivate add-on action hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivateAddon() {

	}

	/**
	 * Uninstall add-on action hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function uninstallAddon() {

	}
}
