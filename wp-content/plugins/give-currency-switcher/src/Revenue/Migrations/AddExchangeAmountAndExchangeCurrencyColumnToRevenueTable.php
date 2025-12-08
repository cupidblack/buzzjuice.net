<?php
namespace GiveCurrencySwitcher\Revenue\Migrations;

use Give\Framework\Migrations\Contracts\Migration;

/**
 * Class CreateFundsTable
 * @package GiveFunds\Migrations
 *
 * @since 1.3.12
 */
class AddExchangeAmountAndExchangeCurrencyColumnToRevenueTable extends Migration {
	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$addExchangeAmountColumnSql   = "ALTER TABLE {$wpdb->give_revenue} ADD COLUMN exchange_amount INT UNSIGNED DEFAULT NULL;"; // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$addExchangeCurrencyColumnSql = "ALTER TABLE {$wpdb->give_revenue} ADD COLUMN exchange_currency CHAR(3) DEFAULT NULL;"; // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

		$wpdb->query( $addExchangeAmountColumnSql );
		$wpdb->query( $addExchangeCurrencyColumnSql );
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'add_exchange_amount_and_exchange_currency_column_to_revenue_table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2019-10-16' );
	}
}
