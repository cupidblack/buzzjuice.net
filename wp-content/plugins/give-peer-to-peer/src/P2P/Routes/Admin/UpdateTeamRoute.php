<?php

namespace GiveP2P\P2P\Routes\Admin;

use Give\Framework\Support\ValueObjects\Money;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Team;
use GiveP2P\P2P\Helpers\FileUpload;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Routes\Endpoint;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;


/**
 * This class use to handle rest api requests for "admin-update-team-captain" endpoint.
 *
 * @since 1.4.0
 */
class UpdateTeamRoute extends Endpoint
{
    /**
     * @var string
     */
    protected $endpoint = 'admin-update-team';

    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @since 1.4.0
     */

    /**
     * @var FundraiserRepository
     */
    private $fundraiserRepository;

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
                        'name' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Team name', 'give-peer-to-peer'),
                        ],
                        'story' => [
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_html__('Team story', 'give-peer-to-peer'),
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

        if (!$team = Team::getTeam($request->get_param('teamId'))) {
            return new WP_REST_Response(
                [
                    'status' => 'invalid_campaign',
                    'message' => __('Campaign you are trying to join does not exist', 'give-peer-to-peer'),
                ],
                404
            );
        }

        $goal = Money::fromDecimal(
            $request->get_param('goal'),
            give_get_option('currency')
        );

        $team = \GiveP2P\P2P\Models\Team::fromArray(
            array_merge(
                $team->toArray(),
                [
                    'campaign_id'               => $campaign->getId(),
                    'name'                      => $request->get_param('name'),
                    'story'                     => $request->get_param('story'),
                    'goal'                      => $goal->formatToMinorAmount(),
                    'access'                    => $request->get_param('access'),
                    'status'                    => Status::ACTIVE,
                    'notify_of_fundraisers'     => $request->get_param('notify_of_fundraisers'),
                    'notify_of_team_donations'  => $request->get_param('notify_of_team_donations')
                ]
            )
        );

        // Handle file upload
        if ($request->get_param('file_url')) {
            $team->set('profile_image', $request->get_param('file_url'));
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
                $team->set('profile_image', $image);
            }
        }

        $wpdb->query('START TRANSACTION');

        if (!$team->save()) {
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

        return new WP_REST_Response(['teamId' => $team->getId()]);
    }
}
