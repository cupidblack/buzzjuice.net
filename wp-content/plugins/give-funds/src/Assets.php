<?php
namespace GiveFunds;

use GiveFunds\Infrastructure\Environment;
use GiveFunds\Admin\DonationOptions;
use GiveFunds\Admin\ReportsPage;
use GiveFunds\Repositories\Funds as FundsRepository;

/**
 * Helper class responsible for loading add-on assets.
 *
 * @package     GiveFunds\Funds
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Assets {
	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * Assets constructor.
	 *
	 * @param FundsRepository $fundsRepository
	 */
	public function __construct( FundsRepository $fundsRepository ) {
		$this->fundsRepository = $fundsRepository;
	}

    /**
     * Load add-on backend assets.
     *
     * @since 1.2.0 Enqueue "give-funds-sortable-list" script.
     * @since 1.0.0
     *
     * @return void
     */
	public function loadBackendAssets() {
		// Get funds
		$fundsData = [];
		$funds     = $this->fundsRepository->getFunds();

		foreach ( $funds as $fund ) {
			$fundsData[] = [
				'id'    => $fund->getId(),
				'title' => $fund->getTitle()
			];
		}

		wp_enqueue_style(
			'give-funds-style-backend',
			GIVE_FUNDS_ADDON_URL . 'public/css/give-funds-admin.css',
			[],
			GIVE_FUNDS_ADDON_VERSION
		);

		wp_enqueue_script(
			'give-funds-script-backend',
			GIVE_FUNDS_ADDON_URL . 'public/js/give-funds-admin.js',
			[ 'wp-i18n' ],
			GIVE_FUNDS_ADDON_VERSION,
			true
		);

        wp_enqueue_script(
            'give-funds-sortable-list',
            GIVE_FUNDS_ADDON_URL . 'public/js/give-funds-sortable-list.js',
            [],
            GIVE_FUNDS_ADDON_VERSION,
            true
        );

		wp_enqueue_script(
			'give-funds-menu-item',
			GIVE_FUNDS_ADDON_URL . 'public/js/menu-item.js',
			[ 'wp-i18n', 'wp-hooks' ],
			GIVE_FUNDS_ADDON_VERSION
		);

		if ( Environment::isFundOverviewPage() ) {
			wp_enqueue_script(
				'give-funds-overview-script',
				GIVE_FUNDS_ADDON_URL . 'public/js/give-funds-overview.js',
				[ 'give-funds-script-backend' ],
				GIVE_FUNDS_ADDON_VERSION,
				true
			);
		}

		if ( Environment::isFundsReportsPage() ) {
			wp_enqueue_script(
				'give-funds-reports-script',
				GIVE_FUNDS_ADDON_URL . 'public/js/give-funds-reports.js',
				[ 'give-funds-script-backend' ],
				GIVE_FUNDS_ADDON_VERSION,
				true
			);
		}

		wp_localize_script(
			'give-funds-script-backend',
			'GiveFunds',
			[
				'adminUrl'     => admin_url(),
				'apiRoot'      => esc_url_raw( rest_url( 'give-api/v2/give-funds' ) ),
				'apiNonce'     => wp_create_nonce( 'wp_rest' ),
				'funds'        => $fundsData,
				'currency'     => give_get_option( 'currency' ),
				'allTimeStart' => DonationOptions::getAllTimeStart()
			]
		);

		wp_localize_script(
			'give-funds-menu-item',
			'GiveFundsMenu',
			[
				'reportsUrl' => admin_url( 'edit.php?post_type=give_forms&page=' . ReportsPage::SLUG )
			]
		);

		// Add js translations
		wp_set_script_translations( 'give-funds-script-backend', 'give-funds' );
	}

	/**
	 * Load add-on front-end assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function loadFrontendAssets() {
		wp_enqueue_style(
			'give-funds-style-frontend',
			GIVE_FUNDS_ADDON_URL . 'public/css/give-funds.css',
			[],
			GIVE_FUNDS_ADDON_VERSION
		);

		wp_enqueue_script(
			'give-funds-script-frontend',
			GIVE_FUNDS_ADDON_URL . 'public/js/give-funds.js',
			[],
			GIVE_FUNDS_ADDON_VERSION,
			true
		);
	}
}
