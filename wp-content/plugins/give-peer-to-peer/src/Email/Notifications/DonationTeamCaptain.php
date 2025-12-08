<?php

namespace GiveP2P\Email\Notifications;

use Give_Email_Notification;
use Give_Payment;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;

/**
 * New Donation Email (Team Captain)
 *
 * @since 1.5.0
 */
class DonationTeamCaptain extends Give_Email_Notification
{
    public $payment;

    /**
     * Create a class instance.
     *
     * @since 1.5.0
     */
    public function init()
    {
        $this->payment = new Give_Payment(0);

        $this->load(
            [
                'id' => 'p2p-new-donation-team-captain',
                'label' => __('New Donation Team Captain', 'give-peer-to-peer'),
                'description' => __(
                    'Sent to designated recipient(s) when a new donation is received or a pending donation is marked as complete.',
                    'give-peer-to-peer'
                ),
                'has_recipient_field' => false,
                'recipient_group_name' => __('Team Captain', 'give-peer-to-peer'),
                'notification_status' => 'enabled',
                'email_tag_context' => ['donor', 'donation', 'general', 'p2p', 'donationTeamCaptain'],
                'form_metabox_setting' => true,
                'default_email_subject' => esc_attr__(
                    'New Donation Received for {team_name} for {campaign_name}!',
                    'give-peer-to-peer'
                ),
                'default_email_message' => $this->getDefaultEmailMessage(),
                'default_email_header' => __('New Donation!', 'give-peer-to-peer'),
            ]
        );

        add_filter("give_get_recipient_setting_field", [$this, 'changeRecipientSettingField'], 10, 4);

        if ('disabled' != $this->get_notification_status()) {
            add_action("give_new-donation_email_notification", [$this, 'sendEmailNotificationToTeamCaptain']);
        }
    }

    /**
     * @since 1.5.0
     *
     * @return string
     */
    public function getDefaultEmailMessage(): string
    {
        $defaultEmailMessage = sprintf(
            esc_html__('Hey %s!', 'give-peer-to-peer') . "\n\n" .
            esc_html__('A new donation has been received to your Team Profile for %s.',
                'give-peer-to-peer') . "\n\n" .
            esc_html__('Here’s the recipient details:', 'give-peer-to-peer') . "\n\n" .
            '<strong>' . esc_html__('Recipient Name:', 'give-peer-to-peer') . '</strong>' . ' %s' . "\n" .
            '<strong>' . esc_html__('Recipient Goal:', 'give-peer-to-peer') . '</strong>' . ' %s' . "\n" .
            '<strong>' . esc_html__('Recipient progress:', 'give-peer-to-peer') . '</strong>' . ' %s' . "\n\n" .
            esc_html__('Here’s the donation details:', 'give-peer-to-peer') . "\n\n" .
            '<strong>' . esc_html__('Donor:', 'give-peer-to-peer') . '</strong>' . ' %s' . "\n" .
            '<strong>' . esc_html__('Amount:', 'give-peer-to-peer') . '</strong>' . ' %s' . "\n" .
            '<strong>' . esc_html__('Donation Date:', 'give-peer-to-peer') . '</strong>' . ' %s' . "\n\n" .
            esc_html__('Share your Team with your friends on social media or by email to reach your fundraising
            goal! View your Fundraising profile here: ',
                'give-peer-to-peer') . '<a href="%s">%s</a>' . "\n\n" .
            '%s' . "\n"
            , '{team_captain_first_name}', '{campaign_name}', '{recipient_name}', '{recipient_goal}',
            '{recipient_progress}', '{fullname}', '{amount}', '{date}',
            '{team_profile_url}', '{team_profile_url}', '{sitename}');

        return apply_filters("give_{$this->config['id']}_get_default_email_message", $defaultEmailMessage);
    }

    /**
     * @since 1.5.0
     *
     * @param array                   $recipient
     * @param Give_Email_Notification $email
     *
     * @return array
     */
    public function changeRecipientSettingField(array $recipient, Give_Email_Notification $email): array
    {
        if ($this->config['id'] === $email->config['id']) {
            $recipient['value'] = $recipient['default'] = '{team_captain_email}';
            $recipient['desc'] = __(
                'This email is automatically sent to the team captain and the recipient cannot be customized.',
                'give-peer-to-peer'
            );
        }

        return $recipient;
    }

    /**
     * @since 1.5.0
     *
     * @param int $paymentId
     */
    public function sendEmailNotificationToTeamCaptain(int $paymentId)
    {
        $this->payment = new Give_Payment($paymentId);

        if ( ! $this->payment->ID ||
             ! isset($this->payment->payment_meta['p2pSourceID']) ||
             ! isset($this->payment->payment_meta['p2pSourceType'])) {
            return;
        }

        $p2pSourceID = $this->payment->payment_meta['p2pSourceID'];
        $p2pSourceType = $this->payment->payment_meta['p2pSourceType'];

        $teamCaptain = false;

        if ('fundraiser' === $p2pSourceType) {
            $fundraiser = Fundraiser::getFundraiser($p2pSourceID);

            $team = Team::getTeam($fundraiser->getTeamId());

            if ( ! $team || ! $team->isNotifiedOfTeamDonations()) {
                return;
            }

            if ($fundraiser->getTeamId()) {
                $teamOwnerId = Team::getTeam($fundraiser->getTeamId())->getOwnerId();
                $teamCaptain = Fundraiser::getFundraiser($teamOwnerId);
            }
        }

        if ('team' === $p2pSourceType) {
            $teamOwnerId = Team::getTeam($p2pSourceID)->getOwnerId();
            $teamCaptain = Fundraiser::getFundraiser($teamOwnerId);
        }

        if ($teamCaptain) {
            $this->recipient_email = $teamCaptain->getEmail();
            $this->send_email_notification(
                [
                    'payment_id' => $paymentId,
                ]
            );
        }
    }
}
