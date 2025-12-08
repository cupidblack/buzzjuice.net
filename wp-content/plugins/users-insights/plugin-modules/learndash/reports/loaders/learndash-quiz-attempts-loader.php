<?php

class USIN_Learndash_Quiz_Attempts_Loader extends USIN_Period_Report_Loader {

	protected function load_data(){
		$passes = $this->get_results(1);
		$fails = $this->get_results(0);

		return array(
			$this->dataset($passes, __('Passes', 'usin'), 'green'),
			$this->dataset($fails, __('Fails', 'usin'), 'red')
		);
	}

	protected function get_results($activity_status){
		global $wpdb;

		$group_by = $this->get_period_group_by($this->label_col);
		$start = $this->get_period_start();
		$end = $this->get_period_end();
		$activity_completed = USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('activity_completed');
		$quiz_id = $this->get_selected_group_filter('quiz');

		$subquery = $wpdb->prepare("SELECT user_id, $activity_completed as $this->label_col FROM {$wpdb->prefix}learndash_user_activity" .
			" WHERE activity_type = 'quiz' AND activity_status = %d", $activity_status);

		if($quiz_id !== null){
			$subquery .= $wpdb->prepare(" AND post_id = %d", intval($quiz_id));
		}

		$query = $wpdb->prepare("SELECT COUNT(user_id) AS $this->total_col, $this->label_col" .
			" FROM ($subquery) AS activities WHERE $this->label_col >= %s AND $this->label_col <= %s GROUP BY $group_by",
			$start, $end);

		return $wpdb->get_results($query);
	}
}