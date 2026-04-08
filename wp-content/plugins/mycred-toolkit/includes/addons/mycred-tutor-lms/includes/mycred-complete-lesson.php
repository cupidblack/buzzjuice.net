<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}
	
	// Require file containing the class or
	// define the class in this function
	
if ( ! class_exists( 'mycred_tutor_lms_Specific_Lesson_Hook_Class' ) ) :
	class mycred_tutor_lms_Specific_Lesson_Hook_Class extends myCRED_Hook {
	   
		 /**
		 * Construct
		 * Used to set the hook id and default settings.
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'tutor_lms_complete_lesson',
				'defaults' => array(
					'creds'      => 10,
					'log'        => '%plural% for completing any lesson.',
					'limit'   => 'x',
					'mycred_check_lesson' => '1',
					'tutor_lms_complete_lesson' => array(
						'creds'   => array(),
						'log'     => array(),
						'select_course' => array(),
						'select_lesson' => array()
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
		   
		   add_action( 'tutor_lesson_completed_after', array( $this, 'my_cred_specific_lesson_func' ) , 10 , 1);
		}
		
		/**
		* tutor_lms specific lesson completion
		**/
		public function my_cred_specific_lesson_func( $lesson ) {
			
			$course_id = tutor_utils()->get_course_id_by_lesson( $lesson );
			// Check if user is excluded (required)
			
			if (!is_user_logged_in( )) {
return;
			}
			
			$user_id = get_current_user_id( );
			
			if ( $this->core->exclude_user( $user_id ) ) {
return;
			}

			$ref_type  = array(
				'ref_type' => 'post',
				'lesson' => $lesson
			);

			if ( !$this->over_hook_limit('tutor_lms_complete_lesson', 'tutor_lms_complete_lesson', $user_id ) ) {

				if ( $this->prefs['mycred_check_lesson'] == '1' && in_array( $course_id, $this->prefs['tutor_lms_complete_lesson']['select_course'] ) && ( in_array(0, $this->prefs['tutor_lms_complete_lesson']['select_lesson'] ) || in_array( $lesson, $this->prefs['tutor_lms_complete_lesson']['select_lesson'] ) ) ) {

					$hook_index = array_search( $lesson, $this->prefs['tutor_lms_complete_lesson']['select_lesson'] );

					if ( $hook_index === false ) {

						foreach ( $this->prefs['tutor_lms_complete_lesson']['select_lesson'] as $key => $value ) {
							
							if ( $this->prefs['tutor_lms_complete_lesson']['select_course'][ $key ] == $course_id && $value == 0 ) {
								$hook_index = $key;
							}

						}

					}

					if ( ! empty( $this->prefs['tutor_lms_complete_lesson']['creds'] ) && isset( $this->prefs['tutor_lms_complete_lesson']['creds'][ $hook_index ] ) && 
						!empty( $this->prefs['tutor_lms_complete_lesson']['log'] ) && !empty( $this->prefs['tutor_lms_complete_lesson']['log'][ $hook_index ] ) ) {
						// Make sure this is a unique event
						if ( !$this->core->has_entry( 'tutor_lms_complete_lesson' , null , $user_id , $ref_type, $this->mycred_type ) ) {
							// Execute
							$this->core->add_creds(
								'tutor_lms_complete_lesson',
								$user_id,
								$this->prefs['tutor_lms_complete_lesson']['creds'][ $hook_index ],
								$this->prefs['tutor_lms_complete_lesson']['log'][ $hook_index ],
								$lesson,
								$ref_type,
								$this->mycred_type
							);
						}
					}
				} else {
					// Make sure this is a unique event
					if ( $this->core->has_entry( 'tutor_lms_complete_lesson' , null , $user_id , $ref_type, $this->mycred_type) ) {
return;
					}

						// Execute
						$this->core->add_creds(
							'tutor_lms_complete_lesson',
							$user_id,
							$this->prefs['creds'],
							$this->prefs['log'],
							$lesson,
							$ref_type,
							$this->mycred_type
						);  
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

			<!-- for completing course -->
			<div class="hook-instance">
				<div class="row">
					<div class="col-lg-12">
						<h3><?php esc_html_e( 'General', 'mycred-toolkit' ); ?></h3>
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
							<input type="text" name="<?php echo esc_attr( $this->field_name('log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags( array( 'general' ) )); ?></span>
						</div>
					</div>
				</div>
			</div>
			<?php
			// complete course
			if (  count ( $prefs['tutor_lms_complete_lesson']['select_course'] ) > 0 ) {
				
				$hooks = $this->mycred_tutor_lms_lesson_complete_arrange_data( $prefs['tutor_lms_complete_lesson'] );

				$this->mycred_tutor_lms_specific_lesson_complete( $hooks, $this );
			} else {

				$lesson_complete = array(
					array(
						'creds'          => '10',
						'log'            => '%plural% for completing specific lesson.',
						'select_course' => '0',
						'select_lesson' => '0'
					)
				);
				$this->mycred_tutor_lms_specific_lesson_complete( $lesson_complete, $this );
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
			$new_data['mycred_check_lesson'] = ( !empty( $data['mycred_check_lesson'] ) ) ? sanitize_text_field( $data['mycred_check_lesson'] ) : '';


			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
					$new_data['limit'] = sanitize_text_field( $data['limit'] );
					$limit = $new_data['limit'];
				if ( $limit == '' ) {
$limit = 0;
				}

					$new_data['limit'] = $limit . '/' . $data['limit_by'];
					unset( $data['limit_by'] );
			}

			foreach ( $data['tutor_lms_complete_lesson'] as $data_key => $data_value ) {

				foreach ( $data_value as $key => $value) {

					if ( $data_key == 'creds' ) {
						$new_data['tutor_lms_complete_lesson'][ $data_key ][ $key ] = ( !empty( $value ) ) ? floatval( $value ) : 10;
					} else if ( $data_key == 'log' ) {
						$new_data['tutor_lms_complete_lesson'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for completing a lesson.';
					} else if ( $data_key == 'select_course' ) {
						$new_data['tutor_lms_complete_lesson'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
					} else if ( $data_key == 'select_lesson' ) {
						$new_data['tutor_lms_complete_lesson'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
					}
				}
			} 

				return $new_data;
		}

		public function mycred_tutor_lms_field_name_lesson( $type, $attr ) {

			$hook_prefs_key = 'mycred_pref_hooks';

			if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
				$hook_prefs_key = 'mycred_pref_hooks_' . $type;
			}

			return "{$hook_prefs_key}[hook_prefs][tutor_lms_complete_lesson][tutor_lms_complete_lesson][{$attr}][]";
		}


		public function mycred_tutor_lms_lesson_complete_arrange_data( $data ) {

			$hook_data = array();

			foreach ( $data['select_course'] as $key => $value ) {
				
				$hook_data[ $key ]['creds']           = $data['creds'][ $key ];
				$hook_data[ $key ]['log']             = $data['log'][ $key ];
				$hook_data[ $key ]['select_lesson']   = $data['select_lesson'][ $key ];
				$hook_data[ $key ]['select_course']   = $value;
			}

			return $hook_data;
		}


		public function mycred_tutor_lms_specific_lesson_complete( $data, $obj ) {

			$prefs = $this->prefs;
			$course_args = array(
				'numberposts' => -1,
				'post_type'   => 'courses'
			);

			$courses = get_posts( $course_args );
			?>
			<div class="hook-instance">
				 <div class="row">
					<div class="col-lg-12">
						<div class="hook-title">
							<h3><?php esc_html_e( 'Specific', 'mycred-toolkit' ); ?></h3>
						</div> 
						<div>
							<label class="mycred_lesson_check" style=" display: block; margin: 14px 0px;">
							<input type="checkbox" name="<?php echo esc_attr( $this->field_name( 'mycred_check_lesson' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'mycred_check_lesson' ) ); ?>" value="1" 
																	<?php
																	if ( $prefs['mycred_check_lesson'] == '1') {
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
					<div class="row"  style="margin-bottom: 0px; padding-bottom: 14px;">
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>"><?php echo esc_html( $obj->core->plural() ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_lesson($obj->mycred_type, 'creds' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $obj->core->number( $prefs['creds'] ) ); ?>" class="form-control mycred-tutor_lms-creds" />
							</div>
						</div>	
							
						<div class="col-lg-6 col-md-8 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_lesson($obj->mycred_type, 'log' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control mycred-tutor_lms-log" />
								<span class="description"><?php echo wp_kses_post($obj->available_template_tags( array( 'general' ) )); ?></span>
							</div>
						</div>
					</div>

					<div class="row">

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label><?php esc_html_e( 'Select' , 'mycred-toolkit' ); ?></label>
								<select class="mycred-tutor_lms-dropdown_course form-control" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_lesson($obj->mycred_type, 'select_course') ); ?>">
									<option value="0" disabled <?php echo selected($prefs['select_course'], 0); ?>>-----Select Your Course-----</option>
										<?php
										foreach ($courses as $key => $value) { 
											?>
												<option name="tutor_lms_complete_lesson" value="<?php echo esc_attr( $value->ID ); ?>"<?php echo selected($prefs['select_course'], $value->ID); ?>>
												<?php echo esc_html( $value->post_title ); ?></option>
														   <?php	
										}
										?>
								</select>

							</div>

						</div>


						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

							<?php 
								
								$course_id = intval( $prefs['select_course'] );
								$post_type = 'lesson';

								$course_contents = array();
							if ( ! empty ( $course_id ) ) {
								$course_contents = mycred_tutor_lms_get_course_content( $post_type, $course_id );
							}
								
							?>
								<label class="pick-your-class"><?php esc_html_e( 'Select' , 'mycred-toolkit' ); ?></label>
								<select class="mycred-tutor_lms-dropdown_lesson form-control" name="<?php echo esc_attr( $this->mycred_tutor_lms_field_name_lesson($obj->mycred_type, 'select_lesson') ); ?>">
									<option value="0" <?php echo ( $prefs['select_lesson'] != 0 && in_array( 0, $this->prefs['tutor_lms_complete_lesson']['select_lesson'] ) ) ?  'disabled' : ''; ?> <?php echo selected($prefs['select_lesson'], 0); ?>>All Lesson</option>
									<?php 
									foreach ($course_contents as $content => $value) {
																			
										$lesson_title = $value->post_title;
											
										$lesson_id = $value->ID;
												
										if ( isset( $prefs['select_course'] ) && isset( $prefs['select_lesson'] ) ) {
											?>
											
												<option value="<?php echo esc_attr( $value->ID ); ?>"
																		  <?php
												echo ( $prefs['select_lesson'] != $value->ID && in_array( $value->ID, $this->prefs['tutor_lms_complete_lesson']['select_lesson'] ) ) ?  'disabled' : ''
																			?>
																		  <?php echo selected($prefs['select_lesson'], $value->ID); ?>>
												
												<?php echo esc_html( $value->post_title ); ?></option>
											
												<?php
										}
									}
									?>
																		</select>

						</div>



					</div>

						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group specific-hook-actions textright">
								<button class="button button-small mycred-add-tutor_lms-hook" type="button">Add More</button>
								<button class="button button-small mycred-remove-tutor_lms-hook" type="button">Remove</button>
								</div>
							</div>
						</div>
					</div>

				<?php } ?>
			</div> 
			<?php
		}
	}
endif;