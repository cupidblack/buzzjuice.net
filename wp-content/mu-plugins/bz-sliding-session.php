<?php
/**
 * Plugin Name: Buzzjuice Sliding Session Expiration
 * Description: Provides rolling/sliding login session expiration: 48 hours or 14 days (with Remember Me) from latest activity. Designed for BuddyBoss/WordPress.
 * Version: 1.1
 * Author: Buzzjuice Team
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Track last activity (frontend only)
add_action( 'init', function() {
	if ( is_user_logged_in() ) {
		if ( ! is_admin() && ! wp_doing_ajax() && ! (defined('REST_REQUEST') && REST_REQUEST) ) {
			update_user_meta( get_current_user_id(), 'buzzjuice_last_activity', time() );
		}
	}
}, 8);

// 2. On each user activity, refresh session cookie (sliding)
add_action( 'init', function() {
	if ( is_user_logged_in() ) {
		if ( ! is_admin() && ! wp_doing_ajax() && ! (defined('REST_REQUEST') && REST_REQUEST) ) {
			$user_id = get_current_user_id();
			$remember = get_user_meta( $user_id, 'buzzjuice_remember_me', true ) === 'yes';
			// Rolling expiry handled by wp_set_auth_cookie
			wp_set_auth_cookie( $user_id, $remember, is_ssl() );
		}
	}
}, 9);

// 3. Detect Remember Me on login
add_action( 'wp_login', function( $user_login, $user ) {
	$remember = !empty( $_POST['rememberme'] );
	update_user_meta( $user->ID, 'buzzjuice_remember_me', $remember ? 'yes' : 'no' );
}, 10, 2);

// 4. Scheduled fallback refresh (daily for normal, weekly for remember-me users)
if ( ! wp_next_scheduled( 'buzzjuice_daily_session_refresh' ) )
	wp_schedule_event( time(), 'daily', 'buzzjuice_daily_session_refresh' );

add_action( 'buzzjuice_daily_session_refresh', function() {
	$now = time();
	$users = get_users( [ 'fields' => [ 'ID' ] ] );
	foreach ( $users as $user ) {
		$last = get_user_meta( $user->ID, 'buzzjuice_last_activity', true );
		$remember = get_user_meta( $user->ID, 'buzzjuice_remember_me', true ) === 'yes';
		$expiration = $remember ? 14 * DAY_IN_SECONDS : 2 * DAY_IN_SECONDS;

		// For "Remember Me" (weekly fallback)
		if ( $remember && date('w', $now) == 1 ) { // Run only on Mondays (or adjust as needed)
			if ( $last && ($now - $last) < $expiration )
				wp_set_auth_cookie( $user->ID, $remember, is_ssl() );
		}

		// For normal sessions (daily)
		if ( ! $remember ) {
			if ( $last && ($now - $last) < $expiration )
				wp_set_auth_cookie( $user->ID, $remember, is_ssl() );
		}
	}
});

// 5. Clean up user meta on logout
add_action( 'wp_logout', function() {
	$user_id = get_current_user_id();
	delete_user_meta( $user_id, 'buzzjuice_last_activity' );
	delete_user_meta( $user_id, 'buzzjuice_remember_me' );
});

// 6. Clean up cron job on plugin deactivation
register_deactivation_hook( __FILE__, function() {
	wp_clear_scheduled_hook( 'buzzjuice_daily_session_refresh' );
});