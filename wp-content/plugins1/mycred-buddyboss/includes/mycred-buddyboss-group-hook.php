<?php
if ( ! defined( 'MYCRED_buddyboss_SLUG' ) ) exit;

/**
 * myCRED_buddyboss_Group_Events_Hook class
 * Creds for group events updates
 * @since 0.1
 * @version 1.3
 */


class myCRED_buddyboss_Group_Events_Hook extends myCRED_Hook {

	/**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_group_events',
			'defaults' => array(
				'create_group'   => array(
					'creds' => 0,
					'log'   => '%plural% for Creating Group'
				),
				'join_group'   => array(
					'creds' => 10,
					'log'   => '%plural% for Joining Group'
				),
				
				'leave_group' => array(
					'creds' => -5,
					'log'   => '%singular% for Leaving Group'
				),
				
                'accepted_on_private_group' => array(
					'creds' => 0,
					'log'   => '%plural% for Accepting on private group'
                ),
                'group_invitations' => array(
					'creds' => 0,
					'log'   => '%plural% for Group Invitations'
                ),
                'group_promotions' => array(
					'creds' => 0,
					'log'   => '%plural% for Group Promotions'
                ),
				'publish_group_activity_feed_message' => array(
					'creds' => 0,
					'log'   => '%plural% for publishing group activity stream message'
                ),
				'remove_group_activity_feed_message' => array(
					'creds' => 0,
					'log'   => '%plural% for removing group activity stream message'
                ),
				
			)
		), $hook_prefs, $type );

	}

    /**
		 * Run
		 * @since 0.1
		 * @version 1.0
		 */
		public function run() {

            add_action('buddyboss_alter_user_result',  array( $this, 'buddyboss_content_result' ), 10, 4);

			if ( $this->prefs['create_group']['creds'] != 0  )
				add_action( 'groups_group_create_complete',     array( $this, 'user_create_group' ) );

				if ( $this->prefs['create_group']['creds'] < 0 )
				add_filter( 'bp_user_can_create_groups',        array( $this, 'user_restrict_group_creation' ), 99, 2 );

                if ( $this->prefs['join_group']['creds'] != 0 || ( $this->prefs['create_group']['creds'] != 0 && $this->prefs['create_group']['min'] != 0 ) )
				add_action( 'groups_join_group',                array( $this, 'user_join_group' ), 20, 2 );


                if ( $this->prefs['leave_group']['creds'] != 0 )
				add_action( 'groups_leave_group',               array( $this, 'user_leave_group' ), 20, 2 );
 
                

                if ( $this->prefs['accepted_on_private_group']['creds'] != 0 )
				add_action( 'groups_membership_accepted',               array( $this, 'user_accepted_private_group' ), 40, 3 ); 

                if ( $this->prefs['group_invitations']['creds'] != 0 )
				add_action( 'groups_invite_user',               array( $this, 'user_group_invitations' ) ); 

				if ( $this->prefs['publish_group_activity_feed_message']['creds'] != 0 )
				add_action( 'bp_groups_posted_update',               array( $this, 'publish_group_activity_feed_message' ) ); 	

				if ( $this->prefs['remove_group_activity_feed_message']['creds'] != 0 )
				add_action( 'bp_activity_action_delete_activity',               array( $this, 'remove_group_activity_feed_message' ) ); 

				if ( $this->prefs['group_promotions']['creds'] != 0 )
				add_action( 'groups_promote_member',               array( $this, 'group_promotions' ), 20, 3 ); 

        }

        /**
		 * Creating Group
		 * @since 0.1
		 * @version 1.0
		 */
		public function user_create_group( $group_id ) {

			global $bp;

			// Check if user should be excluded
			if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return;

			// Execute
			$this->core->add_creds(
				'creation_of_new_group',
				$bp->loggedin_user->id,
				$this->prefs['create_group']['creds'],
				$this->prefs['create_group']['log'],
				$group_id,
				'bp_group',
				$this->mycred_type
			);

		}

		  /**
		 * Group Promotions
		 * @since 0.1
		 * @version 1.0
		 */

		public function group_promotions($group_id, $user_id, $status){

			  // Check if user is excluded
			  if ( $this->core->exclude_user( $user_id ) ) return;

			  //Limit
			if ($this->over_hook_limit( 'group_promotions', 'promotions_activity' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'promotions_activity', $group_id, $user_id) ) return;

			// Execute
			$this->core->add_creds(
				'promotions_activity',
				$user_id,
				$this->prefs['group_promotions']['creds'],
				$this->prefs['group_promotions']['log'],
				$group_id,
				'bp_group_promotions',
				$this->mycred_type
			);



		}

		/**
		 * Restrict Group Creation
		 * If creating a group costs and the user does not have enough points, we restrict creations.
		 * @since 0.1
		 * @version 1.0
		 */
		public function user_restrict_group_creation( $can_create, $restricted ) {

			global $bp;

			// Check if user should be excluded
			if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return $can_create;

			// Check if user has enough to create a group
			$cost = abs( $this->prefs['create_group']['creds'] );
			$balance = $this->core->get_users_balance( $bp->loggedin_user->id, $this->mycred_type );
			if ( $cost > $balance ) return false;

			return $can_create;

		}


        /**
		 * Joining Group
		 * @since 0.1
		 * @version 1.1
		 */
		public function user_join_group( $group_id, $user_id ) {

			// Minimum members limit
			if ( $this->prefs['create_group']['min'] != 0 ) {
				$group = groups_get_group( array( 'group_id' => $group_id ) );

				// Award creator if we have reached the minimum number of members and we have not yet been awarded
				if ( $group->total_member_count >= (int) $this->prefs['create_group']['min'] && ! $this->core->has_entry( 'creation_of_new_group', $group_id, $group->creator_id ) )
					$this->core->add_creds(
						'creation_of_new_group',
						$group->creator_id,
						$this->prefs['create_group']['creds'],
						$this->prefs['create_group']['log'],
						$group_id,
						'bp_group',
						$this->mycred_type
					);

				// Clean up
				unset( $group );

			}

			// Check if user should be excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'join_group', 'joining_group' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'joining_group', $group_id, $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'joining_group',
				$user_id,
				$this->prefs['join_group']['creds'],
				$this->prefs['join_group']['log'],
				$group_id,
				'bp_group',
				$this->mycred_type
				);

		}

		/**
		 * Accepted on Private Group
		 * @since 0.1
		 * @version 1.0
		 */

        public function user_accepted_private_group( $user_id, $group_id, $accepted) {



			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'accepted_on_private_group', 'accepted_private_group' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'accepted_private_group', $group_id, $user_id ) ) return;


            	// Execute
			$this->core->add_creds(
				'accepted_private_group',
				$user_id,
				$this->prefs['accepted_on_private_group']['creds'],
				$this->prefs['accepted_on_private_group']['log'],
				$group_id,
				'bp_group',
				$this->mycred_type
				);
        }

        


        /**
		 * Leaving Group
		 * @since 0.1
		 * @version 1.0
		 */
		public function user_leave_group( $group_id, $user_id ) {

			// Check if user should be excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'leaving_group', $group_id, $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'leaving_group',
				$user_id,
				$this->prefs['leave_group']['creds'],
				$this->prefs['leave_group']['log'],
				$group_id,
					'bp_group',
				$this->mycred_type
			);

		}

           



        /**
		 * Group Invitations
		 * @since 0.1
		 * @version 1.0
		 */

        public function user_group_invitations($group_id) {

			global $bp;

			// Check if user should be excluded
			if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return;
 
			 // Execute
			 $this->core->add_creds(
				 'user_group_invitation',
				 $bp->loggedin_user->id,
				 $this->prefs['group_invitations']['creds'],
				 $this->prefs['group_invitations']['log'],
				 $group_id,
					 'bp_group',
				 $this->mycred_type
			 );
     
        }

		/**
		 * Publish Group Activity Stream Message
		 * @since 0.1
		 * @version 1.0
		 */

		public function publish_group_activity_feed_message($group_id) {

			$user_id = get_current_user_id();

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			 // Execute
			 $this->core->add_creds(
				'publish_activity_feed_message',
				$user_id,
				$this->prefs['publish_group_activity_feed_message']['creds'],
				$this->prefs['publish_group_activity_feed_message']['log'],
				$group_id,
					'bp_publish_activity_feed',
				$this->mycred_type
			);

		}

		

		/**
		 * Remove Group Activity Stream Message
		 * @since 0.1
		 * @version 1.0
		 */

		public function remove_group_activity_feed_message($group_id) {

			$user_id = get_current_user_id();

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			 // Execute
			 $this->core->add_creds(
				'delete_activity_feed_message',
				$user_id,
				$this->prefs['remove_group_activity_feed_message']['creds'],
				$this->prefs['remove_group_activity_feed_message']['log'],
				$group_id,
					'bp_delete_activity_feed',
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
		 * Preferences
		 * @since 0.1
		 * @version 1.3
		 */
		public function preferences() {

			$prefs = $this->prefs;

?>

<div class="hook-instance">
	<h3><?php _e( 'Group Creation', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'create_group', 'creds' ) )); ?>"><?php echo esc_attr($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'create_group', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'create_group', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['create_group']['creds'] )); ?>" class="form-control" />
				<!-- <span class="description"><?php echo esc_html($this->core->template_tags_general( __( 'If you use a negative value and the user does not have enough %_plural%, the "Create Group" button will be disabled.', 'mycred' ) )); ?></span> -->
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'create_group', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'create_group', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'create', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['create_group']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="hook-instance">
	<h3><?php _e( 'Joining Groups', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'join_group', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'join_group', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'join_group', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['join_group']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'join_group', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'join_group', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'join_group', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['join_group']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>





<div class="hook-instance">
	<h3><?php _e( 'Leaving Groups', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'leave_group', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'leave_group', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'leave_group', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['leave_group']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'leave_group', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'leave_group', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'leave_group', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['leave_group']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
			</div>
		</div>
	</div>
</div>





<div class="hook-instance">
	<h3><?php _e( 'Accepted on Private Group', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'accepted_on_private_group', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'accepted_on_private_group', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'accepted_on_private_group', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['accepted_on_private_group']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'accepted_on_private_group', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'accepted_on_private_group', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'accepted_on_private_group', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['accepted_on_private_group']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'Group Invitations', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'group_invitations', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'group_invitations', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'group_invitations', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['group_invitations']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'group_invitations', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'group_invitations', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'group_invitations', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['group_invitations']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'Publish Group Activity Stream Message', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'publish_group_activity_feed_message', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'publish_group_activity_feed_message', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'publish_group_activity_feed_message', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['publish_group_activity_feed_message']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'publish_group_activity_feed_message', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'publish_group_activity_feed_message', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'publish_group_activity_feed_message', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['publish_group_activity_feed_message']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'Remove Group Activity Stream Message', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'remove_group_activity_feed_message', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'remove_group_activity_feed_message', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'remove_group_activity_feed_message', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['remove_group_activity_feed_message']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'remove_group_activity_feed_message', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'remove_group_activity_feed_message', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'remove_group_activity_feed_message', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['remove_group_activity_feed_message']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'Group Promotions', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'group_promotions', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'group_promotions', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'group_promotions', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['group_promotions']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'group_promotions', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'group_promotions', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'group_promotions', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['group_promotions']['log'] ); ?>" class="form-control" />
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


			$data['create_group']['creds'] = ( !empty( $data['create_group']['creds'] ) ) ? floatval( $data['create_group']['creds'] ) : $this->defaults['create_group']['creds'];

	  	$data['create_group']['log'] = ( !empty( $data['create_group']['log'] ) ) ? sanitize_text_field( $data['create_group']['log'] ) : $this->defaults['create_group']['log'];


	  	$data['join_group']['creds'] = ( !empty( $data['join_group']['creds'] ) ) ? floatval( $data['join_group']['creds'] ) : $this->defaults['join_group']['creds'];

	  	$data['join_group']['log'] = ( !empty( $data['join_group']['log'] ) ) ? sanitize_text_field( $data['join_group']['log'] ) : $this->defaults['join_group']['log'];



	  	$data['leave_group']['creds'] = ( !empty( $data['leave_group']['creds'] ) ) ? floatval( $data['leave_group']['creds'] ) : $this->defaults['leave_group']['creds'];

	  	$data['leave_group']['log'] = ( !empty( $data['leave_group']['log'] ) ) ? sanitize_text_field( $data['leave_group']['log'] ) : $this->defaults['leave_group']['log'];


	  		$data['accepted_on_private_group']['creds'] = ( !empty( $data['accepted_on_private_group']['creds'] ) ) ? floatval( $data['accepted_on_private_group']['creds'] ) : $this->defaults['accepted_on_private_group']['creds'];

	  	$data['accepted_on_private_group']['log'] = ( !empty( $data['accepted_on_private_group']['log'] ) ) ? sanitize_text_field( $data['accepted_on_private_group']['log'] ) : $this->defaults['accepted_on_private_group']['log'];



	  	$data['group_invitations']['creds'] = ( !empty( $data['group_invitations']['creds'] ) ) ? floatval( $data['group_invitations']['creds'] ) : $this->defaults['group_invitations']['creds'];

	  	$data['group_invitations']['log'] = ( !empty( $data['group_invitations']['log'] ) ) ? sanitize_text_field( $data['group_invitations']['log'] ) : $this->defaults['group_invitations']['log'];



	  	 	$data['group_promotions']['creds'] = ( !empty( $data['group_promotions']['creds'] ) ) ? floatval( $data['group_promotions']['creds'] ) : $this->defaults['group_promotions']['creds'];

	  	$data['group_promotions']['log'] = ( !empty( $data['group_promotions']['log'] ) ) ? sanitize_text_field( $data['group_promotions']['log'] ) : $this->defaults['group_promotions']['log'];



	  	$data['publish_group_activity_feed_message']['creds'] = ( !empty( $data['publish_group_activity_feed_message']['creds'] ) ) ? floatval( $data['publish_group_activity_feed_message']['creds'] ) : $this->defaults['publish_group_activity_feed_message']['creds'];

	  	$data['publish_group_activity_feed_message']['log'] = ( !empty( $data['publish_group_activity_feed_message']['log'] ) ) ? sanitize_text_field( $data['publish_group_activity_feed_message']['log'] ) : $this->defaults['publish_group_activity_feed_message']['log'];

	  	

	  	  	$data['remove_group_activity_feed_message']['creds'] = ( !empty( $data['remove_group_activity_feed_message']['creds'] ) ) ? floatval( $data['remove_group_activity_feed_message']['creds'] ) : $this->defaults['remove_group_activity_feed_message']['creds'];

	  	$data['remove_group_activity_feed_message']['log'] = ( !empty( $data['remove_group_activity_feed_message']['log'] ) ) ? sanitize_text_field( $data['remove_group_activity_feed_message']['log'] ) : $this->defaults['remove_group_activity_feed_message']['log'];


			if ( isset( $data['group_invitations']['limit'] ) && isset( $data['group_invitations']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['group_invitations']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['group_invitations']['limit'] = $limit . '/' . $data['group_invitations']['limit_by'];
				unset( $data['group_invitations']['limit_by'] );
			}


			

			if ( isset( $data['group_promotions']['limit'] ) && isset( $data['group_promotions']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['group_promotions']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['group_promotions']['limit'] = $limit . '/' . $data['group_promotions']['limit_by'];
				unset( $data['group_promotions']['limit_by'] );
			}



			if ( isset( $data['publish_group_activity_feed_message']['limit'] ) && isset( $data['publish_group_activity_feed_message']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['publish_group_activity_feed_message']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['publish_group_activity_feed_message']['limit'] = $limit . '/' . $data['publish_group_activity_feed_message']['limit_by'];
				unset( $data['publish_group_activity_feed_message']['limit_by'] );
			}


			if ( isset( $data['remove_group_activity_feed_message']['limit'] ) && isset( $data['remove_group_activity_feed_message']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['remove_group_activity_feed_message']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['remove_group_activity_feed_message']['limit'] = $limit . '/' . $data['remove_group_activity_feed_message']['limit_by'];
				unset( $data['remove_group_activity_feed_message']['limit_by'] );
			}




			if ( isset( $data['create_group']['limit'] ) && isset( $data['create_group']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['create_group']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['create_group']['limit'] = $limit . '/' . $data['create_group']['limit_by'];
				unset( $data['create_group']['limit_by'] );
			}

		

			if ( isset( $data['join_group']['limit'] ) && isset( $data['join_group']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['join_group']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['join_group']['limit'] = $limit . '/' . $data['join_group']['limit_by'];
				unset( $data['join_group']['limit_by'] );
			}

			

			if ( isset( $data['accepted_on_private_group']['limit'] ) && isset( $data['accepted_on_private_group']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['accepted_on_private_group']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['accepted_on_private_group']['limit'] = $limit . '/' . $data['accepted_on_private_group']['limit_by'];
				unset( $data['accepted_on_private_group']['limit_by'] );
			}
		

				if ( isset( $data['leave_group']['limit'] ) && isset( $data['leave_group']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['leave_group']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['leave_group']['limit'] = $limit . '/' . $data['leave_group']['limit_by'];
				unset( $data['leave_group']['limit_by'] );
			}

		
// 


			if ( isset( $data['new_topic']['limit'] ) && isset( $data['new_topic']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_topic']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_topic']['limit'] = $limit . '/' . $data['new_topic']['limit_by'];
				unset( $data['new_topic']['limit_by'] );
			}

			if ( isset( $data['edit_topic']['limit'] ) && isset( $data['edit_topic']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['edit_topic']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['edit_topic']['limit'] = $limit . '/' . $data['edit_topic']['limit_by'];
				unset( $data['edit_topic']['limit_by'] );
			}

			if ( isset( $data['new_post']['limit'] ) && isset( $data['new_post']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_post']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_post']['limit'] = $limit . '/' . $data['new_post']['limit_by'];
				unset( $data['new_post']['limit_by'] );
			}

			if ( isset( $data['edit_post']['limit'] ) && isset( $data['edit_post']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['edit_post']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['edit_post']['limit'] = $limit . '/' . $data['edit_post']['limit_by'];
				unset( $data['edit_post']['limit_by'] );
			}

			if ( isset( $data['join']['limit'] ) && isset( $data['join']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['join']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['join']['limit'] = $limit . '/' . $data['join']['limit_by'];
				unset( $data['join']['limit_by'] );
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

			if ( isset( $data['comments']['limit'] ) && isset( $data['comments']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['comments']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['comments']['limit'] = $limit . '/' . $data['comments']['limit_by'];
				unset( $data['comments']['limit_by'] );
			}

			return $data;

		}

}