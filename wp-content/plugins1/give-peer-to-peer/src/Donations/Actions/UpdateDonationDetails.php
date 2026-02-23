<?php

namespace GiveP2P\Donations\Actions;

use GiveP2P\P2P\Commands\InsertDonationSource;
use GiveP2P\P2P\Exceptions\DonationSourceNotFound;
use GiveP2P\P2P\Repositories\DonationSourceRepository;

/**
 * Class UpdateDonationDetails
 *
 * @since 1.4.0
 */
class UpdateDonationDetails
{
    /**
     * @since 1.4.0
     *
     * @return void
     * @since 1.6.0 Only update donationSource and donationMeta when sourceType and sourceID are available.
     */
    public function __invoke(array $data)
    {

        $donationId = absint($data['give_payment_id']);

        if (isset($data['p2pFundraiser']) && $data['p2pFundraiser']) {
            $sourceType = 'fundraiser';
            $sourceId = absint($data['p2pFundraiser']);
        } elseif (isset($data['p2pTeam']) && $data['p2pTeam']) {
            $sourceType = 'team';
            $sourceId = absint($data['p2pTeam']);
        } elseif (isset($data['p2pCampaign']) && $data['p2pCampaign']) {
            $sourceType = 'campaign';
            $sourceId = absint($data['p2pCampaign']);
        }

        if(isset($sourceType, $sourceId)){
            $this->updateDonationMeta($donationId, $sourceType, $sourceId);

            try {
                $source = give(DonationSourceRepository::class)->getSourceRow($donationId);
                $this->updateDonationSource($donationId, $sourceType, $sourceId);
            } catch (DonationSourceNotFound $ex) {
                $this->createDonationSource($donationId, $sourceType, $sourceId);
            }
        }
    }

    /**
     * @since 1.6.2
     *
     * @return void
     */
    protected function updateDonationMeta(int $donationId, string $sourceType, string $sourceID)
    {
        give_update_meta($donationId, 'p2pSourceType', $sourceType);
        give_update_meta($donationId, 'p2pSourceID', absint($sourceID));
    }

    /**
     *
     * @since 1.4.0
     *
     * @return void
     */
    protected function updateDonationSource(int $donationId, string $sourceType, string $sourceID)
    {
        global $wpdb;

        $wpdb->update($wpdb->give_p2p_donation_source, [
            'source_id' => absint($sourceID),
            'source_type' => $sourceType
        ], [
            'donation_id' => $donationId
        ]);
    }

    /**
     * @since 1.6.2
     *
     * @return void
     */
    protected function createDonationSource(int $donationId, string $sourceType, string $sourceID)
    {
        $insertDonationSource = new InsertDonationSource();
        $insertDonationSource($donationId);
    }
}
