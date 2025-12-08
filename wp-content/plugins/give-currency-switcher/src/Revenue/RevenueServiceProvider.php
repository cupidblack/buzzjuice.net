<?php
namespace GiveCurrencySwitcher\Revenue;

use Give\Framework\Migrations\MigrationsRegister;
use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider;
use GiveCurrencySwitcher\Revenue\Migrations\AddExchangeAmountAndExchangeCurrencyColumnToRevenueTable;
use GiveCurrencySwitcher\Revenue\Migrations\AddExchangeCurrencyAndAmountToDonationsInRevenueTable;

/**
 * Class RevenueServiceProvider
 * @package GiveCurrencySwitcher
 *
 * @since 1.3.12
 */
class RevenueServiceProvider implements ServiceProvider {
	/**
	 * @inheritdoc
	 */
	public function register() {
		$this->registerMigrations();
	}

	/**
	 * @inheritdoc
	 */
	public function boot() {
		Hooks::addAction( 'give_recurring_record_payment', RevenueHandler::class, 'handleNewRenewal', 998 );
		Hooks::addFilter( 'give_revenue_insert_data', RevenueHandler::class, 'handle', 999 );
		Hooks::addAction( 'give_register_updates', AddExchangeCurrencyAndAmountToDonationsInRevenueTable::class, 'register' );
	}

	/**
	 * Register migrations.
	 *
	 * @since 1.3.12
	 */
	private function registerMigrations() {
		/* @var MigrationsRegister $migrationRegister */
		$migrationRegister = give( MigrationsRegister::class );

		$migrationRegister->addMigration( AddExchangeAmountAndExchangeCurrencyColumnToRevenueTable::class );
	}
}
