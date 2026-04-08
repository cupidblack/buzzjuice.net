<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook for WP Travel Booking Complete
 * 
 * 
 */
 if ( ! class_exists( 'myCRED_WPTravel_Complete_Booking_Hook' ) ) :
    class myCRED_WPTravel_Complete_Booking_Hook extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'wptravel_successful_booking_complete',
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

                 add_action( 'wp_travel_after_booking_data_save', array( $this, 'mycred_booking_status_change' ),10,1 );

        }

        public function mycred_booking_status_change( $booking_id ) {

            $prefs = $this->prefs;

            // Get traveller email properly
            $email_meta = get_post_meta( $booking_id, 'wp_travel_email_traveller', true );

            $email = '';
            if ( is_array( $email_meta ) ) {
                foreach ( $email_meta as $traveller_data ) {
                    if ( is_array( $traveller_data ) && ! empty( $traveller_data[0] ) ) {
                        $email = $traveller_data[0];
                        break;
                    }
                }
            } else {
                $email = $email_meta;
            }

            $email = sanitize_email( $email );

            if ( empty( $email ) ) {
                return;
            }

            // Get user by email
            $user = get_user_by( 'email', $email );
            if ( ! $user ) {
                return;
            }

            $user_id = $user->ID;

            // Check booking status
            $booking_status = get_post_meta( $booking_id, 'wp_travel_booking_status', true );
            if ( $booking_status !== 'booked' ) {
                return;
            }

            // Check payment status
            $payment_status = get_post_meta( $booking_id, 'wp_travel_payment_status', true );
            if ( $payment_status !== 'paid' ) {
                return;
            }

            // Prevent duplicate rewards
            if ( $this->has_entry( 'wptravel_booking_complete', $booking_id, $user_id ) ) {
                return;
            }

            // Award points
            $this->core->add_creds(
                'wptravel_successful_booking_complete',
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $booking_id,
                'wptravel_successful_booking_complete',
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


