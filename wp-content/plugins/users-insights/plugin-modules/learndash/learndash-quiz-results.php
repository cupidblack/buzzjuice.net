<?php

class USIN_LearnDash_Quiz_Results{

	const MODULE_NAME = 'learndash';
	const FIELD_PREFIX = 'quiz_results_';

	private static $quiz_ids;

	public static function init(){
		$quiz_ids = self::get_enabled_quiz_ids();
		if(!empty($quiz_ids)){
			add_filter('usin_user_db_data', array(__CLASS__ , 'format_data'));
		}
	}

	public static function get_fields(){
		$result = array();
		$quizzes = self::get_enabled_quizzes();

		foreach ($quizzes as $id => $name) {
			$result[]=array(
				'name' => sprintf( __('%s Results', 'usin'), $name),
				'id' => self::get_field_id($id),
				'order' => false,
				'show' => false,
				'fieldType' => self::MODULE_NAME,
				'filter' => false,
				'allowHtml' => true,
				'module' => self::MODULE_NAME
			);
		}

		return $result;
	}

	public static function get_field_id($quiz_id){
		return self::FIELD_PREFIX.$quiz_id;
	}

	public static function get_quiz_id_by_field_id($field_id){
		return intval(str_replace(self::FIELD_PREFIX, '', $field_id));
	}

	public static function get_field_ids(){
		$quiz_ids = self::get_enabled_quiz_ids();
		$result = array();
		foreach ($quiz_ids as $quiz_id ) {
			$result[]=self::get_field_id($quiz_id);
		}
		return $result;
	}

	public static function get_enabled_quiz_ids(){
		if(!isset(self::$quiz_ids)){
			$quiz_ids = usin_get_module_setting(self::MODULE_NAME, 'enable_quiz_results');
			self::$quiz_ids = array_map('intval', $quiz_ids);
		}
		
		return self::$quiz_ids;
	}

	public static function get_enabled_quizzes(){
		$result = array();
		$quiz_ids = self::get_enabled_quiz_ids();

		if(empty($quiz_ids)){
			return $result;
		}
		
		$posts = get_posts(array('include' => $quiz_ids, 'post_type' => USIN_LearnDash::QUIZ_POST_TYPE));

		foreach ($posts as $post ) {
			$result[$post->ID] = $post->post_title;
		}

		return $result;
	}

	public static function format_data($data){
		$field_ids = self::get_field_ids();
		
		foreach ($field_ids as $field_id) {
			$quiz_id = self::get_quiz_id_by_field_id($field_id);

			if(!empty($data->$field_id)){
				$attempts = maybe_unserialize($data->$field_id);
				$values = array();

				if(is_array($attempts)){
					foreach ($attempts as $attempt ) {
						if(intval($attempt['quiz']) == $quiz_id){
							if(isset($data->is_exported) && $data->is_exported === true){
								$values[]=$attempt['percentage'].'%';
							}else{
								$values[]= USIN_LearnDash_User_Activity::generate_quiz_result_tag($attempt);
							}
						}
					}
				}

				$data->$field_id = implode(' ', $values);
			}
		}	
		return $data;
	}
}