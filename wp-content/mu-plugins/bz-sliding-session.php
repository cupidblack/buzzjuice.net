<?php
/**
 * Plugin Name: Buzzjuice Sliding Session Expiration (True Sliding)
 * Description: True sliding session: session extended logically on activity without rotating session tokens/cookies, fully compatible with nonces, forms and JWT SSO. 60 hours normal, 15 days Remember Me. Uses WordPress core hooks.
 * Version: 2.0
 * Author: Buzzjuice Team
 */

if (!defined('ABSPATH')) exit;

/**
 * -----------------------------------------------------------------------------
 * CONFIGURATION
 * -----------------------------------------------------------------------------
 */
define('BZ_SLIDING_NORMAL_HOURS', 60);   // Normal login duration (hours)
define('BZ_SLIDING_REMEMBER_DAYS', 15);  // Remember Me login duration (days)
define('BZ_SLIDING_META_LAST', 'buzzjuice_last_activity');
define('BZ_SLIDING_META_REMEMBER', 'buzzjuice_remember_me');

/**
 * -----------------------------------------------------------------------------
 * COOKIE EXPIRATION (INITIAL, ON LOGIN ONLY)
 * -----------------------------------------------------------------------------
 * Set WordPress session duration: 60h, 15d (never rotated after login)
 */
add_filter('auth_cookie_expiration', function($expiration, $user_id, $remember) {
    return $remember ? BZ_SLIDING_REMEMBER_DAYS * DAY_IN_SECONDS : BZ_SLIDING_NORMAL_HOURS * HOUR_IN_SECONDS;
}, 10, 3);

add_filter('logged_in_cookie_expiration', function($expiration, $user_id, $remember) {
    return $remember ? BZ_SLIDING_REMEMBER_DAYS * DAY_IN_SECONDS : BZ_SLIDING_NORMAL_HOURS * HOUR_IN_SECONDS;
}, 10, 3);

/**
 * -----------------------------------------------------------------------------
 * TRACK USER ACTIVITY (Sliding, NEVER on POST)
 * -----------------------------------------------------------------------------
 * Updates last activity timestamp for sliding expiration.
 * Only for safe request methods (GET, HEAD). Never for POST or nonce validation.
 * Tracks everywhere: admin, frontend, REST, AJAX, BuddyBoss, etc.
 */
add_action('init', function() {
    if (!is_user_logged_in()) return;
    $safe_methods = array('GET', 'HEAD');
    if (!in_array($_SERVER['REQUEST_METHOD'], $safe_methods)) return;
    $user_id = get_current_user_id();
    update_user_meta($user_id, BZ_SLIDING_META_LAST, time());
}, 10);

/**
 * -----------------------------------------------------------------------------
 * STORE REMEMBER ME STATE AT LOGIN
 * -----------------------------------------------------------------------------
 * WordPress does not retain remember-me status across session, so we track it once at login.
 */
add_action('wp_login', function($user_login, $user) {
    $remember = !empty($_POST['rememberme']);
    update_user_meta($user->ID, BZ_SLIDING_META_REMEMBER, $remember ? 'yes' : 'no');
}, 10, 2);

/**
 * -----------------------------------------------------------------------------
 * ENFORCE SLIDING SESSION EXPIRATION
 * -----------------------------------------------------------------------------
 * Log out user if inactivity exceeds session limit.
 * Never rotates tokens/cookies, never breaks nonces/forms.
 * Compatible with admin, frontend, APIs and JWT SSO.
 */
add_action('init', function() {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $last_activity = (int) get_user_meta($user_id, BZ_SLIDING_META_LAST, true);

    if (!$last_activity) {
        update_user_meta($user_id, BZ_SLIDING_META_LAST, time());
        return;
    }

    // Use stored remember-me state from login
    $remember = get_user_meta($user_id, BZ_SLIDING_META_REMEMBER, true) === 'yes';
    $session_limit = $remember
        ? BZ_SLIDING_REMEMBER_DAYS * DAY_IN_SECONDS
        : BZ_SLIDING_NORMAL_HOURS * HOUR_IN_SECONDS;

    if ((time() - $last_activity) > $session_limit) {
        wp_logout();
        // Optionally redirect to login unless API/AJAX/REST
        if (
            empty($_SERVER['HTTP_X_REQUESTED_WITH']) && // not ajax
            !wp_doing_ajax() &&
            !(defined('REST_REQUEST') && REST_REQUEST) &&
            !headers_sent()
        ) {
            wp_safe_redirect(wp_login_url());
            exit;
        }
    }
}, 20);

/**
 * -----------------------------------------------------------------------------
 * CLEAN UP USER META ON LOGOUT
 * -----------------------------------------------------------------------------
 */
add_action('wp_logout', function() {
    $user_id = get_current_user_id();
    if ($user_id) {
        delete_user_meta($user_id, BZ_SLIDING_META_LAST);
        delete_user_meta($user_id, BZ_SLIDING_META_REMEMBER);
    }
});