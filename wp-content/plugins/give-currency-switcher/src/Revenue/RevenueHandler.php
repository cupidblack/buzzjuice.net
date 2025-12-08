<?php
namespace GiveCurrencySwitcher\Revenue;

use Give\Helpers\Hooks;
use Give\ValueObjects\Money;

/**
 * Class RevenueHandler
 * @package GiveCurrencySwitcher
 *
 * @since 1.3.12
 */
class RevenueHandler {

	/**
	 * Convert revenue amount in base currency.
	 *
	 * @param array $revenueData Revenue data
	 *
	 * @return mixed
	 * @since 1.3.12
	 */
	public function handle( $revenueData ) {
		$donationId = absint( $revenueData['donation_id'] );
		$amount     = $this->getDonationAmountInBaseCurrency( $donationId );

		if ( $amount ) {
			$currencyCode          = give_get_payment_currency_code( $donationId );
			$revenueData['amount'] = Money::of( $amount, give_get_option( 'currency' ) )->getMinorAmount();

			if ( ! $this->isDonationGivenInBaseCurrency( $donationId ) ) {
				$revenueData['exchange_amount']   = Money::of( give_donation_amount( $donationId ), $currencyCode )->getMinorAmount();
				$revenueData['exchange_currency'] = $currencyCode;
			}
		}

		return $revenueData;
	}

	/**
	 * Convert revenue amount in base currency.
	 *
	 * @since 1.3.12
	 *
	 * @param int $donationId
	 */
	public function handleNewRenewal( $donationId ) {
		$amount = $this->getDonationAmountInBaseCurrency( $donationId );

		if ( $amount ) {
			Hooks::addFilter(
				'give_revenue_insert_data',
				function ( $revenueData ) use ( $amount, $donationId ) {
					if ( $donationId !== $revenueData['donation_id'] ) {
						return $revenueData;
					}

					$currencyCode          = give_get_payment_currency_code( $donationId );
					$revenueData['amount'] = Money::of( $amount, give_get_option( 'currency' ) )->getMinorAmount();

					if ( ! $this->isDonationGivenInBaseCurrency( $donationId ) ) {
						$revenueData['exchange_amount']   = Money::of( give_donation_amount( $donationId ), $currencyCode )->getMinorAmount();
						$revenueData['exchange_currency'] = $currencyCode;
					}

					return $revenueData;
				}
			);
		}
	}

	/**
	 * Get donation amount in base currency.
	 *
	 * 2since 1.3.12
	 *
	 * @param int $donationId
	 *
	 * @return bool|mixed|string
	 */
	private function getDonationAmountInBaseCurrency( $donationId ) {
		return give()->payment_meta->get_meta( $donationId, '_give_cs_base_amount', true );
	}

	/**
	 * Return whether or not donation given in base currency.
	 *
	 * @since 1.3.12
	 *
	 * @param string $donationId
	 *
	 * @return bool
	 */
	private function isDonationGivenInBaseCurrency( $donationId ) {
		return give_get_option( 'currency' ) === give_get_payment_currency_code( $donationId );
	}
}
