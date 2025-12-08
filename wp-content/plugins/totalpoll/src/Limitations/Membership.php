<?php

namespace TotalPoll\Limitations;
! defined( 'ABSPATH' ) && exit();



use TotalPollVendors\TotalCore\Limitations\Limitation;

/**
 * Membership Limitation.
 * @package TotalPoll\Limitations
 */
class Membership extends Limitation {
	/**
	 * Limitation check logic.
	 *
	 * @return bool|\WP_Error
	 */
	public function check() {
		
		$roles = empty( $this->args['roles'] ) ? [] : (array) $this->args['roles'];

		if ( is_user_logged_in() ):
			if ( ! empty( $roles ) && count( array_intersect( $GLOBALS['current_user']->roles, $roles ) ) === 0 ):
				$labels = wp_list_pluck( wp_roles()->roles, 'name' );

				$roles = implode(
					', ',
					array_map(
						static function ( $role ) use ( $labels ) {
							return translate_user_role( empty( $labels[ $role ] ) ? 'unknown' : $labels[ $role ] );
						},
						$roles
					)
				);

				return new \WP_Error(
					'membership_type',
					sprintf(
						esc_html__( 'To continue, you must be a part of these roles: %s.', 'totalpoll' ),
						$roles
					)
				);

			endif;
		else:
			return new \WP_Error(
				'logged_in',
				wp_kses(
					sprintf(
						__( 'You cannot vote because you are a guest, please <a href="%s">sign in</a> or <a href="%s">register</a>.', 'totalpoll' ),
						wp_login_url( home_url( add_query_arg( null, null ) ) ),
						wp_registration_url()
					),
					[ 'a' => [ 'href' => [] ] ] )
			);
		endif;

		

		return true;
	}
}
