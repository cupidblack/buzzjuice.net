<?php

namespace GiveP2P\P2P\Controllers\Admin;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Admin\Settings\Collection as FieldsCollection;
use GiveP2P\P2P\Commands\CreateDonationFormForCampaign;
use GiveP2P\P2P\FieldsAPI\Options;
use GiveP2P\P2P\Helpers\CampaignHelper;
use GiveP2P\P2P\Models\Campaign;
use GiveP2P\P2P\Models\SettingsData;

/**
 * Class AddCampaignController
 * @package GiveP2P\P2P\Controllers\Admin
 *
 * @since 1.0.0
 */
class AddCampaignController {
	/**
	 * @var FieldsCollection
	 */
	private $fieldsCollection;

	/**
	 * @var CampaignHelper
	 */
	private $campaignHelper;

	/**
	 * @var CreateDonationFormForCampaign
	 */
	protected $createDonationFormForCampaign;

	/**
	 * @param  FieldsCollection               $fieldsCollection
	 * @param  CampaignHelper                 $campaignHelper
	 * @param  CreateDonationFormForCampaign  $createDonationFormForCampaign
	 */
	public function __construct(
		FieldsCollection $fieldsCollection,
		CampaignHelper $campaignHelper,
		CreateDonationFormForCampaign $createDonationFormForCampaign
	) {
		$this->fieldsCollection              = $fieldsCollection;
		$this->campaignHelper                = $campaignHelper;
		$this->createDonationFormForCampaign = $createDonationFormForCampaign;
	}

	/**
	 * Add Campaign
	 */
	public function handleData() {
		// Verify nonce
		if (
			! isset( $_POST[ 'give-p2p-add-campaign' ], $_POST[ 'give-p2p-nonce' ] )
			|| ! wp_verify_nonce( $_POST[ 'give-p2p-nonce' ], 'add-campaign' )
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

		$settingsData = SettingsData::fromRequest( $_POST );

		$fields = $this->fieldsCollection->getFieldsWithData( $settingsData );

		// Validate field collection
		if ( ! $this->campaignHelper->validateFields( $fields ) ) {
			return;
		}

		// Save campaign
		$campaign = Campaign::fromCollection( $fields );

		if ( $settingsData->offsetGet( 'form_new' ) === Options::ENABLED ) {
			call_user_func_array( $this->createDonationFormForCampaign, [ &$campaign ] );
		}

		// Redirect if saved successfully
		if ( $campaign->save() ) {
			wp_safe_redirect(
				admin_url('edit.php?post_type=give_forms&page=p2p-edit-campaign&id=' . $campaign->getId() . '&p2p-action=campaign_added')
			);
		}
	}
}
