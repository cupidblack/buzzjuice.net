<?php

namespace GiveFunds\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Class CreateFundsTable
 * @package GiveFunds\Migrations
 *
 * @since 1.0.0
 */
class CreateFundsTable extends Migration {
	/**
	 * @inheritdoc
	 *
	 * @sice 1.0.0
	 * @since 1.0.2 Use DB::delta to improve error reporting.
	 * @throws DatabaseQueryException
	 */
	public function run() {
		global $wpdb;

		DB::delta(
			"
				CREATE TABLE {$wpdb->give_funds} (
					id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					title varchar(255) NOT NULL,
					description text NOT NULL,
					is_default tinyint(1) NOT NULL,
					author_id bigint(20) UNSIGNED NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					PRIMARY KEY  (id)
				) {$wpdb->get_charset_collate()}
			"
		);
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'create_funds_table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2019-09-17' );
	}
}
