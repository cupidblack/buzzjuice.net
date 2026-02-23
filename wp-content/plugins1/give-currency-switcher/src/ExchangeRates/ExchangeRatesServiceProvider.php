<?php

namespace GiveCurrencySwitcher\ExchangeRates;

use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider;
use GiveCurrencySwitcher\ExchangeRates\Crons\UpdateExchangeRates;
use GiveCurrencySwitcher\ExchangeRates\Repositories\ExchangeRates;

class ExchangeRatesServiceProvider implements ServiceProvider {
	/**
	 * @inheritDoc
	 *
	 * @since 1.5.0
	 */
	public function register() {
		give()->singleton( ExchangeRates::class );
	}

	/**
	 * @inheritDoc
	 *
	 * @since 1.5.0
	 */
	public function boot() {
		$this->scheduleCronJob();
	}

	/**
	 * Schedules the cron job
	 *
	 * @since 1.5.0
	 */
	private function scheduleCronJob() {
		Hooks::addAction( 'cs_give_cron_update_exchange_rates', UpdateExchangeRates::class );

		if ( ! wp_next_scheduled( 'cs_give_cron_update_exchange_rates' ) ) {
			wp_schedule_event( time(), 'daily', 'cs_give_cron_update_exchange_rates' );
		}
	}
}
