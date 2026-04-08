<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * myCRED_buddyboss_Message_Events_Hook class
 * Creds for message events updates
 *
 * 
 */
class myCRED_buddyboss_Message_Events_Hook extends myCRED_Hook { 


	/**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'completing_buddyboss_message_events',
			'defaults' => array(
				'user_message' => array(
					'creds' => 0,
					'log'   => '%plural% for sending a message'
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

		if ( $this->prefs['user_message']['creds'] != 0 ) {
			add_action( 'messages_message_sent', array( $this, 'user_messages' ) );
		}
	}

		/**
		 * New Message
		 * 
		 * 
		 */
	public function user_messages( $message ) {

		// Check if user is excluded
		if ( $this->core->exclude_user( $message->sender_id ) ) {
return;
		}

		// Limit
		if ( $this->over_hook_limit( 'user_message', 'new_message', $message->sender_id ) ) {
return;
		}

		// Make sure this is unique event
		if ( $this->core->has_entry( 'new_message', $message->thread_id ) ) {
return;
		}

		$user_member_type = bp_get_member_type($message->sender_id);

		if ($user_member_type === $this->core->exclude['by_roles'] ) {
				return;
		}

		// Execute
		$this->core->add_creds(
			'new_message',
			$message->sender_id,
			$this->prefs['user_message']['creds'],
			$this->prefs['user_message']['log'],
			$message->thread_id,
			'bp_message',
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
	<h3><?php esc_html_e( 'New Private Message', 'mycred-toolkit' ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_message', 'creds' ) )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_message', 'creds' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_message', 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['user_message']['creds'] )); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr($this->field_id( array( 'user_message', 'log' ) )); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
				<input type="text" name="<?php echo esc_attr($this->field_name( array( 'user_message', 'log' ) )); ?>" id="<?php echo esc_attr($this->field_id( array( 'user_message', 'log' ) )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['user_message']['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
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

	   $data['user_message']['creds'] = ( !empty( $data['user_message']['creds'] ) ) ? floatval( $data['user_message']['creds'] ) : $this->defaults['user_message']['creds'];

		$data['user_message']['log'] = ( !empty( $data['user_message']['log'] ) ) ? sanitize_text_field( $data['user_message']['log'] ) : $this->defaults['user_message']['log'];

		if ( isset( $data['user_message']['limit'] ) && isset( $data['user_message']['limit_by'] ) ) {
			$limit = sanitize_text_field( $data['user_message']['limit'] );
			if ( $limit == '' ) {
$limit = 0;
			}
			$data['user_message']['limit'] = $limit . '/' . $data['user_message']['limit_by'];
			unset( $data['user_message']['limit_by'] );
		}

		return $data;
	}
}