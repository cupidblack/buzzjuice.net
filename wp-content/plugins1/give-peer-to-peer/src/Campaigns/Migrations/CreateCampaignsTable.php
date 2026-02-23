<?php

namespace GiveP2P\Campaigns\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Migration responsible for creating the give_campaigns table
 *
 * Class CreateCampaignsTable
 * @package GiveP2P\Campaigns\Migrations
 *
 * @since 1.0.0
 */
class CreateCampaignsTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'give-campaigns-create-campaigns-table';
	}

	public static function title() {
		return 'Create give_campaigns table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2021-04-26 00:00:00' );
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$table   = $wpdb->give_campaigns;
		$charset = DB::get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id INT NOT NULL,
			campaign_title TEXT NOT NULL,
			campaign_url TEXT NOT NULL,
			short_desc TEXT NOT NULL,
			long_desc TEXT NOT NULL,
			campaign_logo TEXT NOT NULL,
			campaign_image TEXT NOT NULL,
			primary_color VARCHAR(7) NOT NULL,
			secondary_color VARCHAR(7) NOT NULL,
			campaign_goal INT UNSIGNED NOT NULL,
			status VARCHAR(12) NOT NULL,
			start_date DATETIME NULL,
			end_date DATETIME NULL,
			date_created DATETIME NOT NULL,
			PRIMARY KEY  (id)
		) $charset";

		try {
			DB::delta( $sql );
		} catch ( DatabaseQueryException $exception ) {
			throw new DatabaseMigrationException( "An error occurred while creating the $table table", 0, $exception );
		}
	}
}
