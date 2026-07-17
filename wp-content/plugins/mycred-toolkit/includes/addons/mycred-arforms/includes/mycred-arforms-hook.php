<?php
if (!defined('ABSPATH')) exit;

/**
 * Hook for ARForms submission
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_ARForms_Hook')) :
    class myCRED_ARForms_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'arforms_successful_submit',
                'defaults' => array(
                    'creds' => 0,
                    'log' => __('%plural% for ARForm Submission', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_arforms_submit' => array(
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
            add_action( 'arfliteentryexecute', array( $this, 'mycred_arforms_submission_rewards' ), 10, 4 );
            add_action( 'arfentryexecute', array( $this, 'mycred_arforms_submission_rewards' ), 10, 4 );
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
         * Award points for ARForms submission
         */
        public function mycred_arforms_submission_rewards($params, $errors, $form, $item_meta_values)
        {
            
            // Abort on validation errors
            if ( ! empty( $errors ) ) {
                return;
            }

            $prefs = $this->prefs;

            // ARForms: form ID comes from $form->id
            $form_id = isset( $form->id ) ? intval( $form->id ) : 0;

            if ( empty( $form_id ) ) {
                return;
            }

            // ENTRY ID (created by ARForms)
            $entry_id = isset( $params['entry_id'] ) ? intval( $params['entry_id'] ) : 0;

            // USER ID from params OR fallback
            $user_id = 0;

            if ( isset( $params['user_id'] ) ) {
                $user_id = intval( $params['user_id'] );
            }

            if ( empty( $user_id ) ) {
                $user_id = get_current_user_id();
            }

            if ( empty( $user_id ) ) {
                return;
            }

            // Exclude user check
            if ( $this->core->exclude_user( $user_id ) ) {
                return;
            }

            $reference = 'arforms_successful_submit';

            // Check if specific hook is enabled and form matches
            if (
                isset($prefs['check_specific_hook']) &&
                $prefs['check_specific_hook'] == '1' &&
                !empty($prefs['specific_arforms_submit']['select_option']) &&
                in_array($form_id, $prefs['specific_arforms_submit']['select_option'])
            ) {

                // Find the index of this form in the configuration
                $hook_index = array_search($form_id, $prefs['specific_arforms_submit']['select_option'], false);

                if (
                    !empty($prefs['specific_arforms_submit']['creds']) &&
                    isset($prefs['specific_arforms_submit']['creds'][$hook_index]) &&
                    !empty($prefs['specific_arforms_submit']['log']) &&
                    !empty($prefs['specific_arforms_submit']['log'][$hook_index]) &&
                    !empty($prefs['specific_arforms_submit']['select_option']) &&
                    isset($prefs['specific_arforms_submit']['select_option'][$hook_index])
                ) {

                    // Check limit for specific hook
                    if ($this->over_hook_limit('specific_arforms_submit', $reference, $user_id)) {
                        return;
                    }

                    if (!empty($prefs['specific_arforms_submit']['creds'][$hook_index])) {

                        $this->core->add_creds(
                            $reference,
                            $user_id,
                            $prefs['specific_arforms_submit']['creds'][$hook_index],
                            $prefs['specific_arforms_submit']['log'][$hook_index],
                            $entry_id,
                            array('ref_type' => 'post'),
                            $this->mycred_type
                        );
                    }
                }
            } else {

                // Check limit for general hook
                if ($this->over_hook_limit('', $reference, $user_id)) {
                    return;
                }

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
        }

        /**
         * Get entry data from ARForms
         */
        private function get_entry_data($entry_id)
        {
            global $wpdb;

            // ARForms stores entries in a custom table
            $table_name = $wpdb->prefix . 'arf_entries';

            $entry = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $entry_id
            ));

            return $entry;
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_arforms_submit_arrange_data($specific_hook_data)
        {
            $hook_data = array();
            foreach ($specific_hook_data['creds'] as $key => $value) {
                $hook_data[$key]['creds'] = $value;
                $hook_data[$key]['log'] = $specific_hook_data['log'][$key];
                $hook_data[$key]['select_option'] = $specific_hook_data['select_option'][$key];
            }
            return $hook_data;
        }

        /**
         * Preference for ARForms Submission Hook
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

            <!-- General ARForms Submission Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('General', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id('creds'); ?>"><?php echo $this->core->plural(); ?></label>
                            <input type="text" name="<?php echo $this->field_name('creds'); ?>" id="<?php echo $this->field_id('creds'); ?>" value="<?php echo $this->core->number($prefs['creds']); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id('log'); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('log'); ?>" id="<?php echo $this->field_id('log'); ?>" value="<?php echo esc_attr($prefs['log']); ?>" class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- General ARForms Submission Ends -->

            <!-- Specific ARForms Submission Starts -->
            <?php
            $arforms_submit_complete_data = array(
                array(
                    'creds' => 0,
                    'log' => __('%plural% for ARForm Submission', 'mycred-toolkit'),
                    'select_option' => 0
                ),
            );

            if (count($prefs['specific_arforms_submit']['creds']) > 0) {
                $arforms_submit_complete_data = $this->mycred_arforms_submit_arrange_data($prefs['specific_arforms_submit']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-arforms-submit">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Specific', 'mycred-toolkit'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php
                    $is_enabled = (isset($prefs['check_specific_hook']) && $prefs['check_specific_hook'] == 1);

                    mycred_create_toggle_field(
                        array(
                            'id'    => $this->field_id('check_specific_hook'),
                            'name'  => $this->field_name('check_specific_hook'),
                            'label' => __('Enable', 'mycred-toolkit'),
                            'after' => false,
                        ),
                        1,
                        $is_enabled
                    );
                    ?>

                </div>
                <?php
                foreach ($arforms_submit_complete_data as $hook => $label) {
                ?>
                    <div class="arforms_submit_custom_hook_class">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="<?php echo $this->field_id(array('specific_arforms_submit' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text" name="<?php echo $this->specific_field_name(array('specific_arforms_submit' => 'creds')); ?>" id="<?php echo $this->field_id(array('specific_arforms_submit' => 'creds')); ?>" value="<?php echo $this->core->number($label['creds']); ?>" class="form-control mycred-arforms-creds" />
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('ARForm', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-arforms-options"
                                        id="arforms_selected"
                                        name="<?php echo $this->specific_field_name(array('specific_arforms_submit' => 'select_option')); ?>">

                                        <option value="0">Select Form</option>
                                        <?php
                                        if (!empty($arforms) && is_array($arforms)) {
                                            foreach ($arforms as $form) {
                                                $form_id = $form->id;
                                                $selected = isset($label['select_option']) && $label['select_option'] == $form_id ? 'selected' : '';

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

                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="<?php echo $this->field_id(array('specific_arforms_submit' => 'log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                                    <input type="text" name="<?php echo $this->specific_field_name(array('specific_arforms_submit' => 'log')); ?>" id="<?php echo $this->field_id(array('specific_arforms_submit' => 'log')); ?>" value="<?php echo esc_attr($label['log']); ?>" class="form-control mycred-arforms-log" />
                                    <span class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group arforms-specific-hook-actions textright">
                                    <button class="button button-small mycred-add-specific-arforms-hook add_button" id="clone_btn" type="button">Add More</button>
                                    <button class="button button-small mycred-remove-specific-arforms-hook" type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
            <!-- Specific ARForms Submission Ends -->

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

            foreach ($data['specific_arforms_submit'] as $data_key => $data_value) {
                foreach ($data_value as $key => $value) {
                    if ($data_key == 'creds') {
                        $data['specific_arforms_submit'][$data_key][$key] = (!empty($value)) ? floatval($value) : 0;
                    } else if ($data_key == 'log') {
                        $data['specific_arforms_submit'][$data_key][$key] = (!empty($value)) ? sanitize_text_field($value) : '%plural% for ARForm Submission';
                    } else if ($data_key == 'select_option') {
                        $data['specific_arforms_submit'][$data_key][$key] = (!empty($value)) ? sanitize_text_field($value) : '0';
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
