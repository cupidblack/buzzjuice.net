<div class="wrap give-p2p-campaigns-list-table give-settings-page">
	<div class="give-settings-header">
        <h1 class="wp-heading-inline">
            <?php esc_html_e( 'P2P Campaigns', 'give-peer-to-peer' ); ?>
            <a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=' . GiveP2P\P2P\Admin\AddCampaign::PAGE_SLUG ); ?>" class="page-title-action">
                <?php esc_html_e( 'New Campaign', 'give-peer-to-peer' ); ?>
            </a>
        </h1>
    </div>
    <div class="wp-header-end"></div>
	<div id="give-p2p-campaigns-app"></div>
</div>
