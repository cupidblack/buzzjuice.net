<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Hook for Booking Calendar booking complete
 * 
 * 
 */

if ( ! class_exists( 'myCRED_BookingCalendar_Complete_Booking_Hook' ) ) :
    class myCRED_BookingCalendar_Complete_Booking_Hook extends myCRED_Hook {
        public $user_id = 0;
        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {
            parent::__construct( array(
                'id' => 'bookingcalendar_successful_booking_complete',
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

            // Hook into booking completion
            add_action('wpbc_set_booking_approved', array($this, 'booking_approved'), 10, 2);
            
            // Hook for AJAX submissions
            add_action('wp_ajax_WPBC_AJX_BOOKING_ACTIONS', array($this, 'booking_ajax_handler'), 5);
            add_action('wp_ajax_nopriv_WPBC_AJX_BOOKING_ACTIONS', array($this, 'booking_ajax_handler'), 5);
            
            // Hook into form submission completion
            add_action('wp_footer', array($this, 'add_booking_success_script'));
        }
        
       
        /**
         * Handle booking approval (alternative hook)
         */
        public function booking_approved($booking_id, $approve_status = 1) {
            if ($approve_status == 1) {
                $this->booking_completed($booking_id);
            }
        }
        
        /**
         * Handle AJAX booking submissions (for free version)
         */
        public function booking_ajax_handler() {
            
            // Check if this is a booking insertion (original booking submission)
            if (isset($_POST['action_param']) && $_POST['action_param'] === 'insert_booking') {
                $this->handle_booking_insertion();
                return;
            }
            
            // Check if this is a booking approval and get the actual booking user
            if (isset($_POST['action_params']['booking_action']) && $_POST['action_params']['booking_action'] === 'set_booking_approved') {
                $booking_id = isset($_POST['action_params']['booking_id']) ? intval($_POST['action_params']['booking_id']) : 0;
                
                
                if ($booking_id > 0) {
                    $this->handle_booking_approval($booking_id);
                }
                return;
            }
            
            
        }
        
        /**
         * Handle original booking insertion
         */
        private function handle_booking_insertion() {

            $user_id = get_current_user_id();
            
            if (!$user_id || $this->core->exclude_user($user_id)) {
                return;
            }
            
            // Check for cooldown to prevent spam (5 minutes)
            $last_reward = get_user_meta($user_id, 'last_booking_reward_time', true);
            if ($last_reward && (time() - $last_reward) < 300) {
                
                return;
            }
            
            $this->award_points_to_user($user_id, 'booking_insertion');
        }
        
        /**
         * Handle booking approval - find the original booking user
         */
        private function handle_booking_approval($booking_id) {
            global $wpdb;
            
            // Get booking details from database
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}booking WHERE booking_id = %d",
                $booking_id
            ));
            
            if (!$booking) {
                return;
            }

            
            // Try to get user ID from different possible fields
            $booking_user_id = 0;
            $booking_email = '';
            
            // Method 1: Check if booking has a user_id field
            if (isset($booking->user_id) && $booking->user_id > 0) {
                $booking_user_id = $booking->user_id;
                
            }
            // Method 2: Parse the form data (Booking Calendar specific format)
            elseif (isset($booking->form) && !empty($booking->form)) {

                // Parse the specific format: 
                $form_fields = explode('~', $booking->form);
                
                foreach ($form_fields as $field) {
                    $parts = explode('^', $field);
                    
                    // Look for email fields
                    if (count($parts) >= 3 && ($parts[0] === 'email' || strpos($parts[1], 'email') !== false)) {
                        $potential_email = $parts[2];
                        if (is_email($potential_email)) {
                            $booking_email = $potential_email;
                            break;
                        }
                    }
                }
                
                // If we found an email, try to find the user
                if ($booking_email) {
                    $user = get_user_by('email', $booking_email);
                    if ($user) {
                        $booking_user_id = $user->ID;
                       
                    } else {
                        
                    }
                }
            }
            // Method 3: Check for direct email field
            elseif (isset($booking->email) && !empty($booking->email)) {
                $user = get_user_by('email', $booking->email);
                if ($user) {
                    $booking_user_id = $user->ID;
                    $booking_email = $booking->email;
                   
                }
            }
            
            if ($booking_user_id > 0) {
                // Check if this user should be excluded
                if ($this->core->exclude_user($booking_user_id)) {
                   
                    return;
                }
                
                // Check if already rewarded
                $already_rewarded = get_user_meta($booking_user_id, 'mycred_booking_' . $booking_id, true);
                if ($already_rewarded) {
                   
                    return;
                }
                
                $this->award_points_to_user($booking_user_id, 'booking_approval', $booking_id);
            } else {
               
                
                // Store booking for later processing when user logs in
                $pending_rewards = get_option('mycred_pending_booking_rewards', array());
                $pending_rewards[$booking_id] = array(
                    'booking_id' => $booking_id,
                    'timestamp' => time(),
                    'booking_data' => $booking,
                    'parsed_email' => $booking_email // Store the parsed email for later matching
                );
                update_option('mycred_pending_booking_rewards', $pending_rewards);
                
            }
        }
        
        /**
         * Award points to a specific user
         */
        private function award_points_to_user($user_id, $context, $booking_id = 0) {
           
            // Award points
            $result = $this->core->add_creds(
                'bookingcalendar_successful_booking_complete',
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $booking_id,
                array(
                    'booking_id' => $booking_id,
                    'context' => $context,
                    'timestamp' => time()
                ),
                $this->mycred_type
            );
            
            if ($result) {
                if ($booking_id > 0) {
                    update_user_meta($user_id, 'mycred_booking_' . $booking_id, time());
                }
                update_user_meta($user_id, 'last_booking_reward_time', time());
               
            } else {
               
            }
        }
        
        /**
         * Add JavaScript to detect successful booking submissions
         */
        public function add_booking_success_script() {
            if (!is_user_logged_in()) {
                return;
            }
            
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Listen for booking calendar success messages
                $(document).on('DOMNodeInserted', function(e) {
                    var target = $(e.target);
                    
                    // Check for success messages (adjust selectors based on your booking calendar version)
                    if (target.hasClass('wpbc_front_end__message') || 
                        target.find('.wpbc_front_end__message').length ||
                        target.text().includes('successfully') ||
                        target.text().includes('confirmed')) {
                        
                        // Send AJAX request to award points
                        $.post(ajaxurl, {
                            action: 'award_booking_points',
                            nonce: '<?php echo wp_create_nonce('award_booking_points'); ?>'
                        });
                    }
                });
            });
            </script>
            <?php
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

// Add hook to check for pending rewards when user logs in
add_action('wp_login', 'check_pending_booking_rewards', 10, 2);

function check_pending_booking_rewards($user_login, $user) {
    if (!function_exists('mycred') || !$user) {
        return;
    }
    
    $pending_rewards = get_option('mycred_pending_booking_rewards', array());
    if (empty($pending_rewards)) {
        return;
    }
    
    $mycred = mycred();
    $hooks = get_option('mycred_pref_hooks', array());
    
    if (!isset($hooks['bookingcalendar_successful_booking_complete'])) {
        return;
    }
    
    $hook_settings = $hooks['bookingcalendar_successful_booking_complete'];
    $user_email = $user->user_email;
    $awarded_any = false;
    
    
    
    foreach ($pending_rewards as $booking_id => $pending_data) {
        $booking_data = $pending_data['booking_data'];
        
        // Check if this booking belongs to the logged-in user
        $is_user_booking = false;
        
        // Method 1: Check parsed email (from new version)
        if (isset($pending_data['parsed_email']) && $pending_data['parsed_email'] === $user_email) {
            $is_user_booking = true;
            
        }
        // Method 2: Check direct email field
        elseif (isset($booking_data->email) && $booking_data->email === $user_email) {
            $is_user_booking = true;
           
        }
        // Method 3: Parse form data again
        elseif (isset($booking_data->form) && !empty($booking_data->form)) {
            $form_fields = explode('~', $booking_data->form);
            
            foreach ($form_fields as $field) {
                $parts = explode('^', $field);
                
                if (count($parts) >= 3 && ($parts[0] === 'email' || strpos($parts[1], 'email') !== false)) {
                    if (is_email($parts[2]) && $parts[2] === $user_email) {
                        $is_user_booking = true;
                        
                        break;
                    }
                }
            }
        }
        
        if ($is_user_booking) {
            // Check if not already rewarded
            $already_rewarded = get_user_meta($user->ID, 'mycred_booking_' . $booking_id, true);
            if (!$already_rewarded && !$mycred->exclude_user($user->ID)) {
                
                $result = $mycred->add_creds(
                    'bookingcalendar_successful_booking_complete',
                    $user->ID,
                    $hook_settings['creds'],
                    $hook_settings['log'] . ' (Pending reward)',
                    $booking_id,
                    array(
                        'booking_id' => $booking_id,
                        'context' => 'pending_login_reward',
                        'original_booking_time' => $pending_data['timestamp']
                    ),
                    'mycred_default'
                );
                
                if ($result) {
                    update_user_meta($user->ID, 'mycred_booking_' . $booking_id, time());
                   
                    $awarded_any = true;
                }
            }
            
            // Remove from pending list
            unset($pending_rewards[$booking_id]);
        }
    }
    
    // Update pending rewards list
    if ($awarded_any || count($pending_rewards) !== count(get_option('mycred_pending_booking_rewards', array()))) {
        update_option('mycred_pending_booking_rewards', $pending_rewards);
    }
}

// Clean up old pending rewards (run daily)
add_action('wp_scheduled_delete', 'cleanup_old_pending_booking_rewards');

function cleanup_old_pending_booking_rewards() {
    $pending_rewards = get_option('mycred_pending_booking_rewards', array());
    if (empty($pending_rewards)) {
        return;
    }
    
    $cutoff_time = time() - (30 * DAY_IN_SECONDS); // 30 days
    $cleaned_rewards = array();
    
    foreach ($pending_rewards as $booking_id => $pending_data) {
        if ($pending_data['timestamp'] > $cutoff_time) {
            $cleaned_rewards[$booking_id] = $pending_data;
        }
    }
    
    if (count($cleaned_rewards) !== count($pending_rewards)) {
        update_option('mycred_pending_booking_rewards', $cleaned_rewards);
        
    }
}
add_action('wp_ajax_award_booking_points', 'handle_js_booking_reward');
add_action('wp_ajax_nopriv_award_booking_points', 'handle_js_booking_reward');

function handle_js_booking_reward() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'award_booking_points')) {
        wp_die('Security check failed');
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die('User not logged in');
    }
    
    // Check cooldown (10 minutes for JS triggered rewards)
    $last_reward = get_user_meta($user_id, 'last_js_booking_reward', true);
    if ($last_reward && (time() - $last_reward) < 600) {
        wp_die('Cooldown active');
    }
    
    // Check if myCred hook is active and configured
    if (function_exists('mycred') && class_exists('myCRED_BookingCalendar_Complete_Booking_Hook')) {
        $mycred = mycred();
        
        // Get hook settings
        $hooks = get_option('mycred_pref_hooks', array());
        if (isset($hooks['bookingcalendar_successful_booking_complete']) && 
            $hooks['bookingcalendar_successful_booking_complete']['creds'] > 0) {
            
            $creds = $hooks['bookingcalendar_successful_booking_complete']['creds'];
            $log = $hooks['bookingcalendar_successful_booking_complete']['log'];
            
            $result = $mycred->add_creds(
                'bookingcalendar_successful_booking_complete',
                $user_id,
                $creds,
                $log,
                0,
                array('js_triggered' => true),
                'mycred_default'
            );
            
            if ($result) {
                update_user_meta($user_id, 'last_js_booking_reward', time());
                
                wp_send_json_success('Points awarded');
            }
        }
    }
    
    wp_send_json_error('Failed to award points');
}


add_action('wp_footer', 'debug_booking_calendar_hooks');

function debug_booking_calendar_hooks() {
    if (!current_user_can('manage_options') || !isset($_GET['debug_booking_hooks'])) {
        return;
    }
    
    echo '<div style="position: fixed; top: 0; left: 0; background: white; padding: 20px; z-index: 9999; max-width: 500px; border: 2px solid red;">';
    echo '<h3>Booking Calendar Debug Info</h3>';
    
    // Check if Booking Calendar is active
    if (class_exists('WPBC_AJX__REQUEST')) {
        echo '<p>✅ Booking Calendar is active</p>';
    } else {
        echo '<p>❌ Booking Calendar not detected</p>';
    }
    
    // Check if myCred is active
    if (function_exists('mycred')) {
        echo '<p>✅ myCred is active</p>';
        
        // Check hook settings
        $hooks = get_option('mycred_pref_hooks', array());
        if (isset($hooks['bookingcalendar_successful_booking_complete'])) {
            echo '<p>✅ Hook is configured</p>';
            echo '<p>Points: ' . $hooks['bookingcalendar_successful_booking_complete']['creds'] . '</p>';
        } else {
            echo '<p>❌ Hook not configured</p>';
        }
    } else {
        echo '<p>❌ myCred not detected</p>';
    }
    
    // Check current user
    if (is_user_logged_in()) {
        echo '<p>✅ User logged in (ID: ' . get_current_user_id() . ')</p>';
        $user = wp_get_current_user();
        echo '<p>Role: ' . implode(', ', $user->roles) . '</p>';
    } else {
        echo '<p>❌ No user logged in</p>';
    }
    
    echo '<p><small>Add ?debug_booking_hooks=1 to URL to see this debug info</small></p>';
    echo '</div>';
}