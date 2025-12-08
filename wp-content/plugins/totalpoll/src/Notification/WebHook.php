<?php

namespace TotalPoll\Notification;
! defined( 'ABSPATH' ) && exit();



/**
 * WebHook Notification Model
 * @package TotalPoll\Notification
 * @since   1.1.0
 */
class WebHook extends Model {
	public function send() {
		
		wp_remote_post( $this->getTo(), [
			'user-agent' => $this->getFrom(),
			'blocking'   => false,
			'sslverify'  => false,
			'body'       => $this->getBody(),
		] );
		
	}
}
