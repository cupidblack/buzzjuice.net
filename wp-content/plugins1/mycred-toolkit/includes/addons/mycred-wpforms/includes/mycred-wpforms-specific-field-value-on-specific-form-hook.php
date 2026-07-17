<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * myCRED_wpforms_Specific_Field_Value_Specific_Form_Hook class
 * Creds for form submit specific field value on specific form events updates
 * 
 * 
 */
if (!class_exists('myCRED_wpforms_Specific_Field_Value_Specific_Form_Hook') && class_exists('myCRED_Hook')) {

	class myCRED_wpforms_Specific_Field_Specific_form_Hook extends myCRED_Hook {

		/**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'specific_field_specific_form',
			'defaults' => array( 
                        'limit' => '0/x',
                        'specific_field_specifc_form' => array(
                        'creds' => array(),
                        'log' => array(),
                        'select_post' => array(),
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

				add_action( 'wpforms_process_complete',  array( $this,'mycred_wp_forms_specific_field_value_specific_form_submission'), 40, 4 );
			
        }

        public function mycred_wp_forms_specific_field_value_specific_form_submission($fields, $entry, $form_data, $entry_id) {

        	// Login is required
            if ( ! is_user_logged_in() ) return;

            $user_id = get_current_user_id();
            $form_id = absint( $form_data['id'] );

             if(in_array( $this->prefs['specific_field_specifc_form'],$this->prefs )) {
                $hook_index = array_search( $this->prefs['specific_field_specifc_form'], $this->prefs );


            }


             foreach ( $fields as $field_id => $field ) {

             	 $field_name =  $field['name'];
                 $field_value = $field['value'];

                 if(in_array( $field['name'],$this->prefs['specific_field_specifc_form']['field_name'] )) {

                    $hook_index_field_name = array_search( $field['name'], $this->prefs['specific_field_specifc_form']['field_name'] );

                  if( $hook_index_field_name === false ){

                    foreach ( $this->prefs['specific_field_specifc_form']['field_name'] as $key => $value) {

                        
                    if( $this->prefs['specific_field_specifc_form']['field_name'] == $field['name'] && $value == 0 ) {
                                                    $hook_index_field_name = $key;
                        }
                   
                    }

                  }


                 }


                if(in_array( $field['value'],$this->prefs['specific_field_specifc_form']['field_value'] )) {

                    $hook_index_field_value = array_search( $field['value'], $this->prefs['specific_field_specifc_form']['field_value'] );

                    if( $hook_index_field_value === false ){

                    foreach ( $this->prefs['specific_field_specifc_form']['field_value'] as $key => $value) {

                        
                        if( $this->prefs['specific_field_specifc_form']['field_value'] == $field['value'] && $value == 0 ) {
                                                        $hook_index_field_value = $key;
                            }

                                             
                        }

                    }


                }


        		foreach ($this->prefs['specific_field_specifc_form']['select_post'] as $value) {
        			if(in_array( $value,$this->prefs['specific_field_specifc_form']['select_post'] )) {

                    $hook_index_post_name = array_search( $value, $this->prefs['specific_field_specifc_form']['select_post'] );

                     break;

                 }
        		}

        		 if( $hook_index_post_name === false ){

                    foreach ( $this->prefs['specific_field_specifc_form']['select_post'] as $key => $value) {


                    if($this->prefs['specific_field_specifc_form']['select_post'][$key] == $form_id && $value == 0 ) {
                                                    $hook_index_post_name = $key;
                        }

                                         
                    }

                    }


                if( apply_filters( 'mycred_wp_forms_exclude_field', false, $field_name, $field_value, $field ) ) {
                    continue;
                }

                   if( $this->core->has_entry( 'specific_field_specifc_form',  $form_id, $user_id) ) {
                        return;
                 } 

                 if ( $this->over_hook_limit('specific_field_specifc_form', 'specific_field_specifc_form', $user_id ) ) {
                    return;
                 }


                // Execute
                if ( !$this->over_hook_limit('specific_field_specifc_form', 'specific_field_specifc_form', $user_id ) ) {
                  if($this->prefs['specific_field_specifc_form']['field_name'][$hook_index_field_name] && $this->prefs['specific_field_specifc_form']['field_value'][$hook_index_field_value] && $this->prefs['specific_field_specifc_form']['select_post'][$hook_index_post_name]) { 

                    $this->core->add_creds(
                        'specific_field_specifc_form',
                        $user_id,
                        $this->prefs['specific_field_specifc_form']['creds'][$hook_index_field_value],
                        $this->prefs['specific_field_specifc_form']['log'][$hook_index_field_value],
                        $form_id,
                        'wpforms_form_specific_value_form_submit',
                        $this->mycred_type
                    );

                    break;

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

         public function mycred_wpforms_arrange_data( $specific_hook_data ) {

                $hook_data = array();
                foreach ( $specific_hook_data['creds'] as $key => $value ) {
                    $hook_data[$key]['creds']      = $value;
                    $hook_data[$key]['log']        = $specific_hook_data['log'][$key];
                    $hook_data[$key]['select_post'] = isset($specific_hook_data['select_post'][$key]) 
                    ? $specific_hook_data['select_post'][$key] 
                    : null;
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
            $form = wpforms()->get( 'form' )->get( (int) $id );
            $label = array();
            $wpforms_id = array();
            $specific_form_data = array(
                         array(
                            'creds' => 0,
                            'log' => __('%plural% for Submitting a Specific Field Value on Specific Form', 'mycred-wpforms'),
                            'limit' => '0/x',
                            'select_post' => 0,
                            'field_name' => '',
                            'field_value' => '',
                              
                        ),
            );   


            if ( count( $prefs['specific_field_specifc_form']['creds'] ) > 0 ) {

                   $specific_form_data = $this->mycred_wpforms_arrange_data( $prefs['specific_field_specifc_form'] );

            }

                if (is_array($form)) {
                    foreach ($form as $form_name) {

                        $form_data_fields = wpforms_decode($form_name->post_content);
                        $form_id = $form_name->ID;

                        foreach ( $form_data_fields['fields'] as $form_data_field) {


                            $form_data_field['label'] = sanitize_text_field( $form_data_field['label'] );
                            $form_data_field['id']    = sanitize_text_field( $form_data_field['id'] );
                              
                        }
                                                      
                    } 
                }

                 $specific_user_form = wpforms()->get( 'form' )->get( (int) $id );
		?>
		<div class="hook-instance" id="specific-hook">
                        <?php 
                        foreach($specific_form_data as $hook => $label) {

                            ?>
                    
                        <div class="form_specific_field_value_specific_form_custom_hook_class">
                            <div class="row">
                            	  <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Select a Form', 'select_post' ); ?></label>
                                        <select class="form-control user_select_post_specific_form " id="selected_option"  name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'select_post'))); ?>" value=""  >
                                            <?php
                                               foreach ($specific_user_form as $form_name)
                                                   {
                                                   	$form_id = $form_name->ID;
                                                   	$label['select_post'] == $form_name->ID;

                                                echo '<option class="select-value" value="'.esc_attr($form_name->ID).'" '. ( $form_name->ID == $label['select_post'] ? ' selected' : '') .' >'.esc_html($form_name->post_title).'</option>';

                                                   }
                                               ?>
                                        </select>    
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Enter Field Name', 'field_name' ); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'field_name'))); ?>" value="<?php echo esc_attr($label['field_name']); ?>" placeholder="Field name">

                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_htmL_e( 'Enter Field Value', 'field_value' ); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'field_value'))); ?>"  value="<?php echo esc_attr($label['field_value']); ?>" placeholder="Field value"> 
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_field_specifc_form' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_field_specifc_form' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number( $label['creds'])); ?>" class="form-control mycred-form-specific-field-value-specific-form-creds" />
                                    </div>
                                </div>   
                            </div>
                            <div class="row">
                            	<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_field_specifc_form' => 'log'))); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_field_specifc_form' => 'log'))); ?>" value="<?php echo esc_attr($label['log']) ; ?>" class="form-control mycred-form-specific-field-value-specific-form-log" />
                                        <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12  field_wrapper">
                                        <div class="form-group specific-hook-actions textright" >
                                            <button class="button button-small mycred-add-specific-form-specific-field-value-specific-form-hook add_button" id="clone_btn" type="button">Add More</button>
                                            <button class="button button-small mycred-remove-form-specific-field-value-specific-form-hook" type="button">Remove</button>
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
                                    <label for="<?php echo esc_attr($this->field_id( 'limit' )); ?>"><?php esc_html_e('', 'mycred'); ?></label>
                                    <?php echo wp_kses(
						$this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ),
						array(
							'div' => array(
								'class' => array()
							),
							'input' => array(
								'type' => array(),
								'size' => array(),
								'class' => array(),
								'name' => array(),
								'id' => array(),
								'value' => array()
							),
							'select' => array(
								'name' => array(),
								'id' => array(),
								'class' => array()
							),
							'option' => array(
								'value' => array(),
								'selected' => array()
							)
						) 
					); 
				?>
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

              foreach ( $data[ 'specific_field_specifc_form' ] as $data_key => $data_value ) {

                     foreach ( $data_value as $key => $value) {

                        if ( $data_key == 'creds' ) {
                            $data[ 'specific_field_specifc_form' ][$data_key][$key] = ( !empty( $value ) ) ? floatval( $value ) : 10;
                        }
                        else if ( $data_key == 'log' ) {
                            $data[ 'specific_field_specifc_form' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for submitting form';
                        }
                        else if ( $data_key == 'select_post' ) {
                            $data[ 'specific_field_specifc_form' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for submitting form';
                        }
                        else if ( $data_key == 'field_name' ) {
                            $data[ 'specific_field_specifc_form' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
                        }
                         else if ( $data_key == 'field_value' ) {
                            $data[ 'specific_field_specifc_form' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
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