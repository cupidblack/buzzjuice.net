<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Fluent Support - Open New Ticket
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Fluent_Support_Hook')):
    class myCRED_Fluent_Support_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'fluent_support_open_ticket',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for opening a new ticket', 'mycred-toolkit'),
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
            add_action('fluent_support/ticket_created', array($this, 'mycred_fluent_support_open_ticket'), 10, 2);
            add_action('fluent_support/agent_assigned_to_ticket', array($this, 'mycred_fluent_support_agent_assigned'), 10, 3);
        }

        /**
         * Award points for Fluent Support - Open New Ticket (when agent creates ticket)
         * @param object $ticket Ticket object
         * @param object $customer Customer object
         */
        public function mycred_fluent_support_open_ticket($ticket, $customer)
        {
            global $wpdb;

            // Convert ticket to array if it's an object
            $ticket_data = is_object($ticket) ? (array) $ticket : $ticket;

            // Check if ticket was created by an agent (source is NULL means agent created it)
            // If source is not NULL, it means customer created it, so we skip
            if (isset($ticket_data['source']) && $ticket_data['source'] !== null) {
                return;
            }

            // Get current user
            $user = wp_get_current_user();
            if (!$user || !$user->ID) {
                return;
            }

            $user_id = absint($user->ID);

            // Must be logged in
            if (!is_user_logged_in() || !$user_id) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            // Check if user is an agent
            $agent = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fs_persons WHERE email=%s",
                $user->user_email
            ));

            // Bail if agent not found
            if (!$agent) {
                return;
            }

            // Bail if person entry is not an agent
            if ($agent->person_type !== 'agent') {
                return;
            }

            $ticket_id = absint($ticket_data['id']);

            // Award points using the common method
            $this->award_points_for_ticket($ticket_id, $user_id);
        }

        /**
         * Award points for Fluent Support - Agent Assigned to Ticket
         * @param object $agent Agent object (Person/Agent model)
         * @param object $ticket Ticket object
         * @param object $assigner Person who assigned the ticket
         */
        public function mycred_fluent_support_agent_assigned($agent, $ticket, $assigner)
        {
            // Get agent user ID directly from the object
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

            // Get ticket ID - handle both object and array
            $ticket_id = is_object($ticket) ? absint($ticket->id) : absint($ticket['id']);

            // Award points using the common method
            $this->award_points_for_ticket($ticket_id, $agent_user_id);
        }

        /**
         * Common method to award points for a ticket
         * Prevents duplicate awards if agent creates and gets assigned to same ticket
         * @param int $ticket_id Ticket ID
         * @param int $user_id User ID to award points to
         */
        private function award_points_for_ticket($ticket_id, $user_id)
        {
            $reference = 'fluent_support_open_ticket';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            // Check for duplicate entry - prevent awarding points for the same ticket
            if ($this->core->has_entry($reference, $ticket_id, $user_id, array('ref_type' => 'ticket'), $this->mycred_type)) {
                return;
            }

            // Award points to the agent
            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $ticket_id,
                array('ref_type' => 'ticket'),
                $this->mycred_type
            );
        }

        /**
         * Preference for Fluent Support Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;
            ?>

            <div class="hook-instance">
                <h3><?php esc_html_e('Opening a New Ticket', 'mycred-toolkit'); ?></h3>
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
