<?php

namespace GiveP2P\P2P\Routes;

use Give\ValueObjects\Money;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\Repositories\TeamRepository;
use GiveP2P\P2P\ValueObjects\Status;
use GiveP2P\P2P\ViewModels\CampaignViewModel;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class GetCampaignsRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class GetCampaignsRoute extends Endpoint
{

    /**
     * @var string
     */
    protected $endpoint = 'get-campaigns';
    /**
     * @var CampaignRepository
     */
    protected $campaignRepository;
    /**
     * @var TeamRepository
     */
    protected $teamRepository;
    /**
     * @var FundraiserRepository
     */
    protected $fundraiserRepository;

    /**
     * @param CampaignRepository $campaignRepository
     * @param TeamRepository $teamRepository
     * @param FundraiserRepository $fundraiserRepository
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        TeamRepository $teamRepository,
        FundraiserRepository $fundraiserRepository
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->teamRepository = $teamRepository;
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
                    'methods' => 'GET',
                    'callback' => [$this, 'handleRequest'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'page' => [
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                            'default' => 1,
                            'description' => esc_html__('Current page', 'give-peer-to-peer'),
                        ],
                        'per_page' => [
                            'validate_callback' => function ($param) {
                                return filter_var($param, FILTER_VALIDATE_INT);
                            },
                            'default' => 10,
                            'description' => esc_html__('Items per page', 'give-peer-to-peer'),
                        ],
                        'status' => [
                            'validate_callback' => function ($param) {
                                if (empty($param) || ('all' === $param)) {
                                    return true;
                                }

                                return Status::isValid($param);
                            },
                            'default' => 'all',
                            'description' => esc_html__('Campaign status', 'give-peer-to-peer'),
                        ],
                        'sort' => [
                            'default' => 'id',
                            'description' => esc_html__('Sort by column', 'give-peer-to-peer'),
                        ],
                        'direction' => [
                            'validate_callback' => function ($param) {
                                if (empty($param)) {
                                    return true;
                                }

                                return in_array(strtoupper($param), ['ASC', 'DESC'], true);
                            },
                            'default' => 'DESC',
                            'description' => esc_html__('Sort direction', 'give-peer-to-peer'),
                        ],
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
        $data = [];

        $campaigns = $this->campaignRepository->getCampaignsForRequest($request);
        $totalCampaigns = $this->campaignRepository->getCampaignsCountForRequest($request);

        foreach ($campaigns as $campaign) {
            $campaignViewModel = new CampaignViewModel(
                $campaign,
                $this->campaignRepository,
                $this->teamRepository,
                $this->fundraiserRepository
            );

            $data[] = $campaignViewModel->exports();
        }

        $data = array_map(function ($data) {
            return array_merge($data, [
                'campaign_url' => home_url('campaign/' . $data['campaign_url']),
                'campaign_goal' => Money::ofMinor($data['campaign_goal'], give_get_option('currency'))->getAmount(),
                'campaign_amount_raised' => Money::ofMinor(
                    $data['campaign_amount_raised'],
                    give_get_option('currency')
                )->getAmount() ?: 0,
            ]);
        }, $data);

        return new WP_REST_Response(
            [
                'status' => true,
                'data' => $data,
                'pages' => ceil($totalCampaigns / $request->get_param('per_page')),
                'total' => $totalCampaigns,
                'statuses' =>
                    [
                        Status::ACTIVE => esc_html__('Active', 'give-peer-to-peer'),
                        Status::INACTIVE => esc_html__('Inactive', 'give-peer-to-peer'),
                        Status::DRAFT => esc_html__('Draft', 'give-peer-to-peer'),
                    ],
            ]
        );
    }
}
