<?php

namespace GiveFunds\Routes;

use WP_REST_Request;
use WP_REST_Server;
use Give\API\RestRoute;
use GiveFunds\Repositories\Revenue as RevenueRepository;


/**
 * Class FundOverviewRoute
 * @package GiveFunds\Routes
 */
class ReassignFundRoute  extends Endpoint {

	/** @var string */
	protected $endpoint = 'give-funds/reassign-fund';

	/**
	 * @inheritDoc
	 */
	public function registerRoute() {
		register_rest_route(
			'give-api/v2',
			$this->endpoint,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'handleRequest' ],
					'permission_callback' => [ $this, 'permissionsCheck' ],
					'args'                => [
						'fundId'    => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitizeFundId' ],
						],
						'donations' => [
							'type'     => 'array',
							'required' => true,
						],
					],
				],
				'schema' => [ $this, 'getSchema' ]
			]
		);
	}

	/**
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getSchema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'give-funds',
			'type'       => 'object',
			'properties' => [
				'fundId'    => [
					'fundId' => esc_html__( 'Fund ID', 'give-funds' ),
					'type'   => 'integer',
				],
				'donations' => [
					'donations' => esc_html__( 'Donations IDs', 'give-funds' ),
					'type'      => 'array',
				]
			],
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function handleRequest( WP_REST_Request $request ) {
		$fundId            = $request->get_param( 'fundId' );
		$donations         = (array) $request->get_param( 'donations' );
		$revenueRepository = new RevenueRepository();

		foreach ( $donations as $donationId ) {
			$revenueRepository->updateDonationFund( $donationId, $fundId );
		}
	}
}
