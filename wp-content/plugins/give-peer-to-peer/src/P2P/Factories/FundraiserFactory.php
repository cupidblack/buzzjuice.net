<?php

namespace GiveP2P\P2P\Factories;

use Give\TestData\Framework\Factory;

class FundraiserFactory extends Factory {
	public function definition() {
		return [
			'campaign_id' => 0,
			'user_id'     => 0,
			'team_id'     => 0,
			'goal'        => '1000' * 100,
		];
	}
}
