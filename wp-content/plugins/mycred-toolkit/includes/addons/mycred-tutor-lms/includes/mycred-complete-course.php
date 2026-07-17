<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}
	
	// Require file containing the class or
	// define the class in this function
	
if ( ! class_exists( 'mycred_tutor_lms_Specific_Course_Hook_Class' ) ) :
	class mycred_tutor_lms_Specific_Course_Hook_Class extends myCRED_Hook {
	   
		 /**
		 * Construct
		 * Used to set the hook id and default settings.
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'tutor_lms_complete_courses',
				'defaults' => array(
					'creds'      => 10,
					'log'        => '%plural% for completing any course.',
					'limit'   => 'x',
					'mycred_check_complete' => '1',
					'tutor_lms_complete_course' => array(
						'creds'   => array(),
						'log'     => array(),
						'select_course' => array()
					),
				)
			), $hook_prefs, $type );
		}

		/**
		 * Run
		 * Fires by myCRED when the hook is loaded.
		 * Used to hook into any instance needed for this hook
		 * to work.
		 */
		public function run() {
		   
			add_action( 'tutor_course_complete_after', array( $this, 'my_cred_complete_generl_course_func' ) , 10 , 1); 
		}
		
		/**
		* tutor_lms specific course completion
		**/
		public function my_cred_complete_generl_course_func( $course_id ) {
			
			// Check if user is excluded (required)
			
			if (!is_user_logged_in( )) {
return;
			}
			
			$user_id=get_current_user_id( );
			
			if ( $this->core->exclude_user( $user_id ) ) {
return;
			}

			$ref_type  = array(
				'ref_type' => 'post',
				'course_id' => $course_id
			);

			if ( !$this->over_hook_limit( 'tutor_lms_complete_course', 'tutor_lms_complete_course', $user_id ) ) {
	
				if ( $this->prefs['mycred_check_complete'] == '1' && ! empty( $this->prefs['tutor_lms_complete_course']['select_course'] ) && in_array( $course_id, $this->prefs['tutor_lms_complete_course']['select_course'] ) ) {
					
					$hook_index = array_search( $course_id, $this->prefs['tutor_lms_complete_course']['select_course'] );

					if ( ! empty( $this->prefs['tutor_lms_complete_course']['creds'] ) && isset( $this->prefs['tutor_lms_complete_course']['creds'][ $hook_index ] ) && !empty( $this->prefs['tutor_lms_complete_course']['log'] ) && !empty( $this->prefs['tutor_lms_complete_course']['log'][ $hook_index ] ) ) {
						// Make sure this is a unique event
						if ( !$this->core->has_entry( 'tutor_lms_complete_course' , null , $user_id , $ref_type, $this->mycred_type ) ) {
							// Execute
							$this->core->add_creds(
								'tutor_lms_complete_course',
								$user_id,
								$this->prefs['tutor_lms_complete_course']['creds'][ $hook_index ],
								$this->prefs['tutor_lms_complete_course']['log'][ $hook_index ],
								$course_id,
								$ref_type,
								$this->mycred_type
							);
						}
					}
				} else {
					// Make sure this is a unique event
					if ( ! $this->core->has_entry( 'tutor_lms_complete_course' , null , $user_id , $ref_type, $this->mycred_type ) ) {
						// Execute
						$this->core->add_creds(
							'tutor_lms_complete_course',
							$user_id,
							$this->prefs['creds'],
							$this->prefs['log'],
							$course_id,
							$ref_type,
							$this->mycred_type
						);
					}
				}
			}
		}
		
		/**
		 * Hook Settings
		 * Needs to be set if the hook has settings.
		 */
		public function preferences() {

			// Our settings are available under $this->prefs
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
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
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
			// complete course
			if (  count ( $prefs['tutor_lms_complete_course']['creds'] ) > 0 ) {
				
				$hooks = $this->mycred_tutor_lms_course_complete_arrange_data( $prefs['tutor_lms_complete_course'] );

				$this->mycred_tutor_lms_specific_course_complete( $hooks, $this );
			} else {

				$course_complete = array(
					array(
						'creds'          => '10',
						'log'            => '%plural% for completing specific course.',
						'select_course' => '0'
					)
				);
				$this->mycred_tutor_lms_specific_course_complete( $course_complete, $this );
			}
			?>

			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="form-group">
						<?php add_filter('mycred_tutor_lms_hook_limits', array( $this, 'custom_limit' )); ?>
						 <label for="<?php echo $this->field_id( 'limit' ); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
					

						 <?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ); ?>
						<p>This limit is valid for both General and Specific Hooks</p>
					</div>
				</div>
			</div>
			<?php
		}
			
		/**
		 * Sanitize Preferences
		 * If the hook has settings, this method must be used
		 * to sanitize / parsing of settings.
		 */
		public function sanitise_preferences( $data ) {

			$new_data = array();
				
			$new_data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : '';
			$new_data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : '';
			$new_data['mycred_check_complete'] = ( !empty( $data['mycred_check_complete'] ) ) ? sanitize_text_field( $data['mycred_check_complete'] ) : '';

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
					$new_data['limit'] = sanitize_text_field( $data['limit'] );
					$limit = $new_data['limit'];
				if ( $limit == '' ) {
$limit = 0;
				}

					$new_data['limit'] = $limit . '/' . $data['limit_by'];
					unset( $data['limit_by'] );
			}

			foreach ( $data['tutor_lms_complete_course'] as $data_key => $data_value ) {

				foreach ( $data_value as $key => $value) {

					if ( $data_key == 'creds' ) {
						$new_data['tutor_lms_complete_course'][ $data_key ][ $key ] = ( !empty( $value ) ) ? floatval( $value ) : 10;
					} else if ( $data_key == 'log' ) {
						$new_data['tutor_lms_complete_course'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for completing a course.';
					} else if ( $data_key == 'select_course' ) {
						$new_data['tutor_lms_complete_course'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
					}
				}
			}

				return $new_data;
		}

		// complete course
		public function mycred_tutor_lms_field_name_course( $type, $attr ) {

			$hook_prefs_key = 'mycred_pref_hooks';

			if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
				$hook_prefs_key = 'mycred_pref_hooks_' . $type;
			}

			return "{$hook_prefs_key}[hook_prefs][tutor_lms_complete_courses][tutor_lms_complete_course][{$attr}][]";
		}


		public function mycred_tutor_lms_course_complete_arrange_data( $data ) {

			$hook_data = array();

			foreach ( $data['select_course'] as $key => $value ) {
				
				$hook_data[ $key ]['creds']      = $data['creds'][ $key ];
				$hook_data[ $key ]['log']        = $data['log'][ $key ];
				$hook_data[ $key ]['select_course']    = $value;
			}

			return $hook_data;
		}

		public function mycred_tutor_lms_specific_course_complete( $data, $obj ) {

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
							<label class="mycred_complete_check" style=" display: block; margin: 14px 0px;">
							<input type="checkbox" name="<?php echo esc_attr( $this->field_name( 'mycred_check_complete' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'mycred_check_complete' ) ); ?>" value="1" 
																	<?php
																	if ( $prefs['mycred_check_complete'] == '1') {
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
				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>"><?php echo esc_html( $obj->core->plural() ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_course($obj->mycred_type, 'creds' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $obj->core->number( $prefs['creds'] ) ); ?>" class="form-control mycred-tutor_lms-creds" />
						</div>
					</div>
					<!-- <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label><?php esc_html_e( 'Select' , 'mycred-toolkit' ); ?></label>
							<select class="mycred-tutor_lms-dropdown form-control" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_course( $obj->mycred_type, 'select_course' ) ); ?>">
								<option value="0" >-----Select Your Course-----</option>
									<?php
									foreach ($courses as $key => $value) {                                   
										?>
											<option name="tutor_lms_comple/te_course" value="<?php echo esc_attr( $value->ID ); ?>"
																										<?php
											echo ( $prefs['select_course'] != $value->ID && in_array( $value->ID, $this->prefs['tutor_lms_complete_course']['select_course'] ) ) ?  'disabled' : ''
																										?>
																										<?php echo selected($prefs['select_course'], $value->ID); ?>>
											<?php echo esc_html( $value->post_title ); ?></option>
														<?php	
									}
									?>
							</select>
						</div>
					</div>  -->
					
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_course($obj->mycred_type, 'log' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control mycred-tutor_lms-log" />
							<span class="description"><?php echo wp_kses_post($obj->available_template_tags( array( 'general' ) )); ?></span>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
						<div class="form-group">
							<label><?php esc_html_e( 'Select' , 'mycred-toolkit' ); ?></label>
							<select class="mycred-tutor_lms-dropdown form-control" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_course( $obj->mycred_type, 'select_course' ) ); ?>">
								<option value="0" >-----Select Your Course-----</option>
									<?php
									foreach ($courses as $key => $value) {                                   
										?>
											<option name="tutor_lms_comple/te_course" value="<?php echo esc_attr( $value->ID ); ?>"
																										<?php
											echo ( $prefs['select_course'] != $value->ID && in_array( $value->ID, $this->prefs['tutor_lms_complete_course']['select_course'] ) ) ?  'disabled' : ''
																										?>
																										<?php echo selected($prefs['select_course'], $value->ID); ?>>
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