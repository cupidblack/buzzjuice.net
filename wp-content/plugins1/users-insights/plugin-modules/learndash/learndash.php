<?php

if(!defined( 'ABSPATH' )){
	exit;
}

/**
 * LearnDash support for Users Insights.
 * Provides data and filters about the LearnDash user activity, such as
 * courses/lessons completed and quizes passed.
 */
class USIN_LearnDash extends USIN_Plugin_Module{
	
	protected $module_name = 'learndash';
	protected $plugin_path = 'sfwd-lms/sfwd_lms.php';
	protected static $statuses = null;
	protected $required_version = '2.3';
	protected $upgrade_notice_set = false;

	const COURSE_POST_TYPE = 'sfwd-courses';
	const QUIZ_POST_TYPE = 'sfwd-quiz';
	const GROUP_POST_TYPE = 'groups';

	
	/**
	 * Overwrites the parent method to also check if the required version of
	 * LearnDash is installed.
	 */
	protected function is_module_active(){
		return parent::is_module_active() && $this->is_required_version_installed();
	}
	
	protected function is_required_version_installed(){
		if(defined('LEARNDASH_VERSION') && version_compare(LEARNDASH_VERSION, $this->required_version, '<')){
			add_action('admin_notices', array($this, 'add_upgrade_notice'));
			return false;
		}
		return true;
	}
	
	public function init(){
		new USIN_LearnDash_Query();
		new USIN_LearnDash_User_Activity($this->module_name);

		USIN_LearnDash_Quiz_Results::init();
	}

	protected function init_reports(){
		new USIN_LearnDash_Reports($this);
	}
	
	
	public function add_upgrade_notice(){
		if(!$this->upgrade_notice_set){
			$message = __( 'The <b>LearnDash Module</b> of Users Insights requires LearnDash 2.3 or newer. 
				Please update LearnDash to the latest version and run the data upgrade as explained in 
				<a href="https://usersinsights.com/introducing-learndash-integration/">this article</a>.', 'usin' );
			printf( '<div class="notice notice-error"><p>%s</p></div>', $message );
			
			$this->upgrade_notice_set = true;
		}
	}


	public function register_module(){
		return array(
			'id' => $this->module_name,
			'name' => __('LearnDash', 'usin'),
			'desc' => __('Detects the LearnDash user activity and makes it available in the user table and filters.', 'usin'),
			'allow_deactivate' => true,
			'buttons' => array(
				array('text'=> __('Learn More', 'usin'), 'link'=>'https://usersinsights.com/learndash-search-filter-user-data/', 'target'=>'_blank')
			),
			'settings' => array(
				'enable_course_analytics' => array(
					'name' => __('Enable course columns for courses', 'usin'),
					'type' => USIN_Settings_Field::TYPE_CHECKBOXES,
					'options' => $this->is_modules_page() ? self::get_items(self::COURSE_POST_TYPE, true) : array(),
					'desc' => __('For each selected course the "course started" and "course completed"
					columns will be added to the user table, showing the course start and end date for each user.', 'usin')
				),
				'enable_quiz_results' => array(
					'name' => __('Enable quiz results columns for quizzes', 'usin'),
					'type' => USIN_Settings_Field::TYPE_CHECKBOXES,
					'options' => $this->is_modules_page() ? self::get_items(self::QUIZ_POST_TYPE, true) : array(),
					'desc' => __('For each selected quiz a new column will be available in the table showing the results of all attempts for each user.', 'usin')
				)
			),
			'active' => false
		);
	}

	/**
	 * Registers the module fields
	 * @return array the LearnDash module fields
	 */
	public function register_fields(){
		$fields = array();
		
		//register the numeric fields
		$numeric_fields = array(
			array(
				'name' => sprintf( _x( '%s Completed', 'Lessons Completed Label', 'usin' ), self::get_label( 'lessons' ) ), 
				'id' => 'ld_lessons_completed'
			),
			array(
				'name' => sprintf( _x( '%s Completed', 'Topics Completed Label', 'usin' ), self::get_label( 'topics' ) ),
				'id' => 'ld_topics_completed'
			),
			array(
				'name' => sprintf( _x( '%s Completed', 'Courses Completed Label', 'usin' ), self::get_label( 'courses' ) ), 
				'id' => 'ld_courses_completed'
			),
			array(
				'name' => sprintf( _x( '%s In Progress', 'Courses Started Label', 'usin' ), self::get_label( 'courses' ) ), 
				'id' => 'ld_courses_in_progress'
			),
			array(
				'name' => sprintf(__('%s Attempts', 'usin'), self::get_label('quiz')),
				'id' => 'ld_quiz_attempts'
			),
			array(
				'name' => sprintf(__('%s Passes', 'usin'), self::get_label('quiz')), 
				'id' => 'ld_quiz_passes'
			)
		);
		
		foreach ($numeric_fields as $field ) {
			$fields[]= $this->build_numeric_field($field['name'], $field['id']);
		}
		
		
		$fields[]=array(
			'name' => __('Last Activity', 'usin'),
			'id' => 'ld_last_activity',
			'order' => 'DESC',
			'show' => true,
			'fieldType' => 'general',
			'filter' => array(
				'type' => 'date'
			),
			'module' => $this->module_name
		);
		
		$course_options = self::get_items(self::COURSE_POST_TYPE);
		$quiz_options = self::get_items(self::QUIZ_POST_TYPE);
		
		//register the filter fields
		$filter_fields = array(
			array('name'=>__('Has engaged in course', 'usin'), 'id' => 'ld_has_enrolled_course', 'options' => $course_options),
			array('name'=>__('Has not engaged in course', 'usin'), 'id' => 'ld_has_not_enrolled_course', 'options' => $course_options),
			array('name'=>__('Has completed course', 'usin'), 'id' => 'ld_has_completed_course', 'options' => $course_options),
			array('name'=>__('Has not completed course', 'usin'), 'id' => 'ld_has_not_completed_course', 'options' => $course_options),
			array('name'=>__('Has passed quiz', 'usin'), 'id' => 'ld_has_passed_quiz', 'options' => $quiz_options),
			array('name'=>__('Has not passed quiz', 'usin'), 'id' => 'ld_has_not_passed_quiz', 'options' => $quiz_options)
		);
			
		foreach ($filter_fields as $field ) {
			$fields[]= $this->build_filter_field($field['name'], $field['id'], $field['options']);
		}
		
		//group field
		$group_opions = self::get_items(self::GROUP_POST_TYPE);
		if(sizeof($group_opions)>0){
			$fields []=array(
				'name' => __('Group', 'usin'),
				'id' => 'ld_group',
				'order' => false,
				'show' => false,
				'fieldType' => $this->module_name,
				'filter' => array(
					'type' => 'include_exclude_with_nulls',
					'options' => $group_opions
				),
				'module' => $this->module_name
			);
		}

		//quiz results field
		$fields = array_merge($fields, USIN_LearnDash_Quiz_Results::get_fields());
		//course analytics fields
		$fields = array_merge($fields, USIN_LearnDash_Course_Analytics::get_fields());


		return $fields;
	}
	
	/**
	 * Retrieves the label of an item. If a custom label has been set in the
	 * LeanDash options, the label will be returned. Otherwise the default name
	 * will be returned.
	 * @param  string $item item name such as course, quiz, etc.
	 * @return string       the label
	 */
	public static function get_label($item){
		$label = '';
		
		if(method_exists('LearnDash_Custom_Label', 'get_label')){
			$label = LearnDash_Custom_Label::get_label($item);
		}
		
		if(empty($label)){
			$label = ucfirst($item);
		}
		
		return $label;
	}
	
	public static function get_items($post_type, $assoc_res = false){
		$options = array();
		$post_status = array('publish');
		if(current_user_can('read_private_posts')){
			$post_status [] = 'private';
		}
		$posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => $post_status ) );

		foreach ($posts as $post) {
			if($assoc_res){
				$options[$post->ID] = $post->post_title;
			}else{
				$options[] = array('key'=>$post->ID, 'val'=>$post->post_title);
			}
		}

		return $options;
	}

	protected function build_numeric_field($name, $id){
		return array(
			'name' => $name,
			'id' => $id,
			'order' => 'DESC',
			'show' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'number',
				'disallow_null' => true
			),
			'module' => $this->module_name
		);
	}
	
	protected function build_filter_field($name, $id, $options){
		return array(
			'name' => $name,
			'id' => $id,
			'order' => 'ASC',
			'show' => false,
			'hideOnTable' => true,
			'fieldType' => $this->module_name,
			'filter' => array(
				'type' => 'select_option',
				'options' => $options
			),
			'module' => $this->module_name
		);
	}
}

new USIN_LearnDash();