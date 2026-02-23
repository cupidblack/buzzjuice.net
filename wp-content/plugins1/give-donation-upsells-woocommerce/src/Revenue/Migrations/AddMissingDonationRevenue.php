<?php

namespace GiveWooCommerceUpsells\Revenue\Migrations;

use Give\Framework\Migrations\Contracts\Migration;
use Give\Log\Log;
use Give\Revenue\Repositories\Revenue;
use Give\ValueObjects\Money;
use Give_Payment;
use Give_Payments_Query;
use Give_Updates;
use Exception;

/**
 * Makes sure that donations that were not added to the revenue table are added
 *
 * @since 1.2.0
 */
class AddMissingDonationRevenue extends Migration {
	/**
	 * Register background update.
	 *
	 * @since 2.9.0
	 *
	 * @param Give_Updates $give_updates
	 *
	 */
	public function register( $give_updates ) {
		$give_updates->register(
			[
				'id' => self::id(),
				'version' => '1.2.0',
				'callback' => [ $this, 'run' ],
			]
		);
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		/* @var Revenue $revenueRepository */
		$revenueRepository = give( Revenue::class );
		$give_updates = Give_Updates::get_instance();

		$donations = new Give_Payments_Query( [
			'gateway' => 'woocommerce',
			'paged' => $give_updates->step,
			'status' => 'any',
			'order' => 'ASC',
			'post_type' => [ 'give_payment' ],
			'posts_per_page' => 100,
		] );

		$donations = $donations->get_payments();

		if ( ! empty( $donations ) ) {
			$give_updates->set_percentage( count( $donations ), $give_updates->step * 100 );

			foreach ( $donations as $donation ) {
				/** @var Give_Payment $donation */
				$revenueData = [
					'donation_id' => $donation->ID,
					'form_id' => $donation->form_id,
					'amount' => Money::of( $donation->total, give_get_option( 'currency' ) )->getMinorAmount(),
				];

				try {
					if ( $revenueRepository->isDonationExist( $donation->ID ) ) {
						continue;
					}

					$revenueRepository->insert( $revenueData );
				} catch ( Exception $e ) {
					$give_updates->__pause_db_update( true );
					update_option( 'give_upgrade_error', 1, false );

					Log::error(
						esc_html__( 'An error occurred inserting data into the revenue table', 'give-woocommerce' ),
						[
							'source' => 'Revenue Migration',
							'Data' => $revenueData,
							'Error' => $e->getMessage(),
						]
					);

					wp_die();
				}
			}
		} else {
			// Update Ran Successfully.
			give_set_upgrade_complete( self::id() );
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'add-missing-donation-revenue';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2021-08-10' );
	}

	/**
	 * @inheritdoc
	 */
	public static function source() {
		return esc_html__( 'Donation Upsells For WooCommerce', 'give-woocommerce' );
	}
}
