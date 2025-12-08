<?php

namespace GiveP2P\P2P\Commands;

use GiveP2P\P2P\Mailer;
use GiveP2P\P2P\Models\Campaign;
use GiveP2P\P2P\Models\Fundraiser;

class SendFundraiserApprovalEmail {

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @param  Mailer  $mailer
     */
    public function __construct( Mailer $mailer ) {
        $this->mailer = $mailer;
    }

    /**
     * @param  Fundraiser  $fundraiser
     * @param  Campaign    $campaign
     */
    public function __invoke( Fundraiser $fundraiser, Campaign $campaign ) {
        $fromName    = give_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $fromAddress = give_get_option( 'from_email', get_option( 'admin_email' ) );

        $headers = "From: $fromName <$fromAddress>\r\n";
        $headers .= "Reply-To: $fromAddress\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        $subject = $campaign->get( 'fundraiser_approvals_email_subject' );
        $message = $campaign->get( 'fundraiser_approvals_email_body' );

        $user = get_userdata( $fundraiser->get( 'user_id' ) );

        // Merge "Campaign Name", "Fundraiser Name", "Fundraiser First Name", and "Fundraiser Profile URL"
        $message = str_replace(
            [ '{campaign_name}', '{fundraiser_name}', '{fundraiser_first_name}', '{fundraiser_profile_url}' ],
            [
                sprintf( '<a href="%s">%s</a>', home_url( '/campaign/' . $campaign->getUrl() ), $campaign->getTitle() ),
                sprintf(
                    '<a href="%s">%s</a>',
                    home_url( '/campaign/' . $campaign->getUrl() . '/fundraiser/' . $fundraiser->getId() ),
                    $user->display_name
                ),
                $user->user_firstname ? : $user->display_name,
                home_url( '/campaign/' . $campaign->getUrl() . '/fundraiser/' . $fundraiser->getId() ),
            ],
            $message
        );

        $emailMessage = $this->mailer->getContents( 'centered', [
            'siteName'     => get_bloginfo( 'name' ),
            'siteURL'      => home_url(),
            'logo'         => $campaign->get( 'campaign_logo' ),
            'emailTitle'   => $subject,
            'emailContent' => nl2br( $message ),
        ] );

        wp_mail(
            $user->user_email,
            $subject,
            $emailMessage,
            $headers
        );
    }
}
