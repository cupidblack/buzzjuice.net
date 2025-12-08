<?php

namespace GiveFunds\Admin;

use GiveFunds\Repositories\Funds;
use GiveFunds\Infrastructure\Notices;
use GiveFunds\Infrastructure\View;

/**
 * Funds page
 *
 * @package GiveFunds\Admin
 */
class FundsPage implements AdminPage {

	/**
	 * Page slug
	 *
	 * @var string
	 */
	const SLUG = 'give-funds';

	/**
	 * @var Funds
	 */
	private $fundsRepository;

	/**
	 * Funds constructor.
	 *
	 * @param Funds $fundsRepository
	 */
	public function __construct( Funds $fundsRepository ) {
		$this->fundsRepository = $fundsRepository;
	}

	/**
	 * Register Funds submenu page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function registerPage() {
		add_submenu_page(
			'edit.php?post_type=give_forms',
			esc_html__( 'Give Funds', 'give-funds' ),
			esc_html__( 'Funds', 'give-funds' ),
			'manage_options',
			self::SLUG,
			[ $this, 'renderPage' ],
			5
		);
	}

	/**
	 * Render Funds list table
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function renderPage() {
		/* @var FundsListTable $fundsListTable */
		$fundsListTable = give( FundsListTable::class );

		$fundsListTable->prepare_items();
		// Render Funds list page
		View::render(
			'admin/funds-page',
			[
				'table' => $fundsListTable
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
		// Bailout
		if (
			! current_user_can( 'manage_give_settings' )
			|| ! isset( $_REQUEST['give-funds-action'], $_REQUEST['give-funds-nonce'] )
		) {
			return;
		}

		// Handle actions.
		switch ( $_REQUEST['give-funds-action'] ) {
			case 'delete-fund':
				// Check ID
				if ( ! isset( $_GET['id'] ) ) {
					break;
				}

				// Verify nonce for delete fund
				if ( ! wp_verify_nonce( $_REQUEST['give-funds-nonce'], sprintf( 'give-funds-delete-%d', $_GET['id'] ) ) ) {
					return;
				}

				try {
					$this->fundsRepository->deleteFund( (int) $_GET['id'] );
					Notices::add( 'success', esc_html__( 'Fund deleted', 'give-funds' ) );
				} catch ( \Exception $e ) {
					Notices::add( 'error', esc_html__( 'Something went wrong, fund is not deleted', 'give-funds' ) );
				}

				break;
		}

	}
}
