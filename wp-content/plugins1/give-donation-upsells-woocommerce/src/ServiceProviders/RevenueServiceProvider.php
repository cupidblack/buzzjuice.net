<?php

namespace GiveWooCommerceUpsells\ServiceProviders;

use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider;
use GiveWooCommerceUpsells\Revenue\Migrations\AddMissingDonationRevenue;

class RevenueServiceProvider implements ServiceProvider {
	/**
	 * @inheritDoc
	 */
	public function register() {
	}

	/**
	 * @inheritDoc
	 */
	public function boot() {
		Hooks::addAction('give_register_updates', AddMissingDonationRevenue::class, 'register');
	}
}
