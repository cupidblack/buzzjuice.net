<?php

namespace GiveCurrencySwitcher\ExchangeRates\Crons;

use GiveCurrencySwitcher\ExchangeRates\Repositories\ExchangeRates;

/**
 * Class UpdateExchangeRates
 * @package GiveCurrencySwitcher\ExchangeRates\Crons
 *
 * @since 1.5.0
 */
class UpdateExchangeRates {
	/**
	 * @var ExchangeRates
	 */
	private $repository;
	/**
	 * @var mixed
	 */
	private $baseCurrency;

	/**
	 * UpdateExchangeRates constructor.
	 *
	 * @param ExchangeRates $repository
	 *
	 * @since 1.5.0
	 */
	public function __construct( ExchangeRates $repository ) {
		$this->repository = $repository;
		$this->baseCurrency = give_get_option( 'currency', 'USD' );
	}

	public function __invoke() {
		/**
		 * Filter prevents Give - Currency Switcher from automatically updating exchange rates
		 *
		 * @since 1.5.0
		 *
		 * @param bool $bypass Set to truthy value to prevent automatic updates
		 */
		$bypass = apply_filters( 'cs_give_cron_update_exchange_rates_bypass', false );
		if ( $bypass ) {
			return;
		}

		$this->updateGlobalRates();
		$this->updatePerFormRates();
	}

	private function updateGlobalRates(){
		// Update global settings
		$currencies = give_cs_get_option( 'cs_supported_currency' );

		if (
			empty( $currencies ) ||
			// Check if has support other then base currency.
			1 === count( $currencies )
		) {
			return;
		}

		$newRates = $this->repository->getRates( $this->baseCurrency, $currencies );

		$savedRates = give_cs_get_option( 'cs_exchange_rates', 0, [] );
		$updatedRates = $this->updateRates( $savedRates, $newRates );

		give_update_option( 'cs_exchange_rates', $updatedRates );
	}

	private function updatePerFormRates(){
		// Update forms with custom rates
		$donationForms = get_posts( [
			'post_type' => [ 'give_forms' ],
			'post_status' => 'publish',
			'numberposts' => - 1,
		] );

		foreach ( $donationForms as $form ) {
			// Ignore form if CS is disabled
			if ( ! give_is_setting_enabled( give_get_meta( $form->ID, 'cs_status', true ) ) ) {
				continue;
			}

			// Ignore form if using global options
			if ( ! give_cs_is_per_form_customized( $form->ID ) ) {
				continue;
			}

			$currencies = give_cs_get_option( 'cs_supported_currency', $form->ID );
			if (
				empty( $currencies ) ||
				// Check if has support other then base currency.
				1 === count( $currencies )
			) {
				continue;
			}

			$newRates = $this->repository->getRates( $this->baseCurrency, $currencies );

			$savedRates = give_cs_get_form_exchange_rates( $form->ID );
			$updatedRates = $this->updateRates( $savedRates, $newRates );

			give_update_meta( $form->ID, 'cs_exchange_rates', $updatedRates );
		}
	}

	/**
	 * Loops through the existing rates and returns an updated array
	 *
	 * @param array $savedRates
	 * @param array $newRates
	 *
	 * @return array
	 */
	private function updateRates( $savedRates, $newRates ) {
		$updatedRates = [];
		foreach ( $savedRates as $currencyCode => $rate ) {
			// Remove currencies no longer supported
			if ( ! isset( $newRates[ $currencyCode ] ) ) {
				continue;
			}

			// Leave manually set currencies alone
			if ( ! empty( $rate['set_manually'] ) ) {
				$updatedRates[ $currencyCode ] = $rate;
				continue;
			}

			$rate['exchange_rate'] = $newRates[ $currencyCode ];
			$updatedRates[ $currencyCode ] = $rate;
		}

		return $updatedRates;
	}
}
