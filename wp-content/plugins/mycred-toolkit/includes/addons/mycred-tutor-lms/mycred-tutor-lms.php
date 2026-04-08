<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCred_Toolkit_Tutor_lMS_Core' ) ) :
	final class myCred_Toolkit_Tutor_lMS_Core {

	

		// Instnace
		protected static $_instance = null;

		/**
		 * Setup Instance
		 * @since 1.0.4
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Define
		 * @since 1.0.4
		 * @version 1.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File
		 * @since 1.0.4
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0.4
		 * @version 1.0
		 */
		public function __construct() {
			$this->define_constants();
			$this->init();
			$this->includes();
		}

		/**
		 * Initialize
		 * @since 1.0
		 * @version 1.0
		 */
		private function init() {

			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );

			if ( is_plugin_active('mycred/mycred.php') && is_plugin_active('tutor/tutor.php') ) {

				add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ));
				add_filter( 'mycred_setup_hooks', array( $this, 'mycred_register_tutor_lms' ), 10 , 2 );
				add_action( 'mycred_load_hooks', array( $this, 'mycred_load_tutor_lms_hook' ));
				add_filter( 'mycred_all_references', array( $this, 'tutor_lms_register_refrences' ) );
				add_action( 'wp_ajax_mycred_specific_course_for_quiz', 'mycred_specific_course_for_quiz' );
				add_action( 'wp_ajax_nopriv_mycred_specific_course_for_quiz', 'mycred_specific_course_for_quiz' );
				add_action( 'wp_ajax_mycred_specific_course_for_lesson', 'mycred_specific_course_for_lesson' );
				add_action( 'wp_ajax_nopriv_mycred_specific_course_for_lesson', 'mycred_specific_course_for_lesson' );

			}

			add_action( 'admin_notices', array( $this, 'mycred_tutor_lms_plugin_notices' ) );
		}

		/**
		 * Define Constants
		 * @since 1.1.1
		 * @version 1.0
		 */
		private function define_constants() {

			
			$this->define( 'mycred_tutor_lms_SLUG', 'mycred-tutor-lms' );
			$this->define( 'mycred_tutor_lms', __FILE__ );
			$this->define( 'mycred_tutor_lms_ROOT_DIR', plugin_dir_path( mycred_tutor_lms ) );
			$this->define( 'mycred_tutor_lms_ASSETS_DIR_URL', plugin_dir_url( mycred_tutor_lms ) . 'assets/' );
			$this->define( 'mycred_tutor_lms_INCLUDES_DIR', mycred_tutor_lms_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 * @since 1.1.1
		 * @version 1.0
		 */

		public function includes() {

			$this->file( mycred_tutor_lms_INCLUDES_DIR . 'function.php' );
		}

		/**
		 * Include Hook Files
		 * @since 1.1.1
		 * @version 1.0
		 */
		public function mycred_load_tutor_lms_hook() {

			// Quiz
			$this->file( mycred_tutor_lms_INCLUDES_DIR . 'mycred-complete-quiz.php' );
			$this->file( mycred_tutor_lms_INCLUDES_DIR . 'mycred-pass-quiz.php' );  
			$this->file( mycred_tutor_lms_INCLUDES_DIR . 'mycred-fail-quiz.php' );
			
			//Course
			$this->file( mycred_tutor_lms_INCLUDES_DIR . 'mycred-enroll-course.php' );
			$this->file( mycred_tutor_lms_INCLUDES_DIR . 'mycred-complete-course.php' );

			//Lesson
			$this->file( mycred_tutor_lms_INCLUDES_DIR . 'mycred-complete-lesson.php' );
		}

		//public function load_assets() {}

		public function admin_script( $hook ) {    
		
			if ( is_mycred_hook_page( $hook ) ) {

				wp_enqueue_style( 'mycred-tutorlms-style', mycred_tutor_lms_ASSETS_DIR_URL . 'css/style.css' );
				wp_enqueue_script( 'mycred-tutorlms-script', mycred_tutor_lms_ASSETS_DIR_URL . 'js/script.js', array( 'jquery' ) );
				
			}
		}

		public function mycred_register_tutor_lms( $installed ) {

			// Remove a specific hook
			//unset( $installed['site_visit'] );
		
			// Add a custom hook
			// General
			$installed['tutor_lms_quiz_pass'] = array(
				'title'        => __('Passing a Quiz (Tutor LMS)', 'mycred-toolkit'),
				'description'  => __('Optional hook description. Must be defined but can be empty.', 'mycred-toolkit'),
				'callback'     => array( 'mycred_tutor_lms_Geneal_Quiz_Hook_Class' ),
			);

			$installed['tutor_lms_enroll_courses'] = array(
				'title'        => __('Enrolling a Course (Tutor LMS)', 'mycred-toolkit'),
				'description'  => __('Optional hook description. Must be defined but can be empty.', 'mycred-toolkit'),
				'callback'     => array( 'mycred_tutor_lms_Geneal_Course_Hook_Class' ),
			);

			$installed['tutor_lms_complete_quiz'] = array(
				'title'        => __('Completing a Quiz (Tutor LMS)', 'mycred-toolkit'),
				'description'  => __('Optional hook description. Must be defined but can be empty.', 'mycred-toolkit'),
				'callback'     => array( 'mycred_tutor_lms_Geneal_Lesson_Hook_Class' ),
			);

			// Add a custom hook
			// Specific 
			$installed['tutor_lms_fail_quiz'] = array(
				'title'        => __('Failing a Quiz (Tutor LMS)', 'mycred-toolkit'),
				'description'  => __('Optional hook description. Must be defined but can be empty.', 'mycred-toolkit'),
				'callback'     => array( 'mycred_tutor_lms_Specific_Quiz_Hook_Class' )
			);

			$installed['tutor_lms_complete_courses'] = array(
				'title'        => __('Completing a Course (Tutor LMS)', 'mycred-toolkit'),
				'description'  => __('Optional hook description. Must be defined but can be empty.', 'mycred-toolkit'),
				'callback'     => array( 'mycred_tutor_lms_Specific_Course_Hook_Class' )
			);

			$installed['tutor_lms_complete_lesson'] = array(
				'title'        => __('Completing a Lesson (Tutor LMS)', 'mycred-toolkit'),
				'description'  => __('Optional hook description. Must be defined but can be empty.', 'mycred-toolkit'),
				'callback'     => array( 'mycred_tutor_lms_Specific_Lesson_Hook_Class' )
			);

			return $installed;
		}


		public function tutor_lms_register_refrences( $list ) {
			
			//General quiz reference
			$list['tutor_lms_quiz_complete']  = __('Completing Quiz', 'mycred-toolkit');
			$list['tutor_lms_quiz_pass']  = __('Passing Quiz', 'mycred-toolkit');
			$list['tutor_lms_quiz_fail']  = __('Fail Quiz', 'mycred-toolkit');
			
			//General course reference
			$list['tutor_lms_complete_course'] = __('Completing Course', 'mycred-toolkit');
			$list['tutor_lms_enroll_course'] = __('Enroll Course', 'mycred-toolkit');
			
			//General lesson reference
			$list['tutor_lms_complete_lesson'] = __('Completing lesson', 'mycred-toolkit');

			return $list;
		}


		public function mycred_tutor_lms_plugin_notices() {
 
			$msg = __( 'need to be active and installed to use myCred Tutor LMS plugin.', 'mycred-toolkit' );
			
			if ( !is_plugin_active('mycred/mycred.php') ) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/mycred/">%1$s</a> %2$s</p></div>', esc_html_e( 'myCred', 'mycred-toolkit' ), esc_html( $msg ) );
			}
		}
	}
endif;


function mycred_toolkit_tutor_lms_core() {

	return myCred_Toolkit_Tutor_lMS_Core::instance();
}

mycred_toolkit_tutor_lms_core();
