<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook for Sureform form submission
 * 
 * 
 */
 if ( ! class_exists( 'myCRED_FluentBooking_Complete_Booking_Hook' ) ) :
    class myCRED_FluentBooking_Complete_Booking_Hook extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'fluentbooking_successful_booking_complete',
                'defaults' => array( 
                    'creds'         => 0,
                    'log'           => __('%plural% for completing booking', 'mycred-toolkit'),  
                )
            ), $hook_prefs, $type );
        }

        /**
         * Run
         */
        public function run() {

            if ( is_user_logged_in() ) {
                $this->user_id = get_current_user_id();
                add_action( 'fluent_booking/booking_schedule_completed', array( $this, 'mycred_fluentbooking_complete_booking' ), 10, 2 );
            }

        }

       public function mycred_fluentbooking_complete_booking($booking, $calendar_event) {

            // Login is required
            if ( ! is_user_logged_in() ) return;

            global $wpdb;

            // Ensure we have booking data
            if ( empty( $booking->id ) ) {
                return;
            }

            // Fetch booking record from DB to get `person_user_id`
            $table_name = $wpdb->prefix . 'fcal_bookings';
            $booking_data = $wpdb->get_row( 
                $wpdb->prepare( 
                    "SELECT person_user_id, status FROM {$table_name} WHERE id = %d", 
                    $booking->id 
                ) 
            );

            if ( ! $booking_data || empty( $booking_data->person_user_id ) ) {
                return; // No user to award
            }

            $user_id = intval( $booking_data->person_user_id );

            $prefs = $this->prefs;

            if ( $this->core->has_entry( 'fluentbooking_successful_booking_complete', $booking->id, $user_id) ) return;

                // Execute
                $this->core->add_creds(
                    'fluentbooking_successful_booking_complete',
                    $user_id,
                    $this->prefs['creds'],
                    $this->prefs['log'],
                    $booking->id,
                    'fluentbooking_successful_booking_complete',
                    $this->mycred_type
                );
        }

        public function preferences() {

            $prefs = $this->prefs;
            
            ?>
              <div class="hook-instance">
                    <h3><?php esc_html_e( 'General', 'mycred' ); ?></h3>
                    <div class="row">
                        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id( 'creds' )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'creds' )); ?>" id="<?php echo esc_attr($this->field_id( 'creds' )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['creds'] )); ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id('log' )); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'log' )); ?>" id="<?php echo esc_attr($this->field_id( 'log' )); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
                                <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
                            </div>
                        </div>
                    </div>
               </div>
            <?php
        }

        public function sanitise_preferences( $data ) {

            $data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
            $data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

            return $data;
                            
        }

  }
endif;