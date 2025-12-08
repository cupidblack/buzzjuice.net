<?php
namespace GiveFunds\Infrastructure;


/**
 * Class License
 * @package GiveFunds\Infrastructure
 *
 * @since 1.0.0
 */
class License {

	/**
	 * Check add-on license.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check() {
		new \Give_License(
			GIVE_FUNDS_ADDON_FILE,
			GIVE_FUNDS_ADDON_NAME,
			GIVE_FUNDS_ADDON_VERSION,
			'GiveWP'
		);
	}
}
