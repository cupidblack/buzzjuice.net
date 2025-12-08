<?php

namespace GiveP2P\P2P\Routes;

use WP_REST_Request;
use WP_REST_Response;
use Give\ValueObjects\Money;
use GiveP2P\P2P\Repositories\FundraiserRepository;

/**
 * Class GetFundraisersTopDonorsRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetFundraisersTopDonorsRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'get-fundraiser-top-donors';
	/**
	 * @var FundraiserRepository
	 */
	protected $fundraiserRepository;

	/**
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
					'methods'  => 'GET',
					'callback' => [ $this, 'handleRequest' ],
                    'permission_callback' => '__return_true',
					'args'     => [
						'fundraiser_id' => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Fundraiser ID', 'give-peer-to-peer' ),
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

		$limit  = 6;
		$donors = $this->fundraiserRepository->getTopDonors( $request->get_param( 'fundraiser_id' ), $limit );

		$data = [];

		$currency = give_get_option( 'currency' );

		foreach ( $donors as $donor ) {
			$amount = Money::ofMinor( $donor[ 'amount' ], $currency );
			$data[] = [
				'id'     => $donor[ 'id' ],
				'name'   => $donor[ 'name' ],
				'amount' => $amount->getAmount(),
			];
		}

		return new WP_REST_Response(
			[
				'data' => $data
			]
		);
	}

}
