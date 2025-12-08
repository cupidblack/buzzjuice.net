<?php

namespace GiveP2P\P2P\Commands;

use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Mailer;

class SendAdminRegistrationDigestEmail {

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

    public function __invoke() {
        $campaigns = array_filter( Campaign::getActiveCampaigns(), function ( $campaign ) {
            return $campaign->isRegistrationDigestEnabled();
        } );

        if ( empty( $campaigns ) ) {
            return;
        }

        $newFundraisers = Fundraiser::getRecentlyRegisteredFundraisers();
        $newTeams       = Team::getRecentlyRegisteredTeams();

        foreach ( $campaigns as $campaign ) {
            $message = '';

            $teams       = array_filter( $newTeams, function ( $team ) use ( $campaign ) {
                return $campaign->getId() === $team->get( 'campaign_id' );
            } );
            $fundraisers = array_filter( $newFundraisers, function ( $fundraiser ) use ( $campaign ) {
                return $campaign->getId() === $fundraiser->getCampaignId();
            } );

            if ( ! count( $fundraisers ) && ! count( $teams ) ) {
                continue;
            }

            if ( count( $fundraisers ) ) {
                $message .= '<h2>'
                            . sprintf( _n( '%s New Fundraiser', '%s New Fundraisers', count( $fundraisers ), 'give-peer-to-peer' ),
                        number_format_i18n( count( $fundraisers ) ) )
                            . '</h2><div>';
                foreach ( array_slice( $fundraisers, 0, 7, true ) as $fundraiser ) {
                    $profileImage = $fundraiser->get( 'profile_image' ) ? : $this->getFallbackProfileImage();
                    $message      .= "<img style='margin-left:-7px;box-shadow: -1px 1px lightgray;width:40px;height:40px;border-radius:50%;vertical-align:middle;' src='$profileImage' /></li>";
                }
                $message .= '</div>';
                $message .= '<br />';
                $url = sprintf('edit.php?post_type=give_forms&page=p2p-edit-campaign&id=%d&tab=fundraisers', $campaign->getId());
                $message .= sprintf('<a href="%s">Review New Fundraisers</a>', admin_url($url));
            }

            if ( count( $teams ) ) {
                $message .= '<h2>'
                            . sprintf( _n( '%s New Team', '%s New Teams', count( $teams ), 'give-peer-to-peer' ), number_format_i18n( count( $teams ) ) )
                            . '</h2><div>';
                foreach ( array_slice( $teams, 0, 7, true ) as $team ) {
                    $profileImage = $team->get( 'profile_image' ) ? : $this->getFallbackProfileImage();
                    $message      .= "<img style='margin-left:-7px;box-shadow: -1px 1px lightgray;width:40px;height:40px;border-radius:50%;vertical-align:middle;' src='$profileImage' /></li>";
                }
                $message .= '</div>';
                $message .= '<br />';
                $url = sprintf('edit.php?post_type=give_forms&page=p2p-edit-campaign&id=%d&tab=teams', $campaign->getId());
                $message .= sprintf('<a href="%s">Review New Teams</a>', admin_url($url));
            }
            $this->send( $message, $campaign );
        }
    }

    public function send( $message, $campaign ) {
        $fromName   = give_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $adminEmail = give_get_option( 'from_email', get_option( 'admin_email' ) );

        $headers = "From: $fromName <$adminEmail>\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        $subject = __( 'New Registrations', 'give-peer-to-peer' );

        $emailMessage = $this->mailer->getContents( 'centered', [
            'siteName'     => get_bloginfo( 'name' ),
            'siteURL'      => home_url(),
            'logo'         => $campaign->get( 'campaign_logo' ) ? : get_site_icon_url(),
            'emailTitle'   => $campaign->getTitle(),
            'emailContent' => nl2br( $message ),
        ] );

        return wp_mail(
            $adminEmail,
            $subject,
            $emailMessage,
            $headers
        );
    }

    public function getFallbackProfileImage() {
        return trailingslashit( GIVE_P2P_URL ) . 'public/img/profile.png';
    }
}
