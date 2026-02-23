<?php

namespace GiveP2P\P2P\ViewModels\Frontend;

use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;

/**
 * Class TeamProfileViewModel
 * @package GiveP2P\P2P\ViewModels\Frontend
 *
 * @since 1.0.0
 */
class TeamProfileViewModel {

	/**
	 * @var Team
	 */
	private $team;

	/**
	 * @var TeamRepository
	 */
	private $repository;


	/**
	 * TeamViewModel constructor.
	 *
	 * @param  Team  $team
	 * @param  TeamRepository  $repository
	 */
	public function __construct(
		Team $team,
		TeamRepository $repository
	) {
		$this->team       = $team;
		$this->repository = $repository;
	}

	/**
     * @since 1.3.0 Added is_approved export value.
     *
	 * @return array
	 */
	public function exports() {
		return [
			'id'            => $this->team->getId(),
			'name'          => $this->team->get( 'name' ),
			'profile_image' => $this->team->get( 'profile_image' ),
			'members'       => $this->repository->getFundraisersCount( $this->team->getId() ),
            'is_approved'   => $this->team->hasApprovalStatus(),
		];
	}
}
