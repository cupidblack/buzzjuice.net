<?php

namespace GiveCurrencySwitcher\ExchangeRates\Repositories;

use GiveCurrencySwitcher\Infrastructure\Log;

class ExchangeRates {
	/**
	 * Fetches the exchange rates from the Connect Gateway
	 *
	 * @since 1.5.0
	 *
	 * @param string   $baseCurrency
	 * @param string[] $currencies
	 *
	 * @return array
	 */
	public function getRates( $baseCurrency, $currencies ) {
		$response = wp_remote_get( 'https://connect.givewp.com/exchange-rates?' . http_build_query( [
				'base_currency' => $baseCurrency,
				'currencies'    => $currencies,
			] ), [
			'headers' => [
				'Accept' => 'application/json',
			],
		] );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			Log::error( 'Failed to retrieve currencies from Connect gateway', [
				'base_currency' => $baseCurrency,
				'currencies'    => $currencies,
				'response'      => $response,
			] );

			return [];
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}
}
