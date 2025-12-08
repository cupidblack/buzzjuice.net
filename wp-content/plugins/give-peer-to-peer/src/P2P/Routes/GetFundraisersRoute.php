<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use GiveP2P\P2P\ValueObjects\Status;
use GiveP2P\P2P\ViewModels\FundraiserViewModel;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class GetTeamFundraisersRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since   1.0.0
 */
class GetFundraisersRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'get-team-fundraisers';
    /**
     * @var TeamRepository
     */
    private $teamRepository;
    /**
     * @var FundraiserRepository
     */
    private $fundraiserRepository;

    /**
     * @param TeamRepository       $teamRepository
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
                        'team_id' => [
                            'validate_callback' => function ($param) {
                                if (empty($param) || ('all' === $param)) {
                                    return true;
                                }

                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                            'default' => null,
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

                                return in_array($param, FundraiserRepository::SORTABLE_COLUMNS, true);
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
            'title' => 'fundraisers',
            'type' => 'object',
            'properties' => [
                'campaign_id' => [
                    'type' => 'integer',
                    'description' => esc_html__('Campaign ID', 'give-peer-to-peer'),
                    'required' => true,
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => esc_html__('Team ID', 'give-peer-to-peer'),
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
                    'description' => esc_html__('Fundraiser status', 'give-peer-to-peer'),
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
        $teams = [];

        $fundraisers = $this->fundraiserRepository->getFundraisersForRequest($request);
        $totalFundraisers = $this->fundraiserRepository->getTotalFundraisersCountForRequest($request);
        $campaignTeams = $this->teamRepository->getCampaignTeams($request->get_param('campaign_id'));

        foreach ($fundraisers as $fundraiser) {
            // Get WP user data
            $user = get_userdata($fundraiser->get('user_id'));
            $fundraiserViewModel = new FundraiserViewModel($fundraiser, $this->fundraiserRepository, $user);

            $fundraiserData = $fundraiserViewModel->exports();
            if ( ! isset($fundraiserData['profile_image']) || ! $fundraiserData['profile_image']) {
                $fundraiserData['profile_image'] = get_avatar_url($fundraiser->get('user_id'));
            }

            $data[] = $fundraiserData;
        }

        $data = array_map(function ($data) {
            return array_merge($data, [
                'amount_raised' => Money::ofMinor($data['amount_raised'], give_get_option('currency'))->getAmount(),
            ]);
        }, $data);

        foreach ($campaignTeams as $team) {
            $teams[$team->get('id')] = $team->get('name');
        }

        return new WP_REST_Response(
            [
                'status' => true,
                'data' => $data,
                'pages' => ceil($totalFundraisers / $request->get_param('per_page')),
                'total' => $totalFundraisers,
                'statuses' =>
                    [
                        Status::ACTIVE => esc_html__('Approved', 'give-peer-to-peer'),
                        Status::PENDING => esc_html__('Pending Approval', 'give-peer-to-peer'),
                    ],
                'teams' => $teams,
            ]
        );
    }
}
