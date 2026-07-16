<?php
/**
 * Helper Functions
 *
 * @package     AffiliateWP Stripe Payouts
 * @subpackage  Functions
 * @copyright   Copyright (c) 2025, AffiliateWP, LLC
 * @license     GPL-2.0+
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if Stripe is connected for the admin
 *
 * @since 2.29.0
 * @param bool $check_toggle Optional. Whether to check if the feature toggle is enabled. Default true.
 * @return bool True if connected, false otherwise
 */
function affwp_stripe_payouts_is_admin_connected( $check_toggle = true ) {
	// Platform account is connected if we have API keys configured.
	return affwp_stripe_payouts_is_configured( $check_toggle );
}

/**
 * Check if a specific affiliate has connected their Stripe account
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID.
 * @return bool True if connected, false otherwise
 */
function affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) {
	$user_id           = affwp_get_affiliate_user_id( $affiliate_id );
	$stripe_account_id = get_user_meta( $user_id, 'affwp_stripe_payouts_account_id', true );
	return ! empty( $stripe_account_id );
}

/**
 * Get the Stripe account ID for an affiliate
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID.
 * @return string The Stripe account ID
 */
function affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id ) {
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	return get_user_meta( $user_id, 'affwp_stripe_payouts_account_id', true );
}

/**
 * Get the admin Stripe account ID
 *
 * @since 2.29.0
 * @return string The Stripe account ID
 */
function affwp_stripe_payouts_get_admin_account_id() {
	return get_option( 'affwp_stripe_payouts_account_id', '' );
}

/**
 * Get the Stripe mode (test or live)
 *
 * @since 2.29.0
 * @return string The Stripe mode ('test' or 'live')
 */
function affwp_stripe_payouts_get_mode() {
	// Only check stripe_test_mode setting - single source of truth.
	$test_mode = affiliate_wp()->settings->get( 'stripe_test_mode', false );
	return $test_mode ? 'test' : 'live';
}

/**
 * Check if Stripe is in test/sandbox mode
 *
 * @since 2.29.0
 * @return bool True if in test mode, false if in live mode
 */
function affwp_stripe_payouts_is_test_mode() {
	return (bool) affiliate_wp()->settings->get( 'stripe_test_mode', false );
}

/**
 * Check if Stripe is in live/production mode
 *
 * @since 2.29.0
 * @return bool True if in live mode, false if in test mode
 */
function affwp_stripe_payouts_is_live_mode() {
	return ! affwp_stripe_payouts_is_test_mode();
}

/**
 * Get the Stripe client ID based on the current mode
 *
 * @since 2.29.0
 * @return string The Stripe client ID
 */
function affwp_stripe_payouts_get_client_id() {
	$mode              = affwp_stripe_payouts_get_mode();
	$client_id_setting = 'stripe_' . $mode . '_client_id';
	return affiliate_wp()->settings->get( $client_id_setting, '' );
}

/**
 * Get the Stripe secret key based on the current mode
 *
 * @since 2.29.0
 * @return string The Stripe secret key
 */
function affwp_stripe_payouts_get_secret_key() {
	$mode = affwp_stripe_payouts_get_mode();

	$secret_key_setting = 'stripe_' . $mode . '_secret_key';

	return affiliate_wp()->settings->get( $secret_key_setting, '' );
}

/**
 * Get the Stripe webhook secret
 *
 * @since 2.29.0
 * @return string The Stripe webhook secret
 */
function affwp_stripe_payouts_get_webhook_secret() {
	return affiliate_wp()->settings->get( 'stripe_webhook_secret', '' );
}

/**
 * Get the OAuth redirect URL for admin
 *
 * @since 2.29.0
 * @return string The OAuth redirect URL
 */
function affwp_stripe_payouts_get_admin_redirect_url() {
	return admin_url( 'admin.php?page=affiliate-wp-settings&tab=stripe_payouts&connect=1' );
}

/**
 * Get the OAuth redirect URL for affiliates
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID.
 * @return string The OAuth redirect URL
 */
function affwp_stripe_payouts_get_affiliate_redirect_url( $affiliate_id ) {
	return home_url(
		'/wp-json/affwp/v1/stripe/connect?' . http_build_query(
			[
				'affiliate_id' => $affiliate_id,
			]
		)
	);
}

/**
 * Format amount for Stripe (converts dollars to cents)
 *
 * @since 2.29.0
 * @param float $amount The amount in dollars.
 * @return int The amount in cents
 */
function affwp_stripe_payouts_format_amount( $amount ) {
	return round( $amount * 100 );
}

/**
 * Log an error message
 *
 * @since 2.29.0
 * @param string $message The error message.
 * @param array  $data Additional data to log.
 * @return void
 */
function affwp_stripe_payouts_log_error( $message, $data = [] ) {
	// Logging temporarily disabled - will be re-enabled with admin setting in next release
	return;

	// Create logs directory if it doesn't exist
	$upload_dir = wp_upload_dir();
	$logs_dir   = trailingslashit( $upload_dir['basedir'] ) . 'affwp-stripe-payouts-logs';

	if ( ! file_exists( $logs_dir ) ) {
		wp_mkdir_p( $logs_dir );
	}

	// Create .htaccess file to protect logs
	$htaccess_file = trailingslashit( $logs_dir ) . '.htaccess';
	if ( ! file_exists( $htaccess_file ) ) {
		$htaccess_content = 'deny from all';
		@file_put_contents( $htaccess_file, $htaccess_content );
	}

	// Create index.php file to prevent directory listing
	$index_file = trailingslashit( $logs_dir ) . 'index.php';
	if ( ! file_exists( $index_file ) ) {
		$index_content = '<?php // Silence is golden';
		@file_put_contents( $index_file, $index_content );
	}

	// Format the log entry
	$log_entry = '[' . date_i18n( 'Y-m-d H:i:s' ) . '] ' . $message . "\n";

	if ( ! empty( $data ) ) {
		$log_entry .= print_r( $data, true ) . "\n";
	}

	// Write to log file
	$log_file = trailingslashit( $logs_dir ) . 'stripe-payouts-' . date_i18n( 'Y-m-d' ) . '.log';
	@file_put_contents( $log_file, $log_entry, FILE_APPEND );
}

/**
 * Log balance state before payout attempt
 *
 * @since 2.29.0
 * @param float  $payout_amount Amount attempting to payout
 * @param string $currency Currency code
 * @return void
 */
function affwp_stripe_payouts_log_balance_state( $payout_amount, $currency = 'usd' ) {
	$balance = affwp_stripe_payouts_check_platform_balance();

	if ( is_wp_error( $balance ) ) {
		affwp_stripe_payouts_log_error(
			'Failed to retrieve balance state before payout',
			[ 'error' => $balance->get_error_message() ]
		);
		return;
	}

	$available_balance = 0;
	if ( isset( $balance['available'] ) && is_array( $balance['available'] ) ) {
		foreach ( $balance['available'] as $balance_item ) {
			if ( isset( $balance_item['currency'] ) && $balance_item['currency'] === strtolower( $currency ) ) {
				$available_balance = isset( $balance_item['amount'] ) ? $balance_item['amount'] / 100 : 0;
				break;
			}
		}
	}

	$log_data = [
		'requested_payout'  => $payout_amount,
		'available_balance' => $available_balance,
		'currency'          => $currency,
		'sufficient_funds'  => ( $payout_amount <= $available_balance ),
		'timestamp'         => current_time( 'mysql' ),
	];

	affwp_stripe_payouts_log_info(
		sprintf(
			'Balance check: Requested %s %s, Available %s %s',
			$payout_amount,
			strtoupper( $currency ),
			$available_balance,
			strtoupper( $currency )
		),
		$log_data
	);
}

/**
 * Log payment intent association with referral
 *
 * @since 2.29.0
 * @param int    $referral_id Referral ID
 * @param string $payment_intent_id Payment intent ID from Stripe
 * @param float  $amount Amount of the payment
 * @return void
 */
function affwp_stripe_payouts_log_payment_association( $referral_id, $payment_intent_id, $amount ) {
	$log_data = [
		'referral_id'       => $referral_id,
		'payment_intent_id' => $payment_intent_id,
		'amount'            => $amount,
		'timestamp'         => current_time( 'mysql' ),
	];

	affwp_stripe_payouts_log_info(
		sprintf(
			'Payment intent %s associated with referral #%d for amount %s',
			$payment_intent_id,
			$referral_id,
			affwp_currency_filter( $amount )
		),
		$log_data
	);
}

/**
 * Log Stripe Payouts info messages
 *
 * @since 2.29.0
 * @param string $message The info message to log
 * @param array  $data    Optional additional data to log
 * @return void
 */
function affwp_stripe_payouts_log_info( $message, $data = [] ) {
	// Logging temporarily disabled - will be re-enabled with admin setting in next release
	return;

	// Only log if debug mode is enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		affwp_stripe_payouts_log_error( '[INFO] ' . $message, $data );
	}
}

/**
 * Initialize the Stripe API
 *
 * @since 2.29.0
 * @param string|null $api_key Optional API key to use. If null, uses the saved setting.
 * @return bool True if API was initialized, false otherwise.
 */
function affwp_stripe_payouts_init_api( $api_key = null ) {
	// Check if the Stripe PHP SDK is already loaded
	if ( ! class_exists( '\Stripe\Stripe' ) ) {
		// If not loaded, require the Composer autoloader
		$autoloader = AFFILIATEWP_PLUGIN_DIR . 'vendor/autoload.php';

		if ( file_exists( $autoloader ) ) {
			require_once $autoloader;
		} else {
			// Log an error if the autoloader is not found
			affwp_stripe_payouts_log_error( 'Stripe PHP SDK not found. Please install via Composer.' );
			return false;
		}
	}

	// Use provided key if available, otherwise fetch from settings
	$key_to_use = $api_key ? $api_key : affwp_stripe_payouts_get_secret_key();

	if ( empty( $key_to_use ) ) {
		affwp_stripe_payouts_log_error( 'Stripe API key is empty, cannot initialize API.' );
		return false;
	}

	// Set the API key
	\Stripe\Stripe::setApiKey( $key_to_use );
	return true;
}

/**
 * Check if required Stripe settings are configured
 *
 * @since 2.29.0
 * @param bool $check_toggle Optional. Whether to check if the feature toggle is enabled. Default true.
 * @return bool True if configured, false otherwise
 */
function affwp_stripe_payouts_is_configured( $check_toggle = true ) {
	// Check if Stripe payouts are enabled (unless bypassed).
	if ( $check_toggle ) {
		$is_enabled = affiliate_wp()->settings->get( 'stripe_payouts', false );
		if ( ! $is_enabled ) {
			return false;
		}
	}

	// Get the secret key for the current mode (test or live)
	$secret_key = affwp_stripe_payouts_get_secret_key();

	// Configuration requires a secret key for the active mode (and optionally the toggle to be on)
	return ! empty( $secret_key );
}

/**
 * Generate an Account Link for an affiliate to manage their Stripe Express account
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return string|WP_Error The Account Link URL or WP_Error on failure
 */
function affwp_stripe_payouts_generate_account_link( $affiliate_id ) {
	// Check if affiliate exists
	if ( ! affiliate_wp()->affiliates->get_by( 'affiliate_id', $affiliate_id ) ) {
		return new WP_Error( 'invalid_affiliate', __( 'Invalid affiliate.', 'affiliate-wp' ) );
	}

	// Check if the current user is the affiliate or an admin
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );

	if ( get_current_user_id() != $user_id && ! current_user_can( 'manage_affiliates' ) ) {
		return new WP_Error( 'permission_denied', __( 'You do not have permission to manage this affiliate account.', 'affiliate-wp' ) );
	}

	// Check if affiliate is connected to Stripe
	if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
		return new WP_Error( 'not_connected', __( 'Affiliate is not connected to Stripe.', 'affiliate-wp' ) );
	}

	// Get the Stripe account ID
	$account_id = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );

	if ( empty( $account_id ) ) {
		return new WP_Error( 'no_account_id', __( 'No Stripe account ID found for this affiliate.', 'affiliate-wp' ) );
	}

	// Check if Stripe is configured
	if ( ! affwp_stripe_payouts_is_configured() ) {
		return new WP_Error( 'not_configured', __( 'Stripe is not configured. Please contact the site administrator.', 'affiliate-wp' ) );
	}

	try {
		// Initialize Stripe API
		if ( ! affwp_stripe_payouts_init_api() ) {
			return new WP_Error( 'api_init_failed', __( 'Failed to initialize Stripe API.', 'affiliate-wp' ) );
		}

		// Clear any cached account status to ensure fresh data
		$cache_key = 'affwp_stripe_account_status_' . $account_id;
		delete_transient( $cache_key );

		// Retrieve the account to check its status
		$account = \Stripe\Account::retrieve( $account_id );

		// Log account status for debugging
		affwp_stripe_payouts_log_error(
			'Account status for ' . $account_id,
			[
				'details_submitted' => $account->details_submitted,
				'charges_enabled'   => $account->charges_enabled,
				'payouts_enabled'   => $account->payouts_enabled,
				'requirements'      => $account->requirements->toArray(),
			]
		);

		// Determine the appropriate Account Link type based on account status
		$link_type = $account->details_submitted ? 'account_update' : 'account_onboarding';

		affwp_stripe_payouts_log_error( 'Using link type: ' . $link_type . ' for account ' . $account_id );

		// Try to create account link with the determined type
		try {
			$account_link = \Stripe\AccountLink::create(
				[
					'account'     => $account_id,
					'refresh_url' => home_url(
						'/wp-json/affwp/v1/stripe/manage?' . http_build_query(
							[
								'affiliate_id' => $affiliate_id,
								'_wpnonce'     => wp_create_nonce( 'affwp_stripe_payouts_manage_' . $affiliate_id ),
							]
						)
					),
					'return_url'  => add_query_arg(
						[
							'stripe_manage_complete' => '1',
						],
						affwp_get_affiliate_area_page_url( 'settings' )
					),
					'type'        => $link_type,
				]
			);

			return $account_link->url;

		} catch ( \Stripe\Exception\InvalidRequestException $e ) {
			// If account_update fails, try account_onboarding as fallback
			if ( $link_type === 'account_update' && strpos( $e->getMessage(), 'account_update' ) !== false ) {
				affwp_stripe_payouts_log_error( 'account_update failed, trying account_onboarding for ' . $account_id );

				$account_link = \Stripe\AccountLink::create(
					[
						'account'     => $account_id,
						'refresh_url' => home_url(
							'/wp-json/affwp/v1/stripe/manage?' . http_build_query(
								[
									'affiliate_id' => $affiliate_id,
									'_wpnonce'     => wp_create_nonce( 'affwp_stripe_payouts_manage_' . $affiliate_id ),
								]
							)
						),
						'return_url'  => add_query_arg(
							[
								'stripe_manage_complete' => '1',
							],
							affwp_get_affiliate_area_page_url( 'settings' )
						),
						'type'        => 'account_onboarding',
					]
				);

				return $account_link->url;
			}

			// Re-throw if it's a different error
			throw $e;
		}
	} catch ( Exception $e ) {
		affwp_stripe_payouts_log_error( 'Error generating account link for affiliate ' . $affiliate_id . ': ' . $e->getMessage() );
		return new WP_Error( 'stripe_error', sprintf( __( 'Error generating account link: %s', 'affiliate-wp' ), $e->getMessage() ) );
	}
}

/**
 * Generate an Express Dashboard login link for an affiliate to manage their Stripe Express account
 *
 * This provides direct access to the Stripe Express Dashboard for daily management tasks
 * like viewing balances, updating bank information, and managing payouts.
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return string|WP_Error The Express Dashboard login URL or WP_Error on failure
 */
function affwp_stripe_payouts_generate_express_dashboard_link( $affiliate_id ) {
	// Check if affiliate exists
	if ( ! affiliate_wp()->affiliates->get_by( 'affiliate_id', $affiliate_id ) ) {
		return new WP_Error( 'invalid_affiliate', __( 'Invalid affiliate.', 'affiliate-wp' ) );
	}

	// Check if the current user is the affiliate or an admin
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );

	if ( get_current_user_id() != $user_id && ! current_user_can( 'manage_affiliates' ) ) {
		return new WP_Error( 'permission_denied', __( 'You do not have permission to manage this affiliate account.', 'affiliate-wp' ) );
	}

	// Check if affiliate is connected to Stripe
	if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
		return new WP_Error( 'not_connected', __( 'Affiliate is not connected to Stripe.', 'affiliate-wp' ) );
	}

	// Get the Stripe account ID
	$account_id = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );

	if ( empty( $account_id ) ) {
		return new WP_Error( 'no_account_id', __( 'No Stripe account ID found for this affiliate.', 'affiliate-wp' ) );
	}

	// Check if Stripe is configured
	if ( ! affwp_stripe_payouts_is_configured() ) {
		return new WP_Error( 'not_configured', __( 'Stripe is not configured. Please contact the site administrator.', 'affiliate-wp' ) );
	}

	try {
		// Initialize Stripe API
		if ( ! affwp_stripe_payouts_init_api() ) {
			return new WP_Error( 'api_init_failed', __( 'Failed to initialize Stripe API.', 'affiliate-wp' ) );
		}

		// Create Express Dashboard login link
		// This provides direct access to the Stripe Express Dashboard
		$login_link = \Stripe\Account::createLoginLink( $account_id );

		affwp_stripe_payouts_log_error( 'Generated Express Dashboard login link for account ' . $account_id );

		return $login_link->url;

	} catch ( \Stripe\Exception\InvalidRequestException $e ) {
		// This error typically occurs if the account is not an Express account
		// or if the account hasn't completed onboarding
		affwp_stripe_payouts_log_error( 'Error generating Express Dashboard link for affiliate ' . $affiliate_id . ': ' . $e->getMessage() );

		// Check if it's because the account isn't Express or hasn't completed onboarding
		if ( strpos( $e->getMessage(), 'express' ) !== false || strpos( $e->getMessage(), 'login_link' ) !== false ) {
			return new WP_Error( 'not_express_account', __( 'This feature is only available for Express accounts that have completed onboarding.', 'affiliate-wp' ) );
		}

		return new WP_Error( 'stripe_error', sprintf( __( 'Error generating dashboard link: %s', 'affiliate-wp' ), $e->getMessage() ) );
	} catch ( Exception $e ) {
		affwp_stripe_payouts_log_error( 'Error generating Express Dashboard link for affiliate ' . $affiliate_id . ': ' . $e->getMessage() );
		return new WP_Error( 'stripe_error', sprintf( __( 'Error generating dashboard link: %s', 'affiliate-wp' ), $e->getMessage() ) );
	}
}

/**
 * Validate affiliate ID
 *
 * @since 2.29.0
 * @param mixed $affiliate_id
 * @return int|WP_Error
 */
function affwp_stripe_payouts_validate_affiliate_id( $affiliate_id ) {
	$affiliate_id = absint( $affiliate_id );

	if ( $affiliate_id <= 0 ) {
		return new WP_Error( 'invalid_affiliate_id', __( 'Invalid affiliate ID.', 'affiliate-wp' ) );
	}

	// Check if affiliate exists
	$affiliate = affwp_get_affiliate( $affiliate_id );
	if ( ! $affiliate ) {
		return new WP_Error( 'affiliate_not_found', __( 'Affiliate not found.', 'affiliate-wp' ) );
	}

	return $affiliate_id;
}

/**
 * Validate referral ID
 *
 * @since 2.29.0
 * @param mixed $referral_id
 * @return int|WP_Error
 */
function affwp_stripe_payouts_validate_referral_id( $referral_id ) {
	$referral_id = absint( $referral_id );

	if ( $referral_id <= 0 ) {
		return new WP_Error( 'invalid_referral_id', __( 'Invalid referral ID.', 'affiliate-wp' ) );
	}

	// Check if referral exists
	$referral = affwp_get_referral( $referral_id );
	if ( ! $referral ) {
		return new WP_Error( 'referral_not_found', __( 'Referral not found.', 'affiliate-wp' ) );
	}

	return $referral_id;
}

/**
 * Validate amount value
 *
 * @since 2.29.0
 * @param mixed $amount
 * @param float $min_amount Minimum allowed amount (default: 0.50)
 * @param float $max_amount Maximum allowed amount (default: 999999.99)
 * @return float|WP_Error
 */
function affwp_stripe_payouts_validate_amount( $amount, $min_amount = 0.50, $max_amount = 999999.99 ) {
	// Sanitize and convert to float
	$amount = floatval( $amount );

	if ( $amount < $min_amount ) {
		return new WP_Error( 'amount_too_small', sprintf( __( 'Amount must be at least %s.', 'affiliate-wp' ), affwp_currency_filter( $min_amount ) ) );
	}

	if ( $amount > $max_amount ) {
		return new WP_Error( 'amount_too_large', sprintf( __( 'Amount cannot exceed %s.', 'affiliate-wp' ), affwp_currency_filter( $max_amount ) ) );
	}

	return $amount;
}

/**
 * Validate email address
 *
 * @since 2.29.0
 * @param string $email
 * @return string|WP_Error
 */
function affwp_stripe_payouts_validate_email( $email ) {
	$email = sanitize_email( $email );

	if ( empty( $email ) ) {
		return new WP_Error( 'invalid_email', __( 'Email address is required.', 'affiliate-wp' ) );
	}

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email_format', __( 'Please enter a valid email address.', 'affiliate-wp' ) );
	}

	return $email;
}

/**
 * Validate URL
 *
 * @since 2.29.0
 * @param string $url
 * @param bool   $require_https Whether to require HTTPS (default: false)
 * @return string|WP_Error
 */
function affwp_stripe_payouts_validate_url( $url, $require_https = false ) {
	$url = esc_url_raw( $url );

	if ( empty( $url ) ) {
		return new WP_Error( 'invalid_url', __( 'URL is required.', 'affiliate-wp' ) );
	}

	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return new WP_Error( 'invalid_url_format', __( 'Please enter a valid URL.', 'affiliate-wp' ) );
	}

	if ( $require_https && strpos( $url, 'https://' ) !== 0 ) {
		return new WP_Error( 'url_not_https', __( 'URL must use HTTPS.', 'affiliate-wp' ) );
	}

	return $url;
}

/**
 * Validate text field with length limits
 *
 * @since 2.29.0
 * @param string $text
 * @param int    $min_length Minimum length (default: 0)
 * @param int    $max_length Maximum length (default: 255)
 * @param bool   $allow_empty Whether to allow empty values (default: true)
 * @return string|WP_Error
 */
function affwp_stripe_payouts_validate_text_field( $text, $min_length = 0, $max_length = 255, $allow_empty = true ) {
	$text = sanitize_text_field( $text );

	if ( ! $allow_empty && empty( $text ) ) {
		return new WP_Error( 'field_required', __( 'This field is required.', 'affiliate-wp' ) );
	}

	$length = strlen( $text );

	if ( ! empty( $text ) && $length < $min_length ) {
		return new WP_Error( 'text_too_short', sprintf( __( 'Text must be at least %d characters long.', 'affiliate-wp' ), $min_length ) );
	}

	if ( $length > $max_length ) {
		return new WP_Error( 'text_too_long', sprintf( __( 'Text cannot exceed %d characters.', 'affiliate-wp' ), $max_length ) );
	}

	return $text;
}

/**
 * Check platform Stripe balance using MCP
 *
 * @since 2.29.0
 * @return array|WP_Error Balance data or error
 */
function affwp_stripe_payouts_check_platform_balance() {
	try {
		// Initialize Stripe API
		affwp_stripe_payouts_init_api();

		// Get the platform account balance
		$balance = \Stripe\Balance::retrieve();

		// Convert Stripe balance object to array format
		$formatted_balance = [
			'available' => [],
			'pending'   => [],
		];

		// Format available balances
		if ( ! empty( $balance->available ) ) {
			foreach ( $balance->available as $bal ) {
				$formatted_balance['available'][] = [
					'amount'   => $bal->amount,
					'currency' => $bal->currency,
				];
			}
		}

		// Format pending balances
		if ( ! empty( $balance->pending ) ) {
			foreach ( $balance->pending as $bal ) {
				$formatted_balance['pending'][] = [
					'amount'   => $bal->amount,
					'currency' => $bal->currency,
				];
			}
		}

		return $formatted_balance;

	} catch ( Exception $e ) {
		return new WP_Error( 'balance_check_failed', $e->getMessage() );
	}
}

/**
 * Verify platform has sufficient funds for payout
 *
 * @since 2.29.0
 * @param float  $amount Amount to payout in dollars
 * @param string $currency Currency code (default: usd)
 * @return bool|WP_Error True if sufficient funds, error otherwise
 */
function affwp_stripe_payouts_verify_sufficient_funds( $amount, $currency = 'usd' ) {
	$balance = affwp_stripe_payouts_check_platform_balance();

	if ( is_wp_error( $balance ) ) {
		return $balance;
	}

	// Convert amount to cents for comparison
	$amount_cents = affwp_stripe_payouts_format_amount( $amount );

	// Find the balance for the specified currency
	$available_balance = 0;
	if ( isset( $balance['available'] ) && is_array( $balance['available'] ) ) {
		foreach ( $balance['available'] as $balance_item ) {
			if ( isset( $balance_item['currency'] ) && $balance_item['currency'] === strtolower( $currency ) ) {
				$available_balance = isset( $balance_item['amount'] ) ? absint( $balance_item['amount'] ) : 0;
				break;
			}
		}
	}

	if ( $amount_cents > $available_balance ) {
		return new WP_Error(
			'insufficient_funds',
			sprintf(
				__( 'Insufficient funds. Required: %1$s, Available: %2$s', 'affiliate-wp' ),
				affwp_currency_filter( $amount ),
				affwp_currency_filter( $available_balance / 100 )
			)
		);
	}

	return true;
}

/**
 * Get formatted balance display for admin
 *
 * @since 2.29.0
 * @return string HTML formatted balance display
 */
function affwp_stripe_payouts_get_balance_display() {
	$balance = affwp_stripe_payouts_check_platform_balance();

	if ( is_wp_error( $balance ) ) {
		return '<span class="affwp-stripe-balance-error">' . esc_html( $balance->get_error_message() ) . '</span>';
	}

	$output = '<div class="affwp-stripe-balance-display">';

	// Display available balance
	if ( isset( $balance['available'] ) && is_array( $balance['available'] ) ) {
		$output .= '<div class="affwp-stripe-balance-available">';
		$output .= '<strong>' . __( 'Available Balance:', 'affiliate-wp' ) . '</strong> ';

		foreach ( $balance['available'] as $balance_item ) {
			if ( isset( $balance_item['amount'] ) && isset( $balance_item['currency'] ) ) {
				$amount  = $balance_item['amount'] / 100; // Convert from cents
				$output .= '<span class="affwp-balance-amount">';
				$output .= esc_html( affwp_currency_filter( $amount ) ) . ' ' . strtoupper( $balance_item['currency'] );
				$output .= '</span> ';
			}
		}
		$output .= '</div>';
	}

	// Display pending balance
	if ( isset( $balance['pending'] ) && is_array( $balance['pending'] ) && ! empty( $balance['pending'] ) ) {
		$has_pending = false;
		foreach ( $balance['pending'] as $balance_item ) {
			if ( isset( $balance_item['amount'] ) && $balance_item['amount'] > 0 ) {
				$has_pending = true;
				break;
			}
		}

		if ( $has_pending ) {
			$output .= '<div class="affwp-stripe-balance-pending">';
			$output .= '<strong>' . __( 'Pending Balance:', 'affiliate-wp' ) . '</strong> ';

			foreach ( $balance['pending'] as $balance_item ) {
				if ( isset( $balance_item['amount'] ) && $balance_item['amount'] > 0 && isset( $balance_item['currency'] ) ) {
					$amount  = $balance_item['amount'] / 100; // Convert from cents
					$output .= '<span class="affwp-balance-amount">';
					$output .= esc_html( affwp_currency_filter( $amount ) ) . ' ' . strtoupper( $balance_item['currency'] );
					$output .= '</span> ';
				}
			}
			$output .= '</div>';
		}
	}

	$output .= '</div>';

	return $output;
}

/**
 * Get cached balance from transient
 *
 * @since 2.29.0
 * @return array|false Balance data or false if not cached
 */
function affwp_stripe_payouts_get_cached_balance() {
	$cache_key = 'affwp_stripe_' . ( affwp_stripe_payouts_is_testing_mode() ? 'test' : 'live' ) . '_balance';
	$cached    = get_transient( $cache_key );

	// If no cache exists and we have API credentials, fetch fresh balance
	if ( $cached === false && affwp_stripe_payouts_is_configured() ) {
		$fresh_balance = affwp_stripe_payouts_check_platform_balance();
		if ( ! is_wp_error( $fresh_balance ) ) {
			// Cache for 1 hour
			affwp_stripe_payouts_set_cached_balance( $fresh_balance, 3600 );
			return $fresh_balance;
		}
	}

	return $cached;
}

/**
 * Set cached balance in transient
 *
 * @since 2.29.0
 * @param array $balance Balance data from Stripe
 * @param int   $expiration Expiration time in seconds (default 1 hour)
 * @return bool Success
 */
function affwp_stripe_payouts_set_cached_balance( $balance, $expiration = 3600 ) {
	if ( ! is_array( $balance ) ) {
		return false;
	}

	$cache_key = 'affwp_stripe_' . ( affwp_stripe_payouts_is_testing_mode() ? 'test' : 'live' ) . '_balance';
	return set_transient( $cache_key, $balance, $expiration );
}

/**
 * Clear cached balance
 *
 * @since 2.29.0
 * @return bool Success
 */
function affwp_stripe_payouts_clear_cached_balance() {
	$cache_key = 'affwp_stripe_' . ( affwp_stripe_payouts_is_testing_mode() ? 'test' : 'live' ) . '_balance';
	return delete_transient( $cache_key );
}

/**
 * Get formatted balance amount for display
 *
 * @since 2.29.0
 * @param array  $balance Balance data from Stripe or cache
 * @param string $currency Currency code (default null to use AffiliateWP currency or first available)
 * @return string Formatted balance amount
 */
function affwp_stripe_payouts_format_balance_amount( $balance, $currency = null ) {
	if ( ! is_array( $balance ) || ! isset( $balance['available'] ) ) {
		// Format zero with proper decimal places
		$formatted = affwp_format_amount( 0 );
		return affwp_currency_filter( $formatted );
	}

	// If no currency specified, try to use AffiliateWP's configured currency
	if ( $currency === null ) {
		$currency = strtolower( affwp_get_currency() );
	}

	// Find the balance for the specified currency
	foreach ( $balance['available'] as $balance_item ) {
		if ( isset( $balance_item['currency'] ) && strtolower( $balance_item['currency'] ) === strtolower( $currency ) ) {
			$amount = isset( $balance_item['amount'] ) ? $balance_item['amount'] / 100 : 0;
			// Format the amount with proper thousands separator and decimal places
			$formatted = affwp_format_amount( $amount );
			return affwp_currency_filter( $formatted );
		}
	}

	// If no matching currency found, return first available balance
	if ( ! empty( $balance['available'][0]['amount'] ) ) {
		$amount = $balance['available'][0]['amount'] / 100;
		// Format the amount with proper thousands separator and decimal places
		$formatted = affwp_format_amount( $amount );
		return affwp_currency_filter( $formatted );
	}

	// Format zero with proper decimal places
	$formatted = affwp_format_amount( 0 );
	return affwp_currency_filter( $formatted );
}

/**
 * Validate Stripe OAuth authorization code
 *
 * @since 2.29.0
 * @param string $code
 * @return string|WP_Error
 */
function affwp_stripe_payouts_validate_oauth_code( $code ) {
	$code = sanitize_text_field( $code );

	if ( empty( $code ) ) {
		return new WP_Error( 'missing_code', __( 'Authorization code is missing.', 'affiliate-wp' ) );
	}

	// Special case for "completed" status from Express Connect flow
	if ( $code === 'completed' ) {
		return $code;
	}

	// Validate OAuth code format (Stripe codes are typically alphanumeric, 20+ chars)
	if ( ! preg_match( '/^[a-zA-Z0-9_-]{20,}$/', $code ) ) {
		return new WP_Error( 'invalid_code_format', __( 'Invalid authorization code format.', 'affiliate-wp' ) );
	}

	return $code;
}

/**
 * Validate Stripe error parameter from OAuth flow
 *
 * @since 2.29.0
 * @param string $error
 * @return string|WP_Error
 */
function affwp_stripe_payouts_validate_stripe_error( $error ) {
	$error = sanitize_text_field( $error );

	if ( empty( $error ) ) {
		return new WP_Error( 'missing_error', __( 'Error parameter is missing.', 'affiliate-wp' ) );
	}

	// Validate error parameter format (should be alphanumeric with underscores)
	if ( ! preg_match( '/^[a-zA-Z_]{1,50}$/', $error ) ) {
		return new WP_Error( 'invalid_error_format', __( 'Invalid error parameter format.', 'affiliate-wp' ) );
	}

	return $error;
}

/**
 * Validate nonce with specific action
 *
 * @since 2.29.0
 * @param string $nonce_value
 * @param string $action
 * @return bool|WP_Error
 */
function affwp_stripe_payouts_validate_nonce( $nonce_value, $action ) {
	if ( empty( $nonce_value ) ) {
		return new WP_Error( 'missing_nonce', __( 'Security token is missing.', 'affiliate-wp' ) );
	}

	if ( ! wp_verify_nonce( $nonce_value, $action ) ) {
		return new WP_Error( 'invalid_nonce', __( 'Security check failed.', 'affiliate-wp' ) );
	}

	return true;
}

/**
 * Validate AJAX request context
 *
 * @since 2.29.0
 * @param string $capability Required capability (default: 'manage_affiliate_options')
 * @return bool|WP_Error
 */
function affwp_stripe_payouts_validate_ajax_request( $capability = 'manage_affiliate_options' ) {
	// Check if it's an AJAX request
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return new WP_Error( 'not_ajax', __( 'Invalid request type.', 'affiliate-wp' ) );
	}

	// Check user capability
	if ( ! current_user_can( $capability ) ) {
		return new WP_Error( 'insufficient_permissions', __( 'You do not have permission to perform this action.', 'affiliate-wp' ) );
	}

	return true;
}

/**
 * Sanitize and validate error message for display
 *
 * @since 2.29.0
 * @param string $message
 * @param int    $max_length Maximum message length (default: 500)
 * @return string
 */
function affwp_stripe_payouts_sanitize_error_message( $message, $max_length = 500 ) {
	// Sanitize the message
	$message = sanitize_text_field( $message );

	// Truncate if too long
	if ( strlen( $message ) > $max_length ) {
		$message = substr( $message, 0, $max_length - 3 ) . '...';
	}

	return $message;
}

/**
 * Generate OAuth link for Stripe Connect
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return string|WP_Error The OAuth URL or error
 */
function affwp_stripe_payouts_generate_oauth_link( $affiliate_id ) {
	// Validate affiliate
	$validated = affwp_stripe_payouts_validate_affiliate_id( $affiliate_id );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}

	// Get client ID
	$client_id = affwp_stripe_payouts_get_client_id();
	if ( empty( $client_id ) ) {
		return new WP_Error( 'no_client_id', __( 'Stripe client ID is not configured', 'affiliate-wp' ) );
	}

	// Get redirect URI
	$redirect_uri = home_url(
		'/wp-json/affwp/v1/stripe/return?' . http_build_query(
			[
				'affiliate_id' => $affiliate_id,
			]
		)
	);

	// Generate state for security
	$state = wp_create_nonce( 'stripe_oauth_' . $affiliate_id );

	// Store state temporarily
	set_transient( 'affwp_stripe_oauth_state_' . $affiliate_id, $state, HOUR_IN_SECONDS );

	// Build OAuth URL
	$oauth_url = add_query_arg(
		[
			'response_type'           => 'code',
			'client_id'               => $client_id,
			'scope'                   => 'read_write',
			'redirect_uri'            => urlencode( $redirect_uri ),
			'state'                   => $state,
			'stripe_user[email]'      => urlencode( affwp_get_affiliate_email( $affiliate_id ) ),
			'stripe_user[first_name]' => urlencode( affwp_get_affiliate_first_name( $affiliate_id ) ),
			'stripe_user[last_name]'  => urlencode( affwp_get_affiliate_last_name( $affiliate_id ) ),
		],
		'https://connect.stripe.com/oauth/authorize'
	);

	return $oauth_url;
}

/**
 * Process OAuth callback and exchange code for access token
 *
 * @since 2.29.0
 * @param string $code The OAuth authorization code
 * @param string $state The OAuth state parameter
 * @return int|WP_Error The affiliate ID on success, WP_Error on failure
 */
function affwp_stripe_payouts_process_oauth_callback( $code, $state ) {
	// Extract affiliate ID from state
	$state_parts = explode( '_', $state );
	if ( count( $state_parts ) < 2 ) {
		return new WP_Error( 'invalid_state', __( 'Invalid state parameter.', 'affiliate-wp' ) );
	}

	$affiliate_id = absint( $state_parts[1] );
	if ( ! $affiliate_id ) {
		return new WP_Error( 'invalid_affiliate', __( 'Invalid affiliate ID in state.', 'affiliate-wp' ) );
	}

	// Verify state
	$stored_state = get_transient( 'affwp_stripe_oauth_state_' . $affiliate_id );
	if ( ! $stored_state || $stored_state !== $state ) {
		return new WP_Error( 'state_mismatch', __( 'OAuth state mismatch.', 'affiliate-wp' ) );
	}

	// Clear the state transient
	delete_transient( 'affwp_stripe_oauth_state_' . $affiliate_id );

	// Initialize Stripe API
	if ( ! affwp_stripe_payouts_init_api() ) {
		return new WP_Error( 'api_init_failed', __( 'Failed to initialize Stripe API.', 'affiliate-wp' ) );
	}

	try {
		// Exchange code for access token
		$response = \Stripe\OAuth::token(
			[
				'grant_type' => 'authorization_code',
				'code'       => $code,
			]
		);

		if ( empty( $response->stripe_user_id ) ) {
			return new WP_Error( 'no_account_id', __( 'No Stripe account ID returned.', 'affiliate-wp' ) );
		}

		// Save the account ID
		$user_id = affwp_get_affiliate_user_id( $affiliate_id );
		if ( ! $user_id ) {
			return new WP_Error( 'invalid_user', __( 'Invalid affiliate user.', 'affiliate-wp' ) );
		}

		update_user_meta( $user_id, 'affwp_stripe_payouts_account_id', $response->stripe_user_id );
		update_user_meta( $user_id, 'affwp_stripe_payouts_connected', true );

		// Store the platform ID this affiliate is connected to
		affwp_stripe_payouts_store_affiliate_platform( $affiliate_id );

		// Log the connection
		affwp_stripe_payouts_log_info( sprintf( 'Affiliate #%d connected to Stripe account %s', $affiliate_id, $response->stripe_user_id ) );

		return $affiliate_id;

	} catch ( \Exception $e ) {
		return new WP_Error( 'oauth_exchange_failed', $e->getMessage() );
	}
}

/**
 * Disconnect an affiliate from Stripe
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function affwp_stripe_payouts_disconnect_affiliate( $affiliate_id ) {
	// Validate affiliate
	$validated = affwp_stripe_payouts_validate_affiliate_id( $affiliate_id );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}

	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( ! $user_id ) {
		return new WP_Error( 'invalid_user', __( 'Invalid affiliate user', 'affiliate-wp' ) );
	}

	// Get the account ID before disconnecting
	$account_id = get_user_meta( $user_id, 'affwp_stripe_payouts_account_id', true );

	// Delete Stripe metadata
	delete_user_meta( $user_id, 'affwp_stripe_payouts_account_id' );
	delete_user_meta( $user_id, 'affwp_stripe_payouts_account_status' );
	delete_user_meta( $user_id, 'affwp_stripe_payouts_connected' );

	// Clear any cached data
	if ( $account_id ) {
		delete_transient( 'affwp_stripe_account_status_' . $account_id );
	}

	// Log the disconnection
	affwp_stripe_payouts_log_info( sprintf( 'Affiliate #%d disconnected from Stripe account %s', $affiliate_id, $account_id ) );

	return true;
}

/**
 * Check if testing features should be enabled
 *
 * Testing features are enabled when:
 * 1. Stripe is in test mode (admin setting), OR
 * 2. The AFFWP_STRIPE_PAYOUTS_TESTING constant is defined (for developers)
 *
 * Testing features include:
 * - Test charge creation
 * - Debug information display
 * - Account testing utilities
 *
 * @since 2.29.0
 * @return bool True if testing features should be enabled
 */
function affwp_stripe_payouts_is_testing_mode() {
	// Enable testing features if admin has set test mode
	if ( affwp_stripe_payouts_is_test_api_mode() ) {
		return true;
	}

	// Also enable if developer constant is set (for advanced debugging)
	return defined( 'AFFWP_STRIPE_PAYOUTS_TESTING' ) && AFFWP_STRIPE_PAYOUTS_TESTING;
}

/**
 * Validate and sanitize webhook payload
 *
 * @since 2.29.0
 * @param string $payload
 * @param string $signature
 * @return array|WP_Error
 */
function affwp_stripe_payouts_validate_webhook_payload( $payload, $signature ) {
	if ( empty( $payload ) ) {
		return new WP_Error( 'empty_payload', __( 'Webhook payload is empty.', 'affiliate-wp' ) );
	}

	if ( empty( $signature ) ) {
		return new WP_Error( 'missing_signature', __( 'Webhook signature is missing.', 'affiliate-wp' ) );
	}

	// Validate signature format
	if ( ! preg_match( '/^t=\d+,v1=[a-f0-9]{64}/', $signature ) ) {
		return new WP_Error( 'invalid_signature_format', __( 'Invalid webhook signature format.', 'affiliate-wp' ) );
	}

	// Validate JSON payload
	$decoded = json_decode( $payload, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new WP_Error( 'invalid_json', __( 'Invalid JSON in webhook payload.', 'affiliate-wp' ) );
	}

	return $decoded;
}

/**
 * Validate user permissions for affiliate operations
 *
 * @since 2.29.0
 * @param int    $affiliate_id
 * @param string $operation
 * @return bool|WP_Error
 */
function affwp_stripe_payouts_validate_user_permissions( $affiliate_id, $operation = 'manage' ) {
	$current_user_id = get_current_user_id();

	if ( ! $current_user_id ) {
		return new WP_Error( 'not_logged_in', __( 'You must be logged in to perform this action.', 'affiliate-wp' ) );
	}

	// Validate affiliate ID first
	$validated_affiliate_id = affwp_stripe_payouts_validate_affiliate_id( $affiliate_id );
	if ( is_wp_error( $validated_affiliate_id ) ) {
		return $validated_affiliate_id;
	}

	$affiliate_user_id = affwp_get_affiliate_user_id( $validated_affiliate_id );

	// Check if user is the affiliate owner or has admin permissions
	if ( $current_user_id === $affiliate_user_id ) {
		return true; // User owns this affiliate
	}

	if ( current_user_can( 'manage_affiliates' ) ) {
		return true; // User has admin permissions
	}

	return new WP_Error( 'insufficient_permissions', __( 'You do not have permission to manage this affiliate.', 'affiliate-wp' ) );
}

/**
 * Rate limit check for sensitive operations
 *
 * @since 2.29.0
 * @param string $action
 * @param int    $user_id
 * @param int    $limit
 * @param int    $window_seconds
 * @return bool|WP_Error
 */
function affwp_stripe_payouts_check_rate_limit( $action, $user_id = null, $limit = 10, $window_seconds = 3600 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return new WP_Error( 'no_user', __( 'Unable to check rate limit without user ID.', 'affiliate-wp' ) );
	}

	$cache_key = 'affwp_stripe_rate_limit_' . $action . '_' . $user_id;
	$attempts  = get_transient( $cache_key );

	if ( false === $attempts ) {
		$attempts = 0;
	}

	if ( $attempts >= $limit ) {
		return new WP_Error(
			'rate_limit_exceeded',
			sprintf(
				__( 'Rate limit exceeded. Please wait %d minutes before trying again.', 'affiliate-wp' ),
				round( $window_seconds / 60 )
			)
		);
	}

	// Increment counter
	set_transient( $cache_key, $attempts + 1, $window_seconds );

	return true;
}

/**
 * Check if we're using Stripe test API keys
 *
 * @since 2.29.0
 * @return bool True if using test API keys
 */
function affwp_stripe_payouts_is_test_api_mode() {
	return 'test' === affwp_stripe_payouts_get_mode();
}

/**
 * Check if a referral has been previously paid via Stripe
 *
 * This function checks if a referral has a Stripe transfer ID in its meta,
 * indicating it was previously paid through Stripe, regardless of its current status.
 *
 * @since 2.29.0
 * @param int $referral_id The referral ID to check
 * @return bool True if the referral has been paid via Stripe before, false otherwise
 */
function affwp_stripe_payouts_referral_has_stripe_payment( $referral_id ) {
	$stripe_transfer_id = affwp_get_referral_meta( $referral_id, 'stripe_transfer_id', true );
	return ! empty( $stripe_transfer_id );
}

/**
 * Get the Stripe transfer ID for a referral
 *
 * @since 2.29.0
 * @param int $referral_id The referral ID
 * @return string|false The Stripe transfer ID or false if not found
 */
function affwp_stripe_payouts_get_referral_transfer_id( $referral_id ) {
	$stripe_transfer_id = affwp_get_referral_meta( $referral_id, 'stripe_transfer_id', true );
	return ! empty( $stripe_transfer_id ) ? $stripe_transfer_id : false;
}

/**
 * Check if a referral has failed Stripe transfers
 *
 * @since 2.29.0
 * @param int $referral_id The referral ID
 * @return bool True if the referral has failed transfers, false otherwise
 */
function affwp_stripe_payouts_referral_has_failed_transfer( $referral_id ) {
	$failed_transfer_id = affwp_get_referral_meta( $referral_id, 'stripe_transfer_failed', true );
	return ! empty( $failed_transfer_id );
}

/**
 * Check if a referral has reversed Stripe transfers
 *
 * @since 2.29.0
 * @param int $referral_id The referral ID
 * @return bool True if the referral has reversed transfers, false otherwise
 */
function affwp_stripe_payouts_referral_has_reversed_transfer( $referral_id ) {
	$reversed_transfer_id = affwp_get_referral_meta( $referral_id, 'stripe_transfer_reversed', true );
	return ! empty( $reversed_transfer_id );
}

/**
 * Check if cross-border payouts are enabled
 *
 * @since 2.29.0
 * @return bool True if cross-border payouts are enabled, false otherwise
 */
function affwp_stripe_payouts_cross_border_enabled() {
	return (bool) affiliate_wp()->settings->get( 'stripe_cross_border_payouts', false );
}

/**
 * Get the platform's Stripe account country
 *
 * @since 2.29.0
 * @return string|WP_Error The country code (e.g., 'US') or error if unable to retrieve
 */
function affwp_stripe_payouts_get_platform_country() {
	// Check if Stripe is configured
	if ( ! affwp_stripe_payouts_is_configured() ) {
		return new WP_Error( 'not_configured', __( 'Stripe is not configured', 'affiliate-wp' ) );
	}

	// Initialize Stripe API
	if ( ! affwp_stripe_payouts_init_api() ) {
		return new WP_Error( 'api_init_failed', __( 'Failed to initialize Stripe API', 'affiliate-wp' ) );
	}

	try {
		// Retrieve the platform account details
		$account = \Stripe\Account::retrieve();

		if ( ! isset( $account->country ) ) {
			return new WP_Error( 'no_country', __( 'Could not determine platform country', 'affiliate-wp' ) );
		}

		return strtoupper( $account->country );

	} catch ( Exception $e ) {
		affwp_stripe_payouts_log_error( 'Failed to retrieve platform country: ' . $e->getMessage() );
		return new WP_Error( 'api_error', $e->getMessage() );
	}
}

/**
 * Check if the platform supports cross-border payouts
 *
 * @since 2.29.0
 * @return bool True if the platform can do cross-border payouts, false otherwise
 */
function affwp_stripe_payouts_platform_supports_cross_border() {
	// Only US platforms can do cross-border payouts
	$platform_country = affwp_stripe_payouts_get_platform_country();

	if ( is_wp_error( $platform_country ) ) {
		return false;
	}

	return $platform_country === 'US';
}

/**
 * Check if an affiliate's country is supported for cross-border payouts
 *
 * @since 2.29.0
 * @param string $country The affiliate's country code
 * @return bool True if the country is supported, false otherwise
 */
function affwp_stripe_payouts_is_country_supported( $country ) {
	// List of countries that support receiving cross-border payouts from US platforms
	// Based on Stripe documentation: https://docs.stripe.com/connect/cross-border-payouts
	$supported_countries = [
		'AT', 'AU', 'BE', 'BG', 'CA', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES',
		'FI', 'FR', 'GB', 'GI', 'GR', 'HK', 'HR', 'HU', 'ID', 'IE', 'IN', 'IT',
		'JP', 'LI', 'LT', 'LU', 'LV', 'MT', 'MX', 'MY', 'NL', 'NO', 'NZ', 'PL',
		'PT', 'RO', 'SE', 'SG', 'SI', 'SK', 'TH', 'US',
	];

	return in_array( strtoupper( $country ), $supported_countries, true );
}

/**
 * Determine if we should create a recipient account for an affiliate
 *
 * @since 2.29.0
 * @param string $affiliate_country The affiliate's country code
 * @return bool True if we should create a recipient account, false for standard Express
 */
function affwp_stripe_payouts_should_create_recipient_account( $affiliate_country ) {
	// Check if cross-border is enabled
	if ( ! affwp_stripe_payouts_cross_border_enabled() ) {
		return false;
	}

	// Check if platform supports cross-border
	if ( ! affwp_stripe_payouts_platform_supports_cross_border() ) {
		return false;
	}

	// US affiliates should use standard Express accounts (faster)
	// International affiliates need recipient accounts
	return strtoupper( $affiliate_country ) !== 'US';
}

/**
 * Get the service agreement type for a given country
 *
 * @since 2.29.0
 * @param string $affiliate_country The affiliate's country code
 * @return string 'recipient' or 'full' (standard)
 */
function affwp_stripe_payouts_get_service_agreement( $affiliate_country ) {
	if ( affwp_stripe_payouts_should_create_recipient_account( $affiliate_country ) ) {
		return 'recipient';
	}

	return 'full'; // Standard Express account
}

/**
 * Clear all affiliate Stripe connections
 *
 * Used when platform account changes and admin chooses to clear connections.
 *
 * @since 2.29.0
 * @return int Number of connections cleared
 */
function affwp_stripe_payouts_clear_all_connections() {
	global $wpdb;

	// Count affected users first
	$affected_count = $wpdb->get_var(
		"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
		WHERE meta_key = 'affwp_stripe_payouts_account_id'
		AND meta_value != ''"
	);

	// Delete all Stripe connection metadata
	$wpdb->delete(
		$wpdb->usermeta,
		[ 'meta_key' => 'affwp_stripe_payouts_account_id' ]
	);

	$wpdb->delete(
		$wpdb->usermeta,
		[ 'meta_key' => 'affwp_stripe_payouts_pending_account_id' ]
	);

	$wpdb->delete(
		$wpdb->usermeta,
		[ 'meta_key' => 'affwp_stripe_payouts_account_status' ]
	);

	$wpdb->delete(
		$wpdb->usermeta,
		[ 'meta_key' => 'affwp_stripe_payouts_connected' ]
	);

	// Log the action
	affwp_stripe_payouts_log_info(
		'Cleared all Stripe connections due to platform account change',
		[ 'affected_affiliates' => $affected_count ]
	);

	return $affected_count;
}

/**
 * Check if a specific affiliate connection is valid with current platform
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return bool True if connection is valid, false otherwise
 */
function affwp_stripe_payouts_validate_affiliate_connection( $affiliate_id ) {
	$account_id = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );

	if ( empty( $account_id ) ) {
		return false;
	}

	// Initialize Stripe API
	affwp_stripe_payouts_init_api();

	try {
		// Try to retrieve the account
		$account = \Stripe\Account::retrieve( $account_id );

		// Check if account exists and has required capabilities
		if ( $account && ! empty( $account->id ) ) {
			return true;
		}
	} catch ( \Exception $e ) {
		affwp_stripe_payouts_log_error(
			'Failed to validate affiliate connection',
			[
				'affiliate_id' => $affiliate_id,
				'account_id'   => $account_id,
				'error'        => $e->getMessage(),
			]
		);
	}

	return false;
}

/**
 * Check if an affiliate's Stripe connection is still valid with the current platform
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return bool|string Returns 'valid', 'invalid', or 'unknown'
 */
function affwp_stripe_payouts_check_affiliate_platform_status( $affiliate_id ) {
	// Get the affiliate's stored platform ID.
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( ! $user_id ) {
		return 'unknown';
	}

	$affiliate_platform_id = get_user_meta( $user_id, 'affwp_stripe_connected_platform_id', true );

	// If no platform ID stored, we can't determine status
	if ( empty( $affiliate_platform_id ) ) {
		// Check if they have an account ID (legacy connection)
		$account_id = get_user_meta( $user_id, 'affwp_stripe_payouts_account_id', true );
		if ( ! empty( $account_id ) ) {
			// Legacy connection, we'll need to validate on first use
			return 'unknown';
		}
		return 'invalid'; // Not connected at all
	}

	// Get the current platform ID
	$current_platform_id = get_option( 'affwp_stripe_platform_account_id', '' );

	if ( empty( $current_platform_id ) ) {
		// Platform not configured
		return 'invalid';
	}

	// Check if they match
	if ( $affiliate_platform_id === $current_platform_id ) {
		return 'valid';
	}

	return 'invalid';
}

/**
 * Store the platform ID for an affiliate when they connect
 *
 * @since 2.29.0
 * @param int    $affiliate_id The affiliate ID
 * @param string $platform_id The platform account ID
 * @return bool
 */
function affwp_stripe_payouts_store_affiliate_platform( $affiliate_id, $platform_id = null ) {
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( ! $user_id ) {
		return false;
	}

	// If no platform ID provided, use the current one
	if ( null === $platform_id ) {
		$platform_id = get_option( 'affwp_stripe_platform_account_id', '' );
	}

	if ( empty( $platform_id ) ) {
		return false;
	}

	return update_user_meta( $user_id, 'affwp_stripe_connected_platform_id', $platform_id );
}

/**
 * Clear an affiliate's Stripe connection due to platform change
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return bool
 */
function affwp_stripe_payouts_clear_affiliate_connection( $affiliate_id ) {
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );
	if ( ! $user_id ) {
		return false;
	}

	// Clear all Stripe-related meta
	delete_user_meta( $user_id, 'affwp_stripe_payouts_account_id' );
	delete_user_meta( $user_id, 'affwp_stripe_payouts_connected' );
	delete_user_meta( $user_id, 'affwp_stripe_connected_platform_id' );

	// Clear any cached status
	$account_id = get_user_meta( $user_id, 'affwp_stripe_payouts_account_id', true );
	if ( ! empty( $account_id ) ) {
		delete_transient( 'affwp_stripe_account_status_' . $account_id );
	}

	// Log the disconnection
	affwp_stripe_payouts_log_info( sprintf( 'Affiliate #%d disconnected due to platform change', $affiliate_id ) );

	return true;
}

/**
 * Get Stripe account status with caching
 *
 * @since 2.29.0
 * @param string $account_id The Stripe account ID
 * @return array Account status information with keys: details_submitted, charges_enabled, payouts_enabled
 */
function affwp_stripe_payouts_get_account_status( $account_id ) {
	if ( empty( $account_id ) ) {
		return [ 'details_submitted' => false ];
	}

	// Check cache first (5 minute cache)
	$cache_key     = 'affwp_stripe_account_status_' . $account_id;
	$cached_status = get_transient( $cache_key );

	if ( false !== $cached_status ) {
		return $cached_status;
	}

	// Default status
	$status = [ 'details_submitted' => false ];

	try {
		// Initialize Stripe API
		if ( affwp_stripe_payouts_init_api() ) {
			$account = \Stripe\Account::retrieve( $account_id );
			$status  = [
				'details_submitted' => $account->details_submitted,
				'charges_enabled'   => $account->charges_enabled,
				'payouts_enabled'   => $account->payouts_enabled,
			];

			// Cache for 5 minutes
			set_transient( $cache_key, $status, 5 * MINUTE_IN_SECONDS );
		}
	} catch ( Exception $e ) {
		// Log error but don't break the UI
		affwp_stripe_payouts_log_error( 'Error retrieving account status for ' . $account_id . ': ' . $e->getMessage() );
	}

	return $status;
}

/**
 * Check and handle platform change for all affiliates
 *
 * @since 2.29.0
 * @param string $old_platform_id The old platform account ID
 * @param string $new_platform_id The new platform account ID
 * @return int Number of affiliates affected
 */
function affwp_stripe_payouts_handle_platform_change( $old_platform_id, $new_platform_id ) {
	global $wpdb;

	// Get all affiliates connected to the old platform
	$connected_users = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta}
			WHERE meta_key = 'affwp_stripe_connected_platform_id'
			AND meta_value = %s",
			$old_platform_id
		)
	);

	// Also get legacy connections (no platform ID stored)
	$legacy_users = $wpdb->get_col(
		"SELECT user_id FROM {$wpdb->usermeta}
		WHERE meta_key = 'affwp_stripe_payouts_account_id'
		AND meta_value != ''
		AND user_id NOT IN (
			SELECT user_id FROM {$wpdb->usermeta}
			WHERE meta_key = 'affwp_stripe_connected_platform_id'
		)"
	);

	$all_affected_users = array_unique( array_merge( $connected_users, $legacy_users ) );
	$affected_count     = 0;

	foreach ( $all_affected_users as $user_id ) {
		$affiliate_id = affwp_get_affiliate_id( $user_id );
		if ( $affiliate_id ) {
			affwp_stripe_payouts_clear_affiliate_connection( $affiliate_id );
			++$affected_count;
		}
	}

	// Log the platform change
	affwp_stripe_payouts_log_info(
		sprintf(
			'Platform changed from %s to %s. %d affiliates disconnected.',
			$old_platform_id,
			$new_platform_id,
			$affected_count
		)
	);

	return $affected_count;
}

/**
 * Check affiliate payment capabilities
 *
 * Checks if an affiliate can receive transfers and/or payouts.
 * This provides more granular information than just checking disabled_reason.
 *
 * @since 2.29.0
 * @param int $affiliate_id The affiliate ID
 * @return array|WP_Error Array with capability status or WP_Error on failure
 *                        Return array structure:
 *                        - can_receive_transfers: bool - Whether affiliate can receive transfers
 *                        - can_payout: bool - Whether affiliate can withdraw to bank
 *                        - disabled_reason: string|null - Reason for any restrictions
 *                        - charges_enabled: bool - Raw Stripe charges_enabled status
 *                        - payouts_enabled: bool - Raw Stripe payouts_enabled status
 *                        - warning_message: string|null - User-friendly warning message
 *                        - error_message: string|null - User-friendly error message
 */
function affwp_stripe_payouts_check_affiliate_payment_capability( $affiliate_id ) {
	// Check if affiliate is connected
	if ( ! affwp_stripe_payouts_is_affiliate_connected( $affiliate_id ) ) {
		return new WP_Error( 'not_connected', __( 'Affiliate is not connected to Stripe', 'affiliate-wp' ) );
	}

	// Get account ID
	$account_id = affwp_stripe_payouts_get_affiliate_account_id( $affiliate_id );
	if ( empty( $account_id ) ) {
		return new WP_Error( 'no_account', __( 'No Stripe account found for affiliate', 'affiliate-wp' ) );
	}

	// Initialize Stripe API
	affwp_stripe_payouts_init_api();

	// Use transient caching to avoid excessive API calls (30 second cache for near real-time updates)
	$cache_key = 'affwp_stripe_capability_' . $account_id;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	try {
		// Retrieve account from Stripe
		$account = \Stripe\Account::retrieve( $account_id );

		// Build capability status array
		$capability_status = [
			'can_receive_transfers' => false,
			'can_payout'            => false,
			'disabled_reason'       => null,
			'charges_enabled'       => false,
			'payouts_enabled'       => false,
			'payments_pausing_soon' => false,
			'payouts_pausing_soon'  => false,
			'future_requirements'   => [],
			'current_deadline'      => null,
			'warning_message'       => null,
			'error_message'         => null,
		];

		// Check raw Stripe capabilities
		$capability_status['charges_enabled'] = ! empty( $account->charges_enabled );
		$capability_status['payouts_enabled'] = ! empty( $account->payouts_enabled );

		// Check for future requirements that will affect capabilities
		if ( ! empty( $account->requirements->future_requirements ) ) {
			$capability_status['future_requirements'] = $account->requirements->future_requirements;

			// Check if future requirements will affect payments/charges
			// Stripe will pause charges/payments if identity or other critical requirements aren't met
			foreach ( $account->requirements->future_requirements as $req ) {
				// Requirements that typically affect payment capabilities
				if ( in_array(
					$req,
					[
						'individual.verification.document',
						'individual.verification.additional_document',
						'individual.id_number',
						'company.verification.document',
						'external_account',
						'tos_acceptance',
					],
					true
				) ) {
					$capability_status['payments_pausing_soon'] = true;
					break;
				}
			}
		}

		// Check current deadline for when requirements are due
		if ( ! empty( $account->requirements->current_deadline ) ) {
			$capability_status['current_deadline'] = $account->requirements->current_deadline;
		}

		// Check if currently_due requirements exist (more urgent than future)
		if ( ! empty( $account->requirements->currently_due ) ) {
			// If there are currently due requirements, payments might pause very soon
			$capability_status['payments_pausing_soon'] = true;
		}

		// Check for disabled reason
		if ( ! empty( $account->requirements->disabled_reason ) ) {
			$capability_status['disabled_reason'] = $account->requirements->disabled_reason;
		}

		// Determine what the affiliate can do
		// Can receive transfers if charges are enabled (or no explicit block on transfers capability)
		if ( $capability_status['charges_enabled'] ) {
			$capability_status['can_receive_transfers'] = true;
		} elseif ( empty( $capability_status['disabled_reason'] ) ||
					! in_array( $capability_status['disabled_reason'], [ 'requirements.past_due', 'rejected.fraud', 'rejected.terms_of_service' ], true ) ) {
			// If there's no disabled reason or it's not a severe one, they might still receive transfers
			// Check the specific transfer capability if available
			if ( isset( $account->capabilities->transfers ) && $account->capabilities->transfers === 'active' ) {
				$capability_status['can_receive_transfers'] = true;
			}
		}

		// Can payout if payouts are enabled
		$capability_status['can_payout'] = $capability_status['payouts_enabled'];

		// Set user-friendly messages based on status
		if ( ! $capability_status['can_receive_transfers'] ) {
			// Cannot receive transfers at all
			$capability_status['error_message'] = sprintf(
				__( 'This affiliate cannot receive payments. Their Stripe account is restricted%s. The affiliate needs to complete their Stripe account setup.', 'affiliate-wp' ),
				$capability_status['disabled_reason'] ? ' (' . $capability_status['disabled_reason'] . ')' : ''
			);
		} elseif ( ! $capability_status['can_payout'] ) {
			// Can receive transfers but cannot payout to bank
			$capability_status['warning_message'] = __( 'This payment will be sent to the affiliate\'s Stripe account, but they cannot withdraw funds to their bank until they complete verification.', 'affiliate-wp' );
		}

		// Add the simplified can_pay flag and reason for easier checking
		$capability_status['can_pay'] = $capability_status['can_receive_transfers'];
		$capability_status['reason']  = $capability_status['error_message'] ?:
			( $capability_status['warning_message'] ?:
			( $capability_status['disabled_reason'] ?: '' ) );

		// Cache the result for 30 seconds for near real-time updates
		set_transient( $cache_key, $capability_status, 30 );

		return $capability_status;

	} catch ( \Exception $e ) {
		affwp_stripe_payouts_log_error( 'Error checking affiliate capabilities: ' . $e->getMessage() );
		return new WP_Error( 'stripe_error', $e->getMessage() );
	}
}
