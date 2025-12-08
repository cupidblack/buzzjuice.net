<?php

namespace GiveP2P\Reallocation;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Models\Team;

/**
 * @since 1.3.0
 */
trait UseFundraiserTable
{
    /**
     * @since 1.3.0
     *
     * @param Fundraiser $fundraiser
     */
    protected function deleteFundraiser( Fundraiser $fundraiser )
    {
        global $wpdb;
        DB::delete( $wpdb->give_p2p_fundraisers, [ 'id' => $fundraiser->getId() ], [ '%d' ] );
    }

    /**
     * @since 1.3.0
     *
     * @param Team $team
     */
    protected function deleteTeamFundraisers( Team $team )
    {
        global $wpdb;
        DB::delete( $wpdb->give_p2p_fundraisers, [ 'team_id' => $team->getId() ], [ '%d' ] );
    }

    /**
     * @since 1.3.0
     *
     * @param Fundraiser $fundraiser
     */
    protected function updateFundraiserCaptain( Fundraiser $fundraiser ) {
        global $wpdb;
        DB::update( $wpdb->give_p2p_fundraisers, [ 'team_captain' => 1 ], [ 'id' => $fundraiser->getId() ] );
    }
}
