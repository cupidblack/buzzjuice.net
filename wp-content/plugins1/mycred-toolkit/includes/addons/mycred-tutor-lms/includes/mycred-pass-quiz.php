<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * mycred_tutor_lms_Addons_Module class
 * @since 0.1
 * @version 1.1.1
 */
if ( ! class_exists( 'mycred_tutor_lms_Geneal_Quiz_Hook_Class' ) ) :
	class mycred_tutor_lms_Geneal_Quiz_Hook_Class extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = mycred_TYPE_KEY ) {

			parent::__construct( 
			array(
				'id'       => 'tutor_lms_quiz_pass',
				'defaults' => array(
					'creds'      => 10,
					'log'        => '%plural% for passing any quiz.',
					'limit'   => 'x',
					'mycred_check_pass_quiz' => '1',
					'tutor_lms_quiz_pass' => array(
						'creds'   => array(),
						'log'     => array(),
						'select_course_pass' => array(),
						'select_quiz_pass' => array()
					),
				)
			),
			
			$hook_prefs, $type );
		}


		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'tutor_quiz/attempt_ended', array( $this, 'my_cred_quiz_pass_func' ) , 10 , 1);
		}

		/**
		 * Page Load
		 * @since 1.8
		 * @version 1.0
		 */
		public function my_cred_quiz_pass_func( $attempt ) {

			if ( !is_user_logged_in( ) ) {
return;
			}

			$user_id = get_current_user_id( );
		   
			if ( $this->core->exclude_user( $user_id ) ) {
return;
			}

			$attempt_data = tutor_utils()->get_attempt($attempt);       
			$quiz_id = $attempt_data->quiz_id;
			$course = tutor_utils()->get_course_by_quiz( $quiz_id );        
			$course_id = $course->ID;
			$attempt_id = $attempt;       
			$total_marks = $attempt_data->total_marks;          
			$marks_obtained = $attempt_data->earned_marks;         
			$attempt_info = unserialize( $attempt_data->attempt_info );         
			$passing_percentage = $attempt_info['passing_grade'];           
			$percentage = $marks_obtained / $total_marks;           
			$percent_obtained = $percentage * 100;
			$ref_type  = array(
				'ref_type' => 'post',
				'quiz_id' => $quiz_id
			);
			
			if ( $this->prefs['mycred_check_pass_quiz'] == '1' && in_array( $course_id, $this->prefs['tutor_lms_quiz_pass']['select_course_pass']) && ( in_array( 0, $this->prefs['tutor_lms_quiz_pass']['select_quiz_pass'] ) || in_array( $quiz_id, $this->prefs['tutor_lms_quiz_pass']['select_quiz_pass'] ) ) ) {

				$hook_index = array_search( $quiz_id, $this->prefs['tutor_lms_quiz_pass']['select_quiz_pass'] );
			
				if ( $hook_index === false ) {
					
					foreach ($this->prefs['tutor_lms_quiz_pass']['select_quiz_pass'] as $key => $value ) {
						
						if ( $this->prefs['tutor_lms_quiz_pass']['select_course_pass'][ $key ] == $course_id && $value == 0 ) {
							$hook_index = $key;
						}
					}
				}

				if ( !empty( $this->prefs['tutor_lms_quiz_pass']['creds'] ) && isset( $this->prefs['tutor_lms_quiz_pass']['creds'][ $hook_index ] ) && !empty( $this->prefs['tutor_lms_quiz_pass']['log'] ) && !empty( $this->prefs['tutor_lms_quiz_pass']['log'][ $hook_index ] ) ) {

					if ( !$this->core->has_entry( 'tutor_lms_quiz_pass' , null , $user_id , $ref_type, $this->mycred_type ) ) {
						if ( !$this->over_hook_limit('tutor_lms_quiz_pass', 'tutor_lms_quiz_pass', $user_id ) ) {

							if ( $percent_obtained >= $passing_percentage ) {
								// Execute
								$this->core->add_creds(
									'tutor_lms_quiz_pass',
									$user_id,
									$this->prefs['tutor_lms_quiz_pass']['creds'][ $hook_index ],
									$this->prefs['tutor_lms_quiz_pass']['log'][ $hook_index ],
									$attempt_id,
									$ref_type,
									$this->mycred_type
								);
							}
						}
					}
				}
			} elseif ( !$this->core->has_entry( 'tutor_lms_quiz_pass' , null , $user_id , $ref_type, $this->mycred_type ) ) {

				if ( !$this->over_hook_limit('tutor_lms_quiz_pass', 'tutor_lms_quiz_pass', $user_id ) ) {
					//Pass
					if ( $percent_obtained >= $passing_percentage ) {
						// Execute
						$this->core->add_creds(
							'tutor_lms_quiz_pass',
							$user_id,
							$this->prefs['creds'],
							$this->prefs['log'],
							$attempt_id,
							$ref_type,
							$this->mycred_type
						);
					}
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

			<!-- for passing quiz -->
			<div class="hook-instance">
				<div class="row">
					 <div class="col-lg-12">
						<div class="hook-title">
							<h3><?php esc_html_e( 'General', 'mycred-toolkit' ); ?></h3>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id('creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
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
					// passing quiz     
			if (  count ( $prefs['tutor_lms_quiz_pass']['select_course_pass'] ) > 0 ) {
						
				$hooks = $this->mycred_tutor_lms_quiz_pass_arrange_data( $prefs['tutor_lms_quiz_pass'] );

				$this->mycred_tutor_lms_specific_pass( $hooks, $this );
					
			} else {

				$quiz_pass = array(
					array(
						'creds'          => '10',
						'log'            => '%plural% for passing specific quiz.',
						'select_quiz_pass' => '0',
						'select_course_pass' => '0'
					)
				);
				$this->mycred_tutor_lms_specific_pass( $quiz_pass, $this );
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
	   */
		public function sanitise_preferences( $data ) {

			$new_data = array();

			$new_data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : '';
			$new_data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : '';
			$new_data['mycred_check_pass_quiz'] = ( !empty( $data['mycred_check_pass_quiz'] ) ) ? sanitize_text_field( $data['mycred_check_pass_quiz'] ) : '';



			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$new_data['limit'] = sanitize_text_field( $data['limit'] );
				$limit = sanitize_text_field($data['limit']);
				if ( $limit == '' ) {
$limit = 0;
				}
				$new_data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $new_data['limit_by'] );
			}

	
			foreach ( $data['tutor_lms_quiz_pass'] as $data_key => $data_value ) {

				foreach ( $data_value as $key => $value) {

					if ( $data_key == 'creds' ) {
						$new_data['tutor_lms_quiz_pass'][ $data_key ][ $key ] = ( !empty( $value ) ) ? floatval( $value ) : 10;
					} else if ( $data_key == 'log' ) {
						$new_data['tutor_lms_quiz_pass'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for passing a quiz.';
					} else if ( $data_key == 'select_course_pass' ) {
						$new_data['tutor_lms_quiz_pass'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
					} else if ( $data_key == 'select_quiz_pass' ) {
						$new_data['tutor_lms_quiz_pass'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '0';
					}
				}
			}

			return $new_data;
		}

		// passing quiz
		public function mycred_tutor_lms_quiz_pass_name( $type, $attr ) {

			$hook_prefs_key = 'mycred_pref_hooks';


			if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
				$hook_prefs_key = 'mycred_pref_hooks_' . $type;
			}

			return "{$hook_prefs_key}[hook_prefs][tutor_lms_quiz_pass][tutor_lms_quiz_pass][{$attr}][]";
		}

		public function mycred_tutor_lms_quiz_pass_arrange_data( $data ) {

			$hook_data = array();

			
			foreach ( $data['select_course_pass'] as $key => $value ) {
				
				$hook_data[ $key ]['creds']      = $data['creds'][ $key ];
				$hook_data[ $key ]['log']        = $data['log'][ $key ];
				$hook_data[ $key ]['select_quiz_pass']    = $data['select_quiz_pass'][ $key ] ?? '';
				$hook_data[ $key ]['select_course_pass']    = $value;
			}
				return $hook_data;
		}

		public function mycred_tutor_lms_specific_pass( $data, $obj ) {

			$prefs = $this->prefs;

			$course_args = array(
				'numberposts' => -1,
				'post_type'   => 'courses'
			);

			$courses = get_posts( $course_args );
			?>
			<div class="hook-instance" style="margin-bottom: 0px; padding-bottom: 14px;">
				 <div class="row">
					<div class="col-lg-12">
						<div class="hook-title">
							<h3><?php esc_html_e( 'Specific', 'mycred-toolkit' ); ?></h3>
						</div>
						<div>
							<label class="mycred_pass_quiz_check" style=" display: block; margin: 14px 0px;">
							<input type="checkbox" name="<?php echo esc_attr( $this->field_name( 'mycred_check_pass_quiz' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'mycred_check_pass_quiz' ) ); ?>" value="1" 
																	<?php
																	if ( $prefs['mycred_check_pass_quiz'] == '1') {
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
								<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_quiz_pass_name($obj->mycred_type, 'creds' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $obj->core->number( $prefs['creds'] ) ); ?>" class="form-control mycred-tutor_lms-creds" />
							</div>
						</div>
						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->mycred_tutor_lms_quiz_pass_name($obj->mycred_type, 'log' ) ); ?>" id="<?php echo esc_attr( $obj->field_id( 'log' ) ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control mycred-tutor_lms-log" />
								<span class="description"><?php echo wp_kses_post($obj->available_template_tags( array( 'general' ) )); ?></span>
							</div>
						</div>
					</div>
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Select' , 'mycred-toolkit' ); ?></label>
									<select class="mycred_tutor_lms_dropdown_pass form-control" name="<?php echo esc_attr( $this->mycred_tutor_lms_quiz_pass_name($obj->mycred_type, 'select_course_pass') ); ?>">
										<option value="0" disabled <?php echo selected($prefs['select_course_pass'], 0 ); ?>>-----Select Your Course-----</option>
											
											<?php	
												
											foreach ($courses as $key => $value) { 
												?>
												
													<option name="tutor_lms_quiz_pass" value="<?php echo esc_attr( $value->ID ); ?>"<?php echo selected($prefs['select_course_pass'], $value->ID); ?>>
													
													<?php echo esc_html( $value->post_title ); ?></option>

													<?php	
											}
											?>
									
									</select>
									
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

								<?php
								
								$course_id = intval( $prefs['select_course_pass'] );
								$post_type ='tutor_quiz';
								
								$contents = array();
								if ( ! empty( $course_id ) ) {
									$contents = mycred_tutor_lms_get_course_content( $post_type, $course_id );
								}

								?>
								<label class="pick-your-class"><?php esc_html_e( 'Select' , 'mycred-toolkit' ); ?></label>
								<select class="quiz_pass form-control" name="<?php echo esc_attr( $this->mycred_tutor_lms_quiz_pass_name($obj->mycred_type, 'select_quiz_pass' ) ); ?>">
										   <option value="0" <?php echo ( $prefs['select_quiz_pass'] != 0 && in_array( 0, $this->prefs['tutor_lms_quiz_pass']['select_quiz_pass'] ) ) ?  'disabled' : ''; ?><?php echo selected($prefs['select_quiz_pass'], 0); ?>>All Quiz</option>
											<?php
											foreach ($contents as $content => $value) {
												
											$quiz_title = $value->post_title;
											
											$quiz_id = $value->ID;
												
												if ( isset( $prefs['select_course_pass'] ) && isset( $prefs['select_quiz_pass'] ) ) {
													?>
											 
												<option value="<?php echo esc_attr( $value->ID ); ?>"
																		  <?php
													echo ( $prefs['select_quiz_pass'] != $value->ID && in_array( $value->ID, $this->prefs['tutor_lms_quiz_pass']['select_quiz_pass'] ) ) ?  'disabled' : ''
																			?>
																			<?php echo selected($prefs['select_quiz_pass'], $value->ID); ?>>
												
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