<?php
// Path: wp-content/mu-plugins/bbj-dashboard-router.php

if (!defined('ABSPATH')) exit;

/**
 * Single-pass authoritative /dashboard router and guard.
 * Only runs on /dashboard for logged-in users, after SSO signals ready.
 */
add_action('template_redirect', 'bbj_dashboard_router_gate', 99);

function bbj_dashboard_router_gate() {
    // Bypass conditions: admin, AJAX, REST, CLI, CRON
    if (
        is_admin() ||
        wp_doing_ajax() ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (defined('WP_CLI') && WP_CLI)
    ) return;

    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();

    // Only apply to /dashboard endpoint (not partial matches)
    $request_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $is_dashboard = ($request_path === 'dashboard');

    if (!$is_dashboard) return;

    // Step 1: Require SSO ready state
    $sso_ready = (
        get_transient('bbj_sso_ready_' . $user_id) == 1 ||
        (isset($_COOKIE['bbj_sso_ready']) && $_COOKIE['bbj_sso_ready'] === '1')
    );
    if (!$sso_ready) return;

    // Step 2: Read verification state
    $verified = (string) get_user_meta($user_id, 'verified', true);

    // Step 3: Immediately consume SSO ready marker for this login cycle (prevents re-entrancy/loops)
    delete_transient('bbj_sso_ready_' . $user_id);
    setcookie('bbj_sso_ready', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);

    // Step 4: Route accordingly
    if ($verified === '1') {
        // Verified user: route to dashboard (which they're already on)
        // Optionally, could wp_safe_redirect(home_url('/dashboard')), but already there
        return;
    }

    // Not verified: send to social steps (external absolute URL for compatibility)
    wp_safe_redirect('https://buzzjuice.net/social/steps');
    exit;
}