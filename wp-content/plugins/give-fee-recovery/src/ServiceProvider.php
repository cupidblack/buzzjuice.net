<?php

namespace GiveFeeRecovery;

use Give\Helpers\Hooks;
use GiveFeeRecovery\Service\AddFeeToDonationAmount;

/**
 * Class ServiceProvider
 * @package GiveFeeRecovery
 * @since 1.9.1
 */
class ServiceProvider implements \Give\ServiceProviders\ServiceProvider {
	/**
	 * @inheritDoc
	 * @since 1.9.1
	 */
	public function register() {}

	/**
	 * @inheritDoc
	 * @since 1.9.1
	 */
	public function boot() {
		Hooks::addFilter( 'give_donation_total', AddFeeToDonationAmount::class );
	}
}
