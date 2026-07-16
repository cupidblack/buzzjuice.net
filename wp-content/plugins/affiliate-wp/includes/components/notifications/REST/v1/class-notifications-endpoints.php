<?php
/**
 * REST: Notifications Endpoints
 *
 * @package     AffiliateWP
 * @subpackage  REST
 * @copyright   Copyright (c) 2022, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.5
 */

namespace AffWP\Components\Notifications\REST\v1;

use \AffWP\REST\v1\Controller;
use AffWP\Components\Notifications\Notification;

#[\AllowDynamicProperties]

/**
 * Notification Endpoints class.
 *
 * Handles viewing and dismissing in-plugin notifications.
 *
 * @since 2.9.5
 */
class Notifications_Endpoints extends Controller {

	/**
	 * Object type.
	 *
	 * @since 2.9.5
	 * @access public
	 * @var string
	 */
	public $object_type = 'affwp_notifications';

	/**
	 * Route base for affiliates.
	 *
	 * @since 2.9.5
	 * @access public
	 * @var string
	 */
	public $rest_base = 'notifications';

	/**
	 * AffWP REST namespace.
	 *
	 * @since 2.9.5
	 * @access public
	 * @var string
	 */
	public $namespace = 'affwp/v1';

	/**
	 * Registers the endpoints.
	 *
	 * @since 2.9.5
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_notifications' ),
					'permission_callback' => array( $this, 'can_view_notification' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'dismiss_all_notifications' ),
					'permission_callback' => array( $this, 'can_view_notification' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			"{$this->rest_base}/(?P<id>\d+)",
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'dismiss_notification' ),
					'permission_callback' => array( $this, 'can_view_notification' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'ID of the notification.', 'affiliate-wp' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => function ( $param, $request, $key ) {
								$notification = affiliate_wp()->notifications->get( intval( $param ) );

								return ! empty( $notification );
							},
							'sanitize_callback' => function ( $param, $request, $key ) {
								return intval( $param );
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Whether the current user can view (and dismiss) notifications.
	 *
	 * @since 2.9.5
	 *
	 * @return bool
	 */
	public function can_view_notification() {
		return current_user_can( 'manage_affiliate_options' );
	}

	/**
	 * Returns a list of notifications.
	 *
	 * @since 2.9.5
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 *
	 * @TODO At a later date we may want to receive dismissed notifications too.
	 */
	public function list_notifications( \WP_REST_Request $request ) {
		$active = array_map( function ( Notification $notification ) {
			return $notification->to_array();
		}, affiliate_wp()->notifications->get_active_notifications() );

		// Inject the Payouts Service sunset notification if applicable.
		$sunset_notification = $this->get_payouts_service_sunset_notification();

		if ( $sunset_notification ) {
			array_unshift( $active, $sunset_notification );
		}

		return new \WP_REST_Response( array(
			'active' => $active,
		) );
	}

	/**
	 * Returns a synthetic Payouts Service sunset notification if applicable.
	 *
	 * The notification is dynamically generated (not stored in DB) so it automatically
	 * appears when the Payouts Service is connected and disappears when it's disconnected
	 * and an alternative payout method is active.
	 *
	 * @since 2.32.0
	 *
	 * @return array|null Notification data array, or null if not applicable.
	 */
	private function get_payouts_service_sunset_notification() {

		if ( ! \affwp_should_show_payouts_sunset_notification() ) {
			return null;
		}

		$is_past_sunset = \affwp_is_payouts_service_sunset_passed();

		if ( $is_past_sunset ) {
			$title   = __( 'The Payouts Service was shut down on September 30, 2026', 'affiliate-wp' );
			$content = __( 'Please configure another payout method to continue paying your affiliates. Your affiliate data and earnings records are not affected.', 'affiliate-wp' );
		} else {
			$title   = __( 'The Payouts Service is shutting down on September 30, 2026', 'affiliate-wp' );
			$content = __( 'Please switch to another payout method before then. Your affiliate data and earnings records will not be affected.', 'affiliate-wp' );
		}

		return [
			'id'            => 'sunset-payouts-service',
			'remote_id'     => null,
			'title'         => $title,
			'content'       => $content,
			'buttons'       => [
				[
					'type'   => 'primary',
					'text'   => __( 'Switch Payout Method', 'affiliate-wp' ),
					'url'    => admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts' ),
					'target' => '_self',
				],
				[
					'type' => 'secondary',
					'text' => __( 'Learn More', 'affiliate-wp' ),
					'url'  => add_query_arg(
						[
							'utm_campaign' => 'plugin',
							'utm_source'   => 'WordPress',
							'utm_medium'   => 'payouts-service-sunset',
							'utm_content'  => 'notification-panel',
						],
						AFFWP_PAYOUTS_SERVICE_SUNSET_URL
					),
				],
			],
			'type'          => 'warning',
			'dismissible'   => false,
			'conditions'    => null,
			'start'         => null,
			'end'           => null,
			'dismissed'     => false,
			// Hardcoded to keep sort position stable; this notification is synthetic (not stored in DB).
			'date_created'  => '2026-03-24 00:00:00',
			'date_updated'  => '2026-03-24 00:00:00',
			'icon_name'     => 'warning',
			'relative_date' => __( 'Action required', 'affiliate-wp' ),
		];
	}

	/**
	 * Dismisses a single notification.
	 *
	 * @since 2.9.5
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function dismiss_notification( \WP_REST_Request $request ) {
		$notification_removed = affiliate_wp()->notifications->update(
			$request->get_param( 'id' ),
			array( 'dismissed' => 1 )
		);

		if ( ! $notification_removed ) {
			return new \WP_REST_Response( array(
				'error' => __( 'Failed to dismiss notification.', 'affiliate-wp' ),
			), 500 );
		}

		wp_cache_delete( 'affwp_active_notification_count', 'affwp_notifications' );

		return new \WP_REST_Response( null, 204 );
	}

	/**
	 * Dismisses all currently active notifications.
	 *
	 * @since 2.30.0
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function dismiss_all_notifications( \WP_REST_Request $request ) {
		$dismissed = affiliate_wp()->notifications->dismiss_active_notifications();

		wp_cache_delete( 'affwp_active_notification_count', 'affwp_notifications' );

		if ( 0 === $dismissed ) {
			return new \WP_REST_Response( null, 204 );
		}

		return new \WP_REST_Response(
			array(
				'dismissed' => $dismissed,
			),
			200
		);
	}
}
