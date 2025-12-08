<?php
namespace GiveFunds\Migrations;

use Give\Framework\Migrations\Contracts\Migration;

/**
 * Class CreateFundsTable
 * @package GiveFunds\Migrations
 *
 * @since 1.0.0
 */
class AddFundIdColumnToRevenueTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "ALTER TABLE {$wpdb->give_revenue} ADD COLUMN fund_id bigint(20) UNSIGNED DEFAULT NULL;" );
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'add_fund_id_column_to_revenue_table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2019-09-22' );
	}
}
