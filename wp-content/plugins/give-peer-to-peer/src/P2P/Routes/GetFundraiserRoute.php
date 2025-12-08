<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\ViewModels\FundraiserViewModel;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class GetTeamFundraiserRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.4.0
 */
class GetFundraiserRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'get-fundraiser';

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
                    'permission_callback' => [$this, 'permissionsCheck'],
                    'args' => [
                        'fundraiserId' => [
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
        $fundraiser = Fundraiser::getFundraiser($request->get_param('fundraiserId'));

        if (!$fundraiser) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_fundraiser',
                    'message' => __(
                        'Fundraiser does not exist.',
                        'give-peer-to-peer'
                    ),
                ],
                404
            );
        }

        return new WP_REST_Response(
            [
                'data' => (
                    new FundraiserViewModel(
                        $fundraiser,
                        give(FundraiserRepository::class),
                        get_user_by('ID', $fundraiser->getUserId())
                    )
                )->exports()
            ]
        );
    }

}
