<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}


if ( ! class_exists( 'myCRED_LifterLMS_Notification' ) ) :
	class myCRED_LifterLMS_Notification {   

		// Instnace
		protected static $_instance = null;

		/**
		 * Setup Instance
		 * @since 1.2
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Construct
		 */
		function __construct() {

			$this->run();
		}

		/**
		 * Hook into WordPress
		 */
		public function run() {
			
			add_action( 'llms_notifications_loaded', array( $this, 'user_gets_notification' ) );
			add_filter( 'mycred_new_log_entry_id', array( $this, 'trigger_llms_notification' ), 10, 3 );
			add_filter( 'llms_notifications_query_get_notifications', array( $this, 'add_mycred_notices' ), 10, 2 );
		}

		public function user_gets_notification( $obj ) {

			$obj->load_controller( 'mycred_points', MYCRED_LIFTERLMS_INC_DIR . 'notifications/class.llms.notification.controller.mycred.points.php' );
			$obj->load_view( 'mycred_points', MYCRED_LIFTERLMS_INC_DIR . 'notifications/class.llms.notification.view.mycred.points.php', 'LLMS' );
		}

		public function trigger_llms_notification( $id, $log_data, $obj ) {

			do_action( 'mycred_points_llms_notification', $id, $log_data, $obj );

			return $id;
		}

		public function add_mycred_notices( $notifications, $obj ) {

			global $wpdb;

			$user_id = get_current_user_id();

			$sql = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lifterlms_notifications
				WHERE type = 'basic' AND trigger_id = 'mycred_points' AND status = 'new' AND subscriber = %d
				ORDER BY updated DESC, id DESC
				LIMIT 5;",
				$user_id
			);

			$results = $wpdb->get_results( $sql );

			if ( $results ) {

				foreach ( $results as $result ) {
					$obj             = new LLMS_Notification( $result->id );
					$notifications[] = $obj->load();
				}

			}

			return $notifications;
		}
	}
endif;
return myCRED_LifterLMS_Notification::instance();
