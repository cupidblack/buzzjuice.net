<?php
/**
 * BZJ GiveWP Email OTP Gate
 *
 * File:
 *   wp-content/mu-plugins/bzj-give-email-otp.php
 *
 * Purpose:
 *   - Require a 5-character email OTP before an anonymous GiveWP donor can
 *     continue through a donation form or create a GiveWP donor account.
 *   - Bind OTP state to a browser transaction, exact normalized email, and
 *     GiveWP form ID.
 *   - Provide a resilient frontend gate for GiveWP Next Generation/React
 *     forms, including same-origin iframes and React DOM replacement.
 *   - Enforce the gate server-side so enabling a disabled button in DevTools
 *     or posting directly cannot bypass verification.
 *
 * This is deliberately separate from bzj-registration-kernel.php.
 * The existing BuddyBoss/AffiliateWP challenge remains untouched.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
 * 0. CONFIGURATION
 * ========================================================================== */

if ( ! defined( 'BZJ_GIVE_OTP_VERSION' ) ) {
	define( 'BZJ_GIVE_OTP_VERSION', '1.0.0' );
}

if ( ! defined( 'BZJ_GIVE_OTP_TTL' ) ) {
	define( 'BZJ_GIVE_OTP_TTL', 15 * MINUTE_IN_SECONDS );
}

if ( ! defined( 'BZJ_GIVE_OTP_LENGTH' ) ) {
	define( 'BZJ_GIVE_OTP_LENGTH', 5 );
}

if ( ! defined( 'BZJ_GIVE_OTP_MAX_ATTEMPTS' ) ) {
	define( 'BZJ_GIVE_OTP_MAX_ATTEMPTS', 5 );
}

if ( ! defined( 'BZJ_GIVE_OTP_RESEND_COOLDOWN' ) ) {
	define( 'BZJ_GIVE_OTP_RESEND_COOLDOWN', 60 );
}

if ( ! defined( 'BZJ_GIVE_OTP_STATE_TTL' ) ) {
	define( 'BZJ_GIVE_OTP_STATE_TTL', 30 * MINUTE_IN_SECONDS );
}

if ( ! defined( 'BZJ_GIVE_OTP_COOKIE' ) ) {
	define( 'BZJ_GIVE_OTP_COOKIE', 'bzj_give_vtx' );
}

if ( ! defined( 'BZJ_GIVE_OTP_NONCE_ACTION' ) ) {
	define( 'BZJ_GIVE_OTP_NONCE_ACTION', 'bzj_give_email_otp' );
}

if ( ! defined( 'BZJ_GIVE_OTP_LOG_DIR' ) ) {
	define(
		'BZJ_GIVE_OTP_LOG_DIR',
		ABSPATH . '/data/logs/bzj-registration-kernel/'
	);
}

/*
 * Set this to a comma-separated list of GiveWP form IDs if only selected
 * forms should require OTP. An empty value means all GiveWP donation forms.
 */
if ( ! defined( 'BZJ_GIVE_OTP_FORM_IDS' ) ) {
	define( 'BZJ_GIVE_OTP_FORM_IDS', '' );
}

/* ==========================================================================
 * 1. BASIC UTILITIES
 * ========================================================================== */

function bzj_give_otp_normalize_email( $email ) {
	$email = sanitize_email( (string) $email );

	if ( ! $email || ! is_email( $email ) ) {
		return '';
	}

	return strtolower( trim( $email ) );
}

function bzj_give_otp_email_hash( $email ) {
	$email = bzj_give_otp_normalize_email( $email );

	return $email ? hash( 'sha256', $email ) : '';
}

function bzj_give_otp_hash( $otp ) {
	return hash_hmac(
		'sha256',
		strtoupper( trim( (string) $otp ) ),
		wp_salt( 'auth' )
	);
}

function bzj_give_otp_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '';
}

function bzj_give_otp_log( $event, $data = array() ) {
	if ( ! file_exists( BZJ_GIVE_OTP_LOG_DIR ) ) {
		wp_mkdir_p( BZJ_GIVE_OTP_LOG_DIR );
	}

	$payload = array(
		'ts'   => gmdate( 'c' ),
		'ip'   => bzj_give_otp_client_ip(),
		'ua'   => isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '',
		'type' => 'give_' . sanitize_key( $event ),
		'data' => is_array( $data ) ? $data : array(),
	);

	@file_put_contents(
		BZJ_GIVE_OTP_LOG_DIR . 'bzj-give-email-otp.log',
		wp_json_encode(
			$payload,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . "\n---\n",
		FILE_APPEND | LOCK_EX
	);
}

function bzj_give_otp_json_error( $message, $status = 400, $extra = array() ) {
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();

	wp_send_json_error(
		array_merge(
			array( 'message' => $message ),
			$extra
		),
		$status
	);
}

function bzj_give_otp_json_success( $data = array() ) {
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();

	wp_send_json_success( $data );
}

/* ==========================================================================
 * 2. FORM SCOPE
 * ========================================================================== */

function bzj_give_otp_allowed_form( $form_id ) {
	$form_id = absint( $form_id );

	if ( ! $form_id ) {
		return false;
	}

	$configured = BZJ_GIVE_OTP_FORM_IDS;

	if ( '' === $configured || array() === $configured ) {
		return true;
	}

	if ( is_string( $configured ) ) {
		$configured = array_filter(
			array_map(
				'absint',
				preg_split( '/\s*,\s*/', $configured )
			)
		);
	}

	return in_array( $form_id, $configured, true );
}

function bzj_give_otp_request_form_id() {
	$candidates = array(
		$_POST['give-form-id'] ?? 0,
		$_POST['form_id'] ?? 0,
		$_POST['formId'] ?? 0,
		$_POST['form-id'] ?? 0,
	);

	foreach ( $candidates as $candidate ) {
		$form_id = absint( wp_unslash( $candidate ) );

		if ( $form_id > 0 ) {
			return $form_id;
		}
	}

	return 0;
}

function bzj_give_otp_request_email() {
	$candidates = array(
		$_POST['give_email'] ?? '',
		$_POST['give_user_email'] ?? '',
		$_POST['email'] ?? '',
		$_POST['donor_email'] ?? '',
	);

	foreach ( $candidates as $candidate ) {
		$email = bzj_give_otp_normalize_email(
			wp_unslash( $candidate )
		);

		if ( $email ) {
			return $email;
		}
	}

	return '';
}

function bzj_give_otp_is_give_request() {
	if ( ! empty( $_POST['give-form-id'] ) ) {
		return true;
	}

	if ( ! empty( $_POST['give_action'] ) ) {
		return true;
	}

	if ( ! empty( $_POST['give-gateway'] ) ) {
		return true;
	}

	if ( ! empty( $_POST['give_email'] ) ) {
		return true;
	}

	$uri = isset( $_SERVER['REQUEST_URI'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
		: '';

	return false !== stripos( $uri, 'give' )
		|| false !== stripos( $uri, 'donate' );
}

/* ==========================================================================
 * 3. BROWSER TRANSACTION STATE
 * ========================================================================== */

function bzj_give_otp_new_transaction_id() {
	try {
		return bin2hex( random_bytes( 32 ) );
	} catch ( Throwable $e ) {
		return hash(
			'sha256',
			uniqid( (string) wp_rand(), true ) . microtime( true )
		);
	}
}

function bzj_give_otp_valid_transaction_id( $value ) {
	return is_string( $value )
		&& 1 === preg_match( '/^[a-f0-9]{64}$/', $value );
}

function bzj_give_otp_set_cookie( $transaction_id ) {
	if ( ! bzj_give_otp_valid_transaction_id( $transaction_id ) ) {
		return false;
	}

	$secure = is_ssl();
	$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

	if ( PHP_VERSION_ID >= 70300 ) {
		return setcookie(
			BZJ_GIVE_OTP_COOKIE,
			$transaction_id,
			array(
				'expires'  => time() + BZJ_GIVE_OTP_STATE_TTL,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	return setcookie(
		BZJ_GIVE_OTP_COOKIE,
		$transaction_id,
		time() + BZJ_GIVE_OTP_STATE_TTL,
		$path,
		$domain,
		$secure,
		true
	);
}

function bzj_give_otp_transaction_id() {
	if ( empty( $_COOKIE[ BZJ_GIVE_OTP_COOKIE ] ) ) {
		return '';
	}

	$value = wp_unslash( $_COOKIE[ BZJ_GIVE_OTP_COOKIE ] );

	return bzj_give_otp_valid_transaction_id( $value ) ? $value : '';
}

function bzj_give_otp_ensure_transaction() {
	$transaction_id = bzj_give_otp_transaction_id();

	if ( $transaction_id ) {
		return $transaction_id;
	}

	$transaction_id = bzj_give_otp_new_transaction_id();

	bzj_give_otp_set_cookie( $transaction_id );

	/* Make it available during the current request as well. */
	$_COOKIE[ BZJ_GIVE_OTP_COOKIE ] = $transaction_id;

	return $transaction_id;
}

function bzj_give_otp_state_key( $transaction_id ) {
	return 'bzj_give_v_' . hash( 'sha256', $transaction_id );
}

function bzj_give_otp_get_state() {
	$transaction_id = bzj_give_otp_transaction_id();

	if ( ! $transaction_id ) {
		return false;
	}

	$state = get_transient(
		bzj_give_otp_state_key( $transaction_id )
	);

	return is_array( $state ) ? $state : false;
}

function bzj_give_otp_set_state( $transaction_id, $state ) {
	if ( ! bzj_give_otp_valid_transaction_id( $transaction_id ) ) {
		return false;
	}

	return set_transient(
		bzj_give_otp_state_key( $transaction_id ),
		$state,
		BZJ_GIVE_OTP_STATE_TTL
	);
}

function bzj_give_otp_delete_state() {
	$transaction_id = bzj_give_otp_transaction_id();

	if ( $transaction_id ) {
		delete_transient(
			bzj_give_otp_state_key( $transaction_id )
		);
	}
}

function bzj_give_otp_state_verified_for( $state, $email, $form_id ) {
	if ( ! is_array( $state ) || empty( $state['verified'] ) ) {
		return false;
	}

	if ( empty( $state['email_hash'] ) ) {
		return false;
	}

	if ( ! empty( $state['verified_at'] ) ) {
		$age = time() - (int) $state['verified_at'];

		if ( $age < 0 || $age > BZJ_GIVE_OTP_STATE_TTL ) {
			return false;
		}
	}

	$email = bzj_give_otp_normalize_email( $email );

	if ( ! $email ) {
		return false;
	}

	$email_hash = bzj_give_otp_email_hash( $email );

	if ( ! hash_equals( $state['email_hash'], $email_hash ) ) {
		return false;
	}

	if (
		$form_id > 0
		&& (
			empty( $state['form_id'] )
			|| (int) $state['form_id'] !== (int) $form_id
		)
	) {
		return false;
	}

	return true;
}

/* ==========================================================================
 * 4. RATE LIMITING
 * ========================================================================== */

function bzj_give_otp_ip_key() {
	return 'bzj_give_ip_' . substr(
		hash(
			'sha256',
			bzj_give_otp_client_ip() . '|' . wp_salt( 'auth' )
		),
		0,
		32
	);
}

function bzj_give_otp_email_key( $email ) {
	return 'bzj_give_em_' . substr(
		bzj_give_otp_email_hash( $email ),
		0,
		32
	);
}

function bzj_give_otp_send_counts_ok( $email ) {
	$ip_count    = (int) get_transient( bzj_give_otp_ip_key() );
	$email_count = (int) get_transient( bzj_give_otp_email_key( $email ) );

	return $ip_count < 10 && $email_count < 5;
}

function bzj_give_otp_record_send( $email ) {
	$ip_key    = bzj_give_otp_ip_key();
	$email_key = bzj_give_otp_email_key( $email );

	set_transient(
		$ip_key,
		(int) get_transient( $ip_key ) + 1,
		HOUR_IN_SECONDS
	);

	set_transient(
		$email_key,
		(int) get_transient( $email_key ) + 1,
		HOUR_IN_SECONDS
	);
}

function bzj_give_otp_cooldown_remaining( $state ) {
	if ( ! is_array( $state ) || empty( $state['last_sent_at'] ) ) {
		return 0;
	}

	return max(
		0,
		BZJ_GIVE_OTP_RESEND_COOLDOWN
		- ( time() - (int) $state['last_sent_at'] )
	);
}

/* ==========================================================================
 * 5. OTP GENERATION
 * ========================================================================== */

function bzj_give_otp_generate() {
	/*
	 * Five uppercase hexadecimal characters preserve the existing 5-character
	 * challenge convention while using cryptographically secure randomness.
	 */
	try {
		return strtoupper(
			substr( bin2hex( random_bytes( 3 ) ), 0, BZJ_GIVE_OTP_LENGTH )
		);
	} catch ( Throwable $e ) {
		return strtoupper(
			substr(
				hash(
					'sha256',
					uniqid( (string) wp_rand(), true ) . microtime( true )
				),
				0,
				BZJ_GIVE_OTP_LENGTH
			)
		);
	}
}

/* ==========================================================================
 * 6. AJAX: REQUEST / RESEND OTP
 * ========================================================================== */

function bzj_give_otp_request_handler() {
	check_ajax_referer(
		BZJ_GIVE_OTP_NONCE_ACTION,
		'nonce'
	);

	$email = bzj_give_otp_normalize_email(
		wp_unslash( $_POST['email'] ?? '' )
	);

	$form_id = absint(
		wp_unslash( $_POST['form_id'] ?? 0 )
	);

	if ( ! $email ) {
		bzj_give_otp_log(
			'invalid_email',
			array( 'form_id' => $form_id )
		);

		bzj_give_otp_json_error(
			'Please enter a valid email address.',
			400
		);
	}

	if ( $form_id && ! bzj_give_otp_allowed_form( $form_id ) ) {
		bzj_give_otp_json_error(
			'Email verification is not available for this donation form.',
			403
		);
	}

	$transaction_id = bzj_give_otp_ensure_transaction();
	$state          = bzj_give_otp_get_state();

	$cooldown = bzj_give_otp_cooldown_remaining( $state );

	if ( $cooldown > 0 ) {
		bzj_give_otp_json_error(
			sprintf(
				'Please wait %d seconds before requesting another code.',
				$cooldown
			),
			429,
			array( 'cooldown' => $cooldown )
		);
	}

	if ( ! bzj_give_otp_send_counts_ok( $email ) ) {
		bzj_give_otp_log(
			'otp_rate_limited',
			array(
				'form_id' => $form_id,
			)
		);

		bzj_give_otp_json_error(
			'Too many verification requests. Please try again later.',
			429
		);
	}

	$otp = bzj_give_otp_generate();

	$new_state = array(
		'version'        => 1,
		'transaction'    => $transaction_id,
		'form_id'        => $form_id,
		'email_hash'     => bzj_give_otp_email_hash( $email ),
		'otp_hash'       => bzj_give_otp_hash( $otp ),
		'otp_expires_at' => time() + BZJ_GIVE_OTP_TTL,
		'last_sent_at'   => time(),
		'attempts'       => 0,
		'verified'       => false,
		'verified_at'    => 0,
		'created_at'     => time(),
	);

	if ( ! bzj_give_otp_set_state( $transaction_id, $new_state ) ) {
		bzj_give_otp_log(
			'state_save_failed',
			array( 'form_id' => $form_id )
		);

		bzj_give_otp_json_error(
			'Unable to start email verification. Please try again.',
			500
		);
	}

	$subject = 'BuzzJuice Donation email Verification Code';

	$message = sprintf(
		"Your BuzzJuice Donation email verification code is:\n\n%s\n\n"
		. "This 5-character code expires in %d minutes.\n\n"
		. "If you did not request this code, you can ignore this email.",
		$otp,
		(int) ( BZJ_GIVE_OTP_TTL / MINUTE_IN_SECONDS )
	);

	$sent = wp_mail(
		$email,
		$subject,
		$message,
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);

	if ( ! $sent ) {
		bzj_give_otp_delete_state();

		bzj_give_otp_log(
			'otp_email_failed',
			array( 'form_id' => $form_id )
		);

		bzj_give_otp_json_error(
			'We could not send the verification email. Please try again.',
			500
		);
	}

	bzj_give_otp_record_send( $email );

	bzj_give_otp_log(
		'otp_sent',
		array(
			'form_id' => $form_id,
		)
	);

	bzj_give_otp_json_success(
		array(
			'message'  => 'Verification code sent.',
			'expires'  => BZJ_GIVE_OTP_TTL,
			'cooldown' => BZJ_GIVE_OTP_RESEND_COOLDOWN,
		)
	);
}

add_action(
	'wp_ajax_nopriv_bzj_give_request_otp',
	'bzj_give_otp_request_handler'
);

/* ==========================================================================
 * 7. AJAX: VERIFY OTP
 * ========================================================================== */

function bzj_give_otp_verify_handler() {
	check_ajax_referer(
		BZJ_GIVE_OTP_NONCE_ACTION,
		'nonce'
	);

	$code = strtoupper(
		sanitize_text_field(
			wp_unslash( $_POST['code'] ?? '' )
		)
	);

	$code = preg_replace( '/[^A-Z0-9]/', '', $code );

	$request_form_id = absint(
		wp_unslash( $_POST['form_id'] ?? 0 )
	);

	if ( strlen( $code ) !== BZJ_GIVE_OTP_LENGTH ) {
		bzj_give_otp_json_error(
			'Enter the complete verification code.',
			400
		);
	}

	$transaction_id = bzj_give_otp_transaction_id();

	if ( ! $transaction_id ) {
		bzj_give_otp_json_error(
			'Your verification session expired. Request a new code.',
			403,
			array( 'reset' => true )
		);
	}

	$state = bzj_give_otp_get_state();

	if ( ! $state ) {
		bzj_give_otp_log( 'otp_missing_state' );

		bzj_give_otp_json_error(
			'Your verification session expired. Request a new code.',
			403,
			array( 'reset' => true )
		);
	}

	/*
	 * Keep the OTP transaction bound to the same GiveWP form for which it
	 * was requested. This prevents one form's verified state being reused
	 * for another form.
	 */
	if (
		$request_form_id > 0
		&& ! empty( $state['form_id'] )
		&& (int) $state['form_id'] !== $request_form_id
	) {
		bzj_give_otp_delete_state();
		bzj_give_otp_json_error(
			'Your verification session belongs to a different donation form. Request a new code.',
			403,
			array( 'reset' => true )
		);
	}

	if (
		empty( $state['otp_hash'] )
		|| empty( $state['otp_expires_at'] )
	) {
		bzj_give_otp_json_error(
			'Please request a new verification code.',
			403,
			array( 'reset' => true )
		);
	}

	if ( time() > (int) $state['otp_expires_at'] ) {
		bzj_give_otp_delete_state();
		bzj_give_otp_log( 'otp_expired' );

		bzj_give_otp_json_error(
			'This verification code has expired. Request a new code.',
			403,
			array( 'reset' => true )
		);
	}

	$attempts = (int) ( $state['attempts'] ?? 0 );

	if ( $attempts >= BZJ_GIVE_OTP_MAX_ATTEMPTS ) {
		bzj_give_otp_delete_state();
		bzj_give_otp_log( 'otp_attempt_limit' );

		bzj_give_otp_json_error(
			'Too many incorrect attempts. Request a new code.',
			403,
			array( 'reset' => true )
		);
	}

	$actual_hash = bzj_give_otp_hash( $code );

	if (
		empty( $state['otp_hash'] )
		|| ! hash_equals( $state['otp_hash'], $actual_hash )
	) {
		$state['attempts'] = $attempts + 1;

		bzj_give_otp_set_state(
			$transaction_id,
			$state
		);

		$remaining = max(
			0,
			BZJ_GIVE_OTP_MAX_ATTEMPTS - $state['attempts']
		);

		bzj_give_otp_log(
			'otp_verification_failed',
			array(
				'attempts'  => $state['attempts'],
				'remaining' => $remaining,
			)
		);

		bzj_give_otp_json_error(
			'Incorrect verification code.',
			403,
			array(
				'attempts_remaining' => $remaining,
			)
		);
	}

	$state['verified']       = true;
	$state['verified_at']    = time();
	$state['otp_hash']       = '';
	$state['otp_expires_at'] = 0;
	$state['attempts']       = 0;

	bzj_give_otp_set_state(
		$transaction_id,
		$state
	);

	bzj_give_otp_log(
		'otp_verified',
		array(
			'form_id' => $state['form_id'] ?? 0,
		)
	);

	bzj_give_otp_json_success(
		array(
			'message' => 'Email verified successfully.',
			'form_id' => (int) ( $state['form_id'] ?? 0 ),
		)
	);
}

add_action(
	'wp_ajax_nopriv_bzj_give_verify_otp',
	'bzj_give_otp_verify_handler'
);

/* ==========================================================================
 * 8. AUTHORITATIVE SERVER-SIDE GIVEWP DONATION GATE
 * ========================================================================== */

function bzj_give_otp_validate_donation() {
	if ( ! bzj_give_otp_is_give_request() ) {
		return;
	}

	/*
	 * The requirement is for anonymous GiveWP donors. Logged-in WordPress
	 * users already have an established site identity.
	 */
	if ( is_user_logged_in() ) {
		return;
	}

	$form_id = bzj_give_otp_request_form_id();

	if ( $form_id && ! bzj_give_otp_allowed_form( $form_id ) ) {
		return;
	}

	$email = bzj_give_otp_request_email();

	if ( ! $email ) {
		if ( function_exists( 'give_set_error' ) ) {
			give_set_error(
				'bzj_give_email_verification',
				'Please enter a valid email address.'
			);
		}

		bzj_give_otp_log(
			'donation_blocked_invalid_email',
			array( 'form_id' => $form_id )
		);

		return;
	}

	$state = bzj_give_otp_get_state();

	if (
		! $state
		|| ! bzj_give_otp_state_verified_for(
			$state,
			$email,
			$form_id
		)
	) {
		if ( function_exists( 'give_set_error' ) ) {
			give_set_error(
				'bzj_give_email_verification',
				'Please verify your email address before continuing with your donation.'
			);
		}

		bzj_give_otp_log(
			'donation_blocked_unverified',
			array(
				'form_id' => $form_id,
			)
		);

		return;
	}

	bzj_give_otp_log(
		'donation_authorized',
		array(
			'form_id' => $form_id,
		)
	);
}

/*
 * Give's supported checkout validation extension point.
 *
 * The callback accepts the current hook argument but reads the request
 * directly. This avoids depending on Give's deprecated second $_POST
 * argument.
 */
add_action(
	'give_checkout_error_checks',
	'bzj_give_otp_validate_donation',
	5,
	1
);

/* ==========================================================================
 * 9. GIVEWP ACCOUNT CREATION GATE
 * ========================================================================== */

function bzj_give_otp_validate_registration() {
	if ( is_user_logged_in() ) {
		return;
	}

	$email = bzj_give_otp_normalize_email(
		wp_unslash( $_POST['give_user_email'] ?? '' )
	);

	if ( ! $email ) {
		$email = bzj_give_otp_request_email();
	}

	$form_id = bzj_give_otp_request_form_id();
	$state   = bzj_give_otp_get_state();

	if (
		! $email
		|| ! bzj_give_otp_state_verified_for(
			$state,
			$email,
			$form_id
		)
	) {
		if ( function_exists( 'give_set_error' ) ) {
			give_set_error(
				'bzj_give_email_verification',
				'Please verify your email address before creating your account.'
			);
		}

		bzj_give_otp_log(
			'give_registration_blocked',
			array(
				'form_id' => $form_id,
			)
		);

		return;
	}

	bzj_give_otp_log(
		'give_registration_authorized',
		array(
			'form_id' => $form_id,
		)
	);
}

add_action(
	'give_pre_process_register_form',
	'bzj_give_otp_validate_registration',
	1
);

/* ==========================================================================
 * 10. PUBLIC BRIDGE FOR bzj-registration-kernel.php
 * ========================================================================== */

/**
 * Used by bzj-registration-kernel.php.
 *
 * IMPORTANT:
 * This does not modify the kernel's normal BuddyBoss/AffiliateWP state.
 * It only authorizes a GiveWP registration when the separate Give OTP
 * transaction state has been verified for the exact email/form combination.
 */
function bzj_give_email_otp_authorizes_registration() {
	if ( is_user_logged_in() ) {
		return true;
	}

	if ( ! bzj_give_otp_is_give_request() ) {
		return false;
	}

	$form_id = bzj_give_otp_request_form_id();

	if ( $form_id && ! bzj_give_otp_allowed_form( $form_id ) ) {
		return false;
	}

	$email = bzj_give_otp_request_email();

	if ( ! $email ) {
		return false;
	}

	return bzj_give_otp_state_verified_for(
		bzj_give_otp_get_state(),
		$email,
		$form_id
	);
}

/* ==========================================================================
 * 11. POST-INSERT ANOMALY LOGGER
 * ========================================================================== */

/*
 * Normally the kernel's pre-insert firewall prevents an unverified user from
 * being created. This hook deliberately does NOT delete a user because the
 * existing kernel already owns the post-insert deletion policy. It records
 * any unexpected Give insertion without a valid OTP for forensic review.
 */
function bzj_give_otp_log_inserted_user( $user_id ) {
	if ( ! $user_id || is_user_logged_in() ) {
		return;
	}

	if ( ! bzj_give_otp_is_give_request() ) {
		return;
	}

	$form_id = bzj_give_otp_request_form_id();
	$user    = get_userdata( $user_id );

	if ( ! $user ) {
		return;
	}

	$email = bzj_give_otp_normalize_email( $user->user_email );
	$state = bzj_give_otp_get_state();

	if (
		bzj_give_otp_state_verified_for(
			$state,
			$email,
			$form_id
		)
	) {
		bzj_give_otp_log(
			'inserted_user_authorized',
			array(
				'user_id' => (int) $user_id,
				'form_id' => $form_id,
			)
		);

		return;
	}

	bzj_give_otp_log(
		'inserted_user_without_verified_otp',
		array(
			'user_id' => (int) $user_id,
			'form_id' => $form_id,
		)
	);
}

add_action(
	'give_insert_user',
	'bzj_give_otp_log_inserted_user',
	5,
	1
);

/* ==========================================================================
 * 12. FRONTEND CONFIGURATION
 * ========================================================================== */

add_action(
	'wp_head',
	function() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$config = array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( BZJ_GIVE_OTP_NONCE_ACTION ),
			'otpLength' => BZJ_GIVE_OTP_LENGTH,
			'cooldown'  => BZJ_GIVE_OTP_RESEND_COOLDOWN,
			'otpTTL'    => BZJ_GIVE_OTP_TTL,
			'selectors' => array(
				'roots' => array(
					'#give-next-gen',
					'[data-givewp-form]',
					'.givewp-donation-form',
				),
				'buttons' => array(
					'#give-next-gen > button',
					'button.givewp-donation-form__steps-button-next',
					'button[type="submit"]',
				),
				'emails' => array(
					'input[type="email"]',
					'input[name="give_email"]',
					'input[name="email"]',
					'input[id*="email" i]',
				),
			),
		);

		echo '<script>window.BZJGiveOTP='
			. wp_json_encode(
				$config,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
			. ';</script>';
	},
	1
);

/* ==========================================================================
 * 13. FRONTEND CSS + JAVASCRIPT
 * ========================================================================== */

add_action(
	'wp_footer',
	function() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		?>
<style id="bzj-give-email-otp-style">
.bzj-give-email-verification {
	margin: 14px 0;
	padding: 12px;
	border: 1px solid #d9dce3;
	border-radius: 8px;
	background: #fafbfd;
	width: 100%;
	box-sizing: border-box;
}
.bzj-give-email-verification__row {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-wrap: wrap;
}
.bzj-give-email-verification__code {
	width: 110px !important;
	min-width: 90px;
	text-transform: uppercase;
	font-family: monospace;
	font-weight: 700;
	letter-spacing: .12em;
	text-align: center;
}
.bzj-give-email-verification__resend,
.bzj-give-email-verification__reset {
	white-space: nowrap;
}
.bzj-give-email-verification__status {
	display: block;
	margin-top: 7px;
	font-size: .9em;
}
.bzj-give-email-verification__status.is-success {
	color: #237a35;
}
.bzj-give-email-verification__status.is-error {
	color: #b42318;
}
.bzj-give-email-verification__status.is-neutral {
	color: #737373;
}
.bzj-give-email-verification__hint {
	display: block;
	margin-bottom: 8px;
	font-size: .9em;
	color: #555;
}
.bzj-give-email-verification__email--locked {
	background: #eeeeee !important;
	color: #666666 !important;
	cursor: not-allowed;
}
</style>

<script id="bzj-give-email-otp-script">
(function () {
	'use strict';

	const CONFIG = window.BZJGiveOTP || null;

	if (!CONFIG) {
		return;
	}

	const ROOT_SELECTOR = (CONFIG.selectors.roots || []).join(',');
	const BUTTON_SELECTORS = CONFIG.selectors.buttons || [];
	const EMAIL_SELECTORS = CONFIG.selectors.emails || [];
	const state = new WeakMap();

	function getDocuments() {
		const docs = [document];

		document.querySelectorAll('iframe').forEach(function (frame) {
			try {
				if (frame.contentDocument) {
					docs.push(frame.contentDocument);
				}
			} catch (e) {
				/* Cross-origin frames cannot be inspected. */
			}
		});

		return docs;
	}

	function queryAll(root, selectors) {
		const found = [];

		selectors.forEach(function (selector) {
			try {
				root.querySelectorAll(selector).forEach(function (node) {
					if (!found.includes(node)) {
						found.push(node);
					}
				});
			} catch (e) {}
		});

		return found;
	}

	function firstVisible(root, selectors) {
		const nodes = queryAll(root, selectors);

		return nodes.find(function (node) {
			return node.offsetParent !== null;
		}) || nodes[0] || null;
	}

	function findEmail(scope) {
		return firstVisible(scope, EMAIL_SELECTORS);
	}

	function findButton(scope) {
		return firstVisible(scope, BUTTON_SELECTORS);
	}

	function findScope(button) {
		if (!button) {
			return document;
		}

		let node = button;

		while (node && node.parentElement) {
			try {
				if (node.matches(ROOT_SELECTOR)) {
					return node;
				}
			} catch (e) {}

			node = node.parentElement;
		}

		return button.ownerDocument;
	}

	function findFormId(scope, email, button) {
		const candidates = [];

		[scope, email, button].forEach(function (node) {
			if (!node) {
				return;
			}

			if (node.getAttribute) {
				[
					'data-form-id',
					'data-give-form-id',
					'data-formid',
					'data-form'
				].forEach(function (attribute) {
					const value = node.getAttribute(attribute);

					if (/^\d+$/.test(String(value || ''))) {
						candidates.push(parseInt(value, 10));
					}
				});
			}
		});

		const hidden = firstVisible(
			scope,
			[
				'input[name="give-form-id"]',
				'input[name="form_id"]',
				'input[name="formId"]',
				'input[id*="give-form-id" i]'
			]
		);

		if (hidden && /^\d+$/.test(String(hidden.value || ''))) {
			candidates.push(parseInt(hidden.value, 10));
		}

		[scope && scope.id, email && email.form && email.form.id,
			button && button.form && button.form.id].forEach(function (value) {
			const match = String(value || '').match(/(?:give-form|form)[-_]?(\d+)/i);

			if (match) {
				candidates.push(parseInt(match[1], 10));
			}
		});

		return candidates.find(function (id) {
			return id > 0;
		}) || 0;
	}

	function setDisabled(button, disabled) {
		if (!button) {
			return;
		}

		button.disabled = !!disabled;
		button.setAttribute(
			'aria-disabled',
			disabled ? 'true' : 'false'
		);

		if (disabled) {
			button.classList.add('bzj-give-otp-disabled');
			button.style.opacity = '0.65';
			button.style.cursor = 'not-allowed';
		} else {
			button.classList.remove('bzj-give-otp-disabled');
			button.style.opacity = '';
			button.style.cursor = '';
		}
	}

	function setStatus(instance, message, type) {
		if (!instance.status) {
			return;
		}

		instance.status.textContent = message || '';
		instance.status.className =
			'bzj-give-email-verification__status is-' +
			(type || 'neutral');
	}

	function normalizeEmail(value) {
		return String(value || '').trim().toLowerCase();
	}

	function validEmail(value) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
			normalizeEmail(value)
		);
	}

	function setEmailValue(input, value) {
		if (!input) {
			return;
		}

		const prototype = Object.getPrototypeOf(input);
		const descriptor = prototype &&
			Object.getOwnPropertyDescriptor(
				prototype,
				'value'
			);

		if (descriptor && descriptor.set) {
			descriptor.set.call(input, value);
		} else {
			input.value = value;
		}

		try {
			input.dispatchEvent(
				new Event('input', { bubbles: true })
			);
			input.dispatchEvent(
				new Event('change', { bubbles: true })
			);
		} catch (e) {}
	}

	function post(action, data) {
		const body = new URLSearchParams();

		body.set('action', action);
		body.set('nonce', CONFIG.nonce);

		Object.keys(data || {}).forEach(function (key) {
			body.set(key, data[key]);
		});

		return fetch(CONFIG.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type':
					'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		}).then(function (response) {
			return response.json().catch(function () {
				return {
					success: false,
					data: {
						message: 'Invalid server response.'
					}
				};
			});
		});
	}

	function reset(instance, message) {
		instance.verified = false;
		instance.verifiedEmail = '';
		instance.requestedEmail = '';
		instance.cooldownUntil = 0;

		if (instance.email) {
			instance.email.readOnly = false;
			instance.email.removeAttribute('aria-readonly');
			instance.email.classList.remove(
				'bzj-give-email-verification__email--locked'
			);
			instance.email.style.background = '';
			instance.email.style.color = '';
		}

		if (instance.code) {
			instance.code.value = '';
			instance.code.disabled = true;
		}

		if (instance.resend) {
			instance.resend.disabled = false;
			instance.resend.textContent = 'Send OTP';
		}

		setDisabled(instance.button, true);

		if (instance.cooldownTimer) {
			clearInterval(instance.cooldownTimer);
			instance.cooldownTimer = null;
		}

		setStatus(
			instance,
			message ||
				'Enter your email and request a verification code.',
			'neutral'
		);
	}

	function lockVerifiedEmail(instance) {
		setEmailValue(
			instance.email,
			instance.verifiedEmail
		);

		instance.email.readOnly = true;
		instance.email.setAttribute('aria-readonly', 'true');
		instance.email.classList.add(
			'bzj-give-email-verification__email--locked'
		);

		if (instance.resend) {
			instance.resend.disabled = true;
			instance.resend.textContent = 'Resend OTP';
		}

		if (instance.code) {
			instance.code.disabled = true;
			instance.code.value = '';
		}
	}

	function startCooldown(instance, seconds) {
		instance.cooldownUntil =
			Date.now() + Math.max(0, seconds) * 1000;

		if (instance.cooldownTimer) {
			clearInterval(instance.cooldownTimer);
		}

		function tick() {
			const remaining = Math.max(
				0,
				Math.ceil(
					(instance.cooldownUntil - Date.now()) / 1000
				)
			);

			if (remaining > 0) {
				instance.resend.disabled = true;
				instance.resend.textContent =
					'Resend (' + remaining + 's)';
			} else {
				clearInterval(instance.cooldownTimer);
				instance.cooldownTimer = null;

				if (!instance.verified) {
					instance.resend.disabled = false;
					instance.resend.textContent = 'Resend OTP';
				}
			}
		}

		tick();
		instance.cooldownTimer = setInterval(tick, 500);
	}

	function requestOtp(instance) {
		const email = normalizeEmail(instance.email.value);

		if (!validEmail(email)) {
			setStatus(
				instance,
				'Enter a valid email address first.',
				'error'
			);
			instance.email.focus();
			return;
		}

		const formId = findFormId(
			instance.scope,
			instance.email,
			instance.button
		);

		instance.requestedEmail = email;
		instance.verifiedEmail = '';
		instance.verified = false;

		setDisabled(instance.button, true);
		setStatus(
			instance,
			'Sending a verification code to ' + email + '…',
			'neutral'
		);

		post(
			'bzj_give_request_otp',
			{
				email: email,
				form_id: formId
			}
		)
			.then(function (result) {
				if (!result || !result.success) {
					const data = result && result.data
						? result.data
						: {};

					if (data.reset) {
						reset(
							instance,
							data.message ||
								'Request a new verification code.'
						);
					} else {
						setStatus(
							instance,
							data.message ||
								'Unable to send the verification code.',
							'error'
						);
					}

					return;
				}

				instance.requestedEmail = email;
				instance.verified = false;

				instance.code.disabled = false;
				instance.code.value = '';
				instance.code.focus();

				setDisabled(instance.button, true);

				setStatus(
					instance,
					result.data.message ||
						'Verification code sent. Enter it below.',
					'neutral'
				);

				startCooldown(
					instance,
					parseInt(
						result.data.cooldown || CONFIG.cooldown,
						10
					)
				);
			})
			.catch(function () {
				setStatus(
					instance,
					'Unable to contact the verification service. Please try again.',
					'error'
				);
			});
	}

	function verifyOtp(instance) {
		const code = String(instance.code.value || '')
			.toUpperCase()
			.replace(/[^A-Z0-9]/g, '');

		instance.code.value = code;
		setDisabled(instance.button, true);

		if (code.length !== CONFIG.otpLength) {
			if (instance.requestedEmail) {
				setStatus(
					instance,
					'Enter the complete 5-character code sent to your email.',
					'neutral'
				);
			}

			return;
		}

		const email = normalizeEmail(instance.email.value);

		if (!validEmail(email)) {
			reset(
				instance,
				'Enter a valid email address and request a new code.'
			);
			return;
		}

		if (
			instance.requestedEmail &&
			email !== instance.requestedEmail
		) {
			instance.requestedEmail = '';
			instance.verifiedEmail = '';
			instance.verified = false;
			instance.code.value = '';
			instance.code.disabled = true;
			setDisabled(instance.button, true);
			setStatus(
				instance,
				'Email changed. Request a new verification code for this email.',
				'error'
			);
			instance.resend.disabled = false;
			instance.resend.textContent = 'Send OTP';
			return;
		}

		const formId = findFormId(
			instance.scope,
			instance.email,
			instance.button
		);

		setStatus(
			instance,
			'Checking verification code…',
			'neutral'
		);

		post(
			'bzj_give_verify_otp',
			{
				code: code,
				form_id: formId
			}
		)
			.then(function (result) {
				if (!result || !result.success) {
					const data = result && result.data
						? result.data
						: {};

					if (data.reset) {
						reset(
							instance,
							data.message ||
								'Request a new verification code.'
						);
					} else {
						instance.code.value = '';
						instance.code.focus();

						setStatus(
							instance,
							data.message ||
								'Incorrect verification code. Try again.',
							'error'
						);
					}

					return;
				}

				instance.verified = true;
				instance.verifiedEmail = email;

				/*
				 * Canonicalize the Give email field before freezing it.
				 * The server independently enforces the same binding.
				 */
				setEmailValue(
					instance.email,
					email
				);

				lockVerifiedEmail(instance);
				setDisabled(instance.button, false);

				setStatus(
					instance,
					'Email verified. You can continue.',
					'success'
				);
			})
			.catch(function () {
				setStatus(
					instance,
					'Unable to verify the code. Please try again.',
					'error'
				);
			});
	}

	function build(instance) {
		if (!instance.button || !instance.email) {
			return false;
		}

		let wrapper = instance.scope.querySelector
			? instance.scope.querySelector(
				'.bzj-give-email-verification'
			)
			: null;

		if (
			wrapper &&
			wrapper.isConnected &&
			wrapper.parentNode === instance.button.parentNode
		) {
			instance.wrapper = wrapper;
			instance.code = wrapper.querySelector(
				'.bzj-give-email-verification__code'
			);
			instance.resend = wrapper.querySelector(
				'.bzj-give-email-verification__resend'
			);
			instance.reset = wrapper.querySelector(
				'.bzj-give-email-verification__reset'
			);
			instance.status = wrapper.querySelector(
				'.bzj-give-email-verification__status'
			);
			return true;
		}

		wrapper = instance.email.ownerDocument.createElement('div');
		wrapper.className = 'bzj-give-email-verification';
		wrapper.setAttribute('data-bzj-give-otp', '1');

		const hint =
			instance.email.ownerDocument.createElement('span');
		hint.className =
			'bzj-give-email-verification__hint';
		hint.textContent =
			'Verify your email to continue with your donation.';

		const row =
			instance.email.ownerDocument.createElement('div');
		row.className =
			'bzj-give-email-verification__row';

		const code =
			instance.email.ownerDocument.createElement('input');
		code.type = 'text';
		code.className =
			'bzj-give-email-verification__code';
		code.maxLength = CONFIG.otpLength;
		code.autocomplete = 'one-time-code';
		code.inputMode = 'text';
		code.placeholder = 'OTP code';
		code.setAttribute(
			'aria-label',
			'5-character email verification code'
		);
		code.disabled = true;

		const resend =
			instance.email.ownerDocument.createElement('button');
		resend.type = 'button';
		resend.className =
			'bzj-give-email-verification__resend';
		resend.textContent = 'Send OTP';

		const resetButton =
			instance.email.ownerDocument.createElement('button');
		resetButton.type = 'button';
		resetButton.className =
			'bzj-give-email-verification__reset';
		resetButton.textContent = 'Reset';

		const status =
			instance.email.ownerDocument.createElement('span');
		status.className =
			'bzj-give-email-verification__status is-neutral';
		status.setAttribute('role', 'status');
		status.setAttribute('aria-live', 'polite');

		row.appendChild(code);
		row.appendChild(resend);
		row.appendChild(resetButton);

		wrapper.appendChild(hint);
		wrapper.appendChild(row);
		wrapper.appendChild(status);

		/*
		 * Proposal 2:
		 * put the OTP control immediately above the live Give button.
		 */
		instance.button.parentNode.insertBefore(
			wrapper,
			instance.button
		);

		instance.wrapper = wrapper;
		instance.code = code;
		instance.resend = resend;
		instance.reset = resetButton;
		instance.status = status;

		resend.addEventListener('click', function () {
			if (!instance.verified) {
				requestOtp(instance);
			}
		});

		resetButton.addEventListener('click', function () {
			reset(
				instance,
				'Enter your email and request a verification code.'
			);

			instance.email.focus();
		});

		code.addEventListener('input', function () {
			verifyOtp(instance);
		});

		instance.email.addEventListener('input', function () {
			const current = normalizeEmail(
				instance.email.value
			);

			if (
				instance.verified &&
				current !== instance.verifiedEmail
			) {
				reset(
					instance,
					'Email changed. Request a new verification code.'
				);
				return;
			}

			if (
				instance.requestedEmail &&
				current !== instance.requestedEmail
			) {
				instance.code.value = '';
				instance.code.disabled = true;
				setDisabled(instance.button, true);
				instance.resend.disabled = false;
				instance.resend.textContent = 'Send OTP';
				setStatus(
					instance,
					'Email changed. Request a new verification code for this email.',
					'error'
				);
				instance.requestedEmail = '';
				return;
			}

			if (!instance.verified) {
				setDisabled(instance.button, true);
			}
		});

		instance.email.addEventListener('change', function () {
			const current = normalizeEmail(
				instance.email.value
			);

			if (
				instance.verified &&
				current !== instance.verifiedEmail
			) {
				reset(
					instance,
					'Email changed. Request a new verification code.'
				);
			}
		});

		reset(
			instance,
			'Enter your email and request a verification code.'
		);

		return true;
	}

	function bindDocument(doc) {
		const buttons = queryAll(doc, BUTTON_SELECTORS);

		buttons.forEach(function (button) {
			if (!button || !button.parentNode) {
				return;
			}

			const scope = findScope(button);
			const email = findEmail(scope);

			if (!email) {
				return;
			}

			let instance = state.get(button);

			if (!instance) {
				instance = {
					scope: scope,
					button: button,
					email: email,
					wrapper: null,
					code: null,
					resend: null,
					reset: null,
					status: null,
					verified: false,
					verifiedEmail: '',
					requestedEmail: '',
					cooldownUntil: 0,
					cooldownTimer: null
				};

				state.set(button, instance);
			} else {
				instance.scope = scope;
				instance.email = email;
			}

			if (
				!instance.wrapper ||
				!instance.wrapper.isConnected ||
				instance.wrapper.parentNode !== button.parentNode
			) {
				instance.wrapper = null;
				instance.code = null;
				instance.resend = null;
				instance.reset = null;
				instance.status = null;

				build(instance);
			}

			/*
			 * React can recreate the button. A fresh button is always locked
			 * until the OTP state is verified through our endpoint.
			 */
			if (!instance.verified) {
				setDisabled(button, true);
			}
		});
	}

	function observeDocument(doc) {
		if (!doc || !doc.documentElement) {
			return;
		}

		if (
			doc.documentElement.dataset.bzjGiveOtpObserved === '1'
		) {
			bindDocument(doc);
			return;
		}

		doc.documentElement.dataset.bzjGiveOtpObserved = '1';

		bindDocument(doc);

		const observer = new MutationObserver(function () {
			window.requestAnimationFrame(function () {
				bindDocument(doc);
			});
		});

		observer.observe(
			doc.documentElement,
			{
				subtree: true,
				childList: true
			}
		);
	}

	function boot() {
		getDocuments().forEach(observeDocument);

		const frameObserver = new MutationObserver(function () {
			getDocuments().forEach(observeDocument);
		});

		if (document.documentElement) {
			frameObserver.observe(
				document.documentElement,
				{
					subtree: true,
					childList: true
				}
			);
		}

		let scans = 0;

		const scanTimer = setInterval(function () {
			getDocuments().forEach(bindDocument);

			scans++;

			if (scans >= 30) {
				clearInterval(scanTimer);
			}
		}, 500);
	}

	if (document.readyState === 'loading') {
		document.addEventListener(
			'DOMContentLoaded',
			boot,
			{ once: true }
		);
	} else {
		boot();
	}
})();
</script>
		<?php
	},
	999
);
