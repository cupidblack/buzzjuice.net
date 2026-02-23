<?php

namespace GiveWooCommerceUpsells\Infrastructure;

/**
 * Helper class responsible for showing add-on notices.
 *
 * @package     GiveTextToGive\Infrastructure
 * @since 1.2.0
 */
class Notices {

	/**
	 * Add notice
	 *
	 * @param  string  $type
	 * @param  string  $description
	 * @param  bool  $show
	 *
	 * @since 1.2.0
	 */
	public static function add( $type, $description, $show = true ) {
		Give()->notices->register_notice(
			[
				'id'          => sprintf( 'give-woocommerce-notice-%s', $type ),
				'type'        => $type,
				'description' => $description,
				'show'        => $show,
			]
		);
	}

	/**
	 * GiveWP min required version notice.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function giveVersionError() {
		self::add( 'error', View::load( 'admin/notices/give-version-error' ) );
	}

	/**
	 * GiveWP inactive notice.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function giveInactive() {
		printf( View::load( 'admin/notices/give-inactive' ) );
	}

	/**
	 * GiveWP inactive notice.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function woocommerceInactive() {
		printf( View::load( 'admin/notices/woocommerce-inactive' ) );
	}
}
