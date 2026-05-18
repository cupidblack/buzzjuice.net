<?php
if ( ! defined( 'MYCRED_buddyboss_SLUG' ) ) exit;

/**
 * myCRED_buddyboss_Forum_Events_Hook class
 * Creds for forum events updates
 * @since 0.1
 * @version 1.3
 */

class myCRED_buddyboss_Forum_Events_Hook extends myCRED_Hook {

    /**
	 * Construct
	 */

    public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_forum_events',
			'defaults' => array(
                'user_new_forum'  => array(
                    'creds'     => 0,
                    'log'       => '%plural% for new forum'                 
                ),
                'user_new_topic'  => array(
                    'creds'     => 0,
                    'log'       => '%plural% for new topic'     
                ),
                'user_add_favorite'   => array(
                    'creds'         => 0,
                    'log'           => '%plural% for giving favorites to a topic',
                    
                ),
				'user_delete_topic'   => array(
                    'creds'         => -1,
                    'log'           => '%plural% for deleting a topic',
                    
                ),
				'user_new_reply'   => array(
                    'creds'         => 0,
                    'log'           => '%plural% for replying to a topic',
                    
                ),
				'user_delete_forum'   => array(
                    'creds'         => -1,
                    'log'           => '%plural% for deleting a forum',
                    
                ),
				'user_delete_reply'   => array(
                    'creds'         => -1,
                    'log'           => '%plural% for deleting reply',
                    
                ),
				'author_gets_favorite'   => array(
                    'creds'         => 0,
                    'log'           => '%plural% for getting favorite on a topic',  
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

            if ( $this->prefs['user_new_topic']['creds'] != 0 )
				add_action( 'bbp_new_topic',              array( $this, 'user_new_topic' ) );

            if ( $this->prefs['user_new_forum']['creds'] != 0 )
                add_action( 'bbp_new_forum',              array( $this, 'user_new_forum' ) );  

            if ( $this->prefs['user_add_favorite']['creds'] != 0 )
				add_action( 'bbp_add_user_favorite',      array( $this, 'user_add_favorite' ),20,2);
				
			if ( $this->prefs['user_delete_topic']['creds'] != 0 )
				add_action( 'bbp_delete_topic',      array( $this, 'user_delete_topic' ));

			if ( $this->prefs['user_new_reply']['creds'] != 0 )
				add_action( 'bbp_new_reply',      array( $this, 'user_new_reply' ), 20, 5);
				
			if ( $this->prefs['user_delete_forum']['creds'] != 0 )
			add_action( 'bbp_delete_forum',      array( $this, 'user_delete_forum' ));	

			if ( $this->prefs['user_delete_reply']['creds'] != 0 )
			add_action( 'bbp_delete_reply',      array( $this, 'user_delete_reply' ));

			if ( $this->prefs['author_gets_favorite']['creds'] != 0 )
			add_action( 'bbp_add_user_favorite',      array( $this, 'author_gets_favorite' ), 20,2);

			add_filter('bbp_get_forum_author_id', array( $this, 'bbp_get_forum_author_id' ), 20, 1);


        }

        /**
		 * New Group Forum Topic
		 * @since 0.1
		 * @version 1.1
		 */
		public function user_new_topic( $topic_id ) {

			global $bp;

			// Check if user should be excluded
			if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'user_new_topic', 'new_group_forum_topic' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'new_group_forum_topic', $topic_id, $bp->loggedin_user->id ) ) return;

			// Execute
			$this->core->add_creds(
				'new_group_forum_topic',
				$bp->loggedin_user->id,
				$this->prefs['user_new_topic']['creds'],
				$this->prefs['user_new_topic']['log'],
				$topic_id,
				'bp_ftopic',
				$this->mycred_type
			);

		}


        public function user_new_forum($forum) {

            global $bp;

			// Check if user should be excluded
			if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return;

            // Make sure this is unique event
			if ( $this->core->has_entry( 'new_forum', $forum['forum_id'], $bp->loggedin_user->id ) ) return;

            // Limit
			if ( $this->over_hook_limit( 'user_new_forum', 'new_forum' ) ) return;

            // Execute
			$this->core->add_creds(
				'new_forum',
				$bp->loggedin_user->id,
				$this->prefs['user_new_forum']['creds'],
				$this->prefs['user_new_forum']['log'],
				$forum['forum_id'],
				'bp_ftopic',
				$this->mycred_type
			);

        }


        /**
		 * Topic Added to Favorites
		 * @by Fee (http://wordpress.org/support/profile/wdfee)
		 * @since 1.1.1
		 * @version 1.5
		 */
		public function user_add_favorite( $user_id, $topic_id) {


			$forum_id = bbp_get_topic_forum_id( $topic_id );

            // Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;
			//Limit
			if ($this->over_hook_limit( 'user_add_favorite', 'fave_activity' ) ) return;
			// Make sure this is unique event
			if ( $this->core->has_entry( 'fave_activity', $topic_id, $user_id) ) return;

			// Execute
			$this->core->add_creds(
				'fave_activity',
				$user_id,
				$this->prefs['user_add_favorite']['creds'],
				$this->prefs['user_add_favorite']['log'],
				$topic_id,
				'bp_fav',
				$this->mycred_type
			);

		}



		public function bbp_get_forum_author_id( $author_id) {


			return (int) $author_id;

		}

		 /**
		 * Topic Author Gets Favorite
		 * @by Fee (http://wordpress.org/support/profile/wdfee)
		 * @since 1.1.1
		 * @version 1.5
		 */

		public function author_gets_favorite($user_id, $topic_id) {


			$forum_id = bbp_get_topic_forum_id( $topic_id );

			$topic_author = get_post_field( 'post_author', $topic_id );

			if( absint( $user_id ) === absint( $topic_author ) ) {
        		return;
    		}

           $topic_author_id = bbp_get_topic_author_id();

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;
			//Limit
			if ($this->over_hook_limit( 'author_gets_favorite', 'author_fave_activity' ) ) return;
			// Make sure this is unique event
			if ( $this->core->has_entry( 'author_fave_activity', $topic_author, $topic_id) ) return;

			// Execute
			$this->core->add_creds(
				'author_fave_activity',
				$topic_author,
				$this->prefs['author_gets_favorite']['creds'],
				$this->prefs['author_gets_favorite']['log'],
				$topic_id,
				'bp_author_fav',
				$this->mycred_type
			);

		}

		  /**
		 * Delete Topic
		 * @by Fee (http://wordpress.org/support/profile/wdfee)
		 * @since 1.1.1
		 * @version 1.5
		 */

		public function user_delete_topic($topic_id) {

			$user_id = get_current_user_id();

			$forum_author_id = $this->bbp_get_forum_author_id( $topic_id);

			$forum_id = bbp_get_topic_forum_id( $topic_id );

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			//Limit
			if ($this->over_hook_limit( 'user_delete_topic', 'delete_activity' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'delete_activity', $topic_id) ) return;

			// Execute
			$this->core->add_creds(
				'delete_activity',
				$forum_author_id,
				$this->prefs['user_delete_topic']['creds'],
				$this->prefs['user_delete_topic']['log'],
				$topic_id,
				'bp_delete',
				$this->mycred_type
			);

		}

		  /**
		 * New Reply
		 * @by Fee (http://wordpress.org/support/profile/wdfee)
		 * @since 1.1.1
		 * @version 1.5
		 */

		public function user_new_reply($reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author) {

			$user_id = get_current_user_id();

			// Check if user is excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			//Limit
			if ($this->over_hook_limit( 'user_new_reply', 'reply_activity' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'reply_activity', $topic_id) ) return;

			// Execute
			$this->core->add_creds(
				'reply_activity',
				$user_id,
				$this->prefs['user_new_reply']['creds'],
				$this->prefs['user_new_reply']['log'],
				$topic_id,
				'bp_reply',
				$this->mycred_type
			);

		}

		public function bbp_get_forum_post_type() {
    		return apply_filters( 'bbp_get_forum_post_type', bbpress()->forum_post_type );
		}

		

		 /**
		 * Delete Forum
		 * @by Fee (http://wordpress.org/support/profile/wdfee)
		 * @since 1.1.1
		 * @version 1.5
		 */

		public function user_delete_forum($forum_id) {


   			$forum_author_id = $this->bbp_get_forum_author_id( $forum_id); 

			$forums = bp_get_option( 'bp_dd_imported_forum_ids' );

			$user_id = get_current_user_id();

			// Check if user is excluded
			if ( $this->core->exclude_user( $forum_author_id ) ) return;

			//Limit
			if ($this->over_hook_limit( 'user_delete_forum', 'forum_delete_activity' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'forum_delete_activity', $forum_author_id) ) return;


			// Execute
			$this->core->add_creds(
				'forum_delete_activity',
				$forum_author_id,
				$this->prefs['user_delete_forum']['creds'],
				$this->prefs['user_delete_forum']['log'],
				$forum_author_id,
				'bp_delete_forum',
				$this->mycred_type
			);


		}

		 /**
		 * Delete Reply
		 * @by Fee (http://wordpress.org/support/profile/wdfee)
		 * @since 1.1.1
		 * @version 1.5
		 */

		public function user_delete_reply($reply_id) {

			$user_id = get_current_user_id();

			$forum_author_id = $this->bbp_get_forum_author_id( $reply_id);

			// Check if user is excluded
			if ( $this->core->exclude_user( $forum_author_id ) ) return;

			//Limit
			if ($this->over_hook_limit( 'user_delete_reply', 'delete_reply_activity' ) ) return;

			// Make sure this is unique event
			if ( $this->core->has_entry( 'delete_reply_activity', $reply_id) ) return;

			// Execute
			$this->core->add_creds(
					'delete_reply_activity',
					$forum_author_id,
					$this->prefs['user_delete_reply']['creds'],
					$this->prefs['user_delete_reply']['log'],
					$reply_id,
					'bp_user_delete_reply',
					$this->mycred_type
			);

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
	<h3><?php _e( 'New Forum ', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_new_forum', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_new_forum', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_new_forum', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_new_forum']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_new_forum', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_new_forum', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_new_forum', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_new_forum']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="hook-instance">
	<h3><?php _e( 'New Forum Topics', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_new_topic', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_new_topic', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_new_topic', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_new_topic']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_new_topic', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_new_topic', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_new_topic', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_new_topic']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>


<div class="hook-instance">
	<h3><?php _e( 'Favorite Topic', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_add_favorite', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_add_favorite', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_add_favorite', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_add_favorite']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_add_favorite', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_add_favorite', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_add_favorite', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_add_favorite']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="hook-instance">
	<h3><?php _e( 'Author Gets Favorite', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'author_gets_favorite', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'author_gets_favorite', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'author_gets_favorite', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['author_gets_favorite']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'author_gets_favorite', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'author_gets_favorite', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'author_gets_favorite', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['author_gets_favorite']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'New Reply', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_new_reply', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_new_reply', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_new_reply', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_new_reply']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_new_reply', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_new_reply', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_new_reply', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_new_reply']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>


<div class="hook-instance">
	<h3><?php _e( 'Delete Reply', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_delete_reply', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_delete_reply', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_delete_reply', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_delete_reply']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_delete_reply', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_delete_reply', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_delete_reply', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_delete_reply']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'Delete Topic', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_delete_topic', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_delete_topic', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_delete_topic', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_delete_topic']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_delete_topic', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_delete_topic', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_delete_topic', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_delete_topic']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo esc_html($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>



<div class="hook-instance">
	<h3><?php _e( 'Delete Forum', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_delete_forum', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_delete_forum', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_delete_forum', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_delete_forum']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
	
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_delete_forum', 'log' ) )); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_delete_forum', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_delete_forum', 'log' ) )); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['user_delete_forum']['log'] ); ?>" class="form-control" />
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


            
			if ( isset( $data['user_add_favorite']['limit'] ) && isset( $data['user_add_favorite']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['user_add_favorite']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['user_add_favorite']['limit'] = $limit . '/' . $data['user_add_favorite']['limit_by'];
				unset( $data['user_add_favorite']['limit_by'] );
			}

			$data['user_new_forum']['creds'] = ( !empty( $data['user_new_forum']['creds'] ) ) ? floatval( $data['user_new_forum']['creds'] ) : $this->defaults['user_new_forum']['creds'];

			

			$data['user_new_forum']['log'] = ( !empty( $data['user_new_forum']['log'] ) ) ? sanitize_text_field( $data['user_new_forum']['log'] ) : $this->defaults['user_new_forum']['log'];

		

			$data['user_delete_forum']['creds'] = ( !empty( $data['user_delete_forum']['creds'] ) ) ? floatval( $data['user_delete_forum']['creds'] ) : $this->defaults['user_delete_forum']['creds'];

			$data['user_delete_forum']['log'] = ( !empty( $data['user_delete_forum']['log'] ) ) ? sanitize_text_field( $data['user_delete_forum']['log'] ) : $this->defaults['user_delete_forum']['log'];



			$data['user_delete_reply']['creds'] = ( !empty( $data['user_delete_reply']['creds'] ) ) ? floatval( $data['user_delete_reply']['creds'] ) : $this->defaults['user_delete_reply']['creds'];

			$data['user_delete_reply']['log'] = ( !empty( $data['user_delete_reply']['log'] ) ) ? sanitize_text_field( $data['user_delete_reply']['log'] ) : $this->defaults['user_delete_reply']['log'];


			$data['author_gets_favorite']['creds'] = ( !empty( $data['author_gets_favorite']['creds'] ) ) ? floatval( $data['author_gets_favorite']['creds'] ) : $this->defaults['author_gets_favorite']['creds'];

			$data['author_gets_favorite']['log'] = ( !empty( $data['author_gets_favorite']['log'] ) ) ? sanitize_text_field( $data['author_gets_favorite']['log'] ) : $this->defaults['author_gets_favorite']['log'];


			$data['user_new_reply']['creds'] = ( !empty( $data['user_new_reply']['creds'] ) ) ? floatval( $data['user_new_reply']['creds'] ) : $this->defaults['user_new_reply']['creds'];

			$data['user_new_reply']['log'] = ( !empty( $data['user_new_reply']['log'] ) ) ? sanitize_text_field( $data['user_new_reply']['log'] ) : $this->defaults['user_new_reply']['log'];

		
				$data['user_new_topic']['creds'] = ( !empty( $data['user_new_topic']['creds'] ) ) ? floatval( $data['user_new_topic']['creds'] ) : $this->defaults['user_new_topic']['creds'];

			$data['user_new_topic']['log'] = ( !empty( $data['user_new_topic']['log'] ) ) ? sanitize_text_field( $data['user_new_topic']['log'] ) : $this->defaults['user_new_topic']['log'];


				$data['user_add_favorite']['creds'] = ( !empty( $data['user_add_favorite']['creds'] ) ) ? floatval( $data['user_add_favorite']['creds'] ) : $this->defaults['user_add_favorite']['creds'];

			$data['user_add_favorite']['log'] = ( !empty( $data['user_add_favorite']['log'] ) ) ? sanitize_text_field( $data['user_add_favorite']['log'] ) : $this->defaults['user_add_favorite']['log'];

			$data['user_delete_topic']['creds'] = ( !empty( $data['user_delete_topic']['creds'] ) ) ? floatval( $data['user_delete_topic']['creds'] ) : $this->defaults['user_delete_topic']['creds'];

			$data['user_delete_topic']['log'] = ( !empty( $data['user_delete_topic']['log'] ) ) ? sanitize_text_field( $data['user_delete_topic']['log'] ) : $this->defaults['user_delete_topic']['log'];


			if ( isset( $data['user_new_forum']['limit'] ) && isset( $data['user_new_forum']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['user_new_forum']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['user_new_forum']['limit'] = $limit . '/' . $data['user_new_forum']['limit_by'];
				unset( $data['user_new_forum']['limit_by'] );
			}
			

			if ( isset( $data['user_new_topic']['limit'] ) && isset( $data['user_new_topic']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['user_new_topic']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['user_new_topic']['limit'] = $limit . '/' . $data['user_new_topic']['limit_by'];
				unset( $data['user_new_topic']['limit_by'] );
			}

		

			if ( isset( $data['user_delete_topic']['limit'] ) && isset( $data['user_delete_topic']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['user_delete_topic']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['user_delete_topic']['limit'] = $limit . '/' . $data['user_delete_topic']['limit_by'];
				unset( $data['user_delete_topic']['limit_by'] );
			}

			

			if ( isset( $data['user_new_reply']['limit'] ) && isset( $data['user_new_reply']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['user_new_reply']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['user_new_reply']['limit'] = $limit . '/' . $data['user_new_reply']['limit_by'];
				unset( $data['user_new_reply']['limit_by'] );
			}

			

				if ( isset( $data['user_delete_forum']['limit'] ) && isset( $data['user_delete_forum']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['user_delete_forum']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['user_delete_forum']['limit'] = $limit . '/' . $data['user_delete_forum']['limit_by'];
				unset( $data['user_delete_forum']['limit_by'] );
			}

			


					if ( isset( $data['user_delete_reply']['limit'] ) && isset( $data['user_delete_reply']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['user_delete_reply']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['user_delete_reply']['limit'] = $limit . '/' . $data['user_delete_reply']['limit_by'];
				unset( $data['user_delete_reply']['limit_by'] );
			}

			

			if ( isset( $data['author_gets_favorite']['limit'] ) && isset( $data['author_gets_favorite']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['author_gets_favorite']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['author_gets_favorite']['limit'] = $limit . '/' . $data['author_gets_favorite']['limit_by'];
				unset( $data['author_gets_favorite']['limit_by'] );
			}

            return $data;

        }

}
