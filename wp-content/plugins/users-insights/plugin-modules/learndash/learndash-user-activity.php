<?php

/**
 * Adds the LearnDash user activity to the user profile section
 */
class USIN_LearnDash_User_Activity{

	protected $module_name;

	public function __construct($module_name){
		$this->module_name = $module_name;
		$this->init();
	}

	public function init(){
		add_filter('usin_user_activity', array($this, 'add_leardash_activity'), 10, 2);
	}

	public function add_leardash_activity($activity, $user_id){

		$ld_activities = array(
			$this->get_course_activity($user_id),
			$this->get_course_access_list($user_id),
			$this->get_quiz_activity($user_id),
			$this->get_group_activity($user_id)
		);

		foreach ($ld_activities as $ld_activity) {
			if(!empty($ld_activity)){
				$activity[]= $ld_activity;
			}
		}

		return $activity;
	}


	/**
	 * Adds the course activity with progress info
	 * @param  int $user_id the ID of the user
	 * @return array          The original user activity including the course activity
	 */
	protected function get_course_activity($user_id){
		$course_activity = $this->get_activity_courses($user_id);

		if(empty($course_activity)){
			return null;
		}

		$list = array();
		foreach ($course_activity as $activity ) {
			$details = array();

			if(!empty($activity->started_on)){
				$details[]= USIN_Html::activity_label( __('Started on', 'usin'), USIN_Helper::format_date($activity->started_on));
			}

			if(!empty($activity->completed_on)){
				$details[]= USIN_Html::activity_label( __('Completed on', 'usin'), USIN_Helper::format_date($activity->completed_on));
			}

			$list[]= array(
				'title' => $activity->course_title . $this->get_course_progress_tag($activity->course_id, $user_id),
				'link' => get_permalink( $activity->course_id ),
				'details' => $details
			);
		}

		return array(
			'type' => 'ld_courses',
			'label' => sprintf(__('%s Activity', 'usin'), USIN_LearnDash::get_label('course')),
			'list' => $list,
			'icon' => $this->module_name
		);
	}

	/**
	 * Returns the HTML markup of a course progress percentage.
	 */
	protected function get_course_progress_tag($course_id, $user_id){
		$result = '';
		if(!function_exists('learndash_course_progress')){
			return $result;
		}

		$progress = learndash_course_progress( array(
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'array'     => true
		) );

		if(is_array($progress) && isset($progress['percentage'])){
			$result = USIN_Html::progress_tag($progress['percentage']);
		}

		return $result;
	}

	protected function get_course_access_list($user_id){

		if(!function_exists('ld_get_mycourses')){
			return null;
		}

		$courses = $this->get_courses(ld_get_mycourses($user_id));
		if(empty($courses)){
			return null;
		}

		foreach ($courses as $course ) {
			$list[]= array(
				'title' => $course->post_title,
				'link' => get_permalink( $course->ID )
			);
		}

		return array(
			'type' => 'ld_course_access',
			'label' => sprintf(__('%s Access', 'usin'), USIN_LearnDash::get_label('course')),
			'list' => $list,
			'icon' => $this->module_name
		);
	}

	protected function get_courses($ids){
		if(empty($ids) || !is_array($ids)){
			return array();
		}

		return get_posts( array(
			'posts_per_page' => -1,
			'post_type' => USIN_LearnDash::COURSE_POST_TYPE,
			'post_status' => 'any',
			'include' => $ids
		));
	}

	protected function get_activity_courses($user_id){
		global $wpdb;

		$subquery = USIN_LearnDash_Query::get_course_activity_query(null, $user_id);
		return $wpdb->get_results("SELECT activity.*, activity.post_id AS course_id, p.post_title AS course_title
					FROM ($subquery) AS activity
					INNER JOIN $wpdb->posts p ON p.ID = activity.post_id");
	}

	/**
	 * Adds the quiz activity with passed percentage info
	 * @param  int $user_id the ID of the user
	 * @return array          The original user activity including the quiz activity
	 */
	protected function get_quiz_activity($user_id){
		$activity = array();

		$quiz_attempts = get_user_meta( $user_id, '_sfwd-quizzes', true );

		if(!empty($quiz_attempts) && is_array($quiz_attempts)){
			$count = sizeof($quiz_attempts);
			$list = array();

			foreach ($quiz_attempts as $quiz_attempt ) {
				$quiz = get_post( $quiz_attempt['quiz'] );
				$title = $quiz->post_title.self::generate_quiz_result_tag($quiz_attempt);

				$details = array();
				if(!empty($quiz_attempt['completed'])){
					$details[] = USIN_Html::activity_label(__('Completed on', 'usin'), USIN_Helper::format_timestamp($quiz_attempt['completed']));
				}

				if(!empty($quiz_attempt['timespent'])){
					$duration = $this->seconds_to_period(intval($quiz_attempt['timespent']));
					$details[]= USIN_Html::activity_label( __('Duration', 'usin'), $duration);
				}

				$quiz_info = array(
					'title' => $title,
					'link' => get_edit_post_link( $quiz->ID, 'usin' ),
					'details' => $details
				);

				$list[]=$quiz_info;
			}

			$activity = array(
				'type' => 'ld_quizes',
				'for' => 'ld_quizes',
				'label' => sprintf(_n('%s Attempt', '%s Attempts', $count, 'usin'), USIN_LearnDash::get_label('quiz')),
				'count' => $count,
				'list' => $list,
				'icon' => $this->module_name
			);
		}

		return $activity;
	}

	private function seconds_to_period($seconds) {
		$hours = floor($seconds / 3600);
		$minutes = floor(($seconds % 3600) / 60);
		$seconds = $seconds % 60;

		return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
	}

	public static function generate_quiz_result_tag($quiz_attempt){
		$percentage = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );
		if(isset($quiz_attempt['pass']) && intval($quiz_attempt['pass'])===0){
			$status = 'fail';
		}elseif(isset($quiz_attempt['pass']) && intval($quiz_attempt['pass'])===1){
			$status = 'success';
		}else{
			$status = 'none';
		}
		return USIN_Html::progress_tag($percentage, $status);
	}

	/**
	 * Adds the quiz activity with passed percentage info
	 * @param  int $user_id the ID of the user
	 * @return array          The original user activity including the quiz activity
	 */
	protected function get_group_activity($user_id){
		$activity = array();

		if(function_exists('learndash_get_users_group_ids')){

			$groups = learndash_get_users_group_ids($user_id);

			if(!empty($groups) && is_array($groups)){
				$count = sizeof($groups);
				$list = array();

				foreach ($groups as $group ) {
					$group = get_post( intval($group) );

					$group_info = array(
						'title' => $group->post_title,
						'link' => get_edit_post_link( $group->ID, 'usin' )
					);

					$list[]=$group_info;
				}

				$activity = array(
					'type' => 'ld_groups',
					'for' => 'ld_groups',
					'label' => sprintf(_n('Belongs to 1 Group', 'Belongs to %d Groups', $count, 'usin'), $count),
					'count' => $count,
					'hide_count' => true,
					'list' => $list,
					'icon' => $this->module_name
				);
			}

		}

		return $activity;
	}


}