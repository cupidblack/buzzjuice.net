<?php

namespace GiveFunds\Routes;

use Exception;
use WP_REST_Request;
use Give\API\RestRoute;
use GiveFunds\Repositories\Revenue as RevenueRepository;


/**
 * Class FundOverviewRoute
 * @package GiveFunds\Routes
 */
class FundOverviewRoute extends Endpoint {

	/** @var string */
	protected $endpoint = 'give-funds/overview';

	/**
	 * @var int
	 */
	private $fundId;

	/**
	 * @var string
	 */
	private $currency;


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
						'fundId'   => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitizeFundId' ],
						],
						'currency' => [
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => [ $this, 'validateCurrency' ],
						],
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
				'currency'  => [
					'currency' => esc_html__( 'Currency', 'give-funds' ),
					'type'     => 'string',
				],
				'startDate' => [
					'start' => esc_html__( 'Start Date', 'give-funds' ),
					'type'  => 'string',
				],
				'endDate'   => [
					'end'  => esc_html__( 'End Date', 'give-funds' ),
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function handleRequest( WP_REST_Request $request ) {

		$this->fundId = $request->get_param( 'fundId' );
		$start        = date_create( $request->get_param( 'start' ) );
		$end          = date_create( $request->get_param( 'end' ) );
		$diff         = date_diff( $start, $end );

		$data = [];

		switch ( true ) {
			case ( $diff->days > 12 ):
				$interval = round( $diff->days / 12 );
				$data     = $this->getData( $start, $end, 'P' . $interval . 'D' );
				break;
			case ( $diff->days > 5 ):
				$data = $this->getData( $start, $end, 'P1D' );
				break;
			case ( $diff->days > 4 ):
				$data = $this->getData( $start, $end, 'PT12H' );
				break;
			case ( $diff->days > 2 ):
				$data = $this->getData( $start, $end, 'PT3H' );
				break;
			case ( $diff->days >= 0 ):
				$data = $this->getData( $start, $end, 'PT1H' );
				break;
		}

		return $data;
	}

	/**
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param int $intervalStr
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getData( $start, $end, $intervalStr ) {

		$data     = [];
		$tooltips = [];
		$income   = [];

		$interval = new \DateInterval( $intervalStr );

		$periodStart = clone $start;
		$periodEnd   = clone $start;

		// Subtract interval to set up period start
		date_sub( $periodStart, $interval );

		while ( $periodStart < $end ) {
			$values          = $this->getValues( $periodStart->format( 'Y-m-d H:i:s' ), $periodEnd->format( 'Y-m-d H:i:s' ) );
			$incomeForPeriod = $values['earnings'];
			$donorsForPeriod = $values['donor_count'];
			$time            = $periodEnd->format( 'Y-m-d H:i:s' );

			switch ( $intervalStr ) {
				case 'P1D':
					$time        = $periodStart->format( 'Y-m-d' );
					$periodLabel = $periodStart->format( 'l' );
					break;
				case 'PT12H':
				case 'PT3H':
				case 'PT1H':
					$periodLabel = $periodStart->format( 'D ga' ) . ' - ' . $periodEnd->format( 'D ga' );
					break;
				default:
					$periodLabel = $periodStart->format( 'M j, Y' ) . ' - ' . $periodEnd->format( 'M j, Y' );
			}

			$income[] = [
				'x' => $time,
				'y' => $incomeForPeriod,
			];

			$tooltips[] = [
				'title'  => give_currency_filter(
					give_format_amount( $incomeForPeriod ),
					[
						'currency_code'   => $this->currency,
						'decode_currency' => true,
						'sanitize'        => false,
					]
				),
				'body'   => $donorsForPeriod . ' ' . esc_html__( 'Donors', 'give-funds' ),
				'footer' => $periodLabel,
			];

			// Add interval to set up next period
			date_add( $periodStart, $interval );
			date_add( $periodEnd, $interval );
		}

		if ( 'P1D' === $intervalStr ) {
			$income   = array_slice( $income, 1 );
			$tooltips = array_slice( $tooltips, 1 );
		}

		// Create data objec to be returned, with 'highlights' object containing total and average figures to display
		$data = [
			'datasets' => [
				[
					'data'     => $income,
					'tooltips' => $tooltips
				],
			],
		];

		return $data;

	}

	/**
	 * @param string $startStr
	 * @param string $endStr
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function getValues( $startStr, $endStr ) {
		$paymentObjects = $this->getPayments( $this->fundId, $startStr, $endStr );

		$earnings = 0;
		$donors   = [];

		foreach ( $paymentObjects as $paymentObject ) {
			if ( $paymentObject->date >= $startStr && $paymentObject->date < $endStr ) {
				if ( 'publish' === $paymentObject->status || 'give_subscription' === $paymentObject->status ) {
					$earnings += $paymentObject->total;
					$donors[]  = $paymentObject->donor_id;
				}
			}
		}

		$unique = array_unique( $donors );

		return [
			'earnings'    => $earnings,
			'donor_count' => count( $unique ),
		];
	}


	/**
	 * Get payment.
	 *
	 * @param $fundId
	 * @param string $startStr
	 * @param string $endStr
	 * @param string $orderBy
	 * @param int $number
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function getPayments( $fundId, $startStr, $endStr, $orderBy = 'date', $number = - 1 ) {

		$revenueRepository = new RevenueRepository();
		$donationIDs       = $revenueRepository->getAllDonationByFundId( $fundId );

		if ( empty( $donationIDs ) ) {
			return [];
		}

		$args = [
			'status'     => [
				'publish',
				'give_subscription',
			],
			'number'     => $number,
			'paged'      => 1,
			'orderby'    => $orderBy,
			'order'      => 'DESC',
			'start_date' => strtotime( $startStr ),
			'end_date'   => strtotime( $endStr ),
			'post__in'   => $donationIDs,
			'gateway'    => array_keys( give_get_payment_gateways() )
		];

		$payments = new \Give_Payments_Query( $args );
		$payments = $payments->get_payments();

		return $payments;

	}

}
