<?php

namespace GiveP2P\P2P\Repositories;

use Give\Framework\Database\DB;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Helpers\Hooks;
use Give\Log\Log;
use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\ValueObjects\Status;
use GiveP2P\Routing\NotFoundException;
use InvalidArgumentException;
use WP_REST_Request;

/**
 * Class TeamRepository
 * @package GiveP2P\P2P\Repositories
 *
 * @since   1.0.0
 */
class TeamRepository
{
    const SORTABLE_COLUMNS = ['name', 'goal', 'captain', 'date_created', 'status'];

    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * TeamRepository constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }


    public static function findFirstOrFail($id)
    {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
			SELECT *
			FROM $wpdb->give_p2p_teams
			WHERE id = %d
		",
                $id
            )
        );

        if (empty($results)) {
            throw new NotFoundException('Team not found.');
        }

        return array_pop($results);
    }

    /**
     * @param $teamId
     *
     * @return Team|null
     */
    public function getTeam($teamId)
    {
        $team = DB::get_row(
            DB::prepare("SELECT * FROM {$this->wpdb->give_p2p_teams} WHERE id = %d", $teamId),
            ARRAY_A
        );

        if (empty($team)) {
            return null;
        }

        return Team::fromArray($team);
    }

    /**
     * @since 1.5.0
     *
     * @param $ownerId
     *
     * @return Team
     */
    public function getTeamByOwnerId($ownerId)
    {
        $team = DB::get_row(
            DB::prepare("SELECT * FROM {$this->wpdb->give_p2p_teams} WHERE owner_id = %d", $ownerId),
            ARRAY_A
        );

        return Team::fromArray($team);
    }


    /**
     * @param $teamId
     *
     * @return array|null
     */
    public function getTeamData($teamId)
    {
        return DB::get_row(
            DB::prepare("
				SELECT teams.*, users.display_name as captain, COUNT( fundraisersCount.id ) as fundraiser_count
				FROM {$this->wpdb->give_p2p_teams} as teams
				LEFT JOIN {$this->wpdb->give_p2p_fundraisers} as fundraisers
					ON teams.id = fundraisers.team_id
				    	AND fundraisers.id = teams.owner_id
				    LEFT JOIN {$this->wpdb->users} as users
				    	ON users.ID = fundraisers.user_id
				LEFT JOIN {$this->wpdb->give_p2p_fundraisers} as fundraisersCount
					ON teams.id = fundraisersCount.team_id
				WHERE teams.id = %d
				GROUP BY teams.id
			", $teamId),
            ARRAY_A
        );
    }

    /**
     * Check if team exist
     *
     * @param int $teamId
     *
     * @return bool
     */
    public function teamExist($teamId)
    {
        $id = DB::get_var(
            DB::prepare("SELECT id FROM {$this->wpdb->give_p2p_teams} WHERE id = %d", $teamId)
        );

        return boolval($id);
    }


    /**
     * @param int $campaignId
     *
     * @return Team[]
     */
    public function getCampaignTeams($campaignId)
    {
        $data = [];

        $teams = DB::get_results(
            DB::prepare("SELECT * FROM {$this->wpdb->give_p2p_teams} WHERE campaign_id = %d", $campaignId),
            ARRAY_A
        );

        if ( ! $teams) {
            return $data;
        }

        foreach ($teams as $team) {
            $data[] = Team::fromArray($team);
        }

        return $data;
    }

    /**
     * @param int $campaignId
     *
     * @return int
     */
    public function getCampaignTeamsCount($campaignId)
    {
        return (int)DB::get_var(
            DB::prepare("SELECT COUNT(id) FROM {$this->wpdb->give_p2p_teams} WHERE campaign_id = %d", $campaignId)
        );
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return Team[]
     */
    public function getCampaignTeamsForRequest(WP_REST_Request $request)
    {
        $data = [];
        $campaignId = $request->get_param('campaign_id');
        $status = $request->get_param('status');
        $page = $request->get_param('page');
        $perPage = $request->get_param('per_page');
        $sortBy = $request->get_param('sort');
        $sortDirection = $request->get_param('direction');

        $offset = ($page - 1) * $perPage;

        $query = "SELECT * FROM {$this->wpdb->give_p2p_teams} WHERE campaign_id = {$campaignId}";

        if ($status && 'all' !== $status) {
            $query .= sprintf(' AND status = "%s"', esc_sql($status));
        }

        if ($sortBy) {
            $column = (in_array($sortBy, self::SORTABLE_COLUMNS, true)) ? $sortBy : 'id';
            $direction = ($sortDirection && strtoupper($sortDirection) === 'ASC') ? 'ASC' : 'DESC';

            $query .= " ORDER BY `{$column}` {$direction}";
        } else {
            $query .= ' ORDER BY id DESC';
        }

        // Limit
        $query .= sprintf(' LIMIT %d', $perPage);

        // Offset
        if ($offset > 1) {
            $query .= sprintf(' OFFSET %d', $offset);
        }

        $teams = DB::get_results($query, ARRAY_A);

        if ( ! $teams) {
            return $data;
        }

        foreach ($teams as $team) {
            $data[] = Team::fromArray($team);
        }

        return $data;
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return int
     */
    public function getCampaignTeamsCountForRequest(WP_REST_Request $request)
    {
        $campaignId = $request->get_param('campaign_id');
        $status = $request->get_param('status');

        $query = "SELECT count(id) FROM {$this->wpdb->give_p2p_teams} WHERE campaign_id = {$campaignId}";

        if ($status && 'all' !== $status) {
            $query .= sprintf(' AND status = "%s"', esc_sql($status));
        }

        return (int)DB::get_var($query);
    }


    /**
     * @param int    $campaignId
     * @param string $status
     *
     * @return int
     */
    public function getCampaignTeamsCountByStatus($campaignId, $status)
    {
        if ( ! Status::isValid($status)) {
            throw new InvalidArgumentException("Invalid status value: $status");
        }

        return (int)DB::get_var(
            DB::prepare("SELECT COUNT(id) FROM {$this->wpdb->give_p2p_teams} WHERE status = %s AND campaign_id = %d",
                $status, $campaignId)
        );
    }


    /**
     * @param int $teamId
     *
     * @return int
     */
    public function getRaisedAmount($teamId)
    {
        $query = "
			SELECT SUM( amount ) FROM (

				SELECT revenue.amount as amount
				FROM {$this->wpdb->give_revenue} AS revenue
				JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
				JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND donation_source.source_type = 'team'
				AND donation_source.source_id = {$teamId}

				UNION ALL

				SELECT revenue.amount as amount
				FROM {$this->wpdb->give_revenue} AS revenue
				JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
				JOIN {$this->wpdb->give_p2p_fundraisers} as fundraisers ON donation_source.source_id = fundraisers.id
                JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND donation_source.source_type = 'fundraiser'
				AND fundraisers.team_id = {$teamId}

			) as aggregatedTeamDonations
		";

        return (int)DB::get_var($query);
    }


    /**
     * @return string[]
     */
    public function getSortableColumns()
    {
        return self::SORTABLE_COLUMNS;
    }

    /** Save team
     *
     * @param Team $team
     *
     * @return bool
     */
    public function saveTeam(Team $team)
    {
        try {
            // Update P2P team data
            DB::update(
                $this->wpdb->give_p2p_teams,
                $team->getUpdatedPropertiesWithout('id'),
                [
                    'id' => $team->getId(),
                ]
            );

            /**
             * @since 1.5.0
             */
            Hooks::doAction('give_p2p_team_updated', $team);

            return true;
        } catch (DatabaseQueryException $e) {
            Log::error(
                'Failed to save P2P team',
                [
                    'category' => 'Peer-to-Peer',
                    'source' => 'Peer-to-Peer Add-on',
                    'Team' => $team->toArray(),
                    'Error Message' => $e->getMessage(),
                    'Query Errors' => $e->getQueryErrors(),
                ]
            );

            return false;
        }
    }

    /** Insert team
     *
     * @param Team $team
     *
     * @return bool
     */
    public function insertTeam(Team $team)
    {
        try {
            // Update P2P team data
            DB::insert(
                $this->wpdb->give_p2p_teams,
                $team->getUpdatedProperties(),
                null
            );

            $team->set('id', DB::last_insert_id());

            /**
             * @since 1.5.0
             */
            Hooks::doAction('give_p2p_team_created', $team);

            return true;
        } catch (DatabaseQueryException $e) {
            Log::error(
                'Failed to insert P2P team',
                [
                    'category' => 'Peer-to-Peer',
                    'source' => 'Peer-to-Peer Add-on',
                    'Team' => $team->toArray(),
                    'Error Message' => $e->getMessage(),
                    'Query Errors' => $e->getQueryErrors(),
                ]
            );

            return false;
        }
    }

    public function getCampaignTeamsSearchCount($campaign_id, $searchString, $showClosedTeams = false)
    {
        $andTeamAccess = ($showClosedTeams) ? '' : "AND access = 'public'";

        $query = DB::prepare(
            "
				SELECT count( id )
				FROM {$this->wpdb->give_p2p_teams}
				WHERE name LIKE %s
				AND status = 'active'
				AND campaign_id = %d

				$andTeamAccess
			",
            "%%$searchString%%", // Escape `%`s with a leading `%`.
            $campaign_id
        );

        return (int)DB::get_var($query);
    }

    public function getCampaignTeamsSearch($campaignId, $searchString, $limit, $showClosedTeams = false, $offset = 0)
    {
        $andTeamAccess = ($showClosedTeams) ? '' : "AND access = 'public'";

        $query = DB::prepare(
            "
				SELECT DISTINCT * FROM (

					SELECT team_id as id, teams.campaign_id, teams.status, teams.name, teams.profile_image, teams.goal, teams.access, teams.owner_id, SUM(amount) as amount FROM (
									  SELECT source_id as team_id, amount
									  FROM {$this->wpdb->give_p2p_donation_source} AS teams_source
                                        JOIN {$this->wpdb->posts} AS team_donations ON team_donations.ID = teams_source.donation_id
											   LEFT JOIN {$this->wpdb->give_revenue} AS teams_revenue
														 ON teams_source.donation_id = teams_revenue.donation_id
									  WHERE team_donations.post_status = 'publish'
									  AND teams_source.source_type = 'team'
									  UNION ALL
									  SELECT fundraisers.team_id as team_id, amount
									  FROM {$this->wpdb->give_p2p_donation_source} AS fundraisers_source
									    JOIN {$this->wpdb->posts} AS fundraiser_donations ON fundraiser_donations.ID = fundraisers_source.donation_id
											   LEFT JOIN {$this->wpdb->give_p2p_fundraisers} AS fundraisers
														 ON fundraisers.id = fundraisers_source.source_id
											   LEFT JOIN {$this->wpdb->give_revenue} AS fundraisers_revenue
														 ON fundraisers_source.donation_id = fundraisers_revenue.donation_id
									  WHERE fundraiser_donations.post_status = 'publish'
									  AND fundraisers_source.source_type = 'fundraiser'
								  ) AS donations
					LEFT JOIN {$this->wpdb->give_p2p_teams} as teams
						ON teams.id = team_id
					GROUP BY id

					UNION ALL

					SELECT teams.id as id, teams.campaign_id, teams.status, teams.name, teams.profile_image, teams.goal, teams.access, teams.owner_id, 0 as amount
					FROM {$this->wpdb->give_p2p_teams} as teams

				) as everything

				WHERE name LIKE %s
			    AND status = 'active'
				AND campaign_id = %d

				$andTeamAccess

				GROUP BY id
				ORDER BY amount DESC
				LIMIT %d
				OFFSET %d
			",
            "%%$searchString%%", // Escape `%`s with a leading `%`.
            $campaignId,
            $limit,
            $offset
        );

        return DB::get_results(
            $query,
            ARRAY_A
        );
    }

    public function getTeamFundraisers($teamID)
    {
        global $wpdb;

        /**
         * @since 1.3.0 Update query to only include amounts from completed donations.
         *      Chained joining ensures that we return all fundraisers while only including amounts
         *      from donations that are published. The Revenue table is joined on the Donation
         *      (Posts) table so that only published donations are included in the sum.
         */

        $query = DB::prepare(
            "
				SELECT fundraisers.id as id, users.display_name as name, fundraisers.profile_image, fundraisers.goal, SUM( revenue.amount ) as amount
				FROM {$this->wpdb->give_p2p_fundraisers} AS fundraisers
				LEFT JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source on fundraisers.id = donation_source.source_id
				    AND donation_source.source_type = 'fundraiser'
                LEFT JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				    AND donation.post_status = 'publish'
				LEFT JOIN {$this->wpdb->give_revenue} AS revenue ON donation.ID = revenue.donation_id
				LEFT JOIN {$this->wpdb->users} AS users ON fundraisers.user_id = users.ID
				WHERE 1
				AND fundraisers.team_id = %d
				AND fundraisers.status = 'active'
				GROUP BY fundraisers.id
				ORDER BY amount DESC
			",
            $teamID
        );

        return DB::get_results(
            $query,
            ARRAY_A
        );
    }

    /**
     * @since 1.0.0
     *
     * @param int $teamId
     * @param int $limit
     *
     * @return array
     */
    public function getTopDonors($teamId, $limit)
    {
        $query = DB::prepare(
            "
				SELECT SUM(revenue_amount) as amount, source_id, donor_id, name
				FROM (
					{$this->getTeamDonorsSubquery()}
					UNION ALL
					{$this->getTeamFundraiserDonorsSubquery()}
				) as campaign_donations
				GROUP BY donor_id
				ORDER BY amount DESC
				LIMIT %d
			",
            $teamId,
            $teamId,
            $limit
        );

        return DB::get_results(
            $query,
            ARRAY_A
        );
    }

    private function getTeamDonorsSubquery()
    {
        return "
			SELECT revenue.amount as revenue_amount, donation_source.source_id as source_id, donors.id as donor_id, COALESCE(donors.name, wp_users.display_name) as name
			FROM {$this->wpdb->give_revenue} AS revenue
			JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
			JOIN {$this->wpdb->prefix}give_donors AS donors ON donation_source.donor_id = donors.id
			JOIN {$this->wpdb->give_p2p_teams} AS teams ON donation_source.source_id = teams.id
            JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			LEFT JOIN {$this->wpdb->users} AS wp_users ON wp_users.ID = donors.user_id
            WHERE donation.post_status = 'publish'
			AND donation_source.source_type = 'team'
			AND teams.id = %d
            AND donation_source.anonymous = 0
		";
    }

    private function getTeamFundraiserDonorsSubquery()
    {
        return "
			SELECT revenue.amount as revenue_amount, donation_source.source_id as source_id, donors.id as donor_id, COALESCE(donors.name, wp_users.display_name) as name
			FROM {$this->wpdb->give_revenue} AS revenue
			JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
			JOIN {$this->wpdb->prefix}give_donors AS donors ON donation_source.donor_id = donors.id
			JOIN {$this->wpdb->give_p2p_fundraisers} AS fundraisers ON donation_source.source_id = fundraisers.id
            JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			LEFT JOIN {$this->wpdb->users} AS wp_users ON wp_users.ID = donors.user_id
            WHERE donation.post_status = 'publish'
			AND donation_source.source_type = 'fundraiser'
			AND fundraisers.team_id = %d
            AND donation_source.anonymous = 0
		";
    }

    /**
     * @param int $teamId
     *
     * @return int
     */
    public function getDonorsCount($teamId)
    {
        $query = DB::prepare(
            "
				SELECT count( DISTINCT donor_id ) as count
				FROM {$this->wpdb->give_p2p_donation_source} AS donation_source
				JOIN {$this->wpdb->give_p2p_fundraisers} AS fundraisers ON donation_source.source_id = fundraisers.id
                JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND (
                        donation_source.source_type = 'team'
                    AND donation_source.source_id = %d
                    OR  donation_source.source_type = 'fundraiser'
                    AND fundraisers.team_id = %d
				)
				GROUP BY donation_source.source_id
		",
            $teamId,
            $teamId
        );

        return (int)DB::get_var($query);
    }

    /**
     * @param int $teamId
     *
     * @return int
     */
    public function getDonationsCount($teamId)
    {
        $query = sprintf(
            "
				SELECT count( donation_id ) as count
				FROM {$this->wpdb->give_p2p_donation_source} AS donation_source
				JOIN {$this->wpdb->give_p2p_fundraisers} AS fundraisers ON donation_source.source_id = fundraisers.id
                JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND (
                        donation_source.source_type = 'team'
                    AND donation_source.source_id = %d
                    OR  donation_source.source_type = 'fundraiser'
                    AND fundraisers.team_id = %d
				)
		",
            $teamId,
            $teamId
        );

        return (int)DB::get_var($query);
    }

    /**
     * @param int $teamId
     *
     * @return int
     */
    public function getAverageDonationAmount($teamId)
    {
        $query = DB::prepare(
            "
				SELECT AVG( revenue.amount ) as amount
				FROM {$this->wpdb->give_p2p_donation_source} AS donation_source
				JOIN {$this->wpdb->give_p2p_fundraisers} AS fundraisers ON donation_source.source_id = fundraisers.id
				JOIN {$this->wpdb->give_revenue} AS revenue ON donation_source.donation_id = revenue.donation_id
                JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND (
				        donation_source.source_type = 'team'
                    AND donation_source.source_id = %d
                    OR  donation_source.source_type = 'fundraiser'
                    AND fundraisers.team_id = %d
				)
				GROUP BY donation_source.source_id
		",
            $teamId,
            $teamId
        );

        return (int)DB::get_var($query);
    }


    /**
     * @param int $teamId
     *
     * @return int
     */
    public function getFundraisersCount($teamId)
    {
        return (int)DB::get_var(
            DB::prepare("SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE team_id = %d", $teamId)
        );
    }

    public function getRecentlyRegisteredTeams()
    {
        return array_map(function ($fundraiser) {
            return Team::fromArray($fundraiser);
        }, DB::get_results("
            SELECT *
            FROM {$this->wpdb->give_p2p_teams}
            WHERE DATE(date_created) >= SUBDATE(CURRENT_DATE, 1)
        ", ARRAY_A));
    }

}
