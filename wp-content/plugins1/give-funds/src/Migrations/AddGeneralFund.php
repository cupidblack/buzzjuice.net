<?php
namespace GiveFunds\Migrations;

use Exception;
use Give\Framework\Migrations\Contracts\Migration;
/**
 * Class AddGeneralFund
 * @package GiveFunds\Migrations
 *
 * @since 2.9.0
 */
class AddGeneralFund extends Migration {
	/**
	 * @inheritdoc
	 * @throws Exception
	 */
	public function run() {
		global $wpdb;

		if ( $wpdb->get_var( "SELECT id from {$wpdb->give_funds} WHERE is_default = 1" ) ) {
			return;
		}

		$currentDate = date( 'Y-m-d H:i:s' );

		$wpdb->insert(
			$wpdb->give_funds,
			[
				'title'         => esc_html__( 'General', 'give-funds' ),
				'is_default'    => 1,
				'author_id'     => get_current_user_id(),
				'date_created'  => $currentDate,
				'date_modified' => $currentDate
			]
		);
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'add-general-fund';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2019-09-17 02:00:00' );
	}
}
