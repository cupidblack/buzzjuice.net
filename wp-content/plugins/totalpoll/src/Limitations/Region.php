<?php

namespace TotalPoll\Limitations;
! defined( 'ABSPATH' ) && exit();



use TotalPollVendors\TotalCore\Limitations\Limitation;

/**
 * Region Limitation
 * @package TotalPoll\Limitations
 */
class Region extends Limitation {
	/**
	 * Limitation check logic.
	 *
	 * @return bool|\WP_Error
	 */
	public function check() {
		
		foreach ( (array) $this->args['rules'] as $rule ):
			$ip = str_replace( ' ', '', empty( $rule['ip'] ) ? '' : $rule['ip'] );
			if ( empty( $ip ) ):
				continue;
			endif;

			$regexp = str_replace( '\*', '.+', preg_quote( $ip, '/' ) );
			$result = (bool) preg_match( "/{$regexp}/i", $this->args['ip'] );

			if ( ( $result && $rule['type'] === 'deny' ) || ( ! $result && $rule['type'] === 'allow' ) ):
				return new \WP_Error( 'region', esc_html__( 'This poll is not available in your region.', 'totalpoll' ) );
			endif;
		endforeach;

		

		return true;
	}
}
