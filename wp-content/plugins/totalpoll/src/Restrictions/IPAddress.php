<?php

namespace TotalPoll\Restrictions;
! defined( 'ABSPATH' ) && exit();


/**
 * IP Restriction.
 * @package TotalPoll\Restrictions
 */
class IPAddress extends Restriction {
	use \TotalPollVendors\TotalCore\Traits\Cookies;

	/**
	 * @return bool|\WP_Error
	 */
	public function check() {
		

		
		// If a specific cookie exists then we can waive DB check
		$cookieName  = $this->getCookieName( 'ip' );
		$cookieValue = absint( $this->getCookie( $cookieName ) );
		$result      = ! ( $cookieValue >= $this->getVotesPerIP() );

		if ( $this->isFullCheck() || $result ):
			$conditions = [
				'poll_id' => $this->getPollId(),
				'action'  => $this->getAction(),
				'status'  => 'accepted',
				'ip'      => (string) TotalPoll( 'http.request' )->ip(),
				'date'    => [],
			];

			$timeout = $this->getTimeout();
			if ( $timeout !== 0 ):
				$date                 = TotalPoll( 'datetime', [ "-{$timeout} minutes" ] );
				$conditions['date'][] = [ 'operator' => '>', 'value' => $date->format( 'Y/m/d H:i:s' ) ];
			endif;

			$count  = TotalPoll( 'log.repository' )->count( [ 'conditions' => $conditions ] );
			$result = $count < $this->getVotesPerIP();

			if ( ! $result ):
				$this->setCookie( $cookieName, (int) $this->getVotesPerIP(), $timeout );
			endif;
		endif;

		return $result ?: new \WP_Error( 'ip', $this->getMessage() ?: esc_html__( 'You cannot vote again.', 'totalpoll' ) );
		
	}

	/**
	 * Apply restriction.
	 */
	public function apply() {
		
		$cookieTimeout = $this->getTimeout();
		$cookieName    = $this->getCookieName( 'ip' );
		$cookieValue   = absint( $this->getCookie( $cookieName, 0 ) );
		$this->setCookie( $cookieName, $cookieValue + 1, $cookieTimeout );
		
	}
}
