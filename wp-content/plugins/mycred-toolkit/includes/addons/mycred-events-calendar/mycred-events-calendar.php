<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * myCred The Events Calendar Integration
 *
 * Rewards points when users publish events via The Events Calendar.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'myCRED_Events_Calendar' ) ) :

	#[AllowDynamicProperties]
	final class myCRED_Events_Calendar {

		public $domain = 'mycred_events_calendar';
		public $slug  = 'mycred-events-calendar';

		protected static $_instance = null;

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		public function __construct() {
			$this->define_constants();
			$this->init();
			$this->plugin = plugin_basename( __FILE__ );
		}

		private function init() {
			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active( 'mycred/mycred.php' ) && is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) ) {
				$this->includes();
				add_filter( 'mycred_setup_hooks', array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks', array( $this, 'load_hooks' ), 10 );
				add_filter( 'mycred_all_references', array( $this, 'register_references' ) );
			}
		}

		private function define_constants() {
			$this->define( 'MYCRED_EVENTS_CALENDAR_SLUG', 'mycred-events-calendar' );
			$this->define( 'MYCRED_EVENTS_CALENDAR', __FILE__ );
			$this->define( 'MYCRED_EVENTS_CALENDAR_ROOT_DIR', plugin_dir_path( MYCRED_EVENTS_CALENDAR ) );
			$this->define( 'MYCRED_EVENTS_CALENDAR_INCLUDES_DIR', MYCRED_EVENTS_CALENDAR_ROOT_DIR . 'includes/' );
		}

		public function includes() {
			// Reserved for shared functions if needed.
		}

		public function load_hooks() {
			$this->file( MYCRED_EVENTS_CALENDAR_INCLUDES_DIR . 'mycred-events-calendar-publish-event-hook.php' );
			$this->file( MYCRED_EVENTS_CALENDAR_INCLUDES_DIR . 'mycred-events-calendar-delete-event-hook.php' );
		}

		public function register_hooks( $installed ) {
			$installed['tec_publish_event'] = array(
				'title'       => __( 'Publishing a New Event (The Events Calendar)', 'mycred-toolkit' ),
				'description' => __( 'Awards points when a user publishes a new event.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Events_Calendar_Publish_Event_Hook' ),
			);
			$installed['tec_delete_event'] = array(
				'title'       => __( 'Deleting an Event (The Events Calendar)', 'mycred-toolkit' ),
				'description' => __( 'Awards or deducts points when a user deletes an event.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Events_Calendar_Delete_Event_Hook' ),
			);
			return $installed;
		}

		public function register_references( $list ) {
			$list['tec_publish_event'] = __( 'Publishing a new event', 'mycred-toolkit' );
			$list['tec_delete_event']  = __( 'Deleting an event', 'mycred-toolkit' );
			return $list;
		}
	}

endif;

function mycred_events_calendar() {
	return myCRED_Events_Calendar::instance();
}
mycred_events_calendar();
