<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

class AffiliateWP_PayPal_API {

	public $credentials;
	private $sandbox = '';

	/**
	 * Get things started
	 *
	 * @access public
	 * @since 2.29.0
	 * @return void
	 */
	public function __construct() {

		if ( affiliate_wp_paypal()->is_test_mode() ) {
			$this->sandbox = 'sandbox.';
		}
	}

	/**
	 * Process a single referral payment
	 *
	 * @access public
	 * @since 2.29.0
	 * @return bool|WP_Error
	 */
	public function send_payment( $args = [] ) {

		$token = $this->get_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		// Build the request payload.
		$payload = [
			'sender_batch_header' => [
				'sender_batch_id' => md5( serialize( $args ) ),
				'email_subject'   => __( 'Affiliate Earnings Payout', 'affiliate-wp' ),
			],
			'items'               => [
				[
					'recipient_type' => 'EMAIL',
					'amount'         => [
						'value'    => affwp_sanitize_amount( $args['amount'] ),
						'currency' => affwp_get_currency(),
					],
					'receiver'       => $args['email'],
					'note'           => $args['description'],
					'sender_item_id' => (string) $args['referral_id'], // Cast to string for PayPal API.
				],
			],
		];

		$request = wp_remote_post(
			'https://api.' . $this->sandbox . 'paypal.com/v1/payments/payouts?sync_mode=false',
			[
				'headers'     => [
					'Content-Type'                  => 'application/json',
					'Authorization'                 => 'Bearer ' . $token->access_token,
					'PayPal-Partner-Attribution-Id' => 'EasyDigitalDownloads_SP',
				],
				'timeout'     => 45,
				'httpversion' => '1.1',
				'body'        => json_encode( $payload ),
			]
		);

		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if ( is_wp_error( $request ) ) {

			affiliate_wp()->utils->log( 'send_payment() request failed with error code ' . $code . ': ' . print_r( $body, true ) );

			return $request;

		} elseif ( 201 === $code && 'created' === strtolower( $message ) ) {

			if ( function_exists( 'affwp_add_payout' ) ) {
				if ( $referral = affwp_get_referral( $args['referral_id'] ) ) {
					affwp_add_payout(
						[
							'affiliate_id'  => $referral->affiliate_id,
							'referrals'     => $referral->ID,
							'amount'        => $referral->amount,
							'payout_method' => 'PayPal',
						]
					);
				}
			} else {
				affwp_set_referral_status( $args['referral_id'], 'paid' );
			}
		} else {

			affiliate_wp()->utils->log( 'send_payment() request failed with error code ' . $code . ': ' . $message );
			affiliate_wp()->utils->log( 'send_payment() request args: ' . print_r( $args, true ) );
			affiliate_wp()->utils->log( 'send_payment() response body: ' . print_r( $body, true ) );

			// Try to decode the error response for more details.
			$decoded_body = json_decode( $body, true );
			if ( $decoded_body && isset( $decoded_body['message'] ) ) {
				affiliate_wp()->utils->log( 'PayPal API Error Details: ' . print_r( $decoded_body, true ) );
			}

			return new WP_Error( $code, $message );

		}

		return true;
	}

	/**
	 * Process a referral payment for a bulk payout
	 *
	 * @access public
	 * @since 2.29.0
	 * @return bool|WP_Error
	 */
	public function send_bulk_payment( $payouts = [] ) {

		$token = $this->get_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$items = [];
		foreach ( $payouts as $affilate_id => $payout ) {

			$items[] = [
				'recipient_type' => 'EMAIL',
				'amount'         => [
					'value'    => affwp_sanitize_amount( $payout['amount'] ),
					'currency' => affwp_get_currency(),
				],
				'receiver'       => $payout['email'],
				'note'           => $payout['description'],
				'sender_item_id' => (string) $affilate_id, // Cast to string for PayPal API.
			];

		}

		$request = wp_remote_post(
			'https://api.' . $this->sandbox . 'paypal.com/v1/payments/payouts?sync_mode=false',
			[
				'headers'     => [
					'Content-Type'                  => 'application/json',
					'Authorization'                 => 'Bearer ' . $token->access_token,
					'PayPal-Partner-Attribution-Id' => 'EasyDigitalDownloads_SP',
				],
				'timeout'     => 45,
				'httpversion' => '1.1',
				'body'        => json_encode(
					[
						'sender_batch_header' => [
							'sender_batch_id' => md5( serialize( $items ) . gmdate( 'Y-m-d' ) ),
							'email_subject'   => __( 'Affiliate Earnings Payout', 'affiliate-wp' ),
						],
						'items'               => $items,
					]
				),
			]
		);

		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if ( 201 === $code && 'created' === strtolower( $message ) ) {

			return true;

		} else {

			affiliate_wp()->utils->log( 'send_bulk_payment() request failed with error code ' . $code . ': ' . $message );
			affiliate_wp()->utils->log( 'send_payment() request items: ' . print_r( $items, true ) );
			affiliate_wp()->utils->log( 'send_payment() request attempt: ' . print_r( $request, true ) );

			$body = json_decode( $body );

			if ( ! empty( $body->name ) && 'VALIDATION_ERROR' === $body->name ) {

				$code    = $body->name;
				$message = $body->message . '. Details: ' . json_encode( $body->details ) . ' - ' . $body->information_link;

			}

			return new WP_Error( $code, $message );

		}
	}

	/**
	 * Retrieve an API access token
	 *
	 * @access private
	 * @since 2.29.0
	 * @return object|WP_Error
	 */
	private function get_token() {

		$request = wp_remote_post(
			'https://api.' . $this->sandbox . 'paypal.com/v1/oauth2/token',
			[
				'headers'     => [
					'Accept'          => 'application/json',
					'Accept-Language' => 'en_US',
					'Authorization'   => 'Basic ' . base64_encode( $this->credentials['client_id'] . ':' . $this->credentials['secret'] ),
				],
				'timeout'     => 45,
				'httpversion' => '1.1',
				'body'        => [
					'grant_type' => 'client_credentials',
				],
			]
		);

		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if ( is_wp_error( $request ) ) {

			affiliate_wp()->utils->log( 'get_token() request failed with error code ' . $code . ': ' . print_r( $body, true ) );

			return $request;

		} elseif ( 200 === $code && 'ok' === strtolower( $message ) ) {

			affiliate_wp()->utils->log( 'get_token() request succeeded: ' . print_r( $body, true ) );

			return json_decode( $body );

		} else {

			$body = json_decode( $body );

			if ( ! empty( $body->error ) ) {

				$code  = $body->error;
				$error = $body->error_description;

			} else {

				$code  = $code;
				$error = $message;

			}

			affiliate_wp()->utils->log( 'get_token() request failed with error code ' . $code . ': ' . $error );

			return new WP_Error( $code, $error );

		}
	}
}
