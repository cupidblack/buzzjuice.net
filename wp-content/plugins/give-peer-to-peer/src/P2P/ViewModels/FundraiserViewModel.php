<?php

namespace GiveP2P\P2P\ViewModels;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use WP_User;

/**
 * Class FundraiserViewModel
 * @package GiveP2P\P2P\ViewModels
 *
 * @since 1.0.0
 */
class FundraiserViewModel {
	/**
	 * @var Fundraiser
	 */
	private $fundraiser;

	/**
	 * @var FundraiserRepository
	 */
	private $fundraiserRepository;

	/**
	 * @var WP_User
	 */
	private $user;

	/**
	 * FundraiserViewModel constructor.
	 *
	 * @param  Fundraiser  $fundraiser
	 * @param  FundraiserRepository  $fundraiserRepository
	 * @param  WP_User  $user
	 */
	public function __construct(
		Fundraiser $fundraiser,
		FundraiserRepository $fundraiserRepository,
		WP_User $user
	) {
		$this->fundraiser           = $fundraiser;
		$this->fundraiserRepository = $fundraiserRepository;
		$this->user                 = $user;
	}

	/**
	 * @return array
	 */
	public function exports() {
		return [
			'id'                            => $this->fundraiser->get( 'id' ),
			'user_id'                       => $this->fundraiser->get( 'user_id' ),
			'fundraiser_name'               => $this->user->display_name,
			'avatar'                        => get_avatar_url( $this->fundraiser->get( 'user_id' ) ),
			'fundraiser_goal'               => $this->fundraiser->get( 'goal' )
                                            ? Money::ofMinor( $this->fundraiser->get( 'goal' ), give_get_option( 'currency' ) )->getAmount()
                                            : 0,
			'team_id'                       => $this->fundraiser->get( 'team_id' ),
			'team'                          => $this->fundraiser->get( 'team_name' ),
			'is_captain'                    => $this->fundraiser->isTeamCaptain(),
			'date_created'                  => $this->fundraiser->get( 'date_created' ),
			'status'                        => $this->fundraiser->hasApprovalStatus(),
			'amount_raised'                 => $this->fundraiserRepository->getRaisedAmount( $this->fundraiser->get( 'id' ) ),
			'story'                         => wpautop( $this->fundraiser->get( 'story' ) ),
			'profile_image'                 => $this->fundraiser->get( 'profile_image' ),
            'notify_of_donations'           => $this->fundraiser->isNotifiedOfDonations() ,
        ];
	}

}
