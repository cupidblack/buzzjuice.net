<?php

namespace GiveCurrencySwitcher\Infrastructure;

class Log extends \Give\Log\Log {
	/**
	 * @since 1.5.0
	 *
	 * @inheritDoc
	 */
	public static function __callStatic( $name, $arguments ) {
		$arguments[1]['source'] = 'Currency Switcher';

		parent::__callStatic( $name, $arguments );
	}
}
