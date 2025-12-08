<?php

namespace GiveP2P\P2P\Factories;

use Faker\Generator;
use Give\TestData\Framework\Factory;
use Bluemmb\Faker\PicsumPhotosProvider;

class TeamFactory extends Factory {

	public function __construct( Generator $faker ) {
		$faker->addProvider( new PicsumPhotosProvider( $faker ) );
		parent::__construct( $faker );
	}


	public function definition() {
		return [
			'campaign_id'   => 0,
			'name'          => $this->faker->company(),
			'story'         => $this->faker->paragraph( 5 ),
			'profile_image' => $this->faker->imageUrl( 640, 480 ),
			'goal'          => '1000' * 100,
		//          access VARCHAR(12) NOT NULL,
		];
	}
}
