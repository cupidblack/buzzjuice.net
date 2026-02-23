<?php

namespace GiveP2P\P2P\Commands;

use GiveP2P\P2P\Factories\DonationFormFactory;
use GiveP2P\P2P\Models\Campaign;

/**
 * @since 1.0.0
 */
class CreateDonationFormForCampaign {

	/**
	 * @var FormFactory
	 */
	protected $formFactory;

	public function __construct( DonationFormFactory $formFactory ) {
		$this->formFactory = $formFactory;
	}

	/**
	 * @since 1.0.0
	 */
	public function __invoke( Campaign &$campaign ) {

		$formID = $this->formFactory->make(
			$campaign->get( 'campaign_title' ),
			$campaign->get('primary_color')
		);

		$campaign->set('form_id', $formID );
	}
}
