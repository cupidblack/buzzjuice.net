<?php

namespace GiveP2P\P2P\Admin\Settings;

use Give\Framework\FieldsAPI\Option;
use GiveP2P\P2P\Admin\Contracts\AdminPageSettings;
use GiveP2P\P2P\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\Options;

/**
 * Fundraiser Fields Settings
 *
 * @package GiveP2P\P2P\Admin\Settings
 *
 * @since   1.0.0
 */
class Fundraiser extends AdminPageSettings
{
	/**
	 * @inheritDoc
	 */
	public function getFields() {
		return [
            Field::radio('fundraiser_approvals')
                ->label(__('Fundraiser Approvals', 'give-peer-to-peer'))
                ->helpText(
                    __(
                        sprintf(
                            'If enabled, this requires that all fundraisers be approved by an administrator before being displayed publicly. You can manage the approval email from the <a href="%s">global email settings.</a>',
                            admin_url('edit.php?post_type=give_forms&page=give-settings&tab=emails&section=p2p-admin-email')
                        )
                    )
                )
                ->defaultValue(Options::ENABLED)
                ->options(
                    Option::make(Options::ENABLED, __('Enable Fundraiser Approval', 'give-peer-to-peer')),
                    Option::make(Options::DISABLED, __('Disable Fundraiser Approval', 'give-peer-to-peer'))
                ),
            Field::money('fundraiser_goal')
                ->label(__('Default Fundraiser Goal', 'give-peer-to-peer'))
                ->helpText(__('This displays for fundraisers when they create their profile. Set a realistic but ambitious amount. Fundraisers can update the goal amount, but most will not.',
                    'give-peer-to-peer'))
                ->defaultValue(50000),
            Field::textarea('fundraiser_story_placeholder')
                ->label(__('Fundraiser Story Placeholder', 'give-peer-to-peer'))
                ->defaultValue(__('I\'m fundraising to support this great cause, because it means so much to me. Help me out by donating through my fundraiser page.',
                    'give-peer-to-peer'
                ))
			     ->helpText( __( 'This is the default text displayed on the fundraiser\'s page to compel their friends to join them in giving to your cause. People give to people, and stories motivate them. Set up your fundraisers for success by prompting them to tell their story here.',
				     'give-peer-to-peer' ) ),

		];
	}
}
