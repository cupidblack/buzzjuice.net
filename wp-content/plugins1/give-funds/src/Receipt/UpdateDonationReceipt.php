<?php

namespace GiveFunds\Receipt;

use Give\Receipt\DonationReceipt;
use Give\Receipt\Section;
use GiveFunds\Models\Fund;
use GiveFunds\Infrastructure\View;
use GiveFunds\Repositories\Form;
use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Repositories\Revenue;
use WP_Post;
use Give_Payment;


/**
 * Class UpdateDonationReceipt
 *
 * Add fund detail on donation receipt
 *
 * @package GiveFunds\Receipt
 * @since 1.0.0
 */
class UpdateDonationReceipt {
	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * FundInfo constructor.
	 *
	 * @param FundsRepository $fundsRepository
	 */
	public function __construct( FundsRepository $fundsRepository ) {
		$this->fundsRepository = $fundsRepository;
	}

	/**
	 * Render Add fund page
	 *
	 * @param WP_Post $donation
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function renderRow( $donation ) {
		if ( ! ( $fund = $this->getFund( $donation->ID ) ) ) { // phpcs:ignore
			return;
		}

		// Display fund only if designation is set to donor choice
		if ( ! $this->isDonorChoice( $donation->ID ) ) {
			return;
		}

		// Render view
		View::render( 'receipt/fund-row', [ 'fundName' => $fund->getTitle() ] );
	}

	/**
	 * Show selected fund for sequoia template
	 *
	 * @param DonationReceipt $receipt
	 * @since 1.0.0
	 * @return void
	 */
	public function renderRowSequoiaTemplate( $receipt ) {
		if ( ! ( $fund = $this->getFund( $receipt->donationId ) ) ) { // phpcs:ignore
			return;
		}

		// Display fund only if designation is set to donor choice
		if ( ! $this->isDonorChoice( $receipt->donationId ) ) {
			return;
		}

		/* @var Section $receiptDonationSection */
		$receiptDonationSection = $receipt[ DonationReceipt::DONATIONSECTIONID ];

		$receiptDonationSection->addLineItem(
			[
				'id'    => 'fund-name',
				'label' => esc_html__( 'Fund', 'give-funds' ),
				'value' => $fund->getTitle()
			],
			'before',
			'totalAmount'
		);
	}

	/**
	 * Get fund associated with donation.
	 *
	 * @since 1.0.0
	 *
	 * @param $donationId
	 *
	 * @return Fund|null
	 */
	private function getFund( $donationId ) {
		/* @var Revenue $revenueRepository */
		$revenueRepository = give( Revenue::class );
		$fundId            = $revenueRepository->getDonationFundId( $donationId );

		if ( ! $fundId ) {
			return null;
		}

		$fund = $this->fundsRepository->getFund( $fundId );

		return $fund ?: null; // phpcs:ignore
	}

	/**
	 * Check if donation designation is set by Donor choice
	 *
	 * @param int $donationId
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function isDonorChoice( $donationId ) {
		// Get donation
		$payment        = new Give_Payment( $donationId );
		$formRepository = give( Form::class );

		return ( 'donor_choice' === $formRepository->getFundDisplayType( $payment->form_id ) );
	}
}
