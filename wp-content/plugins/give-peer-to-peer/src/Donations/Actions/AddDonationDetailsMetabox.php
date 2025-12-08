<?php

namespace GiveP2P\Donations\Actions;

use GiveP2P\Addon\Helpers\View;
use GiveP2P\P2P\Exceptions\DonationSourceNotFound;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Repositories\DonationSourceRepository;

/**
 * @since 1.0.0
 */
class AddDonationDetailsMetabox
{

    /**
     * @since 1.0.0
     *
     * @param int $donationID
     */
    public function __invoke($donationID)
    {
        try {
            $source = give(DonationSourceRepository::class)->getSourceRow($donationID);

            switch ($source->source_type) {
                case 'campaign':
                    $campaignID = $source->source_id;
                    $teamID = null;
                    $fundraiserID = null;
                    break;
                case 'team':
                    $campaignID = Team::getTeam($source->source_id)->get('campaign_id');
                    $teamID = $source->source_id;
                    $fundraiserID = null;
                    break;
                case 'fundraiser':
                    $campaignID = Fundraiser::getFundraiser($source->source_id)->get('campaign_id');
                    $teamID = Fundraiser::getFundraiser($source->source_id)->get('team_id');
                    $fundraiserID = $source->source_id;
                    break;
            }
        } catch (DonationSourceNotFound $e) {
            $campaignID = null;
            $teamID = null;
            $fundraiserID = null;
        }

        echo View::load('Donations.donation-details', [
            'campaignID' => $campaignID,
            'teamID' => $teamID,
            'fundraiserID' => $fundraiserID,
            'campaigns' => Campaign::getActiveCampaigns(),
            'teams' => Team::getCampaignTeams($campaignID),
            'fundraisers' => ($teamID) ? Fundraiser::getTeamFundraisers($teamID) : Fundraiser::getCampaignFundraisers(
                $campaignID
            ),
        ]);
    }
}
