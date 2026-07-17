<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Fluent Support - Agent's Ticket Gets Closed
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Fluent_Support_Close_Hook')):
    class myCRED_Fluent_Support_Close_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'fluent_support_agent_close_ticket',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for ticket closure', 'mycred-toolkit'),
                    'limit' => '0/x'
                )
            ), $hook_prefs, $type);
        }

        /**
         * Run
         * @since 1.0
         * @version 1.0
         */
        public function run()
        {
            add_action('fluent_support/ticket_closed', array($this, 'mycred_fluent_support_agent_ticket_closed'), 10, 2);
        }

        /**
         * Award points for Fluent Support - Agent's Ticket Gets Closed
         * Awards points to the assigned agent when their ticket is closed (regardless of who closes it)
         * @param object $ticket Ticket object
         * @param object $person Person object who closed the ticket (could be agent, admin, or customer)
         */
        public function mycred_fluent_support_agent_ticket_closed($ticket, $person)
        {
            // Get ticket ID and agent_id
            $ticket_id = is_object($ticket) ? absint($ticket->id) : absint($ticket['id']);
            $ticket_agent_id = is_object($ticket) ? (isset($ticket->agent_id) ? absint($ticket->agent_id) : 0) : (isset($ticket['agent_id']) ? absint($ticket['agent_id']) : 0);

            // Only proceed if ticket is assigned to an agent
            if (!$ticket_agent_id || $ticket_agent_id <= 0) {
                return;
            }

            // Load the agent if not already loaded
            $agent = null;
            if (is_object($ticket) && isset($ticket->agent)) {
                $agent = $ticket->agent;
            } else {
                // Need to load the agent
                global $wpdb;
                $agent_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}fs_persons WHERE id=%d AND person_type='agent'",
                    $ticket_agent_id
                ));
                
                if ($agent_data) {
                    // Convert to object-like structure
                    $agent = (object) $agent_data;
                }
            }

            if (!$agent) {
                return;
            }

            // Get agent user ID
            $agent_user_id = isset($agent->user_id) ? absint($agent->user_id) : 0;
            
            // If agent doesn't have a user_id, try to get it from email
            if (!$agent_user_id && isset($agent->email)) {
                $user = get_user_by('email', $agent->email);
                if ($user) {
                    $agent_user_id = absint($user->ID);
                }
            }

            if (!$agent_user_id) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($agent_user_id)) {
                return;
            }

            $reference = 'fluent_support_agent_close_ticket';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $agent_user_id)) {
                return;
            }

            // Check for duplicate entry - prevent awarding points for closing the same ticket twice
            if ($this->core->has_entry($reference, $ticket_id, $agent_user_id, array('ref_type' => 'ticket_close'), $this->mycred_type)) {
                return;
            }

            // Award points to the agent
            $this->core->add_creds(
                $reference,
                $agent_user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $ticket_id,
                array('ref_type' => 'ticket_close'),
                $this->mycred_type
            );
        }

        /**
         * Preference for Fluent Support Close Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;
            ?>

            <div class="hook-instance">
                <h3><?php esc_html_e('Agent\'s Ticket Gets Closed', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('creds')); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>" id="<?php echo esc_attr($this->field_id('creds')); ?>" value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('limit')); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
                            <?php
                            echo wp_kses(
                                $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), $prefs['limit']),
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
                            <label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>" id="<?php echo esc_attr($this->field_id('log')); ?>" value="<?php echo esc_attr($prefs['log']); ?>" class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php
        }

        /**
         * Sanitize Preferences
         */
        function sanitise_preferences($data)
        {

            $data['creds'] = (!empty($data['creds'])) ? floatval($data['creds']) : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            return $data;
        }
    }
endif;
