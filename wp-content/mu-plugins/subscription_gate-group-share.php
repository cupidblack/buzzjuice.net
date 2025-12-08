<?php
/*
Plugin Name: Buzzjuice — Group Share Gate (MU)
Description: Replace group composer with a CTA for non-subscribers and limit non-subscribers to 1 group post per rolling week. Tight REST/AJAX guards only for group activity post attempts.
Author: Buzzjuice
Version: 1.0
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require subscription helpers provided elsewhere in your codebase.
 * The user's request specified: use require_once ABSPATH 'shared/subscription_gate_helpers.php'.
 */
if ( file_exists( ABSPATH . 'shared/subscription_gate_helpers.php' ) ) {
	require_once ABSPATH . 'shared/subscription_gate_helpers.php';
} else {
	// Fallback shim only if the helper file is absent so plugin remains functional in dev environments.
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
 * Weekly quota helpers (1 post per rolling week for non-subscribers)
 * Keys: _bzj_group_post_count, _bzj_group_post_reset
 * -------------------------------------------------------------------------- */
function bzj_get_weekly_group_posts_count( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return 0;
	}

	$now   = time();
	$reset = (int) get_user_meta( $user_id, '_bzj_group_post_reset', true );
	$count = (int) get_user_meta( $user_id, '_bzj_group_post_count', true );

	// If reset time passed, clear and return 0
	if ( $reset && $now >= $reset ) {
		delete_user_meta( $user_id, '_bzj_group_post_count' );
		delete_user_meta( $user_id, '_bzj_group_post_reset' );
		return 0;
	}

	return max( 0, $count );
}

function bzj_increment_weekly_group_posts( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return;
	}

	$count = bzj_get_weekly_group_posts_count( $user_id );
	$count++;
	update_user_meta( $user_id, '_bzj_group_post_count', $count );

	// If no reset set, set the reset to 1 week from now (rolling window).
	if ( ! get_user_meta( $user_id, '_bzj_group_post_reset', true ) ) {
		update_user_meta( $user_id, '_bzj_group_post_reset', strtotime( '+1 week' ) );
	}
}

function bzj_get_weekly_group_posts_remaining( $user_id ) {
	$limit = 1;
	$count = bzj_get_weekly_group_posts_count( $user_id );
	return max( 0, ( $limit - $count ) );
}

/* --------------------------------------------------------------------------
 * Frontend: render CTA + quota notice on group pages for non-subscribers
 * This removes the group's "Share something with the group..." composer (group context only)
 * and replaces it with a CTA box and quota notice.
 * -------------------------------------------------------------------------- */
function bzj_render_group_cta_and_notice() {
	// Only run on frontend group pages
	if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) {
		return;
	}

	$uid = get_current_user_id();

	// Allow subscribers & site admins to see normal composer
	if ( bzj_user_has_subscription_role( $uid ) || user_can( $uid, 'manage_options' ) ) {
		return;
	}

	$cta_url   = home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' );
	$cta_text  = 'Tap here to purchase a subscription to Share something with the group';
	$remaining = bzj_get_weekly_group_posts_remaining( $uid );
	$limit     = 1;
	$notice_text = sprintf( /* translators: X/Y posts left */ __( 'You have %d/%d group posts left this week', 'buzzjuice' ), $remaining, $limit );

	?>
	<style>
	.bzj-subscription-cta-wrap{margin:0 0 18px;font-family:inherit}
	.bzj-quota-notice{margin:0 0 8px;padding:10px;border-radius:6px;background:#fffbe6;border:1px solid #ffebcd;color:#333;font-weight:600}
	.bzj-subscription-cta-link{display:block;padding:14px 16px;border-radius:8px;background:#fff7ed;border:2px dashed #ff8c00;text-align:center;font-weight:700;color:#111;text-decoration:none}
	.bzj-subscription-cta-link:hover{text-decoration:underline}
	</style>

	<div class="bzj-subscription-cta-wrap" aria-live="polite">
<!--		<div class="bzj-quota-notice"><?php /* echo esc_html( $notice_text ); */ ?></div> -->
		<a href="<?php echo esc_url( $cta_url ); ?>" class="bzj-subscription-cta-link" title="<?php echo esc_attr( $cta_text ); ?>">
			<?php echo esc_html( $cta_text ); ?>
		</a>
	</div>

	<script>
	(function(){
		// Composer selectors tailored to BuddyBoss/BuddyPress variations (includes selectors for bp-nouveau and common composer themes)
		var selectors = [
			'.groups form#whats-new-form',
			'.groups .activity-post-form',
			'.groups .bp-activity-form',
			'.bp-nouveau .activity-form',
			'.bb-stream .composer',
			'.component-activity .activity-post-form',
			'.activity .activity-form',
			'.groups [id*="whats-new"]',
			'.bb-activity-form',
			'.bp-nouveau-activity .composer',
			'.buddyboss-activity .composer',
			'.activity .composer',
			'.bb-composer'
		];

		function removeComposers(){
			selectors.forEach(function(sel){
				document.querySelectorAll(sel).forEach(function(el){
					try {
						// Extra guard: ensure element is inside a group context before removing
						var insideGroup = !!( el.closest && ( el.closest('.groups') || el.closest('#group-body') || el.closest('.bp-group-box') || document.body.classList.contains('bp-group') ) );
						if ( insideGroup ) {
							el.remove();
						}
					} catch(e){}
				});
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', removeComposers);
		} else {
			removeComposers();
		}
	})();
	</script>
	<?php
}
add_action( 'bp_before_activity_post_form', 'bzj_render_group_cta_and_notice', 5 );
//add_action( 'bp_after_group_home_content', 'bzj_render_group_cta_and_notice', 5 );

/* --------------------------------------------------------------------------
 * Server-side: prevent saving group activity when quota exhausted.
 * Hook to bp_activity_before_save and return false to prevent save.
 * Only enforce when activity component === 'groups'.
 * -------------------------------------------------------------------------- */
function bzj_prevent_group_activity_save( $activity ) {
	$component = '';
	$user_id   = get_current_user_id();
	$item_id   = 0;

	if ( is_object( $activity ) ) {
		$component = isset( $activity->component ) ? $activity->component : '';
		$user_id   = isset( $activity->user_id ) ? (int) $activity->user_id : $user_id;
		$item_id   = isset( $activity->item_id ) ? (int) $activity->item_id : 0;
	} elseif ( is_array( $activity ) ) {
		$component = isset( $activity['component'] ) ? $activity['component'] : '';
		$user_id   = isset( $activity['user_id'] ) ? (int) $activity['user_id'] : $user_id;
		$item_id   = isset( $activity['item_id'] ) ? (int) $activity['item_id'] : 0;
	} else {
		return $activity;
	}

	// Only enforce for groups component
	if ( 'groups' !== $component ) {
		return $activity;
	}

	// Allow subscribers & admins
	if ( bzj_user_has_subscription_role( $user_id ) || user_can( $user_id, 'manage_options' ) ) {
		return $activity;
	}

	// If no remaining posts, prevent save
	if ( bzj_get_weekly_group_posts_remaining( $user_id ) <= 0 ) {
		return false;
	}

	// When saved, increment the user's weekly count.
	add_action( 'bp_activity_after_save', function( $saved_activity ) use ( $user_id ) {
		try {
			if ( isset( $saved_activity->component ) && 'groups' === $saved_activity->component ) {
				if ( isset( $saved_activity->user_id ) && (int) $saved_activity->user_id === (int) $user_id ) {
					bzj_increment_weekly_group_posts( $user_id );
				}
			}
		} catch ( Exception $e ) {}
	}, 20, 1 );

	return $activity;
}
add_filter( 'bp_activity_before_save', 'bzj_prevent_group_activity_save', 10 );

/* --------------------------------------------------------------------------
 * REST protection: only enforce for POST attempts that include component=groups.
 * Return a WP_Error with structured 'data' including user display + group info.
 * -------------------------------------------------------------------------- */
function bzj_rest_pre_dispatch_protect( $result, $server, $request ) {
	// $request may be null for some hooks
	if ( ! $request || ! method_exists( $request, 'get_param' ) ) {
		return $result;
	}

	// Only enforce on POST (activity creation)
	if ( 'POST' !== strtoupper( $request->get_method() ) ) {
		return $result;
	}

	$component = $request->get_param( 'component' );
	$item_id   = (int) $request->get_param( 'item_id' );
	$uid       = get_current_user_id();

	// Only enforce groups activity posts
	if ( 'groups' !== $component ) {
		return $result;
	}

	// Allow subscribers & admins
	if ( bzj_user_has_subscription_role( $uid ) || user_can( $uid, 'manage_options' ) ) {
		return $result;
	}

	// If exhausted, return detailed WP_Error (status 403) and include user display name + group info in data
	if ( bzj_get_weekly_group_posts_remaining( $uid ) <= 0 ) {
		$user = get_userdata( $uid );
		$group_name = '';
		if ( $item_id && function_exists( 'groups_get_group' ) ) {
			$g = groups_get_group( array( 'group_id' => $item_id ) );
			if ( is_object( $g ) && isset( $g->name ) ) {
				$group_name = $g->name;
			}
		}

		$data = array(
			'user_id'      => $uid,
			'user_display' => $user ? $user->display_name : '',
			'group_id'     => $item_id,
			'group_name'   => $group_name,
		);

		return new WP_Error(
			'bzj_weekly_group_limit',
			__( 'Weekly group post limit reached', 'buzzjuice' ),
			array(
				'status' => 403,
				'data'   => $data,
			)
		);
	}

	return $result;
}
add_filter( 'rest_pre_dispatch', 'bzj_rest_pre_dispatch_protect', 10, 3 );

/* --------------------------------------------------------------------------
 * AJAX protection (admin-ajax) — narrowed so we only block activity POSTs.
 * Enforce when:
 *  - DOING_AJAX is true
 *  - action === 'post_update' OR a valid post_update nonce is supplied
 *  - component === 'groups'
 * -------------------------------------------------------------------------- */
function bzj_ajax_protect() {
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	$action    = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
	$component = isset( $_REQUEST['component'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['component'] ) ) : '';
	$uid       = get_current_user_id();

	// Determine whether this AJAX call is an activity-post attempt:
	// - action === 'post_update' (BuddyPress), OR
	// - presence and validity of the post_update nonce field
	$has_valid_post_update_nonce = false;

	if ( ! empty( $_REQUEST['_wpnonce_post_update'] ) ) {
		$nonce = wp_unslash( $_REQUEST['_wpnonce_post_update'] );
		$has_valid_post_update_nonce = wp_verify_nonce( $nonce, 'post_update' );
	} elseif ( ! empty( $_REQUEST['nonce'] ) ) {
		$nonce = wp_unslash( $_REQUEST['nonce'] );
		$has_valid_post_update_nonce = wp_verify_nonce( $nonce, 'post_update' );
	}

	$is_activity_post = ( 'post_update' === $action ) || $has_valid_post_update_nonce;

	// If this request is not obviously an activity POST, bail early.
	if ( ! $is_activity_post ) {
		return;
	}

	// Now restrict to groups component only
	if ( 'groups' !== $component ) {
		return;
	}

	// Allow subscribers & admins
	if ( bzj_user_has_subscription_role( $uid ) || user_can( $uid, 'manage_options' ) ) {
		return;
	}

	// If exhausted, return JSON error and die (only for this AJAX post attempt)
	if ( bzj_get_weekly_group_posts_remaining( $uid ) <= 0 ) {
		$user = get_userdata( $uid );
		$payload = array(
			'message'      => __( 'Weekly group post limit reached', 'buzzjuice' ),
			'user_id'      => $uid,
			'user_display' => $user ? $user->display_name : '',
		);

		// 403 to match REST behavior
		wp_send_json_error( $payload, 403 );
	}
}
// Use admin_init early so we can intercept AJAX attempts before the BP/post handler accepts them.
add_action( 'admin_init', 'bzj_ajax_protect', 1 );

/* --------------------------------------------------------------------------
 * Localize data for front-end JS (quota, CTA). Only on group pages and logged-in users.
 * -------------------------------------------------------------------------- */
function bzj_localize_quota_data() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( function_exists( 'bp_is_group' ) && ! bp_is_group() ) {
		return;
	}

	$uid = get_current_user_id();

	// Register/enqueue an empty handle and localize onto it (no external JS file required).
	wp_register_script( 'bzj-group-cta', '', array(), '1.0', true );
	wp_enqueue_script( 'bzj-group-cta' );

	$data = array(
		'remaining' => bzj_get_weekly_group_posts_remaining( $uid ),
		'limit'     => 1,
		'user_id'   => $uid,
		'cta_url'   => home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' ),
		'cta_text'  => 'Tap here to purchase a subscription to Share something with the group',
	);

	wp_localize_script( 'bzj-group-cta', 'bzjGroupCtaData', $data );
}
add_action( 'wp_enqueue_scripts', 'bzj_localize_quota_data' );

/* --------------------------------------------------------------------------
 * Helper: programmatic check for templates
 * -------------------------------------------------------------------------- */
function bzj_user_can_post_group( $user_id = null ) {
	if ( is_null( $user_id ) ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}
	if ( bzj_user_has_subscription_role( $user_id ) || user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	return ( bzj_get_weekly_group_posts_remaining( $user_id ) > 0 );
}

/* End of plugin */
?>