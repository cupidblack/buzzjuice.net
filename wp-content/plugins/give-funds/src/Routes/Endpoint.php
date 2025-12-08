<?php

namespace GiveFunds\Routes;

use Give\API\RestRoute;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Endpoint
 * @package GiveFunds\Routes
 */
abstract class Endpoint implements RestRoute {

	/**
	 * @param string $param
	 * @param WP_REST_Request $request
	 * @param string $key
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function validateDate( $param, $request, $key ) {
		// Check that date is valid, and formatted YYYY-MM-DD
		$exploded = explode( '-', $param );
		$valid    = checkdate( $exploded[1], $exploded[2], $exploded[0] );

		// If checking end date, check that it is after start date
		if ( 'end' === $key ) {
			$start = date_create( $request->get_param( 'start' ) );
			$end   = date_create( $request->get_param( 'end' ) );
			$valid = $start <= $end ? $valid : false;
		}

		return $valid;
	}

	/**
	 * @param string $param
	 * @param WP_REST_Request $request
	 * @param string $key
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function sanitizeDate( $param, $request, $key ) {
		// Return Date object from parameter
		$exploded = explode( '-', $param );

		return "{$exploded[0]}-{$exploded[1]}-{$exploded[2]} 24:00:00";
	}

	/**
	 * @param string $param
	 * @param WP_REST_Request $request
	 * @param string $key
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function validateCurrency( $param, $request, $key ) {
		return in_array( $param, array_keys( give_get_currencies_list() ), true );
	}

	/**
	 * @param string $param
	 * @param WP_REST_Request $request
	 * @param string $key
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function sanitizeFundId( $param, $request, $key ) {
		return filter_var( $param, FILTER_VALIDATE_INT );
	}

	/**
	 * Check permissions
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function permissionsCheck( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot view the reports resource.', 'give-funds' ),
				[ 'status' => $this->authorizationStatusCode() ]
			);
		}

		return true;
	}

	// Sets up the proper HTTP status code for authorization.
	public function authorizationStatusCode() {

		$status = 401;
		if ( is_user_logged_in() ) {
			$status = 403;
		}

		return $status;

	}
}
