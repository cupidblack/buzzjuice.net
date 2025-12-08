<?php

namespace TotalPoll\Contracts\Migrations\Poll\Template;
! defined( 'ABSPATH' ) && exit();


/**
 * Interface Options
 * @package TotalPoll\Contracts\Migrations\Poll\Template
 */
interface Options extends Template {
	/**
	 * @param $section
	 * @param $value
	 *
	 * @return mixed
	 */
	public function addOption( $section, $value );
}