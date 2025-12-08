<?php

namespace GiveP2P\P2P\Admin\Contracts;

/**
 * Class AdminPage
 * @package GiveP2P\P2P\Admin\Contracts
 *
 * @since 1.0.0
 */
abstract class AdminPage {
	/**
	 * Register Admin page
	 */
	public function registerPage() {}

	/**
	 * Render Admin page
	 */
	public function renderPage() {}
}
