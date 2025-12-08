<?php

class USIN_Learndash_Course_Enrolments_Loader extends USIN_Period_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$group_by = $this->get_period_group_by($this->label_col);
		$start = $this->get_period_start();
		$end =  $this->get_period_end();

		$focus_course_id = $this->get_selected_group_filter('course');
		$course_condition = $focus_course_id ? $wpdb->prepare(' AND post_id = %d', $focus_course_id) : '';

		$activity_started = USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('activity_started');

		$subquery = "SELECT user_id, $activity_started AS $this->label_col FROM {$wpdb->prefix}learndash_user_activity".
			" WHERE activity_type = 'course'".$course_condition;

		$query = $wpdb->prepare("SELECT COUNT(*) as $this->total_col, $this->label_col".
			" FROM ($subquery) AS enrolments WHERE $this->label_col >= %s AND $this->label_col <= %s GROUP BY $group_by",
			$start, $end);
		
		return $wpdb->get_results( $query );
	}

	protected function get_focus_course_id(){
		if($this->report->id != 'learndash_course_enrolments'){
			return USIN_LearnDash_Course_Analytics::get_course_id_by_field_id($this->report->id);
		}
	}
}
