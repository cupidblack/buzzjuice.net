<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use GiveP2P\P2P\ViewModels\Frontend\TeamProfileViewModel;
use GiveP2P\P2P\ViewModels\Frontend\FundraiserProfileViewModel;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class GetFundraiserInfo
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetFundraiserInfo extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'get-fundraiser-info';

	/**
	 * @var FundraiserRepository
	 */
	private $fundraiserRepository;

	/**
	 * @var TeamRepository
	 */
	private $teamRepository;


	/**
	 * FundraiserLogin constructor.
	 *
	 * @param  FundraiserRepository  $fundraiserRepository
	 * @param  TeamRepository  $teamRepository
	 */
	public function __construct(
		FundraiserRepository $fundraiserRepository,
		TeamRepository $teamRepository
	) {
		$this->fundraiserRepository = $fundraiserRepository;
		$this->teamRepository       = $teamRepository;
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
					'permission_callback' => 'is_user_logged_in',
					'args'                => [
						'campaignId' => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_SANITIZE_STRING );
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
		$data       = [];
		$userId     = get_current_user_id();
		$campaignId = $request->get_param( 'campaignId' );

		// Check if fundraiser exist on campaign
		$fundraiser = $this->fundraiserRepository->getFundraiser(
			$this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId( $userId, $campaignId )
		);

		if ( ! $fundraiser ) {
			return new WP_REST_Response(
				[
					'error'   => 'not_found',
					'message' => __( 'Fundraiser not found', 'give-peer-to-peer' ),
				],
				404
			);
		}

		$user                = get_userdata( $userId );
		$fundraiserViewModel = new FundraiserProfileViewModel( $fundraiser, $this->fundraiserRepository, $user );

		$data[ 'fundraiser' ] = $fundraiserViewModel->exports();

		// Get team
		if ( $fundraiser->get( 'team_id' ) ) {
			$team = $this->teamRepository->getTeam( $fundraiser->get( 'team_id' ) );
			if ( $team ) {
				$teamViewModel  = new TeamProfileViewModel( $team, $this->teamRepository );
				$data[ 'team' ] = $teamViewModel->exports();
			}
		}

		return new WP_REST_Response( [
			'data' => $data,
		] );

	}

}
