<?php

namespace GiveFeeRecovery\Receipt;

use Give\Receipt\DonationReceipt;
use Give\Receipt\UpdateReceipt;
use GiveFeeRecovery\Helpers\Form\Form;
use function give_get_payment_meta as getDonationMetaData;
use function give_format_amount as formatAmount;
use function give_currency_filter as filterCurrency;


/**
 * Class UpdateDonationReceipt
 * @package GiveFeeRecovery\Receipt
 * @since 1.7.9
 */
class UpdateDonationReceipt extends UpdateReceipt {
	/**
	 * Apply change to donation receipt.
	 *
	 * @since 1.7.9
	 */
	public function apply() {
		if( ! Form::hasFeeAmount( $this->receipt->donationId ) ) {
			return;
		}

		$this->receipt[DonationReceipt::DONATIONSECTIONID]['amount']->value = $this->getDonationAmountLineItemValue();
		$this->receipt[DonationReceipt::DONATIONSECTIONID]->addLineItem( $this->getProcessingFeeLineItem(), 'before', 'amount' );
	}

	/**
	 * Get processing fee line item.
	 *
	 * @return array
	 * @since 1.7.9
	 */
	private function getProcessingFeeLineItem() {
		$fee      = getDonationMetaData( $this->receipt->donationId, '_give_fee_amount', true );
		$fee      = $fee ?: 0;
		$currency = getDonationMetaData( $this->receipt->donationId, '_give_payment_currency', true );

		$value = filterCurrency(
			formatAmount(
				$fee,
				[
					'donation_id' => $this->receipt->donationId,
					'currency'    => $currency,
				]
			)
		);


		return [
			'id'    => 'processingFee',
			'label' => esc_html__( 'Processing Fee', 'give-fee-recovery' ),
			'value' => $value,
		];
	}

	/**
	 * Get donation amount line item value.
	 *
	 * @return array
	 * @since 1.7.9
	 */
	private function getDonationAmountLineItemValue() {
		$amount   = getDonationMetaData( $this->receipt->donationId, '_give_fee_donation_amount', true );
		$currency = getDonationMetaData( $this->receipt->donationId, '_give_payment_currency', true );

		return filterCurrency(
			formatAmount(
				$amount,
				[
					'donation_id' => $this->receipt->donationId,
					'currency'    => $currency,
				]
			)
		);

	}
}
