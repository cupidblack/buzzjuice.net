<?php

class USIN_Learndash_Quiz_Time_Spent_Loader extends USIN_Numeric_Field_Loader {
	protected $quiz_pro_id;

	protected function get_default_data(){
		global $wpdb;

		$quiz_id = $this->get_selected_group_filter('quiz');
		$quiz_pro_id = get_post_meta($quiz_id, 'quiz_pro_id', true);

		if(!$quiz_pro_id) return;

		$this->quiz_pro_id = intval($quiz_pro_id);

		$subquery = $this->get_subquery();
		$query = "SELECT COUNT(*) AS $this->total_col, time_spent AS $this->label_col
					FROM ($subquery) AS times_spent GROUP BY time_spent";

		return $wpdb->get_results($query);
	}

	protected function get_subquery(){
		global $wpdb;

		return $wpdb->prepare("SELECT CEIL(SUM(question_time)/60) AS time_spent
					FROM {$wpdb->prefix}wp_pro_quiz_statistic AS s
         			INNER JOIN {$wpdb->prefix}wp_pro_quiz_statistic_ref AS ref on ref.statistic_ref_id = s.statistic_ref_id
					WHERE quiz_id = %d AND ref.user_id != 0
					GROUP BY s.statistic_ref_id", $this->quiz_pro_id);
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
		foreach($data as &$item){
			$item->label .= 'min';
		}
		return $data;
	}
}