<?php

namespace GiveP2P\Reallocation\API;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Routes\Endpoint;
use GiveP2P\Reallocation\Actions\DeleteTeam;
use GiveP2P\Reallocation\Actions\DeleteTeamFundraisers;
use GiveP2P\Reallocation\Actions\ReallocateTeamDonationsToCampaign;
use GiveP2P\Reallocation\Actions\ReallocateTeamFundraisersDonationsToCampaign;
use WP_REST_Request;
use WP_REST_Response;

class DeleteTeamStrategy extends Endpoint {

    /**
     * @var string
     */
    protected $endpoint = 'delete-team-strategy';

    /**
     * @param  WP_REST_Request  $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest( WP_REST_Request $request ) {

        $team = Team::getTeam( $request->get_param( 'team_id' ) );

        DB::query( "START TRANSACTION" );

        try {
            give(ReallocateTeamFundraisersDonationsToCampaign::class)->__invoke($team);
            give(DeleteTeamFundraisers::class)->__invoke($team);
            give(ReallocateTeamDonationsToCampaign::class)->__invoke($team);
            give(DeleteTeam::class)->__invoke($team);
        } catch( \Exception $e ) {
            DB::query( "ROLLBACK" );
            return new \WP_Error( 500, sprintf( __( 'Error when deleting team%s', 'give-peer-to-peer' ), ': ' . $e->getMessage() ) );
        }

        DB::query( "COMMIT" );

        return new WP_REST_Response([
            'strategy' => $request->get_param( 'strategy' ),
            'fundraiser' => $request->get_param( 'fundraiser_id' ),
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
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'handleRequest' ],
                    'permission_callback' => [ $this, 'permissionsCheck' ],
                    'args'                => [
                        'team_id' => [
                            'required' => true,
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
