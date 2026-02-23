<?php

namespace GiveFunds\Admin;

use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Factories\Funds as FundsFactory;
use GiveFunds\Infrastructure\Notices;
use GiveFunds\Infrastructure\View;


/**
 * Edit Fund page
 *
 * @package GiveFunds\Admin
 */
class EditFundPage implements AdminPage {

	const SLUG = 'give-edit-fund';

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * @var FundsFactory
	 */
	private $fundsFactory;

	/**
	 * EditFund constructor.
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
	 * Register Edit Fund page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function registerPage() {
		add_submenu_page( '', '', '', 'manage_options', self::SLUG, [ $this, 'renderPage' ] );
	}

	/**
	 * Render Edit Fund page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function renderPage() {
		$fund = $this->fundsRepository->getFund( absint( $_GET['id'] ) );

		if ( ! $fund ) {
			wp_redirect( 'edit.php?post_type=give_forms&page=give-funds' );
		}

		// Render view
		View::render(
			'admin/edit-fund-page',
			[
				'fund'       => $fund,
				'deleteLink' => wp_nonce_url(
					admin_url( 'edit.php?post_type=give_forms&page=give-funds&give-funds-action=delete-fund&id=' . $fund->getId() ),
					'give-funds-delete-' . $fund->getId(),
					'give-funds-nonce'
				),
			]
		);
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
			! isset( $_POST['give-funds-edit-fund'], $_POST['give-funds-nonce'] )
			|| ! wp_verify_nonce( $_POST['give-funds-nonce'], sprintf( 'edit-fund-%d', $_POST['give-funds-id'] ) )
		) {
			return;
		}

		// Check title
		if ( empty( trim( $_POST['give-funds-title'] ) ) ) {
			Notices::add( 'error', esc_html__( 'Enter fund title', 'give-funds' ) );

			return;
		}

		try {
			// Make Fund
			$fund = $this->fundsFactory->make(
				$_POST['give-funds-id'],
				give_clean( $_POST['give-funds-title'] ),
				give_clean( $_POST['give-funds-description'] ),
				get_current_user_id()
			);

			$this->fundsRepository->saveFund( $fund );
			// Add notice
			Notices::add( 'success', esc_html__( 'Fund updated', 'give-funds' ) );
			// Unset post data
			unset( $_POST );
		} catch ( \Exception $e ) {
			Notices::add( 'error', $e->getMessage() );
		}
	}
}
