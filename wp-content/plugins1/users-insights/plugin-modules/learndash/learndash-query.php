<?php

class USIN_LearnDash_Query{
	
	protected $has_subscription_status_join_applied = false;
	protected $topic_type = 'topic';
	protected $course_type = 'course';
	protected $lesson_type = 'lesson';
	protected $quiz_type = 'quiz';
	protected $count = 0;
	
	public function __construct(){
		$this->init();
	}
	
	public function init(){
		add_filter('usin_db_map', array($this, 'filter_db_map'));
		add_filter('usin_custom_select', array($this, 'filter_query_select'), 10, 2);
		add_filter('usin_db_aggregate_columns', array($this, 'filter_aggregate_columns'));
		add_filter('usin_query_join_table', array($this, 'filter_query_joins'), 10, 2);
		add_filter('usin_custom_query_filter', array($this, 'apply_custom_query_filters'), 10, 2);
	}

	public function filter_db_map($db_map){
		$db_map['ld_lessons_completed'] = array('db_ref'=>'lessons_completed', 'db_table'=>'ld_completed', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['ld_topics_completed'] = array('db_ref'=>'topics_completed', 'db_table'=>'ld_completed', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['ld_courses_completed'] = array('db_ref'=>'courses_completed', 'db_table'=>'ld_completed', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['ld_courses_in_progress'] = array('db_ref'=>'courses_in_progress', 'db_table'=>'ld_courses_in_progress', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['ld_quiz_attempts'] = array('db_ref'=>'attempts', 'db_table'=>'ld_quizes', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['ld_quiz_passes'] = array('db_ref'=>'passes', 'db_table'=>'ld_quizes', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['ld_last_activity'] = array('db_ref'=>'last_activity', 'db_table'=>'ld_last_activity', 'set_alias'=>true, 'nulls_last' => true);
		$db_map['ld_has_completed_course'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['ld_has_not_completed_course'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['ld_has_enrolled_course'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['ld_has_not_enrolled_course'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['ld_has_passed_quiz'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['ld_has_not_passed_quiz'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['ld_group'] = array('db_ref'=>'group_id', 'db_table'=>'ld_groups', 'custom_select'=>true);

		// quiz results
		$quiz_results_field_ids = USIN_LearnDash_Quiz_Results::get_field_ids();
		foreach ($quiz_results_field_ids as $field_id ) {
			$db_map[$field_id] = array('db_ref'=>'meta_value', 'db_table'=>'ld_quiz_results');
		}

		// course_analytics results
		$course_analytics_ids = USIN_LearnDash_Course_Analytics::get_enabled_course_ids();
		foreach ($course_analytics_ids as $course_id ) {
			$started_field = USIN_LearnDash_Course_Analytics::get_started_field_id($course_id);
			$completed_field = USIN_LearnDash_Course_Analytics::get_completed_field_id($course_id);
			$table_alias = USIN_LearnDash_Course_Analytics::get_db_table_alias($course_id);

			$db_map[$started_field] = array('db_ref'=>'started_on', 'db_table'=>$table_alias, 'nulls_last' => true);
			$db_map[$completed_field] = array('db_ref'=>'completed_on', 'db_table'=>$table_alias, 'nulls_last' => true);
		}

		return $db_map;
	}

	public function filter_query_select($query_select, $field){
		if($field == 'ld_group'){
			$query_select="GROUP_CONCAT(DISTINCT ld_groups.group_name SEPARATOR ', ')";
		}
		return $query_select;
	}

	public function filter_aggregate_columns($columns){
		$columns[]='ld_group';
		return $columns;
	}

	public function filter_query_joins($query_joins, $table){
		global $wpdb;

		$ld_activity_table = self::get_ld_table_name();

		if($table =='ld_completed'){
			$query_joins.= " LEFT JOIN (
				SELECT user_id,
				SUM(CASE WHEN activity_type = '$this->lesson_type' THEN 1 ELSE 0 END) AS lessons_completed,
				SUM(CASE WHEN activity_type = '$this->course_type' THEN 1 ELSE 0 END) AS courses_completed,
				SUM(CASE WHEN activity_type = '$this->topic_type' THEN 1 ELSE 0 END) AS topics_completed
				FROM $ld_activity_table
				WHERE activity_status=1 AND activity_type IN ('$this->lesson_type', '$this->course_type', '$this->topic_type')
				GROUP BY user_id
				) AS ld_completed ON $wpdb->users.ID = ld_completed.user_id";
		}elseif($table =='ld_courses_in_progress'){
			$query_joins.= " LEFT JOIN (
				SELECT user_id, COUNT(DISTINCT post_id) AS courses_in_progress
				FROM $ld_activity_table
				WHERE activity_status=0 AND activity_type = '$this->course_type'
				GROUP BY user_id
				) AS ld_courses_in_progress ON $wpdb->users.ID = ld_courses_in_progress.user_id";
		}elseif($table == 'ld_quizes'){
			$query_joins.= " LEFT JOIN (
				SELECT user_id,
				COUNT(activity_id) as attempts,
				SUM(CASE WHEN activity_status = 1 THEN 1 ELSE 0 END) AS passes
				FROM $ld_activity_table
				WHERE activity_type = '$this->quiz_type'
				GROUP BY user_id
			)  AS ld_quizes ON $wpdb->users.ID = ld_quizes.user_id";
		}elseif($table == 'ld_last_activity'){
			$last_activity_select = USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('MAX(activity_updated)');
			$query_joins.= " LEFT JOIN (
				SELECT user_id, $last_activity_select AS last_activity
				FROM $ld_activity_table
				GROUP BY user_id
			)  AS ld_last_activity ON $wpdb->users.ID = ld_last_activity.user_id";
		}elseif($table == 'ld_quiz_results'){
			$query_joins.= " LEFT JOIN $wpdb->usermeta AS ld_quiz_results ON $wpdb->users.ID = ld_quiz_results.user_id AND ld_quiz_results.meta_key = '_sfwd-quizzes'";
		}elseif(USIN_LearnDash_Course_Analytics::is_course_analytics_table($table)){
			$course_id = USIN_LearnDash_Course_Analytics::get_course_id_by_db_table($table);
			$subquery = self::get_course_activity_query($course_id);
			$query_joins.= " LEFT JOIN ($subquery) AS $table ON $wpdb->users.ID = $table.user_id";
		}elseif($table == 'ld_groups'){
			$query_joins.= " LEFT JOIN ( SELECT m.user_id, m.meta_value AS group_id, p.post_title AS group_name FROM $wpdb->usermeta m 
				INNER JOIN $wpdb->posts p on m.meta_value = p.ID and p.post_type = 'groups'
				WHERE meta_key like 'learndash_group_users_%' ) AS ld_groups ON $wpdb->users.ID = ld_groups.user_id";
		}
		return $query_joins;
	}

	public static function get_course_activity_query($course_id = null, $user_id = null){
		global $wpdb;

		$started_select = USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('activity_started');
		$completed_select = USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('activity_completed');
		$ld_activity_table = self::get_ld_table_name();

		$conditions = array("activity_type = 'course'");
		if($course_id != null) $conditions[]= $wpdb->prepare('post_id = %d', $course_id);
		if($user_id != null) $conditions[]= $wpdb->prepare('user_id = %d', $user_id);
		$where_condition = 'WHERE ' . implode(' AND ', $conditions);

		// in some cases LearnDash stores multiple course activities per user per course. For those cases
		// we take the first activity date as activity started, first completed date as activity completed
		// and max(activity_status) as status
		return "SELECT user_id, post_id, MAX(activity_status) AS status,
					MIN(IF(activity_started = 0, NULL, $started_select)) AS started_on,
					MIN(IF(activity_completed = 0, NULL, $completed_select)) AS completed_on
				FROM $ld_activity_table
				$where_condition
				GROUP BY user_id, post_id";
	}

	public function apply_custom_query_filters($custom_query_data, $filter){
		global $wpdb;
		$ref = 'ldr_'.++$this->count;

		if($filter->by == 'ld_has_completed_course' || $filter->by == 'ld_has_not_completed_course'){

			$custom_query_data['joins'] .= $wpdb->prepare(" LEFT JOIN
				(SELECT user_id, post_id FROM ".self::get_ld_table_name()." WHERE post_id = %d 
				AND activity_status = 1 AND activity_type = '$this->course_type'
				GROUP BY user_id) AS $ref ON $wpdb->users.ID = $ref.user_id", $filter->condition);

			$operator = $filter->by == 'ld_has_completed_course' ? 'IS NOT NULL' : 'IS NULL';
			$custom_query_data['where'] = " AND $ref.post_id $operator";

		}elseif($filter->by == 'ld_has_enrolled_course' || $filter->by == 'ld_has_not_enrolled_course' ){

			$custom_query_data['joins'] .= $wpdb->prepare(" LEFT JOIN
				(SELECT user_id, post_id FROM ".self::get_ld_table_name()." WHERE post_id = %d 
				AND activity_type = '$this->course_type'
				GROUP BY user_id) AS $ref ON $wpdb->users.ID = $ref.user_id", $filter->condition);

			$operator = $filter->by == 'ld_has_enrolled_course' ? 'IS NOT NULL' : 'IS NULL';
			$custom_query_data['where'] = " AND $ref.post_id $operator";

		}elseif($filter->by == 'ld_has_passed_quiz' || $filter->by == 'ld_has_not_passed_quiz'){

			$custom_query_data['joins'] .= $wpdb->prepare(" LEFT JOIN
				(SELECT user_id, post_id FROM ".self::get_ld_table_name()." WHERE post_id = %d 
				AND activity_status = 1 AND activity_type = '$this->quiz_type'
				GROUP BY user_id) AS $ref ON $wpdb->users.ID = $ref.user_id", $filter->condition);

			$operator = $filter->by == 'ld_has_passed_quiz' ? 'IS NOT NULL' : 'IS NULL';
			$custom_query_data['where'] = " AND $ref.post_id $operator";
		}

		return $custom_query_data;
	}
	
	

	public static function get_ld_table_name(){
		global $wpdb;
		
		return $wpdb->prefix.'learndash_user_activity';
	}

	
}