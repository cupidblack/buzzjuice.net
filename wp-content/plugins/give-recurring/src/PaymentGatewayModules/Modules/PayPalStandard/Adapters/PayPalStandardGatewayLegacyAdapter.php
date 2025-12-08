<?php

namespace GiveRecurring\PaymentGatewayModules\Modules\PayPalStandard\Adapters;

use Give\PaymentGateways\Gateways\PayPalStandard\PayPalStandard;
use Give_Subscription;

/**
 * This is an adapter for all the legacy PayPal Standard functions.
 *
 * Eventually these will be updated, but for now their integrity is preserved.
 *
 * @since 2.5.0
 */
class PayPalStandardGatewayLegacyAdapter
{
    /**
     * PORTED OVER FROM Give_Recurring_PayPal::can_cancel()
     */
    public function canCancelSubscription(bool $ret, Give_Subscription $subscription): bool
    {
        if ($subscription->gateway !== PayPalStandard::id()) {
            return $ret;
        }
        // Check gateway
        if ($subscription->status === 'active'
            && !empty($subscription->profile_id)
            && false !== strpos($subscription->profile_id, 'I-')
        ) {
            $ret = true;
        } else {
            $ret = false;
        }

        return $ret;
    }

    /**
     * PORTED OVER FROM Give_Recurring_PayPal::settings()
     *
	 * Adds the PayPal Standard settings to the Payment Gateways section
	 * that are required in order to cancel subscriptions on site using PayPal Standard.
	 *
     * @since 2.5.0
	 */
    public function settings(array $settings): array
    {
        $paypalStandardSettings = [
            [
				'id'   => 'paypal_standard_recurring_description',
				'name' => '&nbsp;',
				'desc' => '<p class="give-recurring-description give-paypal-description">' . sprintf( __( 'The following API keys are required in order to process PayPal Standard subscriptions cancellations on site. %1$sClick here%2$s to learn more about PayPal Standard\'s recurring capabilities and requirements.', 'give-recurring' ), '<a href="http://docs.givewp.com/recurring-paypal-standard" target="_blank" class="new-window">', '</a>' ) . '</p>',
				'type' => 'give_description',
            ],
            [
				'id'   => 'live_paypal_standard_api_username',
				'name' => __( 'Live API Username', 'give-recurring' ),
				'desc' => __( 'Enter your live API username', 'give-recurring' ),
				'type' => 'text',
            ],
            [
				'id'   => 'live_paypal_standard_api_password',
				'name' => __( 'Live API Password', 'give-recurring' ),
				'desc' => __( 'Enter your live API password', 'give-recurring' ),
				'type' => 'api_key',
            ],
            [
				'id'   => 'live_paypal_standard_api_signature',
				'name' => __( 'Live API Signature', 'give-recurring' ),
				'desc' => __( 'Enter your live API signature', 'give-recurring' ),
				'type' => 'api_key',
            ],
            [
				'id'   => 'test_paypal_standard_api_username',
				'name' => __( 'Test API Username', 'give-recurring' ),
				'desc' => __( 'Enter your test API username', 'give-recurring' ),
				'type' => 'text',
            ],
            [
				'id'   => 'test_paypal_standard_api_password',
				'name' => __( 'Test API Password', 'give-recurring' ),
				'desc' => __( 'Enter your test API password', 'give-recurring' ),
				'type' => 'api_key',
            ],
            [
				'id'   => 'test_paypal_standard_api_signature',
				'name' => __( 'Test API Signature', 'give-recurring' ),
				'desc' => __( 'Enter your test API signature', 'give-recurring' ),
				'type' => 'api_key',
            ],
        ];

		return give_settings_array_insert(
			$settings,
			'paypal_page_style',
            $paypalStandardSettings
		);
    }

    /**
     * PORTED OVER FROM Give_Recurring_PayPal::checkout_errors()
     *
     * @since 2.5.0
     */
    public function checkoutErrors($valid_data)
    {
        $post_data = give_clean($_POST); // WPCS: input var ok, sanitization ok, CSRF ok.

        if (!empty($post_data['give-gateway']) && PayPalStandard::id() !== $post_data['give-gateway']) {
            return;
        }

        if (!give_get_option('paypal_email', false)) {
            give_set_error(
                'give_recurring_paypal_email_missing',
                __('Please enter your PayPal email address.', 'give-recurring')
            );
        }
    }

    /**
     *
     * PORTED OVER FROM Give_Recurring_PayPal::validate_paypal_recurring_times()
     *
     * Validate PayPal Recurring Donation
     *
     * Additional server side validation for PayPal Standard recurring.
     *
     * @since 2.5.0
     */
    public function validatePaypalRecurringTimes(int $form_id = 0)
    {
        global $post;
        $recurring_option = isset($_REQUEST['_give_recurring']) ? $_REQUEST['_give_recurring'] : 'no';
        $set_or_multi = isset($_REQUEST['_give_price_option']) ? $_REQUEST['_give_price_option'] : '';

        // Sanity Checks
        if (!class_exists('Give_Recurring')) {
            return $form_id;
        }
        if ($recurring_option == 'no') {
            return $form_id;
        }
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined(
                    'DOING_AJAX'
                ) && DOING_AJAX) || isset($_REQUEST['bulk_edit'])) {
            return $form_id;
        }
        if (isset($post->post_type) && $post->post_type == 'revision') {
            return $form_id;
        }
        if (!isset($post->post_type) || $post->post_type != 'give_forms') {
            return $form_id;
        }
        if (!current_user_can('edit_give_forms', $form_id)) {
            return $form_id;
        }

        // Is this gateway active
        if (!give_is_gateway_active($this->id)) {
            return $form_id;
        }

        $message = __(
            'PayPal Standard requires recurring times to be more than 1. Please specify a time with a minimum value of 2 and a maximum value of 52.',
            'give-recurring'
        );

        if ($set_or_multi === 'multi' && $recurring_option == 'yes_admin') {
            $prices = isset($_REQUEST['_give_donation_levels']) ? $_REQUEST['_give_donation_levels'] : array('');
            foreach ($prices as $price_id => $price) {
                $time = isset($price['_give_times']) ? $price['_give_times'] : 0;
                // PayPal download allow times of "1" or above "52"
                // https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
                if ($time == 1 || $time >= 53) {
                    wp_die($message, __('Error', 'give-recurring'), array(
                        'response' => 400,
                    ));
                }
            }
        } else {
            if (Give_Recurring()->is_recurring($form_id)) {
                $time = isset($_REQUEST['_give_times']) ? $_REQUEST['_give_times'] : 0;

                if ($time == 1 || $time >= 53) {
                    wp_die($message, __('Error', 'give-recurring'), array(
                        'response' => 400,
                    ));

                    wp_die($message, __('Error', 'give-recurring'), array(
                        'response' => 400,
                    ));
                }
            }
        }

        return $form_id;
	}
}