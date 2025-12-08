<?php

namespace TotalPoll\Restrictions;
! defined( 'ABSPATH' ) && exit();


/**
 * Authenticated User Restriction.
 * @package TotalPoll\Restrictions
 */
class LoggedInUser extends Restriction {
	use \TotalPollVendors\TotalCore\Traits\Cookies;

	/**
	 * @return bool|\WP_Error
	 */
	public function check() {
		

		
		// If a specific cookie exists then we can waive DB check
		$cookieName  = $this->getCookieName( 'user' );
		$cookieValue = absint( $this->getCookie( $cookieName ) );
		$result      = ! ( $cookieValue >= $this->getVotesPerUser() );

		if ( ( $this->isFullCheck() || $result ) && is_user_logged_in() ):

			$timeout    = (int) $this->getTimeout();
			$conditions = [
				'poll_id' => $this->getPollId(),
				'action'  => $this->getAction(),
				'status'  => 'accepted',
				'user_id' => get_current_user_id(),
				'date'    => [],
			];

			if ( $timeout !== 0 ):
				$date                 = TotalPoll( 'datetime', [ "-{$timeout} minutes" ] );
				$conditions['date'][] = [ 'operator' => '>', 'value' => $date->format( 'Y/m/d H:i:s' ) ];
			endif;

			$count = TotalPoll( 'log.repository' )->count( [
				'conditions' => $conditions,
			] );

			$result = $count < $this->getVotesPerUser();

		endif;

		return $result ?: new \WP_Error( 'loggedin_user', empty( $this->args['message'] ) ? esc_html__( 'You cannot vote again.', 'totalpoll' ) : (string) $this->args['message'] );
		
	}

	/**
	 * Apply Restriction.
	 */
	public function apply() {
		
		$cookieTimeout = $this->getTimeout();
		$cookieName    = $this->getCookieName( 'user' );
		$cookieValue   = absint( $this->getCookie( $cookieName, 0 ) );
		$this->setCookie( $cookieName, $cookieValue + 1, $cookieTimeout );
		
	}
}
