<?php

namespace GiveP2P\Reallocation;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\Models\Fundraiser;

/**
 * @since 1.3.0
 */
trait UseTeamTable
{
    /**
     * @since 1.3.0
     *
     * @param Team $team
     */
    protected function deleteTeam( Team $team )
    {
        global $wpdb;
        DB::delete( $wpdb->give_p2p_teams, [ 'id' => $team->getId() ], [ '%d' ] );
    }

    /**
     * @since 1.3.0
     *
     * @param Fundraiser $fundraiser
     */
    protected function updateTeamOwner( Fundraiser $fundraiser )
    {
        global $wpdb;
        DB::update( $wpdb->give_p2p_teams, [ 'owner_id' => $fundraiser->getId() ], [ 'id' => $fundraiser->getTeamId() ] );
    }
}
