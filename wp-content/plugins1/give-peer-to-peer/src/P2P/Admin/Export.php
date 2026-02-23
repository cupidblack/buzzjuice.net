<?php

namespace GiveP2P\P2P\Admin;

use Give_Payment;
use GiveP2P\Addon\Helpers\View;
use GiveP2P\P2P\Models\Campaign;
use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Exceptions\DonationSourceNotFound;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\DonationSourceRepository;
use GiveP2P\P2P\Repositories\TeamRepository;

class Export {
	/**
	 * @var CampaignRepository
	 */
	private $campaignRepository;

	/**
	 * @var DonationSourceRepository
	 */
	private $donationSourceRepository;


	/**
	 * @param  CampaignRepository  $campaignRepository
	 * @param  DonationSourceRepository  $donationSourceRepository
	 */
	public function __construct(
		CampaignRepository $campaignRepository,
		TeamRepository $teamRepository,
		DonationSourceRepository $donationSourceRepository
	) {
		$this->campaignRepository       = $campaignRepository;
		$this->teamRepository           = $teamRepository;
		$this->donationSourceRepository = $donationSourceRepository;
	}

	/**
	 * Render "P2P Options" on Export Donations page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function renderOptions() {
		View::render( 'P2P.admin/export-options' );
	}

	/**
	 * Filter to get columns name when exporting donation
	 *
	 * @param array $columnNames columns name for CSV
	 * @param array $adminSelectedColumns columns select by admin to export
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function filterColumns( $columnNames, $adminSelectedColumns ) {

		$columns = [
			'p2p_campaign_id'        => __( 'Campaign ID', 'give-peer-to-peer' ),
			'p2p_campaign'           => __( 'Campaign', 'give-peer-to-peer' ),
			'p2p_team_id'            => __( 'Team ID', 'give-peer-to-peer' ),
			'p2p_team'               => __( 'Team', 'give-peer-to-peer' ),
			'p2p_fundraiser_id'      => __( 'Fundraiser ID', 'give-peer-to-peer' ),
			'p2p_fundraiser'         => __( 'Fundraiser', 'give-peer-to-peer' ),
			'p2p_fundraiser_user_id' => __( 'Fundraiser User ID', 'give-peer-to-peer' ),
		];

		foreach( $columns as $id => $name ) {
			if( isset( $adminSelectedColumns[ $id ] ) ) {
				$columnNames[ $id ] = $name;
			}
		}

		return $columnNames;
	}

	/**
	 * Filter to modify Donation CSV data when exporting donation
	 *
	 * @since 1.0.0
	 *
	 * @param array $data
	 * @param Give_Payment $payment
	 * @param array $columns
	 *
	 * @return array
	 */
	public function filterData( $data, $payment, $columns ) {

		try {
			/**
			 * @var $source Campaign|Team|Fundraiser
			 */
			$source = $this->donationSourceRepository->getSourceType( $payment->ID );
		} catch ( DonationSourceNotFound $e ) {
			return $data;
		}

		switch ( get_class( $source ) ) {
			case Campaign::class:
				$sourceData = [
					'p2p_campaign_id'   => $source->get( 'id' ),
					'p2p_campaign'      => $source->get( 'campaign_title' ),
					'p2p_team_id'       => '',
					'p2p_team'          => '',
					'p2p_fundraiser_id' => '',
					'p2p_fundraiser_user_id' => '',
					'p2p_fundraiser'    => '',
				];
				break;

			case Team::class:
				$campaign = $this->campaignRepository->getCampaign( $source->get( 'campaign_id' ) );

				$sourceData = [
					'p2p_campaign_id'   => $campaign->get( 'id' ),
					'p2p_campaign'      => $campaign->get( 'campaign_title' ),
					'p2p_team_id'       => $source->get( 'id' ),
					'p2p_team'          => $source->get( 'name' ),
					'p2p_fundraiser_id' => '',
					'p2p_fundraiser_user_id' => '',
					'p2p_fundraiser'    => '',
				];
				break;

			case Fundraiser::class:
				$campaign = $this->campaignRepository->getCampaign( $source->get( 'campaign_id' ) );
				$user     = get_userdata( $source->get( 'user_id' ) );

				if( $source->getTeamId() ) {
					$team = $this->teamRepository->getTeam( $source->getTeamId() );
				}

				$sourceData = [
					'p2p_campaign_id'        => $campaign->get( 'id' ),
					'p2p_campaign'           => $campaign->get( 'campaign_title' ),
					'p2p_team_id'            => isset( $team ) ? $team->getId() : '',
					'p2p_team'               => isset( $team ) ? $team->getName() : '',
					'p2p_fundraiser_id'      => $source->get( 'id' ),
					'p2p_fundraiser_user_id' => $source->get( 'user_id' ),
					'p2p_fundraiser'         => $user->display_name
				];
				break;
			default:
				$sourceData = [];
		}

		foreach( $sourceData as $key => $value ) {
			if( ! isset( $columns[ $key ] ) ) {
				unset( $sourceData[ $key ] );
			}
		}

		return array_merge( $data, $sourceData );
	}

	/**
	 * Remove P2P custom fields as exportable columns, in favor of explicit P2P export options.
	 * @param array $responses
	 * @return array
	 */
	public function filterCustomFields( $responses ) {
		if( isset( $responses[ 'standard_fields' ] ) ) {
			$responses[ 'standard_fields' ] = array_diff( $responses[ 'standard_fields' ], [ 'p2pSourceID', 'p2pSourceType' ] );
		}
		return $responses;
	}
}
