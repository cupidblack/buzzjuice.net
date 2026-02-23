<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Migration responsible for creating the give_p2p_donation_source table
 *
 * Class CreateDonationSourceTable
 * @package GiveP2P\P2P\Migrations
 *
 * @since 1.0.0
 */
class CreateDonationSourceTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'give-p2p-create-donation-source-table';
	}

	public static function title() {
		return 'Create give_p2p_donation_source table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2021-04-26 00:00:02' );
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$table   = $wpdb->give_p2p_donation_source;
		$charset = DB::get_charset_collate();

		$sql = "CREATE TABLE $table (
			donation_id INT UNSIGNED NOT NULL,
			source_id INT UNSIGNED NOT NULL,
			source_type VARCHAR(128) NOT NULL,
			donor_id INT NOT NULL,
			anonymous TINYINT NULL,
			PRIMARY KEY  (donation_id),
			KEY source_id (source_id),
			KEY source_type (source_type)
		) $charset";

		try {
			DB::delta( $sql );
		} catch ( DatabaseQueryException $exception ) {
			throw new DatabaseMigrationException( "An error occurred while creating the $table table", 0, $exception );
		}
	}
}
