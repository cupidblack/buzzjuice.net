<?php

namespace GiveP2P\P2P\Commands;

use GiveP2P\Addon\Helpers\View;
use GiveP2P\P2P\Repositories\FundraiserRepository;

/**
 * Fundraisers require an underlying WordPress User account. Therefor,
 * User accounts associated to a Fundraisers should not be deleted.
 *
 * @since 1.5.0
 */
class PreventFundraiserUserDeletion
{
    /**
     * Handles the `delete_user` hook.
     *
     * @since 1.5.0
     *
     * @param $userID
     *
     * @return void
     */
    public function __invoke($userID)
    {
        if (give(FundraiserRepository::class)->fundraiserExistsForUser($userID)) {
            wp_die(View::render('P2P.admin/prevent-delete-user', [
                'campaignsUrl' => get_admin_url(null,'edit.php?post_type=give_forms&page=p2p-campaigns'),
            ]));
        }
    }
}
