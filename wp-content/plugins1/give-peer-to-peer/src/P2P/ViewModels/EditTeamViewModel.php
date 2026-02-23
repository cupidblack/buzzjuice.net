<?php

namespace GiveP2P\P2P\ViewModels;

use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\Repositories\TeamRepository;

/**
 * Class EditTeamViewModel
 * @package GiveP2P\P2P\ViewModels
 *
 * @since 1.0.0
 */
class EditTeamViewModel {
	/**
	 * @var Team
	 */
	private $team;

	/**
	 * @var TeamRepository
	 */
	private $teamRepository;

	/**
	 * EditTeamViewModel constructor.
	 *
	 * @param  Team  $team
	 * @param  TeamRepository  $teamRepository
	 */
	public function __construct(
		Team $team,
		TeamRepository $teamRepository
	) {
		$this->team           = $team;
		$this->teamRepository = $teamRepository;
	}

	/**
	 * @return array
	 */
	public function exports() {
		return [
			'team_id'       => $this->team->get( 'id' ),
			'team_name'     => $this->team->get( 'name' ),
			'team_story'    => $this->team->get( 'story' ),
			'team_goal'     => $this->team->get( 'goal' ),
			'access'        => $this->team->get( 'access' ),
			'profile_image' => $this->team->get( 'profile_image' ),
			'amount_raised' => $this->teamRepository->getRaisedAmount( $this->team->get( 'id' ) ),
			'emails'        => [],
        ];
	}

}
