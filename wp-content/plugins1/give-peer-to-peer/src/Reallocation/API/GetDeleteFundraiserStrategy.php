<?php

namespace GiveP2P\Reallocation\API;

use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Routes\Endpoint;
use GiveP2P\Reallocation\StrategyEnum;
use WP_REST_Request;
use WP_REST_Response;

class GetDeleteFundraiserStrategy extends Endpoint {

    /**
     * @var string
     */
    protected $endpoint = 'get-delete-fundraiser-strategy';

    /**
     * @param  WP_REST_Request  $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest( WP_REST_Request $request ) {

        $fundraiser = Fundraiser::getFundraiser( $request->get_param( 'fundraiser_id' ) );

        if( ! $fundraiser->getTeamId() ) {
            return new WP_REST_Response([
                'strategy' => StrategyEnum::CAMPAIGN_FUNDRAISER_STRATEGY,
            ]);
        }

        $teamFundraisers = give( FundraiserRepository::class )->getTeamFundraisers( $fundraiser->getTeamId() );

        if( 1 === count( $teamFundraisers ) ) {
            return new WP_REST_Response([
                'strategy' => StrategyEnum::TEAM_ONLY_FUNDRAISER_STRATEGY,
            ]);
        }

        $team = Team::getTeam( $fundraiser->getTeamId() );

        if( $fundraiser->getId() === $team->get( 'owner_id' ) ) {
            return new WP_REST_Response([
                'strategy' => StrategyEnum::TEAM_OWNER_STRATEGY,
            ]);
        }

        return new WP_REST_Response([
            'strategy' => StrategyEnum::TEAM_MEMBER_STRATEGY,
        ]);
    }

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
                    'permission_callback' => [ $this, 'permissionsCheck' ],
                    'args'                => [
                        'fundraiser_id' => [
                            'validate_callback' => function ( $param ) {
                                return filter_var( $param, FILTER_VALIDATE_INT );
                            },
                        ],
                    ],
                ],
            ]
        );
    }
}
