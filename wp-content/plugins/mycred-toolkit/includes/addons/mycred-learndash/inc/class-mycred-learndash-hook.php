<?php
if (!defined('ABSPATH')) {
	exit; // disable direct access
}

/**
 * Register Learndash Hook
 */
add_filter('mycred_setup_hooks', 'Learndash_myCRED_Hook');

function Learndash_myCRED_Hook( $installed ) {

	$installed['hook_learndash'] = array(
		'title' => __('LearnDash', 'mycred-toolkit'),
		'description' => __('Awards %_plural% for LearnDash actions.', 'mycred-toolkit'),
		'callback' => array( 'myCRED_Hook_Learndash' )
	);

	return $installed;
}

/**
 * Hook for LearnDash
 */
add_action('mycred_load_hooks', 'mycred_load_learndash_hook', 10);

function mycred_load_learndash_hook() {
	if (!class_exists('myCRED_Hook_Learndash') && class_exists('myCRED_Hook')) {

		class myCRED_Hook_Learndash extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = 'mycred_default' ) {
				parent::__construct(array(
					'id' => 'hook_learndash',
					'defaults' => array(
						'course_completed' => array(
							'creds' => 0,
							'log' => __('%plural% for Completing a Course', 'mycred-toolkit'),
							'limit' => '0/x',
						),
						'lesson_completed' => array(
							'creds' => 0,
							'log' => __('%plural% for Completing a Lesson', 'mycred-toolkit'),
							'limit' => '0/x',
						),
						'topic_completed' => array(
							'creds' => 0,
							'log' => __('%plural% for Completing a Topic', 'mycred-toolkit'),
							'limit' => '0/x',
						),
						'quiz_completed' => array(
							'creds' => 0,
							'log' => __('%plural% for Completing a Quiz', 'mycred-toolkit'),
							'limit' => '0/x',
						),
						'quiz_failed' => array(
							'creds' => 0,
							'log' => __('%plural% for Failing at Quiz', 'mycred-toolkit'),
							'limit' => '0/x',
						),
						'uploaded_assignment' => array(
							'creds' => 0,
							'log' => __('%plural% for Upload an Assignment', 'mycred-toolkit'),
							'limit' => '0/x',
						),
						'approved_assignment' => array(
							'creds' => 0,
							'log' => __('%plural% for Approve an Assignment', 'mycred-toolkit'),
							'limit' => '0/x',
						)
					)
				), $hook_prefs, $type);
			}

			/**
			 * Run
			 */
			public function run() {
				// Course Completed
				add_action('learndash_course_completed', array( $this, 'course_completed' ), 5, 1);
				// Lesson Completed
				add_action('learndash_lesson_completed', array( $this, 'lesson_completed' ), 5, 1);
				// Topic Completed
				add_action('learndash_topic_completed', array( $this, 'topic_completed' ), 5, 1);
				// Quiz Completed
				add_action('learndash_quiz_completed', array( $this, 'quiz_completed' ), 5, 1);
				// Assignment Uploaded
				add_action('learndash_assignment_uploaded', array( $this, 'assignment_uploaded' ), 10, 2);
				// Assignment Approved
				add_action('learndash_assignment_approved', array( $this, 'assignment_approved' ), 5, 1);
				// Register Custom myCRED References
				add_filter('mycred_all_references', array( $this, 'add_references' ));
			}

			/**
			 * Learn dash points for quiz 
			 */
			public function learn_dash_quiz_points( $type, $data, $score = null ) {
				//If quiz minimum percentage greater than zero
				if ($data->min_percentage != 0) {
					//If quiz score more than minimum percentage ,quiz completed with points 
					if ($score >= $data->min_percentage) {
						return $this->learn_dash_points($type, $data);
					}
					//If quiz score less than minimum percentage,quiz not completed
					else {
						return 0;
					}
				}
				//If quiz minimum percentage not override, complete quiz 
				else {
					return $this->learn_dash_points($type, $data);
				}
			}

			/**
			 * Learn dash points from learn dash setting if general setting override else from general setting 
			 */
			public function learn_dash_points( $type, $data ) {
				//Override points for Course,Lesson,Topic and Quiz
				$learndash_data = array();
				if ($data->myCred_override_hook === 'on') {
					if (trim($data->myCred_point) != '' && $type != 'quiz_failed') {
						$learndash_data = array(
							'point' => $data->myCred_point,
							'pt_type' => $data->myCred_point_type
						);
						return $learndash_data;
					} elseif ($type == 'quiz_failed' && trim($data->myCred_quiz_point_fail) != '') {
						$learndash_data['pt_fail'] = $data->myCred_quiz_point_fail;
						return $learndash_data;
					}
				} elseif ($this->prefs[ $type ]['creds'] != 0) {
					$learndash_data = array( 'point' => $this->prefs[ $type ]['creds'] );
					return $learndash_data;
				} else {
					return 0;
				}
			}

			/*
			 * Learndash check if badges assigned from single course/topic/lesson/quiz
			 */

			public function get_learndash_badge_id( $id ) {
				if (class_exists('myCRED_Badge')) {
					$badge_id = get_post_meta($id, 'myCred_badges_override', true);
					if ($badge_id) {
						return $badge_id;
					} else {
						return 0;
					}
				} else {
					return 0;
				}
				die;
			}

			/**
			 * Course Completed
			 */
			public function course_completed( $data ) {
				// Get course points
				$learndash_data = $this->learn_dash_points('course_completed', $data['course']);
				$badge_id = $this->get_learndash_badge_id($data['course']->ID);

				if ($learndash_data != 0) {
					$course_id = $data['course']->ID;
					// Must be logged in                
					if (!is_user_logged_in()) {
						return;
					}

					$user_id = get_current_user_id();

					// Check if user is excluded
					if ($this->core->exclude_user($user_id)) {
						return;
					}

					// Make sure this is unique event
					if ($this->core->has_entry('course_completed', $course_id, $user_id)) {
						return;
					}

					if ($this->over_hook_limit('course_completed', 'course_completed', $user_id)) {
						return;
					}


					if ($badge_id != 0) {
						mycred_assign_badge_to_user($user_id, $badge_id);
					}


					if (isset($learndash_data['pt_type'])) {
						if ($learndash_data['pt_type'] === $this->mycred_type) {
							$pt_type = $learndash_data['pt_type'];
							// Execute
							$this->core->add_creds(
									'course_completed', $user_id, $learndash_data['point'], $this->prefs['course_completed']['log'], $course_id, array( 'ref_type' => 'post' ), $pt_type
							);
						}
					} else {
						// Execute
						$this->core->add_creds(
								'course_completed', $user_id, $learndash_data['point'], $this->prefs['course_completed']['log'], $course_id, array( 'ref_type' => 'post' ), $this->mycred_type
						);
					}
				}
			}

			/**
			 * Lesson Completed
			 */
			public function lesson_completed( $data ) {
				// Get lesson points
				$learndash_data = $this->learn_dash_points('lesson_completed', $data['lesson']);
				$badge_id = $this->get_learndash_badge_id($data['lesson']->ID);

				if ($learndash_data != 0) {
					$lesson_id = $data['lesson']->ID;
					// Must be logged in
					if (!is_user_logged_in()) {
						return;
					}

					$user_id = get_current_user_id();

					// Check if user is excluded
					if ($this->core->exclude_user($user_id)) {
						return;
					}

					// Make sure this is unique event
					if ($this->core->has_entry('lesson_completed', $lesson_id, $user_id)) {
						return;
					}

					if ($this->over_hook_limit('lesson_completed', 'lesson_completed', $user_id)) {
						return;
					}

					if ($badge_id != 0) {
						mycred_assign_badge_to_user($user_id, $badge_id);
					}

					if (isset($learndash_data['pt_type'])) {
						if ($learndash_data['pt_type'] === $this->mycred_type) {
							$pt_type = $learndash_data['pt_type'];
							// Execute
							$this->core->add_creds(
									'lesson_completed', $user_id, $learndash_data['point'], $this->prefs['lesson_completed']['log'], $lesson_id, array( 'ref_type' => 'post' ), $pt_type
							);
						}
					} else {
						$this->core->add_creds(
								'lesson_completed', $user_id, $learndash_data['point'], $this->prefs['lesson_completed']['log'], $lesson_id, array( 'ref_type' => 'post' ), $this->mycred_type
						);
					}
				}
			}

			/**
			 * Topic Completed
			 */
			public function topic_completed( $data ) {
				// Get topic points
				$learndash_data = $this->learn_dash_points('topic_completed', $data['topic']);
				$badge_id = $this->get_learndash_badge_id($data['topic']->ID);

				if ($learndash_data != 0) {
					$topic_id = $data['topic']->ID;
					// Must be logged in
					if (!is_user_logged_in()) {
						return;
					}

					$user_id = get_current_user_id();

					// Check if user is excluded
					if ($this->core->exclude_user($user_id)) {
						return;
					}

					// Make sure this is unique event
					if ($this->core->has_entry('topic_completed', $topic_id, $user_id)) {
						return;
					}

					if ($this->over_hook_limit('topic_completed', 'topic_completed', $user_id)) {
						return;
					}


					if ($badge_id != 0) {
						mycred_assign_badge_to_user($user_id, $badge_id);
					}

					if (isset($learndash_data['pt_type'])) {
						if ($learndash_data['pt_type'] === $this->mycred_type) {
							$pt_type = $learndash_data['pt_type'];
							// Execute
							$this->core->add_creds(
									'topic_completed', $user_id, $learndash_data['point'], $this->prefs['topic_completed']['log'], $topic_id, array( 'ref_type' => 'post' ), $pt_type
							);
						}
					} else {
						$this->core->add_creds(
								'topic_completed', $user_id, $learndash_data['point'], $this->prefs['topic_completed']['log'], $topic_id, array( 'ref_type' => 'post' ), $this->mycred_type
						);
					}
				}
			}

			/**
			 * Quiz Completed
			 */
			public function quiz_completed( $data ) {
				// Get quiz points
				if ($data['pass'] == 1) {
					$status = 'completed';
				} else {
					$status = 'failed';
				}
				$learndash_data = $this->learn_dash_quiz_points("quiz_$status", $data['quiz'], $data['percentage']);
				$badge_id = $this->get_learndash_badge_id($data['quiz']->ID);

				if ($learndash_data != 0) {
					$quiz_id = $data['quiz']->ID;
					// Must be logged in
					if (!is_user_logged_in()) {
						return;
					}

					$user_id = get_current_user_id();

					// Check if user is excluded
					if ($this->core->exclude_user($user_id)) {
						return;
					}

					// Make sure this is unique event
					if ($this->core->has_entry("quiz_$status", $quiz_id, $user_id)) {
						return;
					}

					if ($this->over_hook_limit("quiz_$status", "quiz_$status", $user_id)) {
						return;
					}


					if ($data['pass'] != 1) {
						$points = -( $learndash_data['point'] );
					} else {
						$points = $learndash_data['point'];
					}

					if (isset($learndash_data['pt_type'])) {
						if ($learndash_data['pt_type'] === $this->mycred_type) {
							$pt_type = $learndash_data['pt_type'];
							// Execute
							$this->core->add_creds(
									"quiz_$status", $user_id, $points, $this->prefs[ "quiz_$status" ]['log'], $quiz_id, array( 'ref_type' => 'post' ), $pt_type
							);
						}
					} else {
						// Execute
						$this->core->add_creds(
								"quiz_$status", $user_id, $points, $this->prefs[ "quiz_$status" ]['log'], $quiz_id, array( 'ref_type' => 'post' ), $this->mycred_type
						);
					}
				}
			}

			/**
			 * Assignment Uploaded
			 */
			public function assignment_uploaded( $assignment_id, $assignment_meta_data ) {

				$assignment = get_post( $assignment_id );

				if ( $assignment ) {

					$user_id   = $assignment->post_author;
					$lesson_id = get_post_meta( $assignment_id, 'lesson_id', true );
					$assignment_type = 'sfwd-lessons';

					if ( get_post_type( $lesson_id ) != 'sfwd-lessons' ) {
						$lesson_id = get_post_meta( $lesson_id, 'lesson_id', true );
						$assignment_type = 'sfwd-topic';
					}

					$course_id = get_post_meta( $assignment_id, 'course_id', true );

					// Check if user is excluded
					if ( $this->core->exclude_user( $user_id ) ) {
return;
					}

					// Make sure this is unique event
					if ( $this->core->has_entry( 'uploaded_assignment', $assignment_id, $user_id ) ) {
return;
					}

					// Limit
					if ( $this->over_hook_limit( 'uploaded_assignment', 'uploaded_assignment', $user_id ) ) {
return;
					}

					$data = array(
						'ref_type'  => 'post',
						'course_id' => $course_id,
						'lesson_id' => $lesson_id,
						'assignment_type' => $assignment_type,
						'assignment_meta_data' => $assignment_meta_data,
						'badge_course_key' => "badge_course_$course_id",
						'badge_lesson_key' => "badge_lesson_$lesson_id"
					);

					// Execute
					$this->core->add_creds(
						'uploaded_assignment', 
						$user_id,
						$this->prefs['uploaded_assignment']['creds'], 
						$this->prefs['uploaded_assignment']['log'], 
						$assignment_id,
						$data, 
						$this->mycred_type
					);

				}
			}

			/**
			 * Assignment Approved
			 */
			public function assignment_approved( $assignment_id ) {

				$assignment = get_post( $assignment_id );

				if ( $assignment ) {

					$user_id   = $assignment->post_author;
					$lesson_id = get_post_meta( $assignment_id, 'lesson_id', true );
					$assignment_type = 'sfwd-lessons';

					if ( get_post_type( $lesson_id ) != 'sfwd-lessons' ) {
						$lesson_id = get_post_meta( $lesson_id, 'lesson_id', true );
						$assignment_type = 'sfwd-topic';
					}

					$course_id = get_post_meta( $assignment_id, 'course_id', true );

					// Check if user is excluded
					if ( $this->core->exclude_user( $user_id ) ) {
return;
					}

					// Make sure this is unique event
					if ( $this->core->has_entry( 'approved_assignment', $assignment_id, $user_id ) ) {
return;
					}

					// Limit
					if ( $this->over_hook_limit( 'approved_assignment', 'approved_assignment', $user_id ) ) {
return;
					}

					$data = array(
						'ref_type'  => 'post',
						'course_id' => $course_id,
						'lesson_id' => $lesson_id,
						'assignment_type' => $assignment_type,
						'badge_course_key' => "badge_course_$course_id",
						'badge_lesson_key' => "badge_lesson_$lesson_id"
					);

					// Execute
					$this->core->add_creds(
						'approved_assignment', 
						$user_id,
						$this->prefs['approved_assignment']['creds'], 
						$this->prefs['approved_assignment']['log'], 
						$assignment_id,
						$data, 
						$this->mycred_type
					);

				}
			}

			/**
			 * Register Custom myCRED References
			 */
			public function add_references( $references ) {

				// LearnDash References
				$references['course_completed']    = 'Learndash Completed Course';
				$references['lesson_completed']    = 'Learndash Completed Lesson';
				$references['topic_completed']     = 'Learndash Completed Topic';
				$references['quiz_completed']      = 'Learndash Completed Quiz';
				$references['quiz_failed']         = 'Learndash Failed Quiz';
				$references['uploaded_assignment'] = 'Learndash Upload an Assignment';
				$references['approved_assignment'] = 'Learndash Approve an Assignment';
				return $references;
			}

			/**
			 * Preferences for LearnDash
			 */
			public function preferences() {
				$prefs = $this->prefs;
				?>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Completing a Course', 'mycred-toolkit' ); ?></h3>
					<div class="row">
						<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'course_completed' => 'creds' )); ?>"><?php echo $this->core->plural(); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'course_completed' => 'creds' )); ?>" id="<?php echo $this->field_id(array( 'course_completed' => 'creds' )); ?>" value="<?php echo $this->core->number($prefs['course_completed']['creds']); ?>" class="form-control" />
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<?php add_filter('mycred_hook_limits', array( $this, 'custom_limit' )); ?>
								<label for="<?php echo $this->field_id(array( 'course_completed', 'limit' )); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
								<?php echo $this->hook_limit_setting($this->field_name(array( 'course_completed', 'limit' )), $this->field_id(array( 'course_completed', 'limit' )), esc_attr($prefs['course_completed']['limit'] ) ); ?>
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'course_completed' => 'log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'course_completed' => 'log' )); ?>" id="<?php echo $this->field_id(array( 'course_completed' => 'log' )); ?>" value="<?php echo esc_attr($prefs['course_completed']['log']); ?>" class="form-control" />
								<span class="description"><?php echo $this->available_template_tags(array( 'general', 'post' )); ?></span>
							</div>
						</div>
					</div>
				</div>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Completing a Lesson', 'mycred-toolkit' ); ?></h3>
					<div class="row">                        

						<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'lesson_completed' => 'creds' )); ?>"><?php echo $this->core->plural(); ?></label>
								<div class="h2"><input type="text" name="<?php echo $this->field_name(array( 'lesson_completed' => 'creds' )); ?>" id="<?php echo $this->field_id(array( 'lesson_completed' => 'creds' )); ?>" value="<?php echo $this->core->number($prefs['lesson_completed']['creds']); ?>" class="form-control"/></div>
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<?php add_filter('mycred_hook_limits', array( $this, 'custom_limit' )); ?>
								<label for="<?php echo $this->field_id(array( 'lesson_completed', 'limit' )); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
								<?php echo $this->hook_limit_setting($this->field_name(array( 'lesson_completed', 'limit' )), $this->field_id(array( 'lesson_completed', 'limit' )), esc_attr( $prefs['lesson_completed']['limit'] ) ); ?>
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'lesson_completed' => 'log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'lesson_completed' => 'log' )); ?>" id="<?php echo $this->field_id(array( 'lesson_completed' => 'log' )); ?>" value="<?php echo esc_attr($prefs['lesson_completed']['log']); ?>" class="form-control" />
								<span class="description"><?php echo $this->available_template_tags(array( 'general', 'post' )); ?></span>
							</div>
						</div>

					</div>
				</div>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Completing a Topic', 'mycred-toolkit' ); ?></h3>
					<div class="row">

						<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'topic_completed' => 'creds' )); ?>"><?php echo $this->core->plural(); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'topic_completed' => 'creds' )); ?>" id="<?php echo $this->field_id(array( 'topic_completed' => 'creds' )); ?>" value="<?php echo $this->core->number($prefs['topic_completed']['creds']); ?>" class="form-control" />
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<?php add_filter('mycred_hook_limits', array( $this, 'custom_limit' )); ?>
								<label for="<?php echo $this->field_id(array( 'topic_completed', 'limit' )); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
								<?php echo $this->hook_limit_setting($this->field_name(array( 'topic_completed', 'limit' )), $this->field_id(array( 'topic_completed', 'limit' )), esc_attr( $prefs['topic_completed']['limit'] ) ); ?>
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'topic_completed' => 'log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'topic_completed' => 'log' )); ?>" id="<?php echo $this->field_id(array( 'topic_completed' => 'log' )); ?>" value="<?php echo esc_attr($prefs['topic_completed']['log']); ?>" class="form-control" />
								<span class="description"><?php echo $this->available_template_tags(array( 'general', 'post' )); ?></span>
							</div>
						</div>

					</div>
				</div>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Completing a Quiz', 'mycred-toolkit' ); ?></h3>
					<div class="row">

						<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'quiz_completed' => 'creds' )); ?>"><?php echo $this->core->plural(); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'quiz_completed' => 'creds' )); ?>" id="<?php echo $this->field_id(array( 'quiz_completed' => 'creds' )); ?>" value="<?php echo $this->core->number($prefs['quiz_completed']['creds']); ?>" class="form-control" />
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<?php add_filter('mycred_hook_limits', array( $this, 'custom_limit' )); ?>
								<label for="<?php echo $this->field_id(array( 'quiz_completed', 'limit' )); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
								<?php echo $this->hook_limit_setting($this->field_name(array( 'quiz_completed', 'limit' )), $this->field_id(array( 'quiz_completed', 'limit' )), esc_attr( $prefs['quiz_completed']['limit'] ) ); ?>
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'quiz_completed' => 'log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'quiz_completed' => 'log' )); ?>" id="<?php echo $this->field_id(array( 'quiz_completed' => 'log' )); ?>" value="<?php echo esc_attr($prefs['quiz_completed']['log']); ?>" class="form-control" />
								<span class="description"><?php echo $this->available_template_tags(array( 'general', 'post' )); ?></span>
							</div>
						</div>

					</div>
				</div>
				<div class="hook-instance">
					<h3><?php esc_html_e( 'Failed at a Quiz', 'mycred-toolkit' ); ?></h3>
					<div class="row">

						<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'quiz_failed' => 'creds' )); ?>"><?php echo $this->core->plural(); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'quiz_failed' => 'creds' )); ?>" id="<?php echo $this->field_id(array( 'quiz_failed' => 'creds' )); ?>" value="<?php echo $this->core->number($prefs['quiz_failed']['creds']); ?>" class="form-control" />
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<?php add_filter('mycred_hook_limits', array( $this, 'custom_limit' )); ?>
								<label for="<?php echo $this->field_id(array( 'quiz_failed', 'limit' )); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
								<?php echo $this->hook_limit_setting($this->field_name(array( 'quiz_failed', 'limit' )), $this->field_id(array( 'quiz_failed', 'limit' )), esc_attr( $prefs['quiz_failed']['limit'] ) ); ?>
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'quiz_failed' => 'log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'quiz_failed' => 'log' )); ?>" id="<?php echo $this->field_id(array( 'quiz_failed' => 'log' )); ?>" value="<?php echo esc_attr($prefs['quiz_failed']['log']); ?>" class="form-control" />
								<span class="description"><?php echo $this->available_template_tags(array( 'general', 'post' )); ?></span>
							</div>
						</div>

					</div>
				</div>

				<div class="hook-instance">
					<h3><?php esc_html_e( 'Upload an Assignment', 'mycred-toolkit' ); ?></h3>
					<div class="row">

						<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'uploaded_assignment' => 'creds' )); ?>"><?php echo $this->core->plural(); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'uploaded_assignment' => 'creds' )); ?>" id="<?php echo $this->field_id(array( 'uploaded_assignment' => 'creds' )); ?>" value="<?php echo $this->core->number($prefs['uploaded_assignment']['creds']); ?>" class="form-control" />
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<?php add_filter('mycred_hook_limits', array( $this, 'custom_limit' )); ?>
								<label for="<?php echo $this->field_id(array( 'uploaded_assignment', 'limit' )); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
								<?php echo $this->hook_limit_setting($this->field_name(array( 'uploaded_assignment', 'limit' )), $this->field_id(array( 'uploaded_assignment', 'limit' )), esc_attr( $prefs['uploaded_assignment']['limit'] ) ); ?>
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'uploaded_assignment' => 'log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'uploaded_assignment' => 'log' )); ?>" id="<?php echo $this->field_id(array( 'uploaded_assignment' => 'log' )); ?>" value="<?php echo esc_attr($prefs['uploaded_assignment']['log']); ?>" class="form-control" />
								<span class="description"><?php echo $this->available_template_tags(array( 'general', 'post' )); ?></span>
							</div>
						</div>

					</div>
				</div>

				<div class="hook-instance">
					<h3><?php esc_html_e( 'Aprrove an Assignment', 'mycred-toolkit' ); ?></h3>
					<div class="row">

						<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'approved_assignment' => 'creds' )); ?>"><?php echo $this->core->plural(); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'approved_assignment' => 'creds' )); ?>" id="<?php echo $this->field_id(array( 'approved_assignment' => 'creds' )); ?>" value="<?php echo $this->core->number($prefs['approved_assignment']['creds']); ?>" class="form-control" />
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<?php add_filter('mycred_hook_limits', array( $this, 'custom_limit' )); ?>
								<label for="<?php echo $this->field_id(array( 'approved_assignment', 'limit' )); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
								<?php echo $this->hook_limit_setting($this->field_name(array( 'approved_assignment', 'limit' )), $this->field_id(array( 'approved_assignment', 'limit' )), esc_attr($prefs['approved_assignment']['limit'] ) ); ?>
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo $this->field_id(array( 'approved_assignment' => 'log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
								<input type="text" name="<?php echo $this->field_name(array( 'approved_assignment' => 'log' )); ?>" id="<?php echo $this->field_id(array( 'approved_assignment' => 'log' )); ?>" value="<?php echo esc_attr($prefs['approved_assignment']['log']); ?>" class="form-control" />
								<span class="description"><?php echo $this->available_template_tags(array( 'general', 'post' )); ?></span>
							</div>
						</div>

					</div>
				</div>

				<?php
			}

			function sanitise_preferences( $data ) {

				$references = array(
					'course_completed',
					'lesson_completed',
					'topic_completed',
					'quiz_completed',
					'quiz_failed',
					'uploaded_assignment',
					'approved_assignment'
				);

				foreach ($references as $reference) {
					if (isset($data[ $reference ]['limit']) && isset($data[ $reference ]['limit_by'])) {
						$limit = sanitize_text_field($data[ $reference ]['limit']);
						if ($limit == '') {
							$limit = 0;
						}
						$data[ $reference ]['limit'] = $limit . '/' . $data[ $reference ]['limit_by'];
						unset($data[ $reference ]['limit_by']);
					}
				}

				return $data;
			}

			public function custom_limit() {
				return array(
					'x' => __('No limit', 'mycred-toolkit'),
					'd' => __('/ Day', 'mycred-toolkit'),
					'w' => __('/ Week', 'mycred-toolkit'),
					'm' => __('/ Month', 'mycred-toolkit'),
				);
			}
		}

	}
}