<?php
/**
 * Plugin Name: GiveWP Slug Override — contributions
 * Description: Change GiveWP public base slug from "/donations/..." to "/contributions/...".
 *              Includes a one-time rewrite flush (admin-only) and an optional 301 redirect
 *              from /donations/... → /contributions/... to preserve existing links/SEO.
 * Version:     1.0
 * Author:      Blue Crown / ChatGPT
 *
 * Installation:
 *  - Copy this file to: wp-content/mu-plugins/give-slug-override.php
 *  - Visit WP Admin → Settings → Permalinks and click "Save Changes" OR let the plugin flush once (admin-only).
 *
 * Notes:
 *  - MU-plugins load on every request and do not trigger activation hooks, so this file flushes rewrites
 *    once for an administrator (to avoid flushing on every page load).
 *  - If your GiveWP install uses a different CPT name than `give_forms`, change the fallback check accordingly.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Primary: use GiveWP's official filter (if available) to change the front-end slug.
 * This is the safest and most future-proof approach.
 */
add_filter( 'give_rewrite_slug', 'bz_change_give_slug_to_contributions' );
function bz_change_give_slug_to_contributions( $slug ) {
    return 'contributions';
}

/**
 * Fallback: if the Give filter isn't available in your version, adjust the CPT rewrite args.
 * Keep this as a fallback only; the give_rewrite_slug filter above is preferred.
 */
add_filter( 'register_post_type_args', 'bz_give_register_post_type_args_fallback', 10, 2 );
function bz_give_register_post_type_args_fallback( $args, $post_type ) {
    // Give's form CPT is usually 'give_forms' — change here if yours differs
    if ( $post_type === 'give_forms' ) {
        if ( empty( $args['rewrite'] ) || ! is_array( $args['rewrite'] ) ) {
            $args['rewrite'] = array(
                'slug'       => 'contributions',
                'with_front' => false,
            );
        } else {
            $args['rewrite']['slug'] = 'contributions';
            if ( ! isset( $args['rewrite']['with_front'] ) ) {
                $args['rewrite']['with_front'] = false;
            }
        }
    }
    return $args;
}

/**
 * One-time flush of rewrite rules:
 * - Only runs in admin context and only when an admin with manage_options visits.
 * - Sets an option so it never flushes again. This avoids performance issues.
 */
add_action( 'admin_init', 'bz_give_flush_rewrite_rules_once' );
function bz_give_flush_rewrite_rules_once() {
    // Only allow administrators to trigger the one-time flush
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Don't run more than once
    if ( get_option( 'bz_give_slug_flushed', false ) ) {
        return;
    }

    // Only flush if Give's post type exists (best-effort)
    if ( post_type_exists( 'give_forms' ) || class_exists( 'Give' ) || function_exists( 'give' ) ) {
        // false param avoids writing .htaccess if WP is configured to not do so — use safe mode
        flush_rewrite_rules( false );
        update_option( 'bz_give_slug_flushed', time() );
    }
}

/**
 * Optional: redirect old /donations/... URLs to /contributions/... with a 301.
 * Useful when you are changing an existing site to preserve SEO / incoming links.
 *
 * If you prefer server-level redirects (recommended for performance), add an Apache/Nginx rule
 * instead and comment-out this block.
 */
add_action( 'template_redirect', 'bz_redirect_old_give_donations_urls' );
function bz_redirect_old_give_donations_urls() {
    // Only run on front-end requests
    if ( is_admin() ) {
        return;
    }

    $uri = (string) ( $_SERVER['REQUEST_URI'] ?? '' );

    // Match /donations or /donations/* at the beginning of the path
    if ( preg_match( '#^/donations(?:/|$)#i', $uri ) ) {
        $new_path = preg_replace( '#^/donations#i', '/contributions', $uri, 1 );

        // Build absolute URL using site_url() to avoid host mismatches
        $redirect_url = site_url( $new_path );

        // 301 permanent redirect
        wp_redirect( $redirect_url, 301 );
        exit;
    }
}