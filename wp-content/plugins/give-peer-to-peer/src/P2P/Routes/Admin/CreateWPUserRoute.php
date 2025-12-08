<?php

namespace GiveP2P\P2P\Routes\Admin;

use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Routes\Endpoint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This function handles requests on 'admin-add-wp-user' rest api endpoint.
 *
 * @since 1.4.0
 */
class CreateWPUserRoute extends Endpoint
{
    /**
     * @var string
     */
    protected $endpoint = 'admin-add-wp-user';

    /**
     * @var FundraiserRepository
     */
    private $fundraiserRepository;

    /**
     * @since 1.4.0
     */
    public function __construct(FundraiserRepository $fundraiserRepository)
    {
        $this->fundraiserRepository = $fundraiserRepository;
    }

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
                        'firstName' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('First Name', 'give-peer-to-peer'),
                        ],
                        'lastName' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Last Name', 'give-peer-to-peer'),
                        ],
                        'email' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Email', 'give-peer-to-peer'),
                            'format' => 'email'
                        ]
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

        $user = get_user_by_email($request->get_param('email'));

        if (!$user) {
            $userId = wp_insert_user(
                [
                    'user_login' => $request->get_param('email'),
                    'user_email' => $request->get_param('email'),
                    'first_name' => $request->get_param('firstName'),
                    'last_name' => $request->get_param('lastName'),
                    'user_pass' => null
                ]
            );

            if (is_wp_error($userId)) {
                return new WP_REST_Response(
                    [
                        'error' => 'registration_failed',
                        'message' => $userId->get_error_message()
                    ],
                    400
                );
            }

            $user = get_user_by('ID', $userId);
            wp_send_new_user_notifications($user->ID);
        }

        $fundraiser = Fundraiser::getFundraiser(
            $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId($user->ID, $campaign->getId())
        );

        if ($fundraiser) {
            return new WP_REST_Response(
                [
                    'status' => 'fundraiser_found',
                    'message' => __(
                        'Fundraiser is already exist in this campaign with this user email address.',
                        'give-peer-to-peer'
                    ),
                ],
                404
            );
        }

        return new WP_REST_Response(['userId' => $user->ID]);
    }
}
