<?php

namespace GiveP2P\P2P\ViewModels\Frontend;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Models\Team;

/**
 * Class TeamViewModel
 * @package GiveP2P\P2P\ViewModels\Frontend
 *
 * @since 1.0.0
 */
class TeamViewModel
{

    /**
     * @var Team
     */
    private $team;

    /**
     * EditTeamViewModel constructor.
     *
     * @param Team $team
     *
     */
    public function __construct(
        Team $team
    ) {
        $this->team = $team;
    }

    /**
     *
     * @return array
     */
    public function exports()
    {
        return [
            'id' => $this->team->getId(),
            'campaign_id' => $this->team->get('campaign_id'),
            'owner_id' => $this->team->get('owner_id'),
            'name' => $this->team->get('name'),
            'goal' => Money::ofMinor($this->team->get('goal'), give_get_option('currency'))->getAmount(),
            'profile_image' => $this->team->get('profile_image'),
            'story' => wpautop( $this->team->get('story') ),
            'access' => $this->team->get('access'),
            'notify_of_fundraisers' => $this->team->isNotifiedOfFundraisersJoined(),
            'notify_of_team_donations' => $this->team->isNotifiedOfTeamDonations(),
        ];
    }
}
