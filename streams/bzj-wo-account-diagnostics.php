<?php
/**
 * Buzzjuice WoWonder account diagnostic helper.
 *
 * File:
 *   streams/bzj-wo-account-diagnostics.php
 *
 * This file is diagnostic only. It does not repair or mutate accounts.
 * It intentionally avoids logging passwords, tokens, cookies or email values.
 */

if (!function_exists('bzj_wo_account_diagnostic_log')) {
    function bzj_wo_account_diagnostic_log($wo_user_id, $wp_user_id = 0, $reason = 'sso')
    {
        global $sqlConnect;

        $wo_user_id = (int) $wo_user_id;
        $wp_user_id = (int) $wp_user_id;

        if ($wo_user_id < 1 || empty($sqlConnect)) {
            return false;
        }

        $users_table     = defined('T_USERS') ? T_USERS : 'Wo_Users';
        $followers_table = defined('T_FOLLOWERS') ? T_FOLLOWERS : 'Wo_Followers';

        $columns = array(
            'user_id',
            'username',
            'active',
            'start_up',
            'startup_follow',
            'confirm_followers',
            'follow_privacy',
            'type',
            'admin',
            'verified',
            'is_pro',
            'joined',
            'lastseen',
            'wp_user_id'
        );

        $select_columns = implode(',', array_map(function ($column) {
            return '`' . $column . '`';
        }, $columns));

        $account = array();

        $query = mysqli_query(
            $sqlConnect,
            "SELECT {$select_columns}
             FROM {$users_table}
             WHERE `user_id` = {$wo_user_id}
             LIMIT 1"
        );

        if ($query && ($row = mysqli_fetch_assoc($query))) {
            foreach ($row as $key => $value) {
                if ($key === 'username') {
                    /* Username is useful diagnostically; email is deliberately
                     * never selected or logged here. */
                    $account[$key] = (string) $value;
                } elseif ($key === 'lastseen' || $key === 'joined') {
                    $account[$key] = is_numeric($value) ? (int) $value : $value;
                } else {
                    $account[$key] = is_numeric($value) ? (int) $value : $value;
                }
            }
        }

        $follow_counts = array(
            'outgoing_total'  => 0,
            'outgoing_active' => 0,
            'outgoing_pending'=> 0,
            'incoming_total'  => 0,
            'incoming_active' => 0,
            'incoming_pending'=> 0,
        );

        $count_query = mysqli_query(
            $sqlConnect,
            "SELECT
                SUM(CASE WHEN `follower_id` = {$wo_user_id} THEN 1 ELSE 0 END) AS outgoing_total,
                SUM(CASE WHEN `follower_id` = {$wo_user_id} AND `active` = 1 THEN 1 ELSE 0 END) AS outgoing_active,
                SUM(CASE WHEN `follower_id` = {$wo_user_id} AND `active` = 0 THEN 1 ELSE 0 END) AS outgoing_pending,
                SUM(CASE WHEN `following_id` = {$wo_user_id} THEN 1 ELSE 0 END) AS incoming_total,
                SUM(CASE WHEN `following_id` = {$wo_user_id} AND `active` = 1 THEN 1 ELSE 0 END) AS incoming_active,
                SUM(CASE WHEN `following_id` = {$wo_user_id} AND `active` = 0 THEN 1 ELSE 0 END) AS incoming_pending
             FROM {$followers_table}
             WHERE `follower_id` = {$wo_user_id}
                OR `following_id` = {$wo_user_id}"
        );

        if ($count_query && ($counts = mysqli_fetch_assoc($count_query))) {
            foreach ($follow_counts as $key => $unused) {
                $follow_counts[$key] = (int) ($counts[$key] ?? 0);
            }
        }

        $comparison = array();

        /* Compare only stable, relevant account attributes. Do not treat
         * confirm_followers/follow_privacy as defects because the application
         * now intentionally bypasses them for immediate follows. */
        $reference_query = mysqli_query(
            $sqlConnect,
            "SELECT
                `user_id`,
                `active`,
                `start_up`,
                `startup_follow`,
                `confirm_followers`,
                `follow_privacy`,
                `type`,
                `admin`,
                `verified`,
                `is_pro`,
                `wp_user_id`
             FROM {$users_table}
             WHERE `user_id` = 66
             LIMIT 1"
        );

        if ($reference_query && ($reference = mysqli_fetch_assoc($reference_query))) {
            foreach ($reference as $key => $value) {
                if ($key === 'user_id') {
                    continue;
                }

                $account_value = $account[$key] ?? null;
                $comparison[$key] = array(
                    'account'   => is_numeric($account_value) ? (int) $account_value : $account_value,
                    'reference' => is_numeric($value) ? (int) $value : $value,
                    'same'      => ((string) $account_value === (string) $value),
                );
            }
        }

        $payload = array(
            'reason'        => (string) $reason,
            'wo_user_id'    => $wo_user_id,
            'wp_user_id'    => $wp_user_id,
            'account'       => $account,
            'follow_counts' => $follow_counts,
            'reference_66'  => !empty($comparison) ? $comparison : null,
            'timestamp_utc' => gmdate('c'),
        );

        $dir = dirname(__DIR__) . '/data.logs/wo-account-diagnostics';

        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        $line = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($line === false) {
            return false;
        }

        return @file_put_contents(
            $dir . '/account-' . gmdate('Y-m-d') . '.log',
            $line . PHP_EOL,
            FILE_APPEND | LOCK_EX
        ) !== false;
    }
}
