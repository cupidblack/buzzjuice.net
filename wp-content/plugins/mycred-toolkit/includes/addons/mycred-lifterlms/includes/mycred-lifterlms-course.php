<?php


/**
 * The class_exists() check is recommended, to prevent problems during upgrade
 * or when the Groups component is disabled
 */
if (! class_exists('myCRED_LifterLMS_Course')) :
	class myCRED_LifterLMS_Course extends myCRED_Hook {
		
		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			$defaults = array(
				'lifterlms_course_completed'    => array(
					'creds'  => 1,
					'log'    => '%plural% for completing a course',
					'limit'  => '0/x'
				),
				'lifterlms_course_track_completed'    => array(
					'creds'  => 0,
					'log'    => '%plural% for completing a course track',
					'limit'  => '0/x'
				),
				'llms_user_enrolled_in_course'    => array(
					'creds'  => 0,
					'log'    => '%plural% for enrolling in a course',
					'limit'  => '0/x'
				)
			);

			parent::__construct(array(
				'id'       => 'mycred_lifterlms_course',
				'defaults' => $defaults
			), $hook_prefs, $type);
		}

		/**
		 * Hook into WordPress
		 */
		public function run() {
			
			if ($this->prefs['lifterlms_course_completed']['creds'] !== 0) {
				add_action( 'lifterlms_course_completed', array( $this, 'course_completed' ), 100, 2);
			}

			if ($this->prefs['lifterlms_course_track_completed']['creds'] !== 0) {
				add_action( 'lifterlms_course_track_completed', array( $this, 'course_track_completed' ), 100, 2);
			}

			if ($this->prefs['llms_user_enrolled_in_course']['creds'] !== 0) {
				add_action('llms_user_enrolled_in_course', array( $this, 'user_enrolled_in_course' ), 100, 2 );
			}
		}



		/**
		 * Runs when a student completes a course
		 *
		 * @since 1.0
		 *
		 * @param $student_id
		 * @param $course_id
		 */
		public function course_completed( $student_id, $course_id ) {
			// Check for exclusion
			if ($this->core->exclude_user($student_id)) {
return;
			}

			$refrence = 'lifterlms_course_completed';

			$data = array( 'course' => $course_id );

			// Enforce limit and make sure users only get points once per unique course
			if (! $this->over_hook_limit( $refrence, $refrence, $student_id) && ! $this->core->has_entry($refrence, $student_id, $course_id, $data, $this->mycred_type)) {
				$this->core->add_creds(
					$refrence,
					$student_id,
					$this->prefs[ $refrence ]['creds'],
					$this->prefs[ $refrence ]['log'],
					$course_id,
					$data
				);
			}
		}

		/**
		 * Runs when a student completes a track
		 *
		 * @since 1.0
		 *
		 * @param $student_id
		 * @param $course_track_id
		 */
		public function course_track_completed( $student_id, $course_track_id ) {
			// Check for exclusion
			if ($this->core->exclude_user($student_id)) {
return;
			}

			$refrence = 'lifterlms_course_track_completed';

			$data = array( 'track' => $course_track_id );

			// Enforce limit and make sure users only get points once per unique course
			if (! $this->over_hook_limit($refrence, $refrence, $student_id) && ! $this->core->has_entry($refrence, $student_id, $course_track_id, $data, $this->mycred_type)) {
				$this->core->add_creds(
					$refrence,
					$student_id,
					$this->prefs[ $refrence ]['creds'],
					$this->prefs[ $refrence ]['log'],
					$course_track_id,
					$data
				);
			}
		}



		/**
		 * Runs when a student is enrolled in a course
		 *
		 * @since 1.0
		 *
		 * @param $student_id
		 * @param $product_id
		 */
		public function user_enrolled_in_course( $student_id, $product_id ) {
			// Check for exclusion
			if ($this->core->exclude_user($student_id)) {
return;
			}

			$refrence = 'llms_user_enrolled_in_course';

			$data = array( 'product' => $product_id );

			// Enforce limit and make sure users only get points once per unique course
			if (! $this->over_hook_limit($refrence, $refrence, $student_id) && ! $this->core->has_entry( $refrence, $student_id, $product_id, $data, $this->mycred_type)) {
				$this->core->add_creds(
					$refrence,
					$student_id,
					$this->prefs[ $refrence ]['creds'],
					$this->prefs[ $refrence ]['log'],
					$product_id,
					$data
				);
			}
		}

		/**
		* Add Settings
		*/
		public function preferences() {
			// Our settings are available under $this->prefs
			$prefs = $this->prefs; ?>

			<div class="hook-instance">
				<h3><?php esc_html_e('Student completes a course', 'mycred-toolkit'); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_course_completed' => 'creds' ))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( array( 'lifterlms_course_completed' => 'creds' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'lifterlms_course_completed' => 'creds' ))); ?>" value="<?php echo esc_attr($this->core->number($prefs['lifterlms_course_completed']['creds'])); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id(array( 'lifterlms_course_completed' => 'limit' ))); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
							<?php
							echo wp_kses($this->hook_limit_setting($this->field_name(array( 'lifterlms_course_completed' => 'limit' )), $this->field_id(array( 'lifterlms_course_completed' => 'limit' )), $prefs['lifterlms_course_completed']['limit']),
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
					<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_course_completed' => 'log' ))); ?>"><?php esc_html_e('Log template', 'mycred-toolkit'); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name(array( 'lifterlms_course_completed' => 'log' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'lifterlms_course_completed' => 'log' ))); ?>" placeholder="<?php esc_html_e('required', 'mycred-toolkit'); ?>" value="<?php echo esc_attr($prefs['lifterlms_course_completed']['log']); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags(array( 'general' ))); ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="hook-instance">
				<h3><?php esc_html_e('Student comepletes a course track', 'mycred-toolkit'); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id(array( 'lifterlms_course_track_completed' => 'creds' ))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( array( 'lifterlms_course_track_completed' => 'creds' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'lifterlms_course_track_completed' => 'creds' ))); ?>" value="<?php echo esc_attr($this->core->number($prefs['lifterlms_course_track_completed']['creds'])); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id(array( 'lifterlms_course_track_completed' => 'limit' ))); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit'); ?></label>
							<?php
							echo wp_kses($this->hook_limit_setting($this->field_name(array( 'lifterlms_course_track_completed' => 'limit' )), $this->field_id(array( 'lifterlms_course_track_completed' => 'limit' )), $prefs['lifterlms_course_track_completed']['limit']),
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
						)
							;
							?>
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_course_track_completed' => 'log' ))); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit'); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name(array( 'lifterlms_course_track_completed' => 'log' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'lifterlms_course_track_completed' => 'log' ))); ?>" placeholder="<?php esc_html_e('required', 'mycred-toolkit'); ?>" value="<?php echo esc_attr($prefs['lifterlms_course_track_completed']['log']); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags(array( 'general' ))); ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="hook-instance">
				<h3><?php esc_html_e('Student enrolls in a course', 'mycred-toolkit'); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id(array( 'llms_user_enrolled_in_course' => 'creds' ))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( array( 'llms_user_enrolled_in_course' => 'creds' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'llms_user_enrolled_in_course' => 'creds' ))); ?>" value="<?php echo esc_attr($this->core->number($prefs['llms_user_enrolled_in_course']['creds'])); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'llms_user_enrolled_in_course' => 'limit' ))); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php
							echo wp_kses($this->hook_limit_setting($this->field_name( array( 'llms_user_enrolled_in_course' => 'limit' )), $this->field_id( array( 'llms_user_enrolled_in_course' => 'limit' )), $prefs['llms_user_enrolled_in_course']['limit']),
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
						)
							;
							?>
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id(array( 'llms_user_enrolled_in_course' => 'log' ))); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( array( 'llms_user_enrolled_in_course' => 'log' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'llms_user_enrolled_in_course' => 'log' ))); ?>" placeholder="<?php esc_html_e('required', 'mycred-toolkit'); ?>" value="<?php echo esc_attr( $prefs['llms_user_enrolled_in_course']['log']); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags(array( 'general' ))); ?></span>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Sanitize Preferences
		 */
		public function sanitise_preferences( $data ) {

			if (isset( $data['lifterlms_course_completed']['limit']) && isset($data['lifterlms_course_completed']['limit_by'])) {
				$limit = sanitize_text_field($data['lifterlms_course_completed']['limit']);
				if ($limit == '') {
$limit = 0;
				}
				$data['lifterlms_course_completed']['limit'] = $limit . '/' . $data['lifterlms_course_completed']['limit_by'];
				unset($data['lifterlms_course_completed']['limit_by']);
			}

			if (isset($data['lifterlms_course_track_completed']['limit']) && isset($data['lifterlms_course_track_completed']['limit_by'])) {
				$limit = sanitize_text_field($data['lifterlms_course_track_completed']['limit']);
				if ($limit == '') {
$limit = 0;
				}
				$data['lifterlms_course_track_completed']['limit'] = $limit . '/' . $data['lifterlms_course_track_completed']['limit_by'];
				unset($data['lifterlms_course_track_completed']['limit_by']);
			}

			if (isset($data['llms_user_enrolled_in_course']['limit']) && isset($data['llms_user_enrolled_in_course']['limit_by'])) {
				$limit = sanitize_text_field($data['llms_user_enrolled_in_course']['limit']);
				if ($limit == '') {
$limit = 0;
				}
				$data['llms_user_enrolled_in_course']['limit'] = $limit . '/' . $data['llms_user_enrolled_in_course']['limit_by'];
				unset($data['llms_user_enrolled_in_course']['limit_by']);
			}

			return $data;
		}
	}
endif;

?>