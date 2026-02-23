<?php

class USIN_Learndash_Course_Time_To_Complete_Loader extends USIN_Numeric_Field_Loader {
	protected $course_id;
	protected $interval;

	protected function get_default_data(){
		global $wpdb;

		$this->course_id = $this->get_selected_group_filter('course');
		$this->interval = $this->getSelectedFilter();

		if(!$this->course_id || !$this->interval) return;

		$subquery = $this->get_subquery();
		$query = "SELECT COUNT(*) AS $this->total_col, time_spent AS $this->label_col
					FROM ($subquery) AS times_spent GROUP BY time_spent";

		return $wpdb->get_results($query);
	}

	protected function get_subquery(){
		global $wpdb;

		$interval_map = array(
			'hours' => 3600,
			'days' => 86400,
			'weeks' => 604800,
			'months' => 2629746
		);
		$div = $interval_map[$this->interval];

		if(empty($div)){
			throw new Exception("Unknown interval");
		}


		$query = $wpdb->prepare("SELECT ceil((activity_completed - activity_started)/$div) as time_spent
			FROM wp_learndash_user_activity
			WHERE post_id = %d AND activity_type = 'course' AND activity_status = 1
			AND activity_completed IS NOT NULL AND activity_completed != 0", $this->course_id);

		return $query;
	}

	protected function get_data_in_ranges($chunk_size){
		global $wpdb;

		$select = $this->get_select('time_spent', $chunk_size);
		$group_by = $this->get_group_by('time_spent', $chunk_size);
		$subquery = $this->get_subquery();

		$query = "$select FROM ($subquery) AS times_spent $group_by";

		return $wpdb->get_results($query);
	}

	protected function format_data($data){
		$interval_name_map = array(
			'hours' => __('hours', 'usin'),
			'days' => __('days', 'usin'),
			'weeks' => __('weeks', 'usin'),
			'months' => __('months', 'usin')
		);

		foreach($data as &$item){
			$item->label .= ' ' . $interval_name_map[$this->interval];
		}
		return $data;
	}
}