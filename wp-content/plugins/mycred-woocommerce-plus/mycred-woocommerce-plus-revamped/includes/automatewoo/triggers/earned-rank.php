<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access
}

/**
 * This is an example trigger that is triggered via a WordPress action and includes a user data item.
 * Trigger with: do_action('my_custom_action', $user_id );
 */
class My_AutomateWoo_Custom_Trigger extends AutomateWoo\Trigger {

	/**
	 * Define which data items are set by this trigger, this determines which rules and actions will be available
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'customer' );

	/**
	 * Set up the trigger
	 */
	public function init() {
		$this->title = __( 'Earned Rank', 'automatewoo-custom' );
		$this->group = __( 'myCred', 'automatewoo-custom' );
	}

	/**
	 * Add any fields to the trigger (optional)
	 */
	public function load_fields() {
		
	}

	/**
	 * Defines when the trigger is run
	 */
	public function register_hooks() {
		add_action( 'my_custom_action', array( $this, 'catch_hooks' ) );
	}

	/**
	 * Catches the action and calls the maybe_run() method.
	 *
	 * @param $user_id
	 */
	public function catch_hooks( $user_id ) {
		
		// get/create customer object from the user id
		$customer = AutomateWoo\Customer_Factory::get_by_user_id( $user_id );
			
		$this->maybe_run(array(
			'customer' => $customer,
		));
	}

	/**
	 * Performs any validation if required. If this method returns true the trigger will fire.
	 *
	 * @param $workflow AutomateWoo\Workflow
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		// Get objects from the data layer
		$customer = $workflow->data_layer()->get_customer();

		// do something...

		return true;
	}

}
