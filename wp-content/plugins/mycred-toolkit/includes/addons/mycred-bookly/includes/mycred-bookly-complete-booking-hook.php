<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCRED_Bookly_Complete_Booking_Hook' ) ) :

class myCRED_Bookly_Complete_Booking_Hook extends myCRED_Hook {

    public function __construct( $hook_prefs, $type = 'mycred_default' ) {
        parent::__construct( array(
            'id'       => 'bookly_successful_booking_complete',
            'defaults' => array(
                'creds' => 10,
                'log'   => __( '%plural% for completing booking', 'mycred-toolkit' ),
            )
        ), $hook_prefs, $type );
    }

    public function run() {

        if ( ! wp_next_scheduled( 'mycred_bookly_check_appointments' ) ) {
            wp_schedule_event( time(), 'minute', 'mycred_bookly_check_appointments' );
        }

        add_action( 'mycred_bookly_check_appointments', array( $this, 'check_and_award_points' ) );
    }

    public function check_and_award_points() {
        
        global $wpdb;

        // Track last processed ID to avoid duplicates
        $last_processed_id = (int) get_option( 'mycred_last_bookly_processed_id', 0 );

        $bookings = $wpdb->get_results("
            SELECT ca.id AS customer_appointment_id, ca.appointment_id, ca.customer_id, c.email, c.full_name, c.wp_user_id
            FROM {$wpdb->prefix}bookly_customer_appointments ca
            JOIN {$wpdb->prefix}bookly_customers c ON ca.customer_id = c.id
            WHERE ca.status = 'approved'
              AND ca.id > {$last_processed_id}
              AND c.email IS NOT NULL
            ORDER BY ca.id ASC
            LIMIT 10
        ");

        if ( empty( $bookings ) ) {
            return;
        }

        foreach ( $bookings as $booking ) {

            $user_id = 0;

            if ( $booking->wp_user_id && get_userdata( $booking->wp_user_id ) ) {
                $user_id = $booking->wp_user_id;
               
            } else {
                $email = sanitize_email( $booking->email );
                $name  = sanitize_text_field( $booking->full_name );

                $user = get_user_by( 'email', $email );
                if ( $user ) {
                    $user_id = $user->ID;
                   
                } else {
                    $username = sanitize_user( str_replace( ' ', '_', strtolower( $name ) ), true );
                    if ( username_exists( $username ) ) {
                        $username .= '_' . time();
                    }
                    $random_password = wp_generate_password( 12, false );
                    $user_id = wp_create_user( $username, $random_password, $email );

                    if ( is_wp_error( $user_id ) ) {
                       
                        continue;
                    }

                    wp_update_user( array(
                        'ID' => $user_id,
                        'display_name' => $name
                    ) );

                    wp_mail( $email, 'Your account was created', "Hi $name,\n\nAn account has been created for your booking.\nUsername: $username\nYou can reset your password here: " . wp_lostpassword_url() );

                   
                }
            }

            if ( $this->core->has_entry( 'bookly_successful_booking_complete', $booking->customer_appointment_id, $user_id ) ) {
                
                continue;
            }

             if ( $this->core->has_entry( 'bookly_successful_booking_complete', $booking->customer_appointment_id, $user_id) ) return;

            $this->core->add_creds(
                'bookly_successful_booking_complete',
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $booking->customer_appointment_id,
                'bookly_successful_booking_complete',
                $this->mycred_type
            );

            update_option( 'mycred_last_bookly_processed_id', $booking->customer_appointment_id );

            
        }
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
        $data['creds'] = ( ! empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
        $data['log']   = ( ! empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];
        return $data;
    }
}

endif;

// Add custom cron interval (every minute)
add_filter( 'cron_schedules', function ( $schedules ) {
    $schedules['minute'] = array(
        'interval' => 60,
        'display'  => __( 'Every Minute' )
    );
    return $schedules;
} );

// Clear scheduled cron on deactivation
register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'mycred_bookly_check_appointments' );
} );
