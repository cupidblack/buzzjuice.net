<?php 
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Check Page
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'is_mycred_hook_page' ) ) :
	function is_mycred_hook_page( $page ) {
		return ( strpos( $page, 'mycred' ) !== false && strpos( $page, 'hook' ) !== false );
	}
endif;

if ( ! function_exists( 'custom_limit' ) ) :
	function custom_limit() {
		return array(
			'x' => __('No limit', 'mycred-toolkit'),
			'd' => __('/ Day', 'mycred-toolkit'),
			'w' => __('/ Week', 'mycred-toolkit'),
			'm' => __('/ Month', 'mycred-toolkit')
		);
	} 
endif;


if ( ! function_exists( 'mycred_specific_course_for_quiz' ) ) :
	function mycred_specific_course_for_quiz() {

		$tutorlms_course_id = isset($_POST['quiz']) ? absint($_POST['quiz']) : 0;

		$course_id = intval( $tutorlms_course_id );

		if ($_POST['quiz'] == 0 ) {

			global $wpdb;
		  
			  $quizzes = array();                   
			
			$contents =  $wpdb->get_results( $wpdb->prepare(
				
			 "SELECT quiz.ID, quiz.post_title
             FROM wp_posts course, wp_posts topic, wp_posts quiz 
             WHERE topic.ID = quiz.post_parent AND course.ID = topic.post_parent AND quiz.post_type = 'tutor_quiz' AND course.post_status = 'publish';
             ",
			));
			
			foreach ($contents as $content) {
				
				$quiz_title = $content->post_title;
				
				$quiz_id = $content->ID;
				
				$quizzes[] = array( $quiz_id, $quiz_title );

			}

			$quizzes = json_encode( $quizzes );

			echo json_encode($quizzes);
			wp_die();
		}

		$quizzes = array();
		
		$course_contents = tutils()->get_course_contents_by_id( $course_id );
		 
		if (tutils()->count($course_contents)) {
			
			foreach ($course_contents as $content) {
				$quiz_title = $content->post_title;
				 $quiz_id = $content->ID;
				if ($content->post_type === 'tutor_quiz') {
					
					$quizzes[] = array( $quiz_id, $quiz_title );

				}
			}
		}

		   $quizzes = json_encode( $quizzes );

		echo json_encode($quizzes);

		wp_die();
	}
endif;


if ( ! function_exists( 'mycred_specific_course_for_lesson' ) ) :
	function mycred_specific_course_for_lesson() {

		$tutorlms_course_id = isset($_POST['lesson']) ? absint($_POST['lesson']) : 0;

		$course_id = intval( $tutorlms_course_id  );

		if ( $_POST['lesson'] == 0 ) {

			global $wpdb;
		  
			  $lessons = array();                   
			
			$contents =  $wpdb->get_results( $wpdb->prepare(
				
			 "SELECT lesson.ID, lesson.post_title
             FROM wp_posts course, wp_posts topic, wp_posts lesson 
             WHERE topic.ID = lesson.post_parent AND course.ID = topic.post_parent AND lesson.post_type = 'lesson' AND course.post_status = 'publish';
             ",
			));
			
			foreach ($contents as $content) {
				
				$lesson_title = $content->post_title;
				
				$lesson_id = $content->ID;
				
				$lessons[] = array( $lesson_id, $lesson_title );

			}

			$lessons = json_encode( $lessons );

			echo json_encode($lessons);

			wp_die();
		}

		$lessons = array();
		
		$course_contents = tutils()->get_course_contents_by_id( $course_id );
		 
		if (tutils()->count($course_contents)) {
			
			foreach ($course_contents as $content) {
				$lesson_title = $content->post_title;
				 $lesson_id = $content->ID;
				if ( $content->post_type === 'lesson' ) {
					
					$lessons[] = array( $lesson_id, $lesson_title );

				}
			}
		}

		   $lessons = json_encode( $lessons );

		echo json_encode($lessons);

		wp_die();
	}
endif;

if ( !function_exists( 'mycred_tutor_lms_get_course_content' ) ) :
	function mycred_tutor_lms_get_course_content( $post_type, $course_id ) {
	  global $wpdb;

		if ($course_id == 0) {
		  $contents =  $wpdb->get_results( 
		  $wpdb->prepare(
			"SELECT quiz.ID, quiz.post_title
        FROM {$wpdb->posts} as course, {$wpdb->posts} as topic, {$wpdb->posts} as quiz 
        WHERE topic.ID = quiz.post_parent AND course.ID = topic.post_parent AND quiz.post_type = '%s' AND course.post_status = 'publish';
        ",
			$post_type
		  )
		);
		} else {
	  $contents =  $wpdb->get_results( 
	  $wpdb->prepare(
		"SELECT quiz.ID, quiz.post_title
      FROM {$wpdb->posts} as course, {$wpdb->posts} as topic, {$wpdb->posts} as quiz 
      WHERE topic.ID = quiz.post_parent AND course.ID = topic.post_parent AND quiz.post_type = '%s' AND course.post_status = 'publish' AND course.ID = %d;
      ",
		$post_type,
		$course_id 
	  )
	);
		}
			  
   

 return $contents;
	}
endif;
