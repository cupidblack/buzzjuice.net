<?php

namespace GiveP2P\Reallocation;

use GiveP2P\P2P\ValueObjects\Enum;

class StrategyEnum extends Enum {
    const CAMPAIGN_FUNDRAISER_STRATEGY = 'campaignFundraiser';
    const TEAM_ONLY_FUNDRAISER_STRATEGY = 'teamOnlyFundraiser';
    const TEAM_MEMBER_STRATEGY = 'teamMember';
    const TEAM_OWNER_STRATEGY = 'teamOwner';
}
