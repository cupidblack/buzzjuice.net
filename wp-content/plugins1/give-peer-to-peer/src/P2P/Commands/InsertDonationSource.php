<?php

namespace GiveP2P\P2P\Commands;

/**
 * @since 1.0.0
 */
class InsertDonationSource {

	/**
	 * @since 1.0.0
	 * @param int $donationID
	 */
	public function __invoke( $donationID ) {

		$sourceID = give_get_meta( $donationID, 'p2pSourceID', true );
		$sourceType = give_get_meta( $donationID, 'p2pSourceType', true );

		if( $sourceID && $sourceType ) {
			global $wpdb;
			$wpdb->insert($wpdb->give_p2p_donation_source, [
				'donation_id' => $donationID,
				'source_id' => $sourceID,
				'source_type' => $sourceType,
				'donor_id' => give_get_meta( $donationID, '_give_payment_donor_id', true ),
				'anonymous' => give_get_meta( $donationID, '_give_anonymous_donation', true )
			]);
		}
	}
}
