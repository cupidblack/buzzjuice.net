<?php
if (!defined('ABSPATH')) exit;

/**
 * Masteriyo LMS Lesson Completion Hook for myCRED
 */
class myCRED_MasteriyoLMS_Lesson extends myCRED_Hook {

    /**
     * Construct
     */
    function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY) {
        parent::__construct(array(
            'id'       => 'mycred_masteriyolms_lesson',
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
        add_filter( 'masteriyo_rest_pre_insert_course_progress_item_object', array( $this, 'handle_lesson_completion' ) );
    }
    
    public function handle_lesson_completion( $course_progress_item ) {

        if ( ! $course_progress_item->get_completed() ) {
            return $course_progress_item;
        }
        
        $user_id = $course_progress_item->get_user_id();
        $lesson_id = $course_progress_item->get_item_id();
        $course_id = $course_progress_item->get_course_id();
        $data = array(
            'user_id' => $user_id,
            'lesson_id' => $lesson_id,
            'course_id' => $course_id,
        );
    
        $reference = 'masteriyo_lesson_completed';
    
        if ( !$this->over_hook_limit( '', $reference, $user_id ) && !$this->core->has_entry( $reference, $user_id, $lesson_id, $data, $this->mycred_type ) ) {
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

        return $course_progress_item;
    }    

    /**
     * Preference for Point Awards
     */
    public function preferences() {
        $prefs = $this->prefs; ?>
        <div class="hook-instance">
            <h3><?php esc_html_e('Student completes a Lesson', 'mycred'); ?></h3>
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
