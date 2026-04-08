<?php

if (!defined('ABSPATH')) {
	exit; // disable direct access
}

class myCRED_Learndash_Badges {

	/**
	 * Construct
	 */

	public $refrences            = array();  
	public $assignment_refrences = array();  

	function __construct() {

		$this->refrences = array( 
			'course_completed'    => 'sfwd-courses', 
			'lesson_completed'    => 'sfwd-lessons', 
			'topic_completed'     => 'sfwd-topics', 
			'quiz_completed'      => 'sfwd-quiz', 
			'quiz_failed'         => 'sfwd-quiz'
		);

		$this->assignment_refrences = array( 
			'uploaded_assignment' => 'sfwd-assignment',
			'approved_assignment' => 'sfwd-assignment'
		);

		add_filter( 'mycred_badge_requirement', array( $this, 'mycred_learndash_badge_requirement' ), 10, 5 );
		add_filter( 'mycred_badge_requirement_specific_template', array( $this, 'mycred_learndash_badge_template' ), 10, 5 );
		add_action( 'admin_head', array( $this, 'mycred_learndash_admin_header' ) );
	}

	public function mycred_learndash_badge_requirement( $query, $requirement_id, $requirement, $having, $user_id ) {
		
		global $wpdb, $mycred_log_table;

		if ( 
			array_key_exists( $requirement['reference'], $this->refrences ) && 
			! empty( $requirement['specific'] ) && 
			$requirement['specific'] != 'Any' 
		) {
			$query = $wpdb->get_var( $wpdb->prepare( "SELECT {$having} FROM {$mycred_log_table} WHERE ctype = %s AND ref = %s AND ref_id = %d AND user_id = %d;", $requirement['type'], $requirement['reference'], $requirement['specific'], $user_id ) );
		} elseif ( 
			array_key_exists( $requirement['reference'], $this->assignment_refrences ) && 
			! empty( $requirement['specific'] ) && 
			$requirement['specific'] != 'Any' 
		) {
			$requirement_type = get_post_type( $requirement['specific'] ); 

			$search_like = ( ( $requirement_type == 'sfwd-courses' ) ? 'badge_course_' : 'badge_lesson_' ) ;
			$search_like = '%' . $search_like . $requirement['specific'] . '%';

			$query = $wpdb->get_var( $wpdb->prepare( "SELECT {$having} FROM {$mycred_log_table} WHERE ctype = %s AND ref = %s AND data like %s AND user_id = %d;", $requirement['type'], $requirement['reference'], $search_like, $user_id ) );
		}
		return $query;
	}

	public function mycred_learndash_badge_template( $data, $requirement_id, $requirement, $badge, $level ) {
		
		if ( 
			array_key_exists( $requirement['reference'], $this->refrences ) && 
			! empty( $requirement['specific'] ) 
		) {  
			$learndash_courses = $this->get_all_posts( $this->refrences[ $requirement['reference'] ] );

			$learndash_courses_options = '<option>Any</option>';

			foreach ( $learndash_courses as $post ) {
				$learndash_courses_options .= '<option value="' . $post->ID . '" ' . selected( $requirement['specific'], $post->ID, false ) . ' >' . htmlentities( $post->post_title, ENT_QUOTES ) . '</option>';
			}

			$data = '<div class="form-group"><select name="mycred_badge[levels][' . $level . '][requires][' . $requirement_id . '][specific]" class="form-control specific" data-row="' . $requirement_id . '" >' . $learndash_courses_options . '</select></div>';

		} elseif ( 
			array_key_exists( $requirement['reference'], $this->assignment_refrences ) && 
			! empty( $requirement['specific'] ) 
		) {    
			$courses = $this->get_all_posts( $this->refrences['course_completed'] );
			$lessons = $this->get_all_posts( $this->refrences['lesson_completed'] );

			$learndash_assignment_options = '<option>Any</option>';

			if ( ! empty( $courses ) ) {

				$learndash_assignment_options .= '<optgroup label="Courses">';

				foreach ( $courses as $course ) {
					$learndash_assignment_options .= '<option value="' . $course->ID . '" ' . selected( $requirement['specific'], $course->ID, false ) . ' >' . htmlentities( $course->post_title, ENT_QUOTES ) . '</option>';
				}

				$learndash_assignment_options .= '</optgroup>';

			}

			if ( ! empty( $lessons ) ) {

				$learndash_assignment_options .= '<optgroup label="Lessons">';

				foreach ( $lessons as $lesson ) {
					$learndash_assignment_options .= '<option value="' . $lesson->ID . '" ' . selected( $requirement['specific'], $lesson->ID, false ) . ' >' . htmlentities( $lesson->post_title, ENT_QUOTES ) . '</option>';
				}

				$learndash_assignment_options .= '</optgroup>';

			}

			$data = '<div class="form-group"><select name="mycred_badge[levels][' . $level . '][requires][' . $requirement_id . '][specific]" class="form-control specific" data-row="' . $requirement_id . '" >' . $learndash_assignment_options . '</select></div>';

		}
		return $data;
	}

	public function mycred_learndash_admin_header() {
		$screen = get_current_screen();

		if ( $screen->id == MYCRED_BADGE_KEY ) :?>
		<script type="text/javascript">
		<?php

			foreach ( $this->refrences as $key => $value ) {
			
				$learndash_courses = $this->get_all_posts( $value );

				$learndash_courses_options = '<option>Any</option>';

				foreach ( $learndash_courses as $post ) {
					$learndash_courses_options .= '<option value="' . $post->ID . '">' . htmlentities( $post->post_title, ENT_QUOTES ) . '</option>';
				}
				$data = '<div class="form-group"><select name="{{element_name}}" class="form-control specific" data-row="{{reqlevel}}" >' . $learndash_courses_options . '</select></div>';
				echo 'var mycred_badge_' . esc_js($key) . " = '" . esc_js($data) . "';";

			}

			foreach ( $this->assignment_refrences as $key => $value ) {
			
				$courses = $this->get_all_posts( $this->refrences['course_completed'] );
				$lessons = $this->get_all_posts( $this->refrences['lesson_completed'] );

				$learndash_assignment_options = '<option>Any</option>';

				if ( ! empty( $courses ) ) {
					$learndash_assignment_options .= '<optgroup label="Courses">';

					foreach ( $courses as $course ) {
						$learndash_assignment_options .= '<option value="' . $course->ID . '">' . htmlentities( $course->post_title, ENT_QUOTES ) . '</option>';
					}

					$learndash_assignment_options .= '</optgroup>';
				}

				if ( ! empty( $lessons ) ) {
					$learndash_assignment_options .= '<optgroup label="Lessons">';

					foreach ( $lessons as $lesson ) {
						$learndash_assignment_options .= '<option value="' . $lesson->ID . '">' . htmlentities( $lesson->post_title, ENT_QUOTES ) . '</option>';
					}

					$learndash_assignment_options .= '</optgroup>';
				}

				$assignment_data = '<div class="form-group"><select name="{{element_name}}" class="form-control specific" data-row="{{reqlevel}}" >' . $learndash_assignment_options . '</select></div>';
				echo 'var mycred_badge_' . esc_js($key) . " = '" . esc_js($assignment_data) . "';";
			}

			?>
		</script>
		<?php
		endif;
	}

	public function get_all_posts( $post_type ) {

		$args = array( 
			'numberposts' => -1, 
			'post_type'   => $post_type, 
			'post_status' => 'publish' 
		);
		
		return get_posts( $args );
	}
}
