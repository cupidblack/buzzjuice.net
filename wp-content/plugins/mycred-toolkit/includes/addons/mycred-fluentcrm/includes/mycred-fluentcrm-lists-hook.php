<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for FluentCRM - Contact Added to List
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_FluentCRM_Lists_Hook')):
    class myCRED_FluentCRM_Lists_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'fluentcrm_list_added',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for getting added to a list in FluentCRM', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_lists' => array(
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
            // Hook into FluentCRM when contact is added to lists
            add_action('fluent_crm/contact_added_to_lists', array($this, 'mycred_fluentcrm_list_added'), 10, 3);
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
         * Award points for Contact Added to List
         */
        public function mycred_fluentcrm_list_added($contact, $listIds, $source = 'wp-admin')
        {
            // Get contact ID
            $contact_id = 0;
            if (is_object($contact) && isset($contact->id)) {
                $contact_id = (int) $contact->id;
            } elseif (is_array($contact) && isset($contact['id'])) {
                $contact_id = (int) $contact['id'];
            }

            if ($contact_id === 0) {
                return;
            }

            // Get WordPress user ID from contact
            $user_id = 0;

            if (is_object($contact)) {
                if (isset($contact->user_id) && $contact->user_id) {
                    $user_id = (int) $contact->user_id;
                } elseif (isset($contact->email) && $contact->email) {
                    $user = get_user_by('email', $contact->email);
                    if ($user) {
                        $user_id = $user->ID;
                    }
                }
            } elseif (is_array($contact)) {
                if (isset($contact['user_id']) && $contact['user_id']) {
                    $user_id = (int) $contact['user_id'];
                } elseif (isset($contact['email']) && $contact['email']) {
                    $user = get_user_by('email', $contact['email']);
                    if ($user) {
                        $user_id = $user->ID;
                    }
                }
            }

            // User must have a WordPress account to award points
            if ($user_id === 0) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'fluentcrm_list_added';

            // Ensure listIds is an array
            if (!is_array($listIds)) {
                $listIds = array($listIds);
            }

            // Process each list that contact was added to
            foreach ($listIds as $list_id) {
                $list_id = absint($list_id);
                if (empty($list_id)) {
                    continue;
                }

                // Check if specific hook is enabled and list matches
                if (
                    isset($prefs['check_specific_hook']) &&
                    $prefs['check_specific_hook'] == '1' &&
                    !empty($prefs['specific_lists']['select_option']) &&
                    in_array($list_id, $prefs['specific_lists']['select_option'])
                ) {

                    // Find the index of this list in the configuration
                    $hook_index = array_search($list_id, $prefs['specific_lists']['select_option']);

                    if (
                        $hook_index !== false &&
                        !empty($prefs['specific_lists']['creds']) &&
                        isset($prefs['specific_lists']['creds'][$hook_index])
                    ) {

                        // Specific Limit Check
                        if ($this->over_hook_limit('specific_lists', $reference, $user_id)) {
                            continue;
                        }

                        // Check if user already got points for this specific list
                        if ($this->core->has_entry($reference, $list_id, $user_id)) {
                            continue;
                        }

                        $creds = $prefs['specific_lists']['creds'][$hook_index];
                        $log = isset($prefs['specific_lists']['log'][$hook_index]) ? $prefs['specific_lists']['log'][$hook_index] : $prefs['log'];

                        $this->core->add_creds(
                            $reference,
                            $user_id,
                            $creds,
                            $log,
                            $list_id,
                            array('ref_type' => 'list'),
                            $this->mycred_type
                        );

                        continue; // Exit after awarding specific points (exclusive behavior)
                    }
                }

                // General Award - only if specific hook is not enabled or list doesn't match
                // Check limit for general hook
                if ($this->over_hook_limit('', $reference, $user_id)) {
                    continue;
                }

                // Check if user already got points for this list
                if ($this->core->has_entry($reference, $list_id, $user_id)) {
                    continue;
                }

                $this->core->add_creds(
                    $reference,
                    $user_id,
                    $prefs['creds'],
                    $prefs['log'],
                    $list_id,
                    array('ref_type' => 'list'),
                    $this->mycred_type
                );
            }
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_fluentcrm_lists_arrange_data($specific_hook_data)
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
         * Preference for FluentCRM Lists Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Get all FluentCRM lists
            $lists = array();
            if (class_exists('\FluentCrm\App\Models\Lists')) {
                $list_model = new \FluentCrm\App\Models\Lists();
                $lists = $list_model->orderBy('title', 'ASC')->get();
            }

            ?>

            <!-- General List Rewards Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Added to any list', 'mycred-toolkit'); ?></h3>
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
                                for="<?php echo $this->field_id('log'); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('log'); ?>"
                                id="<?php echo $this->field_id('log'); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- General List Rewards Ends -->

            <!-- Specific List Rewards Starts -->
            <?php
            $specific_data = array(
                array(
                    'creds' => 0,
                    'log' => __('%plural% for getting added to a specific list', 'mycred-toolkit'),
                    'select_option' => 0
                ),
            );

            if (!empty($prefs['specific_lists']['creds']) && count($prefs['specific_lists']['creds']) > 0) {
                $specific_data = $this->mycred_fluentcrm_lists_arrange_data($prefs['specific_lists']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-fluentcrm-lists">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Added to specific list', 'mycred-toolkit'); ?></h3>
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
                            'label' => __('Enable', 'mycred-toolkit'),
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
                    <div class="fluentcrm_lists_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_lists' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_lists' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Select List', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-fluentcrm-lists-options"
                                        name="<?php echo $this->specific_field_name(array('specific_lists' => 'select_option')); ?>">

                                        <option value="0"><?php esc_html_e('Select List', 'mycred-toolkit'); ?></option>
                                        <?php
                                        if (!empty($lists)) {
                                            foreach ($lists as $list) {
                                                $list_id = is_object($list) ? $list->id : (isset($list['id']) ? $list['id'] : 0);
                                                $list_title = is_object($list) ? $list->title : (isset($list['title']) ? $list['title'] : '');
                                                $selected = (isset($label['select_option']) && $label['select_option'] == $list_id) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($list_id) . '" ' . $selected . '>' . esc_html($list_title) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_lists' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                    <span
                                        class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small mycred-add-specific-fluentcrm-lists-hook add_button" type="button">Add
                                        More</button>
                                    <button class="button button-small mycred-remove-specific-fluentcrm-lists-hook"
                                        type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <!-- Specific List Rewards Ends -->

            <!-- Hook Limit Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Limit', 'mycred-toolkit'); ?></h3>
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

            if (isset($data['specific_lists'])) {
                foreach ($data['specific_lists']['creds'] as $key => $value) {
                    $new_val = floatval($value);
                    $data['specific_lists']['creds'][$key] = $new_val;

                    $log_val = isset($data['specific_lists']['log'][$key]) ? $data['specific_lists']['log'][$key] : '';
                    $data['specific_lists']['log'][$key] = sanitize_text_field($log_val);

                    $opt_val = isset($data['specific_lists']['select_option'][$key]) ? $data['specific_lists']['select_option'][$key] : 0;
                    $data['specific_lists']['select_option'][$key] = intval($opt_val);
                }
            }

            return $data;
        }

        public function custom_limit()
        {
            return array(
                'x' => __('No limit', 'mycred-toolkit'),
                'd' => __('/ Day', 'mycred-toolkit'),
                'w' => __('/ Week', 'mycred-toolkit'),
                'm' => __('/ Month', 'mycred-toolkit'),
            );
        }
    }
endif;
