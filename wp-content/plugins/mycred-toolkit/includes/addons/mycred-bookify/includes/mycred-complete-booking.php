<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * 
 * @since 0.1
 * @version 1.1.1
 * 
 */
if ( ! class_exists( 'mycred_bookify_Booking_Completion_Hook_Class' ) ) :
	class mycred_bookify_Booking_Completion_Hook_Class extends myCRED_Hook {

		function __construct( $hook_prefs, $type = mycred_TYPE_KEY ) {

			parent::__construct( 
			array(
				'id'       => 'bookify_booking_completion',
				'defaults' => array(
					'creds'      => 10,
					'log'        => '%plural% for completing booking.',
					'limit'   => 'x',
					'mycred_check_complete' => '1',
				)
			),          
			$hook_prefs, $type );
		}

		public function run() {

			add_action('bookify_appointment_status_changed_email', array( $this, 'award_points_on_appointment_status_change' ), 10, 3);

			add_action( 'bookify_appointment_requested_email', array( $this, 'award_points_on_appointment_created' ), 10, 1 );

		}

		public function award_points_on_appointment_created( $appointment_id ) {
			
		    global $wpdb;

		    $prefs = $this->prefs;
		    $appointment = $wpdb->get_row(
		        $wpdb->prepare(
		            "SELECT appointment_customer, appointment_status, appointment_price 
		             FROM {$wpdb->prefix}bookify_appointments 
		             WHERE id = %d",
		            $appointment_id
		        )
		    );

		    if ( empty( $appointment ) || empty( $appointment->appointment_customer ) ) {
		        return;
		    }

		    $customer_id = $appointment->appointment_customer;
		    $appointment_status = $appointment->appointment_status;
		    $appointment_price  = $appointment->appointment_price;

		    // Only award if status is "Completed" at creation
		    if ( strtolower( $appointment_status ) === 'completed' ) {

		        if ( $this->over_hook_limit( '', 'bookify_booking_completion', $customer_id ) ) {
		            return;
		        }

		        $this->core->add_creds(
		            'bookify_booking_completion',
		            $customer_id,
		            $prefs['creds'],
		            $prefs['log'],
		            $appointment_id,
		            array( 'ref_type' => 'post' ),
		            $this->mycred_type
		        );
		    }
		}

		public function award_points_on_appointment_status_change( $appointment_id, $prev_status, $new_status ) {

			global $wpdb;

			$prefs = $this->prefs;
			$ref_type = array( 'ref_type' => 'post' );
			$payment_settings = get_option('bookify_payment_settings');
			$payment_settings_array = json_decode($payment_settings, true);
			$payment_settings_data = array();
			$post = get_post( $appointment_id );

			$customer_id = $wpdb->get_var( 
				$wpdb->prepare(
					"SELECT appointment_customer FROM {$wpdb->prefix}bookify_appointments WHERE id = %d", 
					$appointment_id
				)
			);

			$appointment_price = $wpdb->get_var( 
				$wpdb->prepare(
					"SELECT appointment_price FROM {$wpdb->prefix}bookify_appointments WHERE id = %d", 
					$appointment_id
				)
			);

			
			if (empty($customer_id)) {
			   
				return; 
			}

			foreach ($payment_settings_array as $key => $value) {
				$payment_settings_data[ $key ] = $value;
			}

			$profit_sharing = $payment_settings_data['mycred']['profitsharing'];
			$point_type = $payment_settings_data['mycred']['pointtypechange'];
			$mycred = mycred($point_type);
			$exchange_rate = $payment_settings_data['mycred']['exchangerate'];
			$balance = $mycred->get_users_balance( $customer_id, $point_type);
			$log_template_profit_sharing = $payment_settings_data['mycred']['log'];
			if ($exchange_rate != 0) {
			   $final_price = $mycred->number( ( $appointment_price / $exchange_rate ) );
			}

			if ($new_status === 'Completed' ) {

				if ($this->over_hook_limit('', 'bookify_booking_completion', $customer_id)) {
					return;
				}

						$this->core->add_creds(
							'bookify_booking_completion', 
							$customer_id,            
							 $prefs['creds'], 
							 $prefs['log'],
							$appointment_id,                     
							array( 'ref_type' => 'post' ),                   
							$this->mycred_type 
						);
					
			}
		}

	  /**
	   * Sanitize Preferences
	   */
		public function sanitise_preferences( $data ) {

			$new_data = array();

			$new_data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : '';
			$new_data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : '';
			$new_data['mycred_check_complete'] = ( !empty( $data['mycred_check_complete'] ) ) ? sanitize_text_field( $data['mycred_check_complete'] ) : '';
			
			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$new_data['limit'] = sanitize_text_field( $data['limit'] );
				$limit = sanitize_text_field($data['limit']);
				if ( $limit == '' ) {
$limit = 0;
				}
				$new_data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $new_data['limit_by'] );
			}

			return $new_data;
		}

		/**
		 * Preference for bookify quiz Hook
		 * @since 1.0
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;
			?>

			<div class="hook-instance">
				<div class="row">
					<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id('creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name('creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php
							echo wp_kses(
									$this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ),
									array(
										'div' => array(
											'class' => array()
										),
										'input' => array(
											'type' => array(),
											'size' => array(),
											'class' => array(),
											'name' => array(),
											'id' => array(),
											'value' => array()
										),
										'select' => array(
											'name' => array(),
											'id' => array(),
											'class' => array()
										),
										'option' => array(
											'value' => array(),
											'selected' => array()
										)
									) 
								); 
							?>
						</div>
					</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
						</div>
					</div>
				</div>
			</div>
	 <?php
		}
	}
endif;