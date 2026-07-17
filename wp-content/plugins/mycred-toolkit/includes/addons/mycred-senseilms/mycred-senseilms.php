<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (! class_exists( 'myCRED_Toolkit_SenseiLMS')) :
	class myCRED_Toolkit_SenseiLMS {

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
				_doing_it_wrong('myCRED_Toolkit_SenseiLMS->define()', 'Could not define: ' . esc_html($name) . ' as it is already defined somewhere else!', '1.5');
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

			if (! function_exists('is_plugin_active')) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$is_senseilms_active = is_plugin_active('sensei-lms/sensei-lms.php');
			$is_mycred_active    = is_plugin_active('mycred/mycred.php');

			add_action('admin_init', array( $this, 'prevent_mycred_senseilms_integration_activation' ));

			if ($is_senseilms_active && $is_mycred_active) {
				$this->includes();
				add_filter('mycred_setup_hooks', array( $this, 'register_hooks' ));
				add_filter('mycred_all_references', array( $this, 'setup_references' ));
				add_action('all', function($hook) {
					if (strpos($hook, 'sensei_lms') !== false) {
						error_log($hook);
					}
				});
			}
			
			if (! $is_mycred_active) {
				add_action('admin_notices', array( $this, 'mycred_inactive_admin_notice' ));
			}
		}

		public function prevent_mycred_senseilms_integration_activation() {

			if ( ! is_plugin_active( 'mycred/mycred.php' ) && ! is_plugin_active( 'sensei-lms/sensei-lms.php' ) ) {
				deactivate_plugins( 'mycred-senseilms/mycred-senseilms.php' );
				wp_die( 'Please activate myCred plugin and Sensei LMS plugin before activating myCred – Sensei LMS Integration.' );
			}
			elseif ( ! is_plugin_active( 'mycred/mycred.php' ) ) {
				deactivate_plugins( 'mycred-senseilms/mycred-senseilms.php' );
				wp_die( 'Please activate myCred plugin before activating myCred – Sensei LMS Integration.' );
			}
			elseif ( ! is_plugin_active( 'sensei-lms/sensei-lms.php' ) ) {
				deactivate_plugins( 'mycred-senseilms/mycred-senseilms.php' );
				wp_die( 'Please activate Sensei LMS plugin before activating myCred – Sensei LMS Integration.' );
			}
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define('MYCRED_SENSIELMS_SLUG', __FILE__);
			$this->define('MYCRED_SENSIELMS_SLUG_ROOT', plugin_dir_path( MYCRED_SENSIELMS_SLUG));
			$this->define('MYCRED_SENSIELMS_SLUG_INC_DIR', MYCRED_SENSIELMS_SLUG_ROOT . 'includes/');
			$this->define('MYCRED_SENSIELMS_SLUG_TEMP_DIR', MYCRED_SENSIELMS_SLUG_ROOT . 'templates/');
		}

		/**
		 * Include Plugin Files
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() {
			
			$this->file(MYCRED_SENSIELMS_SLUG_INC_DIR . 'mycred-senseilms-course.php');
			$this->file(MYCRED_SENSIELMS_SLUG_INC_DIR . 'mycred-senseilms-quiz.php');
			$this->file(MYCRED_SENSIELMS_SLUG_INC_DIR . 'mycred-senseilms-lesson.php');
		}

		public function register_hooks( $installed ) {
			$installed['mycred_senseilms_course'] = array(
				'title'       => __('Courses (Sensei LMS)', 'mycred-toolkit'),
				'description' => __('Reward for completing course', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_SenseiLMS_Course' )
			);
			$installed['mycred_senseilms_quiz'] = array(
				'title'       => __('Quiz (Sensei LMS)', 'mycred-toolkit'),
				'description' => __('Reward for completing quiz', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_SenseiLMS_Quiz' )
			);
			$installed['mycred_senseilms_lesson'] = array(
				'title'       => __('Lesson (Sensei LMS)', 'mycred-toolkit'),
				'description' => __('Reward for completing lesson', 'mycred-toolkit'),
				'callback'    => array( 'myCRED_SenseiLMS_Lesson' )
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

			$references['senseilms_course_completed']           = 'Sensei LMS: Student completes a course';
			$references['senseilms_lesson_completed']           = 'Sensei LMS: Student completes a lesson';
			$references['senseilms_quiz_completed']             = 'Sensei LMS: Student completes a quiz';

			return $references;
		}

		/**
		 * Add an admin notice when the myCRED plugin is not currently active
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_inactive_admin_notice() {
			$class   = 'notice notice-error';
			$message = __('myCRED is currently not active. Please activate it so that you can continue to use this Sensei LMS extension.', 'mycred-toolkit');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
		}
	}
endif;

function mycred_senseilms_plugin() {

	return myCRED_Toolkit_SenseiLMS::instance();
}
add_action('mycred_init', 'mycred_senseilms_plugin');
