<?php

namespace GiveP2P\P2P\Routes;

use GiveP2P\P2P\Repositories\CampaignRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * Class CreateTeamRoute
 * @package GiveP2P\P2P\Routes
 *
 * @since 1.0.0
 */
class SendPasswordResetEmail extends Endpoint {

	/**
	 * @var string
	 */
	protected $endpoint = 'send-password-reset-email';

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
						'user_handle' => [
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Username for which a password retrieval email should be sent.', 'give-peer-to-peer' ),
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

		retrieve_password( $request->get_param( 'user_handle' ) );

		return new WP_REST_Response();
	}

}
