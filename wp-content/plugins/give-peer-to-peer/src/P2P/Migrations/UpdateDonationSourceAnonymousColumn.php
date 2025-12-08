<?php

namespace GiveP2P\P2P\Migrations;

use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Migrations\Contracts\Migration;
use Give\Framework\Migrations\Exceptions\DatabaseMigrationException;

/**
 * @since 1.6.4
 */
class UpdateDonationSourceAnonymousColumn extends migration
{

    /**
     * @since 1.6.4
     */
    public static function id()
    {
        return 'give-p2p-update-donation-source-anonymous-column';
    }

    /**
     * @since 1.6.4
     */
    public static function title()
    {
        return 'Update Donation Source Anonymous Column';
    }

    /**
     * @since 1.6.4
     */
    public static function timestamp()
    {
        return strtotime('2023-06-20 00:00:00');
    }

    /**
     * @since 1.6.4
     *
     * @throws DatabaseMigrationException
     */
    public function run()
    {
        global $wpdb;

        $sql = sprintf("
            UPDATE %s AS p2p_donation_source
            JOIN %s AS donations ON donations.ID = p2p_donation_source.donation_id
            JOIN %s AS donation_meta ON donation_meta.donation_id = donations.ID
            SET p2p_donation_source.anonymous = 1
            WHERE donations.post_type = 'give_payment'
            AND donation_meta.meta_key = '_give_anonymous_donation'
            AND donation_meta.meta_value = 1
            AND p2p_donation_source.anonymous = 0
        ",
            $wpdb->give_p2p_donation_source,
            $wpdb->posts,
            $wpdb->donationmeta
        );

        try {
            $wpdb->query($sql);
        } catch (DatabaseQueryException $exception) {
            throw new DatabaseMigrationException("An error occurred while updating the $wpdb->give_p2p_donation_source table",
                0, $exception);
        }
    }
}
