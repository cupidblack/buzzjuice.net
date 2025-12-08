<?php

namespace GiveFunds\Migrations;

use Give\Framework\Migrations\Contracts\Migration;

/**
 * Class RemoveConstraints
 * @package GiveFunds\Migrations
 *
 * @since 1.0.2
 */
class RemoveConstraints extends Migration {
	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		$this->dropColumnForeignKeyConstraint( $wpdb->give_fund_form_relationship, 'fund_id' );
		$this->dropColumnForeignKeyConstraint( $wpdb->give_fund_form_relationship, 'form_id' );
		$this->dropColumnForeignKeyConstraint( $wpdb->give_revenue, 'fund_id' );
	}

	/**
	 * Drops the foreign key constraint for a given table and column
	 *
	 * @since 1.0.2
	 *
	 * @param string $table
	 * @param string $column
	 */
	private function dropColumnForeignKeyConstraint( $table, $column ) {
		global $wpdb;

		$constraintName = $wpdb->get_var(
			$wpdb->prepare(
				"
					SELECT constraints.CONSTRAINT_NAME
					FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS as constraints
					JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE as column_usage
						ON constraints.CONSTRAINT_NAME = column_usage.CONSTRAINT_NAME
					WHERE constraints.CONSTRAINT_TYPE = 'FOREIGN KEY'
						AND constraints.TABLE_NAME = %s
						AND column_usage.COLUMN_NAME = %s
				",
				$table,
				$column
			)
		);

		if ( ! empty( $constraintName ) ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}" );
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'give_funds_remove_constraints';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2020-12-16' );
	}
}
