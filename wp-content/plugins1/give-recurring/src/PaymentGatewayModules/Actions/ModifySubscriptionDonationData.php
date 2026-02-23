<?php

namespace GiveRecurring\PaymentGatewayModules\Actions;

/**
 * Class ModifySubscriptionDonationData
 *
 * This class account for adding to recurring donation information the period, frequency, and times values being passed via post data.
 *
 * @since 2.5.0
 */
class ModifySubscriptionDonationData
{
    public function __invoke($recurringData)
    {
        /**
         * PayPal Donations/Commerce (NextGen)
         * Optionally account for the period, frequency, and times values being passed via post data.
         */
        if (isset($_GET['action']) && 'give_paypal_commerce_create_plan_id' === $_GET['action']) {
            $recurringData['period'] = $recurringData['period'] ?: $recurringData['post_data']['period'];
            $recurringData['frequency'] = $recurringData['frequency'] ?: $recurringData['post_data']['frequency'];
            $recurringData['times'] = $recurringData['times'] ?: $recurringData['post_data']['times'];
        }

        return $recurringData;
    }
}
