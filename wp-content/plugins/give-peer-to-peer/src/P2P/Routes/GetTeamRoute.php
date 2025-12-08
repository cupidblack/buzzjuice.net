<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Repositories\TeamRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class GetTeamRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetTeamRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'get-team';

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
	 * @return array
	 */
	public function getSchema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'team',
			'type'       => 'object',
			'properties' => [
                'team_id' => [
					'type'        => 'integer',
					'description' => esc_html__( 'Team ID', 'give-peer-to-peer' ),
					'required'    => true,
                ],
            ],
        ];
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest(WP_REST_Request $request)
    {
        $teamData = Team::getTeamData($request->get_param('team_id'));
        $team = Team::getTeam($request->get_param('team_id'));

        if ( ! $teamData) {
            return new WP_REST_Response(
                [
                    'status' => false,
                    'message' => __('Team does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $teamData['goal'] = Money::ofMinor($teamData['goal'], give_get_option('currency'))->getAmount();

        $teamData['notify_of_fundraisers'] = $team->isNotifiedOfFundraisersJoined();
        $teamData['notify_of_team_donations'] = $team->isNotifiedOfTeamDonations();

        return new WP_REST_Response(
            [
                'status' => true,
                'data' => $teamData,
            ]
        );
    }

}
