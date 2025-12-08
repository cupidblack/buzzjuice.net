<?php

namespace GiveFunds\Admin;

use GiveFunds\Repositories\Funds as FundsRepository;
use GiveFunds\Infrastructure\View;


/**
 * Overview Fund page
 *
 * @package GiveFunds\Admin
 */
class ReportsPage implements AdminPage {

	const SLUG = 'give-funds-reports';

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
		// Render view
		View::render(
			'admin/reports-page',
			[
				'reportsUrl' => admin_url( 'edit.php?post_type=give_forms&page=' . self::SLUG )
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
