<?php
namespace GiveP2P\Addon;

use WP_CLI;
use Give\Helpers\Hooks;
use Give\Framework\Migrations\MigrationsRegister;
use GiveP2P\Addon\Helpers\Language;
use GiveP2P\Addon\Helpers\License;
use GiveP2P\Addon\Helpers\ActivationBanner;
use GiveP2P\Addon\Helpers\Assets;
use GiveP2P\P2P\Factories\TeamFactory;
use GiveP2P\P2P\Factories\FundraiserFactory;
use GiveP2P\Campaigns\Factories\CampaignFactory;
use Give\ServiceProviders\ServiceProvider as GiveServiceProvider;

/**
 * Add-on ServiceProvider Class
 * @package GiveP2P\Addon
 *
 * @since 1.0.0
 */
class ServiceProvider implements GiveServiceProvider {
	/**
	 * @inheritDoc
	 */
	public function register() {
		//
	}

	/**
	 * @inheritDoc
	 */
	public function boot() {
		// Load add-on translations.
		Hooks::addAction( 'init', Language::class, 'load' );

		is_admin()
			? $this->loadBackend()
			: $this->loadFrontend();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->registerCliCommands();
		}
	}


	/**
	 * Load add-on backend assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function loadBackend() {
		Hooks::addAction( 'admin_init', License::class, 'check' );
		Hooks::addAction( 'admin_init', ActivationBanner::class, 'show', 20 );
		// Load backend assets.
		Hooks::addAction( 'admin_enqueue_scripts', Assets::class, 'loadBackendAssets' );
	}

	/**
	 * Load add-on front-end assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function loadFrontend() {
		// Load front-end assets.
		Hooks::addAction( 'wp_enqueue_scripts', Assets::class, 'loadFrontendAssets' );
	}

	protected function registerCliCommands() {
		WP_CLI::add_command(
			'give p2p:migrate',
			function() {
				$migrations = give( MigrationsRegister::class );
				foreach ( $migrations->getMigrations() as $migrationClass ) {
					if (
					strpos( $migrationClass, 'Campaigns\Migrations' )
					|| strpos( $migrationClass, 'P2P\Migrations' )
					) {
						give( $migrationClass )->run();
					}
				}
			}
		);

		WP_CLI::add_command(
			'give p2p:fresh',
			function() {
				global $wpdb;
				$wpdb->query( "DROP TABLE $wpdb->give_campaigns" );
				$wpdb->query( "DROP TABLE $wpdb->give_p2p_fundraisers" );
				$wpdb->query( "DROP TABLE $wpdb->give_p2p_teams" );
			}
		);

		WP_CLI::add_command(
			'give p2p:seed',
			function() {
				global $wpdb;

				$wpdb->insert(
					$wpdb->give_campaigns,
					give( CampaignFactory::class )->definition()
				);

				$wpdb->insert(
					$wpdb->give_p2p_fundraisers,
					array_merge(
						give( FundraiserFactory::class )->definition(),
						[
							'campaign_id' => 1,
							'user_id'     => 1,
						]
					)
				);

				$wpdb->insert(
					$wpdb->give_p2p_teams,
					array_merge(
						give( TeamFactory::class )->definition(),
						[ 'campaign_id' => 1 ]
					)
				);

			}
		);
	}
}
