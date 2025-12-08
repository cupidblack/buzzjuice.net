<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Facades\Fundraiser;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetCampaignFundraisersSearch extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'get-campaign-fundraisers-search';

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
                        'campaign_id' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Campaign ID', 'give-peer-to-peer'),
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                        ],
                        'search' => [
                            'required' => false,
                            'type' => 'string',
                            'description' => esc_html__('Search text', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return sanitize_text_field($param);
                            },
                        ],
                        'page' => [
                            'required' => false,
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                            'default' => 1,
                            'description' => esc_html__('Current page', 'give-peer-to-peer'),
                        ],
                        'per_page' => [
                            'required' => false,
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                            'default' => 9,
                            'description' => esc_html__('Items per page', 'give-peer-to-peer'),
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
        $fundraisers = Fundraiser::getCampaignFundraisersSearch(
            $request->get_param('campaign_id'),
            $request->get_param('search'),
            $limit = $request->get_param('per_page'),
            $offset = (($request->get_param('page') - 1) * $request->get_param('per_page')));

        $totalFundraisersCount = Fundraiser::getCampaignFundraiserSearchCount(
            $request->get_param('campaign_id'),
            $request->get_param('search'),
            $request->get_param('showClosedTeams') ?: false);

        return new WP_REST_Response(
            [
                'data' => array_map(function ($fundraiser) {
                    $fundraiser['goal'] = Money::ofMinor($fundraiser['goal'], give_get_option('currency'))->getAmount();
                    $fundraiser[ 'amount' ] = Money::ofMinor( $fundraiser[ 'amount' ], give_get_option( 'currency' ) )->getAmount();
                    return $fundraiser;
                }, $fundraisers),
                'count' => count($fundraisers),
                'total' => $totalFundraisersCount,
            ]
        );
	}

}
