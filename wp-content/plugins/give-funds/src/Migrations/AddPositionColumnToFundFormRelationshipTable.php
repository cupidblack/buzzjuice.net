<?php

namespace GiveFunds\Migrations;

use Give\Framework\Migrations\Contracts\Migration;

/**
 * Add position column to give_fund_form_relationship table migration
 *
 * @since 1.1.0
 */
class AddPositionColumnToFundFormRelationshipTable extends Migration {

    /**
     * @inheritdoc
     */
    public static function id() {
        return 'funds_add_position_column_to_fund_form_relationship_table';
    }

    /**
     * @inheritdoc
     */
    public static function timestamp() {
        return strtotime( '2022-02-14' );
    }

    /**
     * @inheritdoc
     */
    public static function title() {
        return __('Add position column to give_fund_form_relationship table', 'give-funds');
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        global $wpdb;

        $wpdb->query( "ALTER TABLE {$wpdb->give_fund_form_relationship} ADD COLUMN position tinyint(2) DEFAULT 0;" );
    }
}
