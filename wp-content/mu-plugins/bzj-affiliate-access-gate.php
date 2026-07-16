<?php
/**
 * Plugin Name: BuzzJuice AffiliateWP Role Sync (MU)
 * Description: Makes the 'jewel_affiliate' WP role the single source of truth for AffiliateWP activation. Auto-registers/activates affiliates for users who have the role, automatically deactivates when the role is removed, and redirects unauthorized users away from the affiliate area. Prevents duplicate approval emails by preventing all unnecessary status updates and adding incremental reconciliation tracking.
 * Version: 4.0
 * Author: BuzzJuice (incremental reconciliation)
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

if ( ! defined( 'BZJ_AFFWP_LAST_SYNC_META' ) ) {
	define( 'BZJ_AFFWP_LAST_SYNC_META', '_bzj_affwp_last_sync_time' );
}

if ( ! defined( 'BZJ_AFFWP_BATCH_SIZE' ) ) {
	define( 'BZJ_AFFWP_BATCH_SIZE', 200 );
}

if ( ! defined( 'BZJ_AFFWP_CRON_HOOK' ) ) {
	define( 'BZJ_AFFWP_CRON_HOOK', 'bzj_affwp_reconcile' );
}

if ( ! defined( 'BZJ_AFFWP_DEBUG' ) ) {
	define( 'BZJ_AFFWP_DEBUG', false );
}

/* ----------------------------
   Logging Helper
   ---------------------------- */

function bzj_affwp_debug_log( $message ) {
	if ( ! BZJ_AFFWP_DEBUG ) {
		return;
	}
	error_log( '[BZJ_AFFWP] ' . $message );
}

/* ----------------------------
   AffiliateWP Compatibility Helpers
   ---------------------------- */

function bzj_affwp_ready() {
	return function_exists( 'affwp_add_affiliate' ) && function_exists( 'affwp_get_affiliate' );
}

/**
 * Get affiliate ID, using cached meta.
 */
function bzj_affwp_get_affiliate_id( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return false;
	}

	if ( function_exists( 'affwp_get_affiliate_id' ) ) {
		$affiliate_id = affwp_get_affiliate_id( $user_id );
		if ( $affiliate_id ) {
			update_user_meta( $user_id, BZJ_AFFWP_USERMETA, (int) $affiliate_id );
			return (int) $affiliate_id;
		}
	}

	$cached = (int) get_user_meta( $user_id, BZJ_AFFWP_USERMETA, true );
	return $cached ? $cached : false;
}

function bzj_affwp_clear_user_cache( $user_id ) {
	delete_user_meta( (int) $user_id, BZJ_AFFWP_USERMETA );
	delete_user_meta( (int) $user_id, BZJ_AFFWP_MANAGED_FLAG );
	delete_user_meta( (int) $user_id, BZJ_AFFWP_LAST_SYNC_META );
}

/**
 * Create affiliate for a user (only if it doesn't exist).
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
		bzj_affwp_debug_log( "User {$user_id}: Failed to create affiliate." );
		return false;
	}

	$affiliate_id = (int) $result;
	update_user_meta( $user_id, BZJ_AFFWP_USERMETA, $affiliate_id );
	update_user_meta( $user_id, BZJ_AFFWP_MANAGED_FLAG, 1 );
	bzj_affwp_debug_log( "User {$user_id}: Created new affiliate {$affiliate_id}." );

	return $affiliate_id;
}

/**
 * Set affiliate status ONLY if it differs from current status in the database.
 *
 * CRITICAL: We read the database first, compare, and ONLY call affwp_set_affiliate_status()
 * if the status actually differs. This prevents the affwp_set_affiliate_status action from
 * firing unnecessarily and triggering duplicate approval emails.
 */
function bzj_affwp_set_affiliate_status( $affiliate_id, $desired_status ) {
	static $in_progress = false;

	$affiliate_id = (int) $affiliate_id;
	if ( ! $affiliate_id || $in_progress ) {
		return false;
	}

	if ( ! bzj_affwp_ready() || ! function_exists( 'affwp_get_affiliate' ) ) {
		return false;
	}

	$affiliate = affwp_get_affiliate( $affiliate_id );
	if ( ! $affiliate ) {
		bzj_affwp_debug_log( "Affiliate {$affiliate_id}: Not found." );
		return false;
	}

	$current_status = $affiliate->status;
	$desired_status = strtolower( $desired_status );

	// CRITICAL: If status hasn't changed, exit early WITHOUT calling affwp_set_affiliate_status
	if ( strtolower( $current_status ) === $desired_status ) {
		bzj_affwp_debug_log( "Affiliate {$affiliate_id}: Status already '{$current_status}', no update needed." );
		return true;
	}

	// Status differs — only NOW do we call the AffiliateWP API
	bzj_affwp_debug_log( "Affiliate {$affiliate_id}: Status changing from '{$current_status}' to '{$desired_status}'." );

	$in_progress = true;

	$result = false;
	if ( function_exists( 'affwp_set_affiliate_status' ) ) {
		$result = affwp_set_affiliate_status( $affiliate_id, $desired_status );
	} elseif ( function_exists( 'affwp_update_affiliate' ) ) {
		$result = affwp_update_affiliate( array( 'affiliate_id' => $affiliate_id, 'status' => $desired_status ) );
	}

	$in_progress = false;

	return (bool) $result;
}

/* ----------------------------
   Core Synchronization
   
   The main function that syncs a user's affiliate status based on their roles.
   It is idempotent and safe to call repeatedly without side effects.
   
   This is the ONLY place where affiliate status changes originate.
   ---------------------------- */

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
		// Create affiliate if missing
		if ( ! $affiliate_id ) {
			$affiliate_id = bzj_affwp_create_affiliate( $user_id );
			if ( ! $affiliate_id ) {
				bzj_affwp_debug_log( "User {$user_id}: Has role but affiliate creation failed." );
				return;
			}
		}

		// Ensure active — only updates if status actually differs
		bzj_affwp_set_affiliate_status( $affiliate_id, 'active' );
		return;
	}

	// No role -> ensure inactive if affiliate exists
	if ( $affiliate_id ) {
		bzj_affwp_set_affiliate_status( $affiliate_id, 'inactive' );
	}
}

/* ----------------------------
   Event Hooks (Minimal & Focused)
   
   ONLY role-change hooks are attached. Profile edits, login, and status changes
   don't affect affiliate eligibility—only role changes do.
   
   REMOVED:
   - profile_update: User profile changes don't affect affiliate status
   - wp_login: Redundant; hourly reconciliation handles drift
   - affwp_set_affiliate_status feedback loop: Eliminates cascade of calls
   
   KEPT:
   - user_register: New user registration
   - set_user_role: Any role is added (covers jewel_affiliate being added)
   - remove_user_role: Specific role is removed (covers jewel_affiliate being removed)
   ---------------------------- */

add_action( 'user_register', 'bzj_affwp_sync_user_state', 20 );

add_action( 'set_user_role', function( $user_id, $role, $old_roles ) {
	bzj_affwp_debug_log( "Event: set_user_role for User {$user_id}, role '{$role}'." );
	bzj_affwp_sync_user_state( $user_id );
}, 20, 3 );

add_action( 'remove_user_role', function( $user_id, $role ) {
	if ( BZJ_AFFWP_ROLE === $role ) {
		bzj_affwp_debug_log( "Event: remove_user_role for User {$user_id}, role '{$role}'." );
		bzj_affwp_sync_user_state( $user_id );
	}
}, 20, 2 );

/* ----------------------------
   Affiliate Area Access Protection
   
   Redirect logged-in users without the jewel_affiliate role to the product page
   instead of showing the "Your affiliate account is not active" message.
   ---------------------------- */

function bzj_affwp_protect_affiliate_area() {
	if ( ! is_user_logged_in() || ! bzj_affwp_ready() ) {
		return;
	}

	$user = wp_get_current_user();
	if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
		return;
	}

	if ( in_array( BZJ_AFFWP_ROLE, (array) $user->roles, true ) ) {
		return;
	}

	$is_affiliate_area = false;
	if ( function_exists( 'affwp_is_affiliate_area' ) && affwp_is_affiliate_area() ) {
		$is_affiliate_area = true;
	}

	if ( ! $is_affiliate_area && is_singular() ) {
		$post_id = get_the_ID();
		if ( $post_id ) {
			$post_content = get_post_field( 'post_content', $post_id );
			if ( $post_content && has_shortcode( $post_content, 'affiliate_area' ) ) {
				$is_affiliate_area = true;
			}
		}
	}

	if ( $is_affiliate_area ) {
		bzj_affwp_debug_log( "Access Block: Redirecting User {$user->ID} from Affiliate Area (no role)." );
		wp_safe_redirect( BZJ_AFFWP_PRODUCT_URL );
		exit;
	}
}
add_action( 'template_redirect', 'bzj_affwp_protect_affiliate_area', 0 );

/* ----------------------------
   Clear Cache on Affiliate Deletion
   ---------------------------- */

add_action( 'affwp_delete_affiliate', function( $affiliate_id ) {
	if ( ! bzj_affwp_ready() ) {
		return;
	}

	if ( function_exists( 'affwp_get_affiliate' ) ) {
		$aff = affwp_get_affiliate( $affiliate_id );
		if ( $aff && ! empty( $aff->user_id ) ) {
			bzj_affwp_clear_user_cache( (int) $aff->user_id );
			bzj_affwp_debug_log( "Affiliate {$affiliate_id}: Deleted, cleared cache for User {$aff->user_id}." );
			return;
		}
	}

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
		bzj_affwp_debug_log( "Affiliate {$affiliate_id}: Cleared cache for " . count( $users ) . ' user(s).' );
	}
}, 10, 1 );

/* ----------------------------
   Incremental Reconciliation
   
   This is the key improvement in v4.0: Instead of reconciling ALL users every time,
   we only reconcile users whose role state may have changed since the last sync.
   
   Each user stores a "last sync" timestamp. We only resync if:
   1. The timestamp doesn't exist (first run)
   2. The timestamp is old (re-reconcile periodically to catch drift)
   
   This reduces unnecessary work and prevents duplicate emails.
   
   @since 4.0
   ---------------------------- */

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
 * Incremental reconciliation: Only resync users if their state may have changed.
 *
 * Strategy:
 * 1. Scan users with jewel_affiliate role and check if we've synced them recently. If not, sync.
 * 2. Scan users with existing affiliates and check if we've synced them recently. If not, sync.
 *
 * This dramatically reduces the work compared to full reconciliation and prevents
 * unnecessary status updates from triggering duplicate emails.
 *
 * @since 4.0
 */
function bzj_affwp_reconcile_all_users() {
	if ( ! bzj_affwp_reconcile_allowed() ) {
		bzj_affwp_debug_log( 'Reconciliation not allowed in this context.' );
		return;
	}

	if ( ! bzj_affwp_ready() ) {
		bzj_affwp_debug_log( 'AffiliateWP not ready, skipping reconciliation.' );
		return;
	}

	bzj_affwp_debug_log( 'Starting incremental reconciliation pass.' );

	$batch = (int) BZJ_AFFWP_BATCH_SIZE;
	$page  = 1;
	$now   = time();
	// Re-reconcile users every 24 hours even if their role hasn't changed (drift detection)
	$sync_interval = 24 * HOUR_IN_SECONDS;

	// Step 1: Scan users with jewel_affiliate role
	bzj_affwp_debug_log( 'Step 1: Scanning users with jewel_affiliate role.' );
	$step1_synced = 0;

	while ( true ) {
		$user_query = new WP_User_Query( array(
			'role'   => BZJ_AFFWP_ROLE,
			'number' => $batch,
			'paged'  => $page,
			'fields' => 'ID',
		) );

		$users = $user_query->get_results();
		if ( empty( $users ) ) {
			break;
		}

		foreach ( $users as $uid ) {
			$last_sync = (int) get_user_meta( $uid, BZJ_AFFWP_LAST_SYNC_META, true );

			// Sync if: never synced OR last sync was > 24 hours ago
			if ( ! $last_sync || ( $now - $last_sync > $sync_interval ) ) {
				bzj_affwp_sync_user_state( $uid );
				update_user_meta( $uid, BZJ_AFFWP_LAST_SYNC_META, $now );
				$step1_synced++;
			}
		}

		if ( count( $users ) < $batch ) {
			break;
		}

		$page++;
	}

	bzj_affwp_debug_log( "Step 1 complete: Synced {$step1_synced} users with jewel_affiliate role." );

	// Step 2: Scan users with existing affiliates (in case role was removed but sync hasn't occurred)
	if ( function_exists( 'affwp_get_affiliates' ) ) {
		bzj_affwp_debug_log( 'Step 2: Scanning users with existing affiliates.' );
		$page = 1;
		$step2_synced = 0;

		while ( true ) {
			$affiliates = affwp_get_affiliates( array(
				'number' => $batch,
				'offset' => ( $page - 1 ) * $batch,
			) );

			if ( empty( $affiliates ) ) {
				break;
			}

			foreach ( $affiliates as $affiliate ) {
				if ( ! empty( $affiliate->user_id ) ) {
					$uid = (int) $affiliate->user_id;
					$last_sync = (int) get_user_meta( $uid, BZJ_AFFWP_LAST_SYNC_META, true );

					// Sync if: never synced OR last sync was > 24 hours ago
					if ( ! $last_sync || ( $now - $last_sync > $sync_interval ) ) {
						bzj_affwp_sync_user_state( $uid );
						update_user_meta( $uid, BZJ_AFFWP_LAST_SYNC_META, $now );
						$step2_synced++;
					}
				}
			}

			if ( count( $affiliates ) < $batch ) {
				break;
			}

			$page++;
		}

		bzj_affwp_debug_log( "Step 2 complete: Synced {$step2_synced} users with existing affiliates." );
	}

	bzj_affwp_debug_log( "Reconciliation complete." );
}

add_action( 'admin_init', function() {
	bzj_affwp_reconcile_all_users();
}, 40 );

add_action( 'init', function() {
	if ( ! wp_next_scheduled( BZJ_AFFWP_CRON_HOOK ) ) {
		wp_schedule_event( time(), 'hourly', BZJ_AFFWP_CRON_HOOK );
		bzj_affwp_debug_log( 'Scheduled hourly reconciliation cron.' );
	}
}, 1 );

add_action( BZJ_AFFWP_CRON_HOOK, 'bzj_affwp_reconcile_all_users' );

/* ----------------------------
   WP-CLI Commands
   ---------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	class BZJ_AffWP_CLI_Command {
		public function backfill( $args, $assoc_args ) {
			if ( ! bzj_affwp_ready() ) {
				\WP_CLI::error( 'AffiliateWP functions not available.' );
				return;
			}
			\WP_CLI::log( 'Starting AffiliateWP role -> affiliate reconciliation...' );
			bzj_affwp_reconcile_all_users();
			\WP_CLI::success( 'Reconciliation completed.' );
		}
	}

	\WP_CLI::add_command( 'bzj-affwp', 'BZJ_AffWP_CLI_Command' );
}