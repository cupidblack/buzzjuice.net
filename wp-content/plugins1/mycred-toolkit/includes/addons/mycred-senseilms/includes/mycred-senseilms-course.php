<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (! class_exists('myCRED_SenseiLMS_Course')) :
    class myCRED_SenseiLMS_Course extends myCRED_Hook {
       
        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {
            $defaults = array(
                'sensei_course_completed' => array(
                    'creds'  => 1,
                    'log'    => '%plural% for completing a course',
                    'limit'  => '0/x'
                ),
                'sensei_course_enrolled' => array(
                    'creds'  => 0,
                    'log'    => '%plural% for enrolling in a course',
                    'limit'  => '0/x'
                )
            );
            parent::__construct(array(
                'id'       => 'mycred_senseilms_course',
                'defaults' => $defaults
            ), $hook_prefs, $type);
        }

        /**
         * Hook into WordPress
         */
        public function run() {
            // Hook into Sensei course enrollment
            add_action('sensei_user_course_start', array($this, 'user_enrolled_in_course'), 10, 2);
            
            // Hook into Sensei course completion
            add_action('sensei_user_course_end', array($this, 'course_completed'), 10, 2);
        }

        /**
         * Award points for course completion
         */
        public function course_completed($user_id, $course_id) {
            // Check for exclusion
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $reference = 'sensei_course_completed';
            $data = array('course' => $course_id);

            // Enforce limit and make sure users only get points once per unique course
            if (!$this->over_hook_limit($reference, $reference, $user_id) && 
                !$this->core->has_entry($reference, $user_id, $course_id, $data, $this->mycred_type)) {
                
                $this->core->add_creds(
                    $reference,
                    $user_id,
                    $this->prefs[$reference]['creds'],
                    $this->prefs[$reference]['log'],
                    $course_id,
                    $data,
                    $this->mycred_type
                );
            }
        }

        /**
         * Award points for course enrollment
         */
        public function user_enrolled_in_course($user_id, $course_id) {
            // Check for exclusion
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $reference = 'sensei_course_enrolled';
            $data = array('course' => $course_id);

            // Enforce limit and make sure users only get points once per unique course
            if (!$this->over_hook_limit($reference, $reference, $user_id) && 
                !$this->core->has_entry($reference, $user_id, $course_id, $data, $this->mycred_type)) {
                
                $this->core->add_creds(
                    $reference,
                    $user_id,
                    $this->prefs[$reference]['creds'],
                    $this->prefs[$reference]['log'],
                    $course_id,
                    $data,
                    $this->mycred_type
                );
            }
        }

        /**
         * Add Settings
         */
        public function preferences() {
            $prefs = $this->prefs; ?>
           
            <div class="hook-instance">
                <h3><?php esc_html_e('Student enrolls in a course', 'mycred'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('sensei_course_enrolled' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('sensei_course_enrolled' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('sensei_course_enrolled' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number($prefs['sensei_course_enrolled']['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('sensei_course_enrolled' => 'limit'))); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                            <?php echo wp_kses($this->hook_limit_setting($this->field_name(array('sensei_course_enrolled' => 'limit')), $this->field_id(array('sensei_course_enrolled' => 'limit')), $prefs['sensei_course_enrolled']['limit']), array('div' => array('class' => array()), 'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()), 'select' => array('name' => array(), 'id' => array(), 'class' => array()), 'option' => array('value' => array(), 'selected' => array()))); ?>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('sensei_course_enrolled' => 'log'))); ?>"><?php esc_html_e('Log template', 'mycred'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('sensei_course_enrolled' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('sensei_course_enrolled' => 'log'))); ?>" placeholder="<?php esc_html_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs['sensei_course_enrolled']['log']); ?>" class="form-control" />
                            <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general'))); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hook-instance">
                <h3><?php esc_html_e('Student completes a course', 'mycred'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('sensei_course_completed' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('sensei_course_completed' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('sensei_course_completed' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number($prefs['sensei_course_completed']['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('sensei_course_completed' => 'limit'))); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                            <?php echo wp_kses($this->hook_limit_setting($this->field_name(array('sensei_course_completed' => 'limit')), $this->field_id(array('sensei_course_completed' => 'limit')), $prefs['sensei_course_completed']['limit']), array('div' => array('class' => array()), 'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()), 'select' => array('name' => array(), 'id' => array(), 'class' => array()), 'option' => array('value' => array(), 'selected' => array()))); ?>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('sensei_course_completed' => 'log'))); ?>"><?php esc_html_e('Log template', 'mycred'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('sensei_course_completed' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('sensei_course_completed' => 'log'))); ?>" placeholder="<?php esc_html_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs['sensei_course_completed']['log']); ?>" class="form-control" />
                            <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general'))); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Sanitize Preferences
         */
        public function sanitise_preferences($data) {
            if (isset($data['sensei_course_completed']['limit']) && isset($data['sensei_course_completed']['limit_by'])) {
                $limit = sanitize_text_field($data['sensei_course_completed']['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['sensei_course_completed']['limit'] = $limit . '/' . $data['sensei_course_completed']['limit_by'];
                unset($data['sensei_course_completed']['limit_by']);
            }
            if (isset($data['sensei_course_enrolled']['limit']) && isset($data['sensei_course_enrolled']['limit_by'])) {
                $limit = sanitize_text_field($data['sensei_course_enrolled']['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['sensei_course_enrolled']['limit'] = $limit . '/' . $data['sensei_course_enrolled']['limit_by'];
                unset($data['sensei_course_enrolled']['limit_by']);
            }
            return $data;
        }
    }
endif;