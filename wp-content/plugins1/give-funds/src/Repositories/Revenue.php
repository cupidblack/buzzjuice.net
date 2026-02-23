<?php
namespace GiveFunds\Repositories;

use Give\ValueObjects\Money;
use InvalidArgumentException;

class Revenue {
	/**
	 * Get donations count for Fund
	 *
	 * @param int $fundId
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getFundDonationsCount( $fundId ) {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT count(revenue.id) as count
				FROM {$wpdb->give_revenue} as revenue
				INNER JOIN {$wpdb->posts} as posts
				ON revenue.donation_id = posts.ID
				WHERE fund_id = %d
				AND posts.post_status IN ( 'publish', 'give_subscription' )
				",
				$fundId
			)
		);

		if ( ! $result ) {
			return 0;
		}

		return $result->count;
	}


	/**
	 * Get fund revenue
	 *
	 * @param int $fundId
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getFundRevenue( $fundId ) {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT SUM(revenue.amount) as amount
				FROM {$wpdb->give_revenue} as revenue
				INNER JOIN {$wpdb->posts} as posts
				ON revenue.donation_id = posts.ID
				WHERE fund_id = %d
				AND posts.post_status IN ( 'publish', 'give_subscription' )
				",
				$fundId
			)
		);

		if ( ! $result ) {
			return 0;
		}

		return Money::ofMinor( $result->amount, give_get_option( 'currency' ) )->getAmount();
	}

	/**
	 * Get Donation Fund ID.
	 *
	 * @param int $donationId
	 * @since 1.0.0
	 * @return int|false
	 */
	public function getDonationFundId( $donationId ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT fund_id
				FROM {$wpdb->give_revenue}
				WHERE donation_id = %d
				",
				$donationId
			)
		);
	}

	/**
	 * Get all Donation by Fund ID.
	 *
	 * @param int $fundId
	 * @since 1.0.0
	 * @return array Donation IDs
	 */
	public function getAllDonationByFundId( $fundId ) {
		global $wpdb;

		$data = [];

		$result = $wpdb->get_results(
			$wpdb->prepare( "SELECT donation_id as id FROM {$wpdb->give_revenue} WHERE fund_id = %d", $fundId )
		);

		if ( $result ) {
			foreach ( $result as $donation ) {
				$data[] = $donation->id;
			}
		}

		return $data;
	}

	/**
	 * Assign revenue to a Fund.
	 *
	 * @param int $fundId
	 * @param array|int $fund
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function assignRevenue( $fundId, $fund ) {
		global $wpdb;

		// Fund ID(s)
		$ids = is_array( $fund )
			? implode( ',', array_map( 'intval', $fund ) )
			: (int) $fund;

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:disable
				'UPDATE %1$s SET fund_id = %2$s WHERE fund_id IN (%3$s)',
				$wpdb->give_revenue,
				(int) $fundId,
				$ids
			)
		);
	}


	/**
	 * Update Donation fund.
	 *
	 * @param int $donationId
	 * @param int $fundId
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function updateDonationFund( $donationId, $fundId ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->give_revenue,
			[
				'donation_id' => $donationId,
				'fund_id'     => $fundId
			],
			[
				'donation_id' => $donationId
			],
			[
				'%d'
			]
		);
	}

	/**
	 * Update Form fund.
	 *
	 * @param int $formId
	 * @param int $fundId
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function updateFormFund( $formId, $fundId ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->give_revenue,
			[
				'form_id' => $formId,
				'fund_id' => $fundId
			],
			[
				'form_id' => $formId
			],
			[
				'%d'
			]
		);
	}

	/**
	 * Validate new revenue data.
	 *
	 * @since 2.9.0
	 *
	 * @param array $array
	 */
	protected function validateNewRevenueData( $array ) {
		$required = [ 'donation_id', 'form_id', 'amount', 'fund_id' ];

		if ( array_diff( $required, array_keys( $array ) ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'To insert revenue, please provide valid %1$s.',
					implode( ', ', $required )
				)
			);
		}

		foreach ( $required as $columnName ) {
			if ( empty( $array[ $columnName ] ) ) {
				throw new InvalidArgumentException( 'Empty value is not allowed to create revenue.' );
			}
		}

		/* @var Funds $fundRepository */
		$fundRepository = give( Funds::class );
		if ( ! $fundRepository->isFundExist( $array['fund_id'] ) ) {
			throw new InvalidArgumentException( 'Can not associate invalid fund id to revenue.' );
		}
	}
}
