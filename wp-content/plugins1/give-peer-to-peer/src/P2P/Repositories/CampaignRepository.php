<?php

namespace GiveP2P\P2P\Repositories;

use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use Give\Log\Log;
use Give\Framework\Database\DB;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use GiveP2P\P2P\Models\Campaign;

/**
 * Class CampaignRepository
 * @package GiveP2P\P2P\Repositories
 *
 * @since 1.0.0
 */
class CampaignRepository {
    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * CampaignRepository constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get P2P Campaign
     *
     * @param  int  $id
     *
     * @return Campaign
     */
    public function getCampaign( $id ) {

        // Select campaign data
        $query = DB::prepare(
            "
			SELECT *, campaigns.id as id FROM {$this->wpdb->give_campaigns} campaigns
			JOIN {$this->wpdb->give_p2p_campaigns} p2p_campaigns
			ON campaigns.id = p2p_campaigns.campaign_id
			WHERE campaigns.id = %d
		",
            $id
        );

        $campaign = DB::get_row( $query, ARRAY_A );

        if ( ! $campaign ) {
            return null;
        }

        // Get campaign sponsors
        $campaign[ 'sponsors' ] = DB::get_results(
            DB::prepare( "SELECT * FROM {$this->wpdb->give_p2p_sponsors} WHERE campaign_id = %d", $id ),
            ARRAY_A
        );

        return Campaign::fromArray( $campaign );
    }


    /**
     * @return Campaign[]
     */
    public function getCampaigns() {
        $data = [];

        // Select campaigns
        $query = "
			SELECT *, campaigns.id as id FROM {$this->wpdb->give_campaigns} campaigns
			LEFT JOIN {$this->wpdb->give_p2p_campaigns} p2p_campaigns
			ON campaigns.id = p2p_campaigns.campaign_id
			GROUP BY campaigns.id
		";

        $campaigns = DB::get_results( $query, ARRAY_A );

        if ( ! $campaigns ) {
            return $data;
        }

        foreach ( $campaigns as $campaign ) {
            // Get campaign sponsors
            $campaign[ 'sponsors' ] = DB::get_results(
                DB::prepare( "SELECT * FROM {$this->wpdb->give_p2p_sponsors} WHERE campaign_id = %d", $campaign[ 'id' ] ),
                ARRAY_A
            );

            $data[] = Campaign::fromArray( $campaign );
        }

        return $data;
    }

    /**
     * @return Campaign[]
     */
    public function getActiveCampaigns() {

        // Select campaigns
        $query = "
			SELECT *, campaigns.id as id FROM {$this->wpdb->give_campaigns} campaigns
			    RIGHT JOIN {$this->wpdb->give_p2p_campaigns} p2p_campaigns
			        ON campaigns.id = p2p_campaigns.campaign_id
			WHERE campaigns.status = 'active'
			GROUP BY campaigns.id
		";

        $campaigns = DB::get_results( $query, ARRAY_A );

        return array_map( function ( $campaign ) {
            return Campaign::fromArray( $campaign );
        }, $campaigns );
    }


    /**
     * @return Campaign[]
     */
    public function getCampaignsForRequest( WP_REST_Request $request ) {
        $data          = [];
        $status        = $request->get_param( 'status' );
        $page          = $request->get_param( 'page' );
        $perPage       = $request->get_param( 'per_page' );
        $sortBy        = $request->get_param( 'sort' );
        $sortDirection = $request->get_param( 'direction' );

        $offset = ( $page - 1 ) * $perPage;

        // Select campaigns
        $query = "
			SELECT *, campaigns.id as id FROM {$this->wpdb->give_campaigns} campaigns
			LEFT JOIN {$this->wpdb->give_p2p_campaigns} p2p_campaigns
			ON campaigns.id = p2p_campaigns.campaign_id
		";

        if ( $status && 'all' !== $status ) {
            $query .= sprintf( ' WHERE status = "%s"', esc_sql( $status ) );
        }

        $query .= ' GROUP BY campaigns.id';

        if ( $sortBy ) {
            $direction = ( $sortDirection && strtoupper( $sortDirection ) === 'ASC' ) ? 'ASC' : 'DESC';

            $query .= sprintf( ' ORDER BY %s %s', esc_sql( $sortBy ), $direction );
        } else {
            $query .= ' ORDER BY campaigns.id DESC';
        }

        // Limit
        $query .= sprintf( ' LIMIT %d', $perPage );

        // Offset
        if ( $offset > 1 ) {
            $query .= sprintf( ' OFFSET %d', $offset );
        }

        //echo $query; exit;

        $campaigns = DB::get_results( $query, ARRAY_A );

        if ( ! $campaigns ) {
            return $data;
        }

        foreach ( $campaigns as $campaign ) {
            // Get campaign sponsors
            $campaign[ 'sponsors' ] = DB::get_results(
                DB::prepare( "SELECT * FROM {$this->wpdb->give_p2p_sponsors} WHERE campaign_id = %d", $campaign[ 'id' ] ),
                ARRAY_A
            );

            $data[] = Campaign::fromArray( $campaign );
        }

        return $data;
    }

    /**
     * @param  WP_REST_Request  $request
     *
     * @return int
     */
    public function getCampaignsCountForRequest( WP_REST_Request $request ) {
        $status = $request->get_param( 'status' );
        $query  = "SELECT count(id) FROM {$this->wpdb->give_campaigns}";

        if ( $status && 'all' !== $status ) {
            $query .= sprintf( ' WHERE status = "%s"', esc_sql( $status ) );
        }

        return (int) DB::get_var( $query );
    }

    /**
     * @param  string  $slug
     *
     * @return Campaign
     */
    public function getCampaignBySlug( $slug ) {
        // Select campaign data by slug
        $query = DB::prepare(
            "
			SELECT *, campaigns.id as id FROM {$this->wpdb->give_campaigns} campaigns
			JOIN {$this->wpdb->give_p2p_campaigns} p2p_campaigns
			ON campaigns.id = p2p_campaigns.campaign_id
			WHERE campaigns.campaign_url = %s
		",
            $slug
        );

        $campaign = DB::get_row( $query, ARRAY_A );

        if ( ! $campaign ) {
            return null;
        }

        // Get campaign sponsors
        $campaign[ 'sponsors' ] = 'enabled' == $campaign[ 'sponsors_enabled' ]
            ? DB::get_results(
                DB::prepare( "SELECT * FROM {$this->wpdb->give_p2p_sponsors} WHERE campaign_id = %d", $campaign[ 'id' ] ),
                ARRAY_A
            )
            : [];

        return Campaign::fromArray( $campaign );
    }


    /**
     * @param  string  $slug
     *
     * @return bool
     */
    public function campaignSlugExist( $slug ) {
        $query = DB::prepare( "SELECT campaign_url FROM {$this->wpdb->give_campaigns} WHERE campaign_url = %s", $slug );

        return (bool) DB::get_var( $query );
    }

    /**
     * Insert campaign
     *
     * @param  Campaign  $campaign
     *
     * @return int|false
     */
    public function insertCampaign( Campaign $campaign ) {
        global $wp_embed;

        $this->wpdb->query( 'START TRANSACTION' );

        try {
            // Check if campaign slug is already in use
            $campaignSlug = sanitize_title( $campaign->get( 'campaign_title' ) );

            if ( $this->campaignSlugExist( $campaignSlug ) ) {
                $campaignSlug = 'campaign-' . $campaignSlug;
            }

            // Insert campaign
            DB::insert(
                $this->wpdb->give_campaigns,
                [
                    'form_id'         => $campaign->get( 'form_id' ),
                    'campaign_title'  => $campaign->get( 'campaign_title' ),
                    'campaign_url'    => $campaignSlug,
                    'short_desc'      => $campaign->get( 'short_desc' ),
                    'long_desc'       => $wp_embed->autoembed( $campaign->get( 'long_desc' ) ),
                    'campaign_logo'   => $campaign->get( 'campaign_logo' ),
                    'campaign_image'  => $campaign->get( 'campaign_image' ),
                    'primary_color'   => $campaign->get( 'primary_color' ),
                    'secondary_color' => $campaign->get( 'secondary_color' ),
                    'campaign_goal'   => $campaign->get( 'campaign_goal' ),
                    'status'          => $campaign->get( 'status' ),
                    'date_created'    => date( 'Y-m-d H:i:s' ),
                ],
                null
            );

            $campaignId = DB::last_insert_id();

            // Insert P2P campaign data
            DB::insert(
                $this->wpdb->give_p2p_campaigns,
                [
                    'campaign_id'                        => $campaignId,
                    'sponsors_enabled'                   => $campaign->get( 'sponsors_enabled' ),
                    'sponsor_linking'                    => $campaign->get( 'sponsor_linking' ),
                    'sponsor_section_heading'            => $campaign->get( 'sponsor_section_heading' ),
                    'sponsor_application_page'           => $campaign->get( 'sponsor_application_page' ),
                    'sponsors_display'                   => $campaign->get( 'sponsors_display' ),
                    'fundraiser_approvals'               => $campaign->get( 'fundraiser_approvals' ),
                    'fundraiser_approvals_email_subject' => $campaign->get( 'fundraiser_approvals_email_subject' ),
                    'fundraiser_approvals_email_body'    => $campaign->get( 'fundraiser_approvals_email_body' ),
                    'fundraiser_goal'                    => $campaign->get( 'fundraiser_goal' ),
                    'fundraiser_story_placeholder'       => $campaign->get( 'fundraiser_story_placeholder' ),
                    'teams_registration'                 => $campaign->get( 'teams_registration' ),
                    'team_approvals'                     => $campaign->get( 'team_approvals' ),
                    'team_approvals_email_subject'       => $campaign->get( 'team_approvals_email_subject' ),
                    'team_approvals_email_body'          => $campaign->get( 'team_approvals_email_body' ),
                    'team_goal'                          => $campaign->get( 'team_goal' ),
                    'team_story_placeholder'             => $campaign->get( 'team_story_placeholder' ),
                    'team_rankings'                      => $campaign->get( 'team_rankings' ),
                    'registration_digest'                => $campaign->get( 'registration_digest' ),
                ],
                null
            );

            // Insert sponsors
            foreach ( $campaign->getSponsors() as $sponsor ) {
                // Skip empty
                if ( empty( $sponsor->get( 'sponsor_name' ) ) ) {
                    continue;
                }

                DB::insert(
                    $this->wpdb->give_p2p_sponsors,
                    [
                        'campaign_id'   => $campaignId,
                        'sponsor_name'  => $sponsor->get( 'sponsor_name' ),
                        'sponsor_url'   => $sponsor->get( 'sponsor_url' ),
                        'sponsor_image' => $sponsor->get( 'sponsor_image' ),
                    ],
                    null
                );
            }

            $this->wpdb->query( 'COMMIT' );

            return $campaignId;

        } catch ( DatabaseQueryException $e ) {
            $this->wpdb->query( 'ROLLBACK' );

            Log::error(
                'Failed to insert new P2P campaign',
                [
                    'category'      => 'Peer-to-Peer',
                    'source'        => 'Peer-to-Peer Add-on',
                    'Campaign'      => $campaign->toArray(),
                    'Error Message' => $e->getMessage(),
                    'Query Errors'  => $e->getQueryErrors(),
                ]
            );

            return false;
        }
    }

    /** Save campaign
     *
     * @param  Campaign  $campaign
     *
     * @return bool
     */
    public function saveCampaign( Campaign $campaign ) {
        global $wp_embed;

        $this->wpdb->query( 'START TRANSACTION' );

        try {
            // Insert campaign
            DB::update(
                $this->wpdb->give_campaigns,
                [
                    'form_id'         => $campaign->get( 'form_id' ),
                    'campaign_title'  => $campaign->get( 'campaign_title' ),
                    'campaign_url'    => $campaign->get( 'campaign_url' ),
                    'short_desc'      => $campaign->get( 'short_desc' ),
                    'long_desc'       => $wp_embed->autoembed( $campaign->get( 'long_desc' ) ),
                    'campaign_logo'   => $campaign->get( 'campaign_logo' ),
                    'campaign_image'  => $campaign->get( 'campaign_image' ),
                    'primary_color'   => $campaign->get( 'primary_color' ),
                    'secondary_color' => $campaign->get( 'secondary_color' ),
                    'campaign_goal'   => $campaign->get( 'campaign_goal' ),
                    'status'          => $campaign->get( 'status' ),
                ],
                [
                    'id' => $campaign->getId(),
                ]
            );

            // Insert P2P campaign data
            DB::update(
                $this->wpdb->give_p2p_campaigns,
                [
                    'sponsors_enabled'                   => $campaign->get( 'sponsors_enabled' ),
                    'sponsor_linking'                    => $campaign->get( 'sponsor_linking' ),
                    'sponsor_section_heading'            => $campaign->get( 'sponsor_section_heading' ),
                    'sponsor_application_page'           => $campaign->get( 'sponsor_application_page' ),
                    'sponsors_display'                   => $campaign->get( 'sponsors_display' ),
                    'fundraiser_approvals'               => $campaign->get( 'fundraiser_approvals' ),
                    'fundraiser_approvals_email_subject' => $campaign->get( 'fundraiser_approvals_email_subject' ),
                    'fundraiser_approvals_email_body'    => $campaign->get( 'fundraiser_approvals_email_body' ),
                    'fundraiser_goal'                    => $campaign->get( 'fundraiser_goal' ),
                    'fundraiser_story_placeholder'       => $campaign->get( 'fundraiser_story_placeholder' ),
                    'teams_registration'                 => $campaign->get( 'teams_registration' ),
                    'team_approvals'                     => $campaign->get( 'team_approvals' ),
                    'team_approvals_email_subject'       => $campaign->get( 'team_approvals_email_subject' ),
                    'team_approvals_email_body'          => $campaign->get( 'team_approvals_email_body' ),
                    'team_goal'                          => $campaign->get( 'team_goal' ),
                    'team_story_placeholder'             => $campaign->get( 'team_story_placeholder' ),
                    'team_rankings'                      => $campaign->get( 'team_rankings' ),
                    'registration_digest'                => $campaign->get( 'registration_digest' ),
                ],
                [
                    'campaign_id' => $campaign->getId(),
                ]
            );

            // Delete sponsors
            DB::delete(
                $this->wpdb->give_p2p_sponsors,
                [
                    'campaign_id' => $campaign->getId(),
                ],
                null
            );

            // Insert sponsors
            foreach ( $campaign->getSponsors() as $sponsor ) {
                // Skip empty
                if ( empty( $sponsor->get( 'sponsor_name' ) ) ) {
                    continue;
                }

                DB::insert(
                    $this->wpdb->give_p2p_sponsors,
                    [
                        'campaign_id'   => $campaign->getId(),
                        'sponsor_name'  => $sponsor->get( 'sponsor_name' ),
                        'sponsor_url'   => $sponsor->get( 'sponsor_url' ),
                        'sponsor_image' => $sponsor->get( 'sponsor_image' ),
                    ],
                    null
                );
            }

            $this->wpdb->query( 'COMMIT' );

            return true;

        } catch ( DatabaseQueryException $e ) {
            $this->wpdb->query( 'ROLLBACK' );

            Log::error(
                'Failed to save P2P campaign',
                [
                    'category'      => 'Peer-to-Peer',
                    'source'        => 'Peer-to-Peer Add-on',
                    'Campaign'      => $campaign->toArray(),
                    'Error Message' => $e->getMessage(),
                    'Query Errors'  => $e->getQueryErrors(),
                ]
            );

            return false;
        }
    }

    /**
     * @param  int  $campaignId
     */
    public function getRaisedAmount( $campaignId ) {
        $queryTemplate = "
			SELECT SUM( revenue.amount )
			FROM {$this->wpdb->give_revenue} AS revenue
			JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
			JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			WHERE donation.post_status = 'publish'
            %s
		";

        $campaignAmount = (int) DB::get_var( sprintf( $queryTemplate, "
            AND donation_source.source_type = 'campaign'
            AND donation_source.source_id IN (
                SELECT id FROM {$this->wpdb->give_campaigns} WHERE id = {$campaignId}
            )
        ") );

        $campaignTeamsAmount = (int) DB::get_var( sprintf( $queryTemplate, "
            AND donation_source.source_type = 'team'
            AND donation_source.source_id IN (
                SELECT id FROM {$this->wpdb->give_p2p_teams} WHERE campaign_id = {$campaignId}
            )
        ") );

        $campaignFundraisersAmount = (int) DB::get_var( sprintf( $queryTemplate, "
            AND donation_source.source_type = 'fundraiser'
            AND donation_source.source_id IN (
                SELECT id FROM {$this->wpdb->give_p2p_fundraisers}  WHERE campaign_id = {$campaignId}
            )
        ") );

        return $campaignAmount + $campaignTeamsAmount + $campaignFundraisersAmount;
    }

    /**
     * @param  int  $campaignId
     *
     * @return int
     */
    public function getDonationsCount( $campaignId ) {
        return (int) DB::get_var(
            "
			SELECT count(source_id)
			FROM {$this->wpdb->give_p2p_donation_source} AS donation_source
			JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			WHERE donation.post_status = 'publish'
			AND source_id IN(
			  SELECT id as campaigns_ids FROM {$this->wpdb->give_campaigns} WHERE id = {$campaignId}
			  UNION SELECT id as teams_ids FROM {$this->wpdb->give_p2p_teams} WHERE campaign_id = {$campaignId}
			  UNION SELECT id as fundraisers_ids FROM {$this->wpdb->give_p2p_fundraisers}  WHERE campaign_id = {$campaignId}
		    )
		" );
    }

    /**
     * @since 1.0.0
     * @since 1.4.0 Limit count to active fundraisers
     *
     * @param  int  $campaignId
     *
     * @return int
     */
    public function getFundraisersCount( $campaignId ) {
        $query = DB::prepare(
            "
			SELECT count(id) FROM {$this->wpdb->give_p2p_fundraisers}
			WHERE campaign_id = %d
            AND status = %s
		",
            $campaignId,
            Status::ACTIVE
        );

        return (int) DB::get_var( $query );
    }

    /**
     * @param  int  $campaignId
     *
     * @return int
     */
    public function getTeamsCount( $campaignId ) {
        $query = DB::prepare(
            "
			SELECT count(id) FROM {$this->wpdb->give_p2p_teams}
			WHERE campaign_id = %d
			AND status = %s
		",
            $campaignId,
            Status::ACTIVE
        );

        return (int) DB::get_var( $query );
    }

    /**
     * @since 1.0.0
     *
     * @param  int  $campaignId
     * @param  int  $limit
     *
     * @return array
     */
    public function getTopDonors( $campaignId, $limit ) {

        $query = DB::prepare(
            "
				SELECT SUM(revenue_amount) as amount, source_id, donor_id, name
				FROM (
					{$this->getCampaignDonorsSubquery()}
					UNION ALL
					{$this->getCampaignTeamDonorsSubquery()}
					UNION ALL
					{$this->getCampaignFundraiserDonorsSubquery()}
				) as campaign_donations
				GROUP BY donor_id
				ORDER BY amount DESC
				LIMIT %d
			",
            $campaignId,
            $campaignId,
            $campaignId,
            $limit
        );

        return DB::get_results(
            $query,
            ARRAY_A
        );
    }

    private function getCampaignDonorsSubquery() {
        return "
			SELECT revenue.amount as revenue_amount, donation_source.source_id as source_id, donors.id as donor_id, COALESCE(donors.name, wp_users.display_name) as name
			FROM {$this->wpdb->give_revenue} AS revenue
			JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
			JOIN {$this->wpdb->prefix}give_donors AS donors ON donation_source.donor_id = donors.id
			LEFT JOIN {$this->wpdb->users} AS wp_users ON wp_users.ID = donors.user_id
			JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			WHERE donation.post_status = 'publish'
			AND donation_source.source_type = 'campaign'
			AND donation_source.source_id = %d
            AND donation_source.anonymous = 0
		";
    }

    private function getCampaignTeamDonorsSubquery() {
        return "
			SELECT revenue.amount as revenue_amount, donation_source.source_id as source_id, donors.id as donor_id, COALESCE(donors.name, wp_users.display_name) as name
			FROM {$this->wpdb->give_revenue} AS revenue
			JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
			JOIN {$this->wpdb->prefix}give_donors AS donors ON donation_source.donor_id = donors.id
			JOIN {$this->wpdb->give_p2p_teams} AS teams ON donation_source.source_id = teams.id
			LEFT JOIN {$this->wpdb->users} AS wp_users ON wp_users.ID = donors.user_id
            JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			WHERE donation.post_status = 'publish'
			AND donation_source.source_type = 'team'
			AND teams.campaign_id = %d
            AND donation_source.anonymous = 0
		";
    }

    private function getCampaignFundraiserDonorsSubquery() {
        return "
			SELECT revenue.amount as revenue_amount, donation_source.source_id as source_id, donors.id as donor_id, COALESCE(donors.name, wp_users.display_name) as name
			FROM {$this->wpdb->give_revenue} AS revenue
			JOIN {$this->wpdb->give_p2p_donation_source} AS donation_source ON revenue.donation_id = donation_source.donation_id
			JOIN {$this->wpdb->prefix}give_donors AS donors ON donation_source.donor_id = donors.id
			JOIN {$this->wpdb->give_p2p_fundraisers} AS fundraisers ON donation_source.source_id = fundraisers.id
			LEFT JOIN {$this->wpdb->users} AS wp_users ON wp_users.ID = donors.user_id
            JOIN {$this->wpdb->posts} AS donation ON donation.ID = donation_source.donation_id
			WHERE donation.post_status = 'publish'
			AND donation_source.source_type = 'fundraiser'
			AND fundraisers.campaign_id = %d
            AND donation_source.anonymous = 0
		";
    }
}
