<?php

namespace GiveFunds\Infrastructure;

/**
 * Helper class responsible for showing add-on notices.
 *
 * @package     GiveFunds\Infrastructure
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Notices {

	/**
	 * Add notice
	 *
	 * @param string $type
	 * @param string $description
	 * @param bool $show
	 */
	public static function add( $type, $description, $show = true ) {
		Give()->notices->register_notice(
			[
				'id'          => sprintf( 'give-funds-notice-%s', $type ),
				'type'        => $type,
				'description' => $description,
				'show'        => $show,
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
		self::add( 'error', View::load( 'admin/notices/give-version-error' ) );
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

	/**
	 * GiveWP missing revenue database table notice.
	 *
	 * @since 1.0.0
	 */
	public static function revenueDatabaseTaleDoesNotExist() {
		self::add( 'error', View::load( 'admin/notices/revenue-database-table' ) );
	}
}
