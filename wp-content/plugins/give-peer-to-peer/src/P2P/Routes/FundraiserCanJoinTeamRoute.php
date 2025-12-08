<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Repositories\FundraiserRepository;
use WP_REST_Request;
use WP_REST_Response;

class FundraiserCanJoinTeamRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'fundraiser-can-join-team';

	/**
	 * @var FundraiserRepository
	 */
	private $fundraiserRepository;


	/**
	 * FundraiserLogin constructor.
	 *
	 * @param  FundraiserRepository  $fundraiserRepository
	 */
	public function __construct( FundraiserRepository $fundraiserRepository ) {
		$this->fundraiserRepository = $fundraiserRepository;
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
					'permission_callback' => 'is_user_logged_in',
					'args'                => [
						'campaignId' => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
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

		$userId     = get_current_user_id();
		$campaignId = $request->get_param( 'campaignId' );

		// Check if user has fundraiser account on this campaign
		if ( $fundraiserId = $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId( $userId, $campaignId ) ) {

			$fundraiser = $this->fundraiserRepository->getFundraiser( $fundraiserId );

			// Check if fundraiser actually exist and does he already joined team
			if ( $fundraiser && $fundraiser->get( 'team_id' ) ) {
				return new WP_REST_Response(
					[
						'message' => __( 'Fundraiser has already joined team', 'give-peer-to-peer' ),
					],
					403
				);
			}

		}

		return new WP_REST_Response( null,200 );
	}
}
