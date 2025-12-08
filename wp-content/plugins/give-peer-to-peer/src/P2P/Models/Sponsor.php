<?php

namespace GiveP2P\P2P\Models;

use InvalidArgumentException;
use GiveP2P\P2P\Models\Traits\Properties;

class Sponsor {

	use Properties;

	/**
	 * @var int|null
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $sponsor_name;

	/**
	 * @var string
	 */
	protected $sponsor_image;

	/**
	 * @var string
	 */
	protected $sponsor_url;

	/**
	 * @param array $sponsorData
	 *
	 * @return static
	 */
	public static function fromArray( $sponsorData ) {
		$sponsor = new static();
		$sponsor->validateArray( $sponsorData );
		$sponsor->setPropertiesFromArray( $sponsorData );

		return $sponsor;
	}

	/**
	 * Validate Sponsor data array
	 *
	 * @param  array  $sponsorData
	 */
	private function validateArray( $sponsorData ) {
		if ( array_diff( $this->getRequiredFields(), array_keys( $sponsorData ) ) ) {
			throw new InvalidArgumentException(
				esc_html__( 'To create a Sponsor, please provide all the required fields: ' . implode( ', ', $this->getRequiredFields() ), 'give-peer-to-peer' )
			);
		}
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id'            => $this->id,
			'sponsor_name'  => $this->sponsor_name,
			'sponsor_image' => $this->sponsor_image,
			'sponsor_url'   => $this->sponsor_url,
		];
	}

	/**
	 * return array
	 */
	public function getRequiredFields() {
		return [
			'sponsor_name',
		];
	}

}
