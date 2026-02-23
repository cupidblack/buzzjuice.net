<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * Class CreateCampaignsTable
 * @package GiveP2P\P2P\Migrations
 *
 * @note give_p2p_campaigns table is used to store P2P campaign data and it has one-to-one relationship with give_campaigns table
 *
 * @since 1.0.0
 */
class CreateCampaignsTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'give-p2p-create-campaigns-table';
	}

	public static function title() {
		return 'Create give_p2p_campaigns table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2021-04-26 00:00:01' );
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$table   = $wpdb->give_p2p_campaigns;
		$charset = DB::get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id INT UNSIGNED NOT NULL,
			sponsors_enabled VARCHAR(12) NOT NULL,
			sponsor_linking VARCHAR(12) NOT NULL,
			sponsor_section_heading TEXT NOT NULL,
			sponsor_application_page TEXT NOT NULL,
			sponsors_display VARCHAR(12) NOT NULL,
			fundraiser_approvals VARCHAR(12) NOT NULL,
			fundraiser_approvals_email_subject TEXT NOT NULL,
			fundraiser_approvals_email_body TEXT NOT NULL,
			fundraiser_goal INT UNSIGNED NOT NULL,
			fundraiser_story_placeholder TEXT NOT NULL,
			team_approvals VARCHAR(12) NOT NULL,
			team_approvals_email_subject TEXT NOT NULL,
			team_approvals_email_body TEXT NOT NULL,
			team_goal INT UNSIGNED NOT NULL,
			team_story_placeholder TEXT NOT NULL,
			team_rankings VARCHAR(12) NOT NULL,
			PRIMARY KEY  (id)
		) $charset";

		try {
			DB::delta( $sql );
		} catch ( DatabaseQueryException $exception ) {
			throw new DatabaseMigrationException( "An error occurred while creating the $table table", 0, $exception );
		}
	}
}
