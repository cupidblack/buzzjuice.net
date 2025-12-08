<?php

namespace TotalPoll\Notification;
! defined( 'ABSPATH' ) && exit();


/**
 * Mail Notification Model.
 * @package TotalPoll\Notification
 */
class Mail extends Model {
	public function send() {
		
		return wp_mail( $this->getTo(), $this->getSubject(), $this->getBody(), [ 'Content-Type: text/html; charset=UTF-8' ] );
		
	}
}
