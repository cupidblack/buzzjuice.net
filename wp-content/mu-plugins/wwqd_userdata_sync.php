<?php
/**
 * Unified WordPress to WoWonder and QuickDate user metadata sync
 *
 * This MU plugin automatically syncs WordPress user metadata and BuddyBoss xProfile data
 * to both WoWonder and QuickDate platforms whenever a user profile is updated.
 */

require_once __DIR__ . '/../../shared/wwqd_bridge.php';

// WordPress hooks for user metadata updates (lock-wrapped)
add_action('profile_update', function($user_id) {
    $user_id = (int)$user_id;
    if (function_exists('_wwqd_acquire_sync_lock')) {
        if (!_wwqd_acquire_sync_lock('WordPress', $user_id)) {
            // already in progress — skip to avoid recursion
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
}, 10, 1);

add_action('edit_user_profile_update', function($user_id) {
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
}, 10, 1);

add_action('personal_options_update', function($user_id) {
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
}, 10, 1);

// BuddyBoss xProfile updates
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

// Avatar updates specifically for QuickDate (keeps origin marker)
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
            do_platform_update(
                get_qd_db_conn(),
                QD_USERS_TABLE,
                'id',
                $qd_id,
                ['avatar' => normalize_avatar_url($avatar_url)],
                'QuickDate',
                'WordPress' // explicit origin
            );
        }
    } finally {
        if (function_exists('_wwqd_release_sync_lock')) {
            _wwqd_release_sync_lock('WordPress', $user_id);
        }
    }
}, 10, 1);