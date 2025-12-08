<?php

namespace GiveRecurring\FormExtension;

use Give\Helpers\Hooks;
use GiveRecurring\FormExtension\Hooks\NewFormDefaultSettings;

/**
 * Class ServiceProvider
 * @package GiveRecurring
 * @since 2.5.0
 */
class ServiceProvider implements \Give\ServiceProviders\ServiceProvider
{
    /**
     * @inheritDoc
     * @since 2.5.0
     */
    public function register() { }

    /**
     * @inheritDoc
     * @since 2.5.0
     */
    public function boot()
    {
        // Form Extension hooks
        Hooks::addAction('givewp_form_builder_new_form', NewFormDefaultSettings::class);
    }
}
