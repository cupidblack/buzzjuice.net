<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Migration responsible for creating the give_p2p_teams table
 *
 * Class CreateTeamsTable
 * @package GiveP2P\P2P\Migrations
 *
 * @since 1.0.0
 */
class CreateTeamsTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'give-p2p-create-teams-table';
	}

	/**
	 * @inheritdoc
	 */
	public static function title() {
		return 'Create give_p2p_teams table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2021-04-26 00:00:06' );
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$table   = $wpdb->give_p2p_teams;
		$charset = DB::get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id INT NOT NULL,
			owner_id INT NOT NULL,
			name VARCHAR(128) NOT NULL,
			story TEXT NOT NULL,
			profile_image TEXT NOT NULL,
			goal INT UNSIGNED NOT NULL,
			access VARCHAR(12) NOT NULL,
			status VARCHAR(12) NOT NULL,
			date_created DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id)
		) $charset";

		try {
			DB::delta( $sql );
		} catch ( DatabaseQueryException $exception ) {
			throw new DatabaseMigrationException( "An error occurred while creating the $table table", 0, $exception );
		}
	}
}
