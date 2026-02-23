<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Repositories\FundraiserRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since unreleased
 */
class FundraiserJoinTeamRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'fundraiser-join-team';

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
						'teamId'     => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Team ID', 'give-peer-to-peer' ),
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
		$teamId     = $request->get_param( 'teamId' );
		$campaignId = $request->get_param( 'campaignId' );

		// Check if user has fundraiser account on this campaign
		if ( $fundraiserId = $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId( $userId, $campaignId ) ) {

			$fundraiser = $this->fundraiserRepository->getFundraiser( $fundraiserId );
			$fundraiser->set( 'team_id', $teamId );

			if ( ! $fundraiser->save() ) {
				return new WP_REST_Response(
					[
						'error'   => 'update_failed',
						'message' => __( 'Something went wrong', 'give-peer-to-peer' ),
					],
					400
				);
			}

			return new WP_REST_Response(
				[
					'redirect' => '/start-fundraising/',
				]
			);

		}

		return new WP_REST_Response(
			[
				'error'   => 'fundraiser_does_not_exist',
				'message' => __( 'Something went wrong', 'give-peer-to-peer' ),
			],
			404
		);
	}

}
