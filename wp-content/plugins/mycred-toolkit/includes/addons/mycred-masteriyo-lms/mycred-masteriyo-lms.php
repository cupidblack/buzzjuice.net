<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (! class_exists( 'myCRED_Toolkit_MasteriyoLMS')) :
	class myCRED_Toolkit_MasteriyoLMS {

		// Instnace
		protected static $_instance = null;

		/**
		 * Setup Instance
		 * @since 1.1.2
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __clone() {
 			_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.5'); 
		}

		/**
		 * Not allowed
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function __wakeup() {
 			_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.5'); 
		}

		/**
		 * Define
		 * @since 1.1.2
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if (! defined($name)) {
				define($name, $value);
			} elseif (! $definable && defined($name)) {
				_doing_it_wrong('myCRED_Toolkit_MasteriyoLMS->define()', 'Could not define: ' . esc_html($name) . ' as it is already defined somewhere else!', '1.5');
			}
		}

		/**
		 * Require File
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$is_masteriyolms_active = is_plugin_active('learning-management-system/lms.php');
			$is_mycred_active    = is_plugin_active('mycred/mycred.php');

			add_action('admin_init', array( $this, 'prevent_mycred_masteriyolms_integration_activation' ));

			if ( $is_masteriyolms_active && $is_mycred_active ) {
				$this->includes();
				add_filter('mycred_setup_hooks', array( $this, 'register_hooks' ));
				add_filter('mycred_all_references', array( $this, 'setup_references' ));
				add_action('all', function($hook) {
					if (strpos($hook, 'ms_lms') !== false) {
						error_log($hook);
					}
				});
			}
			
			if (! $is_mycred_active) {
				add_action('admin_notices', array( $this, 'mycred_inactive_admin_notice' ));
			}
		}

		public function prevent_mycred_masteriyolms_integration_activation() {

			if ( ! is_plugin_active( 'mycred/mycred.php' ) && ! is_plugin_active( 'learning-management-system/lms.php' ) ) {
				deactivate_plugins( 'mycred-masteriyo-lms/mycred-masteriyo-lms.php' );
				wp_die( 'Please activate myCred plugin and Masteriyo LMS plugin before activating myCred – Masteriyo LMS Integration.' );
			}
			elseif ( ! is_plugin_active( 'mycred/mycred.php' ) ) {
				deactivate_plugins( 'mycred-masteriyo-lms/mycred-masteriyo-lms.php' );
				wp_die( 'Please activate myCred plugin before activating myCred – Masteriyo LMS Integration.' );
			}
			elseif ( ! is_plugin_active( 'learning-management-system/lms.php' ) ) {
				deactivate_plugins( 'mycred-masteriyo-lms/mycred-masteriyo-lms.php' );
				wp_die( 'Please activate Masteriyo LMS plugin before activating myCred – Masteriyo LMS Integration.' );
			}
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define('MYCRED_MASTERIYOLMS_SLUG', 'mycred-masteriyo-lms');
			$this->define('MYCRED_MASTERIYOLMS', __FILE__);
			$this->define('MYCRED_MASTERIYOLMS_ROOT', plugin_dir_path( MYCRED_MASTERIYOLMS));
			$this->define('MYCRED_MASTERIYOLMS_INC_DIR', MYCRED_MASTERIYOLMS_ROOT . 'includes/');
			$this->define('MYCRED_MASTERIYOLMS_TEMP_DIR', MYCRED_MASTERIYOLMS_ROOT . 'templates/');
		}

		/**
		 * Include Plugin Files
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() {
			
			$this->file(MYCRED_MASTERIYOLMS_INC_DIR . 'mycred-masteriyolms-course.php');
			$this->file(MYCRED_MASTERIYOLMS_INC_DIR . 'mycred-masteriyolms-quiz.php');
			$this->file(MYCRED_MASTERIYOLMS_INC_DIR . 'mycred-masteriyolms-lesson.php');
		}

		public function register_hooks( $installed ) {
			$installed['mycred_masteriyolms_course'] = array(
				'title'       => __('Courses (Masteriyo LMS)', 'mycred-toolkit'),
				'description' => __('Reward for completing course', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_MasteriyoLMS_Course' )
			);
			$installed['mycred_masteriyolms_quiz'] = array(
				'title'       => __('Quiz (Masteriyo LMS)', 'mycred-toolkit'),
				'description' => __('Reward for completing quiz', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_MasteriyoLMS_Quiz' )
			);
			$installed['mycred_masteriyolms_lesson'] = array(
				'title'       => __('Lesson (Masteriyo LMS)', 'mycred-toolkit'),
				'description' => __('Reward for completing lesson', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_MasteriyoLMS_Lesson' )
			);

			return $installed;
		}

		/**
		 * Setup references so they show on the Edit Points log screen
		 *
		 * @param $references
		 *
		 * @return mixed
		 */
		function setup_references( $references ) {

			$references['masteriyo_course_completed']           = 'MasteriyoLMS: Student completes a course';
			$references['masteriyo_lesson_completed']           = 'MasteriyoLMS: Student completes a lesson';
			$references['masteriyo_quiz_completed']             = 'MasteriyoLMS: Student completes a quiz';

			return $references;
		}

		/**
		 * Add an admin notice when the myCRED plugin is not currently active
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_inactive_admin_notice() {
			$class   = 'notice notice-error';
			$message = __('myCRED is currently not active. Please activate it so that you can continue to use this MasteriyoLMS extension.', 'mycred-toolkit');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
		}
	}
endif;

function mycred_masteriyolms_plugin() {

	return myCRED_Toolkit_MasteriyoLMS::instance();
}
add_action('mycred_init', 'mycred_masteriyolms_plugin');
