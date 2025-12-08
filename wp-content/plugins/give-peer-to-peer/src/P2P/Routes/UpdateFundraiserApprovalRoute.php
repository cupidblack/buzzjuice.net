<?php

namespace GiveP2P\P2P\Routes;

use Give\Helpers\Hooks;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @package GiveP2P\P2P\Routes
 *
 * @since   1.0.0
 */
class UpdateFundraiserApprovalRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'update-fundraiser-approval';

    /**
     * @param TeamRepository       $teamRepository
     * @param FundraiserRepository $fundraiserRepository
     * @param CampaignRepository   $campaignRepository
     */
    public function __construct(
        TeamRepository $teamRepository,
        FundraiserRepository $fundraiserRepository,
        CampaignRepository $campaignRepository
    ) {
        $this->teamRepository = $teamRepository;
        $this->fundraiserRepository = $fundraiserRepository;
        $this->campaignRepository = $campaignRepository;
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
                        'fundraiser_id' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Fundraiser ID', 'give-peer-to-peer'),
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                        ],
                        'status' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );
    }


    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest(WP_REST_Request $request)
    {
        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->give_p2p_fundraisers,
            [
                'status' => $request->get_param('status'),
            ],
            [
                'id' => $request->get_param('fundraiser_id'),
            ]
        );

        $fundraiser = $this->fundraiserRepository->getFundraiser($request->get_param('fundraiser_id'));
        $campaign = $this->campaignRepository->getCampaign($fundraiser->get('campaign_id'));

        /**
         * @since 1.5.0
         */
        Hooks::doAction('give_p2p_fundraiser_approved', $fundraiser, $campaign);

        if ( ! $updated) {
            return new WP_REST_Response(
                [
                    'error' => 'update_failed',
                    'message' => __('Something went wrong', 'give-peer-to-peer'),
                ],
                400
            );
        }

        return new WP_REST_Response(
            ['message' => __('Fundraiser updated', 'give-peer-to-peer')]
        );
    }

}
