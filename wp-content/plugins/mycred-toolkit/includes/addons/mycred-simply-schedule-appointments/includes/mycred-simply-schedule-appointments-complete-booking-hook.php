<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 
 * Hook for Simply Schedule Appointments Booking Complete
 * 
 */
 if ( ! class_exists( 'myCRED_SimplyScheduleAppointments_Complete_Booking_Hook' ) ) :
    class myCRED_SimplyScheduleAppointments_Complete_Booking_Hook extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'simplyscheduleappointments_successful_booking_complete',
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

                add_action( 'ssa/appointment/booked', array( $this, 'mycred_simplyscheduleappointments_complete_booking' ), 10, 4 );
           
        }

        public function mycred_simplyscheduleappointments_complete_booking($appointment_id, $data, $data_before, $response) {

            $user_id = 0;

            // 1. Get email from guest booking
            if ( ! empty( $data['customer_information']['Email'] ) ) {
                $email = sanitize_email( $data['customer_information']['Email'] );
                $name  = sanitize_text_field( $data['customer_information']['Name'] );

                // 2. Try to get existing user
                $user = get_user_by( 'email', $email );

                if ( $user ) {
                    $user_id = $user->ID;
                   
                } else {
                    // 3. Create new user
                    $username = sanitize_user( str_replace( ' ', '_', strtolower( $name ) ), true );
                    if ( username_exists( $username ) ) {
                        $username .= '_' . time(); // Make unique
                    }

                    $random_password = wp_generate_password( 12, false );
                    $user_id = wp_create_user( $username, $random_password, $email );

                    if ( is_wp_error( $user_id ) ) {
                       
                        return;
                    }

                    wp_update_user( array(
                        'ID' => $user_id,
                        'display_name' => $name
                    ) );


                    // Optional: send email to user
                    wp_mail( $email, 'Your account was created', "Hi $name,\n\nAn account has been created for you to manage your bookings.\nUsername: $username\n\nYou can reset your password here: " . wp_lostpassword_url() );
                }
            }

            // 4. Still no user? Abort
            if ( $user_id === 0 ) {
              
                return;
            }

            // 5. Prevent duplicate points
            if ( $this->core->has_entry( 'simplyscheduleappointments_successful_booking_complete', $appointment_id, $user_id ) ) {
              
                return;
            }

            // 6. Award points
            $this->core->add_creds(
                'simplyscheduleappointments_successful_booking_complete',
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $appointment_id,
                'simplyscheduleappointments_successful_booking_complete',
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