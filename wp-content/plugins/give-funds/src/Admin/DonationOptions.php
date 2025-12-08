<?php

namespace GiveFunds\Admin;

use Exception;
use Give_Payments_Query;
use GiveFunds\Infrastructure\View;
use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Repositories\Revenue as RevenueRepository;

class DonationOptions {

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * @var RevenueRepository
	 */
	private $revenueRepository;

	/**
	 * FundOptions constructor.
	 *
	 * @param FundsRepository $fundsRepository
	 * @param RevenueRepository $revenueRepository
	 */
	public function __construct(
		FundsRepository $fundsRepository,
		RevenueRepository $revenueRepository
	) {
		$this->fundsRepository   = $fundsRepository;
		$this->revenueRepository = $revenueRepository;
	}

	/**
	 * Show funds dropdown.
	 *
	 * @param $donationId
	 * @since 1.0.0
	 * @return void
	 */
	public function renderDropdown( $donationId ) {
		try {
			$funds        = $this->fundsRepository->getFunds();
			$selectedFund = $this->revenueRepository->getDonationFundId( $donationId );

			if ( ! $selectedFund ) {
				$selectedFund = $this->fundsRepository->getDefaultFundId();
			}

			View::render(
				'admin/donation-options',
				[
					'funds'        => $funds,
					'selectedFund' => (int) $selectedFund
				]
			);
		} catch ( Exception $e ) {
			error_log(
				sprintf( 'There was an error within the Funds add-on while trying to display donation options. Donation ID %d. %s', $donationId, $e->getMessage() )
			);
		}
	}

	/**
	 * Register donations bulk action
	 *
	 * @param array $actions
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function registerBulkActions( $actions ) {
		$fundsCount = $this->fundsRepository->getFundsCount();

		if ( $fundsCount > 1 ) {
			$actions['reassign'] = esc_html__( 'Reassign Revenue to Fund', 'give-funds' );
		}

		return $actions;
	}

	/**
	 * Process donations bulk actions
	 *
	 * @param int $id
	 * @param string $currentAction
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handleBulkActions( $id, $currentAction ) {
		// Check action and selected fund ID
		if (
			'reassign' !== $currentAction
			|| ! isset( $_GET['give-funds-selected-fund'] )
		) {
			return;
		}

		$fundId = (int) $_GET['give-funds-selected-fund'];

		// Update donation fund
		$this->revenueRepository->updateDonationFund( $id, $fundId );
	}

	/**
	 * Handle funds on donation update
	 *
	 * @param $donationId
	 * @since 1.0.0
	 * @return void
	 */
	public function handleData( $donationId ) {
		try {
			// Check selected fund
			if (
				! isset( $_POST['give-selected-fund'] )
				|| empty( $_POST['give-selected-fund'] )
			) {
				// Fallback to default fund
				$fund   = $this->fundsRepository->getDefaultFund();
				$fundId = $fund->getId();
			} else {
				$fundId = (int) $_POST['give-selected-fund'];
			}

			$this->revenueRepository->updateDonationFund( $donationId, $fundId );
		} catch ( Exception $e ) {
			error_log(
				sprintf( 'There was an error associating donation %d with a fund. %s', $donationId, $e->getMessage() )
			);
		}
	}

	/**
	 * Get the earliest donation date
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function getAllTimeStart() {

		$start = date_create( '01/01/2015' );
		$end   = date_create();

		// Setup donation query args (get sanitized start/end date from request)
		$args = [
			'number'     => 1,
			'paged'      => 1,
			'orderby'    => 'date',
			'order'      => 'ASC',
			'start_date' => $start->format( 'Y-m-d H:i:s' ),
			'end_date'   => $end->format( 'Y-m-d H:i:s' ),
		];

		$donations = new \Give_Payments_Query( $args );
		$donations = $donations->get_payments();

		return isset( $donations[0] ) ? $donations[0]->date : $start->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Add Fund column to Donations list table.
	 *
	 * @param array $columns
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function addFundColumn( $columns ) {
		return array_splice( $columns, 0, 3 ) + [ 'fund' => esc_html__( 'Fund', 'give-funds' ) ] + $columns;
	}

	/**
	 * Filter Fund column value.
	 *
	 * @param string $value
	 * @param int $paymentId
	 * @param string $columnName
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function filterFundColumn( $value, $paymentId, $columnName ) {
		// Bailout.
		if ( 'fund' !== $columnName ) {
			return $value;
		}

		$fundId = $this->revenueRepository->getDonationFundId( $paymentId );
		$fund   = $this->fundsRepository->getFund( $fundId );

		if ( ! $fundId || ! $fund ) {
			return esc_html__( 'Unassigned', 'give-funds' );
		}

		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			admin_url( 'edit.php?post_type=give_forms&page=give-fund-overview&id=' . $fundId ),
			esc_attr( $fund->getTitle() )
		);
	}
}
