<?php

namespace GiveP2P\Reallocation\Actions;

use GiveP2P\P2P\Models\Team;
use GiveP2P\Reallocation\UseTeamTable;

class DeleteTeam
{
    use UseTeamTable;

    public function __invoke( Team $team )
    {
        $this->deleteTeam( $team );
    }
}
