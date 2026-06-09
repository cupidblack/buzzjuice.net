<?php
/**
 * Plugin Name: BuzzJuice AffiliateWP Role Sync (MU)
 * Description: Makes the 'jewel_affiliate' WP role the single source of truth for AffiliateWP activation. Auto-registers/activates affiliates for users who have the role and automatically deactivates affiliates when the role is removed. Redirects logged-in users without the role away from AffiliateWP pages to the Jewel Affiliate product page.
 * Version: 2.1
 * Author: BuzzJuice (assistant)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ----------------------------
   Configuration
   ---------------------------- */

if ( ! defined( 'BZJ_AFFWP_ROLE' ) ) {
	define( 'BZJ_AFFWP_ROLE', 'jewel_affiliate' );
}

if ( ! defined( 'BZJ_AFFWP_PRODUCT_URL' ) ) {
	define( 'BZJ_AFFWP_PRODUCT_URL', 'https://buzzjuice.net/product/jewel-affiliate/' );
}

if ( ! defined( 'BZJ_AFFWP_USERMETA' ) ) {
	define( 'BZJ_AFFWP_USERMETA', 'bzj_affwp_affiliate_id' );
}

if ( ! defined( 'BZJ_AFFWP_MANAGED_FLAG' ) ) {
	define( 'BZJ_AFFWP_MANAGED_FLAG', '_bzj_affiliate_managed' );
}

if ( ! defined( 'BZJ_AFFWP_BATCH_SIZE' ) ) {
	define( 'BZJ_AFFWP_BATCH_SIZE', 200 );
}

if ( ! defined( 'BZJ_AFFWP_RECON_TRANSIENT' ) ) {
	define( 'BZJ_AFFWP_RECON_TRANSIENT', 'bzj_affwp_reconcile_lock' );
}

if ( ! defined( 'BZJ_AFFWP_CRON_HOOK' ) ) {
	define( 'BZJ_AFFWP_CRON_HOOK', 'bzj_affwp_reconcile' );
}

/* ----------------------------
   AffiliateWP compatibility helpers
   ---------------------------- */

/**
 * Is AffiliateWP available and has the core functions we need?
 *
 * @return bool
 */
function bzj_affwp_ready() {
	return function_exists( 'affwp_add_affiliate' ) && function_exists( 'affwp_get_affiliate' );
}

/**
 * Get affiliate id for a WP user. Prefer AffiliateWP API, keep a cached usermeta.
 *
 * @param int $user_id
 * @return int|false
 */
function bzj_affwp_get_affiliate_id( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return false;
	}

	// Try AffiliateWP API first if available
	if ( function_exists( 'affwp_get_affiliate_id' ) ) {
		$affiliate_id = affwp_get_affiliate_id( $user_id );
		if ( $affiliate_id ) {
			update_user_meta( $user_id, BZJ_AFFWP_USERMETA, (int) $affiliate_id );
			return (int) $affiliate_id;
		}
	}

	// Fallback to cached meta
	$cached = (int) get_user_meta( $user_id, BZJ_AFFWP_USERMETA, true );
	return $cached ? $cached : false;
}

/**
 * Clear cached affiliate id and managed flag for a user.
 *
 * @param int $user_id
 * @return void
 */
function bzj_affwp_clear_user_cache( $user_id ) {
	delete_user_meta( (int) $user_id, BZJ_AFFWP_USERMETA );
	delete_user_meta( (int) $user_id, BZJ_AFFWP_MANAGED_FLAG );
}

/**
 * Create affiliate for a given user and cache ID + mark managed.
 *
 * @param int $user_id
 * @return int|false
 */
function bzj_affwp_create_affiliate( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || ! function_exists( 'affwp_add_affiliate' ) ) {
		return false;
	}

	$existing = bzj_affwp_get_affiliate_id( $user_id );
	if ( $existing ) {
		return $existing;
	}

	$args = array(
		'user_id'             => $user_id,
		'status'              => 'active',
		'registration_method' => 'role_jewel_affiliate',
	);

	$result = affwp_add_affiliate( $args );

	if ( is_wp_error( $result ) || empty( $result ) ) {
		return false;
	}

	$affiliate_id = (int) $result;
	update_user_meta( $user_id, BZJ_AFFWP_USERMETA, $affiliate_id );
	update_user_meta( $user_id, BZJ_AFFWP_MANAGED_FLAG, 1 );

	return $affiliate_id;
}

/**
 * Set affiliate status with recursion protection.
 *
 * @param int    $affiliate_id
 * @param string $status
 * @return bool
 */
function bzj_affwp_set_affiliate_status( $affiliate_id, $status ) {
	static $in_progress = false;

	$affiliate_id = (int) $affiliate_id;
	if ( ! $affiliate_id || $in_progress ) {
		return false;
	}

	$in_progress = true;

	$done = false;
	if ( function_exists( 'affwp_set_affiliate_status' ) ) {
		$done = affwp_set_affiliate_status( $affiliate_id, $status );
	} elseif ( function_exists( 'affwp_update_affiliate' ) ) {
		$done = affwp_update_affiliate( array( 'affiliate_id' => $affiliate_id, 'status' => $status ) );
	}

	$in_progress = false;

	return (bool) $done;
}

/* ----------------------------
   Core sync/reconcile logic
   ---------------------------- */

/**
 * Ensure affiliate state matches WP role for a single user.
 *
 * @param int $user_id
 * @return void
 */
function bzj_affwp_sync_user_state( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || ! bzj_affwp_ready() ) {
		return;
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}

	$has_role = in_array( BZJ_AFFWP_ROLE, (array) $user->roles, true );
	$affiliate_id = bzj_affwp_get_affiliate_id( $user_id );

	if ( $has_role ) {
		// Create if missing
		if ( ! $affiliate_id ) {
			$affiliate_id = bzj_affwp_create_affiliate( $user_id );
			if ( ! $affiliate_id ) {
				return;
			}
		}
		// Ensure active
		bzj_affwp_set_affiliate_status( $affiliate_id, 'active' );
		return;
	}

	// No role -> ensure inactive if affiliate exists
	if ( $affiliate_id ) {
		bzj_affwp_set_affiliate_status( $affiliate_id, 'inactive' );
	}
}

/* ----------------------------
   Event hooks (fast-path)
   ---------------------------- */

add_action( 'user_register', 'bzj_affwp_sync_user_state', 20 );
add_action( 'profile_update', 'bzj_affwp_sync_user_state', 20 );

/**
 * set_user_role: ( $user_id, $role, $old_roles )
 */
add_action( 'set_user_role', function( $user_id, $role, $old_roles ) {
	// Sync the user's affiliate state whenever roles change
	bzj_affwp_sync_user_state( $user_id );
}, 20, 3 );

/**
 * remove_user_role: ( $user_id, $role )
 */
add_action( 'remove_user_role', function( $user_id, $role ) {
	// Only run sync if the jewel role was removed
	if ( BZJ_AFFWP_ROLE === $role ) {
		bzj_affwp_sync_user_state( $user_id );
	}
}, 20, 2 );

/**
 * Ensure sync occurs at login (catches role edits done via external flows)
 */
add_action( 'wp_login', function( $user_login, $user ) {
	if ( $user instanceof WP_User ) {
		bzj_affwp_sync_user_state( $user->ID );
	}
}, 20, 2 );

/* ----------------------------
   Prevent unauthorized manual reactivation
   ---------------------------- */

/**
 * When AffiliateWP sets a status, re-check and correct if needed.
 *
 * Runs after status change and will revert unauthorized activations.
 *
 * @param int $affiliate_id
 */
function bzj_affwp_enforce_activation_rules( $affiliate_id ) {
	if ( ! bzj_affwp_ready() || ! function_exists( 'affwp_get_affiliate' ) ) {
		return;
	}

	$affiliate = affwp_get_affiliate( $affiliate_id );
	if ( ! $affiliate || empty( $affiliate->user_id ) ) {
		return;
	}

	// Re-sync based on user's role - bzj_affwp_set_affiliate_status has recursion guard
	bzj_affwp_sync_user_state( (int) $affiliate->user_id );
}
add_action( 'affwp_set_affiliate_status', 'bzj_affwp_enforce_activation_rules', 50, 1 );

/* ----------------------------
   Affiliate area access protection
   ---------------------------- */

/**
 * Redirect logged-in users without the jewel_affiliate role away from AffiliateWP pages.
 */
function bzj_affwp_protect_affiliate_area() {

	// Only affect logged-in users (we don't interfere with guest flows)
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( ! bzj_affwp_ready() ) {
		return;
	}

	$user = wp_get_current_user();
	if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
		return;
	}

	// If they have the required role, allow
	if ( in_array( BZJ_AFFWP_ROLE, (array) $user->roles, true ) ) {
		return;
	}

	// Detect Affiliate Area: use affwp_is_affiliate_area when available
	$is_affiliate_area = false;
	if ( function_exists( 'affwp_is_affiliate_area' ) && affwp_is_affiliate_area() ) {
		$is_affiliate_area = true;
	}

	// Also detect if current post contains the affiliate_area shortcode (safer for some setups)
	if ( ! $is_affiliate_area && is_singular() ) {
		$post_content = get_post_field( 'post_content', get_the_ID() );
		if ( $post_content && has_shortcode( $post_content, 'affiliate_area' ) ) {
			$is_affiliate_area = true;
		}
	}

	if ( $is_affiliate_area ) {
		wp_safe_redirect( BZJ_AFFWP_PRODUCT_URL );
		exit;
	}
}
add_action( 'template_redirect', 'bzj_affwp_protect_affiliate_area', 0 );

/* ----------------------------
   Clear usermeta when AffiliateWP deletes an affiliate
   ---------------------------- */

add_action( 'affwp_delete_affiliate', function( $affiliate_id ) {
	if ( ! bzj_affwp_ready() ) {
		return;
	}

	if ( function_exists( 'affwp_get_affiliate' ) ) {
		$aff = affwp_get_affiliate( $affiliate_id );
		if ( $aff && ! empty( $aff->user_id ) ) {
			bzj_affwp_clear_user_cache( (int) $aff->user_id );
			return;
		}
	}

	// Fallback: clear any cached users that had this affiliate id
	$users = get_users( array(
		'meta_key'   => BZJ_AFFWP_USERMETA,
		'meta_value' => $affiliate_id,
		'fields'     => 'ID',
		'number'     => 50,
	) );
	if ( ! empty( $users ) ) {
		foreach ( $users as $uid ) {
			bzj_affwp_clear_user_cache( $uid );
		}
	}
}, 10, 1 );

/* ----------------------------
   Reconciliation / Backfill (batched)
   ---------------------------- */

/**
 * Decide whether reconciliation is allowed in the current context.
 *
 * @return bool
 */
function bzj_affwp_reconcile_allowed() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return true;
	}
	if ( is_admin() && current_user_can( 'manage_options' ) ) {
		return true;
	}
	return false;
}

/**
 * Batched reconciliation: scan users and sync affiliate state.
 *
 * Safe to call from admin_init (admin), cron, or WP-CLI.
 *
 * @return void
 */
function bzj_affwp_reconcile_all_users() {

	if ( ! bzj_affwp_reconcile_allowed() ) {
		return;
	}

	if ( get_transient( BZJ_AFFWP_RECON_TRANSIENT ) ) {
		return;
	}
	set_transient( BZJ_AFFWP_RECON_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS );

	if ( ! bzj_affwp_ready() ) {
		delete_transient( BZJ_AFFWP_RECON_TRANSIENT );
		return;
	}

	$batch = (int) BZJ_AFFWP_BATCH_SIZE;
	$page  = 1;

	while ( true ) {
		$user_query = new WP_User_Query( array(
			'number' => $batch,
			'paged'  => $page,
			'fields' => 'ID',
		) );

		$users = $user_query->get_results();

		if ( empty( $users ) ) {
			break;
		}

		foreach ( $users as $uid ) {
			bzj_affwp_sync_user_state( (int) $uid );
		}

		if ( count( $users ) < $batch ) {
			break;
		}

		$page++;
	}

	delete_transient( BZJ_AFFWP_RECON_TRANSIENT );
}

/**
 * Run reconciliation when an admin visits (safe & batched).
 */
add_action( 'admin_init', function() {
	bzj_affwp_reconcile_all_users();
}, 40 );

/**
 * Ensure a cron event exists for periodic reconciliation (only once).
 */
add_action( 'init', function() {
	if ( ! wp_next_scheduled( BZJ_AFFWP_CRON_HOOK ) ) {
		wp_schedule_event( time(), 'hourly', BZJ_AFFWP_CRON_HOOK );
	}
}, 1 );

/**
 * Cron handler.
 */
add_action( BZJ_AFFWP_CRON_HOOK, 'bzj_affwp_reconcile_all_users' );

/* ----------------------------
   WP-CLI: optional backfill command
   ---------------------------- */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	class BZJ_AffWP_CLI_Command {
		/**
		 * Run a full reconciliation pass (same as the admin backfill).
		 *
		 * Usage: wp bzj-affwp backfill
		 */
		public function backfill( $args, $assoc_args ) {
			if ( ! bzj_affwp_ready() ) {
				\WP_CLI::error( 'AffiliateWP functions not available. Activate AffiliateWP and try again.' );
				return;
			}
			\WP_CLI::log( 'Starting AffiliateWP role -> affiliate reconciliation...' );
			bzj_affwp_reconcile_all_users();
			\WP_CLI::success( 'Reconciliation completed.' );
		}
	}

	\WP_CLI::add_command( 'bzj-affwp', 'BZJ_AffWP_CLI_Command' );
}