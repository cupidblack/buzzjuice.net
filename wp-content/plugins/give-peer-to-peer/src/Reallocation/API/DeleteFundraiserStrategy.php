<?php

namespace GiveP2P\Reallocation\API;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Routes\Endpoint;
use GiveP2P\Reallocation\Actions\DeleteFundraiser;
use GiveP2P\Reallocation\Actions\DeleteTeam;
use GiveP2P\Reallocation\Actions\ReallocateFundraiserDonationsToCampaign;
use GiveP2P\Reallocation\Actions\ReallocateFundraiserDonationsToTeam;
use GiveP2P\Reallocation\Actions\ReallocateTeamDonationsToCampaign;
use GiveP2P\Reallocation\Actions\PromoteFundraiserToTeamOwner;
use GiveP2P\Reallocation\StrategyEnum as Strategy;
use WP_REST_Request;
use WP_REST_Response;

class DeleteFundraiserStrategy extends Endpoint {

    /**
     * @var string
     */
    protected $endpoint = 'delete-fundraiser-strategy';

    /**
     * @param  \WP_REST_Request  $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function handleRequest( WP_REST_Request $request ) {

        $fundraiser = Fundraiser::getFundraiser( $request->get_param( 'fundraiser_id' ) );

        DB::query( "START TRANSACTION" );

        try {
            switch ($request->get_param('strategy')) {

                case Strategy::CAMPAIGN_FUNDRAISER_STRATEGY:
                    give(ReallocateFundraiserDonationsToCampaign::class)->__invoke($fundraiser);
                    break;

                case Strategy::TEAM_ONLY_FUNDRAISER_STRATEGY:
                    $team = Team::getTeam($fundraiser->getTeamId());
                    give(ReallocateFundraiserDonationsToCampaign::class)->__invoke($fundraiser);
                    give(ReallocateTeamDonationsToCampaign::class)->__invoke($team);
                    give(DeleteTeam::class)->__invoke($team);
                    break;

                case Strategy::TEAM_MEMBER_STRATEGY:
                    give(ReallocateFundraiserDonationsToTeam::class)->__invoke($fundraiser);
                    break;

                case Strategy::TEAM_OWNER_STRATEGY:
                    give(ReallocateFundraiserDonationsToTeam::class)->__invoke($fundraiser);
                    give(PromoteFundraiserToTeamOwner::class)->__invoke(
                        Fundraiser::getFundraiser($request->get_param('new_team_owner_fundraiser_id'))
                    );
                    break;
            }

            give( DeleteFundraiser::class )->__invoke( $fundraiser );

        } catch( \Exception $e ) {
            DB::query( "ROLLBACK" );
            return new \WP_Error( 500, sprintf( __( 'Error when deleting fundraiser%s', 'give-peer-to-peer' ), ': ' . $e->getMessage() ) );
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
                        'strategy' => [
                            'required' => true,
                            'validate_callback' => function ( $strategy ) {
                                return Strategy::isValid( $strategy );
                            },
                        ],
                        'fundraiser_id' => [
                            'required' => true,
                            'validate_callback' => function ( $param ) {
                                return filter_var( $param, FILTER_VALIDATE_INT );
                            },
                        ],
                        'new_team_owner_fundraiser_id' => [
                            // Use a `false` default value to force the validation callback
                            // in order to conditionally require the parameter/value based on the strategy.
                            'default' => false,
                            'validate_callback' => function ( $param, $request ) {
                                if( $request[ 'strategy' ] === Strategy::TEAM_OWNER_STRATEGY ) {
                                    return $param !== false;
                                }
                                return true;
                            },
                        ],
                    ],
                ],
            ]
        );
    }
}
