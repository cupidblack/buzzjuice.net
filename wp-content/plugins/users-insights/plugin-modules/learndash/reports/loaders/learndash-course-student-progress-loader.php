<?php

class USIN_Learndash_Course_Student_Progress_Loader extends USIN_Standard_Report_Loader {
	protected $question_list;

	protected function load_data(){
		global $wpdb;

		$course_id = $this->get_selected_group_filter('course');

		// set the period condition
		$filter = $this->getSelectedFilter();
		$period_condition = USIN_Standard_Report_With_Period_Filter::generate_condition($filter,
			USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('activity_started'));

		$query = $wpdb->prepare("SELECT activity_status AS $this->label_col, count(*) AS $this->total_col
					FROM {$wpdb->prefix}learndash_user_activity
					WHERE post_id = %d AND activity_type = 'course'".$period_condition."
					GROUP BY activity_status ORDER BY $this->label_col DESC", $course_id);

		return $wpdb->get_results($query);
	}

	protected function format_data($data){
		$colors = array(
			'0' => 'yellow',
			'1' => 'green'
		);

		$data = $this->format_data_with_colors($data, $colors);

		$labels = array(
			'0' => __('In Progress', 'usin'),
			'1' => __('Completed', 'usin')
		);

		return $this->format_data_with_labels($data, $labels);
	}
}