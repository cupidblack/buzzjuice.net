<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * This class is a migration which adds "teams_registration" column to "give_p2p_campaigns" table.
 * @since 1.4.0
 */
class AddTeamsRegistrationColumn extends Migration
{
    /**
     * @inheritdoc
     */
    public static function id() {
        return 'give-p2p-add-teams-registration-column';
    }

    /**
     * @inheritdoc
     */
    public static function title() {
        return 'Add teams registration column to campaign table';
    }

    /**
     * @inheritdoc
     */
    public static function timestamp() {
        return strtotime( '2021-07-20 11:41:00' );
    }

    /**
     * @inheritdoc
     * @throws DatabaseMigrationException
     */
    public function run() {
        global $wpdb;

        $table = $wpdb->give_p2p_campaigns;

        $sql = "
            ALTER TABLE $table
            ADD teams_registration VARCHAR(12) NOT NULL DEFAULT 'enabled'
        ";

        try {
            $wpdb->query( $sql );
        } catch ( DatabaseQueryException $exception ) {
            throw new DatabaseMigrationException( "An error occurred while updating the $table table", 0, $exception );
        }
    }
}
