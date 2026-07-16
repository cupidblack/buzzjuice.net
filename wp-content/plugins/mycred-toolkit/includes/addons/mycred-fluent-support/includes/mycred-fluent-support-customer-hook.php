<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Fluent Support - Customer Opens New Ticket
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Fluent_Support_Customer_Hook')):
    class myCRED_Fluent_Support_Customer_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'fluent_support_customer_open_ticket',
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
            add_action('fluent_support/ticket_created', array($this, 'mycred_fluent_support_customer_open_ticket'), 10, 2);
            add_action('fluent_support/ticket_created_behalf_of_customer', array($this, 'mycred_fluent_support_customer_ticket_on_behalf'), 10, 3);
        }

        /**
         * Award points for Fluent Support - Customer Opens New Ticket
         * @param object $ticket Ticket object
         * @param object $customer Customer object
         */
        public function mycred_fluent_support_customer_open_ticket($ticket, $customer)
        {
            // Convert ticket to array if it's an object
            $ticket_data = is_object($ticket) ? (array) $ticket : $ticket;

            // Check if ticket was created by a customer (source is NOT NULL means customer created it)
            // If source is NULL, it means agent created it, so we skip
            if (!isset($ticket_data['source']) || $ticket_data['source'] === null) {
                return;
            }

            // Get customer user ID
            $customer_user_id = isset($customer->user_id) ? absint($customer->user_id) : 0;
            
            // If customer doesn't have a user_id, try to get it from email
            if (!$customer_user_id && isset($customer->email)) {
                $user = get_user_by('email', $customer->email);
                if ($user) {
                    $customer_user_id = absint($user->ID);
                }
            }

            // Only award points if customer has a WordPress user account
            if (!$customer_user_id) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($customer_user_id)) {
                return;
            }

            $ticket_id = absint($ticket_data['id']);

            $reference = 'fluent_support_customer_open_ticket';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $customer_user_id)) {
                return;
            }

            // Check for duplicate entry - prevent awarding points for the same ticket
            if ($this->core->has_entry($reference, $ticket_id, $customer_user_id, array('ref_type' => 'ticket'), $this->mycred_type)) {
                return;
            }

            // Award points to the customer
            $this->core->add_creds(
                $reference,
                $customer_user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $ticket_id,
                array('ref_type' => 'ticket'),
                $this->mycred_type
            );
        }

        /**
         * Award points for Fluent Support - Customer Ticket Created On Behalf Of Customer
         * @param object $ticket Ticket object
         * @param object $customer Customer object
         * @param object $agent Agent object who created the ticket on behalf of customer
         */
        public function mycred_fluent_support_customer_ticket_on_behalf($ticket, $customer, $agent)
        {
            // Get customer user ID
            $customer_user_id = isset($customer->user_id) ? absint($customer->user_id) : 0;
            
            // If customer doesn't have a user_id, try to get it from email
            if (!$customer_user_id && isset($customer->email)) {
                $user = get_user_by('email', $customer->email);
                if ($user) {
                    $customer_user_id = absint($user->ID);
                }
            }

            // Only award points if customer has a WordPress user account
            if (!$customer_user_id) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($customer_user_id)) {
                return;
            }

            $ticket_id = is_object($ticket) ? absint($ticket->id) : absint($ticket['id']);

            $reference = 'fluent_support_customer_open_ticket';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $customer_user_id)) {
                return;
            }

            // Check for duplicate entry - prevent awarding points for the same ticket
            if ($this->core->has_entry($reference, $ticket_id, $customer_user_id, array('ref_type' => 'ticket'), $this->mycred_type)) {
                return;
            }

            // Award points to the customer
            $this->core->add_creds(
                $reference,
                $customer_user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $ticket_id,
                array('ref_type' => 'ticket'),
                $this->mycred_type
            );
        }

        /**
         * Preference for Fluent Support Customer Hook
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
