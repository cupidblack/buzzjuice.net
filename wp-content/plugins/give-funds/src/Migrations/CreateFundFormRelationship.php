<?php

namespace GiveFunds\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Class CreateFundFormRelationship
 * @package GiveFunds\Migrations
 *
 * @since 1.0.0
 */
class CreateFundFormRelationship extends Migration {
	/**
	 * @inheritdoc
	 *
	 * @since 1.0.0
	 * @since 1.0.2 Use DB::delta to improve error reporting.
	 *
	 * @throws DatabaseQueryException
	 */
	public function run() {
		global $wpdb;

		DB::delta(
			"
				CREATE TABLE {$wpdb->give_fund_form_relationship} (
					fund_id bigint(20) UNSIGNED NOT NULL,
					form_id bigint(20) UNSIGNED NOT NULL,
					PRIMARY KEY  (form_id, fund_id)
				) {$wpdb->get_charset_collate()}
			"
		);
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'create_fund_form_relationship_table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2019-09-17 01:00:00' );
	}
}
