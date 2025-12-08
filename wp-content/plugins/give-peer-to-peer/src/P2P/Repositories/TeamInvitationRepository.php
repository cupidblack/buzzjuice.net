<?php

namespace GiveP2P\P2P\Repositories;

use Give\Log\Log;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Models\TeamInvitation;
use WP_REST_Request;
use InvalidArgumentException;
use Give\Framework\Database\DB;
use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\ValueObjects\Status;
use GiveP2P\Routing\NotFoundException;
use Give\Framework\Database\Exceptions\DatabaseQueryException;

/**
 * Class TeamInvitationRepository
 * @package GiveP2P\P2P\Repositories
 *
 * @since 1.0.0
 */
class TeamInvitationRepository {

	/**
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * TeamRepository constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}


	/**
	 * @since 1.0.0
	 * @param $id
	 * @return mixed|null
	 * @throws NotFoundException
	 */
	public static function findFirstOrFail( $id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->give_p2p_team_invitations WHERE id = %d",
				$id
			)
		);

		if ( empty( $results ) ) {
			throw new NotFoundException( 'Team Invitation not found.' );
		}

		return array_pop( $results );
	}

	/**
	 * @since 1.0.0
	 * @param $teamID
	 * @return array
	 */
	public function getForTeam( $teamID ) {
		global $wpdb;
		$invitations = DB::get_results(
			DB::prepare(
				"SELECT * FROM $wpdb->give_p2p_team_invitations WHERE team_id = %d",
				$teamID
			),
			ARRAY_A
		);

		return array_map([ TeamInvitation::class, 'fromArray' ], $invitations );
	}

	/**
	 * @since 1.0.0
	 * @param $teamID
	 * @return array
	 */
	public function getForTeamNotSent( $teamID ) {
		global $wpdb;
		$invitations = DB::get_results(
			DB::prepare(
				"SELECT * FROM $wpdb->give_p2p_team_invitations WHERE date_sent IS NULL AND team_id = %d",
				$teamID
			),
			ARRAY_A
		);

		return array_map([ TeamInvitation::class, 'fromArray' ], $invitations );
	}

	/**
	 * @since 1.0.0
	 * @param TeamInvitation $invitation
	 * @return bool
	 */
	public function save( TeamInvitation $invitation ) {
		try {
			// Update P2P team data
			DB::update(
				$this->wpdb->give_p2p_team_invitations,
				$invitation->getUpdatedPropertiesWithout( 'id' ),
				[
					'id' => $invitation->getId(),
				]
			);

			return true;

		} catch ( DatabaseQueryException $e ) {
			Log::error(
				'Failed to save P2P team invitation',
				[
					'category'      => 'Peer-to-Peer',
					'source'        => 'Peer-to-Peer Add-on',
					'Invitation'    => $invitation->toArray(),
					'Error Message' => $e->getMessage(),
					'Query Errors'  => $e->getQueryErrors(),
				]
			);

			return false;
		}
	}

	/**
	 * @since 1.0.0
	 * @param TeamInvitation $invitation
	 * @return bool
	 */
	public function insert( TeamInvitation $invitation ) {
		try {
			// Update P2P team data
			DB::insert(
				$this->wpdb->give_p2p_team_invitations,
				$invitation->getUpdatedProperties(),
				null
			);

			  $invitation->set( 'id', DB::last_insert_id() );

			  return true;

		} catch ( DatabaseQueryException $e ) {
			Log::error(
				'Failed to insert P2P team invitation',
				[
					'category'      => 'Peer-to-Peer',
					'source'        => 'Peer-to-Peer Add-on',
					'Invitation'    => $invitation->toArray(),
					'Error Message' => $e->getMessage(),
					'Query Errors'  => $e->getQueryErrors(),
				]
			);

			return false;
		}
	}

}
