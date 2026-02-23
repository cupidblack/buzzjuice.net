<?php

namespace GiveFunds\Admin;

use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Infrastructure\View;


/**
 * Overview Fund page
 *
 * @package GiveFunds\Admin
 */
class OverviewFund implements AdminPage {

	const SLUG = 'give-fund-overview';

	/**
	 * @var FundsRepository
	 */
	private $fundsRepository;

	/**
	 * OverviewFund constructor.
	 *
	 * @param FundsRepository $fundsRepository
	 */
	public function __construct( FundsRepository $fundsRepository ) {
		$this->fundsRepository = $fundsRepository;
	}
	/**
	 * Register Overview page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function registerPage() {
		add_submenu_page( '', '', '', 'manage_options', self::SLUG, [ $this, 'renderPage' ] );
	}

	/**
	 * Render Overview fund page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function renderPage() {
		$fund = $this->fundsRepository->getFund( $_GET['id'] );

		if ( ! $fund ) {
			wp_redirect( 'edit.php?post_type=give_forms&page=give-funds' );
		}

		// Render view
		View::render(
			'admin/overview-page',
			[
				'fund' => $fund
			]
		);
	}

	/**
	 * Handle data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handleData() {}
}
