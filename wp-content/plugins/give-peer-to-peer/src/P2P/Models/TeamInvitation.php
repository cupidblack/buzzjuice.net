<?php

namespace GiveP2P\P2P\Models;

use GiveP2P\P2P\Repositories\TeamInvitationRepository;
use InvalidArgumentException;
use GiveP2P\P2P\Models\Traits\Status;
use GiveP2P\P2P\Models\Traits\Properties;
use GiveP2P\P2P\Repositories\TeamRepository;

/**
 * Class TeamInvitation
 * @package GiveP2P\P2P\Models
 *
 * @since 1.0.0
 */
class TeamInvitation {

	use Properties;

	/**
	 * @var int|null
	 */
	protected $id;

	/**
	 * @var int
	 */
	protected $team_id;

	/**
	 * @var string
	 */
	protected $email;

	/**
	 * @var string
	 */
	protected $date_created;

	/**
	 * @var string
	 */
	protected $date_sent;

	/**
	 * @param  array  $teamData
	 *
	 * @return static
	 */
	public static function fromArray( $teamData ) {
		$team = new static();
		$team->validateArray( $teamData );
		$team->setPropertiesFromArray( $teamData );

		return $team;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id'           => $this->id,
			'team_id'      => $this->team_id,
			'email'        => $this->email,
			'date_created' => $this->date_created,
			'date_sent'    => $this->date_sent,
		];
	}

	/**
	 * Validate Team data array
	 *
	 * @param  array  $teamData
	 */
	private function validateArray( $teamData ) {
		if ( array_diff( $this->getRequiredFields(), array_keys( $teamData ) ) ) {
			throw new InvalidArgumentException(
				sprintf(
					esc_html__( 'To create a Team Invitation, please provide all the required fields: %s', 'give-peer-to-peer' ),
					implode( ', ', $this->getRequiredFields() )
				)
			);
		}
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * return array
	 */
	public function getRequiredFields() {
		return [
			'team_id',
			'email',
		];
	}

	/**
	 * @param $value
	 */
	public function setDateSent( $value ) {
		$this->date_sent = $value;
	}

	/**
	 * Save Team data
	 * @return bool
	 */
	public function save() {
		/**
		 * @var TeamRepository $repository
		 */
		$repository = give( TeamInvitationRepository::class );

		return $this->getId()
			? $repository->save( $this )
			: $repository->insert( $this );
	}
}
