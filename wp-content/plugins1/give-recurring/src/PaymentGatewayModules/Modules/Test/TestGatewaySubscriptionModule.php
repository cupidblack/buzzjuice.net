<?php

namespace GiveRecurring\PaymentGatewayModules\Modules\Test;

use Exception;
use Give\Donations\Models\Donation;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Donations\ValueObjects\DonationType;
use Give\Framework\PaymentGateways\Commands\SubscriptionComplete;
use Give\Framework\PaymentGateways\Commands\SubscriptionSynced;
use Give\Framework\PaymentGateways\Contracts\Subscription\SubscriptionTransactionsSynchronizable;
use Give\Framework\PaymentGateways\SubscriptionModule;
use Give\Framework\Support\ValueObjects\Money;
use Give\Subscriptions\Models\Subscription;
use Give\Subscriptions\ValueObjects\SubscriptionPeriod;
use Give\Subscriptions\ValueObjects\SubscriptionStatus;

/**
 * @since 2.5.0
 */
class TestGatewaySubscriptionModule extends SubscriptionModule implements SubscriptionTransactionsSynchronizable
{
    /**
     * @since 2.5.0
     */
    public function createSubscription(
        Donation $donation,
        Subscription $subscription,
        $gatewayData = null
    ): SubscriptionComplete {
        return new SubscriptionComplete(
            "test-gateway-transaction-id-$donation->id",
            "test-gateway-subscription-id-$subscription->id"
        );
    }


    /**
     * @since 2.5.0
     *
     * @throws Exception
     */
    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->status = SubscriptionStatus::CANCELLED();
        $subscription->save();
    }

    /**
     * @since 2.5.0
     *
     * @throws Exception
     */
    public function updateSubscriptionAmount(Subscription $subscription, Money $newRenewalAmount)
    {
        $subscription->amount = $newRenewalAmount;
        $subscription->save();
    }

    /**
     * @since 2.5.0
     *
     * @throws Exception
     */
    public function synchronizeSubscription(Subscription $subscription): SubscriptionSynced
    {
        /**
         * Step #1 - DETAILS: check if the subscription is up-to-date with the gateway;
         **/
        $gatewayStatus = SubscriptionStatus::ACTIVE();
        $gatewayPeriod = SubscriptionPeriod::MONTH();
        $gatewayCreatedAt = $subscription->createdAt;
        $subscription->status = $gatewayStatus;
        $subscription->period = $gatewayPeriod;
        $subscription->createdAt = $gatewayCreatedAt;

        /**
         * Step #2 - TRANSACTIONS: check the transaction list for the subscription on the gateway side and create the
         * missing transactions (as renewal donations) on our side, then store them in an array and also the already
         * present transactions on our side in another array;
         */
        $missingDonations[] = $this->createRenewalDonation($subscription);
        $missingDonations[] = $this->createRenewalDonation($subscription);
        $presentDonations = $subscription->donations;

        /**
         * Step #3 - When this command gets handled by our API, it will return a JSON response to be used on the UI
         * with the subscription details and the missing/present transactions created in the previous step.
         */
        return new SubscriptionSynced(
            $subscription, // do not save the subscription, so our API can see what's dirty
            $missingDonations, // array<Donation> of the added missing donations
            $presentDonations, // array<Donation> of the already present donations
            __('Sample Notice: Test Gateway can only synchronize as far back as 6 months', 'give-recurring')
        );
    }

    /**
     * @since 2.5.0
     *
     * @throws Exception
     */
    private function createRenewalDonation(Subscription $subscription): Donation
    {
        return Donation::create([
            'subscriptionId' => $subscription->id,
            'amount' => $subscription->amount,
            'status' => DonationStatus::COMPLETE(),
            'type' => DonationType::RENEWAL(),
            'donorId' => $subscription->donorId,
            'firstName' => $subscription->donor->firstName,
            'lastName' => $subscription->donor->lastName,
            'email' => $subscription->donor->email,
            'gatewayId' => $subscription->gatewayId,
            'formId' => $subscription->donationFormId,
            'gatewayTransactionId' => uniqid(),
        ]);
    }
}
