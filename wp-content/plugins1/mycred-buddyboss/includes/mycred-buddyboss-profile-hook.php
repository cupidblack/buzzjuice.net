<?php
if ( ! defined( 'MYCRED_buddyboss_SLUG' ) ) exit;

/**
 * myCRED_buddyboss_Profile_Events_Hook class
 * Creds for profile events updates
 * @since 0.1
 * @version 1.3
 */

class myCRED_buddyboss_Profile_Events_Hook extends myCRED_Hook {

    /**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_profile_events',
			'defaults' => array(
				'add_avatar'   => array(
					'creds' => 10,
					'log'   => '%plural% for new avatar'
				),
				'add_cover'   => array(
					'creds' => 10,
					'log'   => '%plural% for cover image'
				),
				'update_profile'   => array(
					'creds' => 0,
					'log'   => '%plural% for update profile'
				),
				'minimum_percent_profile' => array(
					'creds' => 0,
					'log'   => '%plural% for minimum percent profile'
				),
				'user_account_activation'   => array(
                    'creds'         => 1,
                    'log'           => '%plural% for account activation',
                    
                ),
				'user_assigned_specific_profile_type'   => array(
                    'creds'         => 1,
                    'log'           => '%plural% for user assigned profile type',
                    
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
        add_action('buddyboss_alter_user_result',  array( $this, 'buddyboss_content_result' ), 10, 4);

        if ( $this->prefs['add_avatar']['creds'] != 0 )
				add_action( 'xprofile_avatar_uploaded',           array( $this, 'add_avatar' ),20,1 );

				if ( $this->prefs['add_cover']['creds'] != 0 )
				add_action( 'xprofile_cover_image_uploaded',      array( $this, 'add_cover' ),20,1 );
				// 

				if ( $this->prefs['update_profile']['creds'] != 0 )
				add_action( 'xprofile_updated_profile',      array( $this, 'update_profile' ),10,5 );


				if ( $this->prefs['minimum_percent_profile']['creds'] != 0 )
				add_action( 'xprofile_updated_profile',      array( $this, 'minimum_percent_profile' ),10,5 );

			
				if ( $this->prefs['user_account_activation']['creds'] != 0 )
				add_action( 'bp_core_activated_user',      array( $this, 'user_account_activation' ),20,3);

				if ( $this->prefs['user_assigned_specific_profile_type']['creds'] != 0 )
				add_action( 'bp_set_member_type', array( $this,'user_assigned_specific_profile_type'), 20, 2 );

    }

    /**
		 * Avatar Upload
		 * @since 0.1
		 * @version 1.2
		 */
		public function add_avatar($user_id) {
			

			$user_id = apply_filters( 'bp_xprofile_new_avatar_user_id', $user_id );

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'add_avatar', 'update_avatar', $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'update_avatar',
				$user_id,
				$this->prefs['add_avatar']['creds'],
				$this->prefs['add_avatar']['log'],
				0,
				'',
				$this->mycred_type
			);	

		}

		 /**
		 * Account Activation
		 * @since 0.1
		 * @version 1.2
		 */

		public function user_account_activation($user_id, $key, $user) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			//Limit
			if ($this->over_hook_limit( 'user_account_activation', 'account_activation_activity' ) ) return;
			
			// Make sure this is unique event
			if ( $this->core->has_entry( 'account_activation_activity', $user, $user_id) ) return;

				// Execute
				$this->core->add_creds(
					'account_activation_activity',
					$user_id,
					$this->prefs['user_account_activation']['creds'],
					$this->prefs['user_account_activation']['log'],
					$user,
					'bp_account_activation',
					$this->mycred_type
				);

		}

		 /**
		 * User Assigned Specific Profile Type
		 * @since 0.1
		 * @version 1.2
		 */

		public function user_assigned_specific_profile_type($user_id, $member_type) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			//Limit
			if ($this->over_hook_limit( 'user_assigned_specific_profile_type', 'specific_profile_type_activity' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'specific_profile_type_activity', $member_type) ) return;

			
			// Execute
			$this->core->add_creds(
				'specific_profile_type_activity',
				$user_id,
				$this->prefs['user_assigned_specific_profile_type']['creds'],
				$this->prefs['user_assigned_specific_profile_type']['log'],
				$member_type,
				'bp_assigned_profile_type',
				$this->mycred_type
			);


		}

			/**
		 * Cover Upload
		 * @since 1.7
		 * @version 1.0
		 */
		public function add_cover( $user_id = NULL ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'add_cover', 'update_cover', $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'update_cover',
				$user_id,
				$this->prefs['add_cover']['creds'],
				$this->prefs['add_cover']['log'],
				0,
				'',
				$this->mycred_type
			);

		}

		

		/**
		 * New Profile Update
		 * @since 0.1
		 * @version 1.2
		 */
		public function update_profile( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {


			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'update_profile', 'user_profile_update', $user_id ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'new_profile_update', $posted_field_ids, $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'user_profile_update',
				$user_id,
				$this->prefs['update_profile']['creds'],
				$this->prefs['update_profile']['log'],
				$posted_field_ids,
				'bp_user_update_profile',
				$this->mycred_type
			);

		}



		/**
		 * Minimum Percent Profile
		 * @since 0.1
		 * @version 1.2
		 */
		public function minimum_percent_profile( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'minimum_percent_profile', 'minimum_percent_profile', $user_id ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'minimum_percent_profile', $user_id, $posted_field_ids ) ) return;	


			// Execute
			$this->core->add_creds(
				'minimum_percent_profile',
				$user_id,
				$this->prefs['minimum_percent_profile']['creds'],
				$this->prefs['minimum_percent_profile']['log'],
				$posted_field_ids,
				'bp_minimum_profile',
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
		 * Preference for Anniversary Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;

?>


<div class="hook-instance">
<h3><?php _e( 'Change Profile Avatar', 'mycred_buddyboss' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
			<label for="<?php echo esc_attr($this->field_id( array( 'add_avatar' => 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
			<input type="text" class="form-control" name="<?php echo esc_attr($this->field_name( array( 'add_avatar' => 'creds' ) )); ?>" id="<?php echo esc_html($this->field_id( array( 'add_avatar' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['add_avatar']['creds'] )); ?>" size="8" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
			<label  for="<?php echo esc_attr($this->field_id( array( 'add_avatar' => 'log' ) )); ?>"><?php _e( 'Log template', 'mycred_buddyboss' ); ?></label>
			<input type="text" name="<?php echo esc_attr($this->field_name( array( 'add_avatar' => 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'add_avatar' => 'log' ) )); ?>" value="<?php echo esc_attr( $prefs['add_avatar']['log'] ); ?>" class="form-control" />
			<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general', 'post' ) )); ?></span>
			</div>
		</div>
	</div>
	
</div>

<div class="hook-instance">
	<h3><?php _e( 'Change Cover Image', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'add_cover', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'add_cover', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'add_cover', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['add_cover']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'add_cover', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'add_cover', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'add_cover', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['add_cover']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general', 'post' ) )); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="hook-instance">
	<h3><?php _e( 'Profile Update', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'update_profile', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'update_profile', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'update_profile', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['update_profile']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'update_profile', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'update_profile', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'update_profile', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['update_profile']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'Minimum Percent Profile', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'minimum_percent_profile', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'minimum_percent_profile', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'minimum_percent_profile', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['minimum_percent_profile']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'minimum_percent_profile', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'minimum_percent_profile', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'minimum_percent_profile', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['minimum_percent_profile']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="hook-instance">
	<h3><?php _e( 'User Account Activation', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_account_activation', 'creds' ) )); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_account_activation', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_account_activation', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_account_activation']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_account_activation', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_account_activation', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_account_activation', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_account_activation']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="hook-instance">
	<h3><?php _e( 'User Assigned specific Profile Type', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_assigned_specific_profile_type', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_assigned_specific_profile_type', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_assigned_specific_profile_type', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_assigned_specific_profile_type']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_assigned_specific_profile_type', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_assigned_specific_profile_type', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_assigned_specific_profile_type', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_assigned_specific_profile_type']['log'] ); ?>" class="form-control" />
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


	  	if ( isset( $data['add_avatar']['limit'] ) && isset( $data['add_avatar']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['add_avatar']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['add_avatar']['limit'] = $limit . '/' . $data['add_avatar']['limit_by'];
			unset( $data['add_avatar']['limit_by'] );
		}


		$data['add_avatar']['creds'] = ( !empty( $data['add_avatar']['creds'] ) ) ? floatval( $data['add_avatar']['creds'] ) : $this->defaults['add_avatar']['creds'];

			

			$data['add_avatar']['log'] = ( !empty( $data['add_avatar']['log'] ) ) ? sanitize_text_field( $data['add_avatar']['log'] ) : $this->defaults['add_avatar']['log'];



			$data['add_cover']['creds'] = ( !empty( $data['add_cover']['creds'] ) ) ? floatval( $data['add_cover']['creds'] ) : $this->defaults['add_cover']['creds'];

			

			$data['add_cover']['log'] = ( !empty( $data['add_cover']['log'] ) ) ? sanitize_text_field( $data['add_cover']['log'] ) : $this->defaults['add_cover']['log'];



				$data['update_profile']['creds'] = ( !empty( $data['update_profile']['creds'] ) ) ? floatval( $data['update_profile']['creds'] ) : $this->defaults['update_profile']['creds'];

			

			$data['update_profile']['log'] = ( !empty( $data['update_profile']['log'] ) ) ? sanitize_text_field( $data['update_profile']['log'] ) : $this->defaults['update_profile']['log'];



					$data['minimum_percent_profile']['creds'] = ( !empty( $data['minimum_percent_profile']['creds'] ) ) ? floatval( $data['minimum_percent_profile']['creds'] ) : $this->defaults['minimum_percent_profile']['creds'];

			

			$data['minimum_percent_profile']['log'] = ( !empty( $data['minimum_percent_profile']['log'] ) ) ? sanitize_text_field( $data['minimum_percent_profile']['log'] ) : $this->defaults['minimum_percent_profile']['log'];



			$data['user_account_activation']['creds'] = ( !empty( $data['user_account_activation']['creds'] ) ) ? floatval( $data['user_account_activation']['creds'] ) : $this->defaults['user_account_activation']['creds'];

			

			$data['user_account_activation']['log'] = ( !empty( $data['user_account_activation']['log'] ) ) ? sanitize_text_field( $data['user_account_activation']['log'] ) : $this->defaults['user_account_activation']['log'];


			$data['user_assigned_specific_profile_type']['creds'] = ( !empty( $data['user_assigned_specific_profile_type']['creds'] ) ) ? floatval( $data['user_assigned_specific_profile_type']['creds'] ) : $this->defaults['user_assigned_specific_profile_type']['creds'];

			

			$data['user_assigned_specific_profile_type']['log'] = ( !empty( $data['user_assigned_specific_profile_type']['log'] ) ) ? sanitize_text_field( $data['user_assigned_specific_profile_type']['log'] ) : $this->defaults['user_assigned_specific_profile_type']['log'];





	  	if ( isset( $data['add_cover']['limit'] ) && isset( $data['add_cover']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['add_cover']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['add_cover']['limit'] = $limit . '/' . $data['add_cover']['limit_by'];
			unset( $data['add_cover']['limit_by'] );
		}



	  		if ( isset( $data['update_profile']['limit'] ) && isset( $data['update_profile']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['update_profile']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['update_profile']['limit'] = $limit . '/' . $data['update_profile']['limit_by'];
			unset( $data['update_profile']['limit_by'] );
		}


	  	

	  	if ( isset( $data['minimum_percent_profile']['limit'] ) && isset( $data['minimum_percent_profile']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['minimum_percent_profile']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['minimum_percent_profile']['limit'] = $limit . '/' . $data['minimum_percent_profile']['limit_by'];
			unset( $data['minimum_percent_profile']['limit_by'] );
		}


	 

	  	if ( isset( $data['user_account_activation']['limit'] ) && isset( $data['user_account_activation']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['user_account_activation']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['user_account_activation']['limit'] = $limit . '/' . $data['user_account_activation']['limit_by'];
			unset( $data['user_account_activation']['limit_by'] );
		}



	  

	  	if ( isset( $data['user_assigned_specific_profile_type']['limit'] ) && isset( $data['user_assigned_specific_profile_type']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['user_assigned_specific_profile_type']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['user_assigned_specific_profile_type']['limit'] = $limit . '/' . $data['user_assigned_specific_profile_type']['limit_by'];
			unset( $data['user_assigned_specific_profile_type']['limit_by'] );
		}

		

		if ( isset( $data['follow']['limit'] ) && isset( $data['follow']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['update']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['follow']['limit'] = $limit . '/' . $data['follow']['limit_by'];
			unset( $data['follow']['limit_by'] );
		}

		if ( isset( $data['removed_update']['limit'] ) && isset( $data['removed_update']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['removed_update']['limit'] );
			if ( $limit == '' ) $limit = 0;
			$data['removed_update']['limit'] = $limit . '/' . $data['removed_update']['limit_by'];
			unset( $data['removed_update']['limit_by'] );
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