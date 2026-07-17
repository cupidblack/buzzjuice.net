<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * myCred Event Tickets Integration
 *
 * Rewards points when users confirm RSVP for events via Event Tickets.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'myCRED_Event_Tickets' ) ) :

	#[AllowDynamicProperties]
	final class myCRED_Event_Tickets {

		public $domain = 'mycred_event_tickets';
		public $slug  = 'mycred-event-tickets';

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
			if ( is_plugin_active( 'mycred/mycred.php' ) && is_plugin_active( 'event-tickets/event-tickets.php' ) ) {
				$this->includes();
				add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
				add_filter( 'mycred_setup_hooks', array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks', array( $this, 'load_hooks' ), 10 );
				add_filter( 'mycred_all_references', array( $this, 'register_references' ) );
			}
		}

		private function define_constants() {
			$this->define( 'MYCRED_EVENT_TICKETS_SLUG', 'mycred-event-tickets' );
			$this->define( 'MYCRED_EVENT_TICKETS', __FILE__ );
			$this->define( 'MYCRED_EVENT_TICKETS_ROOT_DIR', plugin_dir_path( MYCRED_EVENT_TICKETS ) );
			$this->define( 'MYCRED_EVENT_TICKETS_ASSETS_DIR_URL', plugin_dir_url( MYCRED_EVENT_TICKETS ) . 'assets/' );
			$this->define( 'MYCRED_EVENT_TICKETS_INCLUDES_DIR', MYCRED_EVENT_TICKETS_ROOT_DIR . 'includes/' );
		}

		public function includes() {
		}

		public function load_hooks() {
			$this->file( MYCRED_EVENT_TICKETS_INCLUDES_DIR . 'mycred-event-tickets-rsvp-hook.php' );
			$this->file( MYCRED_EVENT_TICKETS_INCLUDES_DIR . 'mycred-event-tickets-purchase-hook.php' );
		}

		public function load_assets( $hook ) {
				wp_enqueue_script(
					'mycred-event-tickets-admin',
					MYCRED_EVENT_TICKETS_ASSETS_DIR_URL . 'js/script.js',
					array( 'jquery' ),
					'1.0',
					true
				);
		}

		public function register_hooks( $installed ) {
			$installed['et_rsvp_confirm'] = array(
				'title'       => __( 'Event Tickets - Confirm RSVP', 'mycred-toolkit' ),
				'description' => __( 'Awards %_plural% when a user confirms RSVP for an event. Optionally set different rewards for specific events.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Event_Tickets_RSVP_Hook' ),
			);
			$installed['et_purchase_ticket'] = array(
				'title'       => __( 'Event Tickets - Purchase Ticket', 'mycred-toolkit' ),
				'description' => __( 'Awards %_plural% when a user purchases a ticket for an event. Optionally set different rewards for specific events.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Event_Tickets_Purchase_Hook' ),
			);
			return $installed;
		}

		public function register_references( $list ) {
			$list['et_rsvp_confirm']     = __( 'Confirming RSVP for an event', 'mycred-toolkit' );
			$list['et_purchase_ticket']  = __( 'Purchasing a ticket for an event', 'mycred-toolkit' );
			return $list;
		}
	}

endif;

function mycred_event_tickets() {
	return myCRED_Event_Tickets::instance();
}
mycred_event_tickets();
