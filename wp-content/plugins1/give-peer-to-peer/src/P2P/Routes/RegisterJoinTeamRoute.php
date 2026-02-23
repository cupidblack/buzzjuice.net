<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Models\Fundraiser;
use GiveP2P\P2P\ValueObjects\Status;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.2.0
 */
class RegisterJoinTeamRoute extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'register-join-team';

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
                    'permission_callback' => '__return_true',
					'args'                => [
						'campaignId'     => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Campaign ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
						],
						'teamId'     => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => esc_html__( 'Team ID', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_INT );
							},
						],
						'email'     => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Fundraiser email address', 'give-peer-to-peer' ),
							'validate_callback' => function ( $param ) {
								return filter_var( $param, FILTER_VALIDATE_EMAIL );
							},
						],
						'firstName'     => [
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
							'validate_callback' => function ( $param ) {
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
		// Check Campaign
		if ( ! $campaign = Campaign::getCampaign( $request->get_param( 'campaignId' ) ) ) {
			return new WP_REST_Response(
				[
					'status'  => 'invalid_campaign',
					'message' => __( 'Campaign you are trying to join does not exist', 'give-peer-to-peer' ),
				],
				404
			);
		}

		$userID = wp_insert_user(
			[
				'user_pass'  => $request->get_param( 'password' ),
				'user_login' => $request->get_param( 'email' ),
				'user_email' => $request->get_param( 'email' ),
				'first_name' => $request->get_param( 'firstName' ),
				'last_name'  => $request->get_param( 'lastName' ),
			]
		);

		if ( is_wp_error( $userID ) ) {
			return new WP_REST_Response(
				[
					'error'   => 'registration_failed',
					'message' => $userID->get_error_message()
				],
				400
			);
		}

        $fundraiser = new Fundraiser();
        $fundraiser->set('campaign_id', $campaign->getId());
        $fundraiser->set('team_id', $request->get_param( 'teamId' ));
        $fundraiser->set('user_id', $userID);
        $fundraiser->set('status', $campaign->doesRequireFundraiserApproval() ? Status::PENDING : Status::ACTIVE);
        $fundraiser->set('goal', 0);
        $fundraiser->save();

		$user = wp_signon(
			[
				'user_login' => $request->get_param('email'),
				'user_password' => $request->get_param('password'),
				'remember' => true,
			]
		);

		if ( is_wp_error( $user ) ) {
			return new WP_REST_Response(
				[
					'error'   => 'update_failed',
					'message' => current_user_can( 'manage_options' )
						? $user->get_error_message()
						: __( 'Something went wrong', 'give-peer-to-peer' ),
				],
				400
			);
		}

		return new WP_REST_Response(
			[
				'redirect' => '/register/create-profile/',
			]
		);
	}

}
