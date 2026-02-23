<?php
namespace GiveFunds;

use GiveFunds\Infrastructure\Environment;
use GiveFunds\Repositories\Funds;

/**
 * Class responsible for registering and handling add-on activation hooks.
 *
 * @package     GiveFunds
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Activation {
	/**
	 * Activate add-on action hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activateAddon() {
		// Bailout if requirements are not meet
		if (
			! Environment::giveMinRequiredVersionCheck() ||
			! Environment::revenueDatabaseTableExists() ||
			! Environment::fundsDatabaseTableExists()
		) {
			return;
		}

		give( Funds::class )->setNullFundIdsToDefaultFundId();
	}
}
