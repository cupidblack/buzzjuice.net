<?php

namespace GiveP2P\Reallocation\Actions;

use GiveP2P\P2P\Models\Team;
use GiveP2P\Reallocation\UseFundraiserTable;

class DeleteTeamFundraisers
{
    use UseFundraiserTable;

    public function __invoke( Team $team )
    {
        $this->deleteTeamFundraisers( $team );
    }
}
