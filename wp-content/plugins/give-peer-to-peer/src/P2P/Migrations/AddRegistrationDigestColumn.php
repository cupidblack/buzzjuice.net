<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\DB;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * @since 1.1.0
 */
class AddRegistrationDigestColumn extends Migration {
    /**
     * @inheritdoc
     */
    public static function id() {
        return 'give-p2p-add-registration-digest-column';
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
        return strtotime( '2021-10-05 10:45:00' );
    }

    /**
     * @inheritdoc
     */
    public function run() {
        global $wpdb;

        $table = $wpdb->give_p2p_campaigns;

        $sql = "
            ALTER TABLE $table
            ADD registration_digest VARCHAR(12) NOT NULL DEFAULT 'disabled'
        ";

        try {
            $wpdb->query( $sql );
        } catch ( DatabaseQueryException $exception ) {
            throw new DatabaseMigrationException( "An error occurred while updating the $table table", 0, $exception );
        }
    }
}
