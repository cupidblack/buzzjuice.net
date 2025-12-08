<?php

namespace GiveP2P\Reallocation\Actions;

use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Models\Team;
use GiveP2P\Reallocation\UseDonationSourceTable;

/**
 * @since 1.3.0
 */
class ReallocateTeamDonationsToCampaign {

    use UseDonationSourceTable;

    /**
     * @since 1.3.0
     *
     * @param Fundraiser $fundraiser
     */
    public function __invoke( Team $team )
    {
        $data  = [ 'source_id' => $team->getCampaignId(), 'source_type' => 'campaign' ];
        $where = [ 'source_id' => $team->getId(),         'source_type' => 'team' ];
        $this->updateDonationSource( $data, $where );
    }
}
