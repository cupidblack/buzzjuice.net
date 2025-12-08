<?php

namespace GiveP2P\P2P\Routes;

use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.2.0
 */
class GetUserInfoRoute extends Endpoint {

    /**
     * @var string
     */
    protected $endpoint = 'get-user-info';

    /**
     * @inheritDoc
     */
    public function registerRoute() {
        register_rest_route(
            parent::ROUTE_NAMESPACE,
            $this->endpoint,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'handleRequest' ],
                    'permission_callback' => 'is_user_logged_in',
                ],
            ]
        );
    }

    /**     *
     * @return WP_REST_Response
     */
    public function handleRequest() {
        if ( $user = wp_get_current_user() ) {
            return new WP_REST_Response( [
                'data' => [
                    'firstName' => $user->user_firstname,
                    'lastName'  => $user->user_lastname,
                    'email'     => $user->user_email,
                ]
            ] );
        }

        return new WP_REST_Response(
            [
                'error'   => 'not_found',
                'message' => __( 'User not found', 'give-peer-to-peer' ),
            ],
            404
        );
    }

}
