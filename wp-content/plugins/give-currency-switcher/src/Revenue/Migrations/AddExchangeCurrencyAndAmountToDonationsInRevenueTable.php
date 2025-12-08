<?php
namespace GiveCurrencySwitcher\Revenue\Migrations;

use Give\Framework\Migrations\Contracts\Migration;
use Give\Revenue\Migrations\AddPastDonationsToRevenueTable;
use Give\Revenue\Repositories\Revenue;
use Give\ValueObjects\Money;
use Give_Updates;
use Exception;

/**
 * Class AddPastDonationToRevenueTable
 *
 * Use this table to migrated past donations exchange currency code and amount to revenue table.
 * This data migration will perform in background.
 *
 * @package Give\Revenue\Migrations
 *
 * @since 1.3.12
 */
class AddExchangeCurrencyAndAmountToDonationsInRevenueTable extends Migration {
	/**
	 * Register background update.
	 *
	 * @param Give_Updates $give_updates
	 *
	 * @since 1.3.12
	 */
	public function register( $give_updates ) {
		$give_updates->register(
			[
				'id'       => self::id(),
				'version'  => '1.3.12',
				'callback' => [ $this, 'run' ],
				'depends'  => [ AddPastDonationsToRevenueTable::id() ]
			]
		);
	}

	/**
	 * @inheritdoc
	 */
	public function run() {
		global $wpdb;

		/* @var Revenue $revenueRepository */
		$give_updates = Give_Updates::get_instance();

		$totalDonations = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->give_revenue}" );

		if ( $totalDonations ) {
			$limit = 100;
			$give_updates->set_percentage(
				$totalDonations,
				$give_updates->step * $limit
			);
			$offset = ( $give_updates->step - 1 ) * $limit;

			$donationIds = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT donation_id
					FROM {$wpdb->give_revenue}
					LIMIT %d
					OFFSET %d
					",
					$limit,
					$offset
				)
			);

			if ( $donationIds ) {
				foreach ( $donationIds as $donationId ) {
					if ( ! $this->canUpdateDonationDetails( $donationId ) ) {
						continue;
					}

					$currencyCode = give_get_payment_currency_code( $donationId );

					try {
						$wpdb->update(
							$wpdb->give_revenue,
							[
								'exchange_amount'   => Money::of( give_donation_amount( $donationId ), $currencyCode )->getMinorAmount(),
								'exchange_currency' => $currencyCode
							],
							[
								'donation_id' => $donationId,
								'form_id'     => give_get_payment_form_id( $donationId )
							],
							[ '%d', '%s' ],
							[ '%d', '%d' ]
						);

					} catch ( Exception $e ) {
						give()->logs->add(
							'Update Error',
							sprintf(
								'Unable to set exchange currency and amount to revenue for this donation: ' . "\n" . '%1$s' . "\n" . '%2$s',
								$donationId,
								$e->getMessage()
							),
							0,
							'update'
						);

						continue;
					}
				}

				return;
			}
		}

		// Update Ran Successfully.
		give_set_upgrade_complete( self::id() );
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'add-exchange-currency-and-amount-to-revenue-table';
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2019-09-24' );
	}

	/**
	 * Return whether or not update revenue details.
	 *
	 * @since 1.3.12
	 *
	 * @param string $donationId
	 *
	 * @return bool
	 */
	private function canUpdateDonationDetails( $donationId ) {
		global $wpdb;

		$hasBaseCurrency = give_get_option( 'currency' ) === give_get_payment_currency_code( $donationId );
		$canUpdate       = ! $hasBaseCurrency;

		if ( $canUpdate ) {
			$hasExchangeCurrency = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT exchange_currency
				FROM {$wpdb->give_revenue}
				WHERE donation_id=%d``
				",
					$donationId
				)
			);

			$canUpdate = ! $hasExchangeCurrency;
		}

		return $canUpdate;
	}
}
