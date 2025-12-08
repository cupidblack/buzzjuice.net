<?php

namespace GiveP2P\Reallocation\Actions;

use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\Reallocation\UseFundraiserTable;

class DeleteFundraiser
{
    use UseFundraiserTable;

    public function __invoke( Fundraiser $fundraiser )
    {
        $this->deleteFundraiser( $fundraiser );
    }
}
