<?php

namespace GiveRecurring\PaymentGatewayModules\Modules\PayPalStandard\Adapters;

use Give_Subscription;
use Give_Subscriptions_DB;

/**
 * This is an adapter for all the legacy PayPal Standard webhooks.
 *
 * Eventually these will be updated, but for now their integrity is preserved.
 *
 * @since 2.5.0
 */
class PayPalStandardGatewayLegacyWebhooksAdapter
{
     /**
     * @since 2.5.0
     */
    public function processWebhooks()
    {
        // Process PayPal subscription sign ups.
        add_action('give_paypal_subscr_signup', array($this, 'process_paypal_subscr_signup'));

        // Process PayPal subscription payments.
        add_action('give_paypal_subscr_payment', array($this, 'process_paypal_subscr_payment'));

        // Process PayPal subscription cancellations.
        add_action('give_paypal_subscr_cancel', array($this, 'process_paypal_subscr_cancel'));

        // Process PayPal subscription end of term notices.
        add_action('give_paypal_subscr_eot', array($this, 'process_paypal_subscr_eot'));

        // Process PayPal payment failed.
        add_action('give_paypal_subscr_failed', array($this, 'process_paypal_subscr_failed'));
    }

    /**
	 * Processes the "signup" IPN notice
	 *
	 * @param $ipn_data
	 *
	 * @return void
	 */
	public function process_paypal_subscr_signup( $ipn_data ) {

		$parent_payment_id = absint( $ipn_data['custom'] );

		if ( empty( $parent_payment_id ) ) {
			return;
		}

		// Check for payment
		if ( ! give_get_payment_by( 'id', $parent_payment_id ) ) {
			return;
		}

		give_update_payment_status( $parent_payment_id, 'publish' );

		// Record PayPal subscription ID
		if ( isset( $ipn_data['subscr_id'] ) ) {
			give_insert_payment_note( $parent_payment_id, sprintf( __( 'PayPal Subscription ID: %s', 'give-recurring' ), $ipn_data['subscr_id'] ) );
		}

		$subscription = $this->get_subscription( $ipn_data );

		if ( false === $subscription ) {
			return;
		}

		// Retrieve pending subscription from database and update it's status to active and set proper profile ID
		$subscription->update( array(
			'profile_id' => $ipn_data['subscr_id'],
			'status'     => 'active',
		) );

	}

    /**
	 * Processes the recurring payments as they come in.
	 *
	 * @param array $ipn_data The data from PayPal Standard's IPN request.
	 *
	 * @since  1.0
	 * @return void
	 */
	public function process_paypal_subscr_payment( $ipn_data ) {

		$subscription = $this->get_subscription( $ipn_data );

		if ( false === $subscription ) {
			return;
		}

		$transaction_id = give_get_payment_transaction_id( $subscription->parent_payment_id );
		$signup_date    = strtotime( $subscription->created );
		$today          = date( 'Y-n-d', $signup_date ) == date( 'Y-n-d', strtotime( $ipn_data['payment_date'] ) );

		// Look to see if payment is same day as sign up and we haven't set the transaction ID on the parent payment yet.
		if ( $today && ( ! $transaction_id || $transaction_id == $subscription->parent_payment_id ) ) {

			// Verify the amount paid.
			$initial_amount = round( $subscription->initial_amount, 2 );
			$paid_amount    = round( $ipn_data['mc_gross'], 2 );

			if ( $paid_amount < $initial_amount ) {

				$payment         = new Give_Payment( $subscription->parent_payment_id );
				$payment->status = 'failed';
				$payment->add_note( __( 'Payment failed due to invalid amount in PayPal Recurring IPN.', 'give-recurring' ) );
				$payment->save();

				give_record_gateway_error( __( 'IPN Error', 'give-recurring' ), sprintf( __( 'Invalid payment amount in IPN subscr_payment response. IPN data: %s', 'give-recurring' ), json_encode( $ipn_data ) ), $payment->ID );

				return;
			}

			// This is the very first payment so set the transaction ID.
			$subscription->set_transaction_id( $ipn_data['txn_id'] );
			give_set_payment_transaction_id( $subscription->parent_payment_id, $ipn_data['txn_id'] );

			return;
		}

		// Is this payment already recorded?
		if ( give_get_purchase_id_by_transaction_id( $ipn_data['txn_id'] ) ) {
			return; // Payment already recorded.
		}

		$args = array(
			'amount'         => $ipn_data['mc_gross'],
			'transaction_id' => $ipn_data['txn_id']
		);

		$subscription->add_payment( $args );
		$subscription->renew();

	}

    /**
	 * Processes the "cancel" IPN notice
	 *
	 * @since  1.0
	 *
	 * @param $ipn_data
	 */
	public function process_paypal_subscr_cancel( $ipn_data ) {

		$subscription = $this->get_subscription( $ipn_data );

		if ( false === $subscription ) {
			return;
		}

		$subscription->cancel();

	}

    /**
	 * Processes the "end of term (eot)" IPN notice
	 *
	 * @since  1.0
	 * @return void
	 */
	public function process_paypal_subscr_eot( $ipn_data ) {

		$subscription = $this->get_subscription( $ipn_data );

		if ( false === $subscription ) {
			return;
		}

		// Subscription must be active to set status completed
		if( 'active' !== $subscription->status ) {
			return;
		}

		$subscription->complete();

	}

    /**
	 * Processes the payment failed IPN notice
	 *
	 * @since  1.1.2
	 * @return void
	 */
	public function process_paypal_subscr_failed( $ipn_data ) {

		$subscription = $this->get_subscription( $ipn_data );

		if ( false === $subscription ) {
			return;
		}

		$subscription->failing();

		do_action( 'give_recurring_payment_failed', $subscription );

	}

    /**
	 * Retrieve the subscription this IPN notice is for.
	 *
	 * @since  1.1.2
	 *
	 * @param array $ipn_data Optional. IPN data from PayPal. Default is empty array.
	 *
	 * @return Give_Subscription|false
	 */
	public function get_subscription( $ipn_data = array() ) {

		$parent_payment_id = absint( $ipn_data['custom'] );

		if ( empty( $parent_payment_id ) ) {
			return false;
		}

		$payment = give_get_payment_by( 'id', $parent_payment_id );

		if ( ! $payment ) {
			return false;
		}

		$subscription = new Give_Subscription( $ipn_data['subscr_id'], true );

		if ( ! $subscription || $subscription->id < 1 ) {

			$subs_db      = new Give_Subscriptions_DB;
			$subs         = $subs_db->get_subscriptions( array(
				'parent_payment_id' => $parent_payment_id,
				'number'            => 1,
			) );
			$subscription = reset( $subs );

			if ( $subscription && $subscription->id > 0 ) {

				// Update the profile ID so it is set for future renewals
				$subscription->update( array(
					'profile_id' => sanitize_text_field( $ipn_data['subscr_id'] ),
				) );

			} else {

				// No subscription found with a matching payment ID, bail
				return false;

			}
		}

		return $subscription;

	}

    /**
	 * Retrieve PayPal API credentials
	 *
	 * @access      public
	 * @since       1.0
	 *
	 * @return mixed
	 */
	public function get_paypal_standard_api_credentials() {

		$prefix = 'live_';

		if ( give_is_test_mode() ) {
			$prefix = 'test_';
		}

		$creds = array(
			'username'  => give_get_option( $prefix . 'paypal_standard_api_username' ),
			'password'  => give_get_option( $prefix . 'paypal_standard_api_password' ),
			'signature' => give_get_option( $prefix . 'paypal_standard_api_signature' ),
		);

		return apply_filters( 'give_recurring_get_paypal_standard_api_credentials', $creds );
	}

}