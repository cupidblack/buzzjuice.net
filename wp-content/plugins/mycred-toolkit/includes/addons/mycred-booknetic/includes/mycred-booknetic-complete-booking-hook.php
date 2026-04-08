<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook for Booknetic Booking Complete
 * 
 * 
 */
 if ( ! class_exists( 'myCRED_Booknetic_Complete_Booking_Hook' ) ) :
    class myCRED_Booknetic_Complete_Booking_Hook extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'booknetic_successful_booking_complete',
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

            add_action( 'bkntc_payment_confirmed_backend', array( $this, 'mycred_booknetic_complete_booking' ), 10, 1 );
            add_action( 'bkntc_payment_confirmed', array( $this, 'mycred_booknetic_complete_booking' ), 10, 1 );

        }
        
        public function mycred_booknetic_complete_booking($appointment_id) {

            global $wpdb;

            // Step 1: Get customer_id
            $customer_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT customer_id FROM {$wpdb->prefix}bkntc_appointments WHERE id = %d",
                $appointment_id
            ) );

            if ( ! $customer_id ) {
                return;
            }

            // Step 2: Get WP user ID from customer
            $wp_user_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}bkntc_customers WHERE id = %d",
                $customer_id
            ) );

            // Step 3: If not linked, try to link or create user
            if ( empty($wp_user_id) ) {
                
                $customer = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bkntc_customers WHERE id = %d",
                    $customer_id
                ) );

                if ( ! $customer || empty($customer->email) ) {
                    return;
                }

                // Check if email exists in WordPress
                if ( email_exists( $customer->email ) ) {
                    // Link to existing WordPress user
                    $existing_user = get_user_by('email', $customer->email);
                    
                    if ( $existing_user ) {
                        $wp_user_id = $existing_user->ID;
                        
                        // Link existing WP user to Booknetic customer
                        $wpdb->update(
                            "{$wpdb->prefix}bkntc_customers",
                            [ 'user_id' => $wp_user_id ],
                            [ 'id' => $customer_id ]
                        );
                    } else {
                        return;
                    }
                    
                } else {
                    // Create new WordPress user
                    $username = sanitize_user( current( explode( '@', $customer->email ) ), true );
                    
                    // Ensure unique username
                    if ( username_exists( $username ) ) {
                        $username = $username . '_' . rand(100, 999);
                    }
                    
                    $random_password = wp_generate_password();
                    $new_user_id = wp_create_user( $username, $random_password, $customer->email );

                    if ( ! is_wp_error( $new_user_id ) ) {
                        $wp_user_id = $new_user_id;

                        // Link new WP user to Booknetic customer
                        $wpdb->update(
                            "{$wpdb->prefix}bkntc_customers",
                            [ 'user_id' => $wp_user_id ],
                            [ 'id' => $customer_id ]
                        );

                    } else {
                        return;
                    }
                }
            }

            // Step 4: Confirm user is valid
            if ( ! get_userdata($wp_user_id) ) {
                return;
            }

            // Step 5: Check if points already awarded
            if ( $this->core->has_entry( 'booknetic_successful_booking_complete', $appointment_id, $wp_user_id) ) {
                return;
            }

            // Step 6: Award points to customer
            $this->core->add_creds(
                'booknetic_successful_booking_complete',
                $wp_user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $appointment_id,
                'booknetic_successful_booking_complete',
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