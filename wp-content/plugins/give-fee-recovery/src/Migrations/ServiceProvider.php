<?php

namespace GiveFeeRecovery\Migrations;

use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register()
    {
    }

    /**
     * @since 1.9.8 add migration AddFeeRecoveryMetaToRenewals
     *
     * @inheritdoc
     */
    public function boot()
    {
        Hooks::addAction('give_register_updates', AddFeeRecoveryMetaToRenewals::class, 'register');
    }
}
