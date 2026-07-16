<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook for Event Tickets RSVP confirmation
 *
 * Awards points when a user confirms RSVP for any event or for specific events.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'myCRED_Event_Tickets_RSVP_Hook' ) ) :

	class myCRED_Event_Tickets_RSVP_Hook extends myCRED_Hook {

		public function __construct( $hook_prefs, $type = 'mycred_default' ) {
			parent::__construct(
				array(
					'id'       => 'et_rsvp_confirm',
					'defaults' => array(
						'creds'               => 1,
						'log'                 => __( '%plural% for confirming RSVP', 'mycred-toolkit' ),
						'limit'               => '0/x',
						'check_specific_hook' => 0,
						'specific_events'     => array(
							'creds'          => array(),
							'log'            => array(),
							'select_option'  => array(),
						),
					),
				),
				$hook_prefs,
				$type
			);
		}

		public function run() {
			add_action( 'event_tickets_rsvp_tickets_generated', array( $this, 'on_rsvp_confirmed' ), 10, 3 );
		}

		/**
		 * Generate specific field name for repeatable fields.
		 */
		public function specific_field_name( $field = '' ) {
			$option_id = 'mycred_pref_hooks';
			if ( ! $this->is_main_type ) {
				$option_id = $option_id . '_' . $this->mycred_type;
			}
			if ( is_array( $field ) ) {
				$array = array();
				foreach ( $field as $parent => $child ) {
					if ( ! is_numeric( $parent ) ) {
						$array[] = $parent;
					}
					if ( ! empty( $child ) && ! is_array( $child ) ) {
						$array[] = $child;
					}
				}
				$field = '[' . implode( '][', $array ) . ']';
			} else {
				$field = '[' . $field . ']';
			}
			return $option_id . '[hook_prefs][' . $this->id . ']' . $field . '[]';
		}

		/**
		 * Award points when RSVP is confirmed.
		 *
		 * @param int    $order_id              RSVP order ID.
		 * @param int    $post_id               Event/post ID.
		 * @param string $attendee_order_status Attendee status (e.g. 'yes', 'no').
		 */
		public function on_rsvp_confirmed( $order_id, $post_id, $attendee_order_status ) {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$user_id  = get_current_user_id();
			$post_id  = absint( $post_id );
			$order_id = absint( $order_id );

			if ( empty( $post_id ) || empty( $user_id ) ) {
				return;
			}

			if ( $this->core->exclude_user( $user_id ) ) {
				return;
			}

			$prefs    = $this->prefs;
			$reference = 'et_rsvp_confirm';

			// Optional: only award when user indicated they will attend.
			if ( $attendee_order_status !== 'yes' ) {
				return;
			}

			// Check specific event first.
			if (
				isset( $prefs['check_specific_hook'] ) &&
				(int) $prefs['check_specific_hook'] === 1 &&
				! empty( $prefs['specific_events']['select_option'] ) &&
				in_array( $post_id, $prefs['specific_events']['select_option'] )
			) {
				$hook_index = array_search( $post_id, $prefs['specific_events']['select_option'] );

				if (
					$hook_index !== false &&
					! empty( $prefs['specific_events']['creds'] ) &&
					isset( $prefs['specific_events']['creds'][ $hook_index ] )
				) {
					if ( $this->over_hook_limit( 'specific_events', $reference, $user_id ) ) {
						return;
					}
					if ( $this->core->has_entry( $reference, $order_id, $user_id ) ) {
						return;
					}

					$creds = $prefs['specific_events']['creds'][ $hook_index ];
					$log   = isset( $prefs['specific_events']['log'][ $hook_index ] ) ? $prefs['specific_events']['log'][ $hook_index ] : $prefs['log'];

					$this->core->add_creds(
						$reference,
						$user_id,
						$creds,
						$log,
						$post_id,
						array( 'ref_type' => 'post' ),
						$this->mycred_type
					);
					return;
				}
			}

			// General award.
			if ( $this->over_hook_limit( '', $reference, $user_id ) ) {
				return;
			}
			if ( $this->core->has_entry( $reference, $order_id, $user_id ) ) {
				return;
			}

			$this->core->add_creds(
				$reference,
				$user_id,
				$prefs['creds'],
				$prefs['log'],
				$post_id,
				array( 'ref_type' => 'post' ),
				$this->mycred_type
			);
		}

		/**
		 * Arrange specific hook data for display.
		 */
		public function arrange_specific_data( $specific_hook_data ) {
			$hook_data = array();
			if ( isset( $specific_hook_data['creds'] ) && is_array( $specific_hook_data['creds'] ) ) {
				foreach ( $specific_hook_data['creds'] as $key => $value ) {
					$hook_data[ $key ]['creds']         = $value;
					$hook_data[ $key ]['log']           = isset( $specific_hook_data['log'][ $key ] ) ? $specific_hook_data['log'][ $key ] : '';
					$hook_data[ $key ]['select_option'] = isset( $specific_hook_data['select_option'][ $key ] ) ? $specific_hook_data['select_option'][ $key ] : '';
				}
			}
			return $hook_data;
		}

		/**
		 * Get posts (events) for the specific event dropdown.
		 */
		public function get_events_for_select() {
			if ( ! post_type_exists( 'tribe_events' ) ) {
				return array();
			}
			$args = array(
				'post_type'      => 'tribe_events',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			);
			return get_posts( $args );
		}

		public function preferences() {
			$prefs = $this->prefs;
			$posts = $this->get_events_for_select();

			?>
			<!-- General RSVP Rewards -->
			<div class="hook-instance">
				<h3><?php esc_html_e( 'Confirm RSVP for any event', 'mycred-toolkit' ); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Specific Event Rewards -->
			<?php
			$specific_data = array(
				array(
					'creds'         => 0,
					'log'           => __( '%plural% for confirming RSVP', 'mycred-toolkit' ),
					'select_option' => 0,
				),
			);
			if ( ! empty( $prefs['specific_events']['creds'] ) && count( $prefs['specific_events']['creds'] ) > 0 ) {
				$specific_data = $this->arrange_specific_data( $prefs['specific_events'] );
			}
			?>
			<div class="hook-instance" id="specific-hook-et-rsvp">
				<div class="row">
					<div class="col-lg-12">
						<div class="hook-title">
							<h3><?php esc_html_e( 'Confirm RSVP for a specific event', 'mycred-toolkit' ); ?></h3>
						</div>
					</div>
				</div>
				<div class="form-group">
					<?php
					// Hidden input ensures unchecked checkbox still submits a value (0); when checked, checkbox value 1 is sent.
					echo '<input type="hidden" name="' . esc_attr( $this->field_name( 'check_specific_hook' ) ) . '" value="0" />';
					$is_enabled = ( isset( $prefs['check_specific_hook'] ) && (int) $prefs['check_specific_hook'] === 1 );
					mycred_create_toggle_field(
						array(
							'id'    => $this->field_id( 'check_specific_hook' ),
							'name'  => $this->field_name( 'check_specific_hook' ),
							'label' => __( 'Enable', 'mycred-toolkit' ),
							'after' => false,
						),
						1,
						$is_enabled
					);
					?>
				</div>
				<?php
				foreach ( $specific_data as $hook_idx => $label ) {
					?>
					<div class="et_rsvp_specific_row">
						<div class="row">
							<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
								<div class="form-group">
									<label for="<?php echo esc_attr( $this->field_id( array( 'specific_events' => 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
									<input type="text" name="<?php echo esc_attr( $this->specific_field_name( array( 'specific_events' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $label['creds'] ) ); ?>" class="form-control" />
								</div>
							</div>
							<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Select Event', 'mycred-toolkit' ); ?></label>
									<select class="form-control mycred-et-rsvp-options" name="<?php echo esc_attr( $this->specific_field_name( array( 'specific_events' => 'select_option' ) ) ); ?>">
										<option value="0"><?php esc_html_e( 'Select Event', 'mycred-toolkit' ); ?></option>
										<?php
										if ( ! empty( $posts ) ) {
											foreach ( $posts as $post ) {
												$selected = ( isset( $label['select_option'] ) && $label['select_option'] == $post->ID ) ? 'selected' : '';
												echo '<option value="' . esc_attr( $post->ID ) . '" ' . $selected . '>' . esc_html( $post->post_title ) . '</option>';
											}
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
									<input type="text" name="<?php echo esc_attr( $this->specific_field_name( array( 'specific_events' => 'log' ) ) ); ?>" value="<?php echo esc_attr( $label['log'] ); ?>" class="form-control" />
									<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
								<div class="form-group textright">
									<button class="button button-small mycred-add-specific-et-rsvp-hook add_button" type="button"><?php esc_html_e( 'Add More', 'mycred-toolkit' ); ?></button>
									<button class="button button-small mycred-remove-specific-et-rsvp-hook" type="button"><?php esc_html_e( 'Remove', 'mycred-toolkit' ); ?></button>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
				?>
			</div>

			<!-- Limit -->
			<div class="hook-instance">
				<h3><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></h3>
				<div class="row">
					<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
						<div class="form-group">
							<?php add_filter( 'mycred_hook_limits', array( $this, 'custom_limit' ) ); ?>
							<label for="<?php echo esc_attr( $this->field_id( 'limit' ) ); ?>"></label>
							<?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ); ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public function sanitise_preferences( $data ) {
			$data['creds']               = ( ! empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : 0;
			$data['check_specific_hook'] = ( isset( $data['check_specific_hook'] ) && $data['check_specific_hook'] === '1' ) ? 1 : 0;
			$data['log']                 = ( ! empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['limit'] );
				if ( $limit == '' ) {
					$limit = 0;
				}
				$data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $data['limit_by'] );
			}

			if ( isset( $data['specific_events'] ) && isset( $data['specific_events']['creds'] ) && is_array( $data['specific_events']['creds'] ) ) {
				foreach ( $data['specific_events']['creds'] as $key => $value ) {
					$data['specific_events']['creds'][ $key ]         = floatval( $value );
					$data['specific_events']['log'][ $key ]           = isset( $data['specific_events']['log'][ $key ] ) ? sanitize_text_field( $data['specific_events']['log'][ $key ] ) : '';
					$data['specific_events']['select_option'][ $key ] = isset( $data['specific_events']['select_option'][ $key ] ) ? absint( $data['specific_events']['select_option'][ $key ] ) : 0;
				}
			}

			return $data;
		}

		public function custom_limit() {
			return array(
				'x' => __( 'No limit', 'mycred-toolkit' ),
				'd' => __( '/ Day', 'mycred-toolkit' ),
				'w' => __( '/ Week', 'mycred-toolkit' ),
				'm' => __( '/ Month', 'mycred-toolkit' ),
			);
		}
	}

endif;
