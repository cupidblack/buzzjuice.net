<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * myCRED_buddyboss_Email_Invites_Events_Hook class
 * Creds for forum events updates
 * @since 0.1
 * @version 1.3
 */

class myCRED_buddyboss_Email_Invites_Events_Hook extends myCRED_Hook {

	 /**
	 * Construct
	 */

	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_email_invites_events',
			'defaults' => array(
				'send_email_invite'  => array(
					'creds'     => 0,
					'log'       => '%plural% for sending an email invitation'                 
				),
				'register_from_email_invite'  => array(
					'creds'     => 0,
					'log'       => '%plural% for getting an invited user register from email invitation'                 
				),
				'email_invitation_account_activated'  => array(
					'creds'     => 0,
					'log'       => '%plural% for account from email invitation gets activated'                 
				),
				'email_invited_user_account_activated'  => array(
					'creds'     => 0,
					'log'       => '%plural% for getting an invited user account activated'                 
				),

				'get_email_inviter_registered'  => array(
					'creds'     => 0,
					'log'       => '%plural% for awarding user that sent the email invitation'                 
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

		if ($this->prefs['send_email_invite']['creds'] != 0 ) {

		  add_action( 'bp_member_invite_submit', array( $this, 'bp_member_invite_submit' ), 10, 2 );
		}

		if ($this->prefs['register_from_email_invite']['creds'] != 0 ) { 

		   add_action( 'bp_core_signup_user', array( $this, 'bp_invites_member_invite_mark_registered_user' ), 10, 5 );

		}

		

		if ($this->prefs['get_email_inviter_registered']['creds'] != 0 ) { 

		   add_action( 'bp_core_signup_user', array( $this, 'get_email_inviter_registered' ), 10, 5 );

		}



		if ( $this->prefs['email_invitation_account_activated']['creds'] != 0 ) {
			add_action( 'bp_invites_member_invite_activate_user', array( $this, 'account_activated_from_email_invitation' ), 10, 3 );
		}

		if ( $this->prefs['email_invited_user_account_activated']['creds'] != 0 ) {
			add_action( 'bp_invites_member_invite_activate_user', array( $this, 'invited_user_account_activated_from_email_invite' ), 10, 3 );

		}
	}

	public function get_email_inviter_registered( $user_id, $user_login, $user_password, $user_email, $usermeta ) {


		$email_invite = $this->invited_user_name();

		if (is_array($email_invite) || is_object($email_invite)) {
			foreach ($email_invite as $key => $value) {

				// Check if user should be excluded
				if ( $this->core->exclude_user( $value->post_author) ) {
return;
				}

				// Limit
				if ( $this->over_hook_limit( 'get_email_inviter_registered', 'user_get_email_inviter_registered' ) ) {
return;
				}

				// Make sure this is unique event
				if ( $this->core->has_entry( 'user_get_email_inviter_registered', $value->post_author, $value->post_author) ) {
return;
				}

				$user_member_type = bp_get_member_type( $value->post_author);

				if ($user_member_type === $this->core->exclude['by_roles'] ) {
								 return;
				}

					

				// // Execute
				$this->core->add_creds(
					'user_get_email_inviter_registered',
					$value->post_author,
					$this->prefs['get_email_inviter_registered']['creds'],
					$this->prefs['get_email_inviter_registered']['log'],
					$value->post_author,
					'bp_register_from_email_invite',
					$this->mycred_type
				);


			}

		}
	}

	public function invited_user_name() {
			$query_args = array( 
				'post_type'         =>   'bp-invite',
				'posts_per_page'    =>   -1,
				'orderby'           =>   'title',
				'order'             =>   'ASC',                                            
			);
		 
			$query_results = new WP_Query( $query_args );

			if ( !empty( $query_results->posts ) ) {
				return $query_results->posts;
			}

			return false;
	}



	public function invited_user_account_activated_from_email_invite( $user_id, $inviter_id, $post_id ) {

		// Check if user that sent the invitation should be excluded
		if ( $this->core->exclude_user( $user_id) ) {
return;
		}

		// // Limit
		if ( $this->over_hook_limit( 'email_invited_user_account_activated', 'user_email_invited_user_account_activated' ) ) {
return;
		}

		// // Make sure this is unique event
		if ( $this->core->has_entry( 'user_email_invited_user_account_activated', $post_id, $user_id) ) {
return;
		}

		$user_member_type = bp_get_member_type( $user_id);

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}

		// Execute
		$this->core->add_creds(
			'user_email_invited_user_account_activated',
			$user_id,
			$this->prefs['email_invited_user_account_activated']['creds'],
			$this->prefs['email_invited_user_account_activated']['log'],
			$post_id,
			'bp_email_invited_user_account_activated',
			$this->mycred_type
		);
	}


	public function account_activated_from_email_invitation( $user_id, $inviter_id, $post_id ) {


		// Check if invited user should be excluded
		if ( $this->core->exclude_user( $inviter_id) ) {
return;
		}

		// Limit
		if ( $this->over_hook_limit( 'email_invitation_account_activated', 'user_email_invitation_account_activated' ) ) {
return;
		}

		// Make sure this is unique event
		if ( $this->core->has_entry( 'user_email_invitation_account_activated', $post_id, $inviter_id) ) {
return;
		}

		$user_member_type = bp_get_member_type( $inviter_id);

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}


		// Execute
		$this->core->add_creds(
			'user_email_invitation_account_activated',
			$inviter_id,
			$this->prefs['email_invitation_account_activated']['creds'],
			$this->prefs['email_invitation_account_activated']['log'],
			$post_id,
			'bp_email_invitation_account_activated',
			$this->mycred_type
		);
	}

	public function bp_invites_member_invite_mark_registered_user( $user_id, $user_login, $user_password, $user_email, $usermeta ) {


		// Check if user should be excluded
		if ( $this->core->exclude_user( $user_id) ) {
return;
		}

		// Limit
		if ( $this->over_hook_limit( 'register_from_email_invite', 'user_register_from_email_invite' ) ) {
return;
		}

		// Make sure this is unique event
		if ( $this->core->has_entry( 'user_register_from_email_invite', $user_id, $user_id) ) {
return;
		}

		$user_member_type = bp_get_member_type( $user_id);

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}


		// Execute
		$this->core->add_creds(
			'user_register_from_email_invite',
			$user_id,
			$this->prefs['register_from_email_invite']['creds'],
			$this->prefs['register_from_email_invite']['log'],
			$user_id,
			'bp_register_from_email_invite',
			$this->mycred_type
		);
	}

	public function bp_member_invite_submit( $user_id, $post_id ) {

		// Check if user should be excluded
		if ( $this->core->exclude_user( $user_id ) ) {
return;
		}

		// Limit
		if ( $this->over_hook_limit( 'send_email_invite', 'user_send_email_invite' ) ) {
return;
		}

		// Make sure this is unique event
		if ( $this->core->has_entry( 'user_send_email_invite', $post_id, $user_id ) ) {
return;
		}

		$user_member_type = bp_get_member_type( $user_id);

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
			return;
		}


		// Execute
		$this->core->add_creds(
			'user_send_email_invite',
			$user_id,
			$this->prefs['send_email_invite']['creds'],
			$this->prefs['send_email_invite']['log'],
			$post_id,
			'bp_send_email_invite',
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
	<h3><?php esc_html_e( 'Send an email invitation ', 'mycred-toolkit' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'send_email_invite', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'send_email_invite', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'send_email_invite', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['send_email_invite']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'send_email_invite', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'send_email_invite', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'send_email_invite', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['send_email_invite']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>


	</div>
</div>


<div class="hook-instance">
	<h3><?php esc_html_e( 'Register from email invitation', 'mycred-toolkit' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'register_from_email_invite', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'register_from_email_invite', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'register_from_email_invite', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['register_from_email_invite']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'register_from_email_invite', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'register_from_email_invite', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'register_from_email_invite', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['register_from_email_invite']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>




<div class="hook-instance">
	<h3><?php esc_html_e( 'Account from email invitation gets activated', 'mycred-toolkit' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'email_invitation_account_activated', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'email_invitation_account_activated', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'email_invitation_account_activated', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['email_invitation_account_activated']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'email_invitation_account_activated', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'email_invitation_account_activated', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'email_invitation_account_activated', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['email_invitation_account_activated']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>




<div class="hook-instance">
	<h3><?php esc_html_e( 'Get an invited user account activated', 'mycred-toolkit' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'email_invited_user_account_activated', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'email_invited_user_account_activated', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'email_invited_user_account_activated', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['email_invited_user_account_activated']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'email_invited_user_account_activated', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'email_invited_user_account_activated', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'email_invited_user_account_activated', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['email_invited_user_account_activated']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
			</div>
		</div>
	</div>
</div>


<!-- get_email_inviter_registered -->

<div class="hook-instance">
	<h3><?php esc_html_e( 'Award User that sent the email invitation', 'mycred-toolkit' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'get_email_inviter_registered', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'get_email_inviter_registered', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_email_inviter_registered', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['get_email_inviter_registered']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'get_email_inviter_registered', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'get_email_inviter_registered', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'get_email_inviter_registered', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['get_email_inviter_registered']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
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


		$data['send_email_invite']['creds'] = ( !empty( $data['send_email_invite']['creds'] ) ) ? floatval( $data['send_email_invite']['creds'] ) : $this->defaults['send_email_invite']['creds'];
		$data['send_email_invite']['log'] = ( !empty( $data['send_email_invite']['log'] ) ) ? sanitize_text_field( $data['send_email_invite']['log'] ) : $this->defaults['send_email_invite']['log'];


		if ( isset( $data['send_email_invite']['limit'] ) && isset( $data['send_email_invite']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['send_email_invite']['limit'] );
			if ( $limit == '' ) {
$limit = 0;
			}
			$data['send_email_invite']['limit'] = $limit . '/' . $data['send_email_invite']['limit_by'];
			unset( $data['send_email_invite']['limit_by'] );
		}

			 

		$data['register_from_email_invite']['creds'] = ( !empty( $data['register_from_email_invite']['creds'] ) ) ? floatval( $data['register_from_email_invite']['creds'] ) : $this->defaults['register_from_email_invite']['creds'];
		$data['register_from_email_invite']['log'] = ( !empty( $data['register_from_email_invite']['log'] ) ) ? sanitize_text_field( $data['register_from_email_invite']['log'] ) : $this->defaults['register_from_email_invite']['log'];


		if ( isset( $data['register_from_email_invite']['limit'] ) && isset( $data['register_from_email_invite']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['register_from_email_invite']['limit'] );
			if ( $limit == '' ) {
$limit = 0;
			}
			$data['register_from_email_invite']['limit'] = $limit . '/' . $data['register_from_email_invite']['limit_by'];
			unset( $data['register_from_email_invite']['limit_by'] );
		}

				

		$data['email_invitation_account_activated']['creds'] = ( !empty( $data['email_invitation_account_activated']['creds'] ) ) ? floatval( $data['email_invitation_account_activated']['creds'] ) : $this->defaults['email_invitation_account_activated']['creds'];
		$data['email_invitation_account_activated']['log'] = ( !empty( $data['email_invitation_account_activated']['log'] ) ) ? sanitize_text_field( $data['email_invitation_account_activated']['log'] ) : $this->defaults['email_invitation_account_activated']['log'];


		if ( isset( $data['email_invitation_account_activated']['limit'] ) && isset( $data['email_invitation_account_activated']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['email_invitation_account_activated']['limit'] );
			if ( $limit == '' ) {
$limit = 0;
			}
			$data['email_invitation_account_activated']['limit'] = $limit . '/' . $data['email_invitation_account_activated']['limit_by'];
			unset( $data['email_invitation_account_activated']['limit_by'] );
		}

			


		$data['email_invited_user_account_activated']['creds'] = ( !empty( $data['email_invited_user_account_activated']['creds'] ) ) ? floatval( $data['email_invited_user_account_activated']['creds'] ) : $this->defaults['email_invited_user_account_activated']['creds'];
		$data['email_invited_user_account_activated']['log'] = ( !empty( $data['email_invited_user_account_activated']['log'] ) ) ? sanitize_text_field( $data['email_invited_user_account_activated']['log'] ) : $this->defaults['email_invited_user_account_activated']['log'];


		if ( isset( $data['email_invited_user_account_activated']['limit'] ) && isset( $data['email_invited_user_account_activated']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['email_invited_user_account_activated']['limit'] );
			if ( $limit == '' ) {
$limit = 0;
			}
			$data['email_invited_user_account_activated']['limit'] = $limit . '/' . $data['email_invited_user_account_activated']['limit_by'];
			unset( $data['email_invited_user_account_activated']['limit_by'] );
		}


		// get_email_inviter_registered

		$data['get_email_inviter_registered']['creds'] = ( !empty( $data['get_email_inviter_registered']['creds'] ) ) ? floatval( $data['get_email_inviter_registered']['creds'] ) : $this->defaults['get_email_inviter_registered']['creds'];
		$data['get_email_inviter_registered']['log'] = ( !empty( $data['get_email_inviter_registered']['log'] ) ) ? sanitize_text_field( $data['get_email_inviter_registered']['log'] ) : $this->defaults['get_email_inviter_registered']['log'];


		if ( isset( $data['get_email_inviter_registered']['limit'] ) && isset( $data['get_email_inviter_registered']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['get_email_inviter_registered']['limit'] );
			if ( $limit == '' ) {
$limit = 0;
			}
			$data['get_email_inviter_registered']['limit'] = $limit . '/' . $data['get_email_inviter_registered']['limit_by'];
			unset( $data['get_email_inviter_registered']['limit_by'] );
		}


		return $data;
	}
}