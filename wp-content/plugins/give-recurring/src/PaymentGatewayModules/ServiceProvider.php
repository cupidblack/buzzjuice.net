<?php

namespace GiveRecurring\PaymentGatewayModules;

use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Helpers\Hooks;
use Give\PaymentGateways\Gateways\PayPalCommerce\PayPalCommerceGateway;
use Give\PaymentGateways\Gateways\PayPalStandard\PayPalStandard;
use Give\PaymentGateways\Gateways\Stripe\StripePaymentElementGateway\StripePaymentElementGateway;
use Give\PaymentGateways\Gateways\TestGateway\TestGateway;
use Give\PaymentGateways\Gateways\TestOffsiteGateway\TestOffsiteGateway;
use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;
use Give_Subscription;
use GiveAuthorizeNet\DataTransferObjects\AuthorizeGatewayData;
use GiveAuthorizeNet\Gateway\CreditCardGateway as CreditCardAuthorizeGateway;
use GiveAuthorizeNet\Gateway\eCheckGateway as eCheckAuthorizeGateway;
use GivePayFast\Gateway\PayFastGateway;
use GiveRecurring\PaymentGatewayModules\Actions\AddPaymentGatewayModulesToLegacyList;
use GiveRecurring\PaymentGatewayModules\Actions\CreateSubscriptionGatewayData;
use GiveRecurring\PaymentGatewayModules\Actions\ModifySubscriptionDonationData;
use GiveRecurring\PaymentGatewayModules\EventHandlers\SubscriptionActive;
use GiveRecurring\PaymentGatewayModules\EventHandlers\SubscriptionCancelled;
use GiveRecurring\PaymentGatewayModules\EventHandlers\SubscriptionCompleted;
use GiveRecurring\PaymentGatewayModules\EventHandlers\SubscriptionExpired;
use GiveRecurring\PaymentGatewayModules\EventHandlers\SubscriptionFailing;
use GiveRecurring\PaymentGatewayModules\EventHandlers\SubscriptionSuspended;
use GiveRecurring\PaymentGatewayModules\Modules\AuthorizeNet\AuthorizeGatewaySubscriptionModule;
use GiveRecurring\PaymentGatewayModules\Modules\AuthorizeNet\EventHandlers\HandleSubscriptionDonations;
use GiveRecurring\PaymentGatewayModules\Modules\AuthorizeNet\LegacyListeners\DispatchGiveAuthorizeRecurringPaymentDescription;
use GiveRecurring\PaymentGatewayModules\Modules\AuthorizeNet\Webhooks\SubscriptionWebhooks;
use GiveRecurring\PaymentGatewayModules\Modules\PayFastGatewaySubscriptionModule;
use GiveRecurring\PaymentGatewayModules\Modules\PayPalCommerce\PayPalCommerceSubscriptionModule;
use GiveRecurring\PaymentGatewayModules\Modules\PayPalStandard\Adapters\PayPalStandardGatewayLegacyAdapter;
use GiveRecurring\PaymentGatewayModules\Modules\PayPalStandard\Adapters\PayPalStandardGatewayLegacyWebhooksAdapter;
use GiveRecurring\PaymentGatewayModules\Modules\PayPalStandard\PayPalStandardGatewaySubscriptionModule;
use GiveRecurring\PaymentGatewayModules\Modules\Square\EventHandlers\HandleSquareSubscriptionDonations;
use GiveRecurring\PaymentGatewayModules\Modules\Square\SquareGatewaySubscriptionModule;
use GiveRecurring\PaymentGatewayModules\Modules\Square\Webhooks\SquareWebhookEvents;
use GiveRecurring\PaymentGatewayModules\Modules\Stripe\StripePaymentElementGatewaySubscriptionModule;
use GiveRecurring\PaymentGatewayModules\Modules\Test\TestGatewaySubscriptionModule;
use GiveRecurring\PaymentGatewayModules\Modules\Test\TestOffsiteGatewaySubscriptionModule;
use GiveSquare\GatewayConnect\Modals\SquareAccount;
use GiveSquare\PaymentGateway\Actions\GetSquareGatewayData;
use GiveSquare\PaymentGateway\CreditCardGateway as CreditCardSquareGateway;

class ServiceProvider implements ServiceProviderInterface
{

    /**
     * @since 1.14.0
     *
     * @inheritDoc
     */
    public function register()
    {
    }

    /**
     * @since 2.5.0 add Test Gateway Subscription module
     * @since 2.5.0 add Stripe Payment Element
     * @since 1.14.0
     *
     * @inheritDoc
     * @throws Exception
     */
    public function boot()
    {
        if (class_exists(PayFastGateway::class)) {
            $payFastGatewayId = PayFastGateway::id();
            add_filter("givewp_gateway_{$payFastGatewayId}_subscription_module", function () {
                return PayFastGatewaySubscriptionModule::class;
            });
        }

        $this->registerTestGatewayModule();
        $this->registerAuthorizeNetModule();
        $this->registerSquareModule();
        $this->registerPayPalStandardModule();
        $this->registerStripePaymentElementModule();
        $this->registerPayPalCommerceModule();


        Hooks::addFilter('give_recurring_available_gateways', AddPaymentGatewayModulesToLegacyList::class);
    }

    /**
     * @since 2.3.0
     * @throws Exception
     */
    private function registerSquareModule()
    {
        if (class_exists(CreditCardSquareGateway::class)) {
            $mode = give_is_test_mode() ? 'test' : 'live';
            $squareAccount = SquareAccount::make($mode);

            if ($squareAccount->isConnected() && $squareAccount->validateScopes()) {
                $squareGatewayId = CreditCardSquareGateway::id();
                add_filter("givewp_gateway_{$squareGatewayId}_subscription_module", function () {
                    return SquareGatewaySubscriptionModule::class;
                });

                Hooks::addFilter(
                    sprintf('givewp_create_subscription_gateway_data_%s', $squareGatewayId),
                    GetSquareGatewayData::class
                );

                Hooks::addFilter(
                    sprintf(
                        'givewp_donor_dashboard_edit_subscription_payment_method_gateway_data_%s',
                        $squareGatewayId
                    ),
                    GetSquareGatewayData::class
                );

                Hooks::addAction('givewp_square_webhook_event', SquareWebhookEvents::class, 'processEvent');

                Hooks::addAction(
                    'givewp_square_event_handle_subscription_donations',
                    HandleSquareSubscriptionDonations::class,
                    'run',
                    10,
                    2
                );

                Hooks::addAction(
                    'givewp_square_event_subscription_active',
                    SubscriptionActive::class,
                    'setStatus',
                    10,
                    2
                );

                Hooks::addAction(
                    'givewp_square_event_subscription_cancelled',
                    SubscriptionCancelled::class,
                    'setStatus',
                    10,
                    2
                );

                Hooks::addAction(
                    'givewp_square_event_subscription_suspended',
                    SubscriptionSuspended::class,
                    'setStatus',
                    10,
                    2
                );
            }
        }
    }

    /**
     * @since 2.3.1 Use givewp_authorize_recurring_payment_description filter
     * @since      2.2.0
     * @throws PaymentGatewayException
     */
    private function registerAuthorizeNetModule()
    {
        if (class_exists(CreditCardAuthorizeGateway::class) && class_exists(eCheckAuthorizeGateway::class)) {
            $creditCardId = CreditCardAuthorizeGateway::id();
            add_filter("givewp_gateway_{$creditCardId}_subscription_module", function () {
                return AuthorizeGatewaySubscriptionModule::class;
            });
            add_filter("givewp_create_subscription_gateway_data_{$creditCardId}", function ($gatewayData) {
                return $this->handleAuthorizeNetGatewayData($gatewayData);
            });
            add_filter(
                "givewp_donor_dashboard_edit_subscription_payment_method_gateway_data_{$creditCardId}",
                function ($gatewayData) {
                    return $this->handleAuthorizeNetGatewayData($gatewayData);
                }
            );

            $eCheckId = eCheckAuthorizeGateway::id();
            add_filter("givewp_gateway_{$eCheckId}_subscription_module", function () {
                return AuthorizeGatewaySubscriptionModule::class;
            });
            add_filter("givewp_create_subscription_gateway_data_{$eCheckId}", function ($gatewayData) {
                return $this->handleAuthorizeNetGatewayData($gatewayData);
            });
            add_filter(
                "givewp_donor_dashboard_edit_subscription_payment_method_gateway_data_{$eCheckId}",
                function ($gatewayData) {
                    return $this->handleAuthorizeNetGatewayData($gatewayData);
                }
            );

            Hooks::addFilter(
                'givewp_authorize_recurring_payment_description',
                DispatchGiveAuthorizeRecurringPaymentDescription::class,
                '__invoke',
                10,
                3
            );

            Hooks::addAction('give_authorize_webhook_payload', SubscriptionWebhooks::class, 'processWebhooks');

            Hooks::addAction(
                'givewp_authorize_event_handle_subscription_donations',
                HandleSubscriptionDonations::class,
                'run',
                10,
                2
            );

            Hooks::addAction(
                'givewp_authorize_event_subscription_active',
                SubscriptionActive::class,
                'setStatus',
                10,
                2
            );
            Hooks::addAction(
                'givewp_authorize_event_subscription_cancelled',
                SubscriptionCancelled::class,
                'setStatus',
                10,
                2
            );
            Hooks::addAction(
                'givewp_authorize_event_subscription_suspended',
                SubscriptionSuspended::class,
                'setStatus',
                10,
                2
            );
            Hooks::addAction(
                'givewp_authorize_event_subscription_completed',
                SubscriptionCompleted::class,
                'setStatus',
                10,
                2
            );
            Hooks::addAction(
                'givewp_authorize_event_subscription_expired',
                SubscriptionExpired::class,
                'setStatus',
                10,
                2
            );
            Hooks::addAction(
                'givewp_authorize_event_subscription_failing',
                SubscriptionFailing::class,
                'setStatus',
                10,
                2
            );
        }
    }

    /**
     * @since 2.2.0
     * @throws PaymentGatewayException
     */
    private function handleAuthorizeNetGatewayData($gatewayData)
    {
        if (is_array($gatewayData) && ! empty($gatewayData)) {
            $gatewayData['authorizeGatewayData'] = AuthorizeGatewayData::fromRequest($gatewayData);
        } else {
            $gatewayData['authorizeGatewayData'] = AuthorizeGatewayData::fromRequest(give_clean($_POST));
        }

        return $gatewayData;
    }

    /**
     * @since 2.5.0
     */
    private function registerPayPalStandardModule()
    {
        if (class_exists(PayPalStandard::class)) {
            add_filter(
                sprintf("givewp_gateway_%s_subscription_module", PayPalStandard::id()),
                static function () {
                    return PayPalStandardGatewaySubscriptionModule::class;
                }
            );

            add_filter(
                'give_subscription_can_cancel',
                static function (bool $ret, Give_Subscription $subscription) {
                    return give(PayPalStandardGatewayLegacyAdapter::class)->canCancelSubscription($ret, $subscription);
                }
                , 99, 2
            );

            // Add settings to PayPal standard
            add_filter('give_settings_gateways', static function ($settings) {
                return give(PayPalStandardGatewayLegacyAdapter::class)->settings($settings);
            });

            // In Give 2.9.0, PayPal Standard settings moved to "Payment Gateways -> PayPal -> PayPal standard"
            add_filter('give_get_settings_paypal_standard', static function ($settings) {
                return give(PayPalStandardGatewayLegacyAdapter::class)->settings($settings);
            });

            // v2 form validation
            add_action('give_checkout_error_checks', static function ($valid_data) {
                give(PayPalStandardGatewayLegacyAdapter::class)->checkoutErrors($valid_data);
            });

            // validate recurring times
            add_action('save_post_give_forms', static function ($form_id) {
                return give(PayPalStandardGatewayLegacyAdapter::class)->validatePaypalRecurringTimes($form_id);
            });

            // PayPal standard Webhooks
            give(PayPalStandardGatewayLegacyWebhooksAdapter::class)->processWebhooks();
        }
    }

    /**
     * @since 2.5.0
     */
    private function registerStripePaymentElementModule()
    {
        if (class_exists(StripePaymentElementGateway::class)) {
            add_filter(
                sprintf("givewp_gateway_%s_subscription_module", StripePaymentElementGateway::id()),
                static function () {
                    return StripePaymentElementGatewaySubscriptionModule::class;
                }
            );
        }
    }

    /**
     * @since 2.5.0
     */
    private function registerTestGatewayModule()
    {
        if (class_exists(TestGateway::class)) {
            add_filter(
                sprintf("givewp_gateway_%s_subscription_module", TestGateway::id()),
                static function () {
                    return TestGatewaySubscriptionModule::class;
                }
            );
        }

        if (class_exists(TestOffsiteGateway::class)) {
            add_filter(
                sprintf("givewp_gateway_%s_subscription_module", TestOffsiteGateway::id()),
                static function () {
                    return TestOffsiteGatewaySubscriptionModule::class;
                }
            );
        }
    }

    /**
     * @since 2.5.0
     */
    private function registerPayPalCommerceModule()
    {
        if (class_exists(PayPalCommerceGateway::class)) {
            Hooks::addFilter(
                'givewp_create_subscription_gateway_data_'  . PayPalCommerceGateway::id(),
                CreateSubscriptionGatewayData::class
            );
            Hooks::addFilter('give_recurring_modify_donation_data', ModifySubscriptionDonationData::class);

            // Register subscription module for PayPal Donations.
            add_filter(
                sprintf("givewp_gateway_%s_subscription_module", PayPalCommerceGateway::id()),
                static function () {
                    return PayPalCommerceSubscriptionModule::class;
                }
            );
        }
    }
}
