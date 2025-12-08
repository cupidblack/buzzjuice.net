<?php
/**
 * Shared subscription helpers for WoWonder / QuickDate.
 * Location: /shared/subscription-check.php (already placed per your note).
 *
 * Responsibilities:
 * - Detect if a WoWonder user is pro/admin/owner.
 * - Provide WP-backed soft-limit counters (stored in wp_usermeta via WordPress DB).
 * - Small helper to produce JSON 403 responses with upgrade_url.
 *
 * Assumptions:
 * - shared/db_helpers.php and shared/wwqd_bridge.php exist and expose:
 *     - get_wp_db_conn()
 *     - get_wowonder_db()
 *     - wp_get_usermeta(...)
 *     - wp_update_usermeta(...)
 *     - ww_get_user_id_by_email($email)
 *
 * If those are not available in your environment, include/adjust accordingly.
 */

if (!defined('BZ_SUBSCRIPTION_HELPERS_LOADED')) {

    define('BZ_SUBSCRIPTION_HELPERS_LOADED', true);

    // try to load common bridge/helpers if not already loaded
    if (file_exists(__DIR__ . '/wwqd_bridge.php')) {
        require_once __DIR__ . '/wwqd_bridge.php';
    } elseif (file_exists(__DIR__ . '/db_helpers.php')) {
        require_once __DIR__ . '/db_helpers.php';
    }

    /**
     * Return WoWonder DB connection (from shared helpers) or create a mysqli connection.
     */
    function bz_get_wowonder_db() {
        if (function_exists('get_wowonder_db')) {
            return get_wowonder_db();
        }
        // fallback: try to create using env - rare since db_helpers should exist
        return false;
    }

    /**
     * Map WoWonder user_id -> WordPress user_id by matching email.
     * Returns WP user ID (int) or false.
     */
    function bz_get_wp_user_id_by_wowonder_id($wow_user_id) {
        $wow_db = bz_get_wowonder_db();
        if (!$wow_db) return false;
        $wow_user_id = intval($wow_user_id);
        if ($wow_user_id <= 0) return false;

        $sql = "SELECT email FROM Wo_Users WHERE user_id = {$wow_user_id} LIMIT 1";
        $res = $wow_db->query($sql);
        if (!$res || $res->num_rows == 0) return false;
        $row = $res->fetch_assoc();
        $email = $row['email'] ?? '';
        if (empty($email)) return false;

        // Use function from wwqd_bridge if available
        if (function_exists('wp_get_user_by_login_or_email')) {
            // This returns assoc WP user row; we want ID. Use that helper.
            $wp_row = wp_get_user_by_login_or_email(get_wp_db_conn(), $email);
            if ($wp_row && isset($wp_row['ID'])) return intval($wp_row['ID']);
        }

        // Fallback: query WP DB directly
        if (function_exists('get_wp_db_conn')) {
            $wp = get_wp_db_conn();
            if ($wp) {
                $email_esc = $wp->real_escape_string($email);
                $q = "SELECT ID FROM `" . (defined('WP_DB_NAME') ? WP_DB_NAME . '`.`' : '') . (defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : '') . "users` WHERE user_email = '{$email_esc}' LIMIT 1";
                $r = $wp->query($q);
                if ($r && $r->num_rows) {
                    $rw = $r->fetch_assoc();
                    return intval($rw['ID']);
                }
            }
        }
        return false;
    }

    /**
     * Check whether a WoWonder user is premium (pro) or site owner/admin.
     * Accepts either WoWonder $wo['user'] array (preferred) or numeric wow_user_id.
     */
    function bz_is_premium($wow_user_or_id = null) {
        global $wo;
        // If passing WoWonder user array object
        if (is_array($wow_user_or_id) && isset($wow_user_or_id['is_pro'])) {
            // Respect existing config flag too when present
            if (!empty($wow_user_or_id['is_pro']) && intval($wow_user_or_id['is_pro']) === 1) {
                if (isset($wo['config']['pro']) && intval($wo['config']['pro']) === 1) {
                    return true;
                }
                // If config pro flag missing, still respect user is_pro
                return true;
            }
            if (!empty($wow_user_or_id['admin']) && intval($wow_user_or_id['admin']) === 1) return true;
            if (!empty($wow_user_or_id['id']) && intval($wow_user_or_id['id']) === 1) return true;
            return false;
        }

        // If called without parameter, use global $wo['user']
        if ($wow_user_or_id === null && isset($wo['user']) && is_array($wo['user'])) {
            return bz_is_premium($wo['user']);
        }

        // If numeric id passed, fetch user row from WoWonder DB
        if (is_numeric($wow_user_or_id)) {
            $wow_db = bz_get_wowonder_db();
            if (!$wow_db) return false;
            $uid = intval($wow_user_or_id);
            $q = "SELECT * FROM Wo_Users WHERE user_id = {$uid} LIMIT 1";
            $r = $wow_db->query($q);
            if ($r && $r->num_rows) {
                $row = $r->fetch_assoc();
                if (!empty($row['is_pro']) && intval($row['is_pro']) === 1) return true;
                if (!empty($row['admin']) && intval($row['admin']) === 1) return true;
                if (!empty($row['user_id']) && intval($row['user_id']) === 1) return true;
            }
        }
        return false;
    }

    /**
     * Helpers to respond with JSON 403 premium required
     */
    function bz_json_premium_required($message = 'Premium required', $upgrade_url = '/index.php?link1=go-pro') {
        header('Content-Type: application/json; charset=utf-8', true, 403);
        echo json_encode([
            'status' => 403,
            'error' => 'premium_required',
            'message' => $message,
            'upgrade_url' => $upgrade_url
        ]);
        exit;
    }

    /* ----------------------------
     * Soft-limit counters (user-meta based, stored in WP usermeta)
     * Meta keys: bz_limits_<limit_name>_count and bz_limits_<limit_name>_date
     * e.g., bz_limits_messages_count, bz_limits_messages_date
     *
     * We store per-day counters. On first increment each day, the date resets to today and count starts at 0.
     * All functions accept a WoWonder user id.
     * ---------------------------- */

    /**
     * Return WP user id for a given WoWonder user id (or false)
     */
    function bz_get_wp_user_id($wow_user_id) {
        return bz_get_wp_user_id_by_wowonder_id($wow_user_id);
    }

    /**
     * Get today's date string in Y-m-d
     */
    function bz_today() {
        return gmdate('Y-m-d');
    }

    /**
     * Get current count for limit (int)
     */
    function bz_get_limit_count($wow_user_id, $limit_name) {
        $wp_user_id = bz_get_wp_user_id($wow_user_id);
        if (!$wp_user_id) return 0;
        // Use bridge helper if available
        if (function_exists('wp_get_usermeta')) {
            $count = wp_get_usermeta($wp_user_id, 'bz_limits_' . $limit_name . '_count');
            $date  = wp_get_usermeta($wp_user_id, 'bz_limits_' . $limit_name . '_date');
        } elseif (function_exists('get_wp_db_conn')) {
            // fallback, direct SQL
            $wp = get_wp_db_conn();
            $k1 = 'bz_limits_' . $limit_name . '_count';
            $k2 = 'bz_limits_' . $limit_name . '_date';
            $k1e = $wp->real_escape_string($k1);
            $k2e = $wp->real_escape_string($k2);
            $uid = intval($wp_user_id);
            $q = "SELECT meta_key, meta_value FROM " . wp_table('usermeta') . " WHERE user_id = {$uid} AND meta_key IN ('$k1e','$k2e')";
            $r = $wp->query($q);
            $count = 0; $date = '';
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    if ($row['meta_key'] === $k1) $count = intval($row['meta_value']);
                    if ($row['meta_key'] === $k2) $date  = $row['meta_value'];
                }
            }
        } else {
            return 0;
        }

        // If date is not today, treat count as 0
        if (empty($date) || $date !== bz_today()) return 0;
        return intval($count);
    }

    /**
     * Increment a limit by $n (default 1). Returns new count.
     */
    function bz_increment_limit($wow_user_id, $limit_name, $n = 1) {
        $wp_user_id = bz_get_wp_user_id($wow_user_id);
        if (!$wp_user_id) return false;
        $meta_count_key = 'bz_limits_' . $limit_name . '_count';
        $meta_date_key  = 'bz_limits_' . $limit_name . '_date';
        $today = bz_today();

        // Use bridge helpers if available for compatibility
        if (function_exists('wp_get_usermeta') && function_exists('wp_update_usermeta')) {
            $current_date = wp_get_usermeta($wp_user_id, $meta_date_key) ?: '';
            $current_count = 0;
            if ($current_date === $today) {
                $current_count = intval(wp_get_usermeta($wp_user_id, $meta_count_key) ?: 0);
            } else {
                $current_count = 0;
            }
            $new_count = $current_count + intval($n);
            // Update both meta keys
            wp_update_usermeta($wp_user_id, $meta_count_key, $new_count);
            wp_update_usermeta($wp_user_id, $meta_date_key, $today);
            return intval($new_count);
        }

        // Direct SQL fallback
        if (function_exists('get_wp_db_conn')) {
            $wp = get_wp_db_conn();
            $uid = intval($wp_user_id);
            $meta_count = $wp->real_escape_string($meta_count_key);
            $meta_date  = $wp->real_escape_string($meta_date_key);
            // Get existing
            $q = "SELECT meta_key,meta_value FROM " . wp_table('usermeta') . " WHERE user_id = {$uid} AND meta_key IN ('{$meta_count}','{$meta_date}')";
            $r = $wp->query($q);
            $current_date = ''; $current_count = 0;
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    if ($row['meta_key'] === $meta_count_key) $current_count = intval($row['meta_value']);
                    if ($row['meta_key'] === $meta_date_key) $current_date = $row['meta_value'];
                }
            }
            if ($current_date !== $today) $current_count = 0;
            $new_count = $current_count + intval($n);

            // Upsert meta: count
            $count_esc = $wp->real_escape_string($new_count);
            $check = "SELECT umeta_id FROM " . wp_table('usermeta') . " WHERE user_id = {$uid} AND meta_key = '{$meta_count}' LIMIT 1";
            $cres = $wp->query($check);
            if ($cres && $cres->num_rows) {
                $row = $cres->fetch_assoc();
                $umid = intval($row['umeta_id']);
                $wp->query("UPDATE " . wp_table('usermeta') . " SET meta_value='{$count_esc}' WHERE umeta_id = {$umid}");
            } else {
                $wp->query("INSERT INTO " . wp_table('usermeta') . " (user_id, meta_key, meta_value) VALUES ({$uid}, '{$meta_count}', '{$count_esc}')");
            }
            // Upsert date
            $date_esc = $wp->real_escape_string($today);
            $check2 = "SELECT umeta_id FROM " . wp_table('usermeta') . " WHERE user_id = {$uid} AND meta_key = '{$meta_date}' LIMIT 1";
            $r2 = $wp->query($check2);
            if ($r2 && $r2->num_rows) {
                $row = $r2->fetch_assoc();
                $wp->query("UPDATE " . wp_table('usermeta') . " SET meta_value='{$date_esc}' WHERE umeta_id = {$row['umeta_id']}");
            } else {
                $wp->query("INSERT INTO " . wp_table('usermeta') . " (user_id, meta_key, meta_value) VALUES ({$uid}, '{$meta_date}', '{$date_esc}')");
            }
            return intval($new_count);
        }

        return false;
    }

    /**
     * Reset a limit (set count=0 and date to today).
     */
    function bz_reset_limit($wow_user_id, $limit_name) {
        $wp_user_id = bz_get_wp_user_id($wow_user_id);
        if (!$wp_user_id) return false;
        $meta_count_key = 'bz_limits_' . $limit_name . '_count';
        $meta_date_key  = 'bz_limits_' . $limit_name . '_date';
        if (function_exists('wp_update_usermeta')) {
            wp_update_usermeta($wp_user_id, $meta_count_key, 0);
            wp_update_usermeta($wp_user_id, $meta_date_key, bz_today());
            return true;
        }
        if (function_exists('get_wp_db_conn')) {
            $wp = get_wp_db_conn();
            $uid = intval($wp_user_id);
            $meta_count = $wp->real_escape_string($meta_count_key);
            $meta_date  = $wp->real_escape_string($meta_date_key);
            $today = $wp->real_escape_string(bz_today());
            $check = "SELECT umeta_id FROM " . wp_table('usermeta') . " WHERE user_id = {$uid} AND meta_key IN ('{$meta_count}','{$meta_date}')";
            $r = $wp->query($check);
            // simple upsert operations
            $wp->query("INSERT INTO " . wp_table('usermeta') . " (user_id, meta_key, meta_value) VALUES ({$uid}, '{$meta_count}', '0') ON DUPLICATE KEY UPDATE meta_value='0'");
            $wp->query("INSERT INTO " . wp_table('usermeta') . " (user_id, meta_key, meta_value) VALUES ({$uid}, '{$meta_date}', '{$today}') ON DUPLICATE KEY UPDATE meta_value='{$today}'");
            return true;
        }
        return false;
    }

    /**
     * Check against a configured limit and return boolean if allowed.
     * $limit_name: e.g., 'messages' or 'likes'
     * $limit_max: integer
     */
    function bz_check_and_increment_limit_or_block($wow_user_id, $limit_name, $limit_max, $increment = 1) {
        // Premium users bypass limits
        if (bz_is_premium($wow_user_id)) return true;

        // Current count
        $current = bz_get_limit_count($wow_user_id, $limit_name);
        if ($current + $increment > intval($limit_max)) {
            return false;
        }
        // increment
        bz_increment_limit($wow_user_id, $limit_name, $increment);
        return true;
    }

    /**
     * Convenience wrapper for generating an upgrade response when blocking an action.
     */
    function bz_block_with_upgrade_json($message = null) {
        $msg = $message ?: 'Feature available to Pro subscribers. Upgrade to continue.';
        $upgrade = '/index.php?link1=go-pro';
        bz_json_premium_required($msg, $upgrade);
    }

} // end include guard