<?php

class USIN_Learndash_Reports extends USIN_Module_Reports{

	protected $group = 'learndash';
	protected $quiz_group = 'learndash_quizzes';
	protected $course_group = 'learndash_courses';
	protected $ld;

	private $quiz_options;

	public function __construct($ld){
		parent::__construct();
		$this->ld = $ld;
	}

	public function get_group(){
		$registered_user_only_text = __('All of the LearnDash reports reflect only registered user activity', 'usin');

		$groups = array(
			array(
				'id' => $this->group,
				'name' => 'LearnDash',
				'info' => "* $registered_user_only_text",
			)
		);

		$groups[] = array(
			'id' => $this->course_group,
			'name' => sprintf('LearnDash %s', USIN_LearnDash::get_label('courses')),
			'info' => "* $registered_user_only_text",
			'filters' => array(
				array(
					'id' => 'course',
					'name' => sprintf(__('Select a %s', 'usin'), USIN_LearnDash::get_label('course')),
					'type' => 'select_option',
					'options' =>  USIN_LearnDash::get_items(USIN_LearnDash::COURSE_POST_TYPE)
				)
			)
		);

		if($this->has_quizzes()){
			$groups[] = array(
				'id' => $this->quiz_group,
				'name' => 'LearnDash Quizzes',
				'info' => "* $registered_user_only_text",
				'filters' => array(
					array(
						'id' => 'quiz',
						'name' => __('Select a quiz', 'usin'),
						'type' => 'select_option',
						'options' => $this->get_quiz_options()
					)
				)
			);
		}

		return $groups;
	}

	public function get_reports(){
		$reports = array();

		$reports[]= $this->get_active_students_report($this->group);
		$reports[]= $this->get_active_students_report($this->course_group);

		$reports[]= $this->get_course_enrolments_report($this->group);
		$reports[]= $this->get_course_enrolments_report($this->course_group);

		$reports[]= $this->get_course_completions_report($this->group);
		$reports[]= $this->get_course_completions_report($this->course_group);

		$reports[]= new USIN_Standard_Report('learndash_course_students', sprintf(__('Top %s by student number', 'usin'), USIN_LearnDash::get_label('courses')),
			array(
				'group'=>'learndash',
				'type'=>USIN_Report::BAR,
				'filters' => array(
					'options' => array(
						'all' => __('All statuses', 'usin'),
						'completed' => __('Completed', 'usin'),
						'in_progress' => __('In Progress', 'usin')
					),
					'default' => 'all'
				)
			)
		);

		$reports[]= new USIN_Standard_Report('learndash_course_time_to_complete',
			sprintf(__('Time to complete %s', 'usin'), strtolower(USIN_LearnDash::get_label('course'))),
			array(
				'group'=>$this->course_group,
				'type'=>USIN_Report::BAR,
				'info'=>sprintf(__('Time interval from start to completion of the %s. Values are rounded up to the nearest integer.', 'usin'),
					strtolower(USIN_LearnDash::get_label('course'))),
				'filters' => array(
					'options' => array(
						'hours' => __('Hours', 'usin'),
						'days' => __('Days', 'usin'),
						'weeks' => __('Weeks', 'usin'),
						'months' => __('Months', 'usin')
					),
					'default' => 'days'
				)
			)
		);

		$reports[]= new USIN_Standard_Report_With_Period_Filter('learndash_course_student_progress', __('Student progress breakdown', 'usin'),
			array('group'=> $this->course_group,
				'info' => __('Date filter segments the data by start date', 'usin'))
		);


		$groups = USIN_LearnDash::get_items(USIN_LearnDash::GROUP_POST_TYPE);
		
		if(sizeof($groups) > 0){
			$reports[]= new USIN_Standard_Report('learndash_groups', __('Top groups by student number', 'usin'), 
					array(
						'group'=>'learndash',
						'type' => USIN_Report::BAR
					)
				);
		}

		if($this->has_quizzes()){
			$reports[]= $this->get_quiz_attempts_report($this->group);
			$reports[]= $this->get_quiz_attempts_report($this->quiz_group);

			$reports[]= $this->get_quiz_score_report($this->group);
			$reports[]= $this->get_quiz_score_report($this->quiz_group);

			$reports[]= $this->get_quiz_attempts_distribution_report($this->group);
			$reports[]= $this->get_quiz_attempts_distribution_report($this->quiz_group);

			$reports[]= new USIN_Standard_Report_With_Period_Filter('learndash_quiz_correct_questions', __('Most correctly answered questions', 'usin'),
				array('group'=> $this->quiz_group,
					'info' => __('LearnDash might reset question stats after question updating', 'usin'),
					'loader_class' => 'USIN_Learndash_Quiz_Question_Accuracy_Loader',
					'type'=>USIN_Report::BAR,
					'options' => array('answer_type' => 'correct'))
			);

			$reports[]= new USIN_Standard_Report_With_Period_Filter('learndash_quiz_incorrect_questions', __('Most incorrectly answered questions', 'usin'),
				array('group'=> $this->quiz_group,
					'info' => __('LearnDash might reset question stats after question updating', 'usin'),
					'loader_class' => 'USIN_Learndash_Quiz_Question_Accuracy_Loader',
					'type'=>USIN_Report::BAR,
					'options' => array('answer_type' => 'incorrect'))
			);

			$reports[]= new USIN_Standard_Report('learndash_quiz_time_spent', sprintf(__('Time spent on %s', 'usin'), strtolower(USIN_LearnDash::get_label('quiz'))),
				array('group'=> $this->quiz_group, 'type' => USIN_Report::BAR)
			);
		}

		return $reports;

	}

	protected function has_quizzes(){
		$quiz_options = $this->get_quiz_options();
		return !empty($quiz_options);
	}

	protected function get_quiz_options(){
		if(!isset($this->quiz_options)){
			$this->quiz_options = USIN_LearnDash::get_items(USIN_LearnDash::QUIZ_POST_TYPE);
		}
		return $this->quiz_options;
	}

	protected function get_active_students_report($group){
		$id = $this->format_report_id_by_group('learndash_active_students', $group);
		return new USIN_Period_Report($id, __('Active students', 'usin'),
				array('group'=> $group, 'loader_class' => 'USIN_Learndash_Active_Students_Loader'));
	}

	protected function get_course_enrolments_report($group){
		if( $group == $this->group){
			$title = sprintf(__('%s started', 'usin'), USIN_LearnDash::get_label('courses'));
		} else{
			$title = sprintf(__('Students started %s', 'usin'), strtolower(USIN_LearnDash::get_label('course')));
		}
		$id = $this->format_report_id_by_group('learndash_course_enrolments', $group);

		return new USIN_Period_Report($id, $title, array('group'=>$group, 'loader_class' => 'USIN_Learndash_Course_Enrolments_Loader'));
	}

	protected function get_course_completions_report($group){
		if( $group == $this->group){
			$title = sprintf(__('%s completed', 'usin'), USIN_LearnDash::get_label('courses'));
		} else{
			$title = sprintf(__('Students completed %s', 'usin'), strtolower(USIN_LearnDash::get_label('course')));
		}
		$id = $this->format_report_id_by_group('learndash_course_completions', $group);

		return new USIN_Period_Report($id, $title, array('group'=>$group, 'loader_class' => 'USIN_Learndash_Course_Completions_Loader'));
	}

	protected function get_quiz_attempts_report($group){
		$id = $this->format_report_id_by_group('learndash_quiz_attempts', $group);
		return new USIN_Stacked_Period_Report($id, sprintf(__('%s attempts', 'usin'), USIN_LearnDash::get_label('quiz')),
			array('group'=> $group, 'loader_class' => 'USIN_Learndash_Quiz_Attempts_Loader')
		);
	}

	protected function get_quiz_attempts_distribution_report($group){
		$id = $this->format_report_id_by_group('learndash_quiz_attempts_distribution', $group);
		return new USIN_Standard_Report($id, sprintf(__('%s attempts distribution', 'usin'), USIN_LearnDash::get_label('quiz')),
			array('group'=> $group, 'loader_class' => 'USIN_Learndash_Quiz_Attempts_Distribution_Loader')
		);
	}

	protected function get_quiz_score_report($group){
		$id = $this->format_report_id_by_group('learndash_quiz_score', $group);
		return new USIN_Standard_Report($id, sprintf(__('%s score', 'usin'), USIN_LearnDash::get_label('quiz')),
			array('group'=> $group, 'type' => USIN_Report::BAR, 'loader_class' => 'USIN_Learndash_Quiz_Score_Loader')
		);
	}

	protected function format_report_id_by_group($id, $group){
		if($group != $this->group){
			return $id.'_focus';
		}
		return $id;
	}
}