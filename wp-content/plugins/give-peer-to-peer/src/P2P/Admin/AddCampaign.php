<?php

namespace GiveP2P\P2P\Admin;

use GiveP2P\Addon\Helpers\View;
use GiveP2P\P2P\Admin\Contracts\AdminPage;
use GiveP2P\P2P\Admin\Settings\Collection as FieldsCollection;
use GiveP2P\P2P\FieldsAPI\Options;
use GiveP2P\P2P\Models\SettingsData;

/**
 * AddCampaign Page
 *
 * @package GiveP2P\P2P\Admin
 *
 * @since 1.0.0
 */
class AddCampaign extends AdminPage {
	/**
	 * Page slug
	 */
	const PAGE_SLUG = 'p2p-add-campaign';

	/**
	 * @inheritDoc
	 */
	public function registerPage() {
		add_submenu_page(
			null,
			esc_html__( 'Add New Peer-to-Peer Campaign', 'give-peer-to-peer' ),
			null,
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'renderPage' ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function renderPage() {
		$collection                 = give( FieldsCollection::class );
		$settingsData               = SettingsData::fromRequest( $_POST );
		$settingsData[ 'form_new' ] = Options::ENABLED;

		View::render(
			'P2P.admin/add-campaign',
			[
                'campaignFields' => $collection->campaign()->getFieldsWithData($settingsData),
                'teamFields' => $collection->team()->getFieldsWithData($settingsData),
                'fundraiserFields' => $collection->fundraiser()->getFieldsWithData($settingsData),
                'sponsorFields' => $collection->sponsor()->getFieldsWithData($settingsData),
            ]
		);
	}
}
