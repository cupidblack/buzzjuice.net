<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\Email\notifications\DonationIndividualFundraiser;
use GiveP2P\Email\notifications\DonationTeamCaptain;
use GiveP2P\Email\notifications\DonationTeamFundraiser;
use GiveP2P\Email\notifications\TeamFundraiserJoined;
use WP_REST_Request;
use WP_REST_Response;

class GetAdminEmailsRoute extends Endpoint
{
    public function __construct( ) {
    }

    /**
     * @var string
     */
    protected $endpoint = 'get-email-settings';


    /**
     * @inheritDoc
     */
    public function registerRoute() {
        register_rest_route(
            parent::ROUTE_NAMESPACE,
            $this->endpoint,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'handleRequest' ],
                    'permission_callback' => '__return_true',
                    'args'                => [],
                ],
                'schema' => [ $this, 'getSchema' ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getSchema() {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'team',
            'type'       => 'object',
            'permission_callback' => 'is_user_logged_in',
        ];
    }

    public function getDonationIndividualFundraiserStatus()
    {
        $status = DonationIndividualFundraiser::get_instance()->get_notification_status();
        return $status === 'enabled';
    }

    public function getDonationTeamCaptainStatus()
    {
        $status = DonationTeamCaptain::get_instance()->get_notification_status();

        return $status === 'enabled';
    }

    public function getTeamFundraiserJoinedStatus()
    {
        $status = TeamFundraiserJoined::get_instance()->get_notification_status();

        return $status === 'enabled';
    }

    public function getDonationTeamFundraiserStatus()
    {
        $status = DonationTeamFundraiser::get_instance()->get_notification_status();

        return $status === 'enabled';
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest()
    {
        if (wp_get_current_user()) {
            return new WP_REST_Response([
                'data' => [
                    'donation_individual_fundraiser' => $this->getDonationIndividualFundraiserStatus(),
                    'donation_team_fundraiser' => $this->getDonationTeamFundraiserStatus(),
                    'team_fundraiser_joined' => $this->getTeamFundraiserJoinedStatus(),
                    'donation_team_captain' => $this->getDonationTeamCaptainStatus(),
                ],
            ]);
        }

        return new WP_REST_Response(
            [
                'error' => 'not_found',
                'message' => __('User not found', 'give-peer-to-peer'),
            ],
            404
        );
    }
}
