<?php

namespace GiveP2P\P2P\Controllers\Admin;

use Give\ValueObjects\Money;
use GiveP2P\Addon\Helpers\Notices;
use GiveP2P\P2P\Admin\Settings\Collection as FieldsCollection;
use GiveP2P\P2P\Commands\SyncDonationFormWithCampaign;
use GiveP2P\P2P\Helpers\CampaignHelper;
use GiveP2P\P2P\Models\Campaign;
use GiveP2P\P2P\Models\SettingsData;

/**
 * Class EditCampaignController
 * @package GiveP2P\P2P\Controllers\Admin
 *
 * @since 1.0.0
 */
class EditCampaignController {
	/**
	 * @var int
	 */
	private $campaignId;

	/**
	 * @var FieldsCollection
	 */
	private $fieldsCollection;

	/**
	 * @var CampaignHelper
	 */
	private $campaignHelper;

	/**
	 * @var SyncDonationFormWithCampaign
	 */
	private $syncDonationFormWithCampaign;

	/**
	 * AddCampaign constructor.
	 *
	 * @param  FieldsCollection              $fieldsCollection
	 * @param  CampaignHelper                $campaignHelper
	 * @param  SyncDonationFormWithCampaign  $syncDonationFormWithCampaign
	 */
	public function __construct(
		FieldsCollection $fieldsCollection,
		CampaignHelper $campaignHelper,
		SyncDonationFormWithCampaign $syncDonationFormWithCampaign
	) {
		$this->campaignId                   = isset( $_GET[ 'id' ] ) ? absint( $_GET[ 'id' ] ) : null;
		$this->fieldsCollection             = $fieldsCollection;
		$this->campaignHelper               = $campaignHelper;
		$this->syncDonationFormWithCampaign = $syncDonationFormWithCampaign;
	}

	/**
	 * Edit campaign
	 */
	public function handleData() {
		// Verify nonce
		if (
			! isset( $_POST[ 'give-p2p-edit-campaign' ], $_POST[ 'give-p2p-nonce' ] )
			|| ! wp_verify_nonce( $_POST[ 'give-p2p-nonce' ], 'edit-campaign-' . $this->campaignId )
		) {
			return;
		}

		// Check user permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Normalize goal amounts as integers in minor amounts.
		foreach( [ 'campaign_goal', 'team_goal', 'fundraiser_goal' ] as $field ) {
			$_POST[ $field ] = Money::of( absint( $_POST[ $field ] ), give_get_option( 'currency' ) )->getMinorAmount();
		}

		$fields = $this->fieldsCollection->getFieldsWithData(
			SettingsData::fromRequest( $_POST )
		);

		// Validate field collection
		if ( ! $this->campaignHelper->validateFields( $fields ) ) {
			return;
		}

		// Save campaign
		$campaign = Campaign::fromCollection( $fields );
		$campaign->set( 'id', $this->campaignId );

		if ( $campaign->save() ) {
			Notices::add( 'success', esc_html__( 'Campaign updated', 'give-peer-to-peer' ) );

			call_user_func( $this->syncDonationFormWithCampaign, $campaign );
		} else {
			Notices::add( 'error', esc_html__( 'Campaign not updated', 'give-peer-to-peer' ) );
		}
	}
}
