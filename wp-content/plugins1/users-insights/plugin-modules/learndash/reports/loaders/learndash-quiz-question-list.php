<?php

class USIN_LearnDash_Question_List {
	protected $quiz_id;
	protected $questions;

	public function __construct($quiz_id){
		$this->quiz_id = $quiz_id;
		$this->load();
	}

	public function is_empty(){
		return empty($this->questions);
	}

	public function get_ids(){
		return array_map('intval', array_keys($this->questions));
	}

	public function is_question_inactive($id){
		return isset($this->questions[$id]) && $this->questions[$id] == 'inactive';
	}

	/**
	 * When LD quiz data upgrade has been performed:
	 * - there is a post linked to each question via question_pro_id meta
	 * - question ids are stored in ld_quiz_questions post meta of the quiz
	 * - when the question is in trash, question.online = 1, and question post post_status is trash. Depending on
	 * how the question is deleted (from builder or Questions page), ld_quiz_questions might not be updated
	 * - when the question is permanently deleted, question.online = 1 and question post does not exist
	 * When LD quiz data upgrade has not been performed:
	 * - there is no post linked to each question
	 * - when the question is in trash, question.online = 0
	 * - when the question is permanently deleted, question.online = 0
	 */
	protected function load(){
		if(isset($this->questions)){
			return $this->questions;
		}

		global $wpdb;
		$questions = array();

		if($this->is_ld_question_data_upgraded()){

			$active_questions = get_post_meta($this->quiz_id, 'ld_quiz_questions', true);

			if(!empty($active_questions)){
				// Get all published posts' IDs. Sometimes LearnDash does not update ld_quiz_questions when a
				// question is moved to trash
				$args = array('post_type' => 'sfwd-question', 'post_status' => 'publish', 'posts_per_page' => -1,
					'fields' => 'ids', 'post__in' => array_map('intval', array_keys($active_questions)));
				$published_questions = get_posts($args);

				foreach($active_questions as $post_id => $question_id){
					if(in_array(intval($post_id), $published_questions)){
						$questions[$question_id] = 'active';
					}
				}
			}

			if($this->display_inactive_questions()){
				// here we don't set the online = 0 condition, as deleted questions have online set to 1
				// we simply load all questions, and all those that have not been added above are considered inactive
				$query = $wpdb->prepare("SELECT id from {$wpdb->prefix}wp_pro_quiz_question AS q
				INNER JOIN $wpdb->postmeta AS m ON m.meta_value = q.quiz_id AND m.meta_key = 'quiz_pro_id'
				WHERE m.post_id = %d", $this->quiz_id);

				$revision_ids = array_map('intval', $wpdb->get_col($query));

				foreach($revision_ids as $revision_id){
					if(!isset($questions[$revision_id])){
						$questions[$revision_id] = 'inactive';
					}
				}
			}
		}else{
			$inactive_condition = $this->display_inactive_questions() ? '' : ' AND q.online = 1';
			$query = $wpdb->prepare("SELECT id, online from {$wpdb->prefix}wp_pro_quiz_question AS q
				INNER JOIN $wpdb->postmeta AS m ON m.meta_value = q.quiz_id AND m.meta_key = 'quiz_pro_id'
				WHERE m.post_id = %d".$inactive_condition, $this->quiz_id);
			$rows = $wpdb->get_results($query);

			foreach($rows as $row){
				$questions[intval($row->id)] = $row->online == 1 ? 'active' : 'inactive';
			}
		}

		$this->questions = $questions;
		return $this->questions;
	}

	protected function is_ld_question_data_upgraded(){
		return function_exists('learndash_is_data_upgrade_quiz_questions_updated') &&
			learndash_is_data_upgrade_quiz_questions_updated();
	}

	protected function display_inactive_questions(){
		return apply_filters('usin_learndash_display_inactive_questions', false);
	}
}
