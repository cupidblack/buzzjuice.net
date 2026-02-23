<?php

namespace GiveRecurring\FormExtension\Hooks;

use Give\DonationForms\Models\DonationForm;
use Give\FormBuilder\ViewModels\FormBuilderViewModel;
use Give\Framework\Blocks\BlockModel;

/**
 * Class NewFormDefaultSettings
 *
 * @since 2.5.0
 */
class NewFormDefaultSettings
{
    /**
     * Enable recurring donation by default in new forms.
     */
    public function __invoke(DonationForm $form)
    {
        $formBuilderViewModel = new FormBuilderViewModel();
        $gateways = $formBuilderViewModel->getGateways();
        $isRecurringSupported = (bool)array_filter($gateways, function ($gateway) {
            return $gateway['supportsSubscriptions'];
        });

        if (!$isRecurringSupported) {
            return;
        }

        /**
         * @var BlockModel $block
         */
        $block = $form->blocks->findByName('givewp/donation-amount');

        if (!$block) {
            return;
        }

        $block
            ->setAttribute('recurringEnabled', true)
            ->setAttribute('recurringDonationChoice', 'donor');
    }
}
