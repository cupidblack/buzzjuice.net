<?php

namespace GiveFunds\Admin;

use GiveFunds\Infrastructure\Notices;
use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Factories\Funds as FundsFactory;
use GiveFunds\Infrastructure\View;


/**
 * Add Fund page
 *
 * @package GiveFunds\Admin
 */
class AddFundPage implements AdminPage {

	const SLUG = 'give-add-fund';

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * @var FundsFactory
	 */
	private $fundsFactory;

	/**
	 * AddFund constructor.
	 *
	 * @param FundsRepository $fundsRepository
	 * @param FundsFactory    $fundsFactory
	 */
	public function __construct(
		FundsRepository $fundsRepository,
		FundsFactory $fundsFactory
	) {
		$this->fundsFactory    = $fundsFactory;
		$this->fundsRepository = $fundsRepository;
	}

	/**
	 * Register Add Fund page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function registerPage() {
		add_submenu_page( '', '', '', 'manage_options', self::SLUG, [ $this, 'renderPage' ] );
	}

	/**
	 * Render Add fund page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function renderPage() {
		View::render( 'admin/add-fund-page' );
	}

	/**
	 * Handle data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handleData() {
		// Check user permission.
		if ( ! current_user_can( 'manage_give_settings' ) ) {
			return;
		}

		// Verify nonce
		if (
			! isset( $_POST['give-funds-add-fund'], $_POST['give-funds-nonce'] )
			|| ! wp_verify_nonce( $_POST['give-funds-nonce'], 'add-fund' )
		) {
			return;
		}

		// Check title
		if ( empty( give_clean( $_POST['give-funds-title'] ) ) ) {
			Notices::add( 'error', esc_html__( 'Enter fund title', 'give-funds' ) );

			return;
		}

		try {
			// Make Fund
			$fund = $this->fundsFactory->make(
				'',
				give_clean( $_POST['give-funds-title'] ),
				give_clean( $_POST['give-funds-description'] ),
				get_current_user_id()
			);

			// Save Fund
			$this->fundsRepository->saveFund( $fund );
			// Add notice
			Notices::add( 'success', esc_html__( 'Fund added', 'give-funds' ) );
			// Unset post data
			unset( $_POST );
		} catch ( \Exception $e ) {
			Notices::add( 'error', $e->getMessage() );
		}
	}
}
