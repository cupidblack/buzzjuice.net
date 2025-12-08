<?php

namespace GiveP2P\Reallocation\Actions;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Models\Team;

/**
 * @since 1.3.0
 */
class ReallocateTeamFundraisersDonationsToCampaign
{
    /**
     * @since 1.3.0
     *
     * @param Team $team
     */
    public function __invoke( Team $team )
    {
        global $wpdb;

        DB::query("
            UPDATE {$wpdb->give_p2p_donation_source}
            SET
                source_type = 'campaign',
                source_id = '{$team->getCampaignId()}'
            WHERE
                  source_type = 'fundraiser'
              AND source_id IN (
                        SELECT id
                        FROM {$wpdb->give_p2p_fundraisers}
                        WHERE team_id = {$team->getId()}
                  )
        ");
    }
}
