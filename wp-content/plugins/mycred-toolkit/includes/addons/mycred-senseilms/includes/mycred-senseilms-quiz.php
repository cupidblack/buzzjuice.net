<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'myCRED_SenseiLMS_Quiz' ) ) :
    class myCRED_SenseiLMS_Quiz extends myCRED_Hook {
       
        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {
            $defaults = array(
                'quiz_completed' => array(
                    'creds'  => 1,
                    'log'    => '%plural% for completing a quiz',
                    'limit'  => '0/x'
                ),
                'quiz_passed' => array(
                    'creds'  => 1,
                    'log'    => '%plural% for passing a quiz',
                    'limit'  => '0/x'
                ),
                'quiz_failed' => array(
                    'creds'  => 0,
                    'log'    => '%plural% for failing a quiz',
                    'limit'  => '0/x'
                )
            );
            parent::__construct( array(
                'id'       => 'mycred_senseilms_quiz',
                'defaults' => $defaults
            ), $hook_prefs, $type );
        }

        /**
         * Hook into WordPress
         */
        public function run() {
            // Quiz submission (completion)
            add_action('sensei_user_quiz_submitted', array($this, 'quiz_completed'), 10, 2);
            
            // Quiz grade processing
            add_action('sensei_user_quiz_grade', array($this, 'process_quiz_grade'), 10, 5);
        }

        /**
         * Award points based on quiz status
         */
        protected function award_points($status, $user_id, $quiz_id, $data = array()) {
            // Check for exclusion
            if ($this->core->exclude_user($user_id)) return;

            $reference = 'quiz_' . $status;
           
            // Skip if no points are set for this status
            if ($this->prefs[$reference]['creds'] == 0) return;

            // Make sure this is unique
            if (!$this->over_hook_limit($reference, $reference, $user_id) &&
                !$this->core->has_entry($reference, $user_id, $quiz_id, $data, $this->mycred_type)) {
               
                $this->core->add_creds(
                    $reference,
                    $user_id,
                    $this->prefs[$reference]['creds'],
                    $this->prefs[$reference]['log'],
                    $quiz_id,
                    $data,
                    $this->mycred_type
                );
            }
        }

        /**
         * Quiz Completed Handler
         */
        public function quiz_completed($user_id, $quiz_id) {
            $data = array(
                'quiz_id' => $quiz_id,
                'status' => 'completed'
            );
            $this->award_points('completed', $user_id, $quiz_id, $data);
        }

        /**
         * Process Quiz Grade
         * Handles both pass and fail scenarios
         */
        public function process_quiz_grade( $user_id, $quiz_id, $grade, $quiz_passmark, $quiz_grade_type ) {
            $data = array(
                'quiz_id' => $quiz_id,
                'grade' => $grade,
                'pass_percentage' => $quiz_passmark
            );

            // Determine if passed or failed
            if ($grade >= $quiz_passmark) {
                $data['status'] = 'passed';
                $this->award_points('passed', $user_id, $quiz_id, $data);
            } else {
                $data['status'] = 'failed';
                $this->award_points('failed', $user_id, $quiz_id, $data);
            }
        }

        /**
         * Preferences
         */
        public function preferences() {
            $prefs = $this->prefs;
            $hook_labels = array(
                'completed' => __('Quiz Completion', 'mycred'),
                'passed' => __('Quiz Passed', 'mycred'),
                'failed' => __('Quiz Failed', 'mycred')
            );

            foreach ($hook_labels as $status => $label) :
                $reference = 'quiz_' . $status; ?>
               
                <div class="hook-instance">
                    <h3><?php echo esc_html($label); ?></h3>
                    <div class="row">
                        <div class="col-lg-2 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id(array($reference => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name(array($reference => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array($reference => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number($prefs[$reference]['creds'])); ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id(array($reference => 'limit'))); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                                <?php echo wp_kses($this->hook_limit_setting($this->field_name(array($reference => 'limit')), $this->field_id(array($reference => 'limit')), $prefs[$reference]['limit']), array(
                                    'div' => array('class' => array()),
                                    'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()),
                                    'select' => array('name' => array(), 'id' => array(), 'class' => array()),
                                    'option' => array('value' => array(), 'selected' => array())
                                )); ?>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id(array($reference => 'log'))); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name(array($reference => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array($reference => 'log'))); ?>" placeholder="<?php esc_attr_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs[$reference]['log']); ?>" class="form-control" />
                                <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general'))); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            endforeach;
        }

        /**
         * Sanitize Preferences
         */
        public function sanitise_preferences($data) {
            $statuses = array('completed', 'passed', 'failed');
           
            foreach ($statuses as $status) {
                $reference = 'quiz_' . $status;
                if (isset($data[$reference]['limit']) && isset($data[$reference]['limit_by'])) {
                    $limit = sanitize_text_field($data[$reference]['limit']);
                    if ($limit == '') $limit = 0;
                    $data[$reference]['limit'] = $limit . '/' . $data[$reference]['limit_by'];
                    unset($data[$reference]['limit_by']);
                }
            }
            return $data;
        }
    }
endif;