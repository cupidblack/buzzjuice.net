<?php

namespace GiveFunds\Infrastructure;

use Give\Log\Log as GiveLog;

/**
 * Class Log
 *
 * @since 1.1.0
 */
class Log extends GiveLog {
	/**
	 * @param string $name
	 * @param array $arguments
	 */
	public static function __callStatic( $name, $arguments ) {
		$arguments[ 1 ][ 'source' ] = GIVE_FUNDS_ADDON_NAME;

		parent::__callStatic( $name, $arguments );
	}
}
