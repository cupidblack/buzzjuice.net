<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook for Event Tickets ticket purchase
 *
 * Awards points when a user purchases a ticket for any event or for specific events.
 * Supports Tickets Commerce (Stripe, PayPal, etc.) and legacy PayPal (TPP).
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'myCRED_Event_Tickets_Purchase_Hook' ) ) :

	class myCRED_Event_Tickets_Purchase_Hook extends myCRED_Hook {

		/** @var string Meta key for order purchaser user ID (Tickets Commerce) */
		const TC_ORDER_PURCHASER_META = '_tec_tc_order_purchaser_user_id';

		/** @var string Meta key for order items (Tickets Commerce) */
		const TC_ORDER_ITEMS_META = '_tec_tc_order_items';

		public function __construct( $hook_prefs, $type = 'mycred_default' ) {
			parent::__construct(
				array(
					'id'       => 'et_purchase_ticket',
					'defaults' => array(
						'creds'               => 1,
						'log'                  => __( '%plural% for purchasing a ticket', 'mycred-toolkit' ),
						'limit'                => '0/x',
						'check_specific_hook'  => 0,
						'specific_events'      => array(
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
			// Tickets Commerce (Stripe, PayPal, Manual, etc.): order status completed.
			add_action( 'tec_tickets_commerce_order_status_completed', array( $this, 'on_tc_order_completed' ), 10, 3 );
			// Legacy PayPal (TPP): tickets generated for an order.
			add_action( 'event_tickets_tpp_tickets_generated', array( $this, 'on_tpp_tickets_generated' ), 10, 2 );
		}

		/**
		 * Tickets Commerce: order status completed.
		 *
		 * @param object  $new_status New status object.
		 * @param object  $old_status Old status object.
		 * @param WP_Post $post       Order post (tec_tc_order).
		 */
		public function on_tc_order_completed( $new_status, $old_status, $post ) {
			if ( ! $post || ! isset( $post->ID ) ) {
				return;
			}
			$order_id = (int) $post->ID;
			$user_id  = (int) get_post_meta( $order_id, self::TC_ORDER_PURCHASER_META, true );
			if ( $user_id <= 0 ) {
				return;
			}
			$items = get_post_meta( $order_id, self::TC_ORDER_ITEMS_META, true );
			$event_id = $this->get_first_event_id_from_items( $items );
			$this->award_purchase( $user_id, $order_id, $event_id );
		}

		/**
		 * Legacy PayPal (TPP): tickets generated. Only awards for logged-in purchaser.
		 *
		 * @param string $order_id PayPal order ID.
		 * @param int    $post_id  Event/post ID.
		 */
		public function on_tpp_tickets_generated( $order_id, $post_id ) {
			if ( ! is_user_logged_in() ) {
				return;
			}
			$user_id  = get_current_user_id();
			$post_id  = absint( $post_id );
			if ( $post_id <= 0 ) {
				return;
			}
			// Stable numeric ref for duplicate check (TPP order_id can be string).
			$ref_id = is_numeric( $order_id ) ? (int) $order_id : absint( crc32( 'tpp_' . $order_id . '_' . $post_id . '_' . $user_id ) );
			$this->award_purchase( $user_id, $ref_id, $post_id );
		}

		/**
		 * Get first event ID from TC order items.
		 *
		 * @param mixed $items Order items (array or serialized).
		 * @return int Event ID or 0.
		 */
		protected function get_first_event_id_from_items( $items ) {
			if ( ! is_array( $items ) || empty( $items ) ) {
				return 0;
			}
			$first = reset( $items );
			return isset( $first['event_id'] ) ? absint( $first['event_id'] ) : 0;
		}

		/**
		 * Award points for a ticket purchase.
		 *
		 * @param int $user_id  User to award.
		 * @param int $ref_id   Reference ID (order ID or equivalent) for duplicate check.
		 * @param int $event_id Event ID (for specific-event matching and log ref_type).
		 */
		protected function award_purchase( $user_id, $ref_id, $event_id ) {
			$user_id  = absint( $user_id );
			$ref_id   = absint( $ref_id );
			$event_id = absint( $event_id );
			if ( $user_id <= 0 ) {
				return;
			}
			if ( $this->core->exclude_user( $user_id ) ) {
				return;
			}

			$prefs     = $this->prefs;
			$reference = 'et_purchase_ticket';

			// Specific event: check if this event is in the list and use that row.
			if (
				isset( $prefs['check_specific_hook'] ) &&
				(int) $prefs['check_specific_hook'] === 1 &&
				! empty( $prefs['specific_events']['select_option'] ) &&
				$event_id > 0
			) {
				$hook_index = false;
				foreach ( $prefs['specific_events']['select_option'] as $idx => $opt_id ) {
					if ( (int) $opt_id === $event_id ) {
						$hook_index = $idx;
						break;
					}
				}
				if (
					$hook_index !== false &&
					isset( $prefs['specific_events']['creds'][ $hook_index ] )
				) {
					if ( $this->over_hook_limit( 'specific_events', $reference, $user_id ) ) {
						return;
					}
					if ( $this->core->has_entry( $reference, $ref_id, $user_id ) ) {
						return;
					}
					$creds = $prefs['specific_events']['creds'][ $hook_index ];
					$log   = isset( $prefs['specific_events']['log'][ $hook_index ] ) ? $prefs['specific_events']['log'][ $hook_index ] : $prefs['log'];
					$this->core->add_creds(
						$reference,
						$user_id,
						$creds,
						$log,
						$event_id,
						array( 'ref_type' => 'post' ),
						$this->mycred_type
					);
					return;
				}
			}

			// General: any event.
			if ( $this->over_hook_limit( '', $reference, $user_id ) ) {
				return;
			}
			if ( $this->core->has_entry( $reference, $ref_id, $user_id ) ) {
				return;
			}
			$ref_for_log = $event_id > 0 ? $event_id : $ref_id;
			$this->core->add_creds(
				$reference,
				$user_id,
				$prefs['creds'],
				$prefs['log'],
				$ref_for_log,
				array( 'ref_type' => $event_id > 0 ? 'post' : 'order' ),
				$this->mycred_type
			);
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
			<!-- General: Purchase ticket for any event -->
			<div class="hook-instance">
				<h3><?php esc_html_e( 'Purchase ticket for any event', 'mycred-toolkit' ); ?></h3>
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

			<!-- Specific: Purchase ticket for a specific event -->
			<?php
			$specific_data = array(
				array(
					'creds'         => 0,
					'log'           => __( '%plural% for purchasing a ticket', 'mycred-toolkit' ),
					'select_option' => 0,
				),
			);
			if ( ! empty( $prefs['specific_events']['creds'] ) && count( $prefs['specific_events']['creds'] ) > 0 ) {
				$specific_data = $this->arrange_specific_data( $prefs['specific_events'] );
			}
			?>
			<div class="hook-instance" id="specific-hook-et-purchase">
				<div class="row">
					<div class="col-lg-12">
						<div class="hook-title">
							<h3><?php esc_html_e( 'Purchase ticket for a specific event', 'mycred-toolkit' ); ?></h3>
						</div>
					</div>
				</div>
				<div class="form-group">
					<?php
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
					<div class="et_purchase_specific_row">
						<div class="row">
							<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
								<div class="form-group">
									<label><?php echo esc_html( $this->core->plural() ); ?></label>
									<input type="text" name="<?php echo esc_attr( $this->specific_field_name( array( 'specific_events' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $label['creds'] ) ); ?>" class="form-control" />
								</div>
							</div>
							<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Select Event', 'mycred-toolkit' ); ?></label>
									<select class="form-control mycred-et-purchase-options" name="<?php echo esc_attr( $this->specific_field_name( array( 'specific_events' => 'select_option' ) ) ); ?>">
										<option value="0"><?php esc_html_e( 'Select Event', 'mycred-toolkit' ); ?></option>
										<?php
										if ( ! empty( $posts ) ) {
											foreach ( $posts as $post ) {
												$selected = ( isset( $label['select_option'] ) && (int) $label['select_option'] === (int) $post->ID ) ? 'selected' : '';
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
									<button class="button button-small mycred-add-specific-et-purchase-hook add_button" type="button"><?php esc_html_e( 'Add More', 'mycred-toolkit' ); ?></button>
									<button class="button button-small mycred-remove-specific-et-purchase-hook" type="button"><?php esc_html_e( 'Remove', 'mycred-toolkit' ); ?></button>
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
			$data['check_specific_hook'] = ( isset( $data['check_specific_hook'] ) && $data['check_specific_hook'] == '1' ) ? 1 : 0;
			$data['log']                 = ( ! empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['limit'] );
				if ( $limit == '' ) {
					$limit = 0;
				}
				$data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $data['limit_by'] );
			}

			if ( isset( $data['specific_events'] ) ) {
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
