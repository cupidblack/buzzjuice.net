<?php

namespace GiveP2P\P2P\Routes\Admin;

use Exception;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Mailer;
use GiveP2P\P2P\Models\TeamInvitation;
use GiveP2P\P2P\Repositories\TeamInvitationRepository;
use GiveP2P\P2P\Routes\Endpoint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class CreateTeamRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.4.0
 */
class SendTeamInvitationEmailsRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'admin-send-team-invitation-emails';

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var TeamInvitationRepository
     */
    protected $invitationRepository;

    /**
     * @param Mailer $mailer
     * @param TeamInvitationRepository $invitationRepository
     */
    public function __construct(
        Mailer $mailer,
        TeamInvitationRepository $invitationRepository
    ) {
        $this->mailer = $mailer;
        $this->invitationRepository = $invitationRepository;
    }

    /**
     * @inheritDoc
     */
    public function registerRoute()
    {
        register_rest_route(
            parent::ROUTE_NAMESPACE,
            $this->endpoint,
            [
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'handleRequest'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                    'args' => [
                        'campaignId' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Campaign ID', 'give-peer-to-peer'),
                        ],
                        'teamId' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Team ID', 'give-peer-to-peer'),
                        ],
                        'emails' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Emails', 'give-peer-to-peer'),
                            'validate_callback' => [$this, 'validateEmails'],
                            'sanitize_callback' => static function ($emails) {
                                return array_filter(array_map('trim', explode(',', $emails)));
                            },
                        ],
                    ],
                ],
            ]
        );
    }


    /**
     * @since 1.4.0
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $campaign = Campaign::getCampaign($request->get_param('campaignId'));
        if (!$campaign) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_campaign',
                    'message' => __('Campaign you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $team = Team::getTeam($request->get_param('teamId'));
        if (!$team) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_team',
                    'message' => __('Team you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        try {
            $wpdb->query('START TRANSACTION');

            // Create team invitations.
            foreach ($request->get_param('emails') as $email) {
                TeamInvitation::fromArray([
                    'team_id' => $team->getId(),
                    'email' => $email,
                ])->save();
            }

            $invitations = $this->invitationRepository->getForTeamNotSent($team->getId());

            $teamUrl = home_url('campaign/' . $campaign->getUrl() . '/team/' . $team->getId() . '/join');
            $campaignUrl = home_url('campaign/' . $campaign->getUrl());

            $fromName = give_get_option('from_name', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
            $fromAddress = give_get_option('from_email', get_option('admin_email'));

            $headers = "From: $fromName <$fromAddress>\r\n";
            $headers .= "Reply-To: $fromAddress\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";

            $subject = __('Invitation to fundraise', 'give-peer-to-peer');

            $emailMessage = $this->mailer->getContents('centered', [
                'siteName' => get_bloginfo('name'),
                'siteURL' => home_url(),
                'logo' => $team->get('profile_image'),
                'emailTitle' => $subject,
                'emailContent' => sprintf(
                    __('You are invited to fundraise with %s on behalf of %s.', 'give-peer-to-peer'),
                    sprintf('<a href="%s">%s</a>', $teamUrl, $team->getName()),
                    sprintf('<a href="%s">%s</a>', $campaignUrl, $campaign->getTitle())
                ),
            ]);

            foreach ($invitations as $invitation) {
                $sent = wp_mail(
                    $invitation->getEmail(),
                    $subject,
                    $emailMessage,
                    $headers
                );
                if ($sent) {
                    $invitation->setDateSent(date('Y-m-d H:i:s'));
                    $invitation->save();
                }
            }
        } catch (Exception $exception) {
            $wpdb->query('ROLLBACK');

            return new WP_REST_Response(
                [
                    'status' => 'failed_send_invitations',
                    'message' => $exception->getMessage(),
                ],
                404
            );
        }

        $wpdb->query('COMMIT');

        return new WP_REST_Response();
    }

    /**
     * @since 1.4.0
     */
    public function validateEmails(string $emails): bool
    {
        return empty($emails) || array_reduce(explode(',', $emails), function ($carry, $email) {
                return $carry && filter_var($email, FILTER_VALIDATE_EMAIL);
            }, true);
    }
}

