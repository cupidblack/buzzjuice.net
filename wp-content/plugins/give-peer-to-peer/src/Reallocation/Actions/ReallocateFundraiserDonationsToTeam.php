<?php

namespace GiveP2P\Reallocation\Actions;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\Reallocation\UseDonationSourceTable;

class ReallocateFundraiserDonationsToTeam
{
    use UseDonationSourceTable;

    /**
     * @since 1.3.0
     *
     * @param Fundraiser $fundraiser
     */
    public function __invoke( Fundraiser $fundraiser )
    {
        $data  = [ 'source_id' => $fundraiser->getTeamId(), 'source_type' => 'team' ];
        $where = [ 'source_id' => $fundraiser->getId(),     'source_type' => 'fundraiser' ];
        $this->updateDonationSource( $data, $where );
    }
}
