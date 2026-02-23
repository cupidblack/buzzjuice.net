<?php

namespace GiveP2P\P2P\Facades;

use Give\Framework\Support\Facades\Facade;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Models\Campaign as CampaignModel;

/**
 * @since 1.0.0
 * @method static CampaignModel getCampaign( $campaignID )
 * @method static CampaignModel[] getCampaigns()
 * @method static CampaignModel[] getActiveCampaigns()
 */
class Campaign extends Facade {
	protected function getFacadeAccessor() {
		return CampaignRepository::class;
	}
}
