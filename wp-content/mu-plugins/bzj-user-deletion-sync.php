<?php
/*
Plugin Name: BZJ User Deletion Sync
Description: Delete linked WoWonder and QuickDate accounts and all relational/user records on WP user deletion (including files), using the mapping in usermeta.
Author: BuzzJuice DevOps
Version: 2.0
*/

if (!defined('ABSPATH')) exit;

// === Setup ===
define('BZJ_WOWONDER_SCHEMA', 'koware_buzzjuice_streams');
define('BZJ_QUICKDATE_SCHEMA', 'koware_buzzjuice_social');
define('BZJ_HELPER_PATH', ABSPATH . 'shared/db_helpers.php');

// Load DB helpers for cross-system connections
require_once BZJ_HELPER_PATH;

// === Register hook on user deletion (by admin or user) ===
add_action('delete_user', 'bzj_run_linked_account_deletion', 1, 1);

function bzj_run_linked_account_deletion($wp_user_id) {
    if (!$wp_user_id) return;

    // Prevent double-execution (idempotency)
    if (get_user_meta($wp_user_id, '_bzj_user_deletion_in_progress', true)) return;
    update_user_meta($wp_user_id, '_bzj_user_deletion_in_progress', time());

    // 1. Get linked WoWonder and QuickDate user IDs from usermeta
    $wo_user_id = (int)get_user_meta($wp_user_id, 'wo_user_id', true);
    $qd_user_id = (int)get_user_meta($wp_user_id, 'qd_user_id', true);

    bzj_log_user_delete("==== [BZJ] START user deletion for WP:{$wp_user_id} WoW:{$wo_user_id} QD:{$qd_user_id} ====");

    // 2. Perform deletion on each system and log/report results
    $result = [
        'wowonder'  => $wo_user_id > 0 ? bzj_nuke_entire_user('wowonder', $wo_user_id) : 'no_linked_account',
        'quickdate' => $qd_user_id > 0 ? bzj_nuke_entire_user('quickdate', $qd_user_id) : 'no_linked_account',
        'time'      => time()
    ];
    update_user_meta($wp_user_id, '_bzj_user_deletion_result', $result);

    bzj_log_user_delete("==== [BZJ] COMPLETE user deletion for WP:{$wp_user_id} ====");
}

// === MAIN orchestration function for each system ===
// $system: 'wowonder' or 'quickdate', $user_id: native user id in that db
function bzj_nuke_entire_user($system, $user_id) {
    $user_id = (int)$user_id;
    if ($user_id < 1) return 'invalid_id';

    // System params
    if ($system === 'wowonder') {
        $db     = function_exists('get_wowonder_db') ? get_wowonder_db() : null;
        $schema = WOWONDER_DB_NAME;
        $core_table = 'Wo_Users';
        $core_col   = 'user_id';
        $file_dir   = ABSPATH . 'streams/upload/';
    } elseif ($system === 'quickdate') {
        $db     = function_exists('get_qd_db_conn') ? get_qd_db_conn() : null;
        $schema = QD_DB_NAME;
        $core_table = 'users';
        $core_col   = 'id';
        $file_dir   = ABSPATH . 'social/upload/';
    } else {
        return 'unknown_system';
    }

    if (!$db) return 'db_connection_failed';

    // 1. Dynamic clean-up (for all tables/columns referencing user)
    $dyn_report = bzj_dynamic_user_cleanup($db, $schema, $user_id, $core_table);

    // 2. Remove main user entry last (avoids dangling FKs if any)
    $core_sql = "DELETE FROM `$core_table` WHERE `$core_col` = '{$user_id}' LIMIT 1";
    bzj_log_user_delete("[$system][CORE] $core_sql");
    @mysqli_query($db, $core_sql);

    // 3. Remove associated files, if any
    bzj_cleanup_user_files($file_dir, $user_id);

    // 4. Done!
    return [
        'dynamic_cleanup' => $dyn_report,
        'core_delete'     => mysqli_affected_rows($db) > 0 ? 'deleted' : 'core_user_missing',
        'files_cleaned'   => true
    ];
}

// Dynamically finds all tables/columns referencing user_id and deletes
function bzj_dynamic_user_cleanup($db, $schema, $user_id, $exclude_table = '') {
    $relevant_cols = [
        'user_id', 'from_id', 'to_id', 'follower_id', 'following_id', 'muted_id',
        'blocked_user_id', 'liked_user_id', 'visited_user_id', 'matched_user_id',
        'notifier_id', 'blocked', 'blocker', 'recipient_id', 'admin_id'
    ];
    $column_list = "'" . implode("','", $relevant_cols) . "'";
    $map_sql = "
        SELECT TABLE_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA='{$schema}'
        AND COLUMN_NAME IN ($column_list)
    ";

    $mapResult = mysqli_query($db, $map_sql);
    if (!$mapResult) return ['error' => mysqli_error($db)];

    $del_count = 0;
    while ($row = mysqli_fetch_assoc($mapResult)) {
        $table = $row['TABLE_NAME'];
        $col   = $row['COLUMN_NAME'];
        if ($table === $exclude_table) continue; // Leave core for last
        // Only delete if at least one record exists
        $chk = mysqli_query($db, "SELECT 1 FROM `$table` WHERE `$col`='{$user_id}' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $sql = "DELETE FROM `$table` WHERE `$col`='{$user_id}'";
            bzj_log_user_delete("[DYNAMIC][$schema][$table] $sql");
            @mysqli_query($db, $sql);
            $del_count += mysqli_affected_rows($db);
        }
    }
    return ['total_deleted_rows' => $del_count];
}

// Remove uploaded media/files: directory-based (can refine pattern as needed)
function bzj_cleanup_user_files($base_dir, $user_id) {
    if (!is_dir($base_dir)) return;
    foreach (glob($base_dir . "*{$user_id}*") as $item) {
        if (is_file($item)) @unlink($item);
        if (is_dir($item)) @rmdir($item);
    }
}

// To WP debug log (includes [BZJ] prefix)
function bzj_log_user_delete($msg) {
    if (function_exists('error_log')) error_log('[BZJ] ' . $msg);
}