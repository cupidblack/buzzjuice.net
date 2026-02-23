<?php

namespace GiveP2P\P2P\Helpers;

use DateInterval;
use DateTime;
use Exception;

/**
 * Output of
 */
class RelativeDateHelper {

	/**
	 * @var DateInterval|false
	 */
	protected $interval;

	/**
	 * RelativeDateHelper constructor.
	 * @param string $relative
	 * @param string|null $reference
	 * @throws Exception
	 */
	public function __construct( $relative, $reference = null ) {
		$this->interval = (new DateTime($reference))->diff(new DateTime($relative));
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function days( $today = 'today', $yesterday = 'yesterday', $daysAgoFormat = '%d days ago' ) {
		switch( $days = $this->interval->format('%a') ) {
			case 0:
				return $today;
			case 1:
				return $yesterday;
			default:
				return sprintf( $daysAgoFormat, $days );
		}
	}
}
