<?php

namespace GiveP2P\Donations\Actions;

/**
 * @since 1.6.4
 */
class SyncDonationMetaValues
{
    /**
     * @since 1.6.4
     */
    public function __invoke($status, $id, $metaKey, $metaValue)
    {
        if ( ! $status) {
            return $status;
        }

        if ('_give_anonymous_donation' === $metaKey) {
            $this->UpdateDonationSourceAnonymousColumn($id, (bool)$metaValue);
        }

        return $status;
    }

    /**
     *
     * @since 1.6.4
     */
    private function UpdateDonationSourceAnonymousColumn(int $donationId, bool $anonymous)
    {
        global $wpdb;

        $wpdb->update($wpdb->give_p2p_donation_source, [
            'anonymous' => (int)$anonymous,
        ], [
            'donation_id' => $donationId,
        ]);
    }
}
