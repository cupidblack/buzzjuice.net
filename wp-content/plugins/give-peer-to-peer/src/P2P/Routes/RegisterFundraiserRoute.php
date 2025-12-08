<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\Repositories\CampaignRepository;
use GiveP2P\P2P\Repositories\FundraiserRepository;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since unreleased
 */
class RegisterFundraiserRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'register-fundraiser';

	/**
	 * @var Fundraiser
	 */
	private $fundraiser;

	/**
	 * @var FundraiserRepository
	 */
	private $fundraiserRepository;

	/**
	 * @var CampaignRepository
	 */
	private $campaignRepository;

	/**
	 * @param  Fundraiser  $fundraiser
	 * @param  FundraiserRepository  $fundraiserRepository
	 * @param  CampaignRepository  $campaignRepository
	 */
	public function __construct(
		Fundraiser $fundraiser,
		FundraiserRepository $fundraiserRepository,
		CampaignRepository $campaignRepository
	) {
		$this->fundraiser           = $fundraiser;
		$this->fundraiserRepository = $fundraiserRepository;
		$this->campaignRepository   = $campaignRepository;
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
					'methods'  => 'POST',
					'callback' => [ $this, 'handleRequest' ],
                    'permission_callback' => '__return_true',
					'args'     => [
						'campaign_id'  => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
						],
						'team_captain' => [
							'required'          => true,
							'type'              => 'bool',
							'description'       => esc_html__( 'Fundraiser is a team captain', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return is_bool( $param );
							},
						],
						'email'        => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Fundraiser email address', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_EMAIL );
							},
							'sanitize_callback' => function ( $param ) {
								return filter_var( $param, FILTER_SANITIZE_EMAIL );
							},
						],
						'firstName'    => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Fundraiser first name', 'give-peer-to-peer' ),
							'sanitize_callback' => function ( $param ) {
								return filter_var( $param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
							},
						],
						'lastName'     => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Fundraiser last name', 'give-peer-to-peer' ),
							'sanitize_callback' => function ( $param ) {
								return filter_var( $param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
							},
						],
						'password'     => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Fundraiser account password', 'give-peer-to-peer' ),
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

        global $wpdb;

        // Check Campaign
        if ( ! $campaign = $this->campaignRepository->getCampaign( $request->get_param( 'campaign_id' ) ) ) {
            return new WP_REST_Response(
                [
                    'status'  => 'invalid_campaign',
                    'message' => __( 'Campaign you are trying to join does not exist', 'give-peer-to-peer' ),
                ],
                404
            );
        }

		// Check if user already has WP account
		if ( $user = get_user_by( 'email', $request->get_param( 'email' ) ) ) {
            return new WP_REST_Response(
                [
                    'status'  => 'user_account_exist',
                    'message' => __( 'An account already exists for that email address.', 'give-peer-to-peer' ),
                ],
                403
            );
		}

		$wpdb->query( 'START TRANSACTION' );

		$newUser = wp_insert_user(
			[
				'user_pass'  => $request->get_param( 'password' ),
				'user_login' => $request->get_param( 'email' ),
				'user_email' => $request->get_param( 'email' ),
				'first_name' => $request->get_param( 'firstName' ),
				'last_name'  => $request->get_param( 'lastName' ),
			]
		);

		if ( is_wp_error( $newUser ) ) {
			$wpdb->query( 'ROLLBACK' );

			return new WP_REST_Response(
				[
					'error'   => 'insert_user_failed',
					'message' => current_user_can( 'manage_options' )
						? $newUser->get_error_message()
						: __( 'A user with that email address already exists.', 'give-peer-to-peer' ),
				],
				400
			);
		}

		$this->fundraiser->set( 'campaign_id', $campaign->getId() );
		$this->fundraiser->set( 'user_id', $newUser );
		$this->fundraiser->set( 'status', ( $campaign->doesRequireFundraiserApproval() ) ? Status::PENDING : Status::ACTIVE );
		$this->fundraiser->set( 'team_captain', $request->get_param( 'team_captain' ) );

		if ( $this->fundraiser->save() ) {
			$wpdb->query( 'COMMIT' );
			// Authenticate the user
			wp_set_auth_cookie( $newUser );

			$endpoint = $request->get_param( 'team_captain' )
				? '/create-team'
				: '/register/create-profile/';

			return new WP_REST_Response(
				[
					'redirect' => $endpoint,
				]
			);

		} else {
			$wpdb->query( 'ROLLBACK' );

			return new WP_REST_Response(
				[
					'error'   => 'registration_failed',
					'message' => __( 'Something went wrong', 'give-peer-to-peer' ),
				],
				400
			);
		}
	}

}
