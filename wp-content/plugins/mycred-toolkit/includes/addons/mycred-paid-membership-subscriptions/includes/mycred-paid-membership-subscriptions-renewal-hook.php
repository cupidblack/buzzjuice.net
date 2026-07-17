<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renewal Hook for Paid Membership Subscriptions plugin
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('MyCred_PMS_Renewal_Subscription_Hook')):
    class MyCred_PMS_Renewal_Subscription_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'pms_renewal_subscription',
                'defaults' => array(
                    'creds' => 10,
                    'log' => __('%plural% for subscription renewal', 'myCred_pms'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_subscriptions' => array(
                        'creds' => array(),
                        'log' => array(),
                        'select_option' => array(),
                    ),
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
            add_action('pms_payment_update', array($this, 'mycred_pms_renew_subscription'), 10, 3);
        }

        /**
         * Generate specific field name for repeatable fields
         */
        public function specific_field_name($field = '')
        {
            $hook_prefs_key = 'mycred_pref_hooks';

            if (is_array($field)) {
                $array = array();
                foreach ($field as $parent => $child) {
                    if (!is_numeric($parent)) {
                        $array[] = $parent;
                    }

                    if (!empty($child) && !is_array($child)) {
                        $array[] = $child;
                    }
                }
                $field = '[' . implode('][', $array) . ']';
            } else {
                $field = '[' . $field . ']';
            }

            $option_id = 'mycred_pref_hooks';
            if (!$this->is_main_type) {
                $option_id = $option_id . '_' . $this->mycred_type;
            }

            return $option_id . '[hook_prefs][' . $this->id . ']' . $field . '[]';
        }

        /**
         * Award points for Renewal on Subscription
         */
        public function mycred_pms_renew_subscription($payment_id, $new_data, $old_data)
        {

            // Check if new status is completed
            if (!isset($new_data['status']) || $new_data['status'] != 'completed') {
                return;
            }

            // Preventing double awarding if it was already completed
            if (isset($old_data['status']) && $old_data['status'] == 'completed') {
                return;
            }

            $payment = pms_get_payment($payment_id);

            if (empty($payment)) {
                return;
            }

            // Check if this is a renewal payment
            if (!isset($payment->type) || $payment->type != 'subscription_renewal_payment') {
                return;
            }

            $subscription_plan_id = $payment->subscription_id;
            $user_id = $payment->user_id;

            if (empty($user_id)) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }


            $prefs = $this->prefs;
            $reference = 'pms_renewal_subscription';

            // Check if specific hook is enabled and subscription plan matches
            if (
                isset($prefs['check_specific_hook']) &&
                $prefs['check_specific_hook'] == '1' &&
                !empty($prefs['specific_subscriptions']['select_option']) &&
                in_array($subscription_plan_id, $prefs['specific_subscriptions']['select_option'])
            ) {

                // Find the index of this plan in the configuration
                $hook_index = array_search($subscription_plan_id, $prefs['specific_subscriptions']['select_option']);

                if (
                    $hook_index !== false &&
                    !empty($prefs['specific_subscriptions']['creds']) &&
                    isset($prefs['specific_subscriptions']['creds'][$hook_index])
                ) {

                    // Specific Limit Check
                    if ($this->over_hook_limit('specific_subscriptions', $reference, $user_id)) {
                        return;
                    }

                    $creds = $prefs['specific_subscriptions']['creds'][$hook_index];
                    $log = isset($prefs['specific_subscriptions']['log'][$hook_index]) ? $prefs['specific_subscriptions']['log'][$hook_index] : $prefs['log'];

                    $this->core->add_creds(
                        $reference,
                        $user_id,
                        $creds,
                        $log,
                        $subscription_plan_id,
                        array('ref_type' => 'post'),
                        $this->mycred_type
                    );

                    return; // Exit after awarding specific points (exclusive behavior)
                }
            }

            // General Award

            // Check limit for general hook
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $subscription_plan_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_pms_arrange_data($specific_hook_data)
        {
            $hook_data = array();
            if (isset($specific_hook_data['creds']) && is_array($specific_hook_data['creds'])) {
                foreach ($specific_hook_data['creds'] as $key => $value) {
                    $hook_data[$key]['creds'] = $value;
                    $hook_data[$key]['log'] = isset($specific_hook_data['log'][$key]) ? $specific_hook_data['log'][$key] : '';
                    $hook_data[$key]['select_option'] = isset($specific_hook_data['select_option'][$key]) ? $specific_hook_data['select_option'][$key] : '';
                }
            }
            return $hook_data;
        }

        /**
         * Preference for PMS Renewal Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Fetch all subscription plans
            $plans = array();
            if (function_exists('pms_get_subscription_plans')) {
                $plans = pms_get_subscription_plans(false);
            }

            ?>

            <!-- General Reward Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Renew any subscription', 'myCred_pms'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id('creds'); ?>"><?php echo $this->core->plural(); ?></label>
                            <input type="text" name="<?php echo $this->field_name('creds'); ?>"
                                id="<?php echo $this->field_id('creds'); ?>"
                                value="<?php echo $this->core->number($prefs['creds']); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('log'); ?>"><?php esc_html_e('Log Template', 'myCred_pms'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('log'); ?>"
                                id="<?php echo $this->field_id('log'); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- General Reward Ends -->

            <!-- Specific Subscription Rewards Starts -->
            <?php
            $specific_data = array(
                array(
                    'creds' => 10,
                    'log' => __('%plural% for subscription renewal', 'myCred_pms'),
                    'select_option' => 0
                ),
            );

            if (!empty($prefs['specific_subscriptions']['creds']) && count($prefs['specific_subscriptions']['creds']) > 0) {
                $specific_data = $this->mycred_pms_arrange_data($prefs['specific_subscriptions']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-pms-renewal">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Renew specific subscription', 'myCred_pms'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php
                    $is_enabled = (isset($prefs['check_specific_hook']) && $prefs['check_specific_hook'] == 1);

                    mycred_create_toggle_field(
                        array(
                            'id' => $this->field_id('check_specific_hook'),
                            'name' => $this->field_name('check_specific_hook'),
                            'label' => __('Enable', 'myCred_pms'),
                            'after' => false,
                        ),
                        1,
                        $is_enabled
                    );
                    ?>

                </div>
                <?php
                foreach ($specific_data as $hook_idx => $label) {
                    ?>
                    <div class="pms_renewal_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_subscriptions' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_subscriptions' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Select Subscription', 'myCred_pms'); ?></label>
                                    <select class="form-control mycred-pms-renewal-options"
                                        name="<?php echo $this->specific_field_name(array('specific_subscriptions' => 'select_option')); ?>">

                                        <option value="0"><?php esc_html_e('Select Subscription', 'myCred_pms'); ?></option>
                                        <?php
                                        if (!empty($plans)) {
                                            foreach ($plans as $plan) {
                                                $selected = (isset($label['select_option']) && $label['select_option'] == $plan->id) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($plan->id) . '" ' . $selected . '>' . esc_html($plan->name) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Log Template', 'myCred_pms'); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_subscriptions' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                    <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small mycred-add-specific-pms-renewal-hook add_button"
                                        type="button">Add
                                        More
                                    </button>
                                    <button class="button button-small mycred-remove-specific-pms-renewal-hook" type="button">Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <!-- Specific Subscription Rewards Ends -->

            <!-- Hook Limit Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Limit', 'myCred_pms'); ?></h3>
                <div class="row">
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <?php add_filter('mycred_hook_limits', array($this, 'custom_limit')); ?>
                            <label for="<?php echo $this->field_id('limit'); ?>"></label>
                            <?php echo $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), esc_attr($prefs['limit'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Hook Limit Ends -->

            <?php
        }

        /**
         * Sanitize Preferences
         */
        function sanitise_preferences($data)
        {

            $data['creds'] = (!empty($data['creds'])) ? floatval($data['creds']) : 0;
            $data['check_specific_hook'] = (isset($data['check_specific_hook']) && $data['check_specific_hook'] == '1') ? 1 : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            if (isset($data['specific_subscriptions'])) {
                foreach ($data['specific_subscriptions']['creds'] as $key => $value) {
                    $new_val = floatval($value);
                    $data['specific_subscriptions']['creds'][$key] = $new_val;

                    $log_val = isset($data['specific_subscriptions']['log'][$key]) ? $data['specific_subscriptions']['log'][$key] : '';
                    $data['specific_subscriptions']['log'][$key] = sanitize_text_field($log_val);

                    $opt_val = isset($data['specific_subscriptions']['select_option'][$key]) ? $data['specific_subscriptions']['select_option'][$key] : 0;
                    $data['specific_subscriptions']['select_option'][$key] = intval($opt_val);
                }
            }

            return $data;
        }

        public function custom_limit()
        {
            return array(
                'x' => __('No limit', 'myCred_pms'),
                'd' => __('/ Day', 'myCred_pms'),
                'w' => __('/ Week', 'myCred_pms'),
                'm' => __('/ Month', 'myCred_pms'),
            );
        }
    }
endif;
