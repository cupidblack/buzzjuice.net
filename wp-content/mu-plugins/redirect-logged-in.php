<?php
/**
 * MU Plugin: Redirect logged-in users away from wp-login.php
 *
 * If a logged-in user lands on the login page (GET), this will redirect them
 * to the URL provided by the "redirect_to" query parameter (if present and safe),
 * otherwise to the default login redirect (admin dashboard by default). It will
 * skip redirection for POST requests and re-auth flows.
 *
 * Drop this single file into: wp-content/mu-plugins/redirect-logged-in.php
 *
 * @package MU_Redirect_Logged_In
 * @author  blueberrybuzzjuice
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect a logged-in visitor if they land on wp-login.php.
 *
 * Hooks early on login_init so it runs when wp-login.php is being served.
 */
add_action( 'login_init', 'bbj_mu_redirect_logged_in_users', 1 );

function bbj_mu_redirect_logged_in_users() {
	// Only act for logged-in users.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Do not redirect on POST (submitting forms) or when reauth is requested.
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	if ( ! empty( $_REQUEST['reauth'] ) ) {
		// Allow reauthentication flow to proceed.
		return;
	}

	// Sanity: avoid acting during ajax/cron requests.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}

	// Determine default redirect (what WP typically uses after login).
	$current_user = wp_get_current_user();
	$default_redirect = admin_url();

	// If a redirect_to param exists, prefer it (but validate for safety).
	$redirect_to = '';
	if ( isset( $_REQUEST['redirect_to'] ) && is_string( $_REQUEST['redirect_to'] ) && '' !== $_REQUEST['redirect_to'] ) {
		$requested = wp_unslash( $_REQUEST['redirect_to'] );
		// Validate and sanitize; falls back to $default_redirect if not allowed.
		$redirect_to = function_exists( 'wp_validate_redirect' ) ? wp_validate_redirect( $requested, $default_redirect ) : esc_url_raw( $requested );
	} else {
		// Use the same filter chain WP uses for login redirects so themes/plugins can modify.
		$redirect_to = apply_filters( 'login_redirect', $default_redirect, '', $current_user );
	}

	// Prevent redirecting back to the login page itself (avoid loops).
	if ( ! $redirect_to ) {
		return;
	}
	$login_page = wp_login_url();
	if ( strpos( $redirect_to, 'wp-login.php' ) !== false || untrailingslashit( $redirect_to ) === untrailingslashit( $login_page ) ) {
		// If the requested redirect would send back to login, use default dashboard instead.
		$redirect_to = $default_redirect;
	}

	// Finally perform a safe redirect.
	wp_safe_redirect( $redirect_to );
	exit;
}