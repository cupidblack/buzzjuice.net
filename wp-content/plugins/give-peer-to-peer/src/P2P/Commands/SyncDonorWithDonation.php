<?php

namespace GiveP2P\P2P\Commands;

/**
 * @since 1.0.0
 */
class SyncDonorWithDonation {

    /**
     * @param  int     $meta_id
     * @param  int     $object_id
     * @param  string  $meta_key
     * @param  string  $_meta_value
     */
    public function __invoke( $meta_id, $object_id, $meta_key, $_meta_value ) {

        if ( '_give_payment_donor_id' === $meta_key ) {
            global $wpdb;
            $wpdb->update(
                $wpdb->give_p2p_donation_source,
                [ 'donor_id' => $_meta_value ],
                [ 'donation_id' => $object_id ]
            );
        }
    }
}
