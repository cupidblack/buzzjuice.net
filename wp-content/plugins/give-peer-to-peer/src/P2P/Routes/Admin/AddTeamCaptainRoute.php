<?php

namespace GiveP2P\P2P\Routes\Admin;


use Give\Framework\Support\ValueObjects\Money;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Helpers\FileUpload;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Routes\Endpoint;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;


/**
 * This class handles rest api requests on 'admin-add-team-captain' endpoint.
 *
 * @since 1.4.0
 */
class AddTeamCaptainRoute extends Endpoint
{
    /**
     * @var string
     */
    protected $endpoint = 'admin-add-team-captain';

    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @var FundraiserRepository
     */
    private $fundraiserRepository;

    /**
     * @since 1.4.0
     */
    public function __construct(FileUpload $fileUpload, FundraiserRepository $fundraiserRepository)
    {
        $this->fileUpload = $fileUpload;
        $this->fundraiserRepository = $fundraiserRepository;
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
                        'firstName' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('First Name', 'give-peer-to-peer'),
                        ],
                        'lastName' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Last Name', 'give-peer-to-peer'),
                        ],
                        'email' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Email', 'give-peer-to-peer'),
                            'format' => 'email'
                        ],
                        'story' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Fundraiser Story', 'give-peer-to-peer'),
                        ],
                        'goal' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Fundraiser Goal', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                            }
                        ],
                        'file_url' => [
                            'required' => false,
                            'type' => 'string',
                            'description' => esc_html__('File URL', 'give-peer-to-peer'),
                        ],
                        'notify_of_donations' => [
                            'required' => false,
                            'type' => 'boolean',
                            'description' => esc_html__('New Donation Notification', 'give-peer-to-peer'),
                            'default' => '0',
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
                    'status' => 'invalid_campaign',
                    'message' => __('Team you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $user = get_user_by_email($request->get_param('email'));

        if (!$user) {
            $userId = wp_insert_user(
                [
                    'user_login' => $request->get_param('email'),
                    'user_email' => $request->get_param('email'),
                    'first_name' => $request->get_param('firstName'),
                    'last_name' => $request->get_param('lastName'),
                    'user_pass' => null
                ]
            );

            if (is_wp_error($userId)) {
                return new WP_REST_Response(
                    [
                        'error' => 'registration_failed',
                        'message' => $userId->get_error_message()
                    ],
                    400
                );
            }

            $user = get_user_by('ID', $userId);
        }

        $fundraiser = \GiveP2P\P2P\Facades\Fundraiser::getFundraiser(
            $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId($user->ID, $campaign->getId())
        );

        if ($fundraiser) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_fundraiser',
                    'message' => __(
                        'Fundraiser already exist in this campaign.',
                        'give-peer-to-peer'
                    ),
                ],
                404
            );
        }

        $goal = Money::fromDecimal(
            $request->get_param('goal'),
            give_get_option('currency')
        );

        $fundraiser = Fundraiser::fromArray([
            'user_id'                   => $user->ID,
            'team_id'                   => $team->getId(),
            'story'                     => $request->get_param('story'),
            'team_captain'              => 1,
            'status'                    => Status::ACTIVE,
            'goal'                      => $goal->formatToMinorAmount(),
            'campaign_id'               => $campaign->getId(),
            'notify_of_donations'       => $request->get_param('notify_of_donations'),
        ]);

        // Handle file upload
        if ($request->get_param('file_url')) {
            $fundraiser->set('profile_image', $request->get_param('file_url'));
        } elseif (!empty($file = $request->get_file_params())) {
            $upload = $this->fileUpload->handleFile($file);

            if (is_wp_error($upload)) {
                return new WP_REST_Response(
                    [
                        'error' => 'upload_failed',
                        'message' => $upload->get_error_message(),
                    ],
                    400
                );
            }

            if ($image = wp_get_attachment_image_url($upload)) {
                $fundraiser->set('profile_image', $image);
            }
        }

        $wpdb->query('START TRANSACTION');

        try {
            if ($fundraiser->save()) {
                // Remove existing fundraiser from team captaincy.
                $teamCaptain = \GiveP2P\P2P\Facades\Fundraiser::getFundraiser($team->getOwnerId());
                if ($teamCaptain) {
                    Fundraiser::fromArray(
                        array_merge(
                            $teamCaptain->toArray(),
                            ['team_captain' => 0]
                        )
                    )->save();
                }

                \GiveP2P\P2P\Models\Team::fromArray(
                    array_merge(
                        $team->toArray(),
                        ['owner_id' => $fundraiser->getId()],
                    )
                )->save();

            }

            $wpdb->query('COMMIT');
        } catch (\Exception $exception) {
            $wpdb->query('ROLLBACK');

            if (!empty($userId)) {
                wp_delete_user($userId);
            }

            return new WP_REST_Response(
                [
                    'status' => 'unable_add_team_captain',
                    'message' => $exception->getMessage(),
                ],
                404
            );
        }

        wp_send_new_user_notifications($user->ID);

        return new WP_REST_Response(['teamId' => $team->getId()]);
    }
}
