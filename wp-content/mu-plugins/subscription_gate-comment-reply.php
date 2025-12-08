<?php
/**
 * Plugin Name: BuzzJuice bbPress Reply Restriction (Quick Reply + Activity)
 * Description: Replaces bbPress "Reply" and BuddyBoss "Quick Reply" with a Subscribe CTA for non-subscriber roles. Blocks quick-reply AJAX endpoint server-side for non-subscribers.
 * Author: Koware Dev (updated)
 * Version: 1.6
 * License: GPLv2 or later
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load project's shared helper if present (per your repository layout).
 * Use the shared helper in repo root when available; fallback shim otherwise.
 */
$shared_helper = ABSPATH . 'shared/subscription_gate_helpers.php';
if ( file_exists( $shared_helper ) ) {
	require_once $shared_helper;
} else {
	// Conservative fallback shim only used when the shared helper is absent.
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

/**
 * CTA target and label — adjust as required via filters:
 *   add_filter( 'buzzjuice_subscription_target_url', fn() => 'https://.../purchase' );
 *   add_filter( 'buzzjuice_subscription_cta_label', fn() => 'Subscribe to Reply' );
 */
function buzzjuice_subscription_target_url() {
	// Default target is your existing SSO bridge subscription flow (same as earlier file).
	return apply_filters( 'buzzjuice_subscription_target_url', home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' ) );
}

function buzzjuice_subscription_cta_label() {
	return apply_filters( 'buzzjuice_subscription_cta_label', 'Subscribe to Reply' );
}

/**
 * Helper: check current user subscription role using shared helper when available.
 */
function buzzjuice_current_user_is_subscriber( $user = null ) {
	if ( function_exists( 'bzj_user_has_subscription_role' ) ) {
		return (bool) bzj_user_has_subscription_role( $user );
	}

	// fallback shim
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

/* ------------------------------------------------------------
 * 1) Keep replacing standard bbPress "Reply" links (topic pages)
 *    This preserves the previous behavior for reply links on single topics.
 * ------------------------------------------------------------ */
add_filter( 'bbp_get_reply_link', 'buzzjuice_replace_reply_button', 10, 2 );
add_filter( 'bbp_get_topic_reply_link', 'buzzjuice_replace_reply_button', 10, 2 );

function buzzjuice_replace_reply_button( $link, $args ) {
	// Allow subscribers and site admins to keep the normal reply button
	if ( buzzjuice_current_user_is_subscriber() || current_user_can( 'manage_options' ) ) {
		return $link;
	}

	return buzzjuice_cta_placeholder();
}

/**
 * CTA markup used for reply replacement and quick-reply replacements.
 */
function buzzjuice_cta_placeholder() {
	$label = esc_html( buzzjuice_subscription_cta_label() );

	// If not logged in, send users through login with redirect to the CTA target.
	if ( is_user_logged_in() ) {
		$target = esc_url( buzzjuice_subscription_target_url() );
	} else {
		$target = esc_url( wp_login_url( buzzjuice_subscription_target_url() ) );
	}

	// Minimal markup; keep styling consistent with prior plugin but safe.
	$tpl = '<div class="buzzjuice-cta" style="text-align:center; margin:18px 0;">'
		. '<a href="%1$s" class="buzzjuice-reply-cta" style="color:#fff; background:#385dff; padding:10px 14px; border-radius:20px; text-decoration:none; display:inline-block; font-weight:600; font-size:14px;">%2$s</a>'
		. '</div>';

	return sprintf( $tpl, $target, $label );
}

/* ------------------------------------------------------------
 * 2) Replace BuddyBoss/BP/Nouveau "Quick Reply" activity buttons for non-subscribers
 *
 * We're conservative: operate only on buttons that are clearly quick-reply controls.
 * We detect by:
 *  - button array key 'quick_reply', OR
 *  - button_attr['data-btn-id'] === 'bbp-reply-form'
 *
 * This preserves "Join Discussion" buttons and other unrelated actions.
 * ------------------------------------------------------------ */
add_filter( 'bb_nouveau_get_activity_inner_buttons', 'buzzjuice_replace_activity_quick_reply_button', 20, 2 );
add_filter( 'bb_rl_activity_inner_buttons', 'buzzjuice_replace_activity_quick_reply_button', 20, 2 ); // defensive: readylaunch variants

function buzzjuice_replace_activity_quick_reply_button( $buttons, $activity_id ) {
	// Allow subscribers and site admins to keep original behavior
	if ( buzzjuice_current_user_is_subscriber() || current_user_can( 'manage_options' ) ) {
		return $buttons;
	}

	// Build CTA URL (logged-in goes straight; logged-out goes to login then CTA)
	if ( is_user_logged_in() ) {
		$cta_url = esc_url( buzzjuice_subscription_target_url() );
	} else {
		$cta_url = esc_url( wp_login_url( buzzjuice_subscription_target_url() ) );
	}

	$cta_label = esc_html( buzzjuice_subscription_cta_label() );

	foreach ( (array) $buttons as $key => $btn ) {
		// Keep safety checks: expect button to be an array
		if ( ! is_array( $btn ) ) {
			continue;
		}

		$should_replace = false;

		// Replace explicit quick_reply key
		if ( 'quick_reply' === $key ) {
			$should_replace = true;
		}

		// Or when data-btn-id targets bbp reply form
		if ( ! $should_replace && isset( $btn['button_attr'] ) && is_array( $btn['button_attr'] ) ) {
			if ( isset( $btn['button_attr']['data-btn-id'] ) && 'bbp-reply-form' === $btn['button_attr']['data-btn-id'] ) {
				$should_replace = true;
			}
		}

		if ( ! $should_replace ) {
			continue;
		}

		// Replace visible label, using same accessible structure many buttons use.
		$buttons[ $key ]['link_text'] = sprintf(
			'<span class="bp-screen-reader-text">%1$s</span> <span class="comment-count">%2$s</span>',
			$cta_label,
			$cta_label
		);

		// Make button a harmless anchor pointing to CTA (so theme JS won't try to open the quick-reply)
		$buttons[ $key ]['must_be_logged_in'] = false;
		$buttons[ $key ]['button_element']   = 'a';

		if ( empty( $buttons[ $key ]['button_attr'] ) || ! is_array( $buttons[ $key ]['button_attr'] ) ) {
			$buttons[ $key ]['button_attr'] = array();
		}

		$buttons[ $key ]['button_attr']['href']  = $cta_url;
		$buttons[ $key ]['button_attr']['class'] = ( isset( $buttons[ $key ]['button_attr']['class'] ) ? $buttons[ $key ]['button_attr']['class'] . ' ' : '' ) . 'buzzjuice-reply-cta bp-secondary-action';

		// Remove attributes that would trigger quick-reply flows or pass data to JS.
		$remove_data_attrs = array( 'data-btn-id', 'data-topic-id', 'data-topic-title', 'data-topic-title-raw', 'data-author-name', 'aria-expanded' );
		foreach ( $remove_data_attrs as $attr ) {
			if ( isset( $buttons[ $key ]['button_attr'][ $attr ] ) ) {
				unset( $buttons[ $key ]['button_attr'][ $attr ] );
			}
		}
	}

	return $buttons;
}

/* ------------------------------------------------------------
 * 3) Defensive front-end swap (short lifespan MutationObserver)
 *
 * If some scripts inject quick-reply anchors after server-side filters run,
 * this small script will swap those anchors to CTA anchors for non-subscribers.
 * Only enqueued for non-subscribers on the front-end.
 * ------------------------------------------------------------ */
add_action( 'wp_enqueue_scripts', 'buzzjuice_enqueue_quick_reply_fallback_script', 9 );

function buzzjuice_enqueue_quick_reply_fallback_script() {
	if ( is_admin() ) {
		return;
	}

	// Only for non-subscribers
	if ( buzzjuice_current_user_is_subscriber() || current_user_can( 'manage_options' ) ) {
		return;
	}

	$cta_url  = esc_url( buzzjuice_subscription_target_url() );
	$cta_text = esc_js( buzzjuice_subscription_cta_label() );

	$inline_js = "(function(){\n"
		. "var CTA_URL = '" . $cta_url . "';\n"
		. "var CTA_TEXT = '" . $cta_text . "';\n"
		. "function isQuickReply(el){ if(!el) return false; try{ if(el.getAttribute('data-btn-id')==='bbp-reply-form') return true; var h=el.getAttribute('href'); if(h && h.indexOf('#new-post')!==-1) return true; }catch(e){} return false; }\n"
		. "function replaceEl(el){ try{ if(!el || (el.dataset && el.dataset.bzjDone)) return; if(!isQuickReply(el)) return; el.dataset.bzjDone='1'; el.setAttribute('href', CTA_URL); el.removeAttribute('data-btn-id'); el.removeAttribute('data-topic-id'); el.removeAttribute('data-topic-title'); el.removeAttribute('aria-expanded'); if(el.querySelector('.bp-screen-reader-text')){ var sr=el.querySelector('.bp-screen-reader-text'); sr.textContent = CTA_TEXT; var cc = el.querySelector('.comment-count'); if(cc) cc.textContent = CTA_TEXT; } else { el.textContent = CTA_TEXT; } el.classList.add('buzzjuice-reply-cta'); }catch(e){}\n }\n"
		. "function scan(){ var sels = ['a[data-btn-id=\"bbp-reply-form\"]','a.bbp-quick-reply','a.quick-reply','a.bp-quick-reply']; sels.forEach(function(sel){ document.querySelectorAll(sel).forEach(replaceEl); }); }\n"
		. "if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', scan); } else { scan(); }\n"
		. "var mo = new MutationObserver(scan); try{ mo.observe(document.body, {childList:true, subtree:true}); }catch(e){}; setTimeout(function(){ try{ mo.disconnect(); }catch(e){} }, 7000);\n"
		. "})();";

	wp_register_script( 'buzzjuice-quick-reply-fallback', '' );
	wp_enqueue_script( 'buzzjuice-quick-reply-fallback' );
	wp_add_inline_script( 'buzzjuice-quick-reply-fallback', $inline_js );

	// Minimal CTA styling
	$css = '.buzzjuice-reply-cta{background:#385dff;color:#fff;border-radius:18px;padding:8px 12px;text-decoration:none !important;display:inline-block !important;}';
	wp_add_inline_style( 'wp-block-library', $css );
}

/* ------------------------------------------------------------
 * 4) Server-side AJAX guard for the quick-reply endpoint
 *
 * The BuddyBoss integration registers a wp_ajax quick_reply_ajax handler
 * which returns the quick-reply form HTML. Block that endpoint for
 * non-subscribers to prevent the quick-reply form from being fetched.
 * ------------------------------------------------------------ */
add_action( 'admin_init', 'buzzjuice_block_quick_reply_ajax', 1 );

function buzzjuice_block_quick_reply_ajax() {
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';

	if ( 'quick_reply_ajax' !== $action ) {
		return;
	}

	// Allow subscribers and admins
	if ( buzzjuice_current_user_is_subscriber() || current_user_can( 'manage_options' ) ) {
		return;
	}

	$uid         = get_current_user_id();
	$user_obj    = $uid ? get_userdata( $uid ) : null;
	$topic_id    = isset( $_REQUEST['topic_id'] ) ? intval( wp_unslash( $_REQUEST['topic_id'] ) ) : ( isset( $_REQUEST['bbp_topic_id'] ) ? intval( wp_unslash( $_REQUEST['bbp_topic_id'] ) ) : 0 );
	$activity_id = isset( $_REQUEST['activity_id'] ) ? intval( wp_unslash( $_REQUEST['activity_id'] ) ) : 0;

	$payload = array(
		'message'      => __( 'Subscription required to use Quick Reply', 'buzzjuice' ),
		'user_id'      => $uid,
		'user_display' => $user_obj ? $user_obj->display_name : '',
		'topic_id'     => $topic_id,
		'activity_id'  => $activity_id,
	);

	// Return JSON error and stop processing — prevents the quick-reply HTML from being returned.
	wp_send_json_error( $payload, 403 );
}

/* End of plugin */