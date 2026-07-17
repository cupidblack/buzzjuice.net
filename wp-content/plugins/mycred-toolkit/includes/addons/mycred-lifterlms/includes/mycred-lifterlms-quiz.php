<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * The class_exists() check is recommended, to prevent problems during upgrade
 * or when the Groups component is disabled
 */
if ( ! class_exists( 'myCRED_LifterLMS_Quiz' ) ) :
	class myCRED_LifterLMS_Quiz extends myCRED_Hook {
		
		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			$defaults = array(
				'lifterlms_quiz_passed'    => array(
					'creds'  => 1,
					'log'    => '%plural% for passing a quiz',
					'limit'  => '0/x'
				),
				'lifterlms_quiz_failed'    => array(
					'creds'  => 0,
					'log'    => '%plural% for failing a quiz',
					'limit'  => '0/x'
				),
				'lifterlms_quiz_completed'    => array(
					'creds'  => 0,
					'log'    => '%plural% for completing a quiz',
					'limit'  => '0/x'
				)
			);

			parent::__construct(array(
				'id'       => 'mycred_lifterlms_quiz',
				'defaults' => $defaults
			), $hook_prefs, $type);
		}

		/**
		 * Hook into WordPress
		 */
		public function run() {
			
			if ($this->prefs['lifterlms_quiz_passed']['creds'] !== 0) {
				add_action('lifterlms_quiz_passed', array( $this, 'quiz_passed' ), 100, 3);
			}

			if ($this->prefs['lifterlms_quiz_failed']['creds'] !== 0) {
				add_action('lifterlms_quiz_failed', array( $this, 'quiz_failed' ), 100, 3);
			}

			if ($this->prefs['lifterlms_quiz_completed']['creds'] !== 0) {
				add_action('lifterlms_quiz_completed', array( $this, 'quiz_completed' ), 100, 3);
			}
		}

		/**
		 * Runs when a student passes a quiz
		 *
		 * @since 1.0
		 *
		 * @param $student_id
		 * @param $quiz_id
		 * @param $quiz_attempt
		 */
		public function quiz_passed( $student_id, $quiz_id, $quiz_attempt ) {
			// Check for exclusion
			if ($this->core->exclude_user($student_id)) {
return;
			}

			$refrence = 'lifterlms_quiz_passed';

			$data = array( 'quiz' => $quiz_id );

			// Enforce limit and make sure users only get points once per unique quiz
			if (! $this->over_hook_limit( $refrence, $refrence, $student_id ) && ! $this->core->has_entry($refrence, $student_id, $quiz_id, $data, $this->mycred_type)) {
				$this->core->add_creds(
					$refrence,
					$student_id,
					$this->prefs[ $refrence ]['creds'],
					$this->prefs[ $refrence ]['log'],
					$quiz_id,
					$data
				);
			}
		}

		/**
		 * Runs when a student fails a quiz
		 *
		 * @since 1.0
		 *
		 * @param $student_id
		 * @param $quiz_id
		 * @param $quiz_attempt
		 */
		public function quiz_failed( $student_id, $quiz_id, $quiz_attempt ) {
			// Check for exclusion
			if ($this->core->exclude_user($student_id )) {
return;
			}

			$refrence = 'lifterlms_quiz_failed';

			$data = array( 'quiz' => $quiz_id );

			// Enforce limit and make sure users only get points once per unique quiz
			if (! $this->over_hook_limit($refrence, $refrence, $student_id ) && ! $this->core->has_entry($refrence, $student_id, $quiz_id, $data, $this->mycred_type)) {
				$this->core->add_creds(
					$refrence,
					$student_id,
					$this->prefs[ $refrence ]['creds'],
					$this->prefs[ $refrence ]['log'],
					$quiz_id,
					$data
				);
			}
		}

		/**
		 * Runs when a student completes a quiz
		 *
		 * @since 1.0
		 *
		 * @param $student_id
		 * @param $quiz_id
		 * @param $quiz_attempt
		 */
		public function quiz_completed( $student_id, $quiz_id, $quiz_attempt ) {
			// Check for exclusion
			if ($this->core->exclude_user($student_id)) {
return;
			}

			$refrence = 'lifterlms_quiz_completed';

			$data = array( 'quiz' => $quiz_id );

			// Enforce limit and make sure users only get points once per unique quiz
			if (! $this->over_hook_limit($refrence, $refrence, $student_id) && ! $this->core->has_entry($refrence, $student_id, $quiz_id, $data, $this->mycred_type)) {
				$this->core->add_creds(
					$refrence,
					$student_id,
					$this->prefs[ $refrence ]['creds'],
					$this->prefs[ $refrence ]['log'],
					$quiz_id,
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
				<h3><?php esc_html_e('Student passes a quiz', 'mycred-toolkit'); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_passed' => 'creds' ))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( array( 'lifterlms_quiz_passed' => 'creds' ))); ?>" id="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_passed' => 'creds' ))); ?>" value="<?php echo esc_attr($this->core->number($prefs['lifterlms_quiz_passed']['creds'])); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_passed' => 'limit' ))); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
							<?php
							echo wp_kses($this->hook_limit_setting($this->field_name( array( 'lifterlms_quiz_passed' => 'limit' )), $this->field_id( array( 'lifterlms_quiz_passed' => 'limit' )), $prefs['lifterlms_quiz_passed']['limit'] ),
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
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_passed' => 'log' ))); ?>"><?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( array( 'lifterlms_quiz_passed' => 'log' ))); ?>" id="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_passed' => 'log' ))); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['lifterlms_quiz_passed']['log']); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags(array( 'general' ))); ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="hook-instance">
				<h3><?php esc_html_e('Student fails a quiz', 'mycred-toolkit'); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_failed' => 'creds' ))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name(array( 'lifterlms_quiz_failed' => 'creds' ))); ?>" id="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_failed' => 'creds' ) )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['lifterlms_quiz_failed']['creds'])); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_failed' => 'limit' ))); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php
							echo wp_kses($this->hook_limit_setting($this->field_name( array( 'lifterlms_quiz_failed' => 'limit' )), $this->field_id( array( 'lifterlms_quiz_failed' => 'limit' )), $prefs['lifterlms_quiz_failed']['limit']),
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
							<label for="<?php echo esc_attr($this->field_id(array( 'lifterlms_quiz_failed' => 'log' ))); ?>"><?php esc_html_e('Log template', 'mycred-toolkit'); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( array( 'lifterlms_quiz_failed' => 'log' ))); ?>" id="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_failed' => 'log' ))); ?>" placeholder="<?php esc_html_e('required', 'mycred-toolkit'); ?>" value="<?php echo esc_attr( $prefs['lifterlms_quiz_failed']['log']); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post($this->available_template_tags(array( 'general' ))); ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="hook-instance">
				<h3><?php esc_html_e('Student completes a quiz', 'mycred-toolkit'); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id(array( 'lifterlms_quiz_completed' => 'creds' ))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name(array( 'lifterlms_quiz_completed' => 'creds' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'lifterlms_quiz_completed' => 'creds' ))); ?>" value="<?php echo esc_attr($this->core->number($prefs['lifterlms_quiz_completed']['creds'])); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id(array( 'lifterlms_quiz_completed' => 'limit' ))); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
							<?php
							echo wp_kses($this->hook_limit_setting($this->field_name( array( 'lifterlms_quiz_completed' => 'limit' )), $this->field_id( array( 'lifterlms_quiz_completed' => 'limit' )), $prefs['lifterlms_quiz_completed']['limit']),
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
							<label for="<?php echo esc_attr($this->field_id( array( 'lifterlms_quiz_completed' => 'log' ))); ?>"><?php esc_html_e('Log template', 'mycred-toolkit'); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name(array( 'lifterlms_quiz_completed' => 'log' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'lifterlms_quiz_completed' => 'log' ))); ?>" placeholder="<?php esc_html_e('required', 'mycred-toolkit'); ?>" value="<?php echo esc_attr($prefs['lifterlms_quiz_completed']['log']); ?>" class="form-control" />
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

			if (isset( $data['lifterlms_quiz_passed']['limit']) && isset($data['lifterlms_quiz_passed']['limit_by'])) {
				$limit = sanitize_text_field($data['lifterlms_quiz_passed']['limit']);
				if ( $limit == '' ) {
$limit = 0;
				}
				$data['lifterlms_quiz_passed']['limit'] = $limit . '/' . $data['lifterlms_quiz_passed']['limit_by'];
				unset($data['lifterlms_quiz_passed']['limit_by']);
			}

			if (isset( $data['lifterlms_quiz_failed']['limit']) && isset($data['lifterlms_quiz_failed']['limit_by'])) {
				$limit = sanitize_text_field($data['lifterlms_quiz_failed']['limit']);
				if ($limit == '') {
$limit = 0;
				}
				$data['lifterlms_quiz_failed']['limit'] = $limit . '/' . $data['lifterlms_quiz_failed']['limit_by'];
				unset($data['lifterlms_quiz_failed']['limit_by']);
			}

			if (isset( $data['lifterlms_quiz_completed']['limit']) && isset($data['lifterlms_quiz_completed']['limit_by'])) {
				$limit = sanitize_text_field($data['lifterlms_quiz_completed']['limit']);
				if ($limit == '') {
$limit = 0;
				}
				$data['lifterlms_quiz_completed']['limit'] = $limit . '/' . $data['lifterlms_quiz_completed']['limit_by'];
				unset($data['lifterlms_quiz_completed']['limit_by']);
			}

			return $data;
		}
	}
endif;

?>