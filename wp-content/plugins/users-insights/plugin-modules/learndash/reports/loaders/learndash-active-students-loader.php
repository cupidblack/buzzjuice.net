<?php

class USIN_Learndash_Active_Students_Loader extends USIN_Period_Report_Loader {

	protected function load_data(){
		global $wpdb;

		$group_by = $this->get_period_group_by($this->label_col);
		$start = $this->get_period_start();
		$end =  $this->get_period_end();
		$activity_updated = USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('activity_updated');
		$focus_course_id = $this->get_selected_group_filter('course');

		$subquery = "SELECT user_id, $activity_updated as $this->label_col FROM {$wpdb->prefix}learndash_user_activity AS a";

		if($focus_course_id){
			$subquery .= $wpdb->prepare(" LEFT JOIN $wpdb->postmeta m ON a.post_id = m.post_id AND m.meta_key = 'course_id'".
				"WHERE (activity_type = 'course' AND a.post_id = %d) OR (activity_type IN ('lesson', 'quiz', 'topic') AND m.meta_value = %d)",
				$focus_course_id, $focus_course_id);
		}else{
			$subquery .= " WHERE activity_type != 'access'";
		}

		$query = $wpdb->prepare("SELECT COUNT(DISTINCT(user_id)) AS $this->total_col, $this->label_col".
			" FROM ($subquery) AS activities WHERE $this->label_col >= %s AND $this->label_col <= %s GROUP BY $group_by",
			$start, $end);

		return $wpdb->get_results( $query );
	}
}