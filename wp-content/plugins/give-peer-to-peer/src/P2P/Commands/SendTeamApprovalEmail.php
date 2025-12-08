<?php

namespace GiveP2P\P2P\Commands;

use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Mailer;
use GiveP2P\P2P\Models\Campaign;
use GiveP2P\P2P\Models\Team;

class SendTeamApprovalEmail {

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
     * @since 1.4.0 Fixed email recipient for team approval email.
     * @param  Team      $team
     * @param  Campaign  $campaign
     */
    public function __invoke( Team $team, Campaign $campaign ) {
        $fromName    = give_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $fromAddress = give_get_option( 'from_email', get_option( 'admin_email' ) );

        $headers = "From: $fromName <$fromAddress>\r\n";
        $headers .= "Reply-To: $fromAddress\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        $subject = $campaign->get( 'team_approvals_email_subject' );
        $message = $campaign->get( 'team_approvals_email_body' );

        $fundraiser = Fundraiser::getFundraiser( $team->get( 'owner_id' )  );
        $user = get_userdata( $fundraiser->getUserId() );

        // Merge "Campaign Name", "Team Name", "Fundraiser First Name", "Team Profile URL"
        $message = str_replace( [ '{campaign_name}', '{team_name}', '{fundraiser_first_name}', '{team_profile_url}' ], [
            sprintf(
                '<a href="%s" target="_blank">%s</a>',
                home_url( '/campaign/' . $campaign->getUrl() ),
                $campaign->getTitle()
            ),
            sprintf(
                '<a href="%s" target="_blank">%s</a>',
                home_url( '/campaign/' . $campaign->getUrl() . '/team/' . $team->getId() ),
                $team->getName()
            ),
            $user->user_firstname ? : $user->display_name,
            home_url( '/campaign/' . $campaign->getUrl() . '/team/' . $team->getId() ),
        ], $message );

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
