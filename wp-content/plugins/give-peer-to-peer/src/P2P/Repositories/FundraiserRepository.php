<?php

namespace GiveP2P\P2P\Repositories;

use Give\Framework\Database\DB;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\QueryBuilder\JoinQueryBuilder;
use Give\Helpers\Hooks;
use Give\Log\Log;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\ValueObjects\Status;
use InvalidArgumentException;
use WP_REST_Request;

/**
 * Class FundraisersRepository
 * @package GiveP2P\P2P\Repositories
 *
 * @since   1.0.0
 * @since   1.3.0 Removed `getFundraiserIdByUserId()` in favor of `getFundraiserIdByUserIdAndCampaignId()`.
 */
class FundraiserRepository
{
    /**
     * Fundraiser sortable columns
     */
    const SORTABLE_COLUMNS = ['goal', 'date_created', 'status', 'team_captain', 'team_id'];

    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * FundraisersRepository constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * @param int $fundraiserId
     *
     * @return Fundraiser|null
     */
    public function getFundraiser($fundraiserId)
    {
        $query = DB::prepare(
            "
				SELECT fundraisers.*, p2p_teams.name as team_name
				FROM {$this->wpdb->give_p2p_fundraisers} fundraisers
				LEFT JOIN {$this->wpdb->give_p2p_teams} p2p_teams
				ON fundraisers.team_id = p2p_teams.id
				WHERE fundraisers.id = %d
			",
            $fundraiserId
        );

        $fundraiser = DB::get_row(
            $query,
            ARRAY_A
        );

        if (!$fundraiser) {
            return null;
        }

        return Fundraiser::fromArray($fundraiser);
    }

    /**
     * Get fundraiser by user id.
     *
     * @since 1.5.0
     *
     * @param int $userId
     *
     * @return Fundraiser|null
     */
    public function getFundraiserByUserId(int $userId)
    {
        $query = DB::table('give_p2p_fundraisers', 'fundraisers')->select(
            'fundraisers.*', 'p2p_teams.name as team_name',
        )->join(function (JoinQueryBuilder $builder) {
            $builder->leftJoin('give_p2p_teams', 'p2p_teams')->on('fundraisers.team_id', 'p2p_teams.id');
        })->where('fundraisers.user_id', $userId)->orderBy('date_created','DESC')->limit(1)->getSQL();

        $fundraiser = DB::get_row(
            $query,
            ARRAY_A
        );

        if ( ! $fundraiser) {
            return null;
        }

        return Fundraiser::fromArray($fundraiser);
    }

    /**
     * @param int $userId WordPress user ID
     * @param int $campaignId
     *
     * @return int Fundraiser ID
     */
    public function getFundraiserIdByUserIdAndCampaignId($userId, $campaignId)
    {
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

        return ( int )DB::get_var($query);
    }

    /**
     * @param int $fundraiserId
     */
    public function getUserIdByFundraiserIdAndCampaignId($fundraiserId, $campaignId)
    {
        $query = DB::prepare(
            "
				SELECT user_id
				FROM {$this->wpdb->give_p2p_fundraisers}
				WHERE id = %d
				AND campaign_id = %d
			",
            $fundraiserId,
            $campaignId
        );

        return ( int )DB::get_var($query);
    }

    /**
     * @param $campaignId
     *
     * @return Fundraiser[]
     */
    public function getCampaignFundraisers($campaignId)
    {
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

        if (!$fundraisers) {
            return $data;
        }

        foreach ($fundraisers as $fundraiser) {
            $data[] = Fundraiser::fromArray($fundraiser);
        }

        return $data;
    }

    /**
     * @param int $teamId
     *
     * @return Fundraiser[]
     */
    public function getTeamFundraisers($teamId)
    {
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

        if (!$fundraisers) {
            return $data;
        }

        foreach ($fundraisers as $fundraiser) {
            $data[] = Fundraiser::fromArray($fundraiser);
        }

        return $data;
    }

    /**
     * @param int $teamId
     *
     * @return Fundraiser[]
     */
    public function getTeamCaptains($teamId)
    {
        $data = [];

        $query = DB::prepare(
            "
				SELECT fundraisers.*, p2p_teams.name as team_name
				FROM {$this->wpdb->give_p2p_fundraisers} fundraisers
				LEFT JOIN {$this->wpdb->give_p2p_teams} p2p_teams
				ON fundraisers.team_id = p2p_teams.id
				WHERE fundraisers.team_id = %d
			    AND fundraisers.team_captain = 1
			",
            $teamId
        );

        $fundraisers = DB::get_results($query, ARRAY_A);

        if (!$fundraisers) {
            return $data;
        }

        foreach ($fundraisers as $fundraiser) {
            $data[] = Fundraiser::fromArray($fundraiser);
        }

        return $data;
    }

    /**
     * @param int $campaignId
     *
     * @return int
     */
    public function getCampaignFundraisersCount($campaignId)
    {
        return (int)DB::get_var(
            DB::prepare("SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE campaign_id = %d", $campaignId)
        );
    }

    /**
     * @param int    $campaignId
     * @param string $status
     *
     * @return int
     */
    public function getCampaignFundraisersCountByStatus($campaignId, $status)
    {
        if (!Status::isValid($status)) {
            throw new InvalidArgumentException("Invalid status value: $status");
        }

        return (int)DB::get_var(
            DB::prepare(
                "SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE status = %s AND campaign_id = %d",
                $status,
                $campaignId
            )
        );
    }

    /**
     * @param int    $teamId
     * @param string $status
     *
     * @return int
     */
    public function getTeamFundraisersCountByStatus($teamId, $status)
    {
        if (!Status::isValid($status)) {
            throw new InvalidArgumentException("Invalid status value: $status");
        }

        return (int)DB::get_var(
            DB::prepare(
                "SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE status = %s AND team_id = %d",
                $status,
                $teamId
            )
        );
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return Fundraiser[]
     */
    public function getFundraisersForRequest(WP_REST_Request $request)
    {
        $data = [];
        $campaignId = $request->get_param('campaign_id');
        $teamId = $request->get_param('team_id');
        $status = $request->get_param('status');
        $page = $request->get_param('page');
        $perPage = $request->get_param('per_page');
        $sortBy = $request->get_param('sort');
        $sortDirection = $request->get_param('direction');

        $offset = ($page - 1) * $perPage;

        $query = "
			SELECT fundraisers.*, p2p_teams.name as team_name FROM {$this->wpdb->give_p2p_fundraisers} fundraisers
			LEFT JOIN {$this->wpdb->give_p2p_teams} p2p_teams
			ON fundraisers.team_id = p2p_teams.id
			WHERE fundraisers.campaign_id = {$campaignId}
		";

        if ($teamId && 'all' !== $teamId) {
            $query .= sprintf(' AND team_id = %d', $teamId);
        }

        if ($status && 'all' !== $status) {
            $query .= sprintf(' AND fundraisers.status = "%s"', esc_sql($status));
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

        $fundraisers = DB::get_results($query, ARRAY_A);

        if (!$fundraisers) {
            return $data;
        }

        foreach ($fundraisers as $fundraiser) {
            $data[] = Fundraiser::fromArray($fundraiser);
        }

        return $data;
    }

    /**
     * Get total fundraisers count for request
     * Used for pagination
     *
     * @since 1.4.0 add missing campaign_id column in SQL query.
     *
     */
    public function getTotalFundraisersCountForRequest(WP_REST_Request $request): int
    {
        $campaignId = $request->get_param('campaign_id');
        $teamId = $request->get_param('team_id');
        $status = $request->get_param('status');

        $queryData[] = $campaignId;
        $query = "SELECT count(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE campaign_id=%s";

        if ($teamId && 'all' !== $teamId) {
            $query .= ' AND team_id = %d';
            $queryData[] = $teamId;
        }

        if ($status && 'all' !== $status) {
            $query .= ' AND status = %s';
            $queryData[] = $status;
        }

        return (int)DB::get_var(
            DB::prepare(
                $query,
                ...$queryData
            )
        );
    }

    /**
     * @param int $fundraiserId
     *
     * @return int
     */
    public function getRaisedAmount($fundraiserId)
    {
        return ( int )DB::get_var(
            "
			SELECT SUM( revenue.amount )
			FROM {$this->wpdb->give_revenue} AS revenue
			JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
            JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			WHERE donation.post_status = 'publish'
			AND donation_source.source_id = {$fundraiserId}
			AND donation_source.source_type = 'fundraiser'
		"
        );
    }

    /**
     * @param Fundraiser $fundraiser
     *
     * @return bool
     */
    public function saveFundraiser(Fundraiser $fundraiser)
    {
        try {
            // Save fundraiser
            DB::update(
                $this->wpdb->give_p2p_fundraisers,
                $fundraiser->getUpdatedPropertiesWithout(['id', 'team_name']),
                [
                    'id' => $fundraiser->getId(),
                ]
            );

            /**
             * @since 1.5.0
             */
            Hooks::doAction('give_p2p_fundraiser_updated', $fundraiser);

            return true;
        } catch (DatabaseQueryException $e) {
            Log::error(
                'Failed to update Fundraiser',
                [
                    'category' => 'Peer-to-Peer',
                    'source' => 'Peer-to-Peer Add-on',
                    'Fundraiser' => $fundraiser->toArray(),
                    'Error Message' => $e->getMessage(),
                    'Query Errors' => $e->getQueryErrors(),
                ]
            );

            return false;
        }
    }

    /**
     * @param Fundraiser $fundraiser
     *
     * @return bool|int
     */
    public function insertFundraiser(Fundraiser $fundraiser)
    {
        try {
            if (!$fundraiser->get('date_created')) {
                $fundraiser->set('date_created', date('Y-m-d H:i:s'));
            }

            DB::insert(
                $this->wpdb->give_p2p_fundraisers,
                [
                    'campaign_id'               => $fundraiser->get('campaign_id'),
                    'user_id'                   => $fundraiser->get('user_id'),
                    'team_id'                   => $fundraiser->get('team_id'),
                    'team_captain'              => $fundraiser->get('team_captain'),
                    'goal'                      => $fundraiser->get('goal'),
                    'story'                     => $fundraiser->get('story'),
                    'status'                    => $fundraiser->get('status'),
                    'profile_image'             => $fundraiser->get('profile_image'),
                    'date_created'              => $fundraiser->get('date_created'),
                    'notify_of_donations'       => $fundraiser->get('notify_of_donations'),
                ],
                null
            );

            /**
             * @since 1.5.0
             */
            Hooks::doAction('give_p2p_fundraiser_created', $fundraiser);

            return DB::last_insert_id();
        } catch (DatabaseQueryException $e) {
            Log::error(
                'Failed to save Fundraiser',
                [
                    'category' => 'Peer-to-Peer',
                    'source' => 'Peer-to-Peer Add-on',
                    'Fundraiser' => $fundraiser->toArray(),
                    'Error Message' => $e->getMessage(),
                    'Query Errors' => $e->getQueryErrors(),
                ]
            );

            return false;
        }
    }

    /**
     * @return string[]
     */
    public function getSortableColumns()
    {
        return self::SORTABLE_COLUMNS;
    }

    /**
     * @param int $fundraiserId
     */
    public function fundraiserHasTeam($fundraiserId)
    {
        return (bool)DB::get_var(
            DB::prepare("SELECT COUNT(id) FROM {$this->wpdb->give_p2p_teams} WHERE owner_id = %d", $fundraiserId)
        );
    }

    /**
     * Check if one or more fundraisers exists for a given user (any campaign, any team).
     *
     * @param $userID
     *
     * @return bool
     */
    public function fundraiserExistsForUser($userID)
    {
        return (bool)DB::get_var(
            DB::prepare("SELECT COUNT(id) FROM {$this->wpdb->give_p2p_fundraisers} WHERE user_id = %d", $userID)
        );
    }

    public function getCampaignFundraiserSearchCount($campaign_id, $searchString)
    {
        $query = DB::prepare(
            "
				SELECT count( fundraisers.id )
				FROM {$this->wpdb->give_p2p_fundraisers} as fundraisers
				LEFT JOIN {$this->wpdb->users} AS users ON fundraisers.user_id = users.ID
				WHERE users.display_name LIKE %s
				AND fundraisers.status = 'active'
				AND campaign_id = %d
			",
            "%%$searchString%%", // Escape `%`s with a leading `%`.
            $campaign_id
        );

        return (int)DB::get_var($query);
    }

    public function getCampaignFundraisersSearch($campaignId, $searchString, $limit, $offset = 0)
    {
        $query = DB::prepare(
            "
				SELECT fundraisers.id as id, users.display_name as name, fundraisers.profile_image,  fundraisers.story as story, fundraisers.goal, SUM( revenue.amount ) as amount
				FROM {$this->wpdb->give_p2p_fundraisers} AS fundraisers
				LEFT JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON fundraisers.id = donation_source.source_id
				    AND donation_source.source_type = 'fundraiser'
				LEFT JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id AND donation.post_status = 'publish'
				LEFT JOIN {$this->wpdb->give_revenue} AS revenue ON donation.ID = revenue.donation_id
				LEFT JOIN {$this->wpdb->users} AS users ON fundraisers.user_id = users.ID
				WHERE 1 = 1
				AND fundraisers.campaign_id = %d
				AND fundraisers.status = 'active'
				AND users.display_name LIKE %s
				GROUP BY fundraisers.id
				ORDER BY amount DESC
				LIMIT %d
				OFFSET %d
			",
            $campaignId,
            "%$searchString%", // Escape `%`s with a leading `%`.
            $limit,
            $offset
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
    public function getTopDonors($fundraiserId, $limit)
    {
        $query = DB::prepare(
            "
				SELECT SUM( revenue.amount ) as amount, donation_source.source_id as id, COALESCE( donors.name, wp_users.display_name) as name
				FROM {$this->wpdb->give_revenue} AS revenue
				JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
                JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				JOIN {$this->wpdb->prefix}give_donors AS donors ON donation_source.donor_id = donors.id
				LEFT JOIN {$this->wpdb->users} AS wp_users ON wp_users.ID = donors.user_id
				WHERE donation.post_status = 'publish'
				AND donation_source.source_type = 'fundraiser'
				AND donation_source.source_id = %d
				AND donation_source.anonymous = 0
				GROUP BY donors.ID
				ORDER BY amount DESC
				LIMIT %d
			",
            $fundraiserId,
            $limit
        );

        return DB::get_results(
            $query,
            ARRAY_A
        );
    }

    /**
     * @param int $fundraiserId
     *
     * @return int
     */
    public function getDonorsCount($fundraiserId)
    {
        $query = DB::prepare(
            "
				SELECT count( DISTINCT donors.id ) as count
				FROM {$this->wpdb->give_p2p_donation_source} AS donation_source
				JOIN {$this->wpdb->prefix}give_donors AS donors ON donation_source.donor_id = donors.id
				JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND donation_source.source_type = 'fundraiser'
				AND donation_source.source_id = %d
				GROUP BY donation_source.source_id
		",
            $fundraiserId
        );

        return (int)DB::get_var($query);
    }

    /**
     * @param int $fundraiserId
     *
     * @return int
     */
    public function getDonationsCount($fundraiserId)
    {
        $query = DB::prepare(
            "
				SELECT count( revenue.id ) as count
				FROM {$this->wpdb->give_revenue} AS revenue
				JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
				JOIN {$this->wpdb->posts} AS donation ON donation.id = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND donation_source.source_type = 'fundraiser'
				AND donation_source.source_id = %d
				GROUP BY donation_source.source_id
		",
            $fundraiserId
        );

        return (int)DB::get_var($query);
    }

    /**
     * @param int $fundraiserId
     *
     * @return int
     */
    public function getAverageDonationAmount($fundraiserId)
    {
        $query = DB::prepare(
            "
				SELECT AVG( revenue.amount ) as amount
				FROM {$this->wpdb->give_revenue} AS revenue
				JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
				JOIN {$this->wpdb->posts} as donation ON donation.ID = donation_source.donation_id
				WHERE donation.post_status = 'publish'
				AND donation_source.source_type = 'fundraiser'
				AND donation_source.source_id = %d
				GROUP BY donation_source.source_id
		",
            $fundraiserId
        );

        return (int)DB::get_var($query);
    }

    public function getRecentlyRegisteredFundraisers()
    {
        return array_map(function ($fundraiser) {
            return Fundraiser::fromArray($fundraiser);
        },
            DB::get_results(
                "
            SELECT *
            FROM {$this->wpdb->give_p2p_fundraisers}
            WHERE DATE(date_created) >= SUBDATE(CURRENT_DATE, 1)
        ",
                ARRAY_A
            ));
    }

    /**
     * Get last registered fundraisers.
     *
     * @since 1.5.0
     *
     * @param int $limit
     *
     * @return array|Fundraiser[]
     */
    public function getLastRegisteredFundraisers(int $limit = 10)
    {
        return array_map(
            function ($fundraiser) {
                return Fundraiser::fromArray($fundraiser);
            },
            DB::get_results(
                "SELECT * FROM {$this->wpdb->give_p2p_fundraisers} ORDER BY date_created DESC LIMIT {$limit}",
                ARRAY_A
            )
        );
    }
}
