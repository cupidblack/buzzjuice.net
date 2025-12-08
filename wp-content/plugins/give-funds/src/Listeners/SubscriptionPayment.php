<?php

namespace GiveFunds\Listeners;

use Give_Payment;
use Give_Recurring;
use Give_Subscription;
use GiveFunds\Repositories\Revenue;

/**
 * Assign fund to subscription payment
 *
 * @since 1.1.0
 */
class SubscriptionPayment {
	/**
	 * @var Revenue
	 */
	private $revenueRepository;

	/**
	 * @param  Revenue  $revenueRepository
	 */
	public function __construct( Revenue $revenueRepository ) {
		$this->revenueRepository = $revenueRepository;
	}

	/**
     * @since 1.1.0 Get the fund from the subscription, not the initial donation
     *
	 * @param  Give_Payment  $payment
	 * @param  Give_Subscription  $subscription
	 */
	public function handleRenewal( Give_Payment $payment, Give_Subscription $subscription ) {
		$fundId = Give_Recurring::instance()->subscription_meta->get_meta( $subscription->id, 'fund_id', true );

		if ( empty($fundId) ) {
			return;
		}

		add_filter(
			 'give_revenue_insert_data',
			 static function ( $data ) use ( $payment, $fundId ) {
				 if ( $payment->ID == $data[ 'donation_id' ] ) {
					  $data[ 'fund_id' ] = $fundId;
				 }

				 return $data;
			 }
		);
	}

	/**
	 * Assign Fund to Subscription
	 *
	 * @param  int  $id  Subscription ID
	 * @param  array  $data  Subscription data
	 */
	public function handleSubscription( $id, $data ) {
		if ( $fundId = $this->revenueRepository->getDonationFundId( $data[ 'parent_payment_id' ] ) ) {
			Give_Recurring::instance()->subscription_meta->update_meta(
				$id,
				'fund_id',
				$fundId
			);
		}
	}
}
