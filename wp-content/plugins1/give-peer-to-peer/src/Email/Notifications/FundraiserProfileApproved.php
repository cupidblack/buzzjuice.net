<?php

namespace GiveP2P\Email\Notifications;

use Give_Email_Notification;
use GiveP2P\P2P\Models\Fundraiser;

/**
 * New Fundraiser Profile Approved Email
 *
 * @since 1.5.0
 */
class FundraiserProfileApproved extends Give_Email_Notification
{

    /**
     * Create a class instance.
     *
     * @since 1.5.0
     */
    public function init()
    {
        $this->load(
            [
                'id' => 'p2p-new-fundraiser-profile-approved',
                'label' => __('New Fundraiser Profile Approved', 'give-peer-to-peer'),
                'description' => __('Sent to designated recipient(s) when a fundraiser profile is approved.',
                    'give-peer-to-peer'),
                'has_recipient_field' => false,
                'recipient_group_name' => __('Individual Fundraiser, Team Fundraiser', 'give-peer-to-peer'),
                'notification_status' => 'enabled',
                'has_preview_header' => true,
                'email_tag_context' => ['donor', 'general', 'p2p', 'fundraiser'],
                'form_metabox_setting' => false,
                'default_email_subject' => esc_attr__('Your fundraiser profile has been approved!',
                    'give-peer-to-peer'),
                'default_email_message' => $this->getDefaultEmailMessage(),
                'default_email_header' => __('Fundraiser Profile Approved', 'give-peer-to-peer'),
            ]
        );

        add_filter("give_get_recipient_setting_field", [$this, 'changeRecipientSettingField'], 10, 4);
        add_filter('give_email_preview_header', [$this, 'emailPreviewHeader'], 10, 2);

        if ('disabled' != $this->get_notification_status()) {
            add_action('give_p2p_fundraiser_approved', [$this, 'sendEmailNotificationToFundraiser']);
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
            esc_html__('Your fundraising profile for %s has been approved!', 'give-peer-to-peer') . "\n\n" .
            esc_html__('Here’s your next steps:', 'give-peer-to-peer') . "\n\n" .
            '<ol>' .
            '<li>' .
            esc_html__('Click ', 'give-peer-to-peer') . '<a href="%s">' . esc_html__('here',
                'give-peer-to-peer') . '</a>' . esc_html__(' to view your profile ', 'give-peer-to-peer') .
            '</li>' . "\n" .
            '<li>' .
            esc_html__('Make sure you’ve added a fundraising goal and a description as to why you’re fundraising for %s.',
                'give-peer-to-peer') .
            '</li>' . "\n" .
            '<li>' .
            esc_html__('Share your fundraising profile on Facebook, Twitter, or by Email!', 'give-peer-to-peer') .
            '</li>' . "\n\n" .
            '</ol>' .
            esc_html__('We look forward to seeing you reach your goal! ', 'give-peer-to-peer') . "\n\n" .
            esc_html__('View your Fundraising profile here: ', 'give-peer-to-peer') . '<a href="%s">%s</a>' . "\n\n" .
            '%s' . "\n"
            , '{fundraiser_first_name}', '{campaign_name}', '{fundraiser_profile_url}', '{campaign_name}',
            '{fundraiser_profile_url}', '{fundraiser_profile_url}', '{sitename}');

        /**
         * @since 1.5.0
         */
        return apply_filters("give_{$this->config['id']}_get_default_email_message", $defaultEmailMessage, $this);
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
            $recipient['value'] = $recipient['default'] = '{fundraiser_email}';
            $recipient['desc'] = __(
                'This email is automatically sent to the fundraiser and the recipient cannot be customized.',
                'give-peer-to-peer'
            );
        }

        return $recipient;
    }

    /**
     * @since 1.5.0
     *
     * @param string                  $emailPreviewHeader
     * @param Give_Email_Notification $email
     *
     * @return string
     */
    public function emailPreviewHeader(string $emailPreviewHeader, Give_Email_Notification $email): string
    {
        if ($this->config['id'] !== $email->config['id']) {
            return $emailPreviewHeader;
        }

        $userId = give_check_variable(give_clean($_GET), 'isset', 0, 'user_id');

        $fundraisers = \GiveP2P\P2P\Facades\Fundraiser::getLastRegisteredFundraisers();
        $options = [];

        // Default option.
        $options[0] = esc_html__('No fundraiser(s) found.', 'give-peer-to-peer');

        // Provide nice human readable options.
        if ($fundraisers) {
            $options[0] = esc_html__('- Select a fundraiser -', 'give-peer-to-peer');
            foreach ($fundraisers as $fundraiser) {
                if ( ! $fundraiser->getUserId() || $fundraiser->isTeamCaptain()) {
                    continue;
                }
                $options[$fundraiser->getUserId()] = esc_html('#' . $fundraiser->getId() . ' - ' . $fundraiser->getEmail());
            }
        }

        $requestUrlData = wp_parse_url($_SERVER['REQUEST_URI']);
        $query = $requestUrlData['query'];

        // Remove user id query param if set from request url.
        $query = remove_query_arg(['user_id'], $query);

        $requestUrl = esc_url_raw(home_url('/?' . str_replace('', '', $query)));

        ob_start();
        ?>
        <script type="text/javascript">
            function change_preview() {
                var transactions = document.getElementById("give_preview_email_user_id");
                var selected_trans = transactions.options[transactions.selectedIndex];
                if (selected_trans) {
                    var url_string = "<?php echo $requestUrl; ?>&user_id=" + selected_trans.value;
                    window.location = url_string;
                }
            }
        </script>

        <style type="text/css">
            .give_preview_email_user_id_main {
                margin: 0;
                padding: 10px 0;
                width: 100%;
                background-color: #FFF;
                border-bottom: 1px solid #eee;
                text-align: center;
            }

            .give_preview_email_user_id_label {
                font-size: 12px;
                color: #333;
                margin: 0 4px 0 0;
            }
        </style>

        <!-- Start constructing HTML output.-->
        <div class="give_preview_email_user_id_main">

            <label for="give_preview_email_user_id" class="give_preview_email_user_id_label">
                <?php
                echo esc_html__('Preview email with a fundraiser:', 'give-peer-to-peer'); ?>
            </label>

            <?php
            // The select field with 100 latest transactions
            echo Give()->html->select(
                [
                    'name' => 'preview_email_user_id',
                    'selected' => $userId,
                    'id' => 'give_preview_email_user_id',
                    'class' => 'give-preview-email-fundraiser-id',
                    'options' => $options,
                    'chosen' => false,
                    'select_atts' => 'onchange="change_preview()"',
                    'show_option_all' => false,
                    'show_option_none' => false,
                ]
            );
            ?>
            <!-- Closing tag-->
        </div>
        <?php

        $emailPreviewHeader = ob_get_clean();

        return $emailPreviewHeader;
    }

    /**
     * @since 1.5.0
     *
     * @param Fundraiser $fundraiser
     */
    public function sendEmailNotificationToFundraiser(Fundraiser $fundraiser)
    {
        if ( ! $fundraiser->hasApprovalStatus() ) {
            return;
        }

        $this->recipient_email = $fundraiser->getEmail();

        $this->send_email_notification(
            [
                'user_id' => $fundraiser->getUserId(),
            ]
        );
    }
}
