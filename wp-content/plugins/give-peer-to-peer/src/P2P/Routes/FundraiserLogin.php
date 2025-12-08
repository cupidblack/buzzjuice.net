<?php

namespace GiveP2P\P2P\Routes;

use WP_REST_Request;
use WP_REST_Response;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;

class FundraiserLogin extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'fundraiser-login';

	/**
	 * @var CampaignRepository
	 */
	private $campaignRepository;

	/**
	 * @var FundraiserRepository
	 */
	private $fundraiserRepository;

	/**
	 * FundraiserLogin constructor.
	 *
	 * @param  CampaignRepository  $campaignRepository
	 * @param  FundraiserRepository  $fundraiserRepository
	 */
	public function __construct(
		CampaignRepository $campaignRepository,
		FundraiserRepository $fundraiserRepository
	) {
		$this->campaignRepository   = $campaignRepository;
		$this->fundraiserRepository = $fundraiserRepository;
	}

	/**
	 * @inheritDoc
	 */
	public function registerRoute() {
		register_rest_route(
			parent::ROUTE_NAMESPACE,
			$this->endpoint,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'handleRequest' ],
					'permission_callback' => '',
					'args'                => [
						'campaign_id' => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
						],
						'user_handle' => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Email or Username', 'give-peer-to-peer' ),
							'sanitize_callback' => function ( $param ) {
								return filter_var( $param, FILTER_SANITIZE_STRING );
							},
						],
						'password'    => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Password', 'give-peer-to-peer' ),
							'sanitize_callback' => function ( $param ) {
								return filter_var( $param, FILTER_SANITIZE_STRING );
							},
						],
					],
				],
			]
		);
	}


	/**
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function handleRequest( WP_REST_Request $request ) {
		$handle   = $request->get_param( 'user_handle' );
		$password = $request->get_param( 'password' );

		$campaign = $this->campaignRepository->getCampaign( $request->get_param( 'campaign_id' ) );

		if ( ! $campaign ) {
			return new WP_REST_Response(
				[
					'message' => __( 'Campaign you are trying to login does not exist', 'give-peer-to-peer' ),
				],
				404
			);
		}

		$user = filter_var( $handle, FILTER_VALIDATE_EMAIL )
			? get_user_by( 'email', $handle )
			: get_user_by( 'login', $handle );

		if ( $user && wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {

            // Authenticate the user
            wp_set_auth_cookie( $user->ID );

			if ( ! $fundraiserId = $this->fundraiserRepository->getFundraiserIdByUserIdAndCampaignId( $user->ID, $campaign->getId() ) ) {
                return new WP_REST_Response(
                    [
                        'redirect' => home_url(
                            sprintf( 'campaign/%s/register/', $campaign->getUrl() )
                        ),
                    ]
                );
			}

			$fundraiser = $this->fundraiserRepository->getFundraiser( $fundraiserId );

			if (
				$fundraiser->isTeamCaptain()
				&& ! $this->fundraiserRepository->fundraiserHasTeam( $fundraiser->getId() )
			) {
				return new WP_REST_Response(
					[
						'redirect' => home_url(
							sprintf( 'campaign/%s/create-team', $campaign->getUrl() )
						),
					]
				);
			}

			return new WP_REST_Response(
				[
					'redirect' => home_url(
						sprintf( 'campaign/%s/fundraiser/%d/', $campaign->getUrl(), $fundraiser->getId() )
					),
				]
			);
		}

		return new WP_REST_Response(
			[
				'message' => __( 'Invalid username or password', 'give-peer-to-peer' ),
			],
			403
		);
	}

}
