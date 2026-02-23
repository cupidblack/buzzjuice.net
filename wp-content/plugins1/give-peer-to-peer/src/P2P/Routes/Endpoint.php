<?php

namespace GiveP2P\P2P\Routes;

use Give\API\RestRoute;
use WP_Error;

/**
 * Class Endpoint
 * @package GiveP2P\Addon\API
 *
 * @since 1.0.0
 */
abstract class Endpoint implements RestRoute {
	/**
	 * Route namespace
	 */
	const ROUTE_NAMESPACE = 'give-api/v2/p2p-campaigns';

	/**
	 * @var string
	 */
	protected $endpoint;

	/**
	 * Check user permissions
	 * @return bool|WP_Error
	 */
	public function permissionsCheck() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You dont have the right permissions', 'give-peer-to-peer' ),
				[ 'status' => $this->authorizationStatusCode() ]
			);
		}

		return true;
	}

	// Sets up the proper HTTP status code for authorization.
	public function authorizationStatusCode() {
		if ( is_user_logged_in() ) {
			return 403;
		}

		return 401;
	}
}
