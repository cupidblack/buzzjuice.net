<?php

namespace TotalPoll\Limitations;
! defined( 'ABSPATH' ) && exit();



use TotalPollVendors\TotalCore\Limitations\Limitation;

/**
 * Quota Limitation
 * @package TotalPoll\Limitations
 */
class Quota extends Limitation {
	/**
	 * Limitation check logic.
	 *
	 * @return bool|\WP_Error
	 */
	public function check() {
		
		$quota        = isset( $this->args['value'] ) ? (int) $this->args['value'] : false;
		$currentValue = isset( $this->args['currentValue'] ) ? (int) $this->args['currentValue'] : false;
		if ( $quota && $quota > 0 && $quota <= $currentValue ):
			return new \WP_Error( 'quota', esc_html__( 'This poll has ended (votes quota has been exceeded).', 'totalpoll' ) );
		endif;
		

		return true;
	}
}
