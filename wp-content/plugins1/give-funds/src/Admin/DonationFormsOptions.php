<?php

namespace GiveFunds\Admin;

use GiveFunds\Repositories\Revenue as RevenueRepository;
use GiveFunds\Repositories\Funds as FundsRepository;

class DonationFormsOptions {

	/**
	 * @var RevenueRepository
	 */
	private $revenueRepository;

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * DonationFormsOptions constructor.
	 *
	 * @param RevenueRepository $revenueRepository
	 * @param FundsRepository $fundsRepository
	 */
	public function __construct(
		RevenueRepository $revenueRepository,
		FundsRepository $fundsRepository
	) {
		$this->revenueRepository = $revenueRepository;
		$this->fundsRepository   = $fundsRepository;
	}

	/**
	 * Register donation forms bulk action
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
	 * Process donation forms bulk actions
	 *
	 * @param string $redirectTo
	 * @param string $action
	 * @param array $selectedFormsIds
	 *
	 * @since 1.0.0
	 * @return string
	 *
	 */
	public function handleBulkActions( $redirectTo, $action, $selectedFormsIds ) {
		// Check action and selected fund ID
		if (
			'reassign' === $action
			&& isset( $_GET['give-funds-selected-fund'] )
		) {
			$fundId = (int) $_GET['give-funds-selected-fund'];

			foreach ( $selectedFormsIds as $formId ) {
				// Update form fund
				$this->revenueRepository->updateFormFund( (int) $formId, $fundId );
			}
		}

		return $redirectTo;
	}
}
