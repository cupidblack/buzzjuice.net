<?php
/*
Plugin Name: Buzzjuice — Media & Album Gate (MU)
Description: Prevent non-subscribers from adding photos or creating albums on forums, groups, user profiles or the main activity timeline. Replace media UI with a CTA and block AJAX/REST media creation attempts server-side.
Author: Buzzjuice
Version: 1.0
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Load subscription helpers (the user requested this path).
 * The helper is expected to expose bzj_user_has_subscription_role() and bzj_allowed_subscription_roles().
 */
$bzj_helpers = ABSPATH . 'shared/subscription_gate_helpers.php';
if ( file_exists( $bzj_helpers ) ) {
	require_once $bzj_helpers;
} else {
	// Safe shim for dev environments — real site should have the helper file.
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

/* --------------------------------------------------------------------------
 * Components we enforce on
 * -------------------------------------------------------------------------- */
function bzj_media_gate_components() {
	// Normalize the components the user asked for: forums, groups, profile, activity
	return array( 'forums', 'groups', 'profile', 'activity' );
}

/* --------------------------------------------------------------------------
 * Front-end: replace Add Photos / Create Album UI for non-subscribers
 * - very defensive targeting:
 *   * only touches elements that appear inside media-specific containers
 *   * only replaces elements that match tight selectors (id=bp-add-media, classes found in BuddyBoss templates)
 *   * repeated passes via MutationObserver and auto-disconnect after a timeout
 * - CTA opens in the same tab
 * -------------------------------------------------------------------------- */
function bzj_enqueue_media_gate_script() {
	// Only on front-end and for logged-in non-subscribers
	if ( is_admin() || ! is_user_logged_in() ) {
		return;
	}

	if ( function_exists( 'bzj_user_has_subscription_role' ) && bzj_user_has_subscription_role( get_current_user_id() ) ) {
		return;
	}

	// Register an empty handle and inline our defensive JS
	$handle = 'bzj-media-gate';
	wp_register_script( $handle, '', array(), '1.0', true );
	wp_enqueue_script( $handle );

	$data = array(
		'cta_url'           => home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' ),
		'add_photos_text'   => 'Tap here to purchase a subscription to add photos',
		'create_album_text' => 'Tap here to purchase a subscription to create albums',
		'max_observe_ms'    => 7000,
	);

	wp_localize_script( $handle, 'bzjMediaGateData', $data );

	$inline = <<<'JS'
(function(){
	if (typeof bzjMediaGateData === 'undefined') return;
	var CTA_URL = bzjMediaGateData.cta_url;
	var ADD_TEXT = bzjMediaGateData.add_photos_text;
	var ALBUM_TEXT = bzjMediaGateData.create_album_text;
	var MAX_MS = parseInt(bzjMediaGateData.max_observe_ms,10) || 7000;

	// Tight selectors used by BuddyBoss/BuddyPress templates
	var selectors = [
		'#bp-add-media',                 // id used in templates
		'.bb-add-media',
		'.bb-add-photos',
		'.bb-create-album',
		'#bb-create-album',
		'.album-add-photos',
		'.bb-album-add-photos'
	];

	// Media-specific ancestor containers used as an extra guard (do not touch elements outside these)
	var mediaAncestors = [
		'[data-component="media"]',
		'[data-action="bp-add-media"]',
		'[data-bp-action*="media"]',
		'.bb-media-actions-wrap',
		'.media-options',
		'.bb-media-actions',
		'.bb-media-actions-wrap',
		'.bp-media',
		'.bp-media-container',
		'.bp-media-actions',
		'.media-options'
	];

	function findMediaAncestor(el){
		for(var i=0;i<mediaAncestors.length;i++){
			if(el.closest && el.closest(mediaAncestors[i])) return el.closest(mediaAncestors[i]);
		}
		return null;
	}

	function makeCTA(isAlbum){
		var wrap = document.createElement('div');
		wrap.className = 'bzj-media-cta-wrap';
		var a = document.createElement('a');
		a.className = 'bzj-media-cta';
		a.href = CTA_URL;
		a.target = '_self'; // same tab
		a.textContent = isAlbum ? ALBUM_TEXT : ADD_TEXT;
		a.style.cssText = 'display:inline-block;padding:8px 12px;margin:6px 0;border:1px dashed #f0a84a;border-radius:8px;background:#fffdf8;font-weight:700;color:#111;text-decoration:none';
		wrap.appendChild(a);
		return wrap;
	}

	function safeReplace(el){
		try {
			if (!el || !el.parentNode) return;
			// only replace if inside a media-specific container
			var anc = findMediaAncestor(el);
			if (!anc) return;

			// Avoid duplicate CTAs
			if (anc.querySelector('.bzj-media-cta')) {
				// hide original for cleanliness
				el.style.display = 'none';
				return;
			}

			var isAlbum = /create-album|album-add-photos|bb-album-add-photos/.test(el.id + ' ' + el.className);
			var cta = makeCTA(isAlbum);

			// Insert CTA next to the original control and hide original
			el.parentNode.insertBefore(cta, el);
			el.style.display = 'none';

			// hide uploader and album creation forms inside the same ancestor if present
			var uploaderSelectors = ['.bp-media-uploader','.bb-media-uploader','#bp-media-uploader','.media-uploader','.bp-media-upload-form'];
			uploaderSelectors.forEach(function(s){
				var u = anc.querySelector(s);
				if (u) u.style.display = 'none';
			});
			var albumFormSelectors = ['.bp-media-create-album','.bb-create-album-form','.bp-media-create-album-form','#bb-create-album-form','.bp-media-create-album'];
			albumFormSelectors.forEach(function(s){
				var f = anc.querySelector(s);
				if (f) f.style.display = 'none';
			});
		} catch(e){}
	}

	function scan(){
		for(var i=0;i<selectors.length;i++){
			var nodes = document.querySelectorAll(selectors[i]);
			for(var j=0;j<nodes.length;j++){
				safeReplace(nodes[j]);
			}
		}
	}

	// initial pass
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', scan);
	} else {
		scan();
	}

	// Observe mutations for dynamically injected UI (disconnect after MAX_MS)
	var mo = new MutationObserver(scan);
	try {
		mo.observe(document.body, { childList: true, subtree: true });
	} catch(e){}
	setTimeout(function(){ try { mo.disconnect(); } catch(e) {} }, MAX_MS);
})();
JS;

	wp_add_inline_script( $handle, $inline );
}
add_action( 'wp_enqueue_scripts', 'bzj_enqueue_media_gate_script', 5 );

/* --------------------------------------------------------------------------
 * Output buffer replacement on media pages / media index to always show CTA
 * This hides the "Add Photos" anchor/buttons and the album creation markup server-side.
 * It's narrow: only runs for front-end requests and when we detect media-related URIs or BuddyPress media contexts.
 * -------------------------------------------------------------------------- */
function bzj_maybe_start_media_ob() {
	if ( is_admin() || ! is_user_logged_in() ) {
		return;
	}
	if ( function_exists( 'bzj_user_has_subscription_role' ) && bzj_user_has_subscription_role( get_current_user_id() ) ) {
		return;
	}

	// Only buffer where media UI is likely: media listing URIs or BuddyPress contexts
	$should_buffer = false;

	if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
		$should_buffer = true;
	} elseif ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
		$should_buffer = true;
	} elseif ( strpos( $_SERVER['REQUEST_URI'], '/media' ) !== false || strpos( $_SERVER['REQUEST_URI'], '/photos' ) !== false ) {
		$should_buffer = true;
	}

	if ( $should_buffer ) {
		ob_start( 'bzj_filter_media_ui_html' );
	}
}
add_action( 'template_redirect', 'bzj_maybe_start_media_ob', 1 );

function bzj_filter_media_ui_html( $html ) {
	if ( empty( $html ) ) {
		return $html;
	}

	$cta_url = esc_url( home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' ) );
	$add_text = esc_html( 'Tap here to purchase a subscription to add photos' );
	$album_text = esc_html( 'Tap here to purchase a subscription to create albums' );

	// Replace known Add Photos anchors (id=bp-add-media and common classes) when inside media templates.
	// These regexes are conservative and target the anchor elements only.
	$html = preg_replace(
		'#<a[^>]+id=["\']bp-add-media["\'][^>]*>.*?<\/a>#is',
		'<div class="bzj-media-cta-wrap"><a class="bzj-media-cta" href="' . $cta_url . '" target="_self">' . $add_text . '</a></div>',
		$html
	);

	$html = preg_replace(
		'#<a[^>]+class=["\'][^"\']*bb-add-photos[^"\']*["\'][^>]*>.*?<\/a>#is',
		'<div class="bzj-media-cta-wrap"><a class="bzj-media-cta" href="' . $cta_url . '" target="_self">' . $add_text . '</a></div>',
		$html
	);

	$html = preg_replace(
		'#<a[^>]+(id|class)=["\'][^"\']*(bb-create-album|album-add-photos|bb-album-add-photos)[^"\']*["\'][^>]*>.*?<\/a>#is',
		'<div class="bzj-media-cta-wrap"><a class="bzj-media-cta" href="' . $cta_url . '" target="_self">' . $album_text . '</a></div>',
		$html
	);

	// Hide uploader forms and album creation forms server-side if present in HTML
	$html = preg_replace('#<div[^>]+class=["\'][^"\']*(bp-media-uploader|bb-media-uploader|media-uploader|bp-media-upload-form)[^"\']*["\'][^>]*>.*?<\/div>#is', '<div class="bzj-media-uploader-hidden" style="display:none"></div>', $html);
	$html = preg_replace('#<div[^>]+class=["\'][^"\']*(bp-media-create-album|bb-create-album-form|bp-media-create-album-form)[^"\']*["\'][^>]*>.*?<\/div>#is', '<div class="bzj-media-albumform-hidden" style="display:none"></div>', $html);

	return $html;
}

/* --------------------------------------------------------------------------
 * Server-side AJAX guard (admin-ajax.php) - narrow, only blocks real media add attempts.
 *
 * Requirements:
 *  - DOING_AJAX
 *  - action === 'bp_add_media' or 'bp-add-media' OR a valid nonce for bp-add-media
 *  - component is one of the enforced components: forums, groups, profile, activity
 *
 * This avoids blocking unrelated admin-ajax requests that may include component parameters.
 * -------------------------------------------------------------------------- */
function bzj_media_ajax_guard() {
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	// Only logged-in users are relevant; anonymous can't add media anyway
	if ( ! is_user_logged_in() ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
	$component = isset( $_REQUEST['component'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['component'] ) ) : '';
	$uid = get_current_user_id();

	// Determine whether this is a genuine bp-add-media attempt:
	$has_valid_nonce = false;
	if ( ! empty( $_REQUEST['_wpnonce_bp_add_media'] ) ) {
		$has_valid_nonce = wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce_bp_add_media'] ), 'bp-add-media' );
	} elseif ( ! empty( $_REQUEST['_wpnonce_bp-add-media'] ) ) {
		$has_valid_nonce = wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce_bp-add-media'] ), 'bp-add-media' );
	} elseif ( ! empty( $_REQUEST['nonce'] ) ) {
		// sometimes themes send generic nonce param
		$has_valid_nonce = wp_verify_nonce( wp_unslash( $_REQUEST['nonce'] ), 'bp-add-media' );
	}

	$is_media_action = ( 'bp_add_media' === $action ) || ( 'bp-add-media' === $action ) || $has_valid_nonce;

	if ( ! $is_media_action ) {
		return; // not a media add action -> do not interfere
	}

	// Now only enforce for specific components (forums, groups, profile, activity)
	$allowed_components = bzj_media_gate_components();
	if ( $component && ! in_array( $component, $allowed_components, true ) ) {
		// component provided but not one we enforce -> bail
		return;
	}

	// If component not provided, we still proceed because many media requests are component-scoped elsewhere;
	// but to avoid false positives we only block if we definitely recognize the bp-add-media action.
	// Allow subscribers & admins to proceed.
	if ( bzj_user_has_subscription_role( $uid ) || user_can( $uid, 'manage_options' ) ) {
		return;
	}

	// Block: return JSON error with user display info and component info
	$user = wp_get_current_user();
	$payload = array(
		'error'         => 'subscription_required',
		'message'       => 'You need a subscription to add photos or create albums.',
		'user_id'       => $uid,
		'user_display'  => $user ? $user->display_name : '',
		'user_roles'    => $user ? $user->roles : array(),
		'component'     => $component ? $component : 'unknown',
	);

	// Use 403 to match REST behaviour
	wp_send_json_error( $payload, 403 );
}
add_action( 'admin_init', 'bzj_media_ajax_guard', 1 );

/* --------------------------------------------------------------------------
 * REST guard: only enforce on POST requests that include component=forums|groups|profile|activity
 * Return a WP_Error with structured data including user display name and component info.
 * -------------------------------------------------------------------------- */
function bzj_media_rest_pre_dispatch( $result, $server, $request ) {
	// Only proceed when we have a request object
	if ( ! $request || ! method_exists( $request, 'get_method' ) ) {
		return $result;
	}

	// Only enforce on POST style creation attempts
	if ( 'POST' !== strtoupper( $request->get_method() ) ) {
		return $result;
	}

	// Only logged-in users
	if ( ! is_user_logged_in() ) {
		return $result;
	}

	$uid = get_current_user_id();

	// Allow subscribers & admins
	if ( bzj_user_has_subscription_role( $uid ) || user_can( $uid, 'manage_options' ) ) {
		return $result;
	}

	// Check component param if present
	$component = $request->get_param( 'component' );
	$allowed_components = bzj_media_gate_components();

	if ( $component && ! in_array( $component, $allowed_components, true ) ) {
		// Not an enforced component - do nothing
		return $result;
	}

	// Narrow to known media endpoints too (if route contains media/albums)
	$route = method_exists( $request, 'get_route' ) ? $request->get_route() : '';
	$is_media_route = false;
	if ( $route ) {
		if ( false !== strpos( $route, '/media' ) || false !== strpos( $route, '/albums' ) ) {
			$is_media_route = true;
		}
	}

	// If neither a declared component we enforce nor a media REST endpoint, bail
	if ( ! $component && ! $is_media_route ) {
		return $result;
	}

	// Block the POST: return WP_Error with structured data for client-side messaging
	$user = wp_get_current_user();
	$data = array(
		'user_id'      => $uid,
		'user_display' => $user ? $user->display_name : '',
		'user_roles'   => $user ? $user->roles : array(),
		'component'    => $component ? $component : ( $is_media_route ? 'media' : 'unknown' ),
	);

	return new WP_Error(
		'bzj_media_subscription_required',
		__( 'Subscription required to add photos or create albums', 'buzzjuice' ),
		array(
			'status' => 403,
			'data'   => $data,
		)
	);
}
add_filter( 'rest_pre_dispatch', 'bzj_media_rest_pre_dispatch', 10, 3 );

/* --------------------------------------------------------------------------
 * Utility for templates / other code to check if a user can create media
 * (useful if a theme wants to render composer conditionally)
 * -------------------------------------------------------------------------- */
function bzj_user_can_create_media( $user_id = null ) {
	if ( is_null( $user_id ) ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}
	if ( bzj_user_has_subscription_role( $user_id ) || user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	return false;
}

/* --------------------------------------------------------------------------
 * Small admin helper: add a class to body if media gate is active for current user
 * (optional cosmetic hook; harmless)
 * -------------------------------------------------------------------------- */
function bzj_media_gate_body_class( $classes ) {
	if ( is_user_logged_in() && function_exists( 'bzj_user_has_subscription_role' ) && ! bzj_user_has_subscription_role( get_current_user_id() ) ) {
		$classes[] = 'bzj-media-gate-active';
	}
	return $classes;
}
add_filter( 'body_class', 'bzj_media_gate_body_class' );

/* End of plugin */