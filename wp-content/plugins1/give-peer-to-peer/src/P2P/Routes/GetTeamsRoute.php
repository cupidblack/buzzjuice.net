<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class GetTeamsRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetTeamsRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'get-teams';
    /**
     * @var TeamRepository
     */
    private $teamRepository;

    /**
     * @var FundraiserRepository
     */
    private $fundraiserRepository;

    /**
     * @param TeamRepository $teamRepository
     * @param FundraiserRepository $fundraiserRepository
     */
    public function __construct(TeamRepository $teamRepository, FundraiserRepository $fundraiserRepository)
    {
        $this->teamRepository = $teamRepository;
        $this->fundraiserRepository = $fundraiserRepository;
    }

    /**
     * @inheritDoc
     * @since 1.4.0 Update 'sort' default value to 'date_created'
     */
    public function registerRoute()
    {
        register_rest_route(
            parent::ROUTE_NAMESPACE,
            $this->endpoint,
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'handleRequest'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                    'args' => [
                        'campaign_id' => [
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                        ],
                        'page' => [
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                            'default' => 1,
                        ],
                        'per_page' => [
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                            'default' => 10,
                        ],
                        'status' => [
                            'validate_callback' => function ($param) {
                                if (empty($param) || ('all' === $param)) {
                                    return true;
                                }

                                return Status::isValid($param);
                            },
                            'default' => 'all',
                        ],
                        'sort' => [
                            'validate_callback' => function ($param) {
                                if (empty($param)) {
                                    return true;
                                }

                                return in_array($param, TeamRepository::SORTABLE_COLUMNS, true);
                            },
                            'default' => 'date_created',
                        ],
                        'direction' => [
                            'validate_callback' => function ($param) {
                                if (empty($param)) {
                                    return true;
                                }

                                return in_array(strtoupper($param), ['ASC', 'DESC'], true);
                            },
                            'default' => 'DESC',
                        ],
                    ],
                ],
                'schema' => [$this, 'getSchema'],
            ]
        );
    }

    /**
     * @return array
     */
    public function getSchema()
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'teams',
            'type' => 'object',
            'properties' => [
                'campaign_id' => [
                    'type' => 'integer',
                    'description' => esc_html__('Campaign ID', 'give-peer-to-peer'),
                    'required' => true,
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => esc_html__('Current page', 'give-peer-to-peer'),
                ],
                'per_page' => [
                    'type' => 'integer',
                    'description' => esc_html__('Items per page', 'give-peer-to-peer'),
                ],
                'status' => [
                    'type' => 'string',
                    'description' => esc_html__('Team status', 'give-peer-to-peer'),
                ],
                'direction' => [
                    'type' => 'string',
                    'description' => esc_html__('Sort direction', 'give-peer-to-peer'),
                ],
                'sort' => [
                    'type' => 'string',
                    'description' => esc_html__('Sort by column', 'give-peer-to-peer'),
                ],
            ],
        ];
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest(WP_REST_Request $request)
    {
        $data = [];

        $teams = $this->teamRepository->getCampaignTeamsForRequest($request);
        $totalTeams = $this->teamRepository->getCampaignTeamsCountForRequest($request);

        foreach ($teams as $team) {
            $captains = [];
            $teamCaptains = $this->fundraiserRepository->getTeamCaptains($team->get('id'));

            foreach ($teamCaptains as $captain) {
                // Get WP user data
                $user = get_userdata($captain->get('user_id'));

                $captains[] = [
                    'id' => $captain->get('id'),
                    'user_id' => $captain->get('user_id'),
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url($captain->get('user_id')),
                ];
            }

            $data[] = [
                'team_id' => $team->get('id'),
                'team_name' => $team->get('name'),
                'team_goal' => $team->get('goal'),
                'profile_image' => $team->get('profile_image'),
                'date_created' => $team->get('date_created'),
                'team_status' => $team->hasApprovalStatus(),
                'team_captains' => $captains,
                'fundraisers_total' => $this->teamRepository->getFundraisersCount($team->get('id')),
                'fundraisers_pending' => $this->fundraiserRepository->getTeamFundraisersCountByStatus(
                    $team->get('id'),
                    Status::PENDING
                ),
                'amount_raised' => $this->teamRepository->getRaisedAmount($team->get('id')),
            ];
        }

        $data = array_map(function ($data) {
            return array_merge($data, [
                'team_goal' => Money::ofMinor($data['team_goal'], give_get_option('currency'))->getAmount(),
                'amount_raised' => Money::ofMinor($data['amount_raised'], give_get_option('currency'))->getAmount(),
            ]);
        }, $data);

        return new WP_REST_Response(
            [
                'status' => true,
                'data' => $data,
                'pages' => ceil($totalTeams / $request->get_param('per_page')),
                'total' => $totalTeams,
                'statuses' =>
                    [
                        Status::ACTIVE => esc_html__('Approved', 'give-peer-to-peer'),
                        Status::PENDING => esc_html__('Pending Approval', 'give-peer-to-peer'),
                    ],
            ]
        );
    }
}
