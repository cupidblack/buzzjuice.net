<?php
/**
 * Unified WordPress to WoWonder and QuickDate user metadata sync
 * Lock-wrapped and whitelist-safe
 *
 * Notes:
 * - Requires shared/wwqd_bridge.php with sync_wp_user_to_platforms() above.
 * - Does NOT forward passwords or session fields.
 */

require_once __DIR__ . '/../../shared/wwqd_bridge.php';

// Generic lock-wrapped callback for profile updates
$wwqd_profile_save_cb = function($user_id) {
    $user_id = (int)$user_id;
    if (function_exists('_wwqd_acquire_sync_lock')) {
        if (!_wwqd_acquire_sync_lock('WordPress', $user_id)) {
            return;
        }
    }
    try {
        sync_wp_user_to_platforms($user_id, 'metadata');
    } finally {
        if (function_exists('_wwqd_release_sync_lock')) {
            _wwqd_release_sync_lock('WordPress', $user_id);
        }
    }
};

add_action('profile_update', $wwqd_profile_save_cb, 10, 1);
add_action('edit_user_profile_update', $wwqd_profile_save_cb, 10, 1);
add_action('personal_options_update', $wwqd_profile_save_cb, 10, 1);

// BuddyBoss xProfile updates (lock-wrapped)
add_action('xprofile_data_after_save', function($data) {
    $user_id = is_object($data) ? (int)$data->user_id : (int)$data;
    if (function_exists('_wwqd_acquire_sync_lock')) {
        if (!_wwqd_acquire_sync_lock('WordPress', $user_id)) return;
    }
    try {
        sync_wp_user_to_platforms($user_id, 'xprofile');
    } finally {
        if (function_exists('_wwqd_release_sync_lock')) {
            _wwqd_release_sync_lock('WordPress', $user_id);
        }
    }
}, 10, 1);

// Avatar uploaded -> notify QuickDate (origin=WordPress)
add_action('bp_core_avatar_uploaded', function($user_id) {
    $user_id = (int)$user_id;
    $user = get_userdata($user_id);
    if (!$user) return;

    if (function_exists('_wwqd_acquire_sync_lock')) {
        if (!_wwqd_acquire_sync_lock('WordPress', $user_id)) return;
    }
    try {
        $qd_id = function_exists('get_quickdate_id_by_email') ? get_quickdate_id_by_email($user->user_email) : null;
        if (!$qd_id) return;

        $avatar_url = function_exists('bp_core_fetch_avatar') ? bp_core_fetch_avatar([
            'item_id' => $user_id,
            'object'  => 'user',
            'type'    => 'full',
            'html'    => false
        ]) : '';

        if ($avatar_url) {
            $avatar = function_exists('normalize_avatar_url') ? normalize_avatar_url($avatar_url) : $avatar_url;
            do_platform_update(
                get_qd_db_conn(),
                defined('QD_USERS_TABLE') ? QD_USERS_TABLE : 'users',
                'id',
                $qd_id,
                ['avatar' => $avatar],
                'QuickDate',
                'WordPress'
            );
        }
    } finally {
        if (function_exists('_wwqd_release_sync_lock')) _wwqd_release_sync_lock('WordPress', $user_id);
    }
}, 10, 1);