<?php
if ( ! defined( 'MYCRED_buddyboss_SLUG' ) ) exit;

/**
 * myCRED_buddyboss_Follow_Events_Hook class
 * Creds for follow events updates
 * @since 0.1
 * @version 1.3
 */
class myCRED_buddyboss_Follow_Events_Hook extends myCRED_Hook {

	/**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_follow_events',
			'defaults' => array(
				'add_follow'   => array(
					'creds' => 0,
					'log'   => '%plural% for Following'
				),
				'stop_follow'   => array(
					'creds' => -10,
					'log'   => '%plural% for Stop Following'
				),
				'get_followers'   => array(
					'creds' => 0,
					'log'   => '%plural% for Get Followers'
				),
				'new_follower' => array(
					'creds' => 0,
					'log'   => '%plural% for New Follower'
				),
				'lose_follower' => array(
					'creds' => 0,
					'log'   => '%plural% for Losing a Follower'
				)
				
			)
		), $hook_prefs, $type );

	}

	/**
	 * Run
	 * @since 1.8
	 * @version 1.0
	 */
	public function run() {

		add_action('buddyboss_alter_user_result',  array( $this, 'buddyboss_content_result' ), 10, 4);

		// Follow/Follower
		if ( $this->prefs['add_follow']['creds'] != 0 || $this->prefs['new_follower']['creds'] != 0 || $this->prefs['get_followers']['creds'] != 0 )
		add_action( 'bp_start_following',    array( $this, 'add_follow' ), 20, 1 );

		// Stop Follow
		if ( $this->prefs['stop_follow']['creds'] != 0 || $this->prefs['lose_follower']['creds'] != 0 )
		add_action( 'bp_stop_following',    array( $this, 'stop_follow' ), 20, 1 );

		

	}


	public function add_follow( $follow ) {	

		$follower_id = $follow->follower_id;
		$leader_id   = $follow->leader_id;

		// Check if follow completed
		if ( $this->core->exclude_user( $follower_id ) ) return;

		// Check if friend is excluded
		if ( $this->core->exclude_user( $leader_id ) ) return;

		// Make sure this is unique event
		if ( $this->core->has_entry( 'add_follow', $leader_id, $follower_id ) ) return;

		// Points to initiator
		$this->core->add_creds(
			'add_follow',
			$follower_id,
			$this->prefs['add_follow']['creds'],
			$this->prefs['add_follow']['log'],
			$leader_id,
			array( 'ref_type' => 'user' ),
			$this->mycred_type
		);

		// Points to friend (ignored if we are deducting points for new friendships)
		if ( $this->prefs['new_follower']['creds'] > 0 )
			$this->core->add_creds(
				'new_follower',
				$leader_id,
				$this->prefs['new_follower']['creds'],
				$this->prefs['new_follower']['log'],
				$follower_id,
				array( 'ref_type' => 'user' ),
				$this->mycred_type
			);


			if ( $this->prefs['get_followers']['creds'] > 0 )

			$this->core->add_creds(
				'get_followers',
				$leader_id,
				$this->prefs['get_followers']['creds'],
				$this->prefs['get_followers']['log'],
				$follower_id,
				array( 'ref_type' => 'user' ),
				$this->mycred_type
			);

	}


	public function stop_follow( $unfollow ) {	

		$follower_id = $unfollow->follower_id;
		$leader_id   = $unfollow->leader_id;

		// Check if follow completed
		if ( $this->core->exclude_user( $follower_id ) ) return;

		// Check if friend is excluded
		if ( $this->core->exclude_user( $leader_id ) ) return;

		// Make sure this is unique event
		if ( $this->core->has_entry( 'stop_follow', $leader_id, $follower_id ) ) return;

		// Points to initiator
		$this->core->add_creds(
			'stop_follow',
			$follower_id,
			$this->prefs['stop_follow']['creds'],
			$this->prefs['stop_follow']['log'],
			$leader_id,
			array( 'ref_type' => 'user' ),
			$this->mycred_type
		);

		if ( $this->prefs['new_follower']['creds'] > 0 )

		$this->core->add_creds(
			'lose_follower',
			$leader_id,
			$this->prefs['lose_follower']['creds'],
			$this->prefs['lose_follower']['log'],
			$follower_id,
			array( 'ref_type' => 'user' ),
			$this->mycred_type
		);

	}


	    /**
		 * Page Load
		 * @since 1.8
		 * @version 1.0
		 */
		public function buddyboss_content_result( $data, $result_id, $content_id, $user_id ) {

			if ( $result_id ) return;

			$hook_prefs_key = 'mycred_pref_hooks';

			if ( $this->mycred_type != MYCRED_DEFAULT_TYPE_KEY ) {
				$hook_prefs_key = 'mycred_pref_hooks_'.$this->mycred_type;
			}

			$hooks = get_option( $hook_prefs_key, false );

			if ( ! empty( $hooks['hook_prefs'] ) && ! empty( $hooks['hook_prefs']['completing_buddyboss_content_specific'] ) ) {
				if( in_array( $content_id, $hooks['hook_prefs']['completing_buddyboss_content_specific']['content'] ) ) return;
			}

			// Make sure user is not excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			$ref_type  = array( 'ref_type' => 'post' );

			if( 
				$this->has_entry( 'completing_buddyboss_content_general', $content_id, $user_id, $ref_type, $this->mycred_type ) ||
				$this->has_entry( 'completing_buddyboss_content_specific', $content_id, $user_id, $ref_type, $this->mycred_type )
			) return;

		

			$max_score = floatval( $data['max_score'] );
			$score = floatval( $data['score'] );
			$percentage = ( $score / $max_score ) * 100;

			if ( $this->prefs['percentage'] == 0 || $percentage >= floatval( $this->prefs['percentage'] ) ) {
				// Execute
				$this->core->add_creds(
			        'completing_buddyboss_content_general',
			        $user_id,
			        $this->prefs['creds'],
			        $this->prefs['log'],
			        $content_id,
			        $ref_type,
					$this->mycred_type
				);
			}

		}

	public function event_bp_start_following( $follow ) {


		// Check if user is excluded
		if ( $this->core->exclude_user( $user_id ) ) return;

		// Limit
		if ( $this->over_hook_limit( 'follow', 'follow_update', $user_id ) ) return;

		// Make sure this is unique event
		if ( $this->core->has_entry( 'follow_update', $activity_id, $user_id ) ) return;

		// Execute
		$this->core->add_creds(
			'follow_update',
			$user_id,
			$this->prefs['follow']['creds'],
			$this->prefs['follow']['log'],
			$activity_id,
			'bp_start',
			$this->mycred_type
		);

	}

	/**
		 * Removing Profile Update
		 * @since 1.6
		 * @version 1.0
		 */
		public function remove_update( $args ) {

			if ( ! isset( $args['user_id'] ) || $args['user_id'] === false ) return;

			$user_id = absint( $args['user_id'] );

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'removed_update', 'deleted_profile_update', $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'deleted_profile_update',
				$user_id,
				$this->prefs['removed_update']['creds'],
				$this->prefs['removed_update']['log'],
				0,
				$args,
				$this->mycred_type
			);

		}

		/**
		 * Avatar Upload
		 * @since 0.1
		 * @version 1.2
		 */
		public function avatar_upload() {

			$user_id = apply_filters( 'bp_xprofile_new_avatar_user_id', bp_displayed_user_id() );

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'avatar', 'upload_avatar', $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'upload_avatar',
				$user_id,
				$this->prefs['avatar']['creds'],
				$this->prefs['avatar']['log'],
				0,
				'',
				$this->mycred_type
			);

		}

		/**
		 * Cover Upload
		 * @since 1.7
		 * @version 1.0
		 */
		public function cover_change( $user_id = NULL ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'cover', 'upload_cover', $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'upload_cover',
				$user_id,
				$this->prefs['cover']['creds'],
				$this->prefs['cover']['log'],
				0,
				'',
				$this->mycred_type
			);

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
		 * New Friendship
		 * @since 0.1
		 * @version 1.3.1
		 */
		public function friendship_join( $friendship_id, $initiator_user_id, $friend_user_id ) {

			// Make sure this is unique event
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
		 * New Comment
		 * @since 0.1
		 * @version 1.2
		 */
		public function new_comment( $comment_id, $params ) {

			$user_id = bp_loggedin_user_id();

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'new_comment', 'new_comment' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'new_comment', $comment_id ) ) return;

			// Execute
			$this->core->add_creds(
				'new_comment',
				$user_id,
				$this->prefs['new_comment']['creds'],
				$this->prefs['new_comment']['log'],
				$comment_id,
				'bp_comment',
				$this->mycred_type
			);

		}

		/**
		 * Comment Deletion
		 * @since 0.1
		 * @version 1.0
		 */
		public function delete_comment( $activity_id, $user_id ) {

			global $wpdb, $bp;
			
			$activity_type = $wpdb->get_var( $wpdb->prepare( "SELECT type FROM {$bp->activity->table_name} WHERE id = %d", $activity_id ) );
            
			if( $activity_type == 'activity_comment' ) {

			    // Check if user is excluded
    			if ( $this->core->exclude_user( $user_id ) ) return;
                
    			// Make sure this is unique event
    			if ( $this->core->has_entry( 'comment_deletion', $activity_id ) ) return;
    
    			// Execute
    			$this->core->add_creds(
    				'comment_deletion',
    				$user_id,
    				$this->prefs['delete_comment']['creds'],
    				$this->prefs['delete_comment']['log'],
    				$activity_id,
    				'bp_comment',
    				$this->mycred_type
    			);
			
			}

		}

		/**
		 * Add to Favorites
		 * @since 1.7
		 * @version 1.0
		 */
		public function add_to_favorites( $activity_id, $user_id ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;
			//Limit
			if ($this->over_hook_limit( 'add_favorite', 'fave_activity' ) ) return;
			// Make sure this is unique event
			if ( $this->core->has_entry( 'fave_activity', $activity_id ) ) return;

			// Execute
			$this->core->add_creds(
				'fave_activity',
				$user_id,
				$this->prefs['add_favorite']['creds'],
				$this->prefs['add_favorite']['log'],
				$activity_id,
				'bp_comment',
				$this->mycred_type
			);

		}

		/**
		 * Remove from Favorites
		 * @since 1.7
		 * @version 1.0
		 */
		public function removed_from_favorites( $activity_id, $user_id ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'unfave_activity', $activity_id ) ) return;

			// Execute
			$this->core->add_creds(
				'unfave_activity',
				$user_id,
				$this->prefs['remove_favorite']['creds'],
				$this->prefs['remove_favorite']['log'],
				$activity_id,
				'bp_comment',
				$this->mycred_type
			);

		}

		/**
		 * New Message
		 * @since 0.1
		 * @version 1.1
		 */
		public function messages( $message ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $message->sender_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'message', 'new_message', $message->sender_id ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'new_message', $message->thread_id ) ) return;

			// Execute
			$this->core->add_creds(
				'new_message',
				$message->sender_id,
				$this->prefs['message']['creds'],
				$this->prefs['message']['log'],
				$message->thread_id,
				'bp_message',
				$this->mycred_type
			);

		}

		/**
		 * Send Gift
		 * @since 0.1
		 * @version 1.1
		 */
		public function send_gifts( $to_user_id, $from_user_id ) {

			// Check if sender is excluded
			if ( $this->core->exclude_user( $from_user_id ) ) return;

			// Check if recipient is excluded
			if ( $this->core->exclude_user( $to_user_id ) ) return;

			// Limit
			if ( ! $this->over_hook_limit( 'send_gift', 'sending_gift', $from_user_id ) )
				$this->core->add_creds(
					'sending_gift',
					$from_user_id,
					$this->prefs['send_gift']['creds'],
					$this->prefs['send_gift']['log'],
					$to_user_id,
					'bp_gifts',
					$this->mycred_type
				);

		}

	/**
		 * Preference for Anniversary Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;

?>

<div class="hook-instance">
<h3><?php _e( 'Follow', 'mycred_buddyboss' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
			<label for="<?php echo esc_attr($this->field_id( array( 'add_follow' => 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
			<input type="text" class="form-control" name="<?php echo esc_attr($this->field_name( array( 'add_follow' => 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'add_follow' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['add_follow']['creds'] )); ?>" size="8" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'add_follow' => 'log' ) )); ?>"><?php _e( 'Log template', 'mycred_buddyboss' ); ?></label>
			<input type="text" name="<?php echo esc_attr($this->field_name( array( 'add_follow' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'add_follow' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['add_follow']['log'] ); ?>" class="form-control" />
			<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
	
</div>

<div class="hook-instance">
<h3><?php _e( 'New Follower', 'mycred_buddyboss' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'new_follower' => 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
			<input type="text" class="form-control" name="<?php echo esc_attr($this->field_name( array( 'new_follower' => 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'new_follower' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['new_follower']['creds'] )); ?>" size="8" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'new_follower' => 'log' ) )); ?>"><?php _e( 'Log template', 'mycred_buddyboss' ); ?></label>
			<input type="text" name="<?php echo esc_attr($this->field_name( array( 'new_follower' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'new_follower' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['new_follower']['log'] ); ?>" class="form-control" />
			<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
		
	</div>
	
</div>



<div class="hook-instance">
<h3><?php _e( 'Get Follower', 'mycred_buddyboss' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
			<label for="<?php echo esc_attr($this->field_id( array( 'get_followers' => 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
			<input type="text" class="form-control" name="<?php echo esc_attr($this->field_name( array( 'get_followers' => 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_followers' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['get_followers']['creds'] )); ?>" size="8" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'get_followers' => 'log' ) )); ?>"><?php _e( 'Log template', 'mycred_buddyboss' ); ?></label>
			<input type="text" name="<?php echo esc_attr($this->field_name( array( 'get_followers' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_followers' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['get_followers']['log'] ); ?>" class="form-control" />
			<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
	
</div>

<div class="hook-instance">
<h3><?php _e( 'Stop Follow', 'mycred_buddyboss' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'stop_follow' => 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
			<input type="text" class="form-control" name="<?php echo esc_attr($this->field_name( array( 'stop_follow' => 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'stop_follow' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['stop_follow']['creds'] )); ?>" size="8" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'stop_follow' => 'log' ) )); ?>"><?php _e( 'Log template', 'mycred_buddyboss' ); ?></label>
			<input type="text" name="<?php echo esc_attr($this->field_name( array( 'stop_follow' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'stop_follow' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['stop_follow']['log'] ); ?>" class="form-control" />
			<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
		
	</div>
	
</div>

<div class="hook-instance">
<h3><?php _e( 'Lose Follower', 'mycred_buddyboss' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'lose_follower' => 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
			<input type="text" class="form-control" name="<?php echo esc_attr($this->field_name( array( 'lose_follower' => 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'lose_follower' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['lose_follower']['creds'] )); ?>" size="8" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'lose_follower' => 'log' ) )); ?>"><?php _e( 'Log template', 'mycred_buddyboss' ); ?></label>
			<input type="text" name="<?php echo esc_attr($this->field_name( array( 'lose_follower' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'lose_follower' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['lose_follower']['log'] ); ?>" class="form-control" />
			<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
		
	</div>
	
</div>


<?php

		}

	   /**
	   * Sanitize Preferences
	   */
	  public function sanitise_preferences( $data ) {


	  	$data['add_follow']['creds'] = ( !empty( $data['add_follow']['creds'] ) ) ? floatval( $data['add_follow']['creds'] ) : $this->defaults['add_follow']['creds'];

	  	$data['add_follow']['log'] = ( !empty( $data['add_follow']['log'] ) ) ? sanitize_text_field( $data['add_follow']['log'] ) : $this->defaults['add_follow']['log'];

	

	  	$data['stop_follow']['creds'] = ( !empty( $data['stop_follow']['creds'] ) ) ? floatval( $data['stop_follow']['creds'] ) : $this->defaults['stop_follow']['creds'];

	  	$data['stop_follow']['log'] = ( !empty( $data['stop_follow']['log'] ) ) ? sanitize_text_field( $data['stop_follow']['log'] ) : $this->defaults['stop_follow']['log'];


	  	$data['get_followers']['creds'] = ( !empty( $data['get_followers']['creds'] ) ) ? floatval( $data['get_followers']['creds'] ) : $this->defaults['get_followers']['creds'];

	  	$data['get_followers']['log'] = ( !empty( $data['get_followers']['log'] ) ) ? sanitize_text_field( $data['get_followers']['log'] ) : $this->defaults['get_followers']['log'];


	  	$data['new_follower']['creds'] = ( !empty( $data['new_follower']['creds'] ) ) ? floatval( $data['new_follower']['creds'] ) : $this->defaults['new_follower']['creds'];

	  	$data['new_follower']['log'] = ( !empty( $data['new_follower']['log'] ) ) ? sanitize_text_field( $data['new_follower']['log'] ) : $this->defaults['new_follower']['log'];


	  	$data['lose_follower']['creds'] = ( !empty( $data['lose_follower']['creds'] ) ) ? floatval( $data['lose_follower']['creds'] ) : $this->defaults['lose_follower']['creds'];

	  	$data['lose_follower']['log'] = ( !empty( $data['lose_follower']['log'] ) ) ? sanitize_text_field( $data['lose_follower']['log'] ) : $this->defaults['lose_follower']['log'];


		if ( isset( $data['follow']['limit'] ) && isset( $data['follow']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['update']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['follow']['limit'] = $limit . '/' . $data['follow']['limit_by'];
			unset( $data['follow']['limit_by'] );
		}

		if ( isset( $data['add_follow']['limit'] ) && isset( $data['add_follow']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['add_follow']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['add_follow']['limit'] = $limit . '/' . $data['add_follow']['limit_by'];
			unset( $data['add_follow']['limit_by'] );
		}



		if ( isset( $data['stop_follow']['limit'] ) && isset( $data['stop_follow']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['stop_follow']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['stop_follow']['limit'] = $limit . '/' . $data['stop_follow']['limit_by'];
			unset( $data['stop_follow']['limit_by'] );
		}


		if ( isset( $data['get_followers']['limit'] ) && isset( $data['get_followers']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['get_followers']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['get_followers']['limit'] = $limit . '/' . $data['get_followers']['limit_by'];
			unset( $data['get_followers']['limit_by'] );
		}


		if ( isset( $data['new_follower']['limit'] ) && isset( $data['new_follower']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['new_follower']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['new_follower']['limit'] = $limit . '/' . $data['new_follower']['limit_by'];
			unset( $data['new_follower']['limit_by'] );
		}


		if ( isset( $data['lose_follower']['limit'] ) && isset( $data['lose_follower']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['lose_follower']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['lose_follower']['limit'] = $limit . '/' . $data['lose_follower']['limit_by'];
			unset( $data['lose_follower']['limit_by'] );
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