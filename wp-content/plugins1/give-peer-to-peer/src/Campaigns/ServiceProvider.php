<?php
namespace GiveP2P\Campaigns;

use Give\Helpers\Hooks;
use Give\Framework\Migrations\MigrationsRegister;
use Give\ServiceProviders\ServiceProvider as GiveServiceProvider;
use GiveP2P\Campaigns\Migrations\CreateCampaignsTable;
use GiveP2P\Campaigns\Migrations\CreateCampaignDonationTable;

/**
 * Campaigns ServiceProvider Class
 * @package GiveP2P\Campaigns
 *
 * @since 1.0.0
 */
class ServiceProvider implements GiveServiceProvider {
	/**
	 * @inheritDoc
	 */
	public function register() {
		global $wpdb;
		$wpdb->give_campaigns         = "{$wpdb->prefix}give_campaigns";
	}

	/**
	 * @inheritDoc
	 */
	public function boot() {
		$this->registerMigrations();
	}


	/**
	 * Register Campaign Migrations
	 */
	public function registerMigrations() {
		give( MigrationsRegister::class )->addMigrations(
			[
				CreateCampaignsTable::class,
			]
		);
	}
}
