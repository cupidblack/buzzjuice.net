<?php
// shared/wwqd_sync_queue.php
// File-based deferred profile sync queue (lightweight)

if (!defined('WWQD_SYNC_QUEUE_FILE')) {
    define('WWQD_SYNC_QUEUE_FILE', __DIR__ . '/wwqd_sync_queue.json');
}

if (!function_exists('wwqd_queue_profile_sync')) {
    function wwqd_queue_profile_sync(string $source, int $user_id, array $update_data): bool {
        $queue = [];
        if (file_exists(WWQD_SYNC_QUEUE_FILE)) {
            $json = @file_get_contents(WWQD_SYNC_QUEUE_FILE);
            $queue = $json ? @json_decode($json, true) : [];
            if (!is_array($queue)) $queue = [];
        }
        $queue[] = [
            'source' => $source,
            'user_id' => $user_id,
            'data' => $update_data,
            'ts' => time()
        ];
        $w = @file_put_contents(WWQD_SYNC_QUEUE_FILE, json_encode($queue, JSON_PRETTY_PRINT), LOCK_EX);
        if ($w === false) {
            if (function_exists('wwqd_debug')) wwqd_debug('wwqd_queue_profile_sync: write_failed', ['file'=>WWQD_SYNC_QUEUE_FILE]);
            return false;
        }
        if (function_exists('wwqd_debug')) wwqd_debug('wwqd_queue_profile_sync: queued', ['source'=>$source,'user'=>$user_id,'fields'=>array_keys($update_data)]);
        return true;
    }
}

if (!function_exists('wwqd_drain_sync_queue')) {
    function wwqd_drain_sync_queue(int $max = 50): void {
        if (!file_exists(WWQD_SYNC_QUEUE_FILE)) return;
        $json = @file_get_contents(WWQD_SYNC_QUEUE_FILE);
        $queue = $json ? @json_decode($json, true) : [];
        if (!is_array($queue) || empty($queue)) {
            @unlink(WWQD_SYNC_QUEUE_FILE);
            return;
        }

        $processed = [];
        foreach ($queue as $i => $job) {
            if ($i >= $max) break;
            $source = $job['source'] ?? '';
            $uid = isset($job['user_id']) ? intval($job['user_id']) : 0;
            $data = isset($job['data']) && is_array($job['data']) ? $job['data'] : [];

            if ($uid <= 0 || empty($data)) {
                $processed[] = $job;
                continue;
            }

            // Dispatch: prefer WP sync function if available
            if (function_exists('sync_wp_user_to_platforms') && $source === 'WordPress') {
                try {
                    sync_wp_user_to_platforms($uid, 'metadata');
                } catch (Throwable $e) {
                    if (function_exists('wwqd_debug')) wwqd_debug('wwqd_drain_sync_queue: sync_wp_user_to_platforms threw', ['ex'=>$e->getMessage(),'user'=>$uid]);
                }
            } elseif (function_exists('Wo_UpdateUserData') && ($source === 'WoWonder' || $source === 'QuickDate')) {
                try {
                    // If Wo_UpdateUserData exists, call with provided data
                    Wo_UpdateUserData($uid, $data);
                } catch (Throwable $e) {
                    if (function_exists('wwqd_debug')) wwqd_debug('wwqd_drain_sync_queue: Wo_UpdateUserData threw', ['ex'=>$e->getMessage(),'user'=>$uid]);
                }
            } else {
                // Fallback: nothing to call; skip
                if (function_exists('wwqd_debug')) wwqd_debug('wwqd_drain_sync_queue: no handler for job', ['job'=>$job]);
            }

            $processed[] = $job;
        }

        // Remove processed prefix from queue
        if (count($processed) >= count($queue)) {
            @unlink(WWQD_SYNC_QUEUE_FILE);
        } else {
            // remove processed items
            $remaining = array_slice($queue, count($processed));
            @file_put_contents(WWQD_SYNC_QUEUE_FILE, json_encode($remaining, JSON_PRETTY_PRINT), LOCK_EX);
        }
    }
}