<?php

namespace GiveFunds\Admin;

interface AdminPage {

	/**
	 * Handle data save
	 * @return void
	 */
	public function handleData();

	/**
	 * Register Admin page
	 * @return void
	 */
	public function registerPage();
}
