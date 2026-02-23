<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Migration responsible for creating the give_p2p_sponsors table
 *
 * Class CreateSponsorsTable
 * @package GiveP2P\Campaigns\Migrations
 *
 * @since 1.0.0
 */
class CreateSponsorsTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'give-p2p-create-sponsors-table';
	}

	public static function title() {
		return 'Create give_p2p_sponsors table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2021-04-26 00:00:03' );
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$table   = $wpdb->give_p2p_sponsors;
		$charset = DB::get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id INT UNSIGNED NOT NULL,
			sponsor_name VARCHAR(64) NOT NULL,
			sponsor_url TEXT NOT NULL,
			sponsor_image TEXT NOT NULL,
			PRIMARY KEY  (id)
		) $charset";

		try {
			DB::delta( $sql );
		} catch ( DatabaseQueryException $exception ) {
			throw new DatabaseMigrationException( "An error occurred while creating the $table table", 0, $exception );
		}
	}
}
