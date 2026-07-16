<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * myCRED_wsform_Specific_Field_Value_Specific_Form_Hook class
 * Creds for form submit specific field value on specific form events updates
 * 
 * 
 */
if (!class_exists('myCRED_wsform_Specific_Field_Value_Specific_Form_Hook') && class_exists('myCRED_Hook')) {

	class myCRED_wsform_Specific_Field_Specific_form_Hook extends myCRED_Hook {

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

				add_action( 'wsf_submit_post_complete',  array( $this,'mycred_wsform_specific_field_value_specific_form_submission'), 40, 1 );
			
        }

        public function mycred_wsform_specific_field_value_specific_form_submission( $ws_form_submit ) {

        	// Login is required
            if ( ! is_user_logged_in() ) return;

            $user_id = get_current_user_id();
            $form_id = absint( $ws_form_submit->form_id );

            // Get fields from form object
            if ( ! isset( $ws_form_submit->form_object ) || ! is_object( $ws_form_submit->form_object ) ) {
                return;
            }

            // Get all fields from the form
            $fields = array();
            if ( isset( $ws_form_submit->form_object->groups ) && is_array( $ws_form_submit->form_object->groups ) ) {
                foreach ( $ws_form_submit->form_object->groups as $group ) {
                    if ( isset( $group->sections ) && is_array( $group->sections ) ) {
                        foreach ( $group->sections as $section ) {
                            if ( isset( $section->fields ) && is_array( $section->fields ) ) {
                                foreach ( $section->fields as $field ) {
                                    if ( isset( $field->id ) ) {
                                        $fields[] = $field;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $field_prefix = defined( 'WS_FORM_FIELD_PREFIX' ) ? WS_FORM_FIELD_PREFIX : 'field_';
            $prefs = $this->prefs['specific_field_specifc_form'];

            foreach ( $fields as $field ) {
                $field_id = isset( $field->id ) ? absint( $field->id ) : 0;
                $field_name = isset( $field->label ) ? sanitize_text_field( $field->label ) : '';
                if ( empty( $field_id ) ) {
                    continue;
                }

                // Get submitted value using WS Form API when available, otherwise from meta
                if ( function_exists( 'wsf_submit_get_value' ) ) {
                    $field_value = wsf_submit_get_value( $ws_form_submit, $field_prefix . $field_id, '' );
                } else {
                    $meta_key = $field_prefix . $field_id;
                    $field_value = '';
                    if ( isset( $ws_form_submit->meta ) ) {
                        $meta = is_array( $ws_form_submit->meta ) ? ( isset( $ws_form_submit->meta[ $meta_key ] ) ? $ws_form_submit->meta[ $meta_key ] : null ) : ( isset( $ws_form_submit->meta->{$meta_key} ) ? $ws_form_submit->meta->{$meta_key} : null );
                        if ( $meta !== null ) {
                            $field_value = is_array( $meta ) ? ( isset( $meta['value'] ) ? $meta['value'] : '' ) : ( isset( $meta->value ) ? $meta->value : '' );
                        }
                    }
                }
                if ( is_array( $field_value ) ) {
                    $field_value = implode( ', ', $field_value );
                }
                $field_value = trim( (string) $field_value );

                if ( apply_filters( 'mycred_wsform_exclude_field', false, $field_name, $field_value, $field, $form_id, $user_id ) ) {
                    continue;
                }

                if ( $this->core->has_entry( 'specific_field_specifc_form', $form_id, $user_id ) ) {
                    return;
                }
                if ( $this->over_hook_limit( 'specific_field_specifc_form', 'specific_field_specifc_form', $user_id ) ) {
                    return;
                }

                // Match by same row: form_id, field name (label), and field value
                if ( empty( $prefs['select_post'] ) || ! is_array( $prefs['select_post'] ) ) {
                    continue;
                }
                foreach ( $prefs['select_post'] as $hook_index => $configured_form_id ) {
                    if ( (int) $configured_form_id !== (int) $form_id ) {
                        continue;
                    }
                    $configured_name  = isset( $prefs['field_name'][ $hook_index ] ) ? trim( (string) $prefs['field_name'][ $hook_index ] ) : '';
                    $configured_value = isset( $prefs['field_value'][ $hook_index ] ) ? trim( (string) $prefs['field_value'][ $hook_index ] ) : '';
                    if ( $configured_name !== '' && $field_name !== $configured_name ) {
                        continue;
                    }
                    if ( $configured_value !== '' && (string) $configured_value !== (string) $field_value ) {
                        continue;
                    }
                    if ( $configured_value === '' && $field_value === '' ) {
                        continue;
                    }
                    if ( empty( $prefs['creds'] ) || ! isset( $prefs['creds'][ $hook_index ] ) ) {
                        continue;
                    }
                    if ( empty( $prefs['log'] ) || ! isset( $prefs['log'][ $hook_index ] ) ) {
                        continue;
                    }
                    $this->core->add_creds(
                        'specific_field_specifc_form',
                        $user_id,
                        $prefs['creds'][ $hook_index ],
                        $prefs['log'][ $hook_index ],
                        $form_id,
                        'wsform_form_specific_value_form_submit',
                        $this->mycred_type
                    );
                    return;
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

         public function mycred_wsform_arrange_data( $specific_hook_data ) {

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
            $specific_form_data = array(
                         array(
                            'creds' => 0,
                            'log' => __('%plural% for Submitting a Specific Field Value on Specific Form', 'mycred_wsform'),
                            'limit' => '0/x',
                            'select_post' => 0,
                            'field_name' => '',
                            'field_value' => '',
                              
                        ),
            );   


            if ( count( $prefs['specific_field_specifc_form']['creds'] ) > 0 ) {

                   $specific_form_data = $this->mycred_wsform_arrange_data( $prefs['specific_field_specifc_form'] );

            }

            // Get WS Form forms
            $forms = array();
            if ( function_exists( 'wsf_form_get_all' ) ) {
            	$forms = wsf_form_get_all( false, 'label' );
            }

		?>
		<div class="hook-instance" id="specific-hook">
                        <?php 
                        foreach($specific_form_data as $hook => $label) {

                            ?>
                    
                        <div class="form_specific_field_value_specific_form_custom_hook_class">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Select a Form', 'select_post' ); ?></label>
                                        <select class="form-control user_select_post_specific_form" id="selected_option" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'select_post'))); ?>">
                                            <?php
                                               if ( is_array( $forms ) && ! empty( $forms ) ) {
                                                   foreach ($forms as $form) {
                                                       $form_id = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
                                                       $form_label = isset( $form['label'] ) ? $form['label'] : '';
                                                       if ( $form_id > 0 ) {
                                                           echo '<option class="select-value" value="'.esc_attr($form_id).'" '. ( $form_id == $label['select_post'] ? ' selected' : '') .' >'.esc_html($form_label).'</option>';
                                                       }
                                                   }
                                               }
                                               ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Enter Field Name', 'field_name' ); ?></label>
                                        <input type="text" class="form-control" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'field_name'))); ?>" value="<?php echo esc_attr($label['field_name']); ?>" placeholder="Field name" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Enter Field Value', 'field_value' ); ?></label>
                                        <input type="text" class="form-control" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'field_value'))); ?>" value="<?php echo esc_attr($label['field_value']); ?>" placeholder="Field value" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_field_specifc_form' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_specifc_form' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_field_specifc_form' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number( $label['creds'])); ?>" class="form-control mycred-form-specific-field-value-specific-form-creds" />
                                    </div>
                                </div>
                                <div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
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
                            $data[ 'specific_field_specifc_form' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
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
