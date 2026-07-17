<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * myCRED_ninjaforms_Specific_Field_Value_Hook class
 * Creds for form submit specific field value events updates
 * 
 * 
 */
if (!class_exists('myCRED_ninjaforms_Specific_Field_Value_Hook') && class_exists('myCRED_Hook')) {
class myCRED_ninjaforms_Specific_Field_Value_Hook extends myCRED_Hook {

		/**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'specific_field_value',
			'defaults' => array(
                        'limit' => '0/x',
                        'check_specific_hook' => 0,
                        'specific_field_value' => array(
                        'creds' => array(),
                        'log' => array(),
                        'field_name' => array(),
                        'field_value' => array()
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

				add_action( 'ninja_forms_after_submission',  array( $this,'mycred_specific_field_value_submission'), 40 );

        }

        public function mycred_specific_field_value_submission($data) {

        	$form_id = $data['form_id'];
		    $user_id = get_current_user_id();

		    // Login is required
		    if ( $user_id === 0 ) return;

		    $fields = $data['fields'];


		    if(in_array( $this->prefs['specific_field_value'],$this->prefs )) {
                $hook_index = array_search( $this->prefs['specific_field_value'], $this->prefs );
            }

		    foreach ( $fields as $field ) { 


		    	// Excluded fields
		        if( in_array( $field['type'], array( 'captcha', 'section', 'submit' ) ) ) {
		            continue;
		        }

		        $field_name = $field['key'];
		        $field_value = $field['value'];

		        $field_label = $field['label'];


		        if(in_array( $field['label'],$this->prefs['specific_field_value']['field_name'])) {

		        	 $hook_index_field_name = array_search( $field['label'], $this->prefs['specific_field_value']['field_name'] );

		        	  if( $hook_index_field_name === false ){

                    foreach ( $this->prefs['specific_field_value']['field_name'] as $key => $value) {

                        
                    if( $this->prefs['specific_field_value']['field_name'] == $field['label'] && $value == 0 ) {
                                                    $hook_index_field_name = $key;
                        }
                   
                    }

                  }

		        }


		         if(in_array( $field['value'],$this->prefs['specific_field_value']['field_value'] )) {

                    $hook_index_field_value = array_search( $field['value'], $this->prefs['specific_field_value']['field_value'] );

                    if( $hook_index_field_value === false ){

                    foreach ( $this->prefs['specific_field_value']['field_value'] as $key => $value) {

                        
                        if( $this->prefs['specific_field_value']['field_value'] == $field['value'] && $value == 0 ) {
                                                        $hook_index_field_value = $key;
                            }

                                             
                        }

                    }


                }



		         if( apply_filters( 'mycred_ninja_forms_exclude_field', false, $field_name, $field_value, $field ) ) {
            			continue;

            	 }


            	 if( $this->core->has_entry( 'specific_field_value',  $form_id, $user_id) ) {
                        return;
                 } 

                 if ( $this->over_hook_limit('specific_field_value', 'specific_field_value', $user_id ) ) {
                    return;
                 }

 

            	// Execute
                if ( !$this->over_hook_limit('specific_field_value', 'specific_field_value', $user_id ) ) {
                  if($this->prefs['specific_field_value']['field_name'][$hook_index_field_name] && $this->prefs['specific_field_value']['field_value'][$hook_index_field_value]) { 



                    $this->core->add_creds(
                        'specific_field_value',
                        $user_id,
                        $this->prefs['specific_field_value']['creds'][$hook_index_field_value],
                        $this->prefs['specific_field_value']['log'][$hook_index_field_value],
                        $form_id,
                        'ninjaforms_form_specific_value_submit',
                        $this->mycred_type
                    );

                  }

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
                    $hook_data[$key]['field_name'] = $specific_hook_data['field_name'][$key];  
                    $hook_data[$key]['field_value'] = $specific_hook_data['field_value'][$key];  
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
            $id = '';
            $forms = Ninja_Forms()->form()->get_forms();
            $form_fields = Ninja_Forms()->form( $id )->get_fields();
            $label = array();
            $ninjaforms_id = array();
            $specific_form_data = array(
                         array(
                            'creds' => 0,
                            'log' => __('%plural% for Submitting a Specific Field Value', 'mycred-toolkit'),
                            'limit' => '0/x',
                            'field_name' => '',
                            'field_value' => '',
                              
                        ),
            );   

                if ( count( $prefs['specific_field_value']['creds'] ) > 0 ) {

                   $specific_form_data = $this->mycred_ninjaforms_arrange_data( $prefs['specific_field_value'] );

                }

               foreach ($forms as $form){

	               	$form_id  = $form->get_id();
	                $form_name    = $form->get_setting( 'title' );


		                foreach( $form_fields as $field ) {

		                	if( is_object( $field ) ) {
				                $field = array(
				                    'id' => $field->get_id(),
				                    'settings' => $field->get_settings()
				                );
		            		}

			            	$field['settings']['field_label'] = sanitize_text_field($field['settings']['field_label']);
			            	$field[ 'id' ] = sanitize_text_field($field[ 'id' ]);

		       

		                }

                  }


		?>

		<div class="hook-instance" id="specific-hook">

                        <?php 
                        foreach($specific_form_data as $hook => $label) {

                            ?>
                    
                        <div class="form_specific_field_value_custom_hook_class">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Enter Field Name', 'field_name' ); ?></label>
                                        <input type="text" class="form-control" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_value' => 'field_name'))); ?>" value="<?php echo esc_attr($label['field_name']); ?>" placeholder="Field name">

                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Enter Field Value', 'field_value' ); ?></label>
                                        <input type="text" class="form-control" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_value' => 'field_value'))); ?>"  value="<?php echo esc_attr($label['field_value']); ?>" placeholder="Field value"> 
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_field_value' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_value' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_field_value' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number( $label['creds'])); ?>" class="form-control mycred-form-specific-field-value-creds" />
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_field_value' => 'log'))); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_value' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_field_value' => 'log'))); ?>" value="<?php echo esc_attr($label['log']) ; ?>" class="form-control mycred-form-specific-field-value-log" />
                                        <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12  field_wrapper">
                                        <div class="form-group specific-hook-actions textright" >
                                            <button class="button button-small mycred-add-specific-form-specific-field-value-hook add_button" id="clone_btn" type="button">Add More</button>
                                            <button class="button button-small mycred-remove-form-specific-field-value-hook" type="button">Remove</button>
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
                        <h3><?php _e( 'Limit', 'mycred' ); ?></h3>
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

              if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
                $limit = sanitize_text_field( $data['limit'] );
                if ( $limit == '' ) $limit = 0;
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset( $data['limit_by'] );
                }

              foreach ( $data[ 'specific_field_value' ] as $data_key => $data_value ) {

                     foreach ( $data_value as $key => $value) {

                        if ( $data_key == 'creds' ) {
                            $data[ 'specific_field_value' ][$data_key][$key] = ( !empty( $value ) ) ? floatval( $value ) : 10;
                        }
                        else if ( $data_key == 'log' ) {
                            $data[ 'specific_field_value' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for submitting form';
                        }
                        else if ( $data_key == 'field_name' ) {
                            $data[ 'specific_field_name' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
                        }
                         else if ( $data_key == 'field_value' ) {
                            $data[ 'specific_field_value' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
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