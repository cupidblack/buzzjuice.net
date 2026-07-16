<?php

/**
 * Stripe Connect Class
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Classes/Stripe Connect
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP_Stripe_Connect Class
 *
 * @since 2.29.0
 */
class AffiliateWP_Stripe_Connect {


	/**
	 * Singleton instance
	 *
	 * @since 2.29.0
	 * @var AffiliateWP_Stripe_Connect
	 */
	private static $instance;

	/**
	 * Main AffiliateWP_Stripe_Connect Instance
	 *
	 * Ensures only one instance of AffiliateWP_Stripe_Connect exists at any one time.
	 *
	 * @since 2.29.0
	 * @static
	 * @return AffiliateWP_Stripe_Connect The one true AffiliateWP_Stripe_Connect
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AffiliateWP_Stripe_Connect ) ) {
			self::$instance = new AffiliateWP_Stripe_Connect();
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
		// Admin.
		add_action( 'admin_init', [ $this, 'process_admin_connect' ] );
		add_action( 'admin_init', [ $this, 'process_admin_disconnect' ] );

		// Frontend.
		add_action( 'init', [ $this, 'process_affiliate_connect' ] );
		add_action( 'template_redirect', [ $this, 'process_affiliate_disconnect' ] );
	}

	/**
	 * Process admin connection to Stripe
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_admin_connect() {
		// Check if we're on the settings page.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'affiliate-wp-settings' ) {
			return;
		}

		// Check for OAuth errors from Stripe first
		if ( isset( $_GET['error'] ) ) {
			$error_validation = affwp_stripe_payouts_validate_stripe_error( $_GET['error'] );
			if ( is_wp_error( $error_validation ) ) {
				$error             = 'invalid_request';
				$error_description = $error_validation->get_error_message();
			} else {
				$error             = $error_validation;
				$error_description = isset( $_GET['error_description'] ) ?
					affwp_stripe_payouts_sanitize_error_message( $_GET['error_description'] ) : '';
			}

			// Log the error for debugging
			affwp_stripe_payouts_log_error( 'OAuth error from Stripe: ' . $error . ' - ' . $error_description );

			// Redirect with sanitized error message
			wp_redirect(
				add_query_arg(
					[
						'stripe_error' => urlencode(
							sprintf(
								__( 'Stripe connection failed: %s', 'affiliate-wp' ),
								$error_description ?: $error
							)
						),
					],
					admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts' )
				)
			);
			exit;
		}

		// Check if this is an OAuth callback from Stripe (either format).
		if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
			// Validate the authorization code
			$code = affwp_stripe_payouts_validate_oauth_code( $_GET['code'] );
			if ( is_wp_error( $code ) ) {
				wp_safe_redirect(
					add_query_arg(
						[
							'stripe_error' => urlencode( $code->get_error_message() ),
						],
						admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts' )
					)
				);
				exit;
			}

			// Initialize Stripe API.
			affwp_stripe_payouts_init_api();

			try {
				// Exchange the authorization code for an access token.
				$stripe_connect_url = 'https://connect.stripe.com/oauth/token';

				$args = [
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => [],
					'body'        => [
						'client_secret' => affwp_stripe_payouts_get_secret_key(),
						'code'          => $code,
						'grant_type'    => 'authorization_code',
					],
				];

				$response = wp_remote_post( $stripe_connect_url, $args );

				if ( is_wp_error( $response ) ) {
					throw new Exception( $response->get_error_message() );
				}

				$body = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $body['error'] ) ) {
					throw new Exception( $body['error_description'] );
				}

				// Store the Stripe account ID.
				if ( isset( $body['stripe_user_id'] ) ) {
					update_option( 'affwp_stripe_payouts_account_id', sanitize_text_field( $body['stripe_user_id'] ) );

					// Redirect to settings page.
					wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts&stripe_connected=1' ) );
					exit;
				} else {
					throw new Exception( __( 'Stripe account ID not found in the response.', 'affiliate-wp' ) );
				}
			} catch ( Exception $e ) {
				affwp_stripe_payouts_log_error( 'Error connecting admin to Stripe: ' . $e->getMessage() );

				// Redirect with error
				wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts&stripe_error=' . urlencode( $e->getMessage() ) ) );
				exit;
			}
		}

		// Check if this is a disconnect request.
		if ( isset( $_GET['disconnect'] ) && $_GET['disconnect'] == '1' && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'affwp_stripe_payouts_disconnect' ) ) {
			delete_option( 'affwp_stripe_payouts_account_id' );

			// Redirect to settings page
			wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts&stripe_disconnected=1' ) );
			exit;
		}
	}

	/**
	 * Process admin disconnection from Stripe
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_admin_disconnect() {
		// Check if this is the admin disconnect action and verify nonce.
		if (
			isset( $_GET['affwp_action'] ) &&
			$_GET['affwp_action'] === 'stripe_payouts_admin_disconnect' &&
			isset( $_GET['_wpnonce'] ) &&
			wp_verify_nonce( $_GET['_wpnonce'], 'affwp_stripe_payouts_admin_disconnect' )
		) {
			// Check user permissions.
			if ( ! current_user_can( 'manage_affiliate_options' ) ) {
				wp_die( __( 'You do not have permission to disconnect Stripe.', 'affiliate-wp' ) );
			}

			// Delete the stored account ID.
			delete_option( 'affwp_stripe_payouts_account_id' );

			// Redirect back to settings page with disconnected notice.
			wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts&stripe_disconnected=1' ) );
			exit;
		}
	}

	/**
	 * Process affiliate connection to Stripe
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_affiliate_connect() {
		// Check if this is an affiliate connect request.
		if ( isset( $_GET['affwp_action'] ) && $_GET['affwp_action'] == 'stripe_payouts_connect' ) {

			// Validate affiliate ID
			if ( ! isset( $_GET['affiliate_id'] ) ) {
				wp_die( __( 'Affiliate ID is required.', 'affiliate-wp' ) );
			}

			$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_GET['affiliate_id'] );
			if ( is_wp_error( $affiliate_id ) ) {
				wp_die( $affiliate_id->get_error_message() );
			}

			// Check user permissions
			$permission_check = affwp_stripe_payouts_validate_user_permissions( $affiliate_id, 'connect' );
			if ( is_wp_error( $permission_check ) ) {
				wp_die( $permission_check->get_error_message() );
			}

			$user_id = affwp_get_affiliate_user_id( $affiliate_id );

			// Check if Stripe is configured.
			if ( ! affwp_stripe_payouts_is_configured() ) {
				wp_die( __( 'Stripe is not configured. Please contact the site administrator.', 'affiliate-wp' ) );
			}

			// Check for OAuth errors from Stripe first
			if ( isset( $_GET['error'] ) ) {
				$error_validation = affwp_stripe_payouts_validate_stripe_error( $_GET['error'] );
				if ( is_wp_error( $error_validation ) ) {
					$error             = 'invalid_request';
					$error_description = $error_validation->get_error_message();
				} else {
					$error             = $error_validation;
					$error_description = isset( $_GET['error_description'] ) ?
						affwp_stripe_payouts_sanitize_error_message( $_GET['error_description'] ) : '';
				}

				// Log the error for debugging
				affwp_stripe_payouts_log_error( 'OAuth error from Stripe (affiliate): ' . $error . ' - ' . $error_description );

				// Redirect with sanitized error message
				wp_safe_redirect(
					add_query_arg(
						[
							'stripe_error' => urlencode(
								sprintf(
									__( 'Stripe connection failed: %s', 'affiliate-wp' ),
									$error_description ?: $error
								)
							),
						],
						affwp_get_affiliate_area_page_url( 'settings' )
					)
				);
				exit;
			}

			// Check if this is a callback from Stripe
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Validate the authorization code
				$code = affwp_stripe_payouts_validate_oauth_code( $_GET['code'] );
				if ( is_wp_error( $code ) ) {
					wp_safe_redirect(
						add_query_arg(
							[
								'stripe_error' => urlencode( $code->get_error_message() ),
							],
							affwp_get_affiliate_area_page_url( 'settings' )
						)
					);
					exit;
				}

				// Process the OAuth callback
				$this->process_affiliate_oauth_callback();
				return;
			}

			try {
				// Initialize Stripe API.
				affwp_stripe_payouts_init_api();

				// Get user info for the account.
				$user_info = get_userdata( $user_id );

				// Determine if we should create a recipient account (for cross-border payouts).
				// We'll let Stripe handle country detection during onboarding, but we can
				// check if cross-border is enabled and platform is US.
				$create_recipient_account = false;

				if ( affwp_stripe_payouts_cross_border_enabled() && affwp_stripe_payouts_platform_supports_cross_border() ) {
					// For now, create recipient accounts for all affiliates when cross-border is enabled
					// Stripe will handle country detection during onboarding.
					$create_recipient_account = true;

					// Log this for debugging.
					affwp_stripe_payouts_log_info(
						'Creating recipient account for affiliate #' . $affiliate_id . ' (cross-border enabled)',
						[ 'platform_supports_cross_border' => true ]
					);
				}

				// Create Express account with appropriate configuration.
				$account_data = [
					'type'          => 'express',
					'capabilities'  => [
						'transfers' => [ 'requested' => true ],
					],
					'business_type' => 'individual',
				];

				// Only add card_payments capability for standard accounts (not recipient accounts).
				if ( ! $create_recipient_account ) {
					$account_data['capabilities']['card_payments'] = [ 'requested' => true ];
				}

				// Set service agreement for recipient accounts.
				if ( $create_recipient_account ) {
					$account_data['tos_acceptance'] = [
						'service_agreement' => 'recipient',
					];
				}

				// Add email if available.
				if ( $user_info && $user_info->user_email ) {
					$account_data['email'] = $user_info->user_email;
				}

				// Add business profile.
				$account_data['business_profile'] = [
					'product_description' => sprintf(
						__( 'Affiliate for %s', 'affiliate-wp' ),
						get_bloginfo( 'name' )
					),
				];

				// Create the account.
				$account = \Stripe\Account::create( $account_data );

				// Store the account ID temporarily.
				update_user_meta( $user_id, 'affwp_stripe_payouts_pending_account_id', $account->id );

				// Create account link for onboarding.
				$account_link = \Stripe\AccountLink::create(
					[
						'account'     => $account->id,
						'refresh_url' => add_query_arg(
							[
								'affwp_action'   => 'stripe_payouts_connect',
								'affiliate_id'   => $affiliate_id,
								'stripe_refresh' => '1',
							],
							affwp_get_affiliate_area_page_url( 'settings' )
						),
						'return_url'  => add_query_arg(
							[
								'affwp_action' => 'stripe_payouts_connect',
								'affiliate_id' => $affiliate_id,
								'code'         => 'completed',
							],
							affwp_get_affiliate_area_page_url( 'settings' )
						),
						'type'        => 'account_onboarding',
					]
				);

				// Redirect to the account link
				wp_redirect( $account_link->url );
				exit;
			} catch ( Exception $e ) {
				$error_message = $e->getMessage();

				// For errors, show a generic message
				wp_die(
					sprintf(
						__( 'Error connecting to Stripe: %s', 'affiliate-wp' ),
						esc_html( $error_message )
					)
				);
			}
		}
	}

	/**
	 * Process OAuth callback for affiliate
	 *
	 * @since 2.29.0
	 * @return void
	 */
	private function process_affiliate_oauth_callback() {
		// Validate affiliate ID
		if ( ! isset( $_GET['affiliate_id'] ) ) {
			wp_die( __( 'Affiliate ID is required.', 'affiliate-wp' ) );
		}

		$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_GET['affiliate_id'] );
		if ( is_wp_error( $affiliate_id ) ) {
			wp_die( $affiliate_id->get_error_message() );
		}

		$user_id = affwp_get_affiliate_user_id( $affiliate_id );

		// Get the pending account ID.
		$account_id = get_user_meta( $user_id, 'affwp_stripe_payouts_pending_account_id', true );

		if ( empty( $account_id ) ) {
			wp_die( __( 'No pending Stripe account found.', 'affiliate-wp' ) );
		}

		try {
			// Initialize Stripe API.
			affwp_stripe_payouts_init_api();

			// Retrieve the account to check its status
			$account = \Stripe\Account::retrieve( $account_id );

			if ( $account->details_submitted ) {
				// Account setup is complete, store the account ID permanently
				update_user_meta( $user_id, 'affwp_stripe_payouts_account_id', $account_id );
				delete_user_meta( $user_id, 'affwp_stripe_payouts_pending_account_id' );

				// Store the platform ID this affiliate is connected to
				$affiliate_id = affwp_get_affiliate_id( $user_id );
				if ( $affiliate_id ) {
					affwp_stripe_payouts_store_affiliate_platform( $affiliate_id );
				}

				// Redirect to affiliate area
				wp_safe_redirect(
					add_query_arg(
						[
							'stripe_connected' => '1',
						],
						affwp_get_affiliate_area_page_url( 'settings' )
					)
				);
				exit;
			} else {
				// Account setup is not complete, create a new account link
				$account_link = \Stripe\AccountLink::create(
					[
						'account'     => $account_id,
						'refresh_url' => add_query_arg(
							[
								'affwp_action'   => 'stripe_payouts_connect',
								'affiliate_id'   => $affiliate_id,
								'stripe_refresh' => '1',
							],
							affwp_get_affiliate_area_page_url( 'settings' )
						),
						'return_url'  => add_query_arg(
							[
								'affwp_action' => 'stripe_payouts_connect',
								'affiliate_id' => $affiliate_id,
								'code'         => 'completed',
							],
							affwp_get_affiliate_area_page_url( 'settings' )
						),
						'type'        => 'account_onboarding',
					]
				);

				// Redirect to the new account link
				wp_redirect( $account_link->url );
				exit;
			}
		} catch ( Exception $e ) {
			affwp_stripe_payouts_log_error( 'Error connecting affiliate to Stripe: ' . $e->getMessage() );

			// Delete the pending account ID
			delete_user_meta( $user_id, 'affwp_stripe_payouts_pending_account_id' );

			// Redirect with error
			wp_safe_redirect(
				add_query_arg(
					[
						'stripe_error' => urlencode( $e->getMessage() ),
					],
					affwp_get_affiliate_area_page_url( 'settings' )
				)
			);
			exit;
		}
	}

	/**
	 * Process affiliate disconnection from Stripe
	 *
	 * @since 2.29.0
	 * @return void
	 */
	public function process_affiliate_disconnect() {
		if ( ! isset( $_GET['affwp_action'] ) || $_GET['affwp_action'] != 'stripe_payouts_disconnect' ) {
			return;
		}

		// Validate affiliate ID
		if ( ! isset( $_GET['affiliate_id'] ) ) {
			wp_die( __( 'Affiliate ID is required.', 'affiliate-wp' ) );
		}

		$affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $_GET['affiliate_id'] );
		if ( is_wp_error( $affiliate_id ) ) {
			wp_die( $affiliate_id->get_error_message() );
		}

		// Check user permissions
		$permission_check = affwp_stripe_payouts_validate_user_permissions( $affiliate_id, 'disconnect' );
		if ( is_wp_error( $permission_check ) ) {
			wp_die( $permission_check->get_error_message() );
		}

		$user_id = affwp_get_affiliate_user_id( $affiliate_id );

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'affwp_stripe_payouts_disconnect_' . $affiliate_id ) ) {
			wp_die( __( 'Security check failed.', 'affiliate-wp' ) );
		}

		// Delete the Stripe account ID
		delete_user_meta( $user_id, 'affwp_stripe_payouts_account_id' );

		// Redirect to affiliate area
		if ( current_user_can( 'manage_affiliates' ) ) {
			// Admin is disconnecting an affiliate
			wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-affiliates&action=edit&affiliate_id=' . $affiliate_id . '&stripe_disconnected=1' ) );
		} else {
			// Affiliate is disconnecting themselves
			wp_safe_redirect(
				add_query_arg(
					[
						'stripe_disconnected' => '1',
					],
					affwp_get_affiliate_area_page_url( 'settings' )
				)
			);
		}
		exit;
	}
}

// Instantiate the class
AffiliateWP_Stripe_Connect::instance();
