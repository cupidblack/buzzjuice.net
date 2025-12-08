<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * @since 1.1.0
 */
class ModifyTeamDateCreatedColumn extends Migration {
    /**
     * @inheritdoc
     */
    public static function id() {
        return 'give-p2p-modify-team-date-created-column.php';
    }

    /**
     * @inheritdoc
     */
    public static function title() {
        return 'Add registration digest column';
    }

    /**
     * @inheritdoc
     */
    public static function timestamp() {
        return strtotime( '2022-02-17 13:10:00' );
    }

    /**
     * @inheritdoc
     */
    public function run() {
        global $wpdb;

        $table = $wpdb->give_p2p_teams;

        $sql = sprintf("
            ALTER TABLE %s
            MODIFY date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ", $table);

        try {
            $wpdb->query( $sql );
        } catch ( DatabaseQueryException $exception ) {
            throw new DatabaseMigrationException( "An error occurred while updating the $table table", 0, $exception );
        }
    }
}
