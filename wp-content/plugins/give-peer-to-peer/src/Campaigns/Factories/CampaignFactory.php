<?php

namespace GiveP2P\Campaigns\Factories;

use Give\TestData\Framework\Factory;

class CampaignFactory extends Factory {
	public function definition() {
		return [
			'form_id'         => 1,
			'title'           => $this->faker->catchPhrase(),
			'short_desc'      => $this->faker->sentence(),
			'long_desc'       => $this->faker->paragraph( 5 ),
			'logo'            => $this->faker->imageUrl( 640, 480, 'animals' ),
			'image'           => $this->faker->imageUrl( 640, 480, 'animals' ),
			'primary_color'   => $this->faker->hexColor(),
			'secondary_color' => $this->faker->hexColor(),
			'goal'            => '1000' * 100,
			'rankings'        => 'enabled',
			'date_created'    => $this->faker->dateTimeBetween( '-3 month', '-1 month' )->format( 'Y/m/d' ),
		];
	}
}
