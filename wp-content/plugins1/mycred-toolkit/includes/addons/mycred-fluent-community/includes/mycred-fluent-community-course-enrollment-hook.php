<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Fluent Community Course Enrollment
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Fluent_Community_Course_Enrollment_Hook')):
    class myCRED_Fluent_Community_Course_Enrollment_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'fluent_community_course_enrollment',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for enrolling in a course in Fluent Community', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_courses' => array(
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
            // Hook into Fluent Community course enrollment
            add_action('fluent_community/course/enrolled', array($this, 'mycred_fluentcommunity_enroll_course'), 10, 2);
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
         * Award points for Course Enrollment
         */
        public function mycred_fluentcommunity_enroll_course($course, $userId)
        {
            $user_id = absint($userId);

            // Bail if no user
            if ($user_id === 0) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            // Get Course ID
            $course_id = 0;
            if (method_exists($course, 'getOriginal')) {
                $course_data = $course->getOriginal();
                $course_id = isset($course_data['id']) ? absint($course_data['id']) : 0;
            } elseif (isset($course->id)) {
                $course_id = absint($course->id);
            }

            if ($course_id === 0) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'fluent_community_course_enrollment';

            // Check if specific hook is enabled and course matches
            if (
                isset($prefs['check_specific_hook']) &&
                $prefs['check_specific_hook'] == '1' &&
                !empty($prefs['specific_courses']['select_option']) &&
                in_array($course_id, $prefs['specific_courses']['select_option'])
            ) {

                // Find the index of this course in the configuration
                $hook_index = array_search($course_id, $prefs['specific_courses']['select_option']);

                if (
                    $hook_index !== false &&
                    !empty($prefs['specific_courses']['creds']) &&
                    isset($prefs['specific_courses']['creds'][$hook_index])
                ) {

                    if ($this->over_hook_limit('specific_courses', $reference, $user_id)) {
                        return;
                    }

                    $creds = $prefs['specific_courses']['creds'][$hook_index];
                    $log = isset($prefs['specific_courses']['log'][$hook_index]) ? $prefs['specific_courses']['log'][$hook_index] : $prefs['log'];

                    $this->core->add_creds(
                        $reference,
                        $user_id,
                        $creds,
                        $log,
                        $course_id,
                        array('ref_type' => 'course'),
                        $this->mycred_type
                    );

                    return; // Exit after processing specific rule
                }
            }

            // General Award/Deduction
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $course_id,
                array('ref_type' => 'course'),
                $this->mycred_type
            );
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_fluent_community_arrange_data($specific_hook_data)
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
         * Preference for Fluent Community Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Get available courses
            $courses = array();
            if (class_exists('\FluentCommunity\App\Functions\Utility')) {
                $courses = \FluentCommunity\App\Functions\Utility::getCourses();
            }
            ?>

                        <!-- General Course Enrollment Rewards Starts -->
                        <div class="hook-instance">
                            <h3><?php esc_html_e('Enroll in Any Course', 'mycred-toolkit'); ?></h3>
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
                        <!-- General Course Enrollment Rewards Ends -->

                        <!-- Specific Courses Starts -->
                        <?php
                        $specific_data = array(
                            array(
                                'creds' => 0,
                                'log' => __('%plural% for enrolling in specific course', 'mycred-toolkit'),
                                'select_option' => 0
                            ),
                        );

                        if (!empty($prefs['specific_courses']['creds']) && count($prefs['specific_courses']['creds']) > 0) {
                            $specific_data = $this->mycred_fluent_community_arrange_data($prefs['specific_courses']);
                        }
                        ?>
                        <div class="hook-instance" id="specific-hook-fluent-community-course-enrollment">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="hook-title">
                                        <h3><?php esc_html_e('Enroll in Specific Course', 'mycred-toolkit'); ?></h3>
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
                                    <div class="fluent_community_specific_row_course_enrollment">
                                        <div class="row">
                                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                                <div class="form-group">
                                                    <label for="<?php echo $this->field_id(array('specific_courses' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                                    <input type="text"
                                                        name="<?php echo $this->specific_field_name(array('specific_courses' => 'creds')); ?>"
                                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                <div class="form-group">
                                                    <label><?php esc_html_e('Select Course', 'mycred-toolkit'); ?></label>
                                                    <select
                                                        name="<?php echo $this->specific_field_name(array('specific_courses' => 'select_option')); ?>"
                                                        class="form-control mycred-fluent-community-options">
                                                        <option value="0"><?php esc_html_e('Select Course', 'mycred-toolkit'); ?></option>
                                                        <?php
                                                        if (!empty($courses)) {
                                                            foreach ($courses as $course) {
                                                                $selected = (isset($label['select_option']) && $label['select_option'] == $course['id']) ? 'selected' : '';
                                                                echo '<option value="' . esc_attr($course['id']) . '" ' . $selected . '>' . esc_html($course['title']) . '</option>';
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
                                                        name="<?php echo $this->specific_field_name(array('specific_courses' => 'log')); ?>"
                                                        value="<?php echo esc_attr($label['log']); ?>"
                                                        id="mycred-course-enrollment-log"
                                                        class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                                <div class="form-group textright">
                                                    <button class="button button-small mycred-add-specific-fluent-community-course-enrollment-hook add_button" type="button">Add More</button>
                                                    <button class="button button-small mycred-remove-specific-fluent-community-course-enrollment-hook" type="button">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php } ?>

                            <!-- Hidden Template for JS 'Add More' functionality -->
                            <script type="text/html" id="tmpl-mycred-fluent-community-specific-row-course-enrollment">
                                <div class="fluent_community_specific_row_course_enrollment">
                                    <div class="row">
                                        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                            <div class="form-group">
                                                <label><?php echo $this->core->plural(); ?></label>
                                                <input type="text"
                                                    name="<?php echo $this->specific_field_name(array('specific_courses' => 'creds')); ?>"
                                                    value="" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                            <div class="form-group">
                                                <label><?php esc_html_e('Select Course', 'mycred-toolkit'); ?></label>
                                                <select
                                                    name="<?php echo $this->specific_field_name(array('specific_courses' => 'select_option')); ?>"
                                                    class="form-control mycred-fluent-community-options">
                                                    <option value="0"><?php esc_html_e('Select Course', 'mycred-toolkit'); ?></option>
                                                    <?php
                                                    if (!empty($courses)) {
                                                        foreach ($courses as $course) {
                                                            echo '<option value="' . esc_attr($course['id']) . '">' . esc_html($course['title']) . '</option>';
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
                                                    name="<?php echo $this->specific_field_name(array('specific_courses' => 'log')); ?>"
                                                    value="" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                            <div class="form-group textright">
                                                <button class="button button-small mycred-remove-specific-fluent-community-course-enrollment-hook" type="button">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </script>
                        </div>
                        <!-- Specific Courses Ends -->

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

            if (isset($data['specific_courses'])) {
                foreach ($data['specific_courses']['creds'] as $key => $value) {
                    $new_val = floatval($value);
                    $data['specific_courses']['creds'][$key] = $new_val;

                    $log_val = isset($data['specific_courses']['log'][$key]) ? $data['specific_courses']['log'][$key] : '';
                    $data['specific_courses']['log'][$key] = sanitize_text_field($log_val);

                    $opt_val = isset($data['specific_courses']['select_option'][$key]) ? $data['specific_courses']['select_option'][$key] : 0;
                    $data['specific_courses']['select_option'][$key] = intval($opt_val);
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
