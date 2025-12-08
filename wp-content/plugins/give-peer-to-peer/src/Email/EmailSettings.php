<?php

namespace GiveP2P\Email;

use Give_Email_Notification;
use GiveP2P\Email\Notifications\AdminFundraiserJoined;
use GiveP2P\Email\Notifications\AdminFundraiserJoinedNeedsApproval;
use GiveP2P\Email\Notifications\AdminTeamCreated;
use GiveP2P\Email\Notifications\AdminTeamCreatedNeedsApproval;
use GiveP2P\Email\Notifications\DonationIndividualFundraiser;
use GiveP2P\Email\Notifications\DonationTeamCaptain;
use GiveP2P\Email\Notifications\DonationTeamFundraiser;
use GiveP2P\Email\Notifications\FundraiserProfileApproved;
use GiveP2P\Email\Notifications\FundraiserProfileCreated;
use GiveP2P\Email\Notifications\TeamFundraiserJoined;
use GiveP2P\Email\Notifications\TeamProfileApproved;
use GiveP2P\Email\Notifications\TeamProfileCreated;

/**
 * Email Settings
 *
 * @since 1.5.0
 */
class EmailSettings
{

    const PAGE_SLUG = 'p2p-email';
    const ADMIN_PAGE_SLUG = 'p2p-admin-email';

    /**
     * @since 1.5.0
     *
     * @param Give_Email_Notification[] $emails
     *
     * @return Give_Email_Notification[]
     */
    public function loadEmailNotifications(array $emails): array
    {
        $newEmails = [
            AdminFundraiserJoined::get_instance(),
            AdminFundraiserJoinedNeedsApproval::get_instance(),
            AdminTeamCreated::get_instance(),
            AdminTeamCreatedNeedsApproval::get_instance(),

            DonationTeamCaptain::get_instance(),
            TeamFundraiserJoined::get_instance(),
            TeamProfileCreated::get_instance(),
            TeamProfileApproved::get_instance(),

            DonationIndividualFundraiser::get_instance(),
            DonationTeamFundraiser::get_instance(),
            FundraiserProfileApproved::get_instance(),
            FundraiserProfileCreated::get_instance(),
        ];

        $emails = array_merge($emails, $newEmails);

        return $emails;
    }

    /**
     * @since 1.5.0
     *
     * @param array $settings
     *
     * @return array
     */
    public function registerEmailSettings(array $settings): array
    {
        $current_section = give_get_current_setting_section();

        if ($current_section == self::PAGE_SLUG) {
            $settings = [
                [
                    'desc' => __('Email notifications related to the Peer-to-Peer Fundraising add-on are listed below. Select an email to configure it.',
                        'give-peer-to-peer'
                    ),
                    'type' => 'title',
                    'id' => 'fundraiser_email_notification_settings',
                    'table_html' => false,
                ],
                [
                    'type' => 'email_notification',
                ],
                [
                    'type' => 'sectionend',
                    'id' => 'fundraiser_email_notification_settings',
                ],

            ];
        } elseif ($current_section == self::ADMIN_PAGE_SLUG) {
            $settings = [
                [
                    'desc' => __('Email notifications related to the Peer-to-Peer Fundraising add-on are listed below. Select an email to configure it.',
                        'give-peer-to-peer'
                    ),
                    'type' => 'title',
                    'id' => 'admin_email_notification_settings',
                    'table_html' => false,
                ],
                [
                    'type' => 'email_notification',
                ],
                [
                    'type' => 'sectionend',
                    'id' => 'admin_email_notification_settings',
                ],

            ];
        }

        return $settings;
    }

    /**
     * @since 1.5.0
     *
     * @param array $sections
     *
     * @return array
     */
    public function registerEmailSection(array $sections): array
    {
        $fundraiser_section = [
            self::PAGE_SLUG => esc_html__('P2P Fundraiser Emails', 'give-peer-to-peer'),
            self::ADMIN_PAGE_SLUG => esc_html__('P2P Admin Emails', 'give-peer-to-peer'),
        ];

        $sections = array_slice($sections, 0, 2) + $fundraiser_section + array_slice($sections, 2);

        return $sections;
    }

    /**
     * @since 1.5.0
     *
     * @param Give_Email_Notification[] $email_notifications
     * @param Give_Email_Notification   $email_notification
     * @param string                    $current_section
     *
     * @return Give_Email_Notification[]
     */
    public function addItemsOnProperEmailSection(
        array $email_notifications,
        Give_Email_Notification $email_notification,
        string $current_section
    ): array {
        if ($this->isP2pEmail($email_notification->config['id']) && $this->isP2pSection($current_section)) {
            $email_notifications[] = $email_notification;
        } elseif ($this->isP2pAdminEmail($email_notification->config['id']) && $this->isP2pAdminSection($current_section)) {
            $email_notifications[] = $email_notification;
        } elseif ($this->isP2pEmail($email_notification->config['id']) && ! $this->isP2pSection(
                $current_section
            ) && ($key = array_search($email_notification, $email_notifications)) !== false) {
            unset($email_notifications[$key]);
        } elseif ($this->isP2pAdminEmail($email_notification->config['id']) && ! $this->isP2pAdminSection(
                $current_section
            ) && ($key = array_search($email_notification, $email_notifications)) !== false) {
            unset($email_notifications[$key]);
        }

        return $email_notifications;
    }

    /**
     * @since 1.5.0
     *
     * @param string $emailID
     *
     * @return bool
     */
    private function isP2pEmail(string $emailID): bool
    {
        return false !== strpos($emailID, 'p2p') && ! $this->isP2pAdminEmail($emailID);
    }

    /**
     * @since 1.5.0
     *
     * @param string $section
     *
     * @return bool
     */
    private function isP2pSection(string $section): bool
    {
        return self::PAGE_SLUG === $section;
    }

    /**
     * @since 1.5.0
     *
     * @param string $emailID
     *
     * @return bool
     */
    private function isP2pAdminEmail(string $emailID): bool
    {
        return false !== strpos($emailID, 'p2p-admin');
    }

    /**
     * @since 1.5.0
     *
     * @param string $section
     *
     * @return bool
     */
    private function isP2pAdminSection(string $section): bool
    {
        return self::ADMIN_PAGE_SLUG === $section;
    }
}
