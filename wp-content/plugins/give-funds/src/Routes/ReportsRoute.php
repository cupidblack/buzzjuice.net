<?php

namespace GiveFunds\Routes;

use Exception;
use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Repositories\Revenue as RevenueRepository;
use WP_REST_Request;

/**
 * Class ReportsRoute
 * @package GiveFunds\Routes
 */
class ReportsRoute extends Endpoint {

	/** @var string */
	protected $endpoint = 'give-funds/get-reports';

	/**
	 * @var int
	 */
	private $selectedFund;

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
							'required'          => false,
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
				'fundId'   => [
					'fundId' => esc_html__( 'Fund ID', 'give-funds' ),
					'type'   => 'integer',
				],
				'currency' => [
					'currency' => esc_html__( 'Fund ID', 'give-funds' ),
					'type'     => 'string',
				],
				'start'    => [
					'start' => esc_html__( 'Start Date', 'give-funds' ),
					'type'  => 'string',
				],
				'end'      => [
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
	 * @since 1.0.0
	 */
	public function handleRequest( WP_REST_Request $request ) {
		$this->selectedFund = $request->get_param( 'fundId' );
		$this->currency     = $request->get_param( 'currency' );
		$start              = date_create( $request->get_param( 'start' ) );
		$end                = date_create( $request->get_param( 'end' ) );
		$diff               = date_diff( $start, $end );

		$data = [];

		switch ( $diff ) {
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

		$data             = [];
		$data['datasets'] = [];

		$fundsRepository = give( FundsRepository::class );
		$funds           = ! $this->selectedFund
			? $fundsRepository->getFunds()
			: [ $fundsRepository->getFund( $this->selectedFund ) ];

		// Get all funds
		foreach ( $funds as $i => $fund ) {

			if ( ! $fund ) {
				continue;
			}

			$tooltips = [];
			$income   = [];

			$interval = new \DateInterval( $intervalStr );

			$periodStart = clone $start;
			$periodEnd   = clone $start;

			// Subtract interval to set up period start
			date_sub( $periodStart, $interval );

			while ( $periodStart < $end ) {

				$values          = $this->getValues( $fund->getId(), $periodStart->format( 'Y-m-d H:i:s' ), $periodEnd->format( 'Y-m-d H:i:s' ) );
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

				$data['labels'][] = $time;

				$income[] = [
					'x' => $time,
					'y' => $incomeForPeriod,
				];

				$tooltips[] = [
					'title'  => $this->getTooltipTitle( $fund->getTitle(), $incomeForPeriod ),
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
			$data['datasets'][ $i ] = [
				'label'    => $fund->getTitle(),
				'data'     => $income,
				'tooltips' => $tooltips,
			];

		}

		return $data;

	}

	/**
	 * @param $fundId
	 * @param string $startStr
	 * @param string $endStr
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function getValues( $fundId, $startStr, $endStr ) {

		$paymentObjects = $this->getPayments( $fundId, $startStr, $endStr );

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
	 * @param string $startStr
	 * @param string $endStr
	 * @param string $orderBy
	 * @param int $number
	 *
	 * @return array
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

	/**
	 * @param string $fundTitle
	 * @param string $incomePeriod
	 * @since 1.0.0
	 * @return string
	 */
	public function getTooltipTitle( $fundTitle, $incomePeriod ) {
		$amount = give_currency_filter(
			give_format_amount( $incomePeriod ),
			[
				'currency_code'   => $this->currency,
				'decode_currency' => true,
				'sanitize'        => false,
			]
		);

		return sprintf( '%s - %s', $fundTitle, $amount );
	}

}
