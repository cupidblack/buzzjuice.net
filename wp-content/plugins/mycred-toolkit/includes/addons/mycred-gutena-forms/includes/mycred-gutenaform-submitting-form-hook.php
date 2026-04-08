<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook for Gutena Forms form submission
 */
if ( ! class_exists( 'myCRED_Gutena_Forms_Submit_Form_Hook' ) ) :
	class myCRED_Gutena_Forms_Submit_Form_Hook extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'gutenaform_successful_submit_form',
                'defaults' => array( 
                    'creds'         => 0,
                    'log'           => __('%plural% for Successfully Submitting a form', 'mycred-toolkit'),  
                )
            ), $hook_prefs, $type );
        }

        /**
         * Run
         */
        public function run() {
            add_action( 'gutena_forms_submission', array( $this, 'mycred_gutena_forms_submission' ), 10, 2 );
        }

        /**
         * Process Gutena Forms submission
         */
        public function mycred_gutena_forms_submission( $form_submit_data, $formSchema ) {

            if ( ! is_user_logged_in() ) return;

            $user_id = get_current_user_id();
            $form_id = 0;
            
            if (isset($form_submit_data['formID'])) {
                $form_id = sanitize_text_field($form_submit_data['formID']);
            } elseif (isset($formSchema['form_attrs']['formID'])) {
                $form_id = sanitize_text_field($formSchema['form_attrs']['formID']);
            }
            
            if (empty($form_id)) return;
            
            if ( $this->core->has_entry( 'gutenaform_successful_submit_form', $form_id, $user_id ) ) return;

            $this->core->add_creds(
                'gutenaform_successful_submit_form',
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $form_id,
                'gutenaform_successful_submit_form',
                $this->mycred_type
            );
        }

        /**
         * Preferences for myCRED
         */
        public function preferences() {

            $prefs = $this->prefs;
            
            ?>
            <div class="hook-instance">
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id( 'creds' )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name( 'creds' )); ?>" id="<?php echo esc_attr($this->field_id( 'creds' )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['creds'] )); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('log' )); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name( 'log' )); ?>" id="<?php echo esc_attr($this->field_id( 'log' )); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
                            <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Sanitize preferences
         */
        public function sanitise_preferences( $data ) {

            $data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
            $data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

            return $data;
        }
    }

endif;