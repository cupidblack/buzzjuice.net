<?php

namespace GiveP2P\P2P\Commands;

use Give\Helpers\Form\Template as FormTemplateUtils;
use GiveP2P\P2P\Models\Campaign;

/**
 * @since 1.0.0
 */
class SyncDonationFormWithCampaign {

    /**
     * @since 1.0.0
     */
    public function __invoke( Campaign $campaign ) {

        $options = FormTemplateUtils::getOptions( $campaign->get( 'form_id' ) );

        if ( $options && isset( $options[ 'introduction' ] ) && isset( $options[ 'introduction' ][ 'primary_color' ] ) ) {
            $options[ 'introduction' ][ 'primary_color' ] = $campaign->get( 'primary_color' );
            FormTemplateUtils::saveOptions( $campaign->get( 'form_id' ), $options );
        }
    }
}
