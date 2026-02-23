<?php

namespace GiveRecurring\PaymentGatewayModules\Actions;

/**
 * Class CreateSubscriptionDonationData
 *
 * payPalSubscriptionId is additional information which is not present in gatewayData.
 * This action will add payPalSubscriptionId in gatewayData which will be used to create subscription.
 *
 * @since 2.5.0
 */
class CreateSubscriptionGatewayData
{
    public function __invoke($gatewayData)
    {
        $gatewayData['payPalSubscriptionId'] = $gatewayData['payPalSubscriptionId']
            ?? give_clean($_POST['gatewayData']['payPalSubscriptionId']);

        return $gatewayData;
    }
}
