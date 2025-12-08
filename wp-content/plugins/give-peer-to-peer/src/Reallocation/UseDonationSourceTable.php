<?php

namespace GiveP2P\Reallocation;

use Give\Framework\Database\DB;

/**
 * @since 1.3.0
 */
trait UseDonationSourceTable
{
    /**
     * @since 1.3.0
     *
     * @param $newSource
     * @param $oldSource
     */
    protected function updateDonationSource( $newSource, $oldSource )
    {
        global $wpdb;
        DB::update( $wpdb->give_p2p_donation_source, $newSource, $oldSource );
    }
}
