<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * mycred_tutor_lms_Addons_Module class
 * @since 0.1
 * @version 1.1.1
 */
if ( ! class_exists( 'mycred_tutor_lms_Geneal_Course_Hook_Class' ) ) :
	class mycred_tutor_lms_Geneal_Course_Hook_Class extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = mycred_DEFAULT_TYPE_KEY ) {

			parent::__construct( 
			array(
				'id'       => 'tutor_lms_enroll_courses',
				'defaults' => array(
					'creds'      => 10,
					'log'        => '%plural% for enroll any course.',
					'limit'      => 'x',
					'mycred_check_specific' =>'1',
					'tutor_lms_enroll_course' => array(
						'creds'   => array(),
						'log'     => array(),
						'select_enroll' => array()
					),
				)
			), $hook_prefs, $type );
		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'tutor_after_enroll', array( $this, 'my_cred_enroll_generl_course_func' ) , 10 , 1);
		}

		/**
		 * Page Load
		 * @since 1.8
		 * @version 1.0
		 */
		public function my_cred_enroll_generl_course_func( $course ) {

			// Make sure user is not excluded
			if ( !is_user_logged_in( ) ) {
return;
			}

			$user_id = get_current_user_id( );
		   
			if ( $this->core->exclude_user( $user_id ) ) {
return;
			}
	 
			$ref_type  = array(
				'ref_type' => 'post',
				'course' => $course
			);
		   
			if ( !$this->over_hook_limit('tutor_lms_enroll_course', 'tutor_lms_enroll_course', $user_id ) ) {

				if ( $this->prefs['mycred_check_specific'] == '1' && ! empty( $this->prefs['tutor_lms_enroll_course']['select_enroll'] ) && in_array( $course, $this->prefs['tutor_lms_enroll_course']['select_enroll'] ) ) {

					$hook_index = array_search( $course, $this->prefs['tutor_lms_enroll_course']['select_enroll'] );

					if ( !empty( $this->prefs['tutor_lms_enroll_course']['creds'] ) && isset( $this->prefs['tutor_lms_enroll_course']['creds'][ $hook_index ] ) && !empty( $this->prefs['tutor_lms_enroll_course']['log'] ) && !empty( $this->prefs['tutor_lms_enroll_course']['log'][ $hook_index ] ) ) {
						// Make sure this is a unique event
						if ( !$this->core->has_entry( 'tutor_lms_enroll_course' , null , $user_id , $ref_type, $this->mycred_type ) ) {
							// Execute
							$this->core->add_creds(
								'tutor_lms_enroll_course',
								$user_id,
								$this->prefs['tutor_lms_enroll_course']['creds'][ $hook_index ],
								$this->prefs['tutor_lms_enroll_course']['log'][ $hook_index ],
								$course,
								$ref_type,
								$this->mycred_type
							);
						}
					}
				} elseif ( !$this->core->has_entry( 'tutor_lms_enroll_course' , null , $user_id , $ref_type, $this->mycred_type) ) {

						// Execute
						$this->core->add_creds(
							'tutor_lms_enroll_course',
							$user_id,
							$this->prefs['creds'],
							$this->prefs['log'],
							$course,
							$ref_type,
							$this->mycred_type
						);
				}   
			}       
		}

		/**
		 * Preference for tutor lms quiz Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;
			$select_parm = array(
				'div' => array(
					'class' => array(),
				),
				'input' => array(
					'class' => array(),
					'type' => array(),
					'name' => array(),
					'id' => array(),
					'size' => array(),
					'value' => array()
				),
				'select' => array(
					'name'  => array(),
					'class' => array(),
					'id' => array(),
				),
				'option' => array(
					'value' => array()
				),
			);
			?>
			<!-- for enroll course -->
			<div class="hook-instance">
				<div class="row">
					<div class="col-lg-12">
						<div class="hook-title">
							<h3><?php esc_html_e( 'General', 'mycred-toolkit' ); ?></h3>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name('creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
						</div>
					</div>
				</div>
			</div>
			<?php

			// enroll course    
			if (  count ( $prefs['tutor_lms_enroll_course']['creds'] ) > 0 ) {
				
				$hooks = $this->mycred_tutor_lms_enroll_arrange_data( $prefs['tutor_lms_enroll_course'] );

				$this->mycred_tutor_lms_specific_enroll( $hooks, $this );
			} else {

				$enroll_course = array(
					array(
						'creds'          => '10',
						'log'            => '%plural% for enroll specific course.',
						'select_enroll' => '0'
					)
				);
				$this->mycred_tutor_lms_specific_enroll( $enroll_course, $this );
			}
			?>
			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="form-group">
						<?php add_filter('mycred_tutor_lms_hook_limits', array( $this, 'custom_limit' ) ); ?>
						<label for="<?php echo esc_attr($this->field_id( 'limit' )); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
			

						 <?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ); ?>
						<p>This limit is valid for both General and Specific Hooks</p>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		* Sanitize Preferences
		*/
		public function sanitise_preferences( $data ) {

			$new_data = array();
				
			$new_data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : '';
			$new_data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : '';
			$new_data['mycred_check_specific'] = ( !empty( $data['mycred_check_specific'] ) ) ? sanitize_text_field( $data['mycred_check_specific'] ) : '';

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
					$new_data['limit'] = sanitize_text_field( $data['limit'] );
					$limit = $new_data['limit'];
				if ( $limit == '' ) {
$limit = 0;
				}

					$new_data['limit'] = $limit . '/' . $data['limit_by'];
					unset( $data['limit_by'] );
			}
		
			foreach ( $data['tutor_lms_enroll_course'] as $data_key => $data_value ) {

				foreach ( $data_value as $key => $value) {

					if ( $data_key == 'creds' ) {
						$new_data['tutor_lms_enroll_course'][ $data_key ][ $key ] = ( !empty( $value ) ) ? floatval( $value ) : 10;
					} else if ( $data_key == 'log' ) {
						$new_data['tutor_lms_enroll_course'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for enroll a course.';
					} else if ( $data_key == 'select_enroll' ) {
						$new_data['tutor_lms_enroll_course'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
					}
				}
			}

			return $new_data;
		}

		// enroll courses
		public function mycred_tutor_lms_enroll_course_name( $type, $attr ) {

			$hook_prefs_key = 'mycred_pref_hooks';

			if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
				$hook_prefs_key = 'mycred_pref_hooks_' . $type;
			}

			return "{$hook_prefs_key}[hook_prefs][tutor_lms_enroll_courses][tutor_lms_enroll_course][{$attr}][]";
		}

		public function mycred_tutor_lms_enroll_arrange_data( $data ) {

			$hook_data = array();
			
			foreach ( $data['select_enroll'] as $key => $value ) {
				
				$hook_data[ $key ]['creds']      = $data['creds'][ $key ];
				$hook_data[ $key ]['log']        = $data['log'][ $key ];
				$hook_data[ $key ]['select_enroll']    = $value;
			}
				return $hook_data;
		}

		public function mycred_tutor_lms_specific_enroll( $data, $obj ) {

			$prefs = $this->prefs;
			$args = array(
				'numberposts' => -1,
				'post_type'   => 'courses',
				'post_status'    => 'publish'
			);

			$courses = get_posts( $args );
			?>

			<div class="hook-instance" style="margin-bottom: 0px; padding-bottom: 14px;">
				 <div class="row">
					<div class="col-lg-12">
						<div class="hook-title">
							<h3><?php esc_html_e( 'Specific', 'mycred-toolkit' ); ?></h3>
						</div>
						<div>
							<label class="mycred_enroll_check" style=" display: block; margin: 14px 0px;">
							<input type="checkbox" name="<?php echo esc_attr( $this->field_name( 'mycred_check_specific' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'mycred_check_specific' ) ); ?>" value="1" 
																	<?php
																	if ( $prefs['mycred_check_specific'] == '1') {
										echo "checked = 'checked'";}
																	?>
							/>
							Enable Specfic</label>
						</div>
					</div>
				</div>

				<?php
				foreach ($data as $prefs) {
					?>
					<div class="custom-hook-instance">
					<div class="row ">
						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>"><?php echo esc_html( $obj->core->plural() ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_enroll_course_name($obj->mycred_type, 'creds' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr($obj->core->number( $prefs['creds'] )); ?>" class="form-control mycred-tutor_lms-creds" />
							</div>
						</div>
						
						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_enroll_course_name($obj->mycred_type, 'log' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control mycred-tutor_lms-log" />
								<span class="description"><?php echo wp_kses_post($obj->available_template_tags( array( 'general' ) )); ?></span>
							</div>
						</div>
					</div>

					<div class="row">

						<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
							<div class="form-group">
								<label><?php esc_html_e( 'Select' , 'mycred-toolkit' ); ?></label>
								<select class="mycred-tutor_lms-dropdown form-control" name="<?php echo esc_attr( $this->mycred_tutor_lms_enroll_course_name($obj->mycred_type, 'select_enroll') ); ?>"	>
									<option value="0">-----Select To Enroll-----</option>
										<?php
										foreach ($courses as $key => $value) { 
											?>
												 <option name="tutor_lms_enroll_course" value="<?php echo esc_attr( $value->ID ); ?>"
																										 <?php
												echo ( $prefs['select_enroll'] != $value->ID && in_array( $value->ID, $this->prefs['tutor_lms_enroll_course']['select_enroll'] ) ) ?  'disabled' : ''
																											?>
																											<?php echo selected($prefs['select_enroll'], $value->ID); ?>>
												<?php echo esc_html( $value->post_title ); ?></option>
														   <?php	
										}
										?>
								</select>
							</div>
						</div>

					</div>

						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group specific-hook-actions textright">
								<button class="button button-small mycred-add-tutor_lms-hook mycred-add-course-enroll" type="button">Add More</button>
								<button class="button button-small mycred-remove-tutor_lms-hook mycred-remove-course-enroll" type="button">Remove</button>
								</div>
							</div>
						</div>
					<!-- </div> -->
					</div>
					<?php
				}
				?>
			</div> 
			<?php
		}
	}
endif;