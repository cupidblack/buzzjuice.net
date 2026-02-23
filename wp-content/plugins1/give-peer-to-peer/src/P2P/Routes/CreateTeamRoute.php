<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Helpers\FileUpload;
use GiveP2P\P2P\Models\Team;
use GiveP2P\P2P\Models\TeamInvitation;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class CreateTeamRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class CreateTeamRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'create-team';

    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @var int
     */
    private $fundraiserId;

    /**
     * @var FundraiserRepository
     */
    private $fundraiserRepository;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @param FileUpload $fileUpload
     * @param FundraiserRepository $fundraiserRepository
     */
    public function __construct(
        FileUpload           $fileUpload,
        FundraiserRepository $fundraiserRepository,
        CampaignRepository   $campaignRepository
    )
    {
        $this->fileUpload = $fileUpload;
        $this->fundraiserRepository = $fundraiserRepository;
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return bool
     */
    public function fundraiserPermissionCheck(WP_REST_Request $request)
    {
        // User exist on campaign?
        $this->fundraiserId = $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId(
            get_current_user_id(),
            $request->get_param('campaign_id')
        );

        return $this->fundraiserId && !$this->fundraiserRepository->fundraiserHasTeam($this->fundraiserId);
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
                    'permission_callback' => [$this, 'fundraiserPermissionCheck'],
                    'args' => [
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
                                return filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                            },
                        ],
                        'access' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Team goal', 'give-peer-to-peer'),
                            'default' => 'public'
                        ],
                        'emails' => [
                            'required' => false,
                            'type' => 'array',
                            'description' => esc_html__('Team member invitation email addresses', 'give-peer-to-peer'),
                            'validate_callback' => [$this, 'validateEmails'],
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
                            'default' => '0',
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
        global $wpdb;

        // Check Campaign
        if (!$campaign = $this->campaignRepository->getCampaign($request->get_param('campaign_id'))) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_campaign',
                    'message' => __('The Campaign you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }
        $wpdb->query('START TRANSACTION');

        $goal = Money::of($request->get_param('goal'), give_get_option('currency'));

        $team = Team::fromArray([
            'campaign_id'               => $request->get_param('campaign_id'),
            'owner_id'                  => $this->fundraiserId,
            'name'                      => $request->get_param('name'),
            'story'                     => $request->get_param('story'),
            'goal'                      => $goal->getMinorAmount(),
            'access'                    => $request->get_param('access'),
            'status'                    => $campaign->doesRequireTeamApproval()
                                        ? Status::PENDING
                                        : Status::ACTIVE,
            'notify_of_fundraisers'     => $request->get_param('notify_of_fundraisers'),
            'notify_of_team_donations'  => $request->get_param('notify_of_team_donations'),
        ]);

        /**
         * @since 1.3.0 Ensure that the team captain is set when registering a team.
         */
        $teamCaptain = Fundraiser::getFundraiser($this->fundraiserId);
        $teamCaptain->set('team_captain', 1);
        $teamCaptain->save();

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

        if (!$team->save()) {
            return new WP_REST_Response(
                [
                    'error' => 'create_failed',
                    'message' => __('An error occurred while processing your request. Unable to create team. ',
                        'give-peer-to-peer'),
                ],
                400
            );
        }

        // Connect fundraiser with the team
        $fundraiser = $this->fundraiserRepository->getFundraiser($this->fundraiserId);
        $fundraiser->set('team_id', $team->getId());

        if (!$fundraiser->save()) {
            $wpdb->query('ROLLBACK');

            return new WP_REST_Response(
                [
                    'error' => 'create_failed',
                    'message' => __('An error occurred while processing your request. Unable to connect fundraiser with team.',
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

        $wpdb->query('COMMIT');

        return new WP_REST_Response(
            [
                'teamId' => $team->getId(),
                'redirect' => '/register/create-profile/',
            ]
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
