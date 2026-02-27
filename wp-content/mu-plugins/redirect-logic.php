<?php
/**
 * MU Plugin: Redirect logged-in users away from wp-login.php
 *
 * Safer variant: runs later (priority 100) and exempts SSO/logout orchestration
 * requests so SSO MU-plugins can process tokens on login_init.
 *
 * @package MU_Redirect_Logged_In
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Redirect a logged-in visitor if they land on wp-login.php.
 *
 * Note: priority intentionally set later (100) so other MU-plugins (SSO handlers)
 * that require login_init to process logout tokens can run first. We also
 * explicitly exempt SSO-specific query params/paths from redirection.
 */
add_action( 'login_init', 'bbj_mu_redirect_logged_in_users', 100 );

function bbj_mu_redirect_logged_in_users() {
    // Only act for logged-in users.
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Only act when serving the login page (safety belt).
    $php_self = isset( $_SERVER['PHP_SELF'] ) ? strtolower( (string) $_SERVER['PHP_SELF'] ) : '';
    $req_uri  = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( (string) $_SERVER['REQUEST_URI'] ) : '';
    // Sometimes WP sets global $pagenow to 'wp-login.php' — check that too.
    $pagenow = isset( $GLOBALS['pagenow'] ) ? strtolower( (string) $GLOBALS['pagenow'] ) : '';

    if ( strpos( $php_self, 'wp-login.php' ) === false && strpos( $req_uri, 'wp-login.php' ) === false && $pagenow !== 'wp-login.php' ) {
        // Not the login page — do not act.
        return;
    }

    // Do not redirect on POST (submitting forms) or when reauth is requested.
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
        return;
    }

    if ( ! empty( $_REQUEST['reauth'] ) ) {
        // Allow reauthentication flow to proceed.
        return;
    }

    // Sanity: avoid acting during ajax/cron/REST requests or CLI
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }
    if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
        return;
    }
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return;
    }

    // IMPORTANT: Allow SSO/logout orchestration to run on wp-login.php.
    // If the request contains SSO-related markers we must NOT redirect.
    $query_raw = isset( $_SERVER['QUERY_STRING'] ) ? strtolower( (string) $_SERVER['QUERY_STRING'] ) : '';

    // If explicit WP logout action, allow it to be processed.
    if ( isset( $_REQUEST['action'] ) && strtolower( (string) $_REQUEST['action'] ) === 'logout' ) {
        return;
    }

    // If a one-time SSO token is present, allow it (sso-session-sync expects to see this).
    if ( isset( $_REQUEST['sso_one_time'] ) || isset( $_REQUEST['sso_one_time_token'] ) || isset( $_REQUEST['sso_token'] ) ) {
        return;
    }

    // Common SSO markers used by the SSO orchestration and bridges.
    $sso_markers = array(
        'sso_one_time',
        'sso_one_time_token',
        'sso_token',
        'from_wp=1',
        '/shared/sso-logout.php',
        'ww-sso-bridge.php',
        'qd-sso-bridge.php',
        'sso_action=do_login',
        'sso_client_log',
        'buzz_sso',
    );

    // Allow filter so markers can be extended if new bridges are added later.
    $sso_markers = (array) apply_filters( 'bbj_sso_login_exempt_markers', $sso_markers );

    foreach ( $sso_markers as $m ) {
        if ( ! $m ) {
            continue;
        }
        if ( strpos( $req_uri, $m ) !== false || strpos( $query_raw, $m ) !== false ) {
            // Allow the request through so SSO handlers can process it.
            return;
        }
    }

    // Determine default redirect (what WP typically uses after login).
    $current_user    = wp_get_current_user();
    $default_redirect = admin_url();

    // If a redirect_to param exists, prefer it (but validate for safety).
    $redirect_to = '';
    if ( isset( $_REQUEST['redirect_to'] ) && is_string( $_REQUEST['redirect_to'] ) && '' !== $_REQUEST['redirect_to'] ) {
        $requested = wp_unslash( (string) $_REQUEST['redirect_to'] );
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




// --- BuddyBoss login redirect compatibility (unchanged) ---
/*
add_action('plugins_loaded', function() {
    if (function_exists('bb_login_redirect')) {
        remove_filter('bp_login_redirect', 'bb_login_redirect', PHP_INT_MAX);
        remove_filter('login_redirect', 'bb_login_redirect', PHP_INT_MAX);
        add_filter('bp_login_redirect', 'bluecrown_bb_login_redirect', PHP_INT_MAX, 3);
        add_filter('login_redirect', 'bluecrown_bb_login_redirect', PHP_INT_MAX, 3);
    }
});
function bluecrown_bb_login_redirect($redirect_to, $request, $user) {
    if ($user && is_object($user) && is_a($user, 'WP_User')) {
        if (in_array('administrator', (array)$user->roles, true)) {
            return $redirect_to;
        }
        if (function_exists('bb_redirect_after_action')) {
            $redirect_to = bb_redirect_after_action($redirect_to, $user->ID, 'login');
        }
    }
    if (!empty($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])) {
        $redirect_to = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
    } else {
        if (function_exists('bb_redirect_after_action')) {
            $redirect_to = bb_redirect_after_action($redirect_to, null, 'login');
        }
    }
    return $redirect_to;
}
*/