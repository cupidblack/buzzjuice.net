<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * myCRED_ninjaforms_Submit_Form_Hook class
 * Creds for form submit events updates
 * 
 * 
 */
if (!class_exists('myCRED_ninjaforms_Submit_Form_Hook') && class_exists('myCRED_Hook')) {
class myCRED_ninjaforms_Submit_Form_Hook extends myCRED_Hook {


	/**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'successful_submit_ninjaform',
			'defaults' => array(
				 		'creds' => 0,
                        'log' => __('%plural% for Successfully Submitting a form', 'mycred-ninjaforms'), 
                        'limit' => '0/x',
                        'check_specific_hook' => 0,
                        'specific_form_submitted' => array(
                        'creds' => array(),
                        'log' => array(),
                        'select_post' => array()
                        ),
			)
		), $hook_prefs, $type );

	}



       /**
		 * Run
		 * 
		 * 
		 */
		public function run() {

				add_action( 'ninja_forms_after_submission',  array( $this,'mycred_ninja_forms_submission'), 40 );

			
        }

    

        /**
		 * Form Submit
		 * 
		 * 
		 */
		public function mycred_ninja_forms_submission( $data ) {


		    $form_id = $data['form_id'];
		    $user_id = get_current_user_id();

		    // Login is required
		    if ( $user_id === 0 ) return;


            if ( !$this->over_hook_limit('specific_form_submitted', 'specific_form_submitted', $user_id ) ) { 


              if(  isset($this->prefs['check_specific_hook']) && $this->prefs['check_specific_hook'] == '1' && in_array($form_id,$this->prefs['specific_form_submitted']['select_post'])) {

                   $hook_index = array_search( $form_id, $this->prefs['specific_form_submitted']['select_post'] );

                     if( $hook_index === false ){

                    foreach ( $this->prefs['specific_form_submitted']['select_post'] as $key => $value) {


                        
                    if( $this->prefs['specific_form_submitted']['select_post'][$key] == $form_id && $value == 0 ) {
                                                    $hook_index = $key;
                        }

                                         
                    }

                    }

                       if ( ! empty( $this->prefs['specific_form_submitted']['creds'] ) && isset( $this->prefs['specific_form_submitted']['creds'][$hook_index] ) && 
                        !empty( $this->prefs['specific_form_submitted']['log'] ) && !empty( $this->prefs['specific_form_submitted']['log'][$hook_index] ) ) {


                    if( $this->core->has_entry( 'successful_submit_ninjaform',  $form_id, $user_id) ) {
                        return;
                    } 

              

                    $this->core->add_creds(
                                'successful_submit_ninjaform',
                                $user_id,
                                $this->prefs['specific_form_submitted']['creds'][$hook_index],
                                $this->prefs['specific_form_submitted']['log'][$hook_index],
                                $form_id,
                               'ninjaforms_form_submit',
                                $this->mycred_type
                            );



                       }


              }

              else {


    		    if ( $this->core->has_entry( 'successful_submit_ninjaform', $form_id, $user_id) ) return;

    		    $this->core->add_creds(
        				'successful_submit_ninjaform',
        				$user_id,
        				$this->prefs['creds'],
        				$this->prefs['log'],
        				$form_id,
        				'ninjaforms_form_submit',
        				$this->mycred_type
        			);
		   }

           }

		}

		public function specific_field_name( $field = '' ) {

                $hook_prefs_key = 'mycred_pref_hooks';

               if ( is_array( $field ) ) {
                   $array = array();
                   foreach ( $field as $parent => $child ) {
                       if ( ! is_numeric( $parent ) )
                           $array[] = $parent;
   
                       if ( ! empty( $child ) && !is_array( $child ) )
                           $array[] = $child;
                   }
                   $field = '[' . implode( '][', $array ) . ']';
               }
               else {
                   $field = '[' . $field . ']';
               }

               $option_id = 'mycred_pref_hooks';
               if ( ! $this->is_main_type )
               $option_id = $option_id . '_' . $this->mycred_type;
   
               return $option_id . '[hook_prefs]['. $this->id . ']'  . $field . '[]';
   
        }


         public function mycred_ninjaforms_arrange_data( $specific_hook_data ){
              
                $hook_data = array();
                foreach ( $specific_hook_data['creds'] as $key => $value ) {
                    $hook_data[$key]['creds']      = $value;
                    $hook_data[$key]['log']        = $specific_hook_data['log'][$key];
                    $hook_data[$key]['select_post'] = $specific_hook_data['select_post'][$key];
                   
                }
                return $hook_data;
          }     

        /**
		 * Preferences
		 * 
		 * 
		 */
		public function preferences() {

			$prefs = $this->prefs;

          
?>
               <div class="hook-instance">
                    <h3><?php _e( 'General', 'mycred' ); ?></h3>
                    <div class="row">
                        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id( 'creds' )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'creds' )); ?>" id="<?php echo $this->field_id( 'creds' ); ?>" value="<?php echo esc_attr($this->core->number( $prefs['creds'] )); ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id('log' )); ?>"><?php _e('Log Template', 'mycred'); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'log' )); ?>" id="<?php echo $this->field_id( 'log' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
                                <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
                            </div>
                        </div>
                    </div>
               </div>


       <?php 

                     $specific_form_data = array(
	                     array(
	                        'creds' => 0,
	                        'log' => __('%plural% for Successfully Submitting a specific form', 'mycred-toolkit'),
	                        'limit' => '0/x',
	                        'select_post' => 0,
	                          
	                    ),
	                );



                   if ( count( $prefs['specific_form_submitted']['creds'] ) > 0 ) {

                    $specific_form_data = $this->mycred_ninjaforms_arrange_data( $prefs['specific_form_submitted'] );

                   }

                   $forms = Ninja_Forms()->form()->get_forms();


                 ?>

                  <div class="hook-instance" id="specific-hook">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="hook-title">
                                    <h3><?php _e( 'Specific', 'mycred' ); ?></h3>
                                </div>
                            </div>
                        </div>
                         <div class="checkbox" style="margin-bottom:14px;">
                            <input type="checkbox" id="<?php echo esc_attr($this->field_id('check_specific_hook')); ?>" name="<?php echo esc_attr($this->field_name('check_specific_hook')); ?>" value="1" <?php if( $prefs['check_specific_hook'] == '1') echo "checked = 'checked'"; ?>>
                            <label for="specifichook"><?php _e( 'Enable Specific Hook', 'mycred' ); ?> </label>
                        </div> 

                        <?php 


                        foreach($specific_form_data as $hook => $label) {

                            ?>

                        <div class="form_submit_custom_hook_class">
                            <div class="row">

                              
                                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Select a Form', 'select_post' ); ?></label>
                                        <select class="form-control user_select_post " id="selected_option"  name="<?php echo esc_attr($this->specific_field_name(array('specific_form_submitted' => 'select_post'))); ?>" value=""  >
                                  
                                            <?php

                                               foreach ($forms as $form)

                                                   {

                                                     $form_id  = $form->get_id();
                                                     $form_name    = $form->get_setting( 'title' );
                                                     $label['select_post'] == $form->get_id();

                                                echo '<option class="select-value" value="'.esc_attr($form->get_id()).'" '. ( $form->get_id() == $label['select_post'] ? ' selected' : '') .' >'.$form->get_setting( 'title' ).'</option>';

                                                   }
                                               ?>
                                        </select>    
                                    </div>
                                </div>

                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_form_submitted' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_form_submitted' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_form_submitted' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number( $label['creds'])); ?>" class="form-control mycred-form-submit-creds" />
                                    </div>
                                </div>
                                <div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_form_submitted' => 'log'))); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_form_submitted' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_form_submitted' => 'log'))); ?>" value="<?php echo esc_attr($label['log']) ; ?>" class="form-control mycred-form-submit-log" />
                                        <span class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12  field_wrapper">
                                        <div class="form-group specific-hook-actions textright" >
                                            <button class="button button-small mycred-add-specific-form-submit-hook add_button" id="clone_btn" type="button">Add More</button>
                                            <button class="button button-small mycred-remove-form-submit-hook" type="button">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div> 
                        </div>

                    <?php 

                            }
                    
                    ?>
                </div>
                <div class="hook-instance">
                        <h3><?php esc_html_e( 'Limit', 'mycred' ); ?></h3>
                        <div class="row">
                            <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <?php add_filter('mycred_hook_limits', array($this, 'custom_limit')); ?>
                                    <label for="<?php echo esc_attr($this->field_id( 'limit' )); ?>"><?php _e('', 'mycred'); ?></label>
                                    <?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ); ?>
                                </div>
                            </div>
                        </div>
                </div>

                     
<?php

		}


        /**
		 * Sanitise Preferences
		 * 
		 * 
		 */
		public function sanitise_preferences( $data ) {


			 $data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
              $data['check_specific_hook'] = ( !empty( $data['check_specific_hook'] ) ) ? sanitize_text_field( $data['check_specific_hook'] ) : $this->defaults['check_specific_hook'];
              $data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

               if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
                $limit = sanitize_text_field( $data['limit'] );
                if ( $limit == '' ) $limit = 0;
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset( $data['limit_by'] );
                }


                 foreach ( $data[ 'specific_form_submitted' ] as $data_key => $data_value ) {

                     foreach ( $data_value as $key => $value) {

                        if ( $data_key == 'creds' ) {
                            $data[ 'specific_form_submitted' ][$data_key][$key] = ( !empty( $value ) ) ? floatval( $value ) : 10;
                        }
                        else if ( $data_key == 'log' ) {
                            $data[ 'specific_form_submitted' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for submitting form';
                        }
                        else if ( $data_key == 'select_post' ) {
                            $data[ 'specific_form_submitted' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
                        }
                       
                    }
               }



			return $data;

		}

		 public function custom_limit() {
                return array(
                    'x' => __('No limit', 'mycred'),
                    'd' => __('/ Day', 'mycred'),
                    'w' => __('/ Week', 'mycred'),
                    'm' => __('/ Month', 'mycred'),
                );
          }

	}

}