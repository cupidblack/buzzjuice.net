<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * myCRED_buddyboss_Activity_Stream_Events_Hook class
 * Creds for forum events updates
 * 
 * 
 */
class myCRED_buddyboss_Activity_Stream_Events_Hook extends myCRED_Hook {

	 /**
	 * Construct
	 */

	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_activity_stream_events',
			'defaults' => array(
				'publish_activity_post'  => array(
					'creds'     => 0,
					'log'       => '%plural% for publishing an activity post'                 
				),
				'remove_activity_post'  => array(
					'creds'     => 0,
					'log'       => '%plural% for removing an activity post'                 
				),
				'reply_activity_post'  => array(
					'creds'     => 0,
					'log'       => '%plural% for replying to an activity post'                 
				),
				'like_activity_post'  => array(
					'creds'     => 0,
					'log'       => '%plural% for liking an activity post'                 
				),
				'unlike_activity_stream_item'  => array(
					'creds'     => 0,
					'log'       => '%plural% for unliking an activity stream item'                 
				),

				'get_like_activity_post'  => array(
					'creds'     => 0,
					'log'       => '%plural% for getting a like on an activity post'                 
				),

				'get_unlike_activity_stream_item'  => array(
					'creds'     => 0,
					'log'       => '%plural% for getting an unlike on an activity stream item'                 
				),
			)
		), $hook_prefs, $type );
	}


		/**
		 * Run
		 * 
		 * 
		 */
	public function run() {

		add_action( 'bp_activity_posted_update', array( $this, 'bp_activity_posted_update' ), 10, 3 );
		add_action( 'bp_before_activity_delete', array( $this, 'mycred_bp_delete_activity' ), 10, 1 );
		add_action( 'bp_activity_comment_posted', array( $this, 'mycred_bp_new_activity_comment' ), 10, 3 );
		add_action( 'bp_activity_add_user_favorite', array( $this, 'mycred_bp_favorite_activity_post' ), 10, 2 );
		add_action( 'bp_activity_remove_user_favorite', array( $this, 'mycred_bp_remove_favorite_activity' ), 10, 2 );

		add_action( 'bp_set_member_type', array( $this, 'mycred_bp_set_member_type' ), 10, 3 );
	}


	public function mycred_bp_set_member_type( $user_id, $member_type, $append ) {

		return $member_type;
	}

	public function mycred_bp_remove_favorite_activity( $activity_id, $user_id ) {


		// Check if user should be excluded
		if ( $this->core->exclude_user( $user_id ) ) {
			return;
		}

			// Limit
		if ( $this->over_hook_limit( 'unlike_activity_stream_item', 'user_unlike_activity_stream_item' ) ) {
			return;
		}

			// Make sure this is unique event
		if ( $this->core->has_entry( 'user_unlike_activity_stream_item', $activity_id, $user_id ) ) {
			return;
		}

		$user_member_type = bp_get_member_type( $user_id );

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}

	  // Execute
		$this->core->add_creds(
			'user_unlike_activity_stream_item',
			$user_id,
			$this->prefs['unlike_activity_stream_item']['creds'],
			$this->prefs['unlike_activity_stream_item']['log'],
			$activity_id,
			'bp_unlike_activity_stream_item',
			$this->mycred_type
		);

		if ( class_exists( 'BP_Activity_Activity' ) ) {

			$activity = new BP_Activity_Activity( $activity_id );

			// Check if user should be excluded
			if ( $this->core->exclude_user( $user_id ) ) {
				return;
			}

			 // Limit
			if ( $this->over_hook_limit( 'get_unlike_activity_stream_item', 'user_get_unlike_activity_stream_item' ) ) {
				return;
			}

				   // Make sure this is unique event
			if ( $this->core->has_entry( 'user_get_unlike_activity_stream_item', $activity_id, $activity->user_id ) ) {
				return;
			}

			$activity_member_type = bp_get_member_type( $activity->user_id );

			if ($activity_member_type === $this->core->exclude['by_roles'] ) {
				return;
			}

			 // Execute
			$this->core->add_creds(
				'user_get_unlike_activity_stream_item',
				$activity->user_id,
				$this->prefs['get_unlike_activity_stream_item']['creds'],
				$this->prefs['get_unlike_activity_stream_item']['log'],
				$activity_id,
				'bp_get_unlike_activity_stream_item',
				$this->mycred_type
			);

		}
	}

	public function mycred_bp_favorite_activity_post( $activity_id, $user_id ) {


	  // Check if user should be excluded
		if ( $this->core->exclude_user( $user_id ) ) {
			return;
		}

	  // Limit
		if ( $this->over_hook_limit( 'like_activity_post', 'user_like_activity_post' ) ) {
			return;
		}

			// Make sure this is unique event
		if ( $this->core->has_entry( 'user_like_activity_post', $activity_id, $user_id ) ) {
			return;
		}

		$user_member_type = bp_get_member_type( $user_id );

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}

	  // Execute
		$this->core->add_creds(
			'user_like_activity_post',
			$user_id,
			$this->prefs['like_activity_post']['creds'],
			$this->prefs['like_activity_post']['log'],
			$activity_id,
			'bp_like_activity_post',
			$this->mycred_type
		);


		if ( class_exists( 'BP_Activity_Activity' ) ) {

			$activity = new BP_Activity_Activity( $activity_id );

			// Check if user should be excluded
			if ( $this->core->exclude_user( $user_id ) ) {
				return;
			}

			 // Limit
			if ( $this->over_hook_limit( 'get_like_activity_post', 'user_get_like_activity_post' ) ) {
				return;
			}

				   // Make sure this is unique event
			if ( $this->core->has_entry( 'user_get_like_activity_post', $activity_id, $activity->user_id ) ) {
				return;
			}

			$activity_member_type = bp_get_member_type( $activity->user_id );

			if ($activity_member_type === $this->core->exclude['by_roles'] ) {
				return;
			}

			 // Execute
			$this->core->add_creds(
				'user_get_like_activity_post',
				$activity->user_id,
				$this->prefs['get_like_activity_post']['creds'],
				$this->prefs['get_like_activity_post']['log'],
				$activity_id,
				'bp_get_like_activity_post',
				$this->mycred_type
			);

		}
	}

	public function mycred_bp_new_activity_comment( $comment_id, $args, $activity ) {


		$user_id = bp_loggedin_user_id();

		// Limit
		if ( $this->over_hook_limit( 'reply_activity_post', 'user_reply_activity_post' ) ) {
			return;
		}

			// Make sure this is unique event
		if ( $this->core->has_entry( 'user_reply_activity_post', $comment_id, $user_id ) ) {
			return;
		}

		$user_member_type = bp_get_member_type( $user_id );

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}

			// Execute
		$this->core->add_creds(
			'user_reply_activity_post',
			$user_id,
			$this->prefs['reply_activity_post']['creds'],
			$this->prefs['reply_activity_post']['log'],
			$comment_id,
			'bp_reply_activity_post',
			$this->mycred_type
		);
	}

	public function mycred_bp_delete_activity( $args ) {

			
		if ( ! isset( $args['id'] ) ) {
			return;
		}

		if ( class_exists( 'BP_Activity_Activity' ) ) {
			$activity = new BP_Activity_Activity( $args['id'] );

			if ( ! $activity ) {
				return;
			}

			if ( $activity->component === 'groups' ) {

				$group_id  = $activity->item_id;

			// Check if user should be excluded
				if ( $this->core->exclude_user( $activity->user_id ) ) {
					return;
				}

				// Limit
				if ( $this->over_hook_limit( 'remove_activity_post', 'user_remove_activity_post' ) ) {
					return;
				}


			 // Make sure this is unique event
				if ( $this->core->has_entry( 'user_remove_activity_post', $group_id, $activity->user_id ) ) {
					return;
				}

				$user_member_type = bp_get_member_type( $activity->user_id);

				if ($user_member_type === $this->core->exclude['by_roles'] ) {
					return;
				}


				// Trigger delete group activity stream message
				$this->core->add_creds(
					'user_remove_activity_post',
					$activity->user_id,
					$this->prefs['remove_activity_post']['creds'],
					$this->prefs['remove_activity_post']['log'],
					$group_id,
					'bp_remove_activity_post',
					$this->mycred_type
				);


			} else {

			  // Check if user should be excluded
				if ( $this->core->exclude_user( $activity->user_id ) ) {
					return;
				}

		   // Limit
				if ( $this->over_hook_limit( 'remove_activity_post', 'user_remove_activity_post' ) ) {
					return;
				}


		   // Make sure this is unique event
				if ( $this->core->has_entry( 'user_remove_activity_post', $activity->id, $activity->user_id ) ) {
					return;
				}

				$activity_member_type = bp_get_member_type( $activity->user_id);

				if ($activity_member_type === $this->core->exclude['by_roles'] ) {
					return;
				}


		   // Trigger delete activity stream message
				$this->core->add_creds(
					'user_remove_activity_post',
					$activity->user_id,
					$this->prefs['remove_activity_post']['creds'],
					$this->prefs['remove_activity_post']['log'],
					$activity->id,
					'bp_remove_activity_post',
					$this->mycred_type
				);


			}
		}
	}


	public function bp_activity_posted_update( $content, $user_id, $activity_id ) {

		// Check if user should be excluded
		if ( $this->core->exclude_user( $user_id ) ) {
			return;
		}

		// Limit
		if ( $this->over_hook_limit( 'publish_activity_post', 'user_publish_activity_post' ) ) {
			return;
		}

		// Make sure this is unique event
		if ( $this->core->has_entry( 'user_publish_activity_post', $activity_id, $user_id ) ) {
			return;
		}

		$user_member_type = bp_get_member_type( $user_id);

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}

			// Execute
		$this->core->add_creds(
			'user_publish_activity_post',
			$user_id,
			$this->prefs['publish_activity_post']['creds'],
			$this->prefs['publish_activity_post']['log'],
			$activity_id,
			'bp_publish_activity_post',
			$this->mycred_type
		);
	}

	 /**
		 * Preferences
		 * 
		 * 
		 */
	public function preferences() {

		$prefs = $this->prefs;

		?>

		 <div class="hook-instance">
			 <h3><?php esc_html_e( 'Publish an Activity Post ', 'mycred-toolkit' ); ?></h3>
			 <div class="row">
				 <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'publish_activity_post', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'publish_activity_post', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'publish_activity_post', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['publish_activity_post']['creds'] )); ?>" class="form-control" />
					 </div>
				 </div>
				 <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'publish_activity_post', 'log' ) ) ); ?>">
					   <?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?>
					</label>
					<input 
						type="text" 
						name="<?php echo esc_attr( $this->field_name( array( 'publish_activity_post', 'log' ) ) ); ?>" 
						id="<?php echo esc_attr( $this->field_id( array( 'publish_activity_post', 'log' ) ) ); ?>" 
						placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" 
						value="<?php echo esc_attr( $prefs['publish_activity_post']['log'] ); ?>" 
						class="form-control" />
						 <span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
					 </div>
				 </div>
			 </div>
		 </div>
		 <div class="hook-instance">
			 <h3><?php esc_html_e( 'Remove an Activity Post ', 'mycred-toolkit' ); ?></h3>
			 <div class="row">
				 <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'remove_activity_post', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'remove_activity_post', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'remove_activity_post', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['remove_activity_post']['creds'] )); ?>" class="form-control" />
					 </div>
				 </div>
				 <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr( $this->field_id( array( 'remove_activity_post', 'log' ) ) ); ?>">
						   <?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?>
						</label>
						<input 
							type="text" 
							name="<?php echo esc_attr( $this->field_name( array( 'remove_activity_post', 'log' ) ) ); ?>" 
							id="<?php echo esc_attr( $this->field_id( array( 'remove_activity_post', 'log' ) ) ); ?>" 
							placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" 
							value="<?php echo esc_attr( $prefs['remove_activity_post']['log'] ); ?>" 
							class="form-control" />

						 <span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
					 </div>
				 </div>
			 </div>
		 </div>
		 <div class="hook-instance">
			 <h3><?php esc_html_e( 'Reply to an Activity Post ', 'mycred-toolkit' ); ?></h3>
			 <div class="row">
				 <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'reply_activity_post', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'reply_activity_post', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'reply_activity_post', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['reply_activity_post']['creds'] )); ?>" class="form-control" />
					 </div>
				 </div>
				 <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'reply_activity_post', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'reply_activity_post', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'reply_activity_post', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['reply_activity_post']['log'] ); ?>" class="form-control" />
						 <span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
					 </div>
				 </div>
			 </div>
		 </div>
		 <div class="hook-instance">
			 <h3><?php esc_html_e( 'Like Activity Post ', 'mycred-toolkit' ); ?></h3>
			 <div class="row">
				 <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'like_activity_post', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'like_activity_post', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'like_activity_post', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['like_activity_post']['creds'] )); ?>" class="form-control" />
					 </div>
				 </div>
				 <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'like_activity_post', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'like_activity_post', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'like_activity_post', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['like_activity_post']['log'] ); ?>" class="form-control" />
						 <span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
					 </div>
				 </div>
			 </div>
		 </div>


		 <div class="hook-instance">
			 <h3><?php esc_html_e( 'Unlike an activity stream item ', 'mycred-toolkit' ); ?></h3>
			 <div class="row">
				 <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'unlike_activity_stream_item', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'unlike_activity_stream_item', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'unlike_activity_stream_item', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['unlike_activity_stream_item']['creds'] )); ?>" class="form-control" />
					 </div>
				 </div>
				 <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'unlike_activity_stream_item', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'unlike_activity_stream_item', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'unlike_activity_stream_item', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['unlike_activity_stream_item']['log'] ); ?>" class="form-control" />
						 <span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
					 </div>
				 </div>
			 </div>
		 </div>



		 <div class="hook-instance">
			 <h3><?php esc_html_e( 'Gets a Like on Activity Post ', 'mycred-toolkit' ); ?></h3>
			 <div class="row">
				 <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'get_like_activity_post', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'get_like_activity_post', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_like_activity_post', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['get_like_activity_post']['creds'] )); ?>" class="form-control" />
					 </div>
				 </div>
				 <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'get_like_activity_post', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'get_like_activity_post', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_like_activity_post', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['get_like_activity_post']['log'] ); ?>" class="form-control" />
						 <span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
					 </div>
				 </div>
			 </div>
		 </div>




		 <div class="hook-instance">
			 <h3><?php esc_html_e( 'Gets Unlike on an activity stream item ', 'mycred-toolkit' ); ?></h3>
			 <div class="row">
				 <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'get_unlike_activity_stream_item', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'get_unlike_activity_stream_item', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_unlike_activity_stream_item', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['get_unlike_activity_stream_item']['creds'] )); ?>" class="form-control" />
					 </div>
				 </div>
				 <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					 <div class="form-group">
						 <label for="<?php echo esc_attr($this->field_id( array( 'get_unlike_activity_stream_item', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
						 <input type="text" name="<?php echo esc_attr($this->field_name( array( 'get_unlike_activity_stream_item', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_unlike_activity_stream_item', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['get_unlike_activity_stream_item']['log'] ); ?>" class="form-control" />
						 <span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
					 </div>
				 </div>
			 </div>
		 </div>

		<?php
	}

		/**
		 * Sanitise Preferences
		 * 
		 * 
		 */
	public function sanitise_preferences( $data ) {

		$data['publish_activity_post']['creds'] = ( !empty( $data['publish_activity_post']['creds'] ) ) ? floatval( $data['publish_activity_post']['creds'] ) : $this->defaults['publish_activity_post']['creds'];
		$data['publish_activity_post']['log'] = ( !empty( $data['publish_activity_post']['log'] ) ) ? sanitize_text_field( $data['publish_activity_post']['log'] ) : $this->defaults['publish_activity_post']['log'];


		if ( isset( $data['publish_activity_post']['limit'] ) && isset( $data['publish_activity_post']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['publish_activity_post']['limit'] );
			if ( $limit == '' ) {
				$limit = 0;
			}
			$data['publish_activity_post']['limit'] = $limit . '/' . $data['publish_activity_post']['limit_by'];
			unset( $data['publish_activity_post']['limit_by'] );
		}


		$data['remove_activity_post']['creds'] = ( !empty( $data['remove_activity_post']['creds'] ) ) ? floatval( $data['remove_activity_post']['creds'] ) : $this->defaults['remove_activity_post']['creds'];
		$data['remove_activity_post']['log'] = ( !empty( $data['remove_activity_post']['log'] ) ) ? sanitize_text_field( $data['remove_activity_post']['log'] ) : $this->defaults['remove_activity_post']['log'];


		if ( isset( $data['remove_activity_post']['limit'] ) && isset( $data['remove_activity_post']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['remove_activity_post']['limit'] );
			if ( $limit == '' ) {
				$limit = 0;
			}
			$data['remove_activity_post']['limit'] = $limit . '/' . $data['remove_activity_post']['limit_by'];
			unset( $data['remove_activity_post']['limit_by'] );
		}

			

		$data['reply_activity_post']['creds'] = ( !empty( $data['reply_activity_post']['creds'] ) ) ? floatval( $data['reply_activity_post']['creds'] ) : $this->defaults['reply_activity_post']['creds'];
		$data['reply_activity_post']['log'] = ( !empty( $data['reply_activity_post']['log'] ) ) ? sanitize_text_field( $data['reply_activity_post']['log'] ) : $this->defaults['reply_activity_post']['log'];


		if ( isset( $data['reply_activity_post']['limit'] ) && isset( $data['reply_activity_post']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['reply_activity_post']['limit'] );
			if ( $limit == '' ) {
				$limit = 0;
			}
			$data['reply_activity_post']['limit'] = $limit . '/' . $data['reply_activity_post']['limit_by'];
			unset( $data['reply_activity_post']['limit_by'] );
		}

			
		$data['like_activity_post']['creds'] = ( !empty( $data['like_activity_post']['creds'] ) ) ? floatval( $data['like_activity_post']['creds'] ) : $this->defaults['like_activity_post']['creds'];
		$data['like_activity_post']['log'] = ( !empty( $data['like_activity_post']['log'] ) ) ? sanitize_text_field( $data['like_activity_post']['log'] ) : $this->defaults['like_activity_post']['log'];


		if ( isset( $data['like_activity_post']['limit'] ) && isset( $data['like_activity_post']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['like_activity_post']['limit'] );
			if ( $limit == '' ) {
				$limit = 0;
			}
			$data['like_activity_post']['limit'] = $limit . '/' . $data['like_activity_post']['limit_by'];
			unset( $data['like_activity_post']['limit_by'] );
		}


		$data['unlike_activity_stream_item']['creds'] = ( !empty( $data['unlike_activity_stream_item']['creds'] ) ) ? floatval( $data['unlike_activity_stream_item']['creds'] ) : $this->defaults['unlike_activity_stream_item']['creds'];
		$data['unlike_activity_stream_item']['log'] = ( !empty( $data['unlike_activity_stream_item']['log'] ) ) ? sanitize_text_field( $data['unlike_activity_stream_item']['log'] ) : $this->defaults['unlike_activity_stream_item']['log'];


		if ( isset( $data['unlike_activity_stream_item']['limit'] ) && isset( $data['unlike_activity_stream_item']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['unlike_activity_stream_item']['limit'] );
			if ( $limit == '' ) {
				$limit = 0;
			}
			$data['unlike_activity_stream_item']['limit'] = $limit . '/' . $data['unlike_activity_stream_item']['limit_by'];
			unset( $data['unlike_activity_stream_item']['limit_by'] );
		}


		$data['get_like_activity_post']['creds'] = ( !empty( $data['get_like_activity_post']['creds'] ) ) ? floatval( $data['get_like_activity_post']['creds'] ) : $this->defaults['get_like_activity_post']['creds'];
		$data['get_like_activity_post']['log'] = ( !empty( $data['get_like_activity_post']['log'] ) ) ? sanitize_text_field( $data['get_like_activity_post']['log'] ) : $this->defaults['get_like_activity_post']['log'];


		if ( isset( $data['get_like_activity_post']['limit'] ) && isset( $data['get_like_activity_post']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['get_like_activity_post']['limit'] );
			if ( $limit == '' ) {
				$limit = 0;
			}
			$data['get_like_activity_post']['limit'] = $limit . '/' . $data['get_like_activity_post']['limit_by'];
			unset( $data['get_like_activity_post']['limit_by'] );
		}



		$data['get_unlike_activity_stream_item']['creds'] = ( !empty( $data['get_unlike_activity_stream_item']['creds'] ) ) ? floatval( $data['get_unlike_activity_stream_item']['creds'] ) : $this->defaults['get_unlike_activity_stream_item']['creds'];
		$data['get_unlike_activity_stream_item']['log'] = ( !empty( $data['get_unlike_activity_stream_item']['log'] ) ) ? sanitize_text_field( $data['get_unlike_activity_stream_item']['log'] ) : $this->defaults['get_unlike_activity_stream_item']['log'];


		if ( isset( $data['get_unlike_activity_stream_item']['limit'] ) && isset( $data['get_unlike_activity_stream_item']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['get_unlike_activity_stream_item']['limit'] );
			if ( $limit == '' ) {
				$limit = 0;
			}
			$data['get_unlike_activity_stream_item']['limit'] = $limit . '/' . $data['get_unlike_activity_stream_item']['limit_by'];
			unset( $data['get_unlike_activity_stream_item']['limit_by'] );
		}

		return $data;
	}
}