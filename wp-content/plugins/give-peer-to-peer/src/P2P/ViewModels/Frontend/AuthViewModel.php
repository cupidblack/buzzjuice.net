<?php

namespace GiveP2P\P2P\ViewModels\Frontend;

use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Models\Campaign;

/**
 * Class AuthViewModel
 * @package GiveP2P\P2P\ViewModels\Frontend
 *
 * @since 1.0.0
 */
class AuthViewModel {

    protected $campaign;

    /**
     * @since 1.3.0 AuthViewModel now depends on a campaign to get the correct fundraiser
     * @param Campaign $campaign
     */
    public function __construct(Campaign $campaign ) {
        $this->campaign = $campaign;
    }

	/**
	 * @return array
	 */
	public function exports() {
		$user_id = get_current_user_id();
		return [
			'is_logged_in'  => is_user_logged_in(),
			'user_id'       => $user_id,
			'fundraiser_id' => Fundraiser::getFundraiserIdByUserIdAndCampaignId( $user_id, $this->campaign->getId() )
		];
	}
}
