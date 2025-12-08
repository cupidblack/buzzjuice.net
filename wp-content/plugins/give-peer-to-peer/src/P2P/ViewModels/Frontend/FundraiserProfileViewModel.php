<?php

namespace GiveP2P\P2P\ViewModels\Frontend;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use WP_User;

/**
 * Class FundraiserProfileViewModel
 * @package GiveP2P\P2P\ViewModels
 *
 * @since 1.0.0
 */
class FundraiserProfileViewModel {
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
     * @since 1.3.0 Added is_approved export value.
     *
	 * @return array
	 */
	public function exports() {
		$raisedAmount = $this->fundraiserRepository->getRaisedAmount( $this->fundraiser->get( 'id' ) );

		return [
			'id'              => $this->fundraiser->get( 'id' ),
			'fundraiser_name' => $this->user->display_name,
			'avatar'          => get_avatar_url( $this->fundraiser->get( 'user_id' ) ),
			'amount_raised'   => Money::ofMinor( $raisedAmount, give_get_option( 'currency' ) )->getAmount(),
			'profile_image'   => $this->fundraiser->get( 'profile_image' ),
            'is_approved'     => $this->fundraiser->hasApprovalStatus(),
		];
	}

}
