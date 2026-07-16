<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for ARForms Field Value Submission
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_ARForms_Field_Value_Hook')):
    class myCRED_ARForms_Field_Value_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'arforms_field_value_submit',
                'defaults' => array(
                    'creds' => 0,
                    'log' => __('%plural% for submitting field value', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'field_name' => '',
                    'field_value' => '',
                    'check_specific_hook' => 0,
                    'specific_field_value_submit' => array(
                        'creds' => array(),
                        'log' => array(),
                        'select_form' => array(),
                        'select_field' => array(),
                        'field_value' => array(),
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
            add_action('arfliteentryexecute', array($this, 'mycred_arforms_field_value_rewards'), 10, 4);
            add_action('arfentryexecute', array($this, 'mycred_arforms_field_value_rewards'), 10, 4);
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

        public function mycred_arforms_field_value_rewards($params, $errors, $form, $item_meta_values)
        {

            global $wpdb;

            // ---------------------------------------------
            // GET FORM FIELDS (NAME => VALUE)
            // ---------------------------------------------
            $form_fields = array();

            $form_id = isset($form->id) ? intval($form->id) : 0;

            if ($form_id > 0) {
                $fields_table = "{$wpdb->prefix}arf_fields";
                $sql_query = $wpdb->prepare("SELECT id, name FROM {$fields_table} WHERE form_id = %d", $form_id);
                $fields = $wpdb->get_results($sql_query);

                if ($fields) {
                    foreach ($fields as $field) {
                        $field_id = $field->id;
                        $field_name = $field->name;
                        $value = isset($item_meta_values[$field_id]) ? $item_meta_values[$field_id] : '';

                        // Store NAME => VALUE for name-based matching
                        $form_fields[$field_name] = $value;

                        // Also store ID => VALUE for ID matching
                        $form_fields[$field_id] = $value;
                    }
                }
            }

            // Stop if validation errors exist
            if (!empty($errors)) return;

            // Preferences
            $prefs = $this->prefs;

            $entry_id = isset($params['entry_id']) ? intval($params['entry_id']) : 0;
            $user_id  = isset($params['user_id']) ? intval($params['user_id']) : get_current_user_id();

            if (empty($user_id)) return;
            if ($this->core->exclude_user($user_id)) return;

            $reference = 'arforms_field_value_submit';

            // --------------------------------------------------------------
            // 1️⃣ GENERAL SETTINGS — Match Field Name/ID + Field Value
            // --------------------------------------------------------------

            $general_field_name  = isset($prefs['field_name']) ? trim($prefs['field_name']) : '';
            $general_field_value = isset($prefs['field_value']) ? trim($prefs['field_value']) : '';

            $general_match_pass = true;

            if ($general_field_name !== '' && $general_field_value !== '') {

                // Check both name and ID keys
                if (
                    (!isset($form_fields[$general_field_name]) || $form_fields[$general_field_name] != $general_field_value) &&
                    (!isset($form_fields[intval($general_field_name)]) || $form_fields[intval($general_field_name)] != $general_field_value)
                ) {
                    $general_match_pass = false;
                }
            }

            // If specific hook is ON → we handle it separately
            $use_specific = isset($prefs['check_specific_hook']) && $prefs['check_specific_hook'] == 1;

            // --------------------------------------------------------------
            // SPECIFIC SETTINGS — Only if Enabled
            // --------------------------------------------------------------

            if ($use_specific) {

                $data = $prefs['specific_field_value_submit'];

                foreach ($data['select_form'] as $index => $selected_form) {

                    // Skip if form does not match AND not set to "Any Form"
                    if ($selected_form != 0 && intval($selected_form) !== intval($form_id)) {
                        continue;
                    }

                    $selected_field_id = isset($data['select_field'][$index]) ? $data['select_field'][$index] : '';
                    $expected_value    = isset($data['field_value'][$index]) ? trim($data['field_value'][$index]) : '';

                    // BOTH must be set to check
                    if ($selected_field_id !== '' && $expected_value !== '') {

                        if (
                            !isset($form_fields[$selected_field_id]) ||
                            $form_fields[$selected_field_id] != $expected_value
                        ) {
                            // SPECIFIC RULE FAILED → skip this rule
                            continue;
                        }
                    }

                    // Limit check
                    if ($this->over_hook_limit('specific_field_value_submit', $reference, $user_id)) {
                        return;
                    }


                    // Award specific rule points
                    $this->core->add_creds(
                        $reference,
                        $user_id,
                        $data['creds'][$index],
                        $data['log'][$index],
                        $entry_id,
                        array('ref_type' => 'post'),
                        $this->mycred_type
                    );

                    return; // Stop after first successful specific rule
                }

            
            }

            // --------------------------------------------------------------
            // 3️⃣ GENERAL RULE (Only if Field Match Passes)
            // --------------------------------------------------------------

            if (!$general_match_pass) {
                return; // Field name/value does not match
            }

            // Limit check
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            // Award general points
            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $entry_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }


        /**
         * Get form fields from ARForms
         */
        private function get_form_fields($form_id)
        {
            global $wpdb;

            if (empty($form_id) || $form_id == '0') {
                return array();
            }

            // ARForms stores fields in a custom table
            $table_name = $wpdb->prefix . 'arf_fields';

            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, type FROM {$table_name} WHERE form_id = %d ORDER BY name ASC",
                $form_id
            ));

            return $fields;
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_arforms_field_value_arrange_data($specific_hook_data)
        {
            $hook_data = array();
            foreach ($specific_hook_data['creds'] as $key => $value) {
                $hook_data[$key]['creds'] = $value;
                $hook_data[$key]['log'] = $specific_hook_data['log'][$key];
                $hook_data[$key]['select_form'] = $specific_hook_data['select_form'][$key];
                $hook_data[$key]['select_field'] = isset($specific_hook_data['select_field'][$key]) ? $specific_hook_data['select_field'][$key] : '';
                $hook_data[$key]['field_value'] = isset($specific_hook_data['field_value'][$key]) ? $specific_hook_data['field_value'][$key] : '';
            }
            return $hook_data;
        }

        /**
         * Preference for ARForms Field Value Submission Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Fetch all ARForms
            global $wpdb;
            $forms_table = $wpdb->prefix . 'arf_forms';
            $arforms = $wpdb->get_results("
                SELECT id, name 
                FROM {$forms_table} 
                WHERE is_template = 0 
                ORDER BY name ASC
            ");

            ?>

            <!-- General Field Value Submission Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('General', 'mycred-toolkit'); ?></h3>
                <p class="description">
                    <?php esc_html_e('Award points when a user submits a specific field value on any form.', 'mycred-toolkit'); ?>
                </p>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id('creds'); ?>"><?php echo $this->core->plural(); ?></label>
                            <input type="text" name="<?php echo $this->field_name('creds'); ?>"
                                id="<?php echo $this->field_id('creds'); ?>"
                                value="<?php echo $this->core->number($prefs['creds']); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('field_name'); ?>"><?php esc_html_e('Field Name or ID', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('field_name'); ?>"
                                id="<?php echo $this->field_id('field_name'); ?>"
                                value="<?php echo esc_attr($prefs['field_name']); ?>" class="form-control"
                                placeholder="<?php esc_attr_e('e.g., Number or 123', 'mycred-toolkit'); ?>" />
                            <span
                                class="description"><?php esc_html_e('Enter field name (e.g., "Number") or numeric ID', 'mycred-toolkit'); ?></span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('field_value'); ?>"><?php esc_html_e('Field Value', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('field_value'); ?>"
                                id="<?php echo $this->field_id('field_value'); ?>"
                                value="<?php echo esc_attr($prefs['field_value']); ?>" class="form-control"
                                placeholder="<?php esc_attr_e('Enter expected value', 'mycred-toolkit'); ?>" />
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('log'); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('log'); ?>"
                                id="<?php echo $this->field_id('log'); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- General Field Value Submission Ends -->

            <!-- Specific Field Value Submission Starts -->
            <?php
            $field_value_complete_data = array(
                array(
                    'creds' => 0,
                    'log' => __('%plural% for submitting field value', 'mycred-toolkit'),
                    'select_form' => 0,
                    'select_field' => '',
                    'field_value' => ''
                ),
            );

            if (count($prefs['specific_field_value_submit']['creds']) > 0) {
                $field_value_complete_data = $this->mycred_arforms_field_value_arrange_data($prefs['specific_field_value_submit']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-arforms-field-value">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Specific', 'mycred-toolkit'); ?></h3>
                            <p class="description">
                                <?php esc_html_e('Award points when a user submits a specific field value on a specific form or any form.', 'mycred-toolkit'); ?>
                            </p>
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
                foreach ($field_value_complete_data as $hook => $label) {
                    ?>
                    <div class="arforms_field_value_custom_hook_class">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_field_value_submit' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_field_value_submit' => 'creds')); ?>"
                                        id="<?php echo $this->field_id(array('specific_field_value_submit' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>"
                                        class="form-control mycred-arforms-field-creds" />
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('ARForm', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-arforms-field-form-select"
                                        name="<?php echo $this->specific_field_name(array('specific_field_value_submit' => 'select_form')); ?>">

                                        <option value="0"><?php esc_html_e('Any Form', 'mycred-toolkit'); ?></option>
                                        <?php
                                        if (!empty($arforms) && is_array($arforms)) {
                                            foreach ($arforms as $form) {
                                                $form_id = $form->id;
                                                $selected = isset($label['select_form']) && $label['select_form'] == $form_id ? 'selected' : '';

                                                echo '<option value="' . esc_attr($form_id) . '" ' . esc_attr($selected) . '>' .
                                                    esc_html($form->name) .
                                                    '</option>';
                                            }
                                        } else {
                                            echo '<option value="">' . esc_html__('No ARForms found', 'mycred-toolkit') . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Field', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-arforms-field-select"
                                        name="<?php echo $this->specific_field_name(array('specific_field_value_submit' => 'select_field')); ?>"
                                        data-selected-field="<?php echo esc_attr($label['select_field']); ?>">
                                        <option value=""><?php esc_html_e('Select Field', 'mycred-toolkit'); ?></option>
                                        <?php
                                        // Load fields if form is selected
                                        if (!empty($label['select_form']) && $label['select_form'] != '0') {
                                            $fields = $this->get_form_fields($label['select_form']);
                                            if (!empty($fields)) {
                                                foreach ($fields as $field) {
                                                    $selected = isset($label['select_field']) && $label['select_field'] == $field->id ? 'selected' : '';
                                                    echo '<option value="' . esc_attr($field->id) . '" ' . esc_attr($selected) . '>' .
                                                        esc_html($field->name) . ' (' . esc_html($field->type) . ')' .
                                                        '</option>';
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Field Value', 'mycred-toolkit'); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_field_value_submit' => 'field_value')); ?>"
                                        value="<?php echo esc_attr($label['field_value']); ?>"
                                        class="form-control mycred-arforms-field-value"
                                        placeholder="<?php esc_attr_e('Enter expected value', 'mycred-toolkit'); ?>" />
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_field_value_submit' => 'log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_field_value_submit' => 'log')); ?>"
                                        id="<?php echo $this->field_id(array('specific_field_value_submit' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control mycred-arforms-field-log" />
                                    <span
                                        class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group arforms-specific-hook-actions textright">
                                    <button class="button button-small mycred-add-specific-arforms-field-hook add_button"
                                        type="button">Add More</button>
                                    <button class="button button-small mycred-remove-specific-arforms-field-hook"
                                        type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <!-- Specific Field Value Submission Ends -->

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
            $data['field_name'] = (!empty($data['field_name'])) ? sanitize_text_field($data['field_name']) : '';
            $data['field_value'] = (!empty($data['field_value'])) ? sanitize_text_field($data['field_value']) : '';

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            foreach ($data['specific_field_value_submit'] as $data_key => $data_value) {
                foreach ($data_value as $key => $value) {
                    if ($data_key == 'creds') {
                        $data['specific_field_value_submit'][$data_key][$key] = (!empty($value)) ? floatval($value) : 0;
                    } else if ($data_key == 'log') {
                        $data['specific_field_value_submit'][$data_key][$key] = (!empty($value)) ? sanitize_text_field($value) : '%plural% for submitting field value';
                    } else if ($data_key == 'select_form') {
                        $data['specific_field_value_submit'][$data_key][$key] = (!empty($value)) ? sanitize_text_field($value) : '0';
                    } else if ($data_key == 'select_field') {
                        $data['specific_field_value_submit'][$data_key][$key] = (!empty($value)) ? sanitize_text_field($value) : '';
                    } else if ($data_key == 'field_value') {
                        $data['specific_field_value_submit'][$data_key][$key] = (!empty($value)) ? sanitize_text_field($value) : '';
                    }
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
