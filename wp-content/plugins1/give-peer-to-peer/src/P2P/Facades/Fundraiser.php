<?php

namespace GiveP2P\P2P\Facades;

use Give\Framework\Support\Facades\Facade;
use GiveP2P\P2P\Models\Fundraiser as Model;
use GiveP2P\P2P\Repositories\FundraiserRepository;

/**
 * @since 1.0.0
 *
 * @method static Model getFundraiser( $fundraiserID )
 * @method static Model[] getTeamFundraisers( $teamID )
 * @method static Model getFundraiserIdByUserIdAndCampaignId( $userID, $campaignId )
 * @method static Model getRecentlyRegisteredFundraisers()
 * @method static int getRaisedAmount( $fundraiserId )
 * @method static array getCampaignFundraisersSearch( $campaignId, $searchString, $limit, $offset = 0 )
 * @method static array getCampaignFundraiserSearchCount( $campaign_id, $searchString )
 */
class Fundraiser extends Facade {
    protected function getFacadeAccessor() {
		return FundraiserRepository::class;
	}
}
