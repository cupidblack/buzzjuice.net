<?php

/**
 * Notification Controller: myCred Points Notification
 */

defined('ABSPATH') || exit;

/**
 * Notification Controller: myCred Points Notification
 */

class LLMS_Notification_Controller_Mycred_Points extends LLMS_Abstract_Notification_Controller {

	/**
	 * Trigger Identifier
	 *
	 * @var  [type]
	 */

	public $id = 'mycred_points';

	/**
	 * Number of accepted arguments passed to the callback function
	 *
	 * @var  integer
	 */

	protected $action_accepted_args = 3;

	/**
	 * Action hooks used to trigger sending of the notification
	 *
	 * @var  array
	 */

	protected $action_hooks = array( 'mycred_points_llms_notification' );

	/**
	 * Callback function, called upon achievement post generation
	 *
	 * @param    int $id        myCred Log ID
	 * @param    int $log_data  myCred Log Data
	 * @param    int $obj       myCred Settings object
	 * @return   void
	 */

	public function action_callback( $id = null, $log_data = null, $obj = null ) {

		$this->user_id = $log_data['user_id'];
		$this->post_id = intval($id) * -1;

		$this->send();
	}

	/**
	 * Takes a subscriber type (student, author, etc) and retrieves a User ID
	 *
	 * @param    string $subscriber  subscriber type string
	 * @return   int|false
	 */

	protected function get_subscriber( $subscriber ) {

		switch ($subscriber) {

			case 'student':
				$uid = $this->user_id;
				break;

			default:
				$uid = false;

		}

		return $uid;
	}

	/**
	 * Get the translatable title for the notification
	 * used on settings screens
	 *
	 * @return   string
	 */

	public function get_title() {
		return __( 'myCred Points Notification', 'mycred-toolkit' );
	}

	/**
	 * Setup the subscriber options for the notification
	 *
	 * @param    string $type  notification type id
	 * @return   array
	 */

	protected function set_subscriber_options( $type ) {

		$options = array();

		switch ( $type ) {

			case 'basic':
				$options[] = $this->get_subscriber_option_array('student', 'yes');
				break;

		}

		return $options;
	}

	/**
	 * Determine what types are supported
	 * Extending classes can override this function in order to add or remove support
	 * 3rd parties should add support via filter on $this->get_supported_types()
	 *
	 * @return   array        associative array, keys are the ID/db type, values should be translated display types
	 */
	
	protected function set_supported_types() {
		return array(
			'basic' => __('Basic', 'mycred-toolkit'),
		);
	}
}

return LLMS_Notification_Controller_Mycred_Points::instance();
