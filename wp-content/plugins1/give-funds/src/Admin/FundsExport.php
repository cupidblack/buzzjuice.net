<?php

namespace GiveFunds\Admin;

use Give_Payment;
use GiveFunds\Infrastructure\View;
use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Repositories\Revenue as RevenueRepository;

class FundsExport {

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;
	/**
	 * @var RevenueRepository
	 */
	private $revenueRepository;

	/**
	 * FundsExport constructor.
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
	 * Render "Fund Options" on Export Donations page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function renderOptions() {
		View::render( 'admin/reports/options' );
	}

	/**
	 * Filter to get columns name when exporting donation
	 *
	 * @param array $cols columns name for CSV
	 * @param array $columns columns select by admin to export
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function filterColumns( $cols, $columns ) {

		if ( isset( $columns['fund_id'] ) ) {
			$cols['fund_id'] = esc_html__( 'Fund ID', 'give-funds' );
		}
		if ( isset( $columns['fund_title'] ) ) {
			$cols['fund_title'] = esc_html__( 'Fund Title', 'give-funds' );
		}
		if ( isset( $columns['fund_description'] ) ) {
			$cols['fund_description'] = esc_html__( 'Fund Description', 'give-funds' );
		}

		return $cols;
	}

	/**
	 * Filter to modify Donation CSV data when exporting donation
	 *
	 * @since 1.0.0
	 *
	 * @param array $donationData
	 * @param Give_Payment $payment
	 * @param array $columns
	 *
	 * @return array
	 */
	public function filterData( $donationData, $payment, $columns ) {
		// Get Fund.
		$fund = $this->fundsRepository->getFund(
			$this->revenueRepository->getDonationFundId( $payment->ID )
		);

		// Bailout.
		if ( ! $fund ) {
			return $donationData;
		}

		if ( isset( $columns['fund_id'] ) ) {
			$donationData['fund_id'] = $fund->getId();
		}
		if ( isset( $columns['fund_title'] ) ) {
			$donationData['fund_title'] = $fund->getTitle();
		}
		if ( isset( $columns['fund_description'] ) ) {
			$donationData['fund_description'] = $fund->getDescription();
		}

		return $donationData;
	}
}
