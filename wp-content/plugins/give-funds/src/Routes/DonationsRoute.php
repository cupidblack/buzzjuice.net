<?php

namespace GiveFunds\Routes;

use WP_REST_Request;
use Give\API\RestRoute;
use GiveFunds\Repositories\Revenue as RevenueRepository;


/**
 * Class FundOverviewRoute
 * @package GiveFunds\Routes
 */
class DonationsRoute extends Endpoint {

	/** @var string */
	protected $endpoint = 'give-funds/get-donations';

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
						'fundId' => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitizeFundId' ],
						],
						'start'  => [
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => [ $this, 'validateDate' ],
							'sanitize_callback' => [ $this, 'sanitizeDate' ],
						],
						'end'    => [
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
				'fundId' => [
					'fundId' => esc_html__( 'Fund ID', 'give-funds' ),
					'type'   => 'integer',
				],
				'start'  => [
					'start' => esc_html__( 'Start Date', 'give-funds' ),
					'type'  => 'string',
				],
				'end'    => [
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
		$fundId = $request->get_param( 'fundId' );
		$start  = $request->get_param( 'start' );
		$end    = $request->get_param( 'end' );
		$page   = $request->get_param( 'page' );

		return $this->getPayments( $fundId, $start, $end, $page );
	}


	/**
	 * Get payments.
	 *
	 * @param $fundId
	 * @param string $start
	 * @param string $end
	 * @param int $page
	 * @param string $orderBy
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function getPayments( $fundId, $start, $end, $page = 1, $orderBy = 'date' ) {

		$data    = [];
		$perPage = 10;

		$revenueRepository = new RevenueRepository();
		$donationIDs       = $revenueRepository->getAllDonationByFundId( $fundId );

		$data['labels'] = [
			'donation'     => esc_html__( 'Donation', 'give-funds' ),
			'amount'       => esc_html__( 'Amount', 'give-funds' ),
			'donationForm' => esc_html__( 'Donation Form', 'give-funds' ),
			'date'         => esc_html__( 'Date', 'give-funds' ),
		];

		$data['rows'] = [];

		if ( empty( $donationIDs ) ) {
			return $data;
		}

		$args = [
			'status'     => [
				'publish',
				'give_subscription',
			],
			'number'     => -1,
			'orderby'    => $orderBy,
			'order'      => 'DESC',
			'start_date' => strtotime( $start ),
			'end_date'   => strtotime( $end ),
			'post__in'   => $donationIDs,
			'gateway'    => array_keys( give_get_payment_gateways() )
		];

		$payments      = new \Give_Payments_Query( $args );
		$paymentsdata  = $payments->get_payments();
		$total         = count( $paymentsdata );
		$paymentsData  = array_slice( $paymentsdata, ( ( $page - 1 ) * $perPage ), $perPage );
		$data['pages'] = ceil( $total / $perPage );

		// Prepare response
		foreach ( $paymentsData as $payment ) {

			$data['rows'][ $payment->ID ] = [
				'donation'     => [
					'number'      => $this->getDonationNumber( $payment ),
					'donatinLink' => $this->getDonationLink( $payment ),
					'donor'       => $this->getDonor( $payment ),
					'donorLink'   => $this->getDonorLink( $payment ),
					'email'       => $this->getDonorEmail( $payment )
				],
				'amount'       => [
					'amount'  => $this->getDonationAmount( $payment ),
					'gateway' => $this->getDonationGateway( $payment )
				],
				'donationForm' => [
					'name' => $this->getDonationFormName( $payment ),
					'link' => $this->getDonationFormLink( $payment )
				],
				'date'         => $this->getDonationDate( $payment )
			];

		}

		return $data;

	}

	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function getDonationLink( $payment ) {
		return add_query_arg( 'id', $payment->ID, admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-payment-details' ) );
	}

	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function getDonationNumber( $payment ) {
		return Give()->seq_donation_number->get_serial_code( $payment );
	}

	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function getDonationAmount( $payment ) {
		return html_entity_decode( give_donation_amount( $payment, true ) );
	}

	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function getDonationGateway( $payment ) {
		return give_get_gateway_admin_label( $payment->gateway );
	}

	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function getDonationFormName( $payment ) {
		return empty( $payment->form_title ) ? sprintf( esc_html__( 'Untitled (#%s)', 'give-funds' ), $payment->form_id ) : $payment->form_title;
	}

	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function getDonationFormLink( $payment ) {
		return admin_url( 'post.php?post=' . $payment->form_id . '&action=edit' );
	}

	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function getDonationDate( $payment ) {
		return date_i18n( give_date_format(), strtotime( $payment->date ) );
	}

	/**
	 * Get donor
	 *
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return string Data shown in the User column
	 */
	private function getDonor( $payment ) {

		$donor_id           = give_get_payment_donor_id( $payment->ID );
		$donor_billing_name = give_get_donor_name_by( $payment->ID, 'donation' );
		$donor_name         = give_get_donor_name_by( $donor_id, 'donor' );

		if ( ! empty( $donor_id ) ) {

			$value = '';

			// Check whether the donor name and WP_User name is same or not.
			if ( sanitize_title( $donor_billing_name ) !== sanitize_title( $donor_name ) ) {
				$value .= $donor_billing_name . ' (';
			}

			$value .= $donor_name;

			// Check whether the donor name and WP_User name is same or not.
			if ( sanitize_title( $donor_billing_name ) !== sanitize_title( $donor_name ) ) {
				$value .= ')';
			}
		} else {
			return esc_html__( 'donor missing', 'give-funds' );
		}

		return apply_filters( 'give_payments_table_column', $value, $payment->ID, 'donor' );
	}


	/**
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 * @return string
	 */
	private function getDonorLink( $payment ) {
		$donor_id = give_get_payment_donor_id( $payment->ID );

		if ( ! empty( $donor_id ) ) {
			return add_query_arg( 'id', $donor_id, admin_url( 'edit.php?post_type=give_forms&page=give-donors&view=overview' ) );
		} else {
			$email = give_get_payment_user_email( $payment->ID );
			return add_query_arg( 's', $email, admin_url( 'edit.php?post_type=give_forms&page=give-payment-history' ) );
		}
	}


	/**
	 * Get donor email.
	 *
	 * @param Give_Payment $payment
	 * @since  1.0.0
	 *
	 * @return array
	 */
	private function getDonorEmail( $payment ) {
		$email = give_get_payment_user_email( $payment->ID );

		if ( empty( $email ) ) {
			$email = esc_html__( '(unknown)', 'give-funds' );
		}

		return [
			'link'  => sprintf( 'mailto:%s', $email ),
			'label' => esc_html__( 'Email donor', 'give-funds' ),
			'value' => $email
		];
	}

}
