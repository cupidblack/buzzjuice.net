<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Helpers\FileUpload;
use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\Models\TeamInvitation;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class UpdateTeamRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class UpdateTeamRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'update-team';

    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @var TeamRepository
     */
    private $teamRepository;
    /**
     * @var FundraiserRepository
     */
    protected $fundraiserRepository;
    /**
     * @var int
     */
    private $fundraiserId;

    /**
     * @param FileUpload $fileUpload
     * @param TeamRepository $teamRepository
     * @param FundraiserRepository $fundraiserRepository
     */
    public function __construct(
        FileUpload           $fileUpload,
        TeamRepository       $teamRepository,
        FundraiserRepository $fundraiserRepository
    )
    {
        $this->fileUpload = $fileUpload;
        $this->teamRepository = $teamRepository;
        $this->fundraiserRepository = $fundraiserRepository;
    }

    public function updateTeamPermissionsCheck(WP_REST_Request $request)
    {
        if (parent::permissionsCheck()) {
            return true;
        }

        $team = $this->teamRepository->getTeam($request->get_param('team_id'));
        $user_id = $this->fundraiserRepository->getUserIdByFundraiserIdAndCampaignId(
            $team->get('owner_id'),
            $team->get('campaign_id')
        );

        return get_current_user_id() === $user_id;
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
                    'permission_callback' => [$this, 'updateTeamPermissionsCheck'],
                    'args' => [
                        'team_id' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Team ID', 'give-peer-to-peer'),
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                        ],
                        'campaign_id' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('Campaign ID', 'give-peer-to-peer'),
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                        ],
                        'name' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Team name', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                            },
                        ],
                        'story' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Team story', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                            },
                        ],
                        'goal' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Team goal', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return filter_var($param, FILTER_SANITIZE_NUMBER_FLOAT);
                            },
                        ],
                        'access' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Access', 'give-peer-to-peer'),
                            'default' => 'public'
                        ],
                        'emails' => [
                            'required' => false,
                            'type' => 'array',
                            'description' => esc_html__('Team member invitation email addresses', 'give-peer-to-peer'),
                            'validate_callback' => [$this, 'validateEmails'],
                        ],
                        'file_url' => [
                            'required' => false,
                            'type' => 'string',
                            'description' => esc_html__('File URL', 'give-peer-to-peer'),
                        ],
                        'notify_of_fundraisers' => [
                            'required' => false,
                            'type' => 'boolean',
                            'description' => esc_html__('New Fundraiser Notification', 'give-peer-to-peer'),
                        ],
                        'notify_of_team_donations' => [
                            'required' => false,
                            'type' => 'boolean',
                            'description' => esc_html__('New Team Donation Notification', 'give-peer-to-peer'),
                        ]
                    ],
                ],
            ]
        );
    }


    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest(WP_REST_Request $request)
    {

        if (!$this->teamRepository->teamExist($request->get_param('team_id'))) {
            return new WP_REST_Response(
                [
                    'status' => false,
                    'message' => __('That team does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $goal = Money::of($request->get_param('goal'), give_get_option('currency'));

        $team = Team::fromArray([
            'id'                        => $request->get_param('team_id'),
            'campaign_id'               => $request->get_param('campaign_id'),
            'name'                      => $request->get_param('name'),
            'story'                     => $request->get_param('story'),
            'goal'                      => $goal->getMinorAmount(),
            'access'                    => $request->get_param('access'),
            'notify_of_fundraisers'     => $request->get_param('notify_of_fundraisers'),
            'notify_of_team_donations'  => $request->get_param('notify_of_team_donations'),
        ]);

        if (!empty($request->get_param('file_url'))) {
            $team->set('profile_image', $request->get_param('file_url'));
        } else {
            // Handle file upload
            if (!empty($file = $request->get_file_params())) {

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
                    $team->set('profile_image', $image);
                }
            }
        }

        if (!$team->save()) {
            return new WP_REST_Response(
                [
                    'error' => 'update_failed',
                    'message' => __('An error occurred while processing your request. Unable to update team.',
                        'give-peer-to-peer'),
                ],
                400
            );
        }

        // Create team invitations.
        foreach ($request->get_param('emails') as $email) {
            TeamInvitation::fromArray([
                'team_id' => $team->getId(),
                'email' => $email,
            ])->save();
        }

        return new WP_REST_Response(
            ['message' => __('Team updated', 'give-peer-to-peer')]
        );
    }

    /**
     * @param string $emails
     * @return bool
     * @since 1.3.1 Validate $emails input as comma separated string.
     * @since 1.0.0
     * @since 1.3.0 Only validate if emails array is not empty.
     */
    public function validateEmails($emails)
    {
        return empty($emails) || array_reduce(explode(',', $emails), function ($carry, $email) {
                return $carry && filter_var($email, FILTER_VALIDATE_EMAIL);
            }, true);
    }
}
