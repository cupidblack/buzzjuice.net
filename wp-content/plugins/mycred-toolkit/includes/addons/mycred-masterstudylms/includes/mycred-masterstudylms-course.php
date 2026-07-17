<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (! class_exists('myCRED_MasterStudyLMS_Course')) :
    class myCRED_MasterStudyLMS_Course extends myCRED_Hook {
       
        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {
            $defaults = array(
                'masterstudy_course_completed' => array(
                    'creds'  => 1,
                    'log'    => '%plural% for completing a course',
                    'limit'  => '0/x'
                ),
                'masterstudy_course_enrolled' => array(
                    'creds'  => 0,
                    'log'    => '%plural% for enrolling in a course',
                    'limit'  => '0/x'
                )
            );
            parent::__construct(array(
                'id'       => 'mycred_masterstudylms_course',
                'defaults' => $defaults
            ), $hook_prefs, $type);
        }

        /**
         * Hook into WordPress
         */
        public function run() {
            // Using progress_updated hook for both enrollment and completion
            add_action('stm_lms_progress_updated', array($this, 'handle_course_progress'), 10, 3);
            
            // Additional hook for enrollment through user meta
            add_action('add_user_course', array($this, 'check_course_enrollment'), 10, 2);
        }

        /**
         * Handle course progress updates - used for both enrollment and completion
         */
        public function handle_course_progress( $course_id, $user_id, $progress ) {
            // Check for exclusion
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            // Check for course completion (100% progress)
            if ($progress >= 100) {
                $this->course_completed($user_id, $course_id);
            }
            // Check for first progress (enrollment)
            elseif ($progress <= 1) {
                $this->user_enrolled_in_course($user_id, $course_id);
            }
        }

        /**
         * Check if the added user meta is a course enrollment
         */
        public function check_course_enrollment( $user_id, $course_id ) {
            // Check for exclusion
            if ($this->core->exclude_user($user_id)) {
                return;
            }
                
			// Verify this is a new enrollment using the database
			global $wpdb;
			$table = $wpdb->prefix . 'stm_lms_user_courses';
			$is_enrolled = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} 
				WHERE user_id = %d AND course_id = %d",
				$user_id,
				$course_id
			));

			if ($is_enrolled) {
				$this->user_enrolled_in_course($user_id, $course_id);
			}
        }

        /**
         * Award points for course completion
         */
        public function course_completed($user_id, $course_id) {
            $reference = 'masterstudy_course_completed';
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
            $reference = 'masterstudy_course_enrolled';
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
                            <label for="<?php echo esc_attr($this->field_id(array('masterstudy_course_enrolled' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('masterstudy_course_enrolled' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('masterstudy_course_enrolled' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number($prefs['masterstudy_course_enrolled']['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('masterstudy_course_enrolled' => 'limit'))); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                            <?php echo wp_kses($this->hook_limit_setting($this->field_name(array('masterstudy_course_enrolled' => 'limit')), $this->field_id(array('masterstudy_course_enrolled' => 'limit')), $prefs['masterstudy_course_enrolled']['limit']), array('div' => array('class' => array()), 'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()), 'select' => array('name' => array(), 'id' => array(), 'class' => array()), 'option' => array('value' => array(), 'selected' => array()))); ?>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('masterstudy_course_enrolled' => 'log'))); ?>"><?php esc_html_e('Log template', 'mycred'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('masterstudy_course_enrolled' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('masterstudy_course_enrolled' => 'log'))); ?>" placeholder="<?php esc_html_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs['masterstudy_course_enrolled']['log']); ?>" class="form-control" />
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
                            <label for="<?php echo esc_attr($this->field_id(array('masterstudy_course_completed' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('masterstudy_course_completed' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('masterstudy_course_completed' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number($prefs['masterstudy_course_completed']['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('masterstudy_course_completed' => 'limit'))); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                            <?php echo wp_kses($this->hook_limit_setting($this->field_name(array('masterstudy_course_completed' => 'limit')), $this->field_id(array('masterstudy_course_completed' => 'limit')), $prefs['masterstudy_course_completed']['limit']), array('div' => array('class' => array()), 'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()), 'select' => array('name' => array(), 'id' => array(), 'class' => array()), 'option' => array('value' => array(), 'selected' => array()))); ?>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('masterstudy_course_completed' => 'log'))); ?>"><?php esc_html_e('Log template', 'mycred'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('masterstudy_course_completed' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('masterstudy_course_completed' => 'log'))); ?>" placeholder="<?php esc_html_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs['masterstudy_course_completed']['log']); ?>" class="form-control" />
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
            if (isset($data['masterstudy_course_completed']['limit']) && isset($data['masterstudy_course_completed']['limit_by'])) {
                $limit = sanitize_text_field($data['masterstudy_course_completed']['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['masterstudy_course_completed']['limit'] = $limit . '/' . $data['masterstudy_course_completed']['limit_by'];
                unset($data['masterstudy_course_completed']['limit_by']);
            }
            if (isset($data['masterstudy_course_enrolled']['limit']) && isset($data['masterstudy_course_enrolled']['limit_by'])) {
                $limit = sanitize_text_field($data['masterstudy_course_enrolled']['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['masterstudy_course_enrolled']['limit'] = $limit . '/' . $data['masterstudy_course_enrolled']['limit_by'];
                unset($data['masterstudy_course_enrolled']['limit_by']);
            }
            return $data;
        }
    }
endif;