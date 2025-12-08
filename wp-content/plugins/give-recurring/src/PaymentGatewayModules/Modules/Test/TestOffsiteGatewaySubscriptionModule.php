<?php

namespace GiveRecurring\PaymentGatewayModules\Modules\Test;

use Exception;
use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Http\Response\Types\RedirectResponse;
use Give\Framework\PaymentGateways\Commands\RedirectOffsite;
use Give\Framework\PaymentGateways\Contracts\Subscription\SubscriptionAmountEditable;
use Give\Framework\PaymentGateways\SubscriptionModule;
use Give\Framework\Support\ValueObjects\Money;
use Give\Subscriptions\Models\Subscription;
use Give\Subscriptions\ValueObjects\SubscriptionStatus;

/**
 * @since 2.5.0
 */
class TestOffsiteGatewaySubscriptionModule extends SubscriptionModule implements SubscriptionAmountEditable
{
    /**
     * @inheritDoc
     */
    public $secureRouteMethods = [
        'securelyReturnFromOffsiteRedirectSubscription'
    ];

    /**
     * @since 2.5.0
     */
    public function createSubscription(
        Donation $donation,
        Subscription $subscription,
        $gatewayData
    ): RedirectOffsite {
        $redirectUrl = $this->gateway->generateSecureGatewayRouteUrl(
            'securelyReturnFromOffsiteRedirectSubscription',
            $donation->id,
            [
                'givewp-donation-id' => $donation->id,
                'givewp-subscription-id' => $subscription->id,
                'givewp-return-url' => $gatewayData['successUrl']
            ]
        );

        return new RedirectOffsite($redirectUrl);
    }

     /**
     * An example of using a secureRouteMethod for extending the Gateway API to handle a redirect.
     *
     * @since 2.5.0
      *
     * @throws Exception
     */
    protected function securelyReturnFromOffsiteRedirectSubscription(array $queryParams): RedirectResponse
    {
        /** @var Donation $donation */
        $donation = Donation::find($queryParams['givewp-donation-id']);
        $this->updateDonation($donation);

        if ($donation->type->isSubscription()) {
            /** @var Subscription $subscription */
            $subscription = Subscription::find($queryParams['givewp-subscription-id']);
            $this->updateSubscription($subscription);
        }

        return new RedirectResponse($queryParams['givewp-return-url']);
    }

    /**
     * @since 2.5.0
     *
     * @return void
     * @throws Exception
     */
    private function updateDonation(Donation $donation)
    {
        $donation->status = DonationStatus::COMPLETE();
        $donation->gatewayTransactionId = "test-gateway-transaction-id";
        $donation->save();

        DonationNote::create([
            'donationId' => $donation->id,
            'content' => 'Donation Completed from Test Gateway Offsite.'
        ]);
    }

     /**
     * @since 2.5.0
     *
     * @return void
     * @throws Exception
     */
    private function updateSubscription(Subscription $subscription)
    {
        $subscription->status = SubscriptionStatus::ACTIVE();
        $subscription->transactionId = "test-gateway-transaction-id";
        $subscription->save();
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
}
