<?php

namespace GiveFunds\Routes;

use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Repositories\Revenue as RevenueRepository;
use WP_REST_Request;
use Give_Payments_Query;

class FundPercentages extends Endpoint {

	/** @var string */
	protected $endpoint = 'give-funds/get-percentages';

	/**
	 * @inheritDoc
	 */
	public function registerRoute() {
		register_rest_route(
			'give-api/v2',
			$this->endpoint,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'handleRequest' ],
					'permission_callback' => [ $this, 'permissionsCheck' ],
					'args'                => [
						'start'    => [
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => [ $this, 'validateDate' ],
							'sanitize_callback' => [ $this, 'sanitizeDate' ],
						],
						'end'      => [
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => [ $this, 'validateDate' ],
							'sanitize_callback' => [ $this, 'sanitizeDate' ],
						],
						'currency' => [
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => [ $this, 'validateCurrency' ],
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
				'start'    => [
					'start' => esc_html__( 'Start Date', 'give-funds' ),
					'type'  => 'string',
				],
				'end'      => [
					'end'  => esc_html__( 'End Date', 'give-funds' ),
					'type' => 'string',
				],
				'currency' => [
					'currency' => esc_html__( 'Currency', 'give-funds' ),
					'type'     => 'string',
				],
			],
		];
	}


	/**
	 * @param WP_REST_Request $request
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function handleRequest( WP_REST_Request $request ) {

		$fundRepository    = give( FundsRepository::class );
		$revenueRepository = give( RevenueRepository::class );

		$paymentObjects = $this->getPayments(
			$request->get_param( 'start' ),
			$request->get_param( 'end' )
		);

		$fundObjects = $fundRepository->getFunds();

		$funds = [];
		foreach ( $fundObjects as $fundObject ) {
			$funds[ $fundObject->getId() ] = [
				'label'  => $fundObject->getTitle(),
				'count'  => 0,
				'amount' => 0
			];
		}

		if ( count( $paymentObjects ) > 0 ) {
			foreach ( $paymentObjects as $paymentObject ) {
				$fundID = $revenueRepository->getDonationFundId( $paymentObject->ID );
				// Fallback to default fund
				if ( ! $fundID ) {
					$fundID = $fundRepository->getDefaultFundId();
				}
				$funds[ $fundID ]['count']  += 1;
				$funds[ $fundID ]['amount'] += $paymentObject->total;
			}
		}

		$fundsSorted = usort(
			$funds,
			function ( $a, $b ) {
				if ( $a['amount'] === $b['amount'] ) {
					return 0;
				}
				return ( $a['amount'] > $b['amount'] ) ? -1 : 1;
			}
		);

		$data     = [];
		$labels   = [];
		$tooltips = [];

		if ( true === $fundsSorted ) {
			$funds = array_slice( $funds, 0, 5 );
			foreach ( $funds as $fund ) {
				$labels[]   = $fund['label'];
				$data[]     = $fund['amount'];
				$tooltips[] = [
					'title'  => give_currency_filter(
						give_format_amount( $fund['amount'] ),
						[
							'currency_code'   => $request->get_param( 'currency' ),
							'decode_currency' => true,
							'sanitize'        => false,
						]
					),
					'body'   => esc_html__( 'Revenue', 'give-funds' ),
					'footer' => $fund['label'],
				];
			}
		}

		return [
			'labels'   => $labels,
			'datasets' => [
				[
					'data'     => $data,
					'tooltips' => $tooltips,
				],
			],
		];

	}

	/**
	 * @param string $start
	 * @param string $end
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function getPayments( $start, $end ) {
		$args = [
			'status'     => [
				'publish',
				'give_subscription',
			],
			'number'     => -1,
			'paged'      => 1,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'start_date' => strtotime( $start ),
			'end_date'   => strtotime( $end ),
			'gateway'    => array_keys( give_get_payment_gateways() )
		];

		$payments = new Give_Payments_Query( $args );

		return $payments->get_payments();
	}
}
