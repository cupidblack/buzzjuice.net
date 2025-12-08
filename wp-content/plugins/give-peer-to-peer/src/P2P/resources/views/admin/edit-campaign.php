<?php

use GiveP2P\P2P\FieldsAPI\Consumers\FieldConsumer;
use GiveP2P\P2P\FieldsAPI\FormField;
use GiveP2P\P2P\ValueObjects\Status;

/**
 * @var GiveP2P\P2P\Models\Campaign $campaign
 * @var array                       $campaignData
 * @var FormField[]                 $campaignFields
 * @var FormField[]                 $teamFields
 * @var FormField[]                 $fundraiserFields
 * @var FormField[]                 $sponsorFields
 */

$statuses = [
    Status::ACTIVE => __('Active', 'give-peer-to-peer'),
    Status::INACTIVE => __('Inactive', 'give-peer-to-peer'),
    Status::DRAFT => __('Draft', 'give-peer-to-peer'),
];

?>
<form method="post" name="give-p2p-edit-campaign">

    <input id="give_form_active_tab" type="hidden" name="give_form_active_tab">

    <?php
    wp_nonce_field('edit-campaign-' . $campaign->getId(), 'give-p2p-nonce'); ?>
    <div class="give-p2p-settings-page">
        <div class="give-p2p-settings-page-content">

            <div id="give-p2p-campaign-title-container">
                <input
                    type="text"
                    id="give-p2p-campaign-title"
                    name="campaign_title"
                    value="<?php
                    echo $campaign->getTitle(); ?>"
                    placeholder="<?php
                    esc_attr_e('Campaign Name', 'give-peer-to-peer'); ?>"
                    spellcheck="true"
                    autocomplete="off"
                />
                <?php
                GiveP2P\Addon\Helpers\View::render('P2P.admin/campaign-permalink', compact('campaign')); ?>
            </div>

            <div id="give-metabox-form-data" class="postbox">
                <div class="postbox-header">
                    <h2><?php
                        _e('Campaign Options', 'give-peer-to-peer'); ?></h2>
                </div>
                <div class="inside">
                    <div class="give-metabox-panel-wrap">
                        <ul class="give-form-data-tabs give-metabox-tabs">
                            <li class="form_template_options_tab active">
                                <a href="#form_template_options" data-tab-id="form_template_options">
                                    <i class="fas fa-tools"></i>
                                    <span class="give-label"><?php
                                        _e('Setup', 'give-peer-to-peer'); ?></span>
                                </a>
                            </li>
                            <li class="form_team_options_tab">
                                <a href="#form_team_options" data-tab-id="form_team_options">
                                    <i class="fas fa-users-cog"></i>
                                    <span class="give-label"><?php
                                        _e('Teams', 'give-peer-to-peer'); ?></span>
                                </a>
                            </li>
                            <li class="form_fundraiser_options_tab">
                                <a href="#form_fundraiser_options" data-tab-id="form_fundraiser_options">
                                    <i class="fas fa-user-cog"></i>
                                    <span class="give-label"><?php
                                        _e('Fundraisers', 'give-peer-to-peer'); ?></span>
                                </a>
                            </li>
                            <li class="form_sponsor_options_tab">
                                <a href="#form_sponsor_options" data-tab-id="form_sponsor_options">
                                    <i class="fas fa-handshake"></i>
                                    <span class="give-label"><?php
                                        _e('Sponsors', 'give-peer-to-peer'); ?></span>
                                </a>
                            </li>
                        </ul>

                        <div id="form_template_options" class="panel give_options_panel active">
                            <?php
                            FieldConsumer::make($campaignFields)->render(); ?>
                        </div>

                        <div id="form_team_options" class="panel give_options_panel">
                            <?php
                            FieldConsumer::make($teamFields)->render(); ?>
                        </div>

                        <div id="form_sponsor_options" class="panel give_options_panel">
                            <?php
                            FieldConsumer::make($sponsorFields)->render(); ?>
                        </div>

                        <div id="form_fundraiser_options" class="panel give_options_panel">
                            <?php
                            FieldConsumer::make($fundraiserFields)->render(); ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="give-p2p-settings-page-sidebar">
            <div class="give-p2p-settings-page-sidebar--sticky-inner">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('Edit Campaign', 'give-peer-to-peer'); ?></h2>
                    </div>

                    <div class="inside">
                        <p>
                            <label for="p2p_campaign_status" class="post-attributes-label-wrapper">
                                <?php _e('Campaign status', 'give-peer-to-peer'); ?>
                            </label>
                        </p>
                        <select id="p2p_campaign_status" name="status">
                            <?php foreach ($statuses as $status => $statusText): ?>
                                <option value="<?php echo $status; ?>" <?php if ($campaign->hasStatus($status)) {
                                    echo 'selected';
                                } ?>>
                                    <?php echo $statusText; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="give-card-footer">
                        <input
                            type="submit"
                            name="give-p2p-edit-campaign"
                            class="button button-primary"
                            value="<?php esc_html_e('Update Campaign', 'give-peer-to-peer'); ?>"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
