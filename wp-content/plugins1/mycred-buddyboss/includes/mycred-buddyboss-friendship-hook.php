<?php
if ( ! defined( 'MYCRED_buddyboss_SLUG' ) ) exit;

/**
 * myCRED_buddyboss_Follow_Events_Hook class
 * Creds for follow events updates
 * @since 0.1
 * @version 1.3
 */

class myCRED_buddyboss_Friendship_Events_Hook extends myCRED_Hook {

    /**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_friendship_events',
			'defaults' => array(
				'send_friendship_request'     => array(
					'creds'         => 1,
					'log'           => '%plural% for sending friendship request',
				),
				'new_friend'     => array(
					'creds'         => 1,
					'log'           => '%plural% for new friendship accepted',
					
				),
			
				'leave_friend'   => array(
					'creds'         => '-1',
					'log'           => '%singular% deduction for losing a friend',
					'limit'         => '0/x'
				),
			)
		), $hook_prefs, $type );


	}

	/**
	 * Run
	 * @since 1.8
	 * @version 1.0
	 */
	public function run() {

		if ( $this->prefs['new_friend']['creds'] < 0 && isset( $this->prefs['new_friend']['block'] ) && $this->prefs['new_friend']['block'] == 1 ) {
			add_action( 'wp_ajax_addremove_friend',           array( $this, 'ajax_addremove_friend' ), 0 );
			add_filter( 'bp_get_add_friend_button',           array( $this, 'disable_friendship' ) );
		}

		if ( $this->prefs['new_friend']['creds'] != 0 )
				add_action( 'friends_friendship_accepted',        array( $this, 'friendship_join' ), 10, 3 );


		if ( $this->prefs['leave_friend']['creds'] != 0 )
				add_action( 'friends_friendship_deleted',         array( $this, 'friendship_leave' ), 10, 3 );

		if ( $this->prefs['send_friendship_request']['creds'] != 0 )
				add_action( 'friends_friendship_requested',         array( $this, 'send_friendship_request' ), 10, 3 );

	}

	   /**
		 * AJAX: Add/Remove Friend
		 * Intercept addremovefriend ajax call and block
		 * action if the user can not afford new friendship.
		 * @since 1.5.4
		 * @version 1.0
		 */
		public function ajax_addremove_friend() {

			// Bail if not a POST action
			if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
				return;

			$user_id = bp_loggedin_user_id();
			$balance = $this->core->get_users_balance( $user_id, $this->mycred_type );
			$cost    = abs( $this->prefs['new_friend']['creds'] );

			// Take into account any existing requests which will be charged when the new
			// friend approves it. Prevents users from requesting more then they can afford.
			$pending_requests = $this->count_pending_requests( $user_id );
			if ( $pending_requests > 0 )
				$cost = $cost + ( $cost * $pending_requests );

			// Prevent BP from running this ajax call
			if ( $balance < $cost ) {
				echo apply_filters( 'mycred_bp_declined_addfriend', __( 'Insufficient Funds', 'mycred' ), $this );
				exit;
			}

		}

		/**
		 * Count Pending Friendship Requests
		 * Counts the given users pending friendship requests sent to
		 * other users.
		 * @since 1.5.4
		 * @version 1.0
		 */
		protected function count_pending_requests( $user_id ) {

			global $wpdb, $bp;

			return $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(*) 
				FROM {$bp->friends->table_name} 
				WHERE initiator_user_id = %d 
				AND is_confirmed = 0;", $user_id ) );

		}

		/**
		 * New Friendship Accepted
		 * @since 0.1
		 * @version 1.3.1
		 */
		public function friendship_join( $friendship_id, $initiator_user_id, $friend_user_id ) {

			if ( ! $this->core->exclude_user( $initiator_user_id ) && ! $this->core->has_entry( 'new_friendship', $friend_user_id, $initiator_user_id ) && ! $this->over_hook_limit( 'new_friend', 'new_friendship', $initiator_user_id ) )
			$this->core->add_creds(
				'new_friendship',
				$initiator_user_id,
				$this->prefs['new_friend']['creds'],
				$this->prefs['new_friend']['log'],
				$friend_user_id,
				array( 'ref_type' => 'user' ),
				$this->mycred_type
			);

			// Points to friend (ignored if we are deducting points for new friendships)
			if ( $this->prefs['new_friend']['creds'] > 0 && ! $this->core->exclude_user( $friend_user_id ) && ! $this->over_hook_limit( 'new_friend', 'new_friendship', $friend_user_id ) )
				$this->core->add_creds(
					'new_friendship',
					$friend_user_id,
					$this->prefs['new_friend']['creds'],
					$this->prefs['new_friend']['log'],
					$initiator_user_id,
					array( 'ref_type' => 'user' ),
					$this->mycred_type
				);

		}

		public function new_friend_request_accepted ($friendship_id, $initiator_user_id, $friend_user_id) {

			// Points to friend (ignored if we are deducting points for new friendships)
			if ( $this->prefs['new_friend_request_accepted']['creds'] > 0 && ! $this->core->exclude_user( $friend_user_id ) && ! $this->over_hook_limit( 'new_friend_request_accepted', 'new_friendship', $friend_user_id ) )
				$this->core->add_creds(
					'new_friendship',
					$friend_user_id,
					$this->prefs['new_friend_request_accepted']['creds'],
					$this->prefs['new_friend_request_accepted']['log'],
					$initiator_user_id,
					array( 'ref_type' => 'user' ),
					$this->mycred_type
				);

		}

		/**
		 * Remove friendship
		 * @since 0.1
		 * @version 1.3.1
		 */

		public function remove_friendship($friendship_id, $initiator_user_id, $friend_user_id) {
			
			if ( ! $this->core->exclude_user( $initiator_user_id ) && ! $this->core->has_entry( 'remove_friendship', $friend_user_id, $initiator_user_id ) && ! $this->over_hook_limit( 'remove_friendship', 'remove_friendship', $initiator_user_id ) )
			$this->core->add_creds(
				'remove_friendship',
				$initiator_user_id,
				$this->prefs['remove_friendship']['creds'],
				$this->prefs['remove_friendship']['log'],
				$friend_user_id,
				array( 'ref_type' => 'user' ),
				$this->mycred_type
			);
		}

		/**
		 * Ending Friendship
		 * @since 0.1
		 * @version 1.2
		 */
		public function friendship_leave( $friendship_id, $initiator_user_id, $friend_user_id ) {

			if ( ! $this->core->exclude_user( $initiator_user_id ) && ! $this->core->has_entry( 'ended_friendship', $friend_user_id, $initiator_user_id ) )
				$this->core->add_creds(
					'ended_friendship',
					$initiator_user_id,
					$this->prefs['leave_friend']['creds'],
					$this->prefs['leave_friend']['log'],
					$friend_user_id,
					array( 'ref_type' => 'user' ),
					$this->mycred_type
				);

			if ( ! $this->core->exclude_user( $friend_user_id ) )
				$this->core->add_creds(
					'ended_friendship',
					$friend_user_id,
					$this->prefs['leave_friend']['creds'],
					$this->prefs['leave_friend']['log'],
					$initiator_user_id,
					array( 'ref_type' => 'user' ),
					$this->mycred_type
				);

		}
		
		/**
		 * Send New Friendship Request
		 * @since 0.1
		 * @version 1.3.1
		 */

		public function send_friendship_request($friendship_id, $initiator_user_id, $friend_user_id){

			if ( ! $this->core->exclude_user( $initiator_user_id ) && ! $this->core->has_entry( 'request_friendship', $friend_user_id, $initiator_user_id ) && ! $this->over_hook_limit( 'send_friendship_request', 'request_friendship', $initiator_user_id ) )
			$this->core->add_creds(
				'request_friendship',
				$initiator_user_id,
				$this->prefs['send_friendship_request']['creds'],
				$this->prefs['send_friendship_request']['log'],
				$friend_user_id,
				array( 'ref_type' => 'user' ),
				$this->mycred_type
			);
		}

			/**
		 * Restrict Group Join
		 * If joining a group costs and the user does not have enough points, we restrict joining of groups.
		 * @since 0.1
		 * @version 1.0
		 */
		public function restrict_joining_group( $button ) {

			global $bp;

			// Check if user should be excluded
			if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return $button;

			// Check if user has enough to join group
			$cost = abs( $this->prefs['join']['creds'] );
			$balance = $this->core->get_users_balance( $bp->loggedin_user->id, $this->mycred_type );
			if ( $cost > $balance ) return false;

			return $button;

		}

		/**
		 * Disable Friendship
		 * If we deduct points from a user for new friendships
		 * we disable the friendship button if the user ca not afford it.
		 * @since 1.5.4
		 * @version 1.0
		 */
		public function disable_friendship( $button ) {

			// Only applicable for Add Friend button
			if ( $button['id'] == 'not_friends' ) {

				$user_id = bp_loggedin_user_id();
				$balance = $this->core->get_users_balance( $user_id, $this->mycred_type );
				$cost    = abs( $this->prefs['new_friend']['creds'] );

				// Take into account any existing requests which will be charged when the new
				// friend approves it. Prevents users from requesting more then they can afford.
				$pending_requests = $this->count_pending_requests( $user_id );
				if ( $pending_requests > 0 )
					$cost = $cost + ( $cost * $pending_requests );

				if ( $balance < $cost )
					return array();

			}

			return $button;

		}

			/**
		 * Preferences
		 * @since 0.1
		 * @version 1.2
		 */
		public function preferences() {

			$prefs = $this->prefs;

			if ( ! isset( $prefs['removed_update'] ) )
				$prefs['removed_update'] = array( 'creds' => 0, 'limit' => '0/x', 'log' => '%plural% deduction for removing profile update' );

			$friend_block = 0;
			if ( isset( $prefs['new_friend']['block'] ) )
				$friend_block = $prefs['new_friend']['block'];

?>

<div class="hook-instance">
	<h3><?php _e( 'Send friendship Request', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'send_friendship_request', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'send_friendship_request', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'send_friendship_request', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['send_friendship_request']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'send_friendship_request', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'send_friendship_request', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'send_friendship_request', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['send_friendship_request']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>

</div>

<div class="hook-instance">
	<h3><?php _e( 'New Friendship', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'new_friend', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'new_friend', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'new_friend', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['new_friend']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'new_friend', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'new_friend', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'new_friend', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['new_friend']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
	
</div>



<div class="hook-instance">
	<h3><?php _e( 'End Friendship', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'leave_friend', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'leave_friend', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'leave_friend', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['leave_friend']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'leave_friend', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'leave_friend', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'leave_friend', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['leave_friend']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>

<?php

}


	/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 1.1
		 */
		public function sanitise_preferences( $data ) {


			$data['leave_friend']['creds'] = ( !empty( $data['leave_friend']['creds'] ) ) ? floatval( $data['sleave_friend']['creds'] ) : $this->defaults['leave_friend']['creds'];


			$data['leave_friend']['log'] = ( !empty( $data['leave_friend']['log'] ) ) ? sanitize_text_field( $data['leave_friend']['log'] ) : $this->defaults['leave_friend']['log'];


			$data['send_friendship_request']['creds'] = ( !empty( $data['send_friendship_request']['creds'] ) ) ? floatval( $data['send_friendship_request']['creds'] ) : $this->defaults['send_friendship_request']['creds'];


			$data['send_friendship_request']['log'] = ( !empty( $data['send_friendship_request']['log'] ) ) ? sanitize_text_field( $data['send_friendship_request']['log'] ) : $this->defaults['send_friendship_request']['log'];



			$data['new_friend']['creds'] = ( !empty( $data['new_friend']['creds'] ) ) ? floatval( $data['new_friend']['creds'] ) : $this->defaults['new_friend']['creds'];

			

			$data['new_friend']['log'] = ( !empty( $data['new_friend']['log'] ) ) ? sanitize_text_field( $data['new_friend']['log'] ) : $this->defaults['new_friend']['log'];


			if ( isset( $data['leave_friend']['limit'] ) && isset( $data['leave_friend']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['leave_friend']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['leave_friend']['limit'] = $limit . '/' . $data['leave_friend']['limit_by'];
				unset( $data['leave_friend']['limit_by'] );
			}


			if ( isset( $data['send_friendship_request']['limit'] ) && isset( $data['send_friendship_request']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['send_friendship_request']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['send_friendship_request']['limit'] = $limit . '/' . $data['send_friendship_request']['limit_by'];
				unset( $data['update']['limit_by'] );
			}



			if ( isset( $data['new_friend']['limit'] ) && isset( $data['new_friend']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_friend']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_friend']['limit'] = $limit . '/' . $data['new_friend']['limit_by'];
				unset( $data['update']['limit_by'] );
			}



			if ( isset( $data['update']['limit'] ) && isset( $data['update']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['update']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['update']['limit'] = $limit . '/' . $data['update']['limit_by'];
				unset( $data['update']['limit_by'] );
			}

			if ( isset( $data['removed_update']['limit'] ) && isset( $data['removed_update']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['removed_update']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['removed_update']['limit'] = $limit . '/' . $data['removed_update']['limit_by'];
				unset( $data['removed_update']['limit_by'] );
			}

			if ( isset( $data['avatar']['limit'] ) && isset( $data['avatar']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['avatar']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['avatar']['limit'] = $limit . '/' . $data['avatar']['limit_by'];
				unset( $data['avatar']['limit_by'] );
			}

			if ( isset( $data['cover']['limit'] ) && isset( $data['cover']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['cover']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['cover']['limit'] = $limit . '/' . $data['cover']['limit_by'];
				unset( $data['cover']['limit_by'] );
			}

			if ( isset( $data['new_friend']['limit'] ) && isset( $data['new_friend']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_friend']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_friend']['limit'] = $limit . '/' . $data['new_friend']['limit_by'];
				unset( $data['new_friend']['limit_by'] );
			}

			$data['new_friend']['block'] = ( isset( $data['new_friend']['block'] ) ) ? absint( $data['new_friend']['block'] ) : 0;

			if ( isset( $data['new_comment']['limit'] ) && isset( $data['new_comment']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_comment']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_comment']['limit'] = $limit . '/' . $data['new_comment']['limit_by'];
				unset( $data['new_comment']['limit_by'] );
			}

			if ( isset( $data['add_favorite']['limit'] ) && isset( $data['add_favorite']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['add_favorite']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['add_favorite']['limit'] = $limit . '/' . $data['add_favorite']['limit_by'];
				unset( $data['add_favorite']['limit_by'] );
			}

			if ( isset( $data['message']['limit'] ) && isset( $data['message']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['message']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['message']['limit'] = $limit . '/' . $data['message']['limit_by'];
				unset( $data['message']['limit_by'] );
			}

			if ( isset( $data['send_gift']['limit'] ) && isset( $data['send_gift']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['send_gift']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['send_gift']['limit'] = $limit . '/' . $data['send_gift']['limit_by'];
				unset( $data['send_gift']['limit_by'] );
			}

			return $data;

		}

	}


