<?php

class USIN_LearnDash_Course_Analytics{

	const MODULE_NAME = 'learndash';
	const STARTED_FIELD_PREFIX = 'ld_course_started_on_';
	const COMPLETED_FIELD_PREFIX = 'ld_course_completed_on_';
	const DB_TABLE_PREFIX = 'ld_course_analytics_';

	private static $course_ids;

	public static function get_fields(){
		$result = array();
		$courses = self::get_enabled_courses();

		foreach ($courses as $course_id => $course_name) {
			$result[]=array(
				'name' => sprintf( __('%s Started', 'usin'), $course_name),
				'id' => self::get_started_field_id($course_id),
				'order' => 'DESC',
				'show' => false,
				'fieldType' => self::MODULE_NAME,
				'filter' => array(
					'type' => 'date'
				),
				'module' => self::MODULE_NAME
			);

			$result[]=array(
				'name' => sprintf( __('%s Completed', 'usin'), $course_name),
				'id' => self::get_completed_field_id($course_id),
				'order' => 'DESC',
				'show' => false,
				'fieldType' => self::MODULE_NAME,
				'filter' => array(
					'type' => 'date'
				),
				'module' => self::MODULE_NAME
			);
		}

		return $result;
	}

	public static function get_started_field_id($course_id){
		return self::STARTED_FIELD_PREFIX.$course_id;
	}

	public static function get_completed_field_id($course_id){
		return self::COMPLETED_FIELD_PREFIX.$course_id;
	}

	public static function get_db_table_alias($course_id){
		return self::DB_TABLE_PREFIX.$course_id;
	}

	public static function get_course_id_by_field_id($field_id){
		return intval(str_replace(array(self::STARTED_FIELD_PREFIX, self::COMPLETED_FIELD_PREFIX), '', $field_id));
	}

	public static function get_course_id_by_db_table($db_table_alias){
		return intval(str_replace(self::DB_TABLE_PREFIX, '', $db_table_alias));
	}

	public static function is_course_started_field($field_id){
		return strpos($field_id, self::STARTED_FIELD_PREFIX) !== false;
	}

	public static function is_course_completed_field($field_id){
		return strpos($field_id, self::COMPLETED_FIELD_PREFIX) !== false;
	}

	public static function is_course_analytics_table($table){
		return strpos($table, self::DB_TABLE_PREFIX) !== false;
	}

	public static function get_field_ids(){
		$course_ids = self::get_enabled_course_ids();
		$result = array();
		foreach ($course_ids as $course_id ) {
			$result[]=self::get_field_id($course_id);
		}
		return $result;
	}

	public static function get_enabled_course_ids(){
		if(!isset(self::$course_ids)){
			$course_ids = usin_get_module_setting(self::MODULE_NAME, 'enable_course_analytics');
			self::$course_ids = array_map('intval', $course_ids);
		}
		
		return self::$course_ids;
	}

	public static function get_enabled_courses(){
		$result = array();
		$course_ids = self::get_enabled_course_ids();
		
		if(empty($course_ids)){
			return $result;
		}
		
		$posts = get_posts(array('include' => $course_ids, 'post_type' => USIN_LearnDash::COURSE_POST_TYPE));

		foreach ($posts as $post ) {
			$result[$post->ID] = $post->post_title;
		}

		return $result;
	}
}
