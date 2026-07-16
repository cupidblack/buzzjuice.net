<?php
/**
 * REST API Controller for Stripe Payouts
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  REST API
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Payouts_REST_Controller Class
 *
 * Handles all REST API endpoints for Stripe Payouts
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Payouts_REST_Controller extends WP_REST_Controller {

	/**
	 * The namespace.
	 *
	 * @since 2.29.0
	 * @var string
	 */
	protected $namespace = 'affwp/v1';

	/**
	 * Rest base for the current object.
	 *
	 * @since 2.29.0
	 * @var string
	 */
	protected $rest_base = 'stripe';

	/**
	 * Rate limiter instance.
	 *
	 * @since 2.29.0
	 * @var AffiliateWP_Stripe_Payouts_Rate_Limiter
	 */
	protected $rate_limiter;

	/**
	 * Constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		// Initialize rate limiter.
		if ( class_exists( 'AffiliateWP_Stripe_Payouts_Rate_Limiter' ) ) {
			$this->rate_limiter = new AffiliateWP_Stripe_Payouts_Rate_Limiter();
		}
	}

	/**
	 * Register the routes.
	 *
	 * @since 2.29.0
	 */
	public function register_routes() {
		// Webhook endpoint - public access.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/webhook',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_webhook' ],
					'permission_callback' => '__return_true', // Webhooks must be public.
				],
			]
		);

		// OAuth connect callback.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/connect',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_oauth_connect' ],
					'permission_callback' => [ $this, 'oauth_permissions_check' ],
					'args'                => $this->get_oauth_connect_args(),
				],
			]
		);

		// OAuth return endpoint (for Stripe redirect).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/return',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_oauth_return' ],
					'permission_callback' => '__return_true', // Must be public for Stripe redirect.
					'args'                => $this->get_oauth_return_args(),
				],
			]
		);

		// Disconnect endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/disconnect',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_disconnect' ],
					'permission_callback' => [ $this, 'disconnect_permissions_check' ],
					'args'                => $this->get_disconnect_args(),
				],
			]
		);

		// Manage account endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/manage',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_manage' ],
					'permission_callback' => [ $this, 'manage_permissions_check' ],
					'args'                => $this->get_manage_args(),
				],
			]
		);
	}

	/**
	 * Handle webhook requests from Stripe.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_webhook( $request ) {
		// Check rate limiting.
		if ( $this->rate_limiter ) {
			$rate_check = $this->rate_limiter->check( 'webhook', $this->get_client_ip() );
			if ( is_wp_error( $rate_check ) ) {
				return $rate_check;
			}
		}

		// Get payload and signature.
		$payload    = $request->get_body();
		$sig_header = $request->get_header( 'stripe-signature' );

		// Validate payload.
		$validation = affwp_stripe_payouts_validate_webhook_payload( $payload, $sig_header );
		if ( is_wp_error( $validation ) ) {
			affwp_stripe_payouts_log_error( 'Webhook validation failed: ' . $validation->get_error_message() );
			return new WP_Error(
				'invalid_webhook',
				$validation->get_error_message(),
				[ 'status' => 400 ]
			);
		}

		// Get webhook secret.
		$webhook_secret = affwp_stripe_payouts_get_webhook_secret();
		if ( empty( $webhook_secret ) ) {
			return new WP_Error(
				'webhook_not_configured',
				__( 'Webhook secret not configured.', 'affiliate-wp' ),
				[ 'status' => 500 ]
			);
		}

		try {
			// Initialize Stripe API.
			affwp_stripe_payouts_init_api();

			// Verify the event with Stripe SDK.
			$event = \Stripe\Webhook::constructEvent(
				$payload,
				$sig_header,
				$webhook_secret
			);

			// Get processor instance.
			$processor = AffiliateWP_Stripe_Payouts_Processor::instance();

			// Handle the event based on type.
			switch ( $event->type ) {
				case 'transfer.created':
					$processor->handle_transfer_created( $event->data->object );
					break;

				case 'transfer.reversed':
					$processor->handle_transfer_reversed( $event->data->object );
					break;

				case 'payout.paid':
					$processor->handle_payout_paid( $event->data->object );
					break;

				case 'payout.failed':
					$processor->handle_payout_failed( $event->data->object );
					break;

				case 'account.updated':
					$processor->handle_account_updated( $event->data->object );
					break;

				case 'account.application.authorized':
					$processor->handle_account_authorized( $event->data->object );
					break;

				case 'account.application.deauthorized':
					$processor->handle_account_deauthorized( $event->data->object );
					break;

				default:
					// Log unhandled event types.
					affwp_stripe_payouts_log_error( 'Unhandled webhook event type: ' . $event->type );
			}

			// Return success response.
			return rest_ensure_response(
				[
					'success' => true,
					'message' => __( 'Webhook processed successfully.', 'affiliate-wp' ),
				]
			);

		} catch ( \UnexpectedValueException $e ) {
			// Invalid payload.
			affwp_stripe_payouts_log_error( 'Invalid webhook payload: ' . $e->getMessage() );
			return new WP_Error(
				'invalid_payload',
				__( 'Invalid webhook payload.', 'affiliate-wp' ),
				[ 'status' => 400 ]
			);
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			// Invalid signature.
			affwp_stripe_payouts_log_error( 'Invalid webhook signature: ' . $e->getMessage() );
			return new WP_Error(
				'invalid_signature',
				__( 'Invalid webhook signature.', 'affiliate-wp' ),
				[ 'status' => 400 ]
			);
		} catch ( Exception $e ) {
			// General error.
			affwp_stripe_payouts_log_error( 'Webhook processing error: ' . $e->getMessage() );
			return new WP_Error(
				'webhook_error',
				__( 'Error processing webhook.', 'affiliate-wp' ),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Handle OAuth connect initiation.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_oauth_connect( $request ) {
		// Check rate limiting.
		if ( $this->rate_limiter ) {
			$rate_check = $this->rate_limiter->check( 'oauth', $this->get_client_ip() );
			if ( is_wp_error( $rate_check ) ) {
				return $rate_check;
			}
		}

		$affiliate_id = $request->get_param( 'affiliate_id' );

		// Generate OAuth URL.
		$oauth_url = affwp_stripe_payouts_generate_oauth_link( $affiliate_id );

		if ( is_wp_error( $oauth_url ) ) {
			return $oauth_url;
		}

		return rest_ensure_response(
			[
				'success'   => true,
				'oauth_url' => $oauth_url,
			]
		);
	}

	/**
	 * Handle OAuth return from Stripe.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_oauth_return( $request ) {
		// Check rate limiting.
		if ( $this->rate_limiter ) {
			$rate_check = $this->rate_limiter->check( 'oauth', $this->get_client_ip() );
			if ( is_wp_error( $rate_check ) ) {
				// For OAuth returns, redirect with error instead of API response.
				$redirect_url = add_query_arg(
					[
						'affwp_notice' => 'rate_limit',
						'type'         => 'error',
					],
					affwp_get_affiliate_area_page_url()
				);
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

		$code  = $request->get_param( 'code' );
		$state = $request->get_param( 'state' );
		$error = $request->get_param( 'error' );

		// Handle errors from Stripe.
		if ( $error ) {
			$error_description = $request->get_param( 'error_description' );
			affwp_stripe_payouts_log_error(
				'OAuth error: ' . $error,
				[ 'description' => $error_description ]
			);

			// Redirect with error.
			$redirect_url = add_query_arg(
				[
					'affwp_notice' => 'stripe_connect_error',
					'type'         => 'error',
				],
				affwp_get_affiliate_area_page_url()
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Process the OAuth callback.
		$result = affwp_stripe_payouts_process_oauth_callback( $code, $state );

		if ( is_wp_error( $result ) ) {
			// Redirect with error.
			$redirect_url = add_query_arg(
				[
					'affwp_notice' => 'stripe_connect_failed',
					'type'         => 'error',
				],
				affwp_get_affiliate_area_page_url()
			);
		} else {
			// Redirect with success.
			$redirect_url = add_query_arg(
				[
					'affwp_notice' => 'stripe_connected',
					'type'         => 'success',
				],
				affwp_get_affiliate_area_page_url()
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle disconnect request.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_disconnect( $request ) {
		$affiliate_id = $request->get_param( 'affiliate_id' );
		$nonce        = $request->get_param( 'nonce' );

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'affwp_stripe_disconnect_' . $affiliate_id ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Security check failed.', 'affiliate-wp' ),
				[ 'status' => 403 ]
			);
		}

		// Disconnect the affiliate's Stripe account.
		$result = affwp_stripe_payouts_disconnect_affiliate( $affiliate_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Stripe account disconnected successfully.', 'affiliate-wp' ),
			]
		);
	}

	/**
	 * Handle manage account request.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_manage( $request ) {
		$affiliate_id = $request->get_param( 'affiliate_id' );

		// Get account ID.
		$account_id = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );
		if ( empty( $account_id ) ) {
			return new WP_Error(
				'not_connected',
				__( 'No Stripe account connected.', 'affiliate-wp' ),
				[ 'status' => 404 ]
			);
		}

		// Generate Express Dashboard link.
		try {
			affwp_stripe_payouts_init_api();

			$stripe = new \Stripe\StripeClient( affwp_stripe_payouts_get_secret_key() );
			$link   = $stripe->accounts->createLoginLink(
				$account_id,
				[
					'redirect_url' => affwp_get_affiliate_area_page_url(),
				]
			);

			// Redirect to Stripe Express Dashboard.
			wp_redirect( $link->url );
			exit;

		} catch ( Exception $e ) {
			affwp_stripe_payouts_log_error( 'Error creating login link: ' . $e->getMessage() );
			return new WP_Error(
				'stripe_error',
				__( 'Could not access Stripe dashboard.', 'affiliate-wp' ),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get OAuth connect arguments.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	protected function get_oauth_connect_args() {
		return [
			'affiliate_id' => [
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => [ $this, 'validate_affiliate_id' ],
				'sanitize_callback' => 'absint',
			],
		];
	}

	/**
	 * Get OAuth return arguments.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	protected function get_oauth_return_args() {
		return [
			'code' => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'state' => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'error' => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'error_description' => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Get disconnect arguments.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	protected function get_disconnect_args() {
		return [
			'affiliate_id' => [
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => [ $this, 'validate_affiliate_id' ],
				'sanitize_callback' => 'absint',
			],
			'nonce' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Get manage arguments.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	protected function get_manage_args() {
		return [
			'affiliate_id' => [
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => [ $this, 'validate_affiliate_id' ],
				'sanitize_callback' => 'absint',
			],
		];
	}

	/**
	 * Check OAuth permissions.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function oauth_permissions_check( $request ) {
		$affiliate_id = $request->get_param( 'affiliate_id' );

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'not_logged_in',
				__( 'You must be logged in to connect Stripe.', 'affiliate-wp' ),
				[ 'status' => 401 ]
			);
		}

		// Check if user owns this affiliate account or is admin.
		$user_id = get_current_user_id();
		$affiliate_user_id = affwp_get_affiliate_user_id( $affiliate_id );

		if ( $user_id !== $affiliate_user_id && ! current_user_can( 'manage_affiliates' ) ) {
			return new WP_Error(
				'unauthorized',
				__( 'You do not have permission to manage this affiliate.', 'affiliate-wp' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Check disconnect permissions.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function disconnect_permissions_check( $request ) {
		// Same as OAuth permissions.
		return $this->oauth_permissions_check( $request );
	}

	/**
	 * Check manage permissions.
	 *
	 * @since 2.29.0
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function manage_permissions_check( $request ) {
		// Same as OAuth permissions.
		return $this->oauth_permissions_check( $request );
	}

	/**
	 * Validate affiliate ID.
	 *
	 * @since 2.29.0
	 * @param mixed           $value   The value to validate.
	 * @param WP_REST_Request $request The request object.
	 * @param string          $param   The parameter name.
	 * @return bool|WP_Error
	 */
	public function validate_affiliate_id( $value, $request, $param ) {
		$result = affwp_stripe_payouts_validate_affiliate_id( $value );

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Get client IP address.
	 *
	 * @since 2.29.0
	 * @return string
	 */
	protected function get_client_ip() {
		$ip_keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );

					if ( filter_var(
						$ip,
						FILTER_VALIDATE_IP,
						FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
					) !== false ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}
}