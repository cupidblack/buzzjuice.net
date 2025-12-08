<?php

namespace GiveP2P\Addon\Helpers;

/**
 * Helper class responsible for showing add-on notices.
 *
 * @package     GiveP2P\Addon\Helpers
 * @copyright   Copyright (c) 2021, GiveWP
 */
class Notices {

	/**
	 * Add a notice
	 *
	 * @param string $type
	 * @param string $description
	 */
	public static function add( $type, $description ) {
		Give()->notices->register_notice(
			[
				'id'          => 'give-p2p-notice-' . $type,
				'type'        => $type,
				'description' => $description,
				'show'        => true,
			]
		);
	}

	/**
	 * GiveWP min required version notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function giveVersionError() {
		Give()->notices->register_notice(
			[
				'id'          => 'give-p2p-activation-error',
				'type'        => 'error',
				'description' => View::load( 'admin/notices/give-version-error' ),
				'show'        => true,
			]
		);
	}

	/**
	 * GiveWP inactive notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function giveInactive() {
		echo View::load( 'admin/notices/give-inactive' );
	}
}
