<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * myCRED_wsform_Submit_Form_Hook class
 * Creds for form submit events updates
 * 
 * 
 */
if (!class_exists('myCRED_wsform_Submit_Form_Hook') && class_exists('myCRED_Hook')) {
class myCRED_wsform_Submit_Form_Hook extends myCRED_Hook {

	/**
	 * Construct
	 */
	public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		parent::__construct( array(
			'id'       => 'successful_submit_form',
			'defaults' => array(
				 		'creds' => 0,
                        'log' => __('%plural% for Successfully Submitting a form', 'mycred_wsform'), 
                        'limit' => '0/x',
                        'check_specific_hook' => 0,
                        'successful_submit_form' => array(
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

				add_action( 'wsf_submit_post_complete',  array( $this,'mycred_wsform_submission'), 40, 1 );

			
        }

    

        /**
		 * Form Submit
		 * 
		 * 
		 */
		public function mycred_wsform_submission( $ws_form_submit ) {

		    // Login is required
		    if ( ! is_user_logged_in() ) return;

		    $user_id = get_current_user_id();
		    $form_id = absint( $ws_form_submit->form_id );
        
		    if(  isset($this->prefs['check_specific_hook']) && $this->prefs['check_specific_hook'] == '1' && in_array($form_id,$this->prefs['successful_submit_form']['select_post'])    ) {

                    $hook_index = array_search( $form_id, $this->prefs['successful_submit_form']['select_post'] );


                    if( $hook_index === false ){

                    foreach ( $this->prefs['successful_submit_form']['select_post'] as $key => $value) {


                        
                    if( $this->prefs['successful_submit_form']['select_post'][$key] == $form_id && $value == 0 ) {
                                                    $hook_index = $key;
                        }

                                         
                    }

                    }



                   if ( ! empty( $this->prefs['successful_submit_form']['creds'] ) && isset( $this->prefs['successful_submit_form']['creds'][$hook_index] ) && 
                        !empty( $this->prefs['successful_submit_form']['log'] ) && !empty( $this->prefs['successful_submit_form']['log'][$hook_index] ) ) {

                 

                    if( $this->core->has_entry( 'successful_submit_form',  $form_id, $user_id) ) {
                        return;
                    } 

                    if($this->over_hook_limit('successful_submit_form', 'successful_submit_form', $user_id )) {
                        return;
                    }

              

                    $this->core->add_creds(
                                'successful_submit_form',
                                $user_id,
                                $this->prefs['successful_submit_form']['creds'][$hook_index],
                                $this->prefs['successful_submit_form']['log'][$hook_index],
                                $form_id,
                               'wsform_form_submit',
                                $this->mycred_type
                            );

                   }


		    } else {

    			// Make sure this is unique event
    			if ( $this->core->has_entry( 'successful_submit_form', $form_id, $user_id) ) return;

                  if($this->over_hook_limit('successful_submit_form', 'successful_submit_form', $user_id )) {
                        return;
                    }


    			// Execute
    			$this->core->add_creds(
    				'successful_submit_form',
    				$user_id,
    				$this->prefs['creds'],
    				$this->prefs['log'],
    				$form_id,
    				'wsform_form_submit',
    				$this->mycred_type
    			);
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

            
?>
               <div class="hook-instance">
                    <h3><?php esc_html_e( 'General', 'mycred' ); ?></h3>
                    <div class="row">
                        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id( 'creds' )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'creds' )); ?>" id="<?php echo esc_attr($this->field_id( 'creds' )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['creds'] )); ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id('log' )); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'log' )); ?>" id="<?php echo esc_attr($this->field_id( 'log' )); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
                                <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
                            </div>
                        </div>
                    </div>
               </div>

<?php 

                     $specific_form_data = array(
	                     array(
	                        'creds' => 0,
	                        'log' => __('%plural% for Successfully Submitting a specific form', 'mycred_wsform'),
	                        'limit' => '0/x',
	                        'select_post' => 0,
	                          
	                    ),
	                );



                   if ( count( $prefs['successful_submit_form']['creds'] ) > 0 ) {

                    $specific_form_data = $this->mycred_wsform_arrange_data( $prefs['successful_submit_form'] );

                   }

                	// Get WS Form forms
                	$forms = array();
                	if ( function_exists( 'wsf_form_get_all' ) ) {
                		$forms = wsf_form_get_all( false, 'label' );
                	}

                    

                 ?>

            <div class="hook-instance" id="specific-hook">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="hook-title">
                                    <h3><?php esc_html_e( 'Specific', 'mycred' ); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <?php
                            $is_enabled = ( isset( $prefs['check_specific_hook'] ) && $prefs['check_specific_hook'] == '1' );
                            if ( function_exists( 'mycred_create_toggle_field' ) ) {
                                mycred_create_toggle_field(
                                    array(
                                        'id'   => $this->field_id( 'check_specific_hook' ),
                                        'name' => $this->field_name( 'check_specific_hook' ),
                                        'label' => __( 'Enable Specific Hook', 'mycred_wsform' ),
                                        'after' => false,
                                    ),
                                    1,
                                    $is_enabled
                                );
                            } else {
                                ?>
                                <label for="<?php echo esc_attr( $this->field_id( 'check_specific_hook' ) ); ?>">
                                    <input type="checkbox" id="<?php echo esc_attr( $this->field_id( 'check_specific_hook' ) ); ?>" name="<?php echo esc_attr( $this->field_name( 'check_specific_hook' ) ); ?>" value="1" <?php checked( $is_enabled, true ); ?>>
                                    <?php esc_html_e( 'Enable Specific Hook', 'mycred_wsform' ); ?>
                                </label>
                                <?php
                            }
                            ?>
                        </div>

                        <?php 
                        foreach($specific_form_data as $hook => $label) {

                            ?>

                        <div class="form_submit_custom_hook_class">
                            <div class="row">
                                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label><?php esc_html_e( 'Select a Form', 'select_post' ); ?></label>
                                        <select class="form-control user_select_post " id="selected_option"  name="<?php echo esc_attr($this->specific_field_name(array('successful_submit_form' => 'select_post'))); ?>" value=""  >
                                  
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
                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('successful_submit_form' => 'creds'))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('successful_submit_form' => 'creds'))); ?>" id="<?php echo esc_attr($this->field_id(array('successful_submit_form' => 'creds'))); ?>" value="<?php echo esc_attr($this->core->number( $label['creds'])); ?>" class="form-control mycred-form-submit-creds" />
                                    </div>
                                </div>
                                <div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label for="<?php echo esc_attr($this->field_id(array('successful_submit_form' => 'log'))); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                        <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('successful_submit_form' => 'log'))); ?>" id="<?php echo esc_attr($this->field_id(array('successful_submit_form' => 'log'))); ?>" value="<?php echo esc_attr($label['log']) ; ?>" class="form-control mycred-form-submit-log" />
                                        <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
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


			 $data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
              $data['check_specific_hook'] = ( !empty( $data['check_specific_hook'] ) ) ? sanitize_text_field( $data['check_specific_hook'] ) : $this->defaults['check_specific_hook'];
              $data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

               if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
                $limit = sanitize_text_field( $data['limit'] );
                if ( $limit == '' ) $limit = 0;
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset( $data['limit_by'] );
                }


                 foreach ( $data[ 'successful_submit_form' ] as $data_key => $data_value ) {

                     foreach ( $data_value as $key => $value) {

                        if ( $data_key == 'creds' ) {
                            $data[ 'successful_submit_form' ][$data_key][$key] = ( !empty( $value ) ) ? floatval( $value ) : 10;
                        }
                        else if ( $data_key == 'log' ) {
                            $data[ 'successful_submit_form' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for submitting form';
                        }
                        else if ( $data_key == 'select_post' ) {
                            $data[ 'successful_submit_form' ][$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
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
