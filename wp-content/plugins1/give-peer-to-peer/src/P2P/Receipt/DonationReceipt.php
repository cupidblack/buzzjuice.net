<?php

namespace GiveP2P\P2P\Receipt;

use Give\Receipt\DonationReceipt as GiveDonationReceipt;
use GiveP2P\Addon\Helpers\View;
use GiveP2P\P2P\Exceptions\DonationSourceNotFound;
use GiveP2P\P2P\Repositories\DonationSourceRepository;
use WP_Post;

/**
 * Class DonationReceipt
 * @package GiveP2P\P2P\Receipt
 *
 * @since 1.0.0
 */
class DonationReceipt {

	/**
	 * @var DonationSourceRepository
	 */
	private $donationSourceRepository;

	/**
	 * @param  DonationSourceRepository  $donationSourceRepository
	 */
	public function __construct( DonationSourceRepository $donationSourceRepository ) {
		$this->donationSourceRepository = $donationSourceRepository;
	}

	/**
	 * @param  WP_Post  $donation
	 */
	public function showInfoLegacyTemplate( $donation ) {
		if ( ! $data = $this->getData( $donation->ID ) ) {
			return;
		}

		View::render( 'P2P.receipt/legacy', [
			'data' => $data
		] );
	}

	/**
	 * @param  GiveDonationReceipt  $receipt
	 */
	public function showInfoSequoiaTemplate( $receipt ) {
		if ( ! $data = $this->getData( $receipt->donationId ) ) {
			return;
		}

		$receiptDonationSection = $receipt[ GiveDonationReceipt::DONATIONSECTIONID ];

		$receiptDonationSection->addLineItem(
			$data,
			'before',
			'totalAmount'
		);
	}

	/**
	 * @param  int  $donationId
	 *
	 * @return array|null
	 */
	private function getData( $donationId ) {
		try {
			list( $source, , $sourceDetails, $campaignDetails ) = $this->donationSourceRepository->getSource( $donationId );

			switch ( $source->source_type ) {
				case 'team':
					return [
						'id'    => 'p2p-team',
						'label' => __( 'Donated to team', 'give-peer-to-peer' ),
						'value' => $sourceDetails->get( 'name' ),
					];
				case 'fundraiser':
					return [
						'id'    => 'p2p-fundraiser',
						'label' => __( 'Donated to fundraiser', 'give-peer-to-peer' ),
						'value' => get_userdata( $sourceDetails->get( 'user_id' ) )->display_name,
					];

				default:
					return [
						'id'    => 'p2p-campaign',
						'label' => __( 'Donated to campaign', 'give-peer-to-peer' ),
						'value' => $campaignDetails->get( 'campaign_title' ),
					];
			}
		} catch ( DonationSourceNotFound $e ) {
			return null;
		}
	}
}
