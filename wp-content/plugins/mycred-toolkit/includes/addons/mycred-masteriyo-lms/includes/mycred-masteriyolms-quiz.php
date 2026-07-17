<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'myCRED_MasteriyoLMS_Quiz' ) ) :
    class myCRED_MasteriyoLMS_Quiz extends myCRED_Hook {
        
        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

            $defaults = array(
                'quiz_completed'    => array(
                    'creds'  => 1,
                    'log'    => '%plural% for completing a quiz',
                    'limit'  => '0/x'
                ),
                'quiz_passed'    => array(
                    'creds'  => 1,
                    'log'    => '%plural% for passing a quiz',
                    'limit'  => '0/x'
                ),
                'quiz_failed'    => array(
                    'creds'  => 0,
                    'log'    => '%plural% for failing a quiz',
                    'limit'  => '0/x'
                )
            );

            parent::__construct( array(
                'id'       => 'mycred_masteriyolms_quiz',
                'defaults' => $defaults
            ), $hook_prefs, $type );
        }

        /**
         * Hook into WordPress
         */
        public function run() {
            add_action( 'masteriyo_quiz_attempt_status_changed', array( $this, 'handle_quiz_status_change' ), 10, 3 );
            add_filter( 'masteriyo_rest_pre_insert_course_progress_item_object', array( $this, 'handle_quiz_completion' ) );
        }

        public function handle_quiz_completion( $course_progress_item ) {

            if ( ! $course_progress_item->get_completed() ) {
                return $course_progress_item;
            }
    
            if ( $course_progress_item->get_item_type() == 'quiz' && $course_progress_item->get_completed() ) {
    
                $user_id = $course_progress_item->get_user_id();
                $quiz_id = $course_progress_item->get_item_id();
                $course_id = $course_progress_item->get_course_id();
                $data = array(
                    'user_id' => $user_id,
                    'quiz_id' => $quiz_id,
                    'course_id' => $course_id,
                );
            
                $reference = 'masteriyo_quiz_completed';
            
                if ( !$this->over_hook_limit( '', $reference, $user_id ) && !$this->core->has_entry( $reference, $user_id, $quiz_id, $data, $this->mycred_type ) ) {
                    $this->core->add_creds(
                        $reference,
                        $user_id,
                        $this->prefs['quiz_completed']['creds'],
                        $this->prefs['quiz_completed']['log'],
                        $quiz_id,
                        $data,
                        $this->mycred_type
                    );
                }
    
            }
    
            return $course_progress_item;
        } 

        public function handle_quiz_status_change( $attempt, $old_status, $new_status ) {
            $quiz_id = $attempt->get_quiz_id();
            $quiz    = masteriyo_get_quiz( $quiz_id );
    
            if ( is_null( $quiz ) ) {
                return;
            }
    
            $attempt_id   = $attempt->get_id();
            $user_id      = $attempt->get_user_id();
            $course_id    = $attempt->get_course_id();
            $failed       = $attempt->get_earned_marks() < $quiz->get_pass_mark();


            if ( $failed ) {
                $this->award_points( 'failed', $user_id, $quiz_id, $attempt, $course_id );
            } else {
                $this->award_points( 'passed', $user_id, $quiz_id, $attempt, $course_id );
            }
        }

        /**
         * Award points based on quiz status
         */
        protected function award_points($status, $user_id, $quiz_id, $attempt, $course_id = 0) {

            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $reference = 'quiz_' . $status;
            
            if ($this->prefs[$reference]['creds'] == 0) {
                return;
            }

            $progress = !empty($attempt) ? $attempt->get_earned_marks() : 0;

            $data = array(
                'quiz_id' => $quiz_id,
                'course_id' => $course_id,
                'progress' => $progress,
                'status' => $status,
                'attempt_id' => !empty($attempt) ? $attempt->get_id() : 0
            );

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
                                <?php echo $this->hook_limit_setting($this->field_name(array($reference => 'limit')), $this->field_id(array($reference => 'limit')), $prefs[$reference]['limit']); ?>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id(array($reference => 'log'))); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name(array($reference => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array($reference => 'log'))); ?>" placeholder="<?php esc_attr_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs[$reference]['log']); ?>" class="form-control" />
                                <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
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