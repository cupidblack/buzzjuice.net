<?php

namespace GiveP2P\Donations\Actions;

/**
 * @since 1.3.0
 */
class DeleteDonationSource
{
    const DonationPostType = 'give_payment';

    /**
     * @since 1.3.0
     *
     * @param int $postID
     * @param \WP_Post $post
     */
    public function __invoke( $postID, \WP_Post $post )
    {
        if( self::DonationPostType === $post->post_type )
        {
            global $wpdb;
            $wpdb->delete( $wpdb->give_p2p_donation_source, [ 'donation_id' => $postID ] );
        }
    }
}
