<?php

namespace GiveP2P\P2P\Repositories;

use Give\Framework\Database\DB;
use GiveP2P\P2P\Exceptions\DonationSourceNotFound;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;

/**
 * @since 1.0.0
 */
class DonationSourceRepository {

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @since 1.0.0
	 * @param int $donationID
	 * @return array
	 * @throws DonationSourceNotFound
	 */
	public function getSource( $donationID ) {

        $source = $this->getSourceRow( $donationID );

		switch( $source->source_type ) {
			case 'fundraiser': $sourceDetails = Fundraiser::getFundraiser( $source->source_id ); break;
			case 'team': $sourceDetails = Team::getTeam( $source->source_id ); break;
			default: $sourceDetails = Campaign::getCampaign( $source->source_id );
		}

		$campaignDetails = ( 'campaign' === $source->source_type )
			? $sourceDetails
			: Campaign::getCampaign( $sourceDetails->get('campaign_id') );

		switch( $source->source_type ) {
			case 'fundraiser': $sourceURL = home_url("campaign/{$campaignDetails->getUrl()}/fundraiser/$source->source_id"); break;
			case 'team': $sourceURL = home_url("campaign/{$campaignDetails->getUrl()}/team/$source->source_id"); break;
			default: $sourceURL = home_url("campaign/{$campaignDetails->getUrl()}");
		}

		return [
			$source,
			$sourceURL,
			$sourceDetails,
			$campaignDetails,
		];
	}

    public function getSourceRow( $donationID ) {
        $table = $this->wpdb->give_p2p_donation_source;
        $source = DB::get_row($this->wpdb->prepare("
			SELECT *
			FROM $table
			WHERE `donation_id` = %d
		", $donationID ) );

        if( ! $source ) {
            throw new DonationSourceNotFound( $donationID );
        }

        return $source;
    }

	/**
	 * Get source type
	 *
	 * @param int $donationID
	 *
	 * @return CampaignRepository|FundraiserRepository|TeamRepository
	 * @throws DonationSourceNotFound
	 *
	 * @since 1.0.0
	 */
	public function getSourceType( $donationID ) {
		$source = DB::get_row($this->wpdb->prepare("
			SELECT *
			FROM {$this->wpdb->give_p2p_donation_source}
			WHERE `donation_id` = %d
		", $donationID ) );

		if( ! $source ) {
			throw new DonationSourceNotFound( $donationID );
		}

		switch( $source->source_type ) {
			case 'fundraiser':
				return Fundraiser::getFundraiser( $source->source_id );
			case 'team':
				return Team::getTeam( $source->source_id );
			case 'campaign':
				return Campaign::getCampaign( $source->source_id );
			default:
				throw new DonationSourceNotFound( $donationID );
		}
	}
}
