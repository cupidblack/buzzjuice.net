<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (! class_exists('myCRED_AcademyLMS_Course')) :
    class myCRED_AcademyLMS_Course extends myCRED_Hook {
       
        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {
            $defaults = array(
                'academy_course_completed' => array(
                    'creds'  => 1,
                    'log'    => '%plural% for completing a course',
                    'limit'  => '0/x'
                ),
                'academy_course_enrolled' => array(
                    'creds'  => 0,
                    'log'    => '%plural% for enrolling in a course',
                    'limit'  => '0/x'
                )
            );
            parent::__construct(array(
                'id'       => 'mycred_academylms_course',
                'defaults' => $defaults
            ), $hook_prefs, $type);
        }

        /**
         * Hook into WordPress
         */
        public function run() {

            add_action('academy/course/after_enroll', array($this, 'handle_course_enrollment'), 10, 3);
            add_action('academy/admin/course_complete_after', array($this, 'handle_course_completion'), 10, 2);
        }

        public function handle_course_enrollment( $course_id, $enroll_id, $user_id ) {

            if ( $this->core->exclude_user ( $user_id ) ) return;

            $reference = 'academy_course_enrolled';
            $data = array ( 'course' => $course_id );

            if (  !$this->over_hook_limit ( $reference, $reference, $user_id ) &&
                ! $this->core->has_entry ( $reference, $user_id, $course_id, $data, $this->mycred_type ) ) {

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

        public function handle_course_completion( $course_id, $user_id ) {

            if ( $this->core->exclude_user ( $user_id ) ) return;

            $reference = 'academy_course_completed';
            $data = array ( 'course' => $course_id );

            if ( ! $this->over_hook_limit ( $reference, $reference, $user_id ) &&
                ! $this->core->has_entry ( $reference, $user_id, $course_id, $data, $this->mycred_type ) ) {

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
                            <label for="<?php echo esc_attr($this->field_id(array('academy_course_enrolled' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('academy_course_enrolled' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('academy_course_enrolled' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number($prefs['academy_course_enrolled']['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('academy_course_enrolled' => 'limit'))); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                            <?php echo wp_kses($this->hook_limit_setting($this->field_name(array('academy_course_enrolled' => 'limit')), $this->field_id(array('academy_course_enrolled' => 'limit')), $prefs['academy_course_enrolled']['limit']), array('div' => array('class' => array()), 'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()), 'select' => array('name' => array(), 'id' => array(), 'class' => array()), 'option' => array('value' => array(), 'selected' => array()))); ?>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('academy_course_enrolled' => 'log'))); ?>"><?php esc_html_e('Log template', 'mycred'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('academy_course_enrolled' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('academy_course_enrolled' => 'log'))); ?>" placeholder="<?php esc_html_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs['academy_course_enrolled']['log']); ?>" class="form-control" />
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
                            <label for="<?php echo esc_attr($this->field_id(array('academy_course_completed' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('academy_course_completed' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('academy_course_completed' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number($prefs['academy_course_completed']['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('academy_course_completed' => 'limit'))); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                            <?php echo wp_kses($this->hook_limit_setting($this->field_name(array('academy_course_completed' => 'limit')), $this->field_id(array('academy_course_completed' => 'limit')), $prefs['academy_course_completed']['limit']), array('div' => array('class' => array()), 'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()), 'select' => array('name' => array(), 'id' => array(), 'class' => array()), 'option' => array('value' => array(), 'selected' => array()))); ?>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id(array('academy_course_completed' => 'log'))); ?>"><?php esc_html_e('Log template', 'mycred'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name(array('academy_course_completed' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('academy_course_completed' => 'log'))); ?>" placeholder="<?php esc_html_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs['academy_course_completed']['log']); ?>" class="form-control" />
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
            if (isset($data['academy_course_completed']['limit']) && isset($data['academy_course_completed']['limit_by'])) {
                $limit = sanitize_text_field($data['academy_course_completed']['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['academy_course_completed']['limit'] = $limit . '/' . $data['academy_course_completed']['limit_by'];
                unset($data['academy_course_completed']['limit_by']);
            }
            if (isset($data['academy_course_enrolled']['limit']) && isset($data['academy_course_enrolled']['limit_by'])) {
                $limit = sanitize_text_field($data['academy_course_enrolled']['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['academy_course_enrolled']['limit'] = $limit . '/' . $data['academy_course_enrolled']['limit_by'];
                unset($data['academy_course_enrolled']['limit_by']);
            }
            return $data;
        }
    }
endif;