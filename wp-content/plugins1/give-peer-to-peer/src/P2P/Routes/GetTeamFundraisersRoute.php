<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use WP_REST_Request;
use WP_REST_Response;
use GiveP2P\P2P\Repositories\TeamRepository;
use GiveP2P\P2P\ViewModels\EditTeamViewModel;

/**
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetTeamFundraisersRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'get-team-fundraisers-all';
	/**
	 * @var TeamRepository
	 */
	private $teamRepository;

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
						'team_id' => [
							'type'        => 'integer',
							'description' => esc_html__( 'Team ID', 'give-peer-to-peer' ),
							'required'    => true,
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
						],
					],
				],
				'schema' => [ $this, 'getSchema' ],
			]
		);
	}

	/**
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function handleRequest( WP_REST_Request $request ) {

		$teamFundraisers = $this->teamRepository->getTeamFundraisers( $request->get_param( 'team_id' ) );

		return new WP_REST_Response(
			[
				'data'   => array_map(function( $fundraiser ) {
					$fundraiser[ 'goal' ] = Money::ofMinor( $fundraiser[ 'goal' ], give_get_option( 'currency' ) )->getAmount();
					$fundraiser[ 'amount' ] = Money::ofMinor( $fundraiser[ 'amount' ], give_get_option( 'currency' ) )->getAmount();
					return $fundraiser;
				}, $teamFundraisers ),
			]
		);
	}

}
