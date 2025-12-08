<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Repositories\TeamRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetCampaignTeamsSearch extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'get-campaign-teams-search';

	/**
	 * @var TeamRepository
	 */
	protected $teamRepository;

	/**
	 * @param TeamRepository $teamRepository
	 */
	public function __construct( TeamRepository $teamRepository ) {
		$this->teamRepository = $teamRepository;
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
                    'permission_callback' => '__return_true',
					'args'                => [
						'campaign_id'     => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
						],
						'search' => [
							'required' => false,
							'type' => 'string',
							'description' => esc_html__( 'Search text', 'give-peer-to-peer' ),
							'sanitize_callback' => function ( $param ) {
								return sanitize_text_field( $param );
							},
						],
						'showClosedTeams' => [
							'required' => false,
							'type' => 'boolean',
							'default' => false,
						],
						'page'      => [
							'required' => false,
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
							'default'           => 1,
							'description'       => esc_html__( 'Current page', 'give-peer-to-peer' ),
						],
						'per_page'  => [
							'required' => false,
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
							'default'           => 9,
							'description'       => esc_html__( 'Items per page', 'give-peer-to-peer' ),
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

		$teams = $this->teamRepository->getCampaignTeamsSearch(
			$request->get_param( 'campaign_id' ),
			$request->get_param( 'search' ),
			$limit = $request->get_param( 'per_page' ),
			$request->get_param( 'showClosedTeams' ),
			$offset = ( ( $request->get_param( 'page' ) - 1 ) * $request->get_param( 'per_page' ) )
		);

		$totalTeamsCount = $this->teamRepository->getCampaignTeamsSearchCount(
			$request->get_param( 'campaign_id' ),
			$request->get_param( 'search' ),
			$request->get_param( 'showClosedTeams' )
		);

		return new WP_REST_Response(
			[
				'data' => array_map(function( $team ) {
                    $captain = Team::getTeamData($team['id'])['captain'];


                    $team[ 'goal' ]    = Money::ofMinor( $team[ 'goal' ], give_get_option( 'currency' ) )->getAmount();
					$team[ 'amount' ]  = Money::ofMinor( $team[ 'amount' ], give_get_option( 'currency' ) )->getAmount();
					$team[ 'team_captain' ]  = $captain;
					$team[ 'fundraiser_total' ]  = Team::getFundraisersCount($team['id']);


					return $team;
				}, $teams ),
				'count' => count( $teams ),
				'total' => $totalTeamsCount,
			]
		);
	}
}
