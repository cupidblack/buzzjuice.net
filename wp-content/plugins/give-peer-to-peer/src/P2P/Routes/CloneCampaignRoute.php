<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Facades\Campaign as CampaignRepository;
use GiveP2P\P2P\Models\Campaign;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.2.0
 */
class CloneCampaignRoute extends Endpoint {

    /**
     * @var string
     */
    protected $endpoint = 'clone-campaign';

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
                        'campaign_id' => [
                            'required'    => true,
                            'type'        => 'integer',
                            'description' => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
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
        if ( ! $campaign = CampaignRepository::getCampaign( $request->get_param( 'campaign_id' ) ) ) {
            return new WP_REST_Response(
                [
                    'status'  => 'invalid_campaign',
                    'message' => __( 'Campaign you are trying to clone does not exist', 'give-peer-to-peer' ),
                ],
                404
            );
        }

        $newCampaign = Campaign::fromArray( $campaign->toArray() );
        $newCampaign->set( 'id', null );
        $newCampaign->set( 'campaign_title', $campaign->getTitle() . ' (copy)' );
        $newCampaign->set( 'campaign_url', $campaign->getUrl() . '-copy' );

        if ( $newCampaign->save() ) {
            return new WP_REST_Response();
        };

        return new WP_REST_Response(
            [
                'status'  => 'error',
                'message' => __( 'Something went wrong', 'give-peer-to-peer' ),
            ],
            404
        );
    }

}
