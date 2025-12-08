<?php

namespace GiveWooCommerceUpsells\Infrastructure;

/**
 * Class Log
 *
 * @package GiveWooCommerceUpsells\Infrastructure
 *
 * @since 1.2.0
 */
class Log extends \Give\Log\Log {
	/**
	 * @inheritDoc
	 *
	 * @param  string  $type
	 * @param  array  $args
	 *
	 * @since 1.2.0
	 *
	 */
	public static function __callStatic( $name, $arguments ) {
		$arguments[1]['source'] = 'Donation-Woocommerce-Upsells';

		parent::__callStatic( $name, $arguments );
	}
}
