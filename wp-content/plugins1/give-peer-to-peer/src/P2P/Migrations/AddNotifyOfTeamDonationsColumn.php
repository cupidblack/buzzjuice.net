<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

class AddNotifyOfTeamDonationsColumn extends Migration
{
    /**
     * @inheritdoc
     */
    public static function id() {
        return 'give-p2p-add-notify-of-team-donations-column';
    }

    /**
     * @inheritdoc
     */
    public static function title() {
        return 'Add Notify Of Fundraisers Column';
    }

    /**
     * @inheritdoc
     */
    public static function timestamp() {
        return strtotime( '2022-09-19 13:55:00' );
    }

    /**
     * @inheritdoc
     */
    public function run() {
        global $wpdb;

        $table = $wpdb->give_p2p_teams;

        $sql = "
            ALTER TABLE $table
            ADD notify_of_team_donations TINYINT(1) NOT NULL DEFAULT '0'
        ";

        try {
            $wpdb->query( $sql );
        } catch ( DatabaseQueryException $exception ) {
            throw new DatabaseMigrationException(__("An error occurred while updating the %s table", $table,
                'give-peer-to-peer'), 0, $exception);
        }
    }
}
