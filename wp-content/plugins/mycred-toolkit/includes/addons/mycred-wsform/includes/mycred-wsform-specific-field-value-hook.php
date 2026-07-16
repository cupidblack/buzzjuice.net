<?php
if( !defined( 'ABSPATH' ) ) exit;

/**
 * myCRED_wsform_Specific_Field_Value_Hook class
 * Creds for form submit specific field value events updates
 * 
 * 
 */
if (!class_exists('myCRED_wsform_Specific_Field_Value_Hook') && class_exists('myCRED_Hook')) {
class myCRED_wsform_Specific_Field_Value_Hook extends myCRED_Hook {

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

				add_action( 'wsf_submit_post_complete',  array( $this,'mycred_wsform_fields_submission'), 40, 1 );
			
        }


        /**
		 * Form Submit
		 * 
		 * 
		 */
		public function mycred_wsform_fields_submission( $ws_form_submit ) {

		    // Login is required
		    if ( ! is_user_logged_in() ) return;

		    $user_id = get_current_user_id();
		    $form_id = absint( $ws_form_submit->form_id );

            if ( $this->core->exclude_user( $user_id ) ) return;

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

                $ref_type = array( 'ref_type' => 'post', 'field_id' => $field_id );

                if ( apply_filters( 'mycred_wsform_exclude_field', false, $field_name, $field_value, $field, $form_id, $user_id ) ) {
                    continue;
                }

                // Match by both field name (label) and field value for the same hook row
                $prefs_fname = isset( $this->prefs['specific_field_value']['field_name'] ) ? $this->prefs['specific_field_value']['field_name'] : array();
                $prefs_fval  = isset( $this->prefs['specific_field_value']['field_value'] ) ? $this->prefs['specific_field_value']['field_value'] : array();
                foreach ( $prefs_fval as $hook_index => $configured_value ) {
                    $configured_name  = isset( $prefs_fname[ $hook_index ] ) ? trim( (string) $prefs_fname[ $hook_index ] ) : '';
                    $configured_value = trim( (string) $configured_value );
                    if ( $configured_name !== '' && $field_name !== $configured_name ) {
                        continue;
                    }
                    // Empty "Field Value" means: award when this field has any submitted value
                    if ( $configured_value !== '' && (string) $configured_value !== (string) $field_value ) {
                        continue;
                    }
                    if ( $configured_value === '' && $field_value === '' ) {
                        continue;
                    }
                    if ( empty( $this->prefs['specific_field_value']['creds'] ) || ! isset( $this->prefs['specific_field_value']['creds'][ $hook_index ] ) ) {
                        continue;
                    }
                    if ( empty( $this->prefs['specific_field_value']['log'] ) || ! isset( $this->prefs['specific_field_value']['log'][ $hook_index ] ) ) {
                        continue;
                    }
                    if ( $this->over_hook_limit( 'specific_field_value', 'specific_field_value', $user_id ) ) {
                        return;
                    }
                    if ( $this->core->has_entry( 'specific_field_value', null, $user_id, $ref_type ) ) {
                        return;
                    }
                    $this->core->add_creds(
                        'specific_field_value',
                        $user_id,
                        $this->prefs['specific_field_value']['creds'][ $hook_index ],
                        $this->prefs['specific_field_value']['log'][ $hook_index ],
                        $form_id,
                        $ref_type,
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

         public function mycred_wsform_arrange_data( $specific_hook_data ){
              
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
                            'log' => __('%plural% for Submitting a Specific Field Value', 'mycred_wsform'),
                            'limit' => '0/x',
                            'select_post' => 0,
                            'field_name' => '',
                            'field_value' => '',
                              
                        ),
            );   

                if ( count( $prefs['specific_field_value']['creds'] ) > 0 ) {

                   $specific_form_data = $this->mycred_wsform_arrange_data( $prefs['specific_field_value'] );

                }

		?>

		<div class="hook-instance" id="specific-hook">

                        <?php 
                        foreach($specific_form_data as $hook => $label) {
                          

                            ?>
                    
                        <div class="form_specific_field_value_custom_hook_class">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Enter Field Name', 'field_name' ); ?></label>
                                        <input type="text" class="form-control" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_value' => 'field_name'))); ?>" value="<?php echo esc_attr($label['field_name']); ?>" placeholder="Field name" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Enter Field Value', 'field_value' ); ?></label>
                                        <input type="text" class="form-control" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_value' => 'field_value'))); ?>" value="<?php echo esc_attr($label['field_value']); ?>" placeholder="Field value" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('specific_field_value' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('specific_field_value' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('specific_field_value' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number( $label['creds'])); ?>" class="form-control mycred-form-specific-field-value-creds" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
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



              foreach ( $data[ 'specific_field_value' ] as $data_key => $data_value ) {

                     foreach ( $data_value as $key => $value) {

                        if ( $data_key == 'creds' ) {
                            $data[ 'specific_field_value' ][$data_key][$key] = ( !empty( $value ) ) ? intval( $value ) : 10;


                        }
                        elseif ( $data_key == 'log' ) {
                            $data[ 'specific_field_value' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for submitting form';
                        }

                        elseif ( $data_key == 'select_post' ) {
                            $data[ 'specific_field_value' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : 0;
                        }
                        elseif ( $data_key == 'field_name' ) {
                            $data[ 'specific_field_value' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
                        }
                         elseif ( $data_key == 'field_value' ) {
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
