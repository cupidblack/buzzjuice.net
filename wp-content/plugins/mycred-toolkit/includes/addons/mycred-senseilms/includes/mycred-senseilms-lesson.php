<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sensei LMS Lesson Hook for myCRED
 * 
 * @since 1.0
 */
class myCRED_SenseiLMS_Lesson extends myCRED_Hook {

    /**
     * Constructor
     */
    function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY) {
        parent::__construct(array(
            'id'       => 'mycred_senseilms_lesson',
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
        if ($this->prefs['creds'] !== 0) {
            add_action('sensei_user_lesson_end', array($this, 'lesson_completed'), 10, 2);
        }
    }

    /**
     * Award points when a student completes a lesson
     * 
     * @param int $user_id    The ID of the student
     * @param int $quiz_lesson_id  The ID of the completed lesson
     */
    public function lesson_completed($user_id, $quiz_lesson_id) {
        // Check if user is excluded
        if ($this->core->exclude_user($user_id)) {
            return;
        }

        // Set up the reference and data
        $reference = 'sensei_lesson_completed';
        $data = array('lesson_id' => $quiz_lesson_id);

        // Check if user is within limits and hasn't already received points for this lesson
        if (!$this->over_hook_limit('', $reference, $user_id) && 
            !$this->core->has_entry($reference, $user_id, $quiz_lesson_id, $data, $this->mycred_type)) {
            
            // Award points
            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $quiz_lesson_id,
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
            <h3><?php esc_html_e('Student completes a lesson', 'mycred'); ?></h3>
            <div class="row">
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('creds')); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>" id="<?php echo esc_attr($this->field_id('creds')); ?>" value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
                    </div>
                </div>
                <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('limit')); ?>"><?php esc_html_e('Limit', 'mycred'); ?></label>
                        <?php echo wp_kses($this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), $prefs['limit']), array(
                            'div' => array('class' => array()),
                            'input' => array('type' => array(), 'size' => array(), 'class' => array(), 'name' => array(), 'id' => array(), 'value' => array()),
                            'select' => array('name' => array(), 'id' => array(), 'class' => array()),
                            'option' => array('value' => array(), 'selected' => array())
                        )); ?>
                    </div>
                </div>
                <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
                    <div class="form-group">
                        <label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                        <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>" id="<?php echo esc_attr($this->field_id('log')); ?>" placeholder="<?php esc_html_e('required', 'mycred'); ?>" value="<?php echo esc_attr($prefs['log']); ?>" class="form-control" />
                        <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general'))); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}