<?php

namespace GiveP2P\P2P\Admin\Settings;

use Give\Framework\FieldsAPI\Option;
use GiveP2P\P2P\Admin\Contracts\AdminPageSettings;
use GiveP2P\P2P\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Options;

/**
 * Teams Fields Settings
 *
 * @package GiveP2P\P2P\Admin\Settings
 *
 * @since   1.0.0
 */
class Team extends AdminPageSettings
{

    /**
     * @inheritDoc
     */
    public function getFields()
    {
        return [
            Field::radio('team_approvals')
                ->label(esc_html__('Team Approvals', 'give-peer-to-peer'))
                ->helpText(
                    __(
                        sprintf(
                            'If enabled, this requires that all teams be approved by an administrator before being displayed publicly. You can manage the approval email from the <a href="%s">global email settings.</a>',
                            admin_url('edit.php?post_type=give_forms&page=give-settings&tab=emails&section=p2p-admin-email')
                        )
                    )
                )
                ->defaultValue(Options::ENABLED)
                ->options(
                    Option::make(Options::ENABLED, esc_html__('Enable Team Approval', 'give-peer-to-peer')),
                    Option::make(Options::DISABLED, esc_html__('Disable Team Approval', 'give-peer-to-peer'))
                ),
            Field::radio('teams_registration')
                ->label(esc_html__('Teams Registration', 'give-peer-to-peer'))
                ->helpText(
                    esc_html__(
                        'If enabled, fundraisers will be able to register and create new teams from their "Start Fundraising" and "Register" pages.',
                        'give-peer-to-peer'
                    )
                )
                ->defaultValue(Options::ENABLED)
                ->options(
                    Option::make(Options::ENABLED, esc_html__('Enable', 'give-peer-to-peer')),
                    Option::make(Options::DISABLED, esc_html__('Disable', 'give-peer-to-peer'))
                ),
            Field::money('team_goal')
                ->label(esc_html__('Default Team Goal', 'give-peer-to-peer'))
                ->helpText(
                    esc_html__(
                        'This displays for team captains during the team creation process. Set a realistic but ambitious amount. Team owners can update the goal amount, but most will not.',
                        'give-peer-to-peer'
                    )
                )
                ->defaultValue(500000),

            Field::textarea('team_story_placeholder')
                ->label(esc_html__('Team Story Placeholder', 'give-peer-to-peer'))
                ->defaultValue(
                    esc_html__(
                        'We are fundraising to support a great cause that we believe in. Help us meet our goal by donating today to support our team.',
                        'give-peer-to-peer'
                    )
                )
                ->helpText(
                    esc_html__(
                        'Teams have their own stories too! This is where the team captain can write a few sentences about why they are fundraising to support the campaign. Set placeholder text for new teams to help them get started!',
                        'give-peer-to-peer'
                    )
                ),

            Field::radio('team_rankings')
                ->label(esc_html__('Team Rankings', 'give-peer-to-peer'))
                ->helpText(
                    esc_html__(
                        'If enabled, this displays rankings for all teams (by how much each team raises) on team and individual profiles as well as the campaign page.',
                        'give-peer-to-peer'
                    )
                )
                ->defaultValue(Options::ENABLED)
                ->options(
                    Option::make(Options::ENABLED, esc_html__('Enable Rankings', 'give-peer-to-peer')),
                    Option::make(Options::DISABLED, esc_html__('Disable Rankings', 'give-peer-to-peer'))
                ),
        ];
    }
}
