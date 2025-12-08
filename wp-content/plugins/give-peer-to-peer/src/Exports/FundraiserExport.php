<?php

namespace GiveP2P\Exports;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;

/**
 * @since 1.3.0
 */
class FundraiserExport extends \Give_Batch_Export {

    /**
     * @since 1.3.0
     * @var string
     */
    public $export_type = 'p2p_fundraisers';

    /**
     * @since 1.3.0
     * @var int
     */
    private $items_per_page = 20;

    /**
     * @since 1.3.0
     * @var array
     */
    protected $posted_data;

    /**
     * Set the properties specific to the export.
     *
     * @since 1.3.0
     * @param array $posted_data The Form Data passed into the batch processing.
     */
    public function set_properties( $posted_data ) {
        $this->posted_data = $posted_data;
    }

    /**
     * @since 1.3.0
     * @return array [ slug => label, ... ]
     */
    public function csv_cols() {
        return [
            'campaign_id' => __( 'Campaign', 'give-peer-to-peer' ),
            'campaign_name' => __( 'Campaign Name', 'give-peer-to-peer' ),
            'fundraiser_id' => __( 'Fundraiser', 'give-peer-to-peer' ),
            'fundraiser_name' => __( 'Fundraiser Name', 'give-peer-to-peer' ),
            'fundraiser_email' => __( 'Fundraiser Email Address', 'give-peer-to-peer' ),
            'fundraiser_goal_amount' => __( 'Fundraiser Goal Amount Formatted', 'give-peer-to-peer' ),
            'fundraiser_raised_amount' => __( 'Fundraiser Raised Amount Formatted', 'give-peer-to-peer' ),
        ];
    }

    /**
     * @since 1.3.0
     * @return array $data The data for the CSV file
     */
    public function get_data() {

        $offset  = ( $this->step - 1 ) * $this->items_per_page;

        $campaign = Campaign::getCampaign( $this->posted_data['p2p_fundraisers_export_campaign'] );

        if( ! $campaign ) {
            return [];
        }

        $fundraisers = Fundraiser::getCampaignFundraisersSearch( $campaign->getId(), '', $this->items_per_page, $offset );

        $export_data = array_map(function( $fundraiserData ) use ( $campaign ) {
            $fundraiser = Fundraiser::getFundraiser( $fundraiserData[ 'id' ] );
            $user = get_userdata( $fundraiser->get( 'user_id' ) );
            return [
                'campaign_id' => $campaign->getId(),
                'campaign_name' => $campaign->getTitle(),
                'fundraiser_id' => $fundraiser->getId(),
                'fundraiser_name' => $user->display_name,
                'fundraiser_email' => $user->user_email,
                'fundraiser_goal_amount' => Money::ofMinor( $fundraiser->getGoal(), give_get_option('currency') )->getAmount(),
                'fundraiser_raised_amount' => Money::ofMinor( Fundraiser::getRaisedAmount( $fundraiser->getId() ), give_get_option('currency') )->getAmount(),
            ];
        }, $fundraisers );

        $this->count = Campaign::getFundraisersCount($campaign->getId());

        /**
         * @since 1.3.0
         * @param $export_data
         */
        $data = apply_filters( "give_export_get_data_{$this->export_type}", $export_data );

        return $data;
    }

    /**
     * @since 1.3.0
     * @return int
     */
    public function get_percentage_complete()
    {
        $percentage = $this->count
            ? ( ( $this->items_per_page * $this->step ) / $this->count ) * 100
            : 100;
        return min( $percentage, 100 );
    }
}
