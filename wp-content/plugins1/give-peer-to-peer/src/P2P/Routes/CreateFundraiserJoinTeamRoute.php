<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.2.0
 */
class CreateFundraiserJoinTeamRoute extends Endpoint {

    /**
     * @var string
     */
    protected $endpoint = 'create-fundraiser-join-team';
    /**
     * @var Fundraiser
     */
    private $fundraiser;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;


    /**
     * @param  Fundraiser  $fundraiser
     * @param  CampaignRepository  $campaignRepository
     */
    public function __construct(
        Fundraiser $fundraiser,
        CampaignRepository $campaignRepository
    ) {
        $this->fundraiser         = $fundraiser;
        $this->campaignRepository = $campaignRepository;
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
                    'methods'  => 'POST',
                    'callback' => [ $this, 'handleRequest' ],
                    'permission_callback' => '__return_true',
                    'args'     => [
                        'campaignId' => [
                            'required'          => true,
                            'type'              => 'integer',
                            'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
                            'validate_callback' => function ( $param ) {
                                return filter_var( $param, FILTER_VALIDATE_INT );
                            },
                        ],
                        'teamId'     => [
                            'required'          => true,
                            'type'              => 'integer',
                            'description'       => esc_html__( 'Team ID', 'give-peer-to-peer' ),
                            'validate_callback' => function ( $param ) {
                                return filter_var( $param, FILTER_VALIDATE_INT );
                            },
                        ],
                        'firstName'  => [
                            'required'          => true,
                            'type'              => 'string',
                            'description'       => esc_html__( 'Fundraiser first name', 'give-peer-to-peer' ),
                            'sanitize_callback' => function ( $param ) {
                                return filter_var( $param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
                            },
                        ],
                        'lastName'   => [
                            'required'          => true,
                            'type'              => 'string',
                            'description'       => esc_html__( 'Fundraiser last name', 'give-peer-to-peer' ),
                            'sanitize_callback' => function ( $param ) {
                                return filter_var( $param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
                            },
                        ],
                    ],
                ],
            ]
        );
    }


    /**
     * @param  WP_REST_Request  $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest( WP_REST_Request $request ) {
        // Check Campaign
        if ( ! $campaign = $this->campaignRepository->getCampaign( $request->get_param( 'campaignId' ) ) ) {
            return new WP_REST_Response(
                [
                    'status'  => 'invalid_campaign',
                    'message' => __( 'Campaign you are trying to join does not exist', 'give-peer-to-peer' ),
                ],
                404
            );
        }


        $this->fundraiser->set( 'campaign_id', $campaign->getId() );
        $this->fundraiser->set( 'team_id', $request->get_param( 'teamId' ) );
        $this->fundraiser->set( 'user_id', get_current_user_id() );
        $this->fundraiser->set( 'status', ( $campaign->doesRequireFundraiserApproval() ) ? Status::PENDING : Status::ACTIVE );

        if ( $this->fundraiser->save() ) {

            return new WP_REST_Response(
                [
                    'redirect' => '/register/create-profile/',
                ]
            );

        }

        return new WP_REST_Response(
            [
                'error'   => 'registration_failed',
                'message' => __( 'Something went wrong', 'give-peer-to-peer' ),
            ],
            400
        );
    }

}
