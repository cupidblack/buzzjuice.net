<?php

namespace GiveP2P\Reallocation;

use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider as GiveServiceProvider;


/**
 * @since 1.3.0
 */
class ServiceProvider implements GiveServiceProvider {

    /**
     * @inheritDoc
     */
    public function register() {
        //
    }

    /**
     * @inheritDoc
     */
    public function boot() {
        add_action( 'admin_enqueue_scripts', function() {
            wp_localize_script('give-p2p-script-backend', 'p2pReallocationStrategies', StrategyEnum::all());
        });
        Hooks::addAction( 'rest_api_init', API\DeleteTeamStrategy::class, 'registerRoute' );
        Hooks::addAction( 'rest_api_init', API\DeleteFundraiserStrategy::class, 'registerRoute' );
        Hooks::addAction( 'rest_api_init', API\GetDeleteFundraiserStrategy::class, 'registerRoute' );
    }
}
