<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Facades\Campaign;
use WP_REST_Request;
use WP_REST_Response;

class GetCampaign extends Endpoint
{
    /**
     * @var string
     */
    protected $endpoint = 'get-campaign';

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
                    'methods' => 'GET',
                    'callback' => [$this, 'handleRequest'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'campaignId' => [
                            'type' => 'integer',
                            'required' => true
                        ]
                    ],
                ]
            ]
        );
    }

    /**
     * @since 1.4.0
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response
    {
        $campaign = Campaign::getCampaign($request->get_param('campaignId'));

        if ( ! $campaign) {
            return new WP_REST_Response(
                [
                    'status' => false,
                    'message' => __(
                        'Campaign does not exist.',
                        'give-peer-to-peer'
                    ),
                ],
                404
            );
        }

        return new WP_REST_Response(
            [
                'status' => true,
                'data' => [
                    'title'             => $campaign->getTitle(),
                    'goal'              => Money::ofMinor($campaign->getGoal(), give_get_option('currency') )->getAmount(),
                    'story'             => $campaign->getLongDescription(),
                    'url'               =>  '/campaign/' . $campaign->getUrl(),
                    'amount'            =>  Money::ofMinor(Campaign::getRaisedAmount($request->get_param('campaignId')), give_get_option('currency') )->getAmount(),
                    'fundraiser_total'  => Campaign::getFundraisersCount($request->get_param('campaignId')),
                    'team_total'        => Campaign::getTeamsCount($request->get_param('campaignId')),
                    'campaign_logo'     => $campaign->getLogo(),
                ]
            ]
        );
    }

}
