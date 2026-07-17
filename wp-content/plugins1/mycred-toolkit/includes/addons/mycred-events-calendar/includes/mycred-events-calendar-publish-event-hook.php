<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook: Publishing a New Event (The Events Calendar)
 *
 * Awards points when a user publishes a new event (post type: tribe_events).
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'myCRED_Events_Calendar_Publish_Event_Hook' ) ) :

	class myCRED_Events_Calendar_Publish_Event_Hook extends myCRED_Hook {

		/**
		 * The Events Calendar event post type.
		 */
		const POST_TYPE = 'tribe_events';

		public function __construct( $hook_prefs, $type = 'mycred_default' ) {
			parent::__construct(
				array(
					'id'       => 'tec_publish_event',
					'defaults' => array(
						'creds' => 0,
						'log'   => __( '%plural% for publishing a new event', 'mycred-toolkit' ),
						'limit' => '0/x',
					),
				),
				$hook_prefs,
				$type
			);
		}

		public function run() {
			add_action( 'transition_post_status', array( $this, 'on_publish_event' ), 10, 3 );
		}

		/**
		 * Fires when a post's status transitions. Award points when an event is published.
		 *
		 * @param string   $new_status New post status.
		 * @param string   $old_status Old post status.
		 * @param WP_Post  $post       Post object.
		 */
		public function on_publish_event( $new_status, $old_status, $post ) {
			if ( $new_status !== 'publish' || $old_status === 'publish' ) {
				return;
			}
			if ( ! $post instanceof WP_Post || $post->post_type !== self::POST_TYPE ) {
				return;
			}

			$user_id = (int) $post->post_author;
			if ( $user_id === 0 ) {
				return;
			}

			if ( $this->core->exclude_user( $user_id ) ) {
				return;
			}

			if ( $this->over_hook_limit( '', 'tec_publish_event', $user_id ) ) {
				return;
			}

			if ( $this->core->has_entry( 'tec_publish_event', $post->ID, $user_id ) ) {
				return;
			}

			$this->core->add_creds(
				'tec_publish_event',
				$user_id,
				$this->prefs['creds'],
				$this->prefs['log'],
				$post->ID,
				array( 'ref_type' => 'post' ),
				$this->mycred_type
			);
		}

		public function preferences() {
			$prefs = $this->prefs;
			?>
			<div class="hook-instance">
				<h3><?php esc_html_e( 'Publishing a New Event', 'mycred-toolkit' ); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php
							echo wp_kses(
								$this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ),
								array(
									'div'    => array( 'class' => array() ),
									'input'  => array(
										'type'  => array(),
										'size'  => array(),
										'class' => array(),
										'name'  => array(),
										'id'    => array(),
										'value' => array(),
									),
									'select' => array(
										'name'  => array(),
										'id'    => array(),
										'class' => array(),
									),
									'option' => array(
										'value'    => array(),
										'selected' => array(),
									),
								)
							);
							?>
						</div>
					</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public function sanitise_preferences( $data ) {
			$data['creds'] = ! empty( $data['creds'] ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
			$data['log']   = ! empty( $data['log'] ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];
			$data['limit'] = ! empty( $data['limit'] ) ? sanitize_text_field( $data['limit'] ) : $this->defaults['limit'];
			return $data;
		}
	}

endif;
