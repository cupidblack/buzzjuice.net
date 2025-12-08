<?php

namespace GiveP2P\P2P\Routes\Admin;

use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Routes\Endpoint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This class use to handle rest api requests for "admin-update-team-captain" endpoint.
 *
 * @since 1.4.0
 */
class UpdateTeamCaptainRoute extends Endpoint
{
    /**
     * @var string
     */
    protected $endpoint = 'admin-update-team-captain';

    /**
     * @inheritDoc
     */
    public function registerRoute()
    {
        register_rest_route(
            parent::ROUTE_NAMESPACE,
            $this->endpoint,
            [
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'handleRequest'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                    'args' => [
                        'campaignId' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Campaign ID', 'give-peer-to-peer'),
                        ],
                        'teamId' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Team ID', 'give-peer-to-peer'),
                        ],
                        'captain' => [
                            'required' => true,
                            'type' => 'number',
                            'description' => esc_html__('Team Captain', 'give-peer-to-peer'),
                        ],
                    ],
                ],
            ]
        );
    }


    /**
     * @since 1.4.0
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $campaign = Campaign::getCampaign($request->get_param('campaignId'));
        if (!$campaign) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_campaign',
                    'message' => __('Campaign you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $team = Team::getTeam($request->get_param('teamId'));
        if (!$team) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_team',
                    'message' => __('Team you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $fundraiser = Fundraiser::getFundraiser($request->get_param('captain'));
        $teamCaptain = Fundraiser::getFundraiser($team->getOwnerId());

        if (!$fundraiser) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_fundraiser',
                    'message' => __(
                        'Fundraiser you are trying to assign as team captain does not exist',
                        'give-peer-to-peer'
                    ),
                ],
                404
            );
        }

        if ( $teamCaptain && $fundraiser->getId() === $teamCaptain->getId()) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_fundraiser',
                    'message' => __(
                        'Selected Fundraiser is captain of this team',
                        'give-peer-to-peer'
                    ),
                ],
                404
            );
        }

        $wpdb->query('START TRANSACTION');

        try {
            // Remove team captain.
            if( $teamCaptain ) {
                \GiveP2P\P2P\Models\Fundraiser::fromArray(
                    array_merge(
                        $teamCaptain->toArray(),
                        ['team_captain' => 0],
                    )
                )->save();
            }

            // Add new team captain.
            \GiveP2P\P2P\Models\Fundraiser::fromArray(
                array_merge(
                    $fundraiser->toArray(),
                    ['team_captain' => 1],
                )
            )->save();

            \GiveP2P\P2P\Models\Team::fromArray(
                array_merge(
                    $team->toArray(),
                    ['owner_id' => $fundraiser->getId()],
                )
            )->save();
        } catch (\Exception $exception) {
            $wpdb->query('ROLLBACK');

            return new WP_REST_Response(
                [
                    'status' => 'unable_update_team_captain',
                    'message' => $exception->getMessage(),
                ],
                404
            );
        }

        $wpdb->query('COMMIT');

        return new WP_REST_Response(['teamId' => $team->getId()]);
    }
}
