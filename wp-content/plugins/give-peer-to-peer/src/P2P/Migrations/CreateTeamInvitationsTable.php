<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Migration responsible for creating the give_p2p_team_invitations table
 *
 * Class CreateTeamsTable
 * @package GiveP2P\P2P\Migrations
 *
 * @since 1.0.0
 */
class CreateTeamInvitationsTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'give-p2p-create-team-invitations-table';
	}

	/**
	 * @inheritdoc
	 */
	public static function title() {
		return 'Create give_p2p_team_invitations table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2021-06-22 14:11:00' );
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$table   = $wpdb->give_p2p_team_invitations;
		$charset = DB::get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			team_id INT NOT NULL,
			email VARCHAR(128) NOT NULL,
			date_sent DATETIME NULL,
			date_created DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY team_id (team_id)
		) $charset";

		try {
			DB::delta( $sql );
		} catch ( DatabaseQueryException $exception ) {
			throw new DatabaseMigrationException( "An error occurred while creating the $table table", 0, $exception );
		}
	}
}
