<?php

namespace GiveP2P\Donations;

use Give\Helpers\Hooks;

/**
 * @since 1.3.0
 */
class ServiceProvider implements \Give\ServiceProviders\ServiceProvider {

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

        Hooks::addAction( 'deleted_post', Actions\DeleteDonationSource::class, '__invoke', 10, 2 );
        Hooks::addAction( 'give_view_donation_details_sidebar_after', Actions\AddDonationDetailsMetabox::class );
        Hooks::addAction( 'give_update_payment_details', Actions\UpdateDonationDetails::class, '__invoke', 1, 9 ); // Before core hook.
        Hooks::addFilter('give_update_meta', Actions\SyncDonationMetaValues::class, '__invoke', 10, 4);

        add_action('give_view_donation_details_after', function() {
            wp_enqueue_script(
                'give-p2p-source-selection',
                GIVE_P2P_URL . 'src/Donations/resources/js/source-selection.js',
                [],
                GIVE_P2P_VERSION,
                false
            );
        });
    }
}
