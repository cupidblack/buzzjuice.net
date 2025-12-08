<?php

namespace GiveP2P\P2P\Routes\Admin;

use Give\Framework\Support\ValueObjects\Money;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Helpers\FileUpload;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Routes\Endpoint;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This endpoint handles create team requests from site administrator request.
 * @package GiveP2P\P2P\Routes\Admin
 *
 * @since 1.4.0
 */
class CreateFundraiserRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'admin-create-fundraiser';

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
                        'userId' => [
                            'required' => true,
                            'type' => 'integer',
                            'description' => esc_html__('WP User ID', 'give-peer-to-peer'),
                        ],
                        'story' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Fundraiser goal', 'give-peer-to-peer'),
                        ],
                        'goal' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Fundraiser goal', 'give-peer-to-peer'),
                            'sanitize_callback' => function ($param) {
                                return filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                            },
                        ],
                        'teamId' => [
                            'type' => 'integer',
                            'description' => esc_html__('Team id', 'give-peer-to-peer'),
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
                        ]
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

        if (!$campaign = Campaign::getCampaign($request->get_param('campaignId'))) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_campaign',
                    'message' => __('Campaign you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $user = get_user_by('ID', $request->get_param('userId'));

        if (!$user) {
            return new WP_REST_Response(
                [
                    'error' => 'invalid_wp_user',
                    'message' => esc_html__('Invalid WP user', 'give-peer-to-peer')
                ],
                400
            );
        }

        $fundraiser = Fundraiser::getFundraiser(
            $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId($user->ID, $campaign->getId())
        );

        if ($fundraiser) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_fundraiser',
                    'message' => sprintf(
                        __(
                            'Fundraiser is already exist in this campaign with %s.',
                            'give-peer-to-peer'
                        ),
                        $user->user_email
                    ),
                ],
                404
            );
        }

        $goal = Money::fromDecimal(
            $request->get_param('goal'),
            give_get_option('currency')
        );

        $fundraiser = \GiveP2P\P2P\Models\Fundraiser::fromArray([
            'campaign_id'           => $campaign->getId(),
            'user_id'               => $user->ID,
            'teamId'                => $user->ID,
            'story'                 => $request->get_param('story'),
            'goal'                  => $goal->formatToMinorAmount(),
            'status'                => Status::ACTIVE,
            'notify_of_donations'   => $request->get_param('notify_of_donations'),
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

        if ($teamId = $request->get_param('teamId')) {
            $team = Team::getTeam($teamId);
            if (!$team) {
                return new WP_REST_Response(
                    [
                        'status' => 'invalid_team',
                        'message' => __('Team you are trying to join does not exist', 'give-peer-to-peer'),
                    ],
                    404
                );
            }

            $fundraiser->set('team_id', $team->getId());
        }

        $wpdb->query('START TRANSACTION');

        if (!$fundraiser->save()) {
            $wpdb->query('ROLLBACK');

            return new WP_REST_Response(
                [
                    'error' => 'create_failed',
                    'message' => __('Something went wrong', 'give-peer-to-peer'),
                ],
                400
            );
        }

        $wpdb->query('COMMIT');

        return new WP_REST_Response(['fundraiserId' => $fundraiser->getId()]);
    }
}
