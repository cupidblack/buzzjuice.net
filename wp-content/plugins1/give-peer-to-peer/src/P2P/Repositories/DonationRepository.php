<?php

namespace GiveP2P\P2P\Repositories;

use GiveP2P\P2P\QueryBuilder\QueryBuilder;
use WP_REST_Request;
use InvalidArgumentException;
use Give\Framework\Database\DB;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\ValueObjects\Status;

/**
 * Class FundraisersRepository
 * @package GiveP2P\P2P\Repositories
 *
 * @since 1.0.0
 */
class DonationRepository {

	/**
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * FundraisersRepository constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @since 1.0.0
	 * @param int $campaignId
	 * @param int $limit
	 * @return array
	 */
	public function getRecentDonationsForCampaign( $campaignId, $limit ) {
		$builder = new QueryBuilder();

		$builder->tables([
			'donation_source' => $this->wpdb->give_p2p_donation_source,
			'revenue' => $this->wpdb->give_revenue,
			'donations' => $this->wpdb->posts,
			'donors' => "{$this->wpdb->prefix}give_donors",
			'campaigns' => $this->wpdb->give_campaigns,
			'teams' => $this->wpdb->give_p2p_teams,
			'fundraisers' => $this->wpdb->give_p2p_fundraisers,
		]);

		$builder
			->select([
				[ 'donation_source.donation_id', 'donationID' ],
				[ 'donation_source.anonymous', 'isAnonymousDonation' ],
				[ 'donation_source.source_id', 'sourceId' ],
				[ 'donation_source.source_type', 'sourceType' ],
				[ 'donors.name', 'donorName' ],
				[ 'donations.post_date', 'donationDate' ],
				[ 'revenue.amount', 'donationAmount' ],
				[ 'campaigns.campaign_title', 'campaignName'],
				[ 'teams.name', 'teamName'],
			])
			->from('donation_source')
			->join('revenue', 'donation_id', 'donation_id')
			->join('donations', 'donation_id', 'ID')
			->join('donors', 'donor_id', 'id')
			->join('campaigns', 'source_id', 'id')
			->join('teams', 'source_id', 'id')
			->join('fundraisers', 'source_id', 'id')
            ->where('donations.post_status', '=', 'publish')
			->where(function( $query ) {
                return $query
                    ->where('donation_source.source_type', '=', 'campaign')
                    ->where('campaigns.id', '=', '%1$s')
                    ->orWhere('donation_source.source_type', '=', 'team')
                    ->where('teams.campaign_id', '=', '%1$s')
                    ->orWhere('donation_source.source_type', '=', 'fundraiser')
                    ->where('fundraisers.campaign_id', '=', '%1$s');
            })
			->groupBy( 'donationID' )
			->orderBy( 'donation_source.donation_id', 'DESC' )
			->limit( $limit )
		;

		$query = sprintf(
			$builder->getSQL(),
			$campaignId
		);

		return DB::get_results(
			$query,
			ARRAY_A
		);
	}


	/**
	 * @param  int  $fundraiserId
	 *
	 * @return array
	 */
	public function getRecentDonationsForFundraiser( $fundraiserId, $limit ) {

		$builder = new QueryBuilder();

		$builder->tables([
			'donation_source' => $this->wpdb->give_p2p_donation_source,
			'revenue' => $this->wpdb->give_revenue,
			'donations' => $this->wpdb->posts,
			'donors' => "{$this->wpdb->prefix}give_donors",
			'fundraisers' => $this->wpdb->give_p2p_fundraisers,
		]);

		$builder
			->select([
				[ 'donation_source.donation_id', 'donationID' ],
				[ 'donation_source.anonymous', 'isAnonymousDonation' ],
				[ 'donors.name', 'donorName' ],
				[ 'donations.post_date', 'donationDate' ],
				[ 'revenue.amount', 'donationAmount' ]
			])
			->from('donation_source')
			->join('revenue', 'donation_id', 'donation_id')
			->join('donations', 'donation_id', 'ID')
			->join('donors', 'donor_id', 'id')
			->join('fundraisers', 'source_id', 'id')
            ->where('donations.post_status', '=', 'publish')
			->where('donation_source.source_type', '=', 'fundraiser')
			->where('fundraisers.id', '=', '%d')
			->orderBy( 'donations.post_date', 'DESC')
			->limit( $limit )
		;

		$query = DB::prepare(
			$builder->getSQL(),
			$fundraiserId
		);

		return DB::get_results(
			$query,
			ARRAY_A
		);
	}

	/**
	 * @param  int  $teamId
	 *
	 * @return array
	 */
	public function getRecentDonationsForTeam( $teamId, $limit ) {

		$builder = new QueryBuilder();

		$builder->tables([
			'donation_source' => $this->wpdb->give_p2p_donation_source,
			'revenue' => $this->wpdb->give_revenue,
			'donations' => $this->wpdb->posts,
			'donors' => "{$this->wpdb->prefix}give_donors",
			'teams' => $this->wpdb->give_p2p_teams,
			'fundraisers' => $this->wpdb->give_p2p_fundraisers,
		]);

		$builder
			->select([
				[ 'donation_source.donation_id', 'donationID' ],
				[ 'donation_source.anonymous', 'isAnonymousDonation' ],
				[ 'donation_source.source_id', 'sourceId' ],
				[ 'donation_source.source_type', 'sourceType' ],
				[ 'donors.name', 'donorName' ],
				[ 'donations.post_date', 'donationDate' ],
				[ 'revenue.amount', 'donationAmount' ],
			])
			->from('donation_source')
			->join('revenue', 'donation_id', 'donation_id')
			->join('donations', 'donation_id', 'ID')
			->join('donors', 'donor_id', 'id')
			->join('teams', 'source_id', 'id')
			->join('fundraisers', 'source_id', 'id')
            ->where('donations.post_status', '=', 'publish')
            ->where(function( $query ) {
                return $query
                        ->where('donation_source.source_type', '=', 'team')
                        ->where('teams.id', '=', '%1$s')
                        ->orWhere('donation_source.source_type', '=', 'fundraiser')
                        ->where('fundraisers.team_id', '=', '%1$s');
            })
			->orderBy( 'donation_source.donation_id', 'DESC' )
			->limit( $limit )
		;

		$query = sprintf(
			$builder->getSQL(),
			$teamId
		);

		return DB::get_results(
			$query,
			ARRAY_A
		);
	}

	/**
	 * @param  int  $userId  WordPress user ID
	 * @param  int  $campaignId
	 *
	 * @return int Fundraiser ID
	 */
	public function getFundraiserIdByUserIdAndCampaignId( $userId, $campaignId ) {
		$query = DB::prepare(
			"
				SELECT id
				FROM {$this->wpdb->give_p2p_fundraisers}
				WHERE user_id = %d
				AND campaign_id = %d
			",
			$userId,
			$campaignId
		);

		return ( int ) DB::get_var( $query );
	}

	/**
	 * @param $campaignId
	 *
	 * @return Fundraiser[]
	 */
	public function getCampaignFundraisers( $campaignId ) {
		$data = [];

		$query = DB::prepare(
			"
				SELECT fundraisers.*, p2p_teams.name as team_name
				FROM {$this->wpdb->give_p2p_fundraisers} fundraisers
				LEFT JOIN {$this->wpdb->give_p2p_teams} p2p_teams
				ON fundraisers.team_id = p2p_teams.id
				WHERE fundraisers.campaign_id = %d
			",
			$campaignId
		);

		$fundraisers = DB::get_results(
			$query,
			ARRAY_A
		);

		if ( ! $fundraisers ) {
			return $data;
		}

		foreach ( $fundraisers as $fundraiser ) {
			$data[] = Fundraiser::fromArray( $fundraiser );
		}

		return $data;
	}

	/**
	 * @param  int  $teamId
	 *
	 * @return Fundraiser[]
	 */
	public function getTeamFundraisers( $teamId ) {
		$data = [];

		$query = DB::prepare(
			"
				SELECT fundraisers.*, p2p_teams.name as team_name
				FROM {$this->wpdb->give_p2p_fundraisers} fundraisers
				LEFT JOIN {$this->wpdb->give_p2p_teams} p2p_teams
				ON fundraisers.team_id = p2p_teams.id
				WHERE fundraisers.team_id = %d
			",
			$teamId
		);

		$fundraisers = DB::get_results(
			$query,
			ARRAY_A
		);

		if ( ! $fundraisers ) {
			return $data;
		}

		foreach ( $fundraisers as $fundraiser ) {
			$data[] = Fundraiser::fromArray( $fundraiser );
		}

		return $data;
	}

	/**
	 * @param  int  $teamId
	 *
	 * @return int
	 */
	public function getTeamFundraisersCount( $teamId ) {
		return (int) DB::get_var(
			DB::prepare( "SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE team_id = %d", $teamId )
		);
	}


	/**
	 * @param  int  $teamId
	 *
	 * @return Fundraiser[]
	 */
	public function getTeamCaptains( $teamId ) {
		$data = [];

		$query = DB::prepare(
			"
				SELECT fundraisers.*, p2p_teams.name as team_name
				FROM {$this->wpdb->give_p2p_fundraisers} fundraisers
				LEFT JOIN {$this->wpdb->give_p2p_teams} p2p_teams
				ON fundraisers.team_id = p2p_teams.id
				WHERE fundraisers.team_id = %d
			    AND fundraisers.team_captain = 1
			    AND fundraisers.status = %s
			",
			$teamId,
			Status::ACTIVE
		);

		$fundraisers = DB::get_results( $query, ARRAY_A );

		if ( ! $fundraisers ) {
			return $data;
		}

		foreach ( $fundraisers as $fundraiser ) {
			$data[] = Fundraiser::fromArray( $fundraiser );
		}

		return $data;
	}

	/**
	 * @param  int  $campaignId
	 *
	 * @return int
	 */
	public function getCampaignFundraisersCount( $campaignId ) {
		return (int) DB::get_var(
			DB::prepare( "SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE campaign_id = %d", $campaignId )
		);
	}

	/**
	 * @param  int  $campaignId
	 * @param  string  $status
	 *
	 * @return int
	 */
	public function getCampaignFundraisersCountByStatus( $campaignId, $status ) {
		if ( ! Status::isValid( $status ) ) {
			throw new InvalidArgumentException( "Invalid status value: $status" );
		}

		return (int) DB::get_var(
			DB::prepare( "SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE status = %s AND campaign_id = %d", $status, $campaignId )
		);
	}

	/**
	 * @param  int  $teamId
	 * @param  string  $status
	 *
	 * @return int
	 */
	public function getTeamFundraisersCountByStatus( $teamId, $status ) {
		if ( ! Status::isValid( $status ) ) {
			throw new InvalidArgumentException( "Invalid status value: $status" );
		}

		return (int) DB::get_var(
			DB::prepare( "SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE status = %s AND id = %d", $status, $teamId )
		);
	}

	/**
	 * @param  WP_REST_Request  $request
	 *
	 * @return Fundraiser[]
	 */
	public function getFundraisersForRequest( WP_REST_Request $request ) {
		$data          = [];
		$campaignId    = $request->get_param( 'campaign_id' );
		$teamId        = $request->get_param( 'team_id' );
		$status        = $request->get_param( 'status' );
		$page          = $request->get_param( 'page' );
		$perPage       = $request->get_param( 'per_page' );
		$sortBy        = $request->get_param( 'sort' );
		$sortDirection = $request->get_param( 'direction' );

		$offset = ( $page - 1 ) * $perPage;

		$query = "
			SELECT fundraisers.*, p2p_teams.name as team_name FROM {$this->wpdb->give_p2p_fundraisers} fundraisers
			LEFT JOIN {$this->wpdb->give_p2p_teams} p2p_teams
			ON fundraisers.team_id = p2p_teams.id
			WHERE fundraisers.campaign_id = {$campaignId}
		";

		if ( $teamId && 'all' !== $teamId ) {
			$query .= sprintf( ' AND team_id = %d', $teamId );
		}

		if ( $status && 'all' !== $status ) {
			$query .= sprintf( ' AND fundraisers.status = "%s"', esc_sql( $status ) );
		}

		if ( $sortBy ) {
			$column    = ( in_array( $sortBy, self::SORTABLE_COLUMNS, true ) ) ? $sortBy : 'id';
			$direction = ( $sortDirection && strtoupper( $sortDirection ) === 'ASC' ) ? 'ASC' : 'DESC';

			$query .= " ORDER BY `{$column}` {$direction}";
		} else {
			$query .= ' ORDER BY id DESC';
		}

		// Limit
		$query .= sprintf( ' LIMIT %d', $perPage );

		// Offset
		if ( $offset > 1 ) {
			$query .= sprintf( ' OFFSET %d', $offset );
		}

		$fundraisers = DB::get_results( $query, ARRAY_A );

		if ( ! $fundraisers ) {
			return $data;
		}

		foreach ( $fundraisers as $fundraiser ) {
			$data[] = Fundraiser::fromArray( $fundraiser );
		}

		return $data;
	}

	/**
	 * Get total fundraisers count for request
	 * Used for pagination
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return int
	 */
	public function getTotalFundraisersCountForRequest( WP_REST_Request $request ) {
		$campaignId = $request->get_param( 'campaign_id' );
		$teamId     = $request->get_param( 'team_id' );
		$status     = $request->get_param( 'status' );

		$query = "SELECT count(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE {$campaignId}";

		if ( $teamId && 'all' !== $teamId ) {
			$query .= sprintf( ' AND team_id = %d', $teamId );
		}

		if ( $status && 'all' !== $status ) {
			$query .= sprintf( ' AND status = "%s"', esc_sql( $status ) );
		}

		return (int) DB::get_var( $query );
	}

	/**
	 * @param  int  $fundraiserId
	 *
	 * @return int
	 */
	public function getRaisedAmount( $fundraiserId ) {
		return 0;
	}

	/**
	 * @return string[]
	 */
	public function getSortableColumns() {
		return self::SORTABLE_COLUMNS;
	}
}
