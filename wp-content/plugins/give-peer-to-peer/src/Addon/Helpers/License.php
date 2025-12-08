<?php

namespace GiveP2P\Addon\Helpers;

class License {

	/**
	 * Check add-on license.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check() {
		new \Give_License(
			GIVE_P2P_FILE,
			GIVE_P2P_NAME,
			GIVE_P2P_VERSION,
			'GiveWP'
		);
	}
}
