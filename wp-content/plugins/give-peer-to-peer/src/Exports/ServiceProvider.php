<?php

namespace GiveP2P\Exports;

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

        /** @note GiveWP Batch Exporting expects an un-namespaced class name. */
        add_action( 'give_batch_export_class_include', function() {
           class_alias( FundraiserExport::class, 'Give_P2P_Fundraisers_Export' );
        });

        Hooks::addAction( 'give_tools_tab_export_table_bottom', Views\FundraiserExportView::class, 'render' );
    }
}
