<?php

namespace GiveP2P\Reallocation\Actions;

use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\Reallocation\UseFundraiserTable;
use GiveP2P\Reallocation\UseTeamTable;

/**
 * @since 1.3.0
 */
class PromoteFundraiserToTeamOwner
{
    use UseTeamTable;
    use UseFundraiserTable;

    /**
     * @since 1.3.0
     *
     * @param Fundraiser $fundraiser
     */
    public function __invoke( Fundraiser $fundraiser )
    {
        $this->updateTeamOwner( $fundraiser );
        $this->updateFundraiserCaptain( $fundraiser );
    }
}
