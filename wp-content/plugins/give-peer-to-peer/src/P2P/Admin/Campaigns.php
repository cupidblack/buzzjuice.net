<?php

namespace GiveP2P\P2P\Admin;

use GiveP2P\Addon\Helpers\Notices;
use GiveP2P\Addon\Helpers\View;
use GiveP2P\P2P\Admin\Contracts\AdminPage;

/**
 * Campaigns Page
 *
 * @package GiveP2P\P2P\Admin
 *
 * @since 1.0.0
 */
class Campaigns extends AdminPage {

	/**
	 * Page slug
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'p2p-campaigns';

	/**
	 * Campaigns constructor.
	 */
	public function __construct() {
		$this->registerNotices();
	}

	/**
	 * Register P2P Campaigns admin page
	 */
	public function registerPage() {
		add_submenu_page(
			'edit.php?post_type=give_forms',
			esc_html__( 'Give P2P Campaigns', 'give-peer-to-peer' ),
			esc_html__( 'P2P Campaigns', 'give-peer-to-peer' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'renderPage' ],
			1
		);
	}

	/**
	 * Register a notice depending on p2p-action query arg
	 */
	public function registerNotices() {
		if ( ! isset( $_GET['p2p-action'] ) ) {
			return;
		}

		switch ( $_GET['p2p-action'] ) {
			case 'campaign_added':
				Notices::add( 'success', esc_html__( 'New P2P Campaign added', 'give-peer-to-peer' ) );
				return;
			default:
		}
	}


	/**
	 * Render P2P Campaigns page
	 *
	 * @return void
	 */
	public function renderPage() {
		View::render( 'P2P.admin/campaigns' );
	}
}
