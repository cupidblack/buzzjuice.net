<?php

namespace GiveP2P\Exports\Views;

use GiveP2P\Addon\Helpers\View;
use GiveP2P\Exports\FundraiserExport;
use GiveP2P\P2P\Facades\Campaign;

/**
 * @since 1.3.0
 */
class FundraiserExportView
{
    /**
     * @since 1.3.0
     */
    public function render()
    {
        echo View::load('Exports.fundraiser-export-table-row', [
            'campaignOptions' => $this->getCampaignOptions(),
            'exportClass' => 'Give_P2P_Fundraisers_Export', // Alias for GiveP2P\Exports\FundraiserExport
        ] );
    }

    /**
     * @since 1.3.0
     * @return string[] [ id => title, ... ]
     */
    protected function getCampaignOptions()
    {
        return array_reduce( Campaign::getCampaigns(), function( $options, $campaign ) {
            $options[ $campaign->getId() ] = $campaign->getTitle();
            return $options;
        }, [ 0 => '' ] );
    }
}
