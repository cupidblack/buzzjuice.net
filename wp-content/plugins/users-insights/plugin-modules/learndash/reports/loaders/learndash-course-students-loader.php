<?php

class USIN_Learndash_Course_Students_Loader extends USIN_Standard_Report_Loader {

	protected function load_data(){
		$data = $this->load_db_data();
		$courses = USIN_LearnDash::get_items(USIN_LearnDash::COURSE_POST_TYPE, true);

		return $this->match_ids_to_names($data, $courses);
	}

	protected function load_db_data(){
		global $wpdb;
		
		$filter = $this->getSelectedFilter();
		$condition = '';

		if($filter == 'completed'){
			$condition .= ' WHERE status = 1';
		}elseif($filter == 'in_progress'){
			$condition .= ' WHERE status = 0';
		}

		$subquery = USIN_LearnDash_Query::get_course_activity_query();
		$query = "SELECT COUNT(*) as $this->total_col, post_id as $this->label_col FROM ($subquery) AS course_activity
			 $condition GROUP BY post_id LIMIT $this->max_items";

		return $wpdb->get_results( $query );
	}
}