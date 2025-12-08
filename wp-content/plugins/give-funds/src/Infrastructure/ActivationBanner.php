<?php

namespace GiveFunds\Infrastructure;

/**
 * Helper class responsible for showing add-on Activation Banner.
 *
 * @package     GiveFunds\Infrastructure
 * @copyright   Copyright (c) 2020, GiveWP
 */
class ActivationBanner {

	/**
	 * Show activation banner
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function show() {
		// Check for Activation banner class.
		if ( ! class_exists( 'Give_Addon_Activation_Banner' ) ) {
			include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
		}

		// Only runs on admin.
		$args = [
			'file'              => GIVE_FUNDS_ADDON_FILE,
			'name'              => GIVE_FUNDS_ADDON_NAME,
			'version'           => GIVE_FUNDS_ADDON_VERSION,
			'documentation_url' => 'http://docs.givewp.com/addon-funds',
			'support_url'       => 'https://givewp.com/support/',
			'testing'           => false, // Never leave true.
		];

		if ( class_exists( 'Give_Addon_Activation_Banner' ) ) {
			new \Give_Addon_Activation_Banner( $args );
		}
	}
}
