<?php
/**
 * Stripe Payouts Class
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Classes/Stripe Payouts
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Payouts_Processor Class
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Payouts_Processor {

	/**
	 * Singleton instance
	 *
	 * @since 2.29.0
	 * @var AffiliateWP_Stripe_Payouts_Processor
	 */
	private static $instance;

	/**
	 * Main AffiliateWP_Stripe_Payouts_Processor Instance
	 *
	 * Ensures only one instance of AffiliateWP_Stripe_Payouts_Processor exists at any one time.
	 *
	 * @since 2.29.0
	 * @static
	 * @return AffiliateWP_Stripe_Payouts_Processor The one true AffiliateWP_Stripe_Payouts_Processor
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AffiliateWP_Stripe_Payouts_Processor ) ) {
			self::$instance = new AffiliateWP_Stripe_Payouts_Processor();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function init() {
		// Set up the webhook listener
		add_action( 'init', [ $this, 'process_webhooks' ] );
	}

	/**
	 * Process Stripe webhook events
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_webhooks() {
		// Check if this is a webhook request
		if ( ! isset( $_GET['affwp_action'] ) || $_GET['affwp_action'] !== 'stripe_payouts_webhook' ) {
			return;
		}

		// Get the webhook secret
		$webhook_secret = affwp_stripe_payouts_get_webhook_secret();

		if ( empty( $webhook_secret ) ) {
			status_header( 400 );
			echo 'Webhook secret not configured';
			exit;
		}

		// Get the payload
		$payload    = file_get_contents( 'php://input' );
		$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) : '';

		// Validate webhook payload
		$payload_validation = affwp_stripe_payouts_validate_webhook_payload( $payload, $sig_header );
		if ( is_wp_error( $payload_validation ) ) {
			affwp_stripe_payouts_log_error( 'Webhook validation failed: ' . $payload_validation->get_error_message() );
			status_header( 400 );
			echo 'Invalid webhook payload';
			exit;
		}

		try {
			// Initialize Stripe API
			affwp_stripe_payouts_init_api();

			// Verify the event
			$event = \Stripe\Webhook::constructEvent(
				$payload,
				$sig_header,
				$webhook_secret
			);

			// Handle the event
			switch ( $event->type ) {
				case 'transfer.created':
					$this->handle_transfer_created( $event->data->object );
					break;
				case 'transfer.reversed':
					$this->handle_transfer_reversed( $event->data->object );
					break;
				case 'payout.paid':
					$this->handle_payout_paid( $event->data->object );
					break;
				case 'payout.failed':
					$this->handle_payout_failed( $event->data->object );
					break;
				case 'account.updated':
					$this->handle_account_updated( $event->data->object );
					break;
				case 'account.application.authorized':
					$this->handle_account_authorized( $event->data->object );
					break;
				case 'account.application.deauthorized':
					$this->handle_account_deauthorized( $event->data->object );
					break;
				default:
					// Unexpected event type
					affwp_stripe_payouts_log_error( 'Unhandled webhook event: ' . $event->type );
			}

			// Return success
			status_header( 200 );
			echo 'Webhook processed';
			exit;

		} catch ( \UnexpectedValueException $e ) {
			// Invalid payload
			affwp_stripe_payouts_log_error( 'Invalid payload: ' . $e->getMessage() );
			status_header( 400 );
			echo 'Invalid payload';
			exit;
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			// Invalid signature
			affwp_stripe_payouts_log_error( 'Invalid signature: ' . $e->getMessage() );
			status_header( 400 );
			echo 'Invalid signature';
			exit;
		} catch ( Exception $e ) {
			// General error
			affwp_stripe_payouts_log_error( 'Webhook error: ' . $e->getMessage() );
			status_header( 400 );
			echo 'Webhook error';
			exit;
		}
	}

	/**
	 * Handle transfer.created event
	 *
	 * @since 2.29.0
	 * @param object $transfer The transfer object
	 * @return void
	 */
	public function handle_transfer_created( $transfer ) {
		// Log the event
		affwp_stripe_payouts_log_error(
			'Transfer created: ' . $transfer->id,
			[
				'amount'      => $transfer->amount,
				'destination' => $transfer->destination,
				'metadata'    => $transfer->metadata,
			]
		);

		// Trigger the transfer created email action
		do_action( 'affwp_stripe_transfer_created', $transfer );
	}


	/**
	 * Handle transfer.reversed event
	 *
	 * @since 2.29.0
	 * @param object $transfer The transfer object
	 * @return void
	 */
	public function handle_transfer_reversed( $transfer ) {
		// Log the event with full metadata for debugging
		affwp_stripe_payouts_log_error(
			'Transfer reversed: ' . $transfer->id,
			[
				'amount'           => $transfer->amount,
				'destination'      => $transfer->destination,
				'metadata'         => $transfer->metadata,
				'has_referral_id'  => isset( $transfer->metadata->referral_id ),
				'has_affiliate_id' => isset( $transfer->metadata->affiliate_id ),
			]
		);

		// Always trigger the email notification action
		// The email handlers will deal with missing metadata gracefully
		do_action( 'affwp_stripe_webhook_transfer_reversed', $transfer );

		// If there's a referral ID in the metadata, update the referral status
		if ( isset( $transfer->metadata->referral_id ) ) {
			$referral_id = affwp_stripe_payouts_validate_referral_id( $transfer->metadata->referral_id );
			if ( is_wp_error( $referral_id ) ) {
				affwp_stripe_payouts_log_error( 'Invalid referral ID in transfer metadata: ' . $transfer->metadata->referral_id );
				// Don't return - continue processing even if referral is invalid
			} else {
				// Get the referral
				$referral = affwp_get_referral( $referral_id );

				if ( $referral ) {
					// Update the referral status back to unpaid if it was paid
					if ( 'paid' === $referral->status ) {
						affwp_set_referral_status( $referral_id, 'unpaid' );
					}

					// Add a note about the reversed transfer
					affwp_add_referral_meta( $referral_id, 'stripe_transfer_reversed', $transfer->id );
				}
			}
		}
	}

	/**
	 * Handle payout.paid event
	 *
	 * @since 2.29.0
	 * @param object $payout The Stripe payout object
	 * @return void
	 */
	public function handle_payout_paid( $payout ) {
		// Log the event
		affwp_stripe_payouts_log(
			'Payout paid: ' . $payout->id,
			[
				'amount'      => $payout->amount,
				'destination' => $payout->destination,
				'metadata'    => $payout->metadata,
			]
		);

		// Trigger action for email notifications
		do_action( 'affwp_stripe_webhook_payout_paid', $payout );
	}

	/**
	 * Handle payout.failed event
	 *
	 * @since 2.29.0
	 * @param object $payout The Stripe payout object
	 * @return void
	 */
	public function handle_payout_failed( $payout ) {
		// Log the event
		affwp_stripe_payouts_log_error(
			'Payout failed: ' . $payout->id,
			[
				'amount'          => $payout->amount,
				'destination'     => $payout->destination,
				'failure_code'    => $payout->failure_code,
				'failure_message' => $payout->failure_message,
				'metadata'        => $payout->metadata,
			]
		);

		// Trigger action for email notifications
		do_action( 'affwp_stripe_webhook_payout_failed', $payout );
	}

	/**
	 * Handle account.updated event
	 *
	 * @since 2.29.0
	 * @param object $account The Stripe account object
	 * @return void
	 */
	public function handle_account_updated( $account ) {

		// Find affiliate by Stripe account ID
		$affiliate_id = $this->get_affiliate_by_stripe_account( $account->id );

		if ( ! $affiliate_id ) {
			return;
		}

		// Store requirements and status in affiliate meta
		$requirements_data = [
			'currently_due'     => $account->requirements->currently_due ?? [],
			'eventually_due'    => $account->requirements->eventually_due ?? [],
			'past_due'          => $account->requirements->past_due ?? [],
			'disabled_reason'   => $account->requirements->disabled_reason ?? '',
			'payouts_enabled'   => $account->payouts_enabled ?? false,
			'charges_enabled'   => $account->charges_enabled ?? false,
			'details_submitted' => $account->details_submitted ?? false,
			'last_updated'      => current_time( 'timestamp' ),
		];

		affwp_update_affiliate_meta( $affiliate_id, 'stripe_requirements', $requirements_data );

		// Store capabilities separately for better organization
		if ( isset( $account->capabilities ) ) {
			$capabilities_data = [];
			foreach ( $account->capabilities as $capability => $status ) {
				$capabilities_data[ $capability ] = $status;
			}
			affwp_update_affiliate_meta( $affiliate_id, 'stripe_capabilities', $capabilities_data );
		}

		// Clear any cached status
		$cache_key = 'affwp_stripe_account_status_' . $account->id;
		delete_transient( $cache_key );

		// Trigger the webhook action to send notification emails
		// This will be handled by email handlers in account-verification-required.php
		do_action( 'affwp_stripe_webhook_account_updated', $account );
	}

	/**
	 * Handle account.application.authorized event
	 *
	 * @since 2.29.0
	 * @param object $account The Stripe account object
	 * @return void
	 */
	public function handle_account_authorized( $account ) {
		// This event fires when an affiliate connects their Stripe account
		// The connection is already handled by the OAuth flow, but we can
		// use this to ensure the data is synced
		$this->handle_account_updated( $account );
	}

	/**
	 * Handle account.application.deauthorized event
	 *
	 * @since 2.29.0
	 * @param object $account The Stripe account object
	 * @return void
	 */
	public function handle_account_deauthorized( $account ) {
		// Find affiliate by Stripe account ID
		$affiliate_id = $this->get_affiliate_by_stripe_account( $account->id );

		if ( ! $affiliate_id ) {
			return;
		}

		// Clear the Stripe connection
		$user_id = affwp_get_affiliate_user_id( $affiliate_id );
		if ( $user_id ) {
			delete_user_meta( $user_id, 'affwp_stripe_payouts_account_id' );
			delete_user_meta( $user_id, 'affwp_stripe_payouts_access_token' );
			delete_user_meta( $user_id, 'affwp_stripe_payouts_refresh_token' );

			// Clear affiliate meta
			affwp_delete_affiliate_meta( $affiliate_id, 'stripe_connected_account' );
			affwp_delete_affiliate_meta( $affiliate_id, 'stripe_requirements' );

			// Clear cache
			$cache_key = 'affwp_stripe_account_status_' . $account->id;
			delete_transient( $cache_key );
		}
	}

	/**
	 * Get affiliate ID by Stripe account ID
	 *
	 * @since 2.29.0
	 * @param string $account_id The Stripe account ID
	 * @return int|false The affiliate ID or false if not found
	 */
	private function get_affiliate_by_stripe_account( $account_id ) {
		global $wpdb;

		// First check user meta
		$user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta}
			WHERE meta_key = 'affwp_stripe_payouts_account_id'
			AND meta_value = %s
			LIMIT 1",
				$account_id
			)
		);

		if ( $user_id ) {
			return affwp_get_affiliate_id( $user_id );
		}

		// Check affiliate meta as fallback
		$affiliate_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT affiliate_id FROM {$wpdb->prefix}affiliate_wp_affiliatemeta
			WHERE meta_key = 'stripe_connected_account'
			AND meta_value = %s
			LIMIT 1",
				$account_id
			)
		);

		return $affiliate_id ? absint( $affiliate_id ) : false;
	}

	/**
	 * Process a single payout
	 *
	 * @since 2.29.0
	 * @param int $referral_id The referral ID
	 * @return array|WP_Error Result of the payout
	 */
	public function process_single_payout( $referral_id ) {
		// Get the referral
		$referral = affwp_get_referral( $referral_id );

		// Check if referral exists and is unpaid
		if ( ! $referral || 'unpaid' !== $referral->status ) {
			return new WP_Error( 'invalid_referral', __( 'Invalid referral or already paid.', 'affiliate-wp' ) );
		}

		// Get the affiliate ID
		$affiliate_id = $referral->affiliate_id;

		// Check if affiliate is connected to Stripe
		if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
			return new WP_Error( 'stripe_not_connected', __( 'Affiliate is not connected to Stripe.', 'affiliate-wp' ) );
		}

		// Initialize Stripe API
		affwp_stripe_payouts_init_api();

		try {
			// Get admin and affiliate Stripe account IDs
			$admin_account_id     = affwp_stripe_payouts_get_admin_account_id();
			$affiliate_account_id = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );

			// Get the amount in cents
			$amount = affwp_stripe_payouts_format_amount( $referral->amount );

			// Create a transfer
			$transfer = \Stripe\Transfer::create(
				[
					'amount'         => $amount,
					'currency'       => affwp_get_currency(),
					'destination'    => $affiliate_account_id,
					'transfer_group' => 'affwp_referral_' . $referral_id,
					'metadata'       => [
						'referral_id'  => $referral_id,
						'affiliate_id' => $affiliate_id,
						'amount'       => $referral->amount,
						'description'  => sprintf( __( 'Referral #%d Payment', 'affiliate-wp' ), $referral_id ),
					],
				]
			);

			// Mark the referral as paid
			if ( $transfer && isset( $transfer->id ) ) {
				// Update the referral
				affwp_set_referral_status( $referral_id, 'paid' );

				// Add a meta for the Stripe transfer ID
				affwp_add_referral_meta( $referral_id, 'stripe_transfer_id', $transfer->id );

				// Trigger success email notification
				$transfer_data = [
					'transfer_id' => $transfer->id,
					'referral_id' => $referral_id,
				];
				do_action( 'affwp_stripe_payout_success', $affiliate_id, $referral, $transfer_data );

				// Return success
				return [
					'success'     => true,
					'message'     => sprintf( __( 'Referral #%d successfully paid via Stripe.', 'affiliate-wp' ), $referral_id ),
					'transfer_id' => $transfer->id,
				];
			} else {
				throw new Exception( __( 'Stripe transfer was not created.', 'affiliate-wp' ) );
			}
		} catch ( Exception $e ) {
			// Enhanced error logging with Stripe details
			$error_details = [
				'referral_id'   => $referral_id,
				'affiliate_id'  => $affiliate_id,
				'amount'        => $referral->amount,
				'error_message' => $e->getMessage(),
			];

			// Add Stripe-specific error details if available
			if ( $e instanceof \Stripe\Exception\ApiErrorException ) {
				$error_details['stripe_code']  = method_exists( $e, 'getStripeCode' ) ? $e->getStripeCode() : null;
				$error_details['http_status']  = method_exists( $e, 'getHttpStatus' ) ? $e->getHttpStatus() : null;
				$error_details['request_id']   = method_exists( $e, 'getRequestId' ) ? $e->getRequestId() : null;
				$error_details['decline_code'] = method_exists( $e, 'getDeclineCode' ) ? $e->getDeclineCode() : null;
			}

			affwp_stripe_payouts_log_error(
				'Payout error: ' . $e->getMessage(),
				$error_details
			);

			// Trigger failure email notification with enhanced error data
			$error_data = [
				'error_message' => $e->getMessage(),
				'error_code'    => ( $e instanceof \Stripe\Exception\ApiErrorException && method_exists( $e, 'getStripeCode' ) && $e->getStripeCode() )
					? $e->getStripeCode()
					: ( $e->getCode() ? $e->getCode() : 'unknown' ),
				'referral_id'   => $referral_id,
			];

			// Add Stripe-specific codes for email templates
			if ( $e instanceof \Stripe\Exception\ApiErrorException ) {
				$error_data['stripe_code']  = method_exists( $e, 'getStripeCode' ) ? $e->getStripeCode() : null;
				$error_data['decline_code'] = method_exists( $e, 'getDeclineCode' ) ? $e->getDeclineCode() : null;
			}

			do_action( 'affwp_stripe_transfer_failed', $affiliate_id, $referral, $error_data );

			// Return error
			return new WP_Error( 'stripe_error', $e->getMessage() );
		}
	}
}

// Initialize the payouts processor
AffiliateWP_Stripe_Payouts_Processor::instance();
