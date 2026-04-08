<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AcademyLMS Lesson Hook for myCRED
 * 
 * @since 1.0
 */
class myCRED_AcademyLMS_Lesson extends myCRED_Hook {

    /**
     * Constructor
     */
    function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY) {
        parent::__construct(array(
            'id'       => 'mycred_academylms_lesson',
            'defaults' => array(
                'creds'  => 1,
                'log'    => '%plural% for completing a lesson',
                'limit'  => '0/x'
            )
        ), $hook_prefs, $type);
    }

    /**
     * Hook into WordPress
     */
    public function run() {
        if ($this->prefs['creds'] != 0) {
            add_action('academy/frontend/after_mark_topic_complete', array($this, 'lesson_completed'), 10, 4);
        }
    }

    /**
     * Award points when a student completes a lesson
     */
    public function lesson_completed( $topic_type, $course_id, $topic_id, $user_id ) {

        if ($this->core->exclude_user($user_id)) {
            return;
        }

        $reference = 'academy_lesson_completed';
        $data = array('lesson_id' => $lesson_id, 'course_id' => $course_id, 'topic_id' => $topic_id);

        if (!$this->over_hook_limit('', $reference, $user_id) &&
            !$this->core->has_entry($reference, $user_id, $lesson_id, $data, $this->mycred_type)) {

            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $lesson_id,
                $data,
                $this->mycred_type
            );
        }
    }

    /**
     * Preference for Point Awards
     */
    public function preferences() {
        $prefs = $this->prefs; ?>

        <div class="hook-instance">
            <div class="row">
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('creds')); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>" id="<?php echo esc_attr($this->field_id('creds')); ?>" value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
                    </div>
                </div>
                <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label for="<?php echo $this->field_id('limit'); ?>"><?php _e('Limit', 'mycred'); ?></label>
                        <?php echo $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), $prefs['limit']); ?>
                    </div>
                </div>
                <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>" id="<?php echo esc_attr($this->field_id('log')); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit'); ?>" value="<?php echo esc_attr($prefs['log']); ?>" class="form-control" />
                        <span class="description"><?php echo wp_kses_post($this->available_template_tags(array( 'general' ))); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}