<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook for BookingPress Complete Booking
 * 
 * 
 */
 if ( ! class_exists( 'myCRED_BookingPress_Complete_Booking_Hook' ) ) :
    class myCRED_BookingPress_Complete_Booking_Hook extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'bookingpress_successful_booking_complete',
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

               
                add_action( 'user_register', [ $this, 'handle_new_user' ], 10, 1 );
                add_action( 'init', [ $this, 'sync_existing_users' ], 10, 1 );
                add_action( 'bookingpress_after_insert_appointment', [ $this, 'schedule_check_and_award' ], 10, 1 );
                add_action( 'bookingpress_after_change_appointment_status', [ $this, 'maybe_award_on_status_change' ], 10, 2 );
                add_action( 'mycred_bookingpress_check_and_award', [ $this, 'check_and_award' ], 10, 1 );

        }

         public function handle_new_user( $user_id ) {

            $user = get_userdata( $user_id );

            if ( in_array( 'administrator', (array) $user->roles ) ) {
                return; // Exclude admin
            }

            // Assign bookingpress-customer role in addition to existing roles
            $user->add_role( 'bookingpress-customer' );

            // Insert into bookingpress customers table
            $this->insert_into_bookingpress( $user_id );

        }

        public function sync_existing_users() {

            $args = [
                'exclude' => [ 1 ], // Exclude super admin (ID 1 usually)
                'fields'  => 'ID'
            ];
            $users = get_users( $args );

            foreach ( $users as $user_id ) {
                $user = get_userdata( $user_id );

                if ( in_array( 'administrator', (array) $user->roles ) ) {
                    continue;
                }

                // Add role if not already assigned
                $user->add_role( 'bookingpress-customer' );

                // Insert into bookingpress table
                $this->insert_into_bookingpress( $user_id );
            }

        }

        private function insert_into_bookingpress( $user_id ) {
        
            global $wpdb;

            $table = $wpdb->prefix . 'bookingpress_customers';

            $user = get_userdata( $user_id );

            // Check if user already exists in bookingpress table
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT bookingpress_customer_id FROM {$table} WHERE bookingpress_wpuser_id = %d",
                $user_id
            ) );

            if ( $exists ) {
                return; // Already inserted
            }

            $first_name = get_user_meta( $user_id, 'first_name', true );
            $last_name  = get_user_meta( $user_id, 'last_name', true );
            $full_name  = trim( $first_name . ' ' . $last_name );

            $wpdb->insert(
                $table,
                [
                    'bookingpress_wpuser_id'        => $user_id,
                    'bookingpress_user_login'       => $user->user_login,
                    'bookingpress_user_status'      => 1,
                    'bookingpress_user_type'        => 2,
                    'bookingpress_user_name'        => $full_name ?: $user->display_name,
                    'bookingpress_user_firstname'   => $first_name,
                    'bookingpress_user_lastname'    => $last_name,
                    'bookingpress_customer_full_name'=> $full_name ?: $user->display_name,
                    'bookingpress_user_email'       => $user->user_email,
                    'bookingpress_user_phone'       => '',
                    'bookingpress_user_country_phone' => '',
                    'bookingpress_user_country_dial_code' => '',
                    'bookingpress_user_timezone'    => '',
                    'bookingpress_created_at'       => time(),
                    'bookingpress_created_by'       => get_current_user_id() ?: 0,
                    'bookingpress_user_created'     => current_time( 'mysql' ),
                ],
                [
                    '%d','%s','%d','%d','%s','%s','%s','%s','%s',
                    '%s','%s','%s','%s','%d','%d','%s'
                ]
            );

        }

        public function schedule_check_and_award( $appointment_id ) {

            // small delay to ensure all related rows/fields are saved
            wp_schedule_single_event( time() + 3, 'mycred_bookingpress_check_and_award', [ (int) $appointment_id ] );

        }

        public function maybe_award_on_status_change( $appointment_id, $new_status ) {

            if ( (int) $new_status === 1 ) {
                // If it just got approved, try to award right now
                $this->check_and_award( (int) $appointment_id );
            }

        }

        public function check_and_award( $appointment_id ) {
            global $wpdb;

            // 1) Confirm Approved via BookingPress table (NOT wp_bookingdates)
            $status = $wpdb->get_var( $wpdb->prepare(
                "SELECT bookingpress_appointment_status
                 FROM {$wpdb->prefix}bookingpress_appointment_bookings
                 WHERE bookingpress_appointment_booking_id = %d",
                $appointment_id
            ) );

            if ( (int) $status !== 1 ) {
                return; // not approved, no points
            }

            // 2) Resolve WP user id
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT c.bookingpress_wpuser_id, c.bookingpress_user_email
                 FROM {$wpdb->prefix}bookingpress_appointment_bookings AS a
                 INNER JOIN {$wpdb->prefix}bookingpress_customers AS c
                         ON a.bookingpress_customer_id = c.bookingpress_customer_id
                 WHERE a.bookingpress_appointment_booking_id = %d",
                $appointment_id
            ) );

            if ( ! $row ) return;

            $wp_user_id = (int) $row->bookingpress_wpuser_id;

            if ( ! $wp_user_id && ! empty( $row->bookingpress_user_email ) ) {
                // fallback: find user by email
                $u = get_user_by( 'email', $row->bookingpress_user_email );
                if ( $u ) {
                    $wp_user_id = (int) $u->ID;

                    // keep BookingPress linked for future
                    $wpdb->update(
                        $wpdb->prefix . 'bookingpress_customers',
                        [ 'bookingpress_wpuser_id' => $wp_user_id ],
                        [ 'bookingpress_user_email' => $row->bookingpress_user_email ],
                        [ '%d' ],
                        [ '%s' ]
                    );
                }
            }

            if ( ! $wp_user_id ) return;

            // 3) Prevent duplicates
            if ( $this->core->has_entry( 'bookingpress_successful_booking_complete', $appointment_id, $wp_user_id ) ) {
                return;
            }

            // 4) Award points
            $this->core->add_creds(
                'bookingpress_successful_booking_complete',
                $wp_user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $appointment_id,
                'bookingpress_successful_booking_complete',
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