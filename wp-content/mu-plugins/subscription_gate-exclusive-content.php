<?php
/**
 * Plugin Name: BuzzJuice — Exclusive Content Gate (MU)
 * Description: Redirects non-subscribers away from exclusive content posts (category or tag: "exclusive-content").
 * Author: Koware Dev
 * Version: 1.0
 * Must Use: true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Load subscription role helpers (fallback-safe)
// -----------------------------------------------------------------------------
if ( file_exists( ABSPATH . 'shared/subscription_gate_helpers.php' ) ) {
	require_once ABSPATH . 'shared/subscription_gate_helpers.php';
} else {
	// fallback helpers
	if ( ! function_exists( 'bzj_allowed_subscription_roles' ) ) {
		function bzj_allowed_subscription_roles() {
			return array(
				'administrator',
				'classic_lifestyle',
				'silver_lifestyle',
				'rockstar_lifestyle',
				'premium_lifestyle',
				'jewel_affiliate',
			);
		}
	}
	if ( ! function_exists( 'bzj_user_has_subscription_role' ) ) {
		function bzj_user_has_subscription_role( $user = null ) {
			if ( is_null( $user ) ) {
				if ( ! is_user_logged_in() ) {
					return false;
				}
				$user = wp_get_current_user();
			} elseif ( is_numeric( $user ) ) {
				$user = get_userdata( (int) $user );
			}

			if ( ! $user || empty( $user->roles ) ) {
				return false;
			}
			return (bool) array_intersect( (array) $user->roles, bzj_allowed_subscription_roles() );
		}
	}
}

// -----------------------------------------------------------------------------
// Exclusive Content Protection
// -----------------------------------------------------------------------------
function bzj_exclusive_content_protect() {
	if ( is_admin() ) {
		return; // don't run in wp-admin
	}

	if ( ! is_singular() ) {
		return; // only block singular posts/pages/CPTs
	}

	global $post;
	if ( ! $post ) {
		return;
	}

	// Check category or tag "exclusive-content"
	$in_exclusive_category = has_category( 'exclusive-content', $post );
	$has_exclusive_tag     = has_tag( 'exclusive-content', $post );

	if ( ! $in_exclusive_category && ! $has_exclusive_tag ) {
		return; // nothing to block
	}

	// Allow if user has subscription role or is admin
	if ( bzj_user_has_subscription_role() || current_user_can( 'manage_options' ) ) {
		return;
	}

	// Redirect otherwise
	$redirect_url = home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' );
	wp_safe_redirect( esc_url( $redirect_url ) );
	exit;
}
add_action( 'template_redirect', 'bzj_exclusive_content_protect' );

// -----------------------------------------------------------------------------
// Exclusive Pages Protection
// -----------------------------------------------------------------------------
/**
 * Restrict access to "create-forum" and "start-discussion-topic" pages.
 * - Visitors (not logged in) are redirected to WP login with redirect back to the page.
 * - Logged in but without the required role are redirected to /streams/go-pro.
 */
function restrict_page_by_role() {
    if ( is_page( array( 'create-forum', 'start-discussion-topic' ) ) ) {
        // If not logged in -> redirect to login page with redirect back here
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        // Allowed roles
        $allowed_roles = array(
            'administrator',
            'classic_lifestyle',
            'silver_lifestyle',
            'rockstar_lifestyle',
            'premium_lifestyle',
            'jewel_affiliate'
        );

        // Get current user roles
        $current_user = wp_get_current_user();
        $user_roles   = (array) $current_user->roles;

        // Check access
        $has_access = array_intersect( $allowed_roles, $user_roles );

        if ( empty( $has_access ) ) {
            // Redirect unauthorized logged-in users straight to go-pro
            $redirect_url = home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}
add_action( 'template_redirect', 'restrict_page_by_role' );
