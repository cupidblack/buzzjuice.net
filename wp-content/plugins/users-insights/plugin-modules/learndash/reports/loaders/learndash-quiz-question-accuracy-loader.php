<?php

include_once('learndash-quiz-question-list.php');

class USIN_Learndash_Quiz_Question_Accuracy_Loader extends USIN_Standard_Report_Loader {
	protected $question_list;

	protected function load_data(){
		global $wpdb;

		$quiz_id = $this->get_selected_group_filter('quiz');
		$this->question_list = new USIN_LearnDash_Question_List($quiz_id);

		$where = 'ref.user_id != 0';

		// set the quiz question IDs
		if($this->question_list->is_empty()){
			return array();
		}
		$where .= " AND q.id IN (" . implode(',', $this->question_list->get_ids()) . ")";

		// set the answer type (correct/incorect)
		if($this->report->options['answer_type'] == 'correct'){
			$where .= " AND correct_count > 0 AND incorrect_count = 0";
		}elseif($this->report->options['answer_type'] == 'incorrect'){
			$where .= " AND incorrect_count > 0";
		}else{
			return new WP_Error('usin_invalid_answer_type', __('Invalid answer type', 'usin'));
		}

		// set the period condition
		$filter = $this->getSelectedFilter();
		$where .= USIN_Standard_Report_With_Period_Filter::generate_condition($filter,
			USIN_Query_Helper::get_unix_timestamp_to_local_datetime_select('ref.create_time'));

		// use left join for question posts and postmeta as posts and meta might not exists if the LearnDash question
		// data migration has not been performed
		$query = "SELECT q.title AS $this->label_col, count(*) AS $this->total_col, q.id
					FROM {$wpdb->prefix}wp_pro_quiz_statistic AS s
					INNER JOIN {$wpdb->prefix}wp_pro_quiz_question AS q ON q.id = s.question_id
					INNER JOIN {$wpdb->prefix}wp_pro_quiz_statistic_ref AS ref on ref.statistic_ref_id = s.statistic_ref_id
					WHERE $where
					GROUP BY question_id
					ORDER BY $this->total_col DESC LIMIT $this->max_items";

		$result = $wpdb->get_results($query);

		return $this->set_inactive_colors($result);
	}

	protected function set_inactive_colors($data){
		foreach($data as &$row){
			if($this->question_list->is_question_inactive($row->id)){
				$row->color = '#e7e7e7';
				$row->label .= " (#{$row->id})";
			}
		}

		return $data;
	}
}