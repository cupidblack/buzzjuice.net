<?php

namespace GiveP2P\P2P\Facades;

use Give\Framework\Support\Facades\Facade;
use GiveP2P\P2P\Models\Team as Model;
use GiveP2P\P2P\Repositories\TeamRepository;

/**
 * @since 1.0.0
 * @method static Model getTeam( $teamID )
 * @method static Model getRecentlyRegisteredTeams()
 */
class Team extends Facade {
	protected function getFacadeAccessor() {
		return TeamRepository::class;
	}
}
