<?php
/**
 * Unified WoWonder <-> QuickDate <-> WordPress bridge
 *
 * This file merges functionality from:
 *  - requests/wp_user_bridge.php (QuickDate <> WordPress)
 *  - assets/wp_user_bridge.php      (WoWonder <> WordPress)
 *
 * Place this file in the WordPress root "shared" folder together with:
 *  - shared/db_helpers.php        (moved from data/db_helpers.php)
 *  - shared/DotEnv.php            (moved from data/DotEnv.php)
 *  - shared/buzz_metadata.json    (moved from data/user_field_metadata.json)
 *
 * Goals:
 *  - Keep original function names where possible (backwards compatibility).
 *  - Reduce redundancy by centralizing DB connection helpers and utilities.
 *  - Provide a simple authenticated HTTP endpoint interface for other platforms
 *    (QuickDate / WoWonder) to request sync actions using BUZZ_SSO_SECRET.
 *
 * Authentication / SSO:
 *  - The endpoint expects a BUZZ_SSO_SECRET value (from .env loaded in db_helpers).
 *  - Provide the secret via POST param `sso_secret` OR Authorization: Bearer <secret>.
 *
 * Usage examples (HTTP):
 *  - Single QuickDate user update (QuickDate -> WordPress/WoWonder):
 *      POST /shared/wwqd_bridge.php?action=sync_quickdate_to_wordpress
 *      Headers: Authorization: Bearer <BUZZ_SSO_SECRET>
 *      Body (JSON):
 *        { "quickdate_user": { "email":"joe@example.com", "username":"joe", "first_name":"Joe", ... } }
 *
 *  - Bulk QuickDate user updates:
 *      POST /shared/wwqd_bridge.php?action=bulk_sync_quickdate
 *      Body (JSON):
 *        { "users": [ { ... }, { ... } ] }
 *
 * NOTE: db_helpers.php (and DotEnv) should be present in same shared folder. db_helpers
 *       will load DotEnv and constants like WP_DB_HOST, QUICKDATE_DB_HOST, WOWONDER_DB_HOST, BUZZ_SSO_SECRET.
 */

/* --------------------------------------------------------------------------
 * Unified logging function for all platforms
 * -------------------------------------------------------------------------- */
if (!function_exists('log_sync_debug')) {
    function log_sync_debug($message, $platform = 'General') {
        $log_file = __DIR__ . '/../shared/wwqd_bridge_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp][$platform] $message\n", 3, $log_file);
    }
}

if (!defined('WP_BRIDGE_DEBUG_LOG')) {
    define('WP_BRIDGE_DEBUG_LOG', __DIR__ . '/wwqd_bridge_ww_func1.log');
}
function wp_log($msg) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $msg\n", 3, WP_BRIDGE_DEBUG_LOG);
}

/* --------------------------------------------------------------------------
 * Basic includes
 * -------------------------------------------------------------------------- */

if (!file_exists(__DIR__ . '/db_helpers.php')) {
    // Friendly error - missing shared helpers
    error_log("[wwqd_bridge] Missing shared/db_helpers.php. Please add the file.");
    return;
}
require_once __DIR__ . '/db_helpers.php'; // This will also load DotEnv.php (as before)

/*
if (!file_exists(__DIR__ . '/../wp-includes/class-phpass.php')) {
    // Friendly error - missing shared helpers
    error_log("[class-phpass] Missing /../wp-includes/class-phpass.php. Please add the file.");
    return;
}
require_once __DIR__ . '/../wp-includes/class-phpass.php'; // This will also load DotEnv.php (as before)
*/
/* --------------------------------------------------------------------------
 * Utilities and compatibility wrappers
 * -------------------------------------------------------------------------- */

/**
 * Return WordPress mysqli connection (alias, backward compatible).
 * Original projects used get_wp_db() / get_wp_db_conn(); keep both.
 */
if (!function_exists('get_wp_db_conn')) {
    function get_wp_db_conn() {
        // db_helpers provides this function already; call it if exists
        if (function_exists('get_wp_db_conn')) {
            return \get_wp_db_conn();
        }
        // Fallback: create new connection from constants
        static $conn = null;
        if ($conn) return $conn;
        if (!defined('WP_DB_HOST')) return false;
        $conn = new mysqli(WP_DB_HOST, WP_DB_USER, WP_DB_PASS, WP_DB_NAME);
        if ($conn->connect_errno) return false;
        $conn->set_charset('utf8mb4');
        return $conn;
    }
}

if (!function_exists('get_wp_db')) {
    function get_wp_db() {
        // Some older code expects a mysqli object in $wpDb global
        // Use get_wp_db_conn() and return same
        return get_wp_db_conn();
    }
}

/**
 * QuickDate DB connection (keeps get_qd_db_conn name used across code)
 */
if (!function_exists('get_qd_db_conn')) {
    function get_qd_db_conn() {
        // db_helpers likely already defines it; call existing if present
        if (function_exists('get_qd_db_conn')) {
            return \get_qd_db_conn();
        }
        // else build from constants
        static $conn = null;
        if ($conn) return $conn;
        if (!defined('QD_DB_HOST')) return false;
        $conn = new mysqli(QD_DB_HOST, QD_DB_USER, QD_DB_PASS, QD_DB_NAME);
        if ($conn->connect_errno) return false;
        $conn->set_charset('utf8mb4');
        return $conn;
    }
}

/**
 * WoWonder DB connection (keeps get_wowonder_db name used across code)
 */
if (!function_exists('get_wowonder_db')) {
    function get_wowonder_db() {
        if (function_exists('get_wowonder_db')) {
            return \get_wowonder_db();
        }
        static $conn = null;
        if ($conn) return $conn;
        if (!defined('WOWONDER_DB_HOST')) return false;
        $conn = new mysqli(WOWONDER_DB_HOST, WOWONDER_DB_USER, WOWONDER_DB_PASS, WOWONDER_DB_NAME);
        if ($conn->connect_errno) return false;
        $conn->set_charset('utf8mb4');
        return $conn;
    }
}

/* Serialization helpers (kept for compatibility) */
if (!function_exists('is_serialized')) {
    function is_serialized($data, $strict = true) {
        if (!is_string($data)) return false;
        $data = trim($data);
        if ('N;' === $data) return true;
        if (strlen($data) < 4) return false;
        if (':' !== $data[1]) return false;
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) return false;
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) return false;
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
                break;
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $rest = substr($data, 2);
                return (bool) preg_match("/^([0-9.E-]+);$/", $rest);
        }
        return false;
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($original) {
        if (is_serialized($original)) return @unserialize($original);
        return $original;
    }
}

/* Table helpers (compatibility) */
if (!function_exists('wp_table')) {
    function wp_table($table) {
        if (defined('WP_TABLE_PREFIX') && defined('WP_DB_NAME')) {
            return '`' . WP_DB_NAME . '`.`' . WP_TABLE_PREFIX . $table . '`';
        }
        return '`' . $table . '`';
    }
}
if (!function_exists('bp_table')) {
    function bp_table($table) {
        if (defined('BP_TABLE_PREFIX') && defined('WP_DB_NAME')) {
            return '`' . WP_DB_NAME . '`.`' . BP_TABLE_PREFIX . $table . '`';
        }
        return '`' . $table . '`';
    }
}

/* --------------------------------------------------------------------------
 * Table column helpers (merged from mu-plugins)
 * -------------------------------------------------------------------------- */
if (!function_exists('get_table_columns')) {
    function get_table_columns($conn, $table, $platform = 'General') {
        static $cache = [];
        $cache_key = "$platform:$table";
        if (isset($cache[$cache_key])) return $cache[$cache_key];

        // Support table names with optional db prefix (db.table) and quote parts safely.
        $table = trim($table);
        $columns = [];
        try {
            if (strpos($table, '.') !== false) {
                $parts = explode('.', $table);
                $quoted = [];
                foreach ($parts as $p) {
                    $quoted[] = '`' . $conn->real_escape_string(trim($p, '`')) . '`';
                }
                $table_query = implode('.', $quoted);
            } else {
                $table_query = '`' . $conn->real_escape_string(trim($table, '`')) . '`';
            }

            // Prefer to catch mysqli exceptions; also suppress warnings for older setups
            $result = @ $conn->query("SHOW COLUMNS FROM {$table_query}");
            if (!$result) {
                // Log and return empty list when table is missing or inaccessible
                $err = isset($conn->error) ? $conn->error : 'unknown error';
                log_sync_debug("Failed to fetch columns for {$table_query}: {$err}", $platform);
                $cache[$cache_key] = [];
                return [];
            }

            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
        } catch (Exception $e) {
            log_sync_debug("Exception fetching columns for {$table}: " . $e->getMessage(), $platform);
            $cache[$cache_key] = [];
            return [];
        }

        $cache[$cache_key] = $columns;
        return $columns;
    }
}

/* --------------------------------------------------------------------------
 * WWQD: additional helpers (locking, field maps, schema decisions)
 * These are part of the proposed integration to make updates origin-aware.
 * -------------------------------------------------------------------------- */

if (!function_exists('_wwqd_acquire_sync_lock')) {
    function _wwqd_acquire_sync_lock(string $origin, int $user_id = 0, int $ttl = 10): bool {
        $key  = md5($origin . ':' . $user_id);
        $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "wwqd_sync_{$key}.lock";

        if (file_exists($file)) {
            $data = @json_decode(@file_get_contents($file), true);
            $ts = isset($data['ts']) ? intval($data['ts']) : 0;
            if ($ts + $ttl > time()) {
                return false;
            }
            @unlink($file);
        }

        $payload = json_encode([
            'origin' => $origin,
            'user'   => $user_id,
            'ts'     => time()
        ]);
        @file_put_contents($file, $payload, LOCK_EX);
        return file_exists($file);
    }
}

if (!function_exists('_wwqd_release_sync_lock')) {
    function _wwqd_release_sync_lock(string $origin, int $user_id = 0): void {
        $key  = md5($origin . ':' . $user_id);
        $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "wwqd_sync_{$key}.lock";
        if (file_exists($file)) @unlink($file);
    }
}

if (!function_exists('_wwqd_field_maps')) {
    function _wwqd_field_maps(): array {
        static $maps = null;
        if ($maps !== null) return $maps;

        $maps = [
            'public_open_fields'    => [],
            'private_secure_fields' => []
        ];

        $candidates = [
            __DIR__ . '/buzz_metadata.json',
            dirname(__DIR__) . '/data/user_field_metadata.json',
            dirname(__DIR__, 2) . '/data/user_field_metadata.json'
        ];

        foreach ($candidates as $p) {
            if (!file_exists($p)) continue;
            $json = @file_get_contents($p);
            if (!$json) continue;
            $d = @json_decode($json, true);
            if (!is_array($d)) continue;
            $maps['public_open_fields']    = isset($d['public_open_fields']) && is_array($d['public_open_fields']) ? $d['public_open_fields'] : $maps['public_open_fields'];
            $maps['private_secure_fields'] = isset($d['private_secure_fields']) && is_array($d['private_secure_fields']) ? $d['private_secure_fields'] : $maps['private_secure_fields'];
            break;
        }

        return $maps;
    }
}

/**
 * Conditional debug logger for WWQD bridge.
 * Enable by defining WWQD_DEBUG = true (e.g., in .env/db_helpers) or env WWQD_DEBUG=1
 */
if (!function_exists('wwqd_debug')) {
    /**
     * Conditional debug logger for WWQD bridge.
     * Accepts a message and optional context (string or array). Will no-op unless WWQD_DEBUG is set.
     */
    function wwqd_debug(string $msg, $ctx = null) {
        $enabled = false;
        if (defined('WWQD_DEBUG') && WWQD_DEBUG) $enabled = true;
        if (getenv('WWQD_DEBUG')) $enabled = true;
        if (!$enabled) return;
        $file = defined('WWQD_DEBUG_log') ? WWQD_DEBUG_log : (__DIR__ . '/wwqd_debug.log');
        $ts = date('Y-m-d H:i:s');
        if (is_array($ctx) || is_object($ctx)) {
            $ctx_s = json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif (is_scalar($ctx)) {
            $ctx_s = (string)$ctx;
        } else {
            $ctx_s = '';
        }
        $prefix = $ctx_s !== '' ? "[$ctx_s] " : '';
        error_log("[$ts] {$prefix}{$msg}\n", 3, $file);
    }
}

/* --------------------------------------------------------------------------
 * WWQD: Login-phase helpers + durable profile-sync queue
 * - Prevents destructive writes during SSO rehydration/login
 * - Simple file-based queue for deferred profile sync jobs
 * -------------------------------------------------------------------------- */
if (!function_exists('wwqd_enter_login_phase')) {
    function wwqd_enter_login_phase(int $wp_user_id, int $ttlSeconds = 15) {
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wwqd_login';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'login_' . intval($wp_user_id);
        $payload = json_encode(['ts' => time(), 'ttl' => intval($ttlSeconds)]);
        @file_put_contents($file, $payload, LOCK_EX);
    }
}

if (!function_exists('wwqd_exit_login_phase')) {
    function wwqd_exit_login_phase(int $wp_user_id) {
        $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wwqd_login' . DIRECTORY_SEPARATOR . 'login_' . intval($wp_user_id);
        if (file_exists($file)) @unlink($file);
    }
}

if (!function_exists('wwqd_in_login_phase')) {
    function wwqd_in_login_phase(int $wp_user_id): bool {
        $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wwqd_login' . DIRECTORY_SEPARATOR . 'login_' . intval($wp_user_id);
        if (!file_exists($file)) return false;
        $contents = @file_get_contents($file);
        if (!$contents) { @unlink($file); return false; }
        $d = @json_decode($contents, true);
        if (!is_array($d) || !isset($d['ts']) || !isset($d['ttl'])) { @unlink($file); return false; }
        $ts = intval($d['ts']); $ttl = intval($d['ttl']);
        if ($ts + $ttl < time()) { @unlink($file); return false; }
        return true;
    }
}

if (!function_exists('wwqd_queue_profile_sync')) {
    function wwqd_queue_profile_sync(string $origin, int $user_id, array $data) {
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wwqd_sync_queue';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $job = [
            'origin' => $origin,
            'user'   => intval($user_id),
            'data'   => $data,
            'ts'     => time()
        ];
        $name = $dir . DIRECTORY_SEPARATOR . uniqid('job_', true) . '.json';
        @file_put_contents($name, json_encode($job), LOCK_EX);
        wwqd_debug('queued_profile_sync', ['file' => $name, 'origin' => $origin, 'user' => $user_id, 'keys' => array_keys($data)]);
        return true;
    }
}

if (!function_exists('wwqd_drain_sync_queue')) {
    function wwqd_drain_sync_queue(int $max = 25) {
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wwqd_sync_queue';
        if (!is_dir($dir)) return;
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
        $count = 0;
        foreach ($files as $f) {
            if ($count >= $max) break;
            $raw = @file_get_contents($f);
            if (!$raw) { @unlink($f); continue; }
            $job = @json_decode($raw, true);
            if (!is_array($job) || empty($job['user']) || empty($job['data'])) { @unlink($f); continue; }
            $uid = intval($job['user']);
            // Skip if login-phase for that user (defer)
            if (function_exists('wwqd_in_login_phase') && wwqd_in_login_phase($uid)) {
                wwqd_debug('drain_skipped_login_phase', ['file'=>$f,'user'=>$uid]);
                continue;
            }
            try {
                if (!empty($job['origin']) && $job['origin'] === 'WordPress') {
                    if (function_exists('sync_wp_user_to_platforms')) {
                        sync_wp_user_to_platforms($uid, 'deferred');
                    }
                } else {
                    if (function_exists('Wo_UpdateUserData')) {
                        Wo_UpdateUserData($uid, $job['data']);
                    }
                }
            } catch (Throwable $e) {
                wwqd_debug('drain_job_exception', ['file'=>$f, 'error'=>$e->getMessage()]);
            }
            @unlink($f);
            $count++;
        }
    }
}

if (!function_exists('wwqd_safe_autodrain')) {
    function wwqd_safe_autodrain() {
        if (defined('DOING_AJAX') && DOING_AJAX) return;
        wwqd_drain_sync_queue(10);
    }
}

if (!function_exists('_wwqd_sensitive_fields')) {
    function _wwqd_sensitive_fields(): array {
        static $list = null;
        if ($list !== null) return $list;
        $base = [
            'phpsessid','php_sessid','php_session_id','session','jwt',
            'buzz_sso','buzz_sso_serialized','buzz_sso_last','buzz_sso_last_sync',
            'wp_sso','wp_sso_login','wp_session_name',
            'user_pass','password','user_pass_hash','auth_token','two_factor_hash',
            'web_device_id','android_m_device_id','ios_m_device_id',
            'last_login_data','lastseen','last_email_sent','session_id','device_id','ip_address'
        ];
        $maps = function_exists('_wwqd_field_maps') ? _wwqd_field_maps() : ['private_secure_fields' => []];
        if (!empty($maps['private_secure_fields']) && is_array($maps['private_secure_fields'])) {
            $private = array_map('strtolower', array_values($maps['private_secure_fields']));
            $base = array_merge($base, $private);
        }
        $normalized = [];
        foreach ($base as $k) {
            $k2 = strtolower(trim((string)$k));
            if ($k2 === '') continue;
            $normalized[$k2] = $k2;
        }
        $list = array_values($normalized);
        return $list;
    }
}

if (!function_exists('_wwqd_table_has_column')) {
    function _wwqd_table_has_column($conn, string $table, string $column): bool {
        if (!$conn || !$table || !$column) return false;
        $table = trim($table);
        $column_safe = $conn->real_escape_string(trim($column, '`'));

        try {
            if (strpos($table, '.') !== false) {
                $parts = explode('.', $table);
                $quoted = [];
                foreach ($parts as $p) {
                    $quoted[] = '`' . $conn->real_escape_string(trim($p, '`')) . '`';
                }
                $table_query = implode('.', $quoted);
            } else {
                $table_query = '`' . $conn->real_escape_string(trim($table, '`')) . '`';
            }

            $q = "SHOW COLUMNS FROM {$table_query} LIKE '{$column_safe}'";
            $res = @ $conn->query($q);
            return ($res && $res->num_rows > 0);
        } catch (Exception $e) {
            log_sync_debug("_wwqd_table_has_column exception for {$table}: " . $e->getMessage(), 'WWQD');
            return false;
        }
    }
}

if (!function_exists('_wwqd_ensure_wp_field')) {
    function _wwqd_ensure_wp_field(string $field): bool {
        $field = trim((string)$field);
        if ($field === '') return false;

        $maps = _wwqd_field_maps();
        // Only attempt for public xProfile fields
        if (!array_key_exists($field, $maps['public_open_fields'])) {
            return true; // nothing to create for private fields
        }

        if (!function_exists('get_wp_db_conn')) return false;
        $conn = get_wp_db_conn();
        if (!$conn) return false;

        // Candidate table names (adjust if your BP table names differ)
        $candidates = [
            WP_TABLE_PREFIX . 'bp_xprofile_fields',
            BP_TABLE_PREFIX . 'bp_xprofile_fields',
            WP_TABLE_PREFIX . 'xprofile_fields',
            BP_TABLE_PREFIX . 'xprofile_fields',
            'bp_xprofile_fields',
            'xprofile_fields'
        ];

        $found = null;
        foreach ($candidates as $t) {
            $t_safe = $conn->real_escape_string(trim($t, '`'));
            $rs = @ $conn->query("SHOW TABLES LIKE '{$t_safe}'");
            if ($rs && $rs->num_rows > 0) {
                $found = $t_safe;
                break;
            }
        }

        if (!$found) {
            // Cannot find xprofile table in WP DB
            return false;
        }

        // Check existence by name
        $field_safe = $conn->real_escape_string($field);
        $r = @ $conn->query("SELECT id FROM `{$found}` WHERE `name` = '{$field_safe}' LIMIT 1");
        if ($r && $r->num_rows > 0) return true;

        // Insert a minimal textbox xProfile field; keep safe
        $stmt = @ $conn->prepare("INSERT INTO `{$found}` (`name`,`type`,`description`,`can_delete`,`position`,`is_required`,`is_unique`,`is_register`,`is_default`) VALUES (?,?,?,?,?,?,?,?,?)");
        if (!$stmt) {
            return false;
        }
        $type = 'textbox';
        $description = "Auto-created by WWQD bridge for field '{$field}'";
        $can_delete = 0;
        $position = 0;
        $is_required = 0;
        $is_unique = 0;
        $is_register = 0;
        $is_default = 0;
        $stmt->bind_param('sssiissii', $field, $type, $description, $can_delete, $position, $is_required, $is_unique, $is_register, $is_default);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }
}






// --- START: WP xProfile DB lookup + minimal media preparation + consolidated WP->Wo sync ---

if (!function_exists('bz_wp_xprofile_field_value')) {
    /**
     * Fetch a BuddyPress/BuddyBoss xProfile field value (DB-backed).
     * Returns '' on no result or on error.
     *
     * Preference order:
     * 1. Use existing wp_get_xprofile_data() helper if available.
     * 2. Try bp_table()/WP_TABLE_PREFIX-based table names (deterministic).
     */
    function bz_wp_xprofile_field_value($wp_conn, int $wp_user_id, string $field_name) {
        if (!$wp_conn || $wp_user_id <= 0 || trim($field_name) === '') return '';

        // 1) Prefer existing helper if defined
        if (function_exists('wp_get_xprofile_data')) {
            try {
                $v = wp_get_xprofile_data($wp_conn, $wp_user_id, $field_name);
                return (string)($v ?? '');
            } catch (Throwable $e) {
                // fall through to DB lookup
            }
        }

        $field_name_raw = trim((string)$field_name);
        $field_name_safe = $wp_conn->real_escape_string($field_name_raw);

        // Deterministic candidate table names (avoid expensive SHOW TABLES discovery)
        $prefixes = [];
        if (defined('WP_TABLE_PREFIX')) $prefixes[] = WP_TABLE_PREFIX;
        if (defined('BP_TABLE_PREFIX')) $prefixes[] = BP_TABLE_PREFIX;
        // Common default
        $prefixes[] = 'wp_';
        $prefixes = array_unique($prefixes);

        $fields_table = '';
        $data_table = '';

        foreach ($prefixes as $pre) {
            $t1 = $pre . 'bp_xprofile_fields';
            $t2 = $pre . 'xprofile_fields';
            // quick existence check — suppress warnings
            $t1_safe = $wp_conn->real_escape_string($t1);
            $res = @ $wp_conn->query("SHOW TABLES LIKE '{$t1_safe}'");
            if ($res && $res->num_rows > 0) { $fields_table = $t1; $data_table = str_replace('_fields', '_data', $t1); break; }

            $t2_safe = $wp_conn->real_escape_string($t2);
            $res2 = @ $wp_conn->query("SHOW TABLES LIKE '{$t2_safe}'");
            if ($res2 && $res2->num_rows > 0) { $fields_table = $t2; $data_table = str_replace('_fields', '_data', $t2); break; }
        }

        // If still not found, attempt bp_table() if available (one last attempt)
        if (!$fields_table && function_exists('bp_table')) {
            $t = bp_table('xprofile_fields');
            $t_safe = $wp_conn->real_escape_string($t);
            $r = @ $wp_conn->query("SHOW TABLES LIKE '{$t_safe}'");
            if ($r && $r->num_rows > 0) {
                $fields_table = $t;
                $data_table = str_replace('fields', 'data', $t);
            }
        }

        if (!$fields_table || !$data_table) return '';

        // Find field id (first exact name then case-insensitive fallback)
        $q = "SELECT id FROM `{$fields_table}` WHERE `name` = '{$field_name_safe}' LIMIT 1";
        $r = @ $wp_conn->query($q);
        $field_id = 0;
        if ($r && $r->num_rows > 0) {
            $row = $r->fetch_assoc(); $field_id = intval($row['id']);
        } else {
            // case-insensitive attempt
            $q2 = "SELECT id FROM `{$fields_table}` WHERE LOWER(`name`) = LOWER('{$field_name_safe}') LIMIT 1";
            $r2 = @ $wp_conn->query($q2);
            if ($r2 && $r2->num_rows > 0) { $row = $r2->fetch_assoc(); $field_id = intval($row['id']); }
        }
        if (!$field_id) return '';

        $uid = intval($wp_user_id); $fid = intval($field_id);
        $qv = "SELECT `value` FROM `{$data_table}` WHERE user_id = {$uid} AND field_id = {$fid} LIMIT 1";
        $rv = @ $wp_conn->query($qv);
        if (!$rv || $rv->num_rows === 0) return '';
        $rowv = $rv->fetch_assoc();
        return (string)($rowv['value'] ?? '');
    }
}

if (!function_exists('bz_prepare_media_for_wo')) {
    /**
     * Minimal media preparation for WoWonder:
     * - Absolute URLs: unchanged.
     * - streams/... or /streams/... or ../streams/... -> ../streams/...
     * - upload/photos/... -> unchanged (WoWonder-native).
     * - /wp-content/... -> https://buzzjuice.net/wp-content/...
     * - Otherwise: return original (WP is source-of-truth).
     *
     * This intentionally performs minimal changes to avoid double-prefixing.
     */
    function bz_prepare_media_for_wo($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return '';

        // Already absolute? pass through
        if (preg_match('#^https?://#i', $raw)) return $raw;

        // Normalize separators
        $raw = str_replace('\\', '/', $raw);
        $raw = preg_replace('#(?<!:)//+#', '/', $raw);

        // WoWonder-native upload path
        if (preg_match('#^upload/photos/#i', $raw)) return $raw;

        // streams/upload/photos/ or streams/... → ../streams/...
        if (preg_match('#^(\.\.?/)?streams/(.+)$#i', $raw, $m)) {
            return '../streams/' . ltrim($m[2], '/');
        }

        // leading /streams/...
        if (preg_match('#^/streams/(.+)$#i', $raw, $m2)) {
            return '../streams/' . ltrim($m2[1], '/');
        }

        // /wp-content/ -> absolute BuzzJuice URL (site canonical)
        if (strpos($raw, '/wp-content/') === 0) {
            return 'https://buzzjuice.net' . $raw;
        }
        if (strpos($raw, 'wp-content/') === 0) {
            return 'https://buzzjuice.net/' . ltrim($raw, '/');
        }

        // Otherwise: return raw value — WP is authoritative
        return $raw;
    }
}

if (!function_exists('sync_wp_user_to_wowonder_meta')) {
    /**
     * Consolidated WP -> WoWonder meta sync.
     *
     * - $wp_conn: mysqli WP connection
     * - $sqlConn: mysqli WoWonder connection
     * - $wp_user_id, $wo_user_id: ints
     *
     * Behavior:
     *  - Read WP xProfile (DB-first), runtime xprofile and usermeta as fallback.
     *  - Build update payload from metadata mapping (shared/buzz_metadata.json)
     *  - Skip avatar/cover in generic mapping (they are handled explicitly)
     *  - Prepare avatar/cover with bz_prepare_media_for_wo() and write them
     *  - Write directly to Wo_Users, verify and perform a single repair pass
     */
    function sync_wp_user_to_wowonder_meta($wp_conn, $sqlConn, int $wp_user_id, int $wo_user_id) {
        if (!$wp_conn || !$sqlConn || $wp_user_id <= 0 || $wo_user_id <= 0) return false;

        $wo_table = defined('T_USERS') ? T_USERS : 'Wo_Users';

        // Load Wo schema (per-request cache)
        static $wo_schema_cache = null;
        if ($wo_schema_cache === null) {
            $wo_schema_cache = [];
            $res = @mysqli_query($sqlConn, "SHOW COLUMNS FROM {$wo_table}");
            while ($res && $r = mysqli_fetch_assoc($res)) $wo_schema_cache[$r['Field']] = true;
        }
        $wo_schema = $wo_schema_cache;

        // Load metadata mapping
        $metadata_file = $_SERVER['DOCUMENT_ROOT'] . '/shared/buzz_metadata.json';
        $meta_map = [];
        if (file_exists($metadata_file)) {
            $json = @json_decode(@file_get_contents($metadata_file), true);
            if (isset($json['private_secure_fields']) && isset($json['public_open_fields'])) {
                $meta_map = array_merge($json['private_secure_fields'], $json['public_open_fields']);
            } elseif (is_array($json)) {
                $meta_map = $json;
            }
        }

        // Acquire WP data (best-effort)
        $wp_data     = function_exists('wp_get_full_user_data') ? wp_get_full_user_data($wp_conn, $wp_user_id) : [];
        $wp_meta     = $wp_data['meta'] ?? [];
        $wp_xprofile = $wp_data['xprofile'] ?? [];
        $wp_core     = $wp_data;

        // Avatar/cover resolution: DB-first (authoritative), then runtime xprofile, then usermeta
        $default_icon      = 'https://buzzjuice.net/wp-content/uploads/2026/04/BuzzJuice-Logo-2.03-icon192x192.png';
        $bb_avatar_default = '/wp-content/plugins/buddyboss-platform/bp-core/images/profile-avatar-buddyboss.png';
        $bb_cover_primary  = '/wp-content/uploads/buddypress/members/0/cover-image/69dd867faacfb-bp-cover-image.jpg';
        $bb_cover_fallback = '/wp-content/plugins/buddyboss-platform/bp-core/images/cover-image.png';

        $wp_avatar_raw = '';
        $wp_cover_raw  = '';

        // 1) runtime xprofile if present
        if (!empty($wp_xprofile) && is_array($wp_xprofile)) {
            foreach ($wp_xprofile as $k => $v) {
                $lk = strtolower(trim($k));
                if ($lk === 'avatar' && $v) $wp_avatar_raw = $v;
                if ($lk === 'cover'  && $v) $wp_cover_raw  = $v;
            }
        }

        // 2) usermeta/bp fallbacks
        if (!$wp_avatar_raw && !empty($wp_meta['bp_profile_avatar'])) $wp_avatar_raw = $wp_meta['bp_profile_avatar'];
        if (!$wp_cover_raw  && !empty($wp_meta['bp_profile_cover']))  $wp_cover_raw  = $wp_meta['bp_profile_cover'];

        // 3) DB xProfile fallback — authoritative
        if (empty($wp_avatar_raw)) {
            $dbval = bz_wp_xprofile_field_value($wp_conn, $wp_user_id, 'avatar');
            if ($dbval !== '') $wp_avatar_raw = $dbval;
        }
        if (empty($wp_cover_raw)) {
            $dbval = bz_wp_xprofile_field_value($wp_conn, $wp_user_id, 'cover');
            if ($dbval !== '') $wp_cover_raw = $dbval;
        }

        // Prepare values for WoWonder
        $avatar_candidate = bz_prepare_media_for_wo($wp_avatar_raw);
        $cover_candidate  = bz_prepare_media_for_wo($wp_cover_raw);

        // Choose final avatar/cover: prefer WP-provided else buddyboss defaults else global default
        $avatar_url = $avatar_candidate ?: bz_prepare_media_for_wo($bb_avatar_default) ?: $default_icon;
        $cover_url  = $cover_candidate  ?: bz_prepare_media_for_wo($bb_cover_primary)  ?: bz_prepare_media_for_wo($bb_cover_fallback) ?: $default_icon;

        // Logging acquisition
        if (function_exists('bz_bridge_log')) {
            bz_bridge_log('WP→Wo avatar/cover acquisition', [
                'wp_user_id' => $wp_user_id,
                'raw_avatar' => $wp_avatar_raw,
                'raw_cover'  => $wp_cover_raw,
                'prepared_avatar' => $avatar_candidate,
                'prepared_cover'  => $cover_candidate,
                'chosen_avatar' => $avatar_url,
                'chosen_cover'  => $cover_url
            ]);
        }

        // Build update payload — skip avatar/cover in generic mapping
        $update = [];
        foreach ($meta_map as $wp_field => $wo_field) {
            if (!isset($wo_schema[$wo_field])) continue;
            if ($wo_field === 'avatar' || $wo_field === 'cover') continue;

            $v = null;
            if (isset($wp_xprofile[$wp_field])) $v = $wp_xprofile[$wp_field];
            elseif (isset($wp_meta[$wp_field]))  $v = $wp_meta[$wp_field];
            elseif (isset($wp_core[$wp_field]))  $v = $wp_core[$wp_field];

            if (function_exists('bz_clean_scalar')) $val = bz_clean_scalar($v);
            else {
                if (is_array($v) || is_object($v)) $val = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                else $val = trim((string)$v);
            }
            if ($val !== '' && $val !== null) $update[$wo_field] = $val;
        }

        // Force identity fields if present
        if (isset($wo_schema['wp_user_id'])) $update['wp_user_id'] = $wp_user_id;
        if (!empty($_SESSION['wp_user_email']) && isset($wo_schema['email'])) $update['email'] = trim($_SESSION['wp_user_email']);
        if (!empty($_SESSION['wp_user_login']) && isset($wo_schema['username'])) $update['username'] = trim($_SESSION['wp_user_login']);

        // Force avatar/cover writes (WordPress is source-of-truth)
        if (isset($wo_schema['avatar'])) $update['avatar'] = $avatar_url;
        if (isset($wo_schema['cover']))  $update['cover']  = $cover_url;

        // Add change hash
        ksort($update);
        $update['wp_meta_hash'] = md5(json_encode($update, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

        // Log prepared payload
        if (function_exists('bz_bridge_log')) {
            bz_bridge_log('WP→Wo sync payload prepared', [
                'wo_user_id' => $wo_user_id,
                'fields'     => array_keys($update),
                'payload'    => $update
            ]);
        }

        // Build UPDATE SQL
        $set = [];
        foreach ($update as $field => $value) {
            if (!isset($wo_schema[$field])) continue;
            $safe_field = preg_replace('/[^a-zA-Z0-9_]/','',$field);
            $safe_value = mysqli_real_escape_string($sqlConn, (string)$value);
            $set[] = "`{$safe_field}`='{$safe_value}'";
        }

        if (empty($set)) {
            if (function_exists('bz_bridge_log')) bz_bridge_log('WP→Wo sync: nothing to update', ['wo_user_id'=>$wo_user_id]);
            return true;
        }

        $sql = "UPDATE {$wo_table} SET " . implode(',', $set) . " WHERE user_id=" . (int)$wo_user_id . " LIMIT 1";
        $write_result = @mysqli_query($sqlConn, $sql);
        if (function_exists('bz_bridge_log')) {
            bz_bridge_log('WP→Wo sync DB update', [
                'wo_user_id' => $wo_user_id,
                'result'     => $write_result ? 'OK' : 'FAIL',
                'mysql_error'=> mysqli_error($sqlConn),
                'sql'        => $sql
            ]);
        }

        // Post-write verify & repair (compare payload vs DB row)
        $post = @mysqli_query($sqlConn, "SELECT * FROM {$wo_table} WHERE user_id=" . (int)$wo_user_id . " LIMIT 1");
        $vrow = $post ? mysqli_fetch_assoc($post) : [];
        $repair = [];
        foreach ($update as $field => $value) {
            if (!isset($wo_schema[$field])) continue;
            $dbval = isset($vrow[$field]) ? (string)$vrow[$field] : '';
            if ($dbval !== (string)$value) $repair[$field] = $value;
        }
        if (!empty($repair)) {
            $repair_set = [];
            foreach ($repair as $field => $val) {
                $safe_field = preg_replace('/[^a-zA-Z0-9_]/','',$field);
                $safe_val = mysqli_real_escape_string($sqlConn, (string)$val);
                $repair_set[] = "`{$safe_field}`='{$safe_val}'";
            }
            $repair_sql = "UPDATE {$wo_table} SET " . implode(',', $repair_set) . " WHERE user_id=" . (int)$wo_user_id . " LIMIT 1";
            @mysqli_query($sqlConn, $repair_sql);
            if (function_exists('bz_bridge_log')) {
                bz_bridge_log('WP→Wo sync repair applied', [
                    'wo_user_id' => $wo_user_id,
                    'repair_fields' => array_keys($repair),
                    'repair_data' => $repair
                ]);
            }
        }

        // Final verification log (selected snapshot)
        $verify = @mysqli_query($sqlConn, "SELECT avatar,cover,first_name,about FROM {$wo_table} WHERE user_id=" . (int)$wo_user_id . " LIMIT 1");
        $snapshot = $verify ? mysqli_fetch_assoc($verify) : [];
        if (function_exists('bz_bridge_log')) {
            bz_bridge_log('WP→Wo sync final verification', [
                'wo_user_id' => $wo_user_id,
                'avatar'     => $snapshot['avatar'] ?? null,
                'cover'      => $snapshot['cover']  ?? null,
                'first_name' => $snapshot['first_name'] ?? null,
                'about'      => $snapshot['about'] ?? null,
                'expected_avatar' => $avatar_url,
                'expected_cover'  => $cover_url
            ]);
        }

        return true;
    }
}

// --- END: WP xProfile DB lookup + minimal media preparation + consolidated WP->Wo sync ---






if (!function_exists('_wwqd_can_update_target')) {
    function _wwqd_can_update_target(string $origin, $target_conn, string $table, string $field): bool {
        $origin = strtolower(trim((string)$origin));
        if ($origin === 'wordpress') {
            return _wwqd_table_has_column($target_conn, $table, $field);
        }

        // Ensure WP has public xProfile field when origin is WW or QD
        @ _wwqd_ensure_wp_field($field);
        // Still only update target if target has column
        return _wwqd_table_has_column($target_conn, $table, $field);
    }
}

/* --------------------------------------------------------------------------
 * Metadata loader
 * - Reads shared/buzz_metadata.json (preferred local file), falls back to remote URL
 * -------------------------------------------------------------------------- */
 
function get_user_field_metadata() {
    static $metadata = null;
    if ($metadata !== null) return $metadata;

    $local = __DIR__ . '/buzz_metadata.json';
    if (file_exists($local)) {
        $json = @file_get_contents($local);
    } 
    
    $metadata = $json ? json_decode($json, true) : ['private_secure_fields' => [], 'public_open_fields' => []];
    // Ensure keys exist
    if (!isset($metadata['private_secure_fields'])) $metadata['private_secure_fields'] = [];
    if (!isset($metadata['public_open_fields'])) $metadata['public_open_fields'] = [];
    return $metadata;
}

/* --------------------------------------------------------------------------
 * WordPress utility functions (kept names for backward compatibility)
 * Many functions optionally accept a DB connection parameter to be flexible.
 * -------------------------------------------------------------------------- */

/**
 * Fetch WP user by login or email
 * Signature maintained: wp_get_user_by_login_or_email($sqlConnect, $login_or_email)
 * also accept call with only ($login_or_email).
 */
if (!function_exists('wp_get_user_by_login_or_email')) {
    function wp_get_user_by_login_or_email($sqlConnectOrLogin, $maybeLogin = null) {
        if ($maybeLogin === null) {
            // called as wp_get_user_by_login_or_email($login)
            $login_or_email = $sqlConnectOrLogin;
            $sqlConnect = get_wp_db_conn();
        } else {
            $sqlConnect = $sqlConnectOrLogin;
            $login_or_email = $maybeLogin;
        }
        if (!$sqlConnect) return false;
        $login_or_email = mysqli_real_escape_string($sqlConnect, $login_or_email);
        $query = "SELECT * FROM " . wp_table('users') . " WHERE user_login = '$login_or_email' OR user_email = '$login_or_email' LIMIT 1";
        $result = mysqli_query($sqlConnect, $query);
        if (!$result || mysqli_num_rows($result) == 0) return false;
        return mysqli_fetch_assoc($result);
    }
}

/**
 * Check WP password using phpass
 * Keep same signature.
 */
 /*
if (!function_exists('wp_check_password')) {
    function wp_check_password($password, $hash) {
        if (!class_exists('PasswordHash')) {
            // try to include a local class-phpass.php if available in shared folder
            $phpass_path = __DIR__ . '/../wp-includes/class-phpass.php';
            if (file_exists($phpass_path)) require_once $phpass_path;
        }
        $wp_hasher = new PasswordHash(8, true);
        return $wp_hasher->CheckPassword($password, $hash);
    }
}
*/

/**
 * Get WP usermeta (compat)
 * Signature: wp_get_usermeta($sqlConnect, $user_id, $meta_key) or wp_get_usermeta($user_id, $meta_key)
 */
if (!function_exists('wp_get_usermeta')) {
    function wp_get_usermeta($sqlConnectOrUserId, $maybeUserId = null, $maybeMetaKey = null) {
        if ($maybeMetaKey === null) {
            // called as wp_get_usermeta($user_id, $meta_key)
            $sqlConnect = get_wp_db_conn();
            $user_id = intval($sqlConnectOrUserId);
            $meta_key = $maybeUserId;
        } else {
            $sqlConnect = $sqlConnectOrUserId;
            $user_id = intval($maybeUserId);
            $meta_key = $maybeMetaKey;
        }
        if (!$sqlConnect) return null;
        $meta_key = mysqli_real_escape_string($sqlConnect, $meta_key);
        $query = "SELECT meta_value FROM " . wp_table('usermeta') . " WHERE user_id = $user_id AND meta_key = '$meta_key' LIMIT 1";
        $result = mysqli_query($sqlConnect, $query);
        if (!$result || mysqli_num_rows($result) == 0) return null;
        $row = mysqli_fetch_assoc($result);
        return maybe_unserialize($row['meta_value']);
    }
}

/**
 * Get BuddyBoss xProfile field
 * Signature kept: wp_get_xprofile_data($sqlConnect, $user_id, $field_name) or wp_get_xprofile_data($user_id, $field_name)
 */
if (!function_exists('wp_get_xprofile_data')) {
    function wp_get_xprofile_data($sqlConnectOrUserId, $maybeUserId = null, $maybeField = null) {
        if ($maybeField === null) {
            $sqlConnect = get_wp_db_conn();
            $user_id = intval($sqlConnectOrUserId);
            $field_name = $maybeUserId;
        } else {
            $sqlConnect = $sqlConnectOrUserId;
            $user_id = intval($maybeUserId);
            $field_name = $maybeField;
        }
        if (!$sqlConnect) return null;
        $field_name = mysqli_real_escape_string($sqlConnect, $field_name);
        $field_id_query = "SELECT id FROM " . bp_table('xprofile_fields') . " WHERE name = '$field_name' LIMIT 1";
        $field_id_result = mysqli_query($sqlConnect, $field_id_query);
        if (!$field_id_result || mysqli_num_rows($field_id_result) == 0) return null;
        $field_id_row = mysqli_fetch_assoc($field_id_result);
        $field_id = intval($field_id_row['id']);
        $value_query = "SELECT value FROM " . bp_table('xprofile_data') . " WHERE user_id = $user_id AND field_id = $field_id LIMIT 1";
        $value_result = mysqli_query($sqlConnect, $value_query);
        if (!$value_result || mysqli_num_rows($value_result) == 0) return null;
        $value_row = mysqli_fetch_assoc($value_result);
        return $value_row['value'];
    }
}

/**
 * Create WP user (basic, no meta)
 * Signature: wp_create_user($sqlConnect, $username, $password, $email) or wp_create_user($username, $password, $email)
 */
if (!function_exists('wp_create_user')) {
    function wp_create_user($sqlConnOrUser, $maybePass = null, $maybeEmail = null, $maybeUnused = null) {
        if ($maybeEmail === null) {
            // called as wp_create_user($username, $password, $email)
            $wp_db_conn = get_wp_db_conn();
            $username = $sqlConnOrUser;
            $password = $maybePass;
            $email = $maybeEmail; // actually null, but keep signature consistent: expecting 3 args
            // If user passed exactly 3 args, shift
            if ($maybePass !== null && $maybeEmail === null) {
                // actually arguments were (user, pass, email) but we saw maybeEmail null due to signature;
                // try retrieving global func args
                $args = func_get_args();
                if (count($args) >= 3) {
                    $username = $args[0];
                    $password = $args[1];
                    $email = $args[2];
                }
            }
        } else {
            $wp_db_conn = $sqlConnOrUser;
            $username = $maybePass;
            $password = $maybeEmail;
            $email = $maybeUnused;
        }

        if (!$wp_db_conn) return false;
        if (!class_exists('PasswordHash')) {
            $phpass_path = __DIR__ . '/class-phpass.php';
            if (file_exists($phpass_path)) require_once $phpass_path;
        }
        $wp_hasher = new PasswordHash(8, true);
        $user_login = mysqli_real_escape_string($wp_db_conn, $username);
        $user_pass = $wp_hasher->HashPassword($password);
        $user_email = mysqli_real_escape_string($wp_db_conn, $email);
        $user_registered = date('Y-m-d H:i:s');
        $check_query = "SELECT ID FROM " . wp_table('users') . " WHERE user_login = '$user_login' OR user_email = '$user_email' LIMIT 1";
        $check_result = mysqli_query($wp_db_conn, $check_query);
        if ($check_result && mysqli_num_rows($check_result) > 0) return false;
        $insert_query = "INSERT INTO " . wp_table('users') . " (user_login, user_pass, user_email, user_registered, user_status) VALUES ('$user_login', '$user_pass', '$user_email', '$user_registered', 0)";
        $insert_result = mysqli_query($wp_db_conn, $insert_query);
        if (!$insert_result) return false;
        return mysqli_insert_id($wp_db_conn);
    }
}

/**
 * Get full WP user data (user + meta + xprofile)
 */
if (!function_exists('wp_get_full_user_data')) {
    function wp_get_full_user_data($sqlConnectOrUserId, $maybeUserId = null) {
        if ($maybeUserId === null) {
            $sqlConnect = get_wp_db_conn();
            $user_id = intval($sqlConnectOrUserId);
        } else {
            $sqlConnect = $sqlConnectOrUserId;
            $user_id = intval($maybeUserId);
        }
        if (!$sqlConnect || $user_id <= 0) return false;
        $user_query = "SELECT * FROM " . wp_table('users') . " WHERE ID = $user_id LIMIT 1";
        $user_result = mysqli_query($sqlConnect, $user_query);
        if (!$user_result || mysqli_num_rows($user_result) == 0) return false;
        $user = mysqli_fetch_assoc($user_result);

        $meta = [];
        $meta_query = "SELECT meta_key, meta_value FROM " . wp_table('usermeta') . " WHERE user_id = $user_id";
        $meta_result = mysqli_query($sqlConnect, $meta_query);
        if ($meta_result) {
            while ($row = mysqli_fetch_assoc($meta_result)) {
                $meta[$row['meta_key']] = maybe_unserialize($row['meta_value']);
            }
        }

        $xprofile = [];
        $field_query = "SELECT f.id, f.name FROM " . bp_table('xprofile_fields') . " AS f";
        $field_result = mysqli_query($sqlConnect, $field_query);
        if ($field_result) {
            while ($field = mysqli_fetch_assoc($field_result)) {
                $field_id = intval($field['id']);
                $field_name = $field['name'];
                $data_query = "SELECT value FROM " . bp_table('xprofile_data') . " WHERE user_id = $user_id AND field_id = $field_id LIMIT 1";
                $data_result = mysqli_query($sqlConnect, $data_query);
                if ($data_result && mysqli_num_rows($data_result)) {
                    $data_row = mysqli_fetch_assoc($data_result);
                    $xprofile[$field_name] = $data_row['value'];
                }
            }
        }

        return [
            'ID' => $user['ID'],
            'user_login' => $user['user_login'],
            'user_email' => $user['user_email'],
            //'user_pass' => $user['user_pass'],
            'user_registered' => $user['user_registered'],
            'display_name' => $user['display_name'] ?? '',
            'meta' => $meta,
            'xprofile' => $xprofile
        ];
    }
}

/* --------------------------------------------------------------------------
 * Update/Insert WP usermeta and WoWonder mapping
 * This is a consolidated wp_update_usermeta signature to support previous callers.
 *
 * There are two historical signatures in the repo:
 *  - wp_update_usermeta($sqlConnect, $user_id, $meta_data, $user = null)
 *    (where $meta_data may be array of keys => values OR single key then extra value)
 *  - wp_update_usermeta($wp_db_conn, $user_id, $meta_key, $meta_value) (assets version)
 *
 * To keep compatibility, detect usage and normalize input.
 * -------------------------------------------------------------------------- */
if (!function_exists('wp_update_usermeta')) {
    function wp_update_usermeta() {
        $args = func_get_args();
        // Determine form:
        // Form A: ($sqlConnect, $user_id, $meta_data (array OR single key), $user_obj_or_value = null)
        // Form B: ($wp_db_conn, $user_id, $meta_key, $meta_value)  [assets version]
        // Form C: ($user_id, $meta_key, $meta_value)  [some callers used this]
        // We'll normalize to: $wp_db_conn, $user_id, $meta_data_array, $user_obj
        $wp_db_conn = null; $user_id = 0; $meta_data = []; $user_obj = null;

        if (count($args) === 0) return false;

        // If first arg is mysqli, treat as form A/B
        if ($args[0] instanceof mysqli) {
            $wp_db_conn = $args[0];
            if (isset($args[1])) $user_id = intval($args[1]);
            if (isset($args[2])) $meta_data = $args[2];
            if (isset($args[3])) $user_obj = $args[3];
        } else {
            // first arg is not mysqli, maybe called as (user_id, key, value)
            if (count($args) >= 3) {
                $wp_db_conn = get_wp_db_conn();
                $user_id = intval($args[0]);
                $meta_data = [$args[1] => $args[2]];
            } elseif (count($args) === 2) {
                // ($user_id, $meta_data_array)
                $wp_db_conn = get_wp_db_conn();
                $user_id = intval($args[0]);
                $meta_data = $args[1];
            } else {
                return false;
            }
        }

        if (!$wp_db_conn || $user_id <= 0 || empty($meta_data)) {
            error_log("[wp_update_usermeta] Invalid parameters.");
            return false;
        }

        // Normalize meta_data: if not array but a string key with a passed value in $user_obj (old form),
        // the previous implementation used func_get_arg(3). We attempted to capture that above.
        if (!is_array($meta_data)) {
            error_log("[wp_update_usermeta] Converting single meta key to array.");
            // Attempt to recover value from user_obj if provided
            if ($user_obj !== null) {
                $meta_data = [$meta_data => $user_obj];
            } else {
                error_log("[wp_update_usermeta] No value provided for meta key.");
                return false;
            }
        }

        // Prepare escaping/serialization
        $prepare_value = function($val) use ($wp_db_conn) {
            if (is_array($val) || is_object($val)) $val = serialize($val);
            return mysqli_real_escape_string($wp_db_conn, $val);
        };

        // Optional: Extract qd_user_id if present (older logic)
        $qd_user_id = null;
        if ($user_obj && is_object($user_obj) && isset($user_obj->id)) {
            // attempt lookup
            $qd_user_id = get_quickdate_user_id_by_wp_email($user_id);
            error_log("[wp_update_usermeta] Found qd_user_id from user object: $qd_user_id");
        } elseif (isset($meta_data['qd_user_id']) && is_numeric($meta_data['qd_user_id'])) {
            $qd_user_id = (int)$meta_data['qd_user_id'];
            error_log("[wp_update_usermeta] Found qd_user_id from meta_data: $qd_user_id");
        }

        if ($qd_user_id) {
            $safe_qd_value = $prepare_value($qd_user_id);
            $check_qd = "SELECT umeta_id FROM " . wp_table('usermeta') . " WHERE user_id = $user_id AND meta_key = 'qd_user_id' LIMIT 1";
            $result_qd = mysqli_query($wp_db_conn, $check_qd);
            if ($result_qd && mysqli_num_rows($result_qd) > 0) {
                $row_qd = mysqli_fetch_assoc($result_qd);
                $umeta_id_qd = intval($row_qd['umeta_id']);
                $update_qd = "UPDATE " . wp_table('usermeta') . " SET meta_value = '$safe_qd_value' WHERE umeta_id = $umeta_id_qd";
                if (mysqli_query($wp_db_conn, $update_qd)) {
                    error_log("[wp_update_usermeta] Updated qd_user_id for user_id $user_id");
                } else {
                    error_log("[wp_update_usermeta] Failed to update qd_user_id: " . mysqli_error($wp_db_conn));
                }
            } else {
                $insert_qd = "INSERT INTO " . wp_table('usermeta') . " (user_id, meta_key, meta_value) VALUES ($user_id, 'qd_user_id', '$safe_qd_value')";
                if (mysqli_query($wp_db_conn, $insert_qd)) {
                    error_log("[wp_update_usermeta] Inserted qd_user_id for user_id $user_id");
                } else {
                    error_log("[wp_update_usermeta] Failed to insert qd_user_id: " . mysqli_error($wp_db_conn));
                }
            }
            unset($meta_data['qd_user_id']);
        } else {
            error_log("[wp_update_usermeta] No valid qd_user_id found for user_id $user_id");
        }

        // Process other meta keys
        foreach ($meta_data as $meta_key => $meta_value) {
            $safe_key = mysqli_real_escape_string($wp_db_conn, $meta_key);
            $safe_value = $prepare_value($meta_value);

            $check = "SELECT umeta_id FROM " . wp_table('usermeta') . " WHERE user_id = $user_id AND meta_key = '$safe_key' LIMIT 1";
            $result = mysqli_query($wp_db_conn, $check);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $umeta_id = intval($row['umeta_id']);
                $update = "UPDATE " . wp_table('usermeta') . " SET meta_value = '$safe_value' WHERE umeta_id = $umeta_id";
                if (mysqli_query($wp_db_conn, $update)) {
                    error_log("[wp_update_usermeta] Updated meta '$meta_key' for user_id $user_id");
                } else {
                    error_log("[wp_update_usermeta] Failed to update '$meta_key': " . mysqli_error($wp_db_conn));
                }
            } else {
                $insert = "INSERT INTO " . wp_table('usermeta') . " (user_id, meta_key, meta_value) VALUES ($user_id, '$safe_key', '$safe_value')";
                if (mysqli_query($wp_db_conn, $insert)) {
                    error_log("[wp_update_usermeta] Inserted meta '$meta_key' for user_id $user_id");
                } else {
                    error_log("[wp_update_usermeta] Failed to insert '$meta_key': " . mysqli_error($wp_db_conn));
                }
            }
        }

        // Also ensure wo_user_id mapping is created/updated (similar to assets logic)
        try {
            // Avoid recursion if meta key already was wo_user_id
            // Attempt to get the WoWonder ID by looking up Wo_Users via user's email
            $q = "SELECT user_email FROM " . wp_table('users') . " WHERE ID = $user_id LIMIT 1";
            $res = mysqli_query($wp_db_conn, $q);
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $user_email = $row['user_email'];
                $ww_db_conn = get_wowonder_db();
                if ($ww_db_conn) {
                    $email_esc = mysqli_real_escape_string($ww_db_conn, $user_email);
                    $ww_query = "SELECT user_id FROM Wo_Users WHERE email='$email_esc' LIMIT 1";
                    $ww_result = mysqli_query($ww_db_conn, $ww_query);
                    if ($ww_result && mysqli_num_rows($ww_result) > 0) {
                        $ww_row = mysqli_fetch_assoc($ww_result);
                        $wowonder_user_id = intval($ww_row['user_id']);
                        $check_q = "SELECT umeta_id, meta_value FROM " . wp_table('usermeta') . " WHERE user_id = $user_id AND meta_key = 'wo_user_id' LIMIT 1";
                        $check_result = mysqli_query($wp_db_conn, $check_q);
                        if ($check_result && mysqli_num_rows($check_result) > 0) {
                            $existing = mysqli_fetch_assoc($check_result);
                            if ($existing['meta_value'] != $wowonder_user_id) {
                                $update = "UPDATE " . wp_table('usermeta') . " SET meta_value = '$wowonder_user_id' WHERE umeta_id = {$existing['umeta_id']}";
                                mysqli_query($wp_db_conn, $update);
                            }
                        } else {
                            $insert = "INSERT INTO " . wp_table('usermeta') . " (user_id, meta_key, meta_value) VALUES ($user_id, 'wo_user_id', '$wowonder_user_id')";
                            mysqli_query($wp_db_conn, $insert);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("[wp_update_usermeta] WoWonder mapping check failed: " . $e->getMessage());
        }

        return true;
    }
}

/* --------------------------------------------------------------------------
 * xProfile update (insert or update)
 * -------------------------------------------------------------------------- */
if (!function_exists('wp_update_xprofile_field')) {
    function wp_update_xprofile_field($sqlConnectOrUserId, $maybeUserId = null, $maybeField = null, $maybeValue = null) {
        // Support both signatures:
        // - wp_update_xprofile_field($sqlConnect, $user_id, $field_name, $field_value)
        // - wp_update_xprofile_field($user_id, $field_name, $field_value)   (uses get_wp_db_conn)
        if ($maybeValue === null) {
            // either called with (user_id, field, value) or (sqlConnect, user_id, field)
            if ($maybeField === null) {
                return false; // too few args
            }
            if ($sqlConnectOrUserId instanceof mysqli) {
                $sqlConnect = $sqlConnectOrUserId;
                $user_id = intval($maybeUserId);
                $field_name = $maybeField;
                $field_value = $maybeValue;
            } else {
                // ($user_id, $field_name, $field_value)
                $sqlConnect = get_wp_db_conn();
                $user_id = intval($sqlConnectOrUserId);
                $field_name = $maybeUserId;
                $field_value = $maybeField;
            }
        } else {
            $sqlConnect = $sqlConnectOrUserId;
            $user_id = intval($maybeUserId);
            $field_name = $maybeField;
            $field_value = $maybeValue;
        }

        if (!$sqlConnect || $user_id <= 0) return false;
        $field_name = mysqli_real_escape_string($sqlConnect, $field_name);
        $field_id_query = "SELECT id FROM " . bp_table('xprofile_fields') . " WHERE name = '$field_name' LIMIT 1";
        $field_id_result = mysqli_query($sqlConnect, $field_id_query);
        if (!$field_id_result || mysqli_num_rows($field_id_result) == 0) return false;
        $field_id_row = mysqli_fetch_assoc($field_id_result);
        $field_id = intval($field_id_row['id']);
        $exists_query = "SELECT id FROM " . bp_table('xprofile_data') . " WHERE user_id = $user_id AND field_id = $field_id LIMIT 1";
        $exists_result = mysqli_query($sqlConnect, $exists_query);
        $field_value_esc = mysqli_real_escape_string($sqlConnect, $field_value);

        if ($exists_result && mysqli_num_rows($exists_result) > 0) {
            $row = mysqli_fetch_assoc($exists_result);
            $update = "UPDATE " . bp_table('xprofile_data') . " SET value = '$field_value_esc' WHERE id = {$row['id']}";
            return mysqli_query($sqlConnect, $update);
        } else {
            $insert = "INSERT INTO " . bp_table('xprofile_data') . " (field_id, user_id, value, last_updated) VALUES ($field_id, $user_id, '$field_value_esc', NOW())";
            return mysqli_query($sqlConnect, $insert);
        }
    }
}

/* --------------------------------------------------------------------------
 * QuickDate helpers
 * -------------------------------------------------------------------------- */

/**
 * Get QuickDate user ID by email (compat: qd_get_user_id_by_email / get_quickdate_id_by_email)
 */
if (!function_exists('qd_get_user_id_by_email')) {
    function qd_get_user_id_by_email($email) {
        $conn = get_qd_db_conn();
        if (!$conn) return false;
        $email = $conn->real_escape_string($email);
        $sql = "SELECT id FROM " . QD_USERS_TABLE . " WHERE email = '$email' LIMIT 1";
        $result = $conn->query($sql);
        if (!$result || $result->num_rows == 0) return false;
        $row = $result->fetch_assoc();
        return (int) $row['id'];
    }
}
if (!function_exists('get_quickdate_id_by_email')) {
    function get_quickdate_id_by_email($email) { return qd_get_user_id_by_email($email); }
}

/**
 * Get QuickDate table columns (cached)
 */
if (!function_exists('qd_get_columns')) {
    function qd_get_columns($conn, $table) {
        static $cache = [];
        if (isset($cache[$table])) return $cache[$table];
        $columns = [];
        $result = $conn->query("SHOW COLUMNS FROM `$table`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $cache[$table] = $columns;
        }
        return $columns;
    }
}

/**
 * Update QuickDate user by email (fields must be valid columns)
 * Signature kept: qd_update_user($email, $data)
 */
if (!function_exists('qd_update_user')) {
    function qd_update_user($email, $data) {
        $conn = get_qd_db_conn();
        if (!$conn) return false;
        $user_id = qd_get_user_id_by_email($email);
        if (!$user_id) return false;
        $columns = qd_get_columns($conn, QD_USERS_TABLE);
        $set = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) {
                error_log("[qd_update_user] Skipping unknown field: $key");
                continue;
            }
            $escaped_value = $conn->real_escape_string($value);
            $set[] = "`$key` = '$escaped_value'";
        }
        if (empty($set)) {
            error_log("[qd_update_user] No valid fields to update for user $user_id");
            return false;
        }
        $sql = "UPDATE `" . QD_USERS_TABLE . "` SET " . implode(',', $set) . " WHERE id = $user_id";
        $result = $conn->query($sql);
        if ($result) return true;
        error_log("[qd_update_user] Update failed: " . $conn->error);
        return false;
    }
}

/* --------------------------------------------------------------------------
 * WoWonder helpers
 * -------------------------------------------------------------------------- */

/**
 * Get WoWonder user id by email
 */
if (!function_exists('ww_get_user_id_by_email')) {
    function ww_get_user_id_by_email($email) {
        $conn = get_wowonder_db();
        if (!$conn) return false;
        $email = mysqli_real_escape_string($conn, $email);
        $sql = "SELECT user_id FROM Wo_Users WHERE email = '$email' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['user_id'];
        }
        return false;
    }
}

/* --------------------------------------------------------------------------
 * Cross-platform sync functions
 * -------------------------------------------------------------------------- */

/**
 * Sync QuickDate user array to WordPress (usermeta + xprofile) and WoWonder.
 * Signature preserved: sync_quickdate_to_wordpress($quickdate_user)
 */
if (!function_exists('sync_quickdate_to_wordpress')) {
    function sync_quickdate_to_wordpress($quickdate_user) {
        $wpDb = get_wp_db_conn();
        if (!$wpDb) return false;

        // Load metadata
        $metadata = get_user_field_metadata();
        $wp_usermeta_fields = array_keys($metadata['private_secure_fields']);
        $wp_xprofile_fields = array_keys($metadata['public_open_fields']);

        // Determine target WP user
        $wp_user_id = isset($quickdate_user['wp_user_id']) ? intval($quickdate_user['wp_user_id']) : 0;
        if (!$wp_user_id && !empty($quickdate_user['email'])) {
            $wp_user = wp_get_user_by_login_or_email($wpDb, $quickdate_user['email']);
            if ($wp_user && isset($wp_user['ID'])) {
                $wp_user_id = intval($wp_user['ID']);
            }
        }
        if (!$wp_user_id) return false;

        // Update usermeta (skip password)
        foreach ($wp_usermeta_fields as $meta_key) {
            if ($meta_key === 'password' || $meta_key === 'user_pass') continue; // skip password
            if (isset($quickdate_user[$meta_key])) {
                $meta_value = $quickdate_user[$meta_key];
                wp_update_usermeta($wpDb, $wp_user_id, $meta_key, $meta_value);
            }
        }

        // Update xprofile (skip password)
        foreach ($wp_xprofile_fields as $xprofile_field) {
            if ($xprofile_field === 'password' || $xprofile_field === 'user_pass') continue; // skip password
            if (isset($quickdate_user[$xprofile_field])) {
                $field_value = $quickdate_user[$xprofile_field];
                wp_update_xprofile_field($wpDb, $wp_user_id, $xprofile_field, $field_value);
            }
        }

        // Also sync to WoWonder
        sync_quickdate_to_wowonder($quickdate_user);

        return true;
    }
}

/**
 * Sync QuickDate user array to WoWonder DB (safe version)
 */
if (!function_exists('sync_quickdate_to_wowonder')) {
    function sync_quickdate_to_wowonder($quickdate_user) {
        $wwDb = get_wowonder_db();
        if (!$wwDb) return false;
        $metadata = get_user_field_metadata();
        $ww_private_fields = $metadata['private_secure_fields'];
        $ww_public_fields = $metadata['public_open_fields'];

        $ww_user_id = isset($quickdate_user['wo_user_id']) ? (int)$quickdate_user['wo_user_id'] : ww_get_user_id_by_email($quickdate_user['email'] ?? '');
        if (!$ww_user_id) {
            error_log("[sync_quickdate_to_wowonder] Could not find WoWonder user for QuickDate email: " . ($quickdate_user['email'] ?? ''));
            return false;
        }

        // === NEW: Load/cached WoWonder user schema ===
        $schema_cache_folder = $_SERVER['DOCUMENT_ROOT'] . '/data/schema_cache/';
        $schema_cache_file   = $schema_cache_folder . 'wo_users_schema.json';
        if (!is_dir($schema_cache_folder)) @mkdir($schema_cache_folder, 0755, true);
        static $wo_schema = null;
        if ($wo_schema === null) {
            if (file_exists($schema_cache_file)) {
                $wo_schema = json_decode(file_get_contents($schema_cache_file), true) ?: [];
            } else {
                $wo_schema = [];
                $q = $wwDb->query("SHOW COLUMNS FROM Wo_Users");
                while ($row = $q && $q->fetch_assoc()) $wo_schema[$row['Field']] = true;
                @file_put_contents($schema_cache_file, json_encode($wo_schema));
            }
        }
        // === END schema load ===

        $set_clause = [];
        // Use $ww_schema to only update fields present in the table
        foreach ($ww_private_fields as $qd_key => $ww_key) {
            if (!isset($wo_schema[$ww_key])) {
                error_log("[sync_quickdate_to_wowonder] Skipping (not in Wo_Users): $ww_key");
                continue;
            }
            if (isset($quickdate_user[$qd_key])) {
                $value = $wwDb->real_escape_string($quickdate_user[$qd_key]);
                $set_clause[] = "`$ww_key` = '$value'";
            }
        }
        foreach ($ww_public_fields as $qd_key => $ww_key) {
            if (!isset($wo_schema[$ww_key])) {
                error_log("[sync_quickdate_to_wowonder] Skipping (not in Wo_Users): $ww_key");
                continue;
            }
            if (isset($quickdate_user[$qd_key])) {
                $value = $wwDb->real_escape_string($quickdate_user[$qd_key]);
                $set_clause[] = "`$ww_key` = '$value'";
            }
        }
        if (empty($set_clause)) {
            error_log("[sync_quickdate_to_wowonder] No fields to update for user_id $ww_user_id");
            return false;
        }
        $set_clause_str = implode(', ', $set_clause);
        $sql = "UPDATE Wo_Users SET $set_clause_str WHERE user_id = $ww_user_id LIMIT 1";
        $result = $wwDb->query($sql);
        if ($result) {
            error_log("[sync_quickdate_to_wowonder] Updated WoWonder user_id $ww_user_id");
            return true;
        } else {
            error_log("[sync_quickdate_to_wowonder] Failed to update WoWonder user_id $ww_user_id: " . $wwDb->error . " SQL: $sql");
            return false;
        }
    }
}

/**
 * Called by WordPress after updating usermeta/xprofile -> will sync to QuickDate
 * Signature: sync_user_to_quickdate($wp_user_email, $usermeta_data = [], $xprofile_data = [])
 */
// Replacement snippet: updated sync_user_to_quickdate function
// Only the function body is shown here — replace the existing function in shared/wwqd_bridge.php
if (!function_exists('sync_user_to_quickdate')) {
    function sync_user_to_quickdate($wp_user_email, $usermeta_data = [], $xprofile_data = []) {
        $qd_data = [];

        // Helper to detect absolute http/https URLs
        $is_abs = function($u) {
            return (bool) preg_match('#^https?://#i', (string)$u);
        };

        // Normalize and prefer xprofile values for profile details
        foreach ($xprofile_data as $key => $value) {
            // normalize value
            $val = $value;
            if (is_array($val) || is_object($val)) {
                // keep original behaviour: serialize/convert if needed by callers elsewhere
                // but for syncing we want string-like values
                $val = is_string($val) ? $val : json_encode($val);
            }
            $val = trim((string)$val);

            if ($val === '') continue;

            // Special handling for avatar/cover fields:
            // - If value already starts with http(s):// (absolute), leave untouched.
            // - If it is a protocol-relative URL (//host/...), leave untouched.
            // - If it already uses the ../streams/ convention, leave untouched.
            // - Otherwise prefix the ../streams/ relative path as before.
            if (in_array($key, ['avatar', 'cover'], true)) {
                if ($is_abs($val) || strpos($val, '//') === 0) {
                    // leave $val as-is
                } else {
                    $val = '/../' . ltrim($val, '/');
                }
            }

            $qd_data[$key] = $val;
        }

        // Include usermeta values only if not already set (xprofile takes precedence)
        foreach ($usermeta_data as $key => $value) {
            if (isset($qd_data[$key])) continue;
            $val = $value;
            if (is_array($val) || is_object($val)) {
                $val = is_string($val) ? $val : json_encode($val);
            }
            $val = trim((string)$val);
            if ($val === '') continue;

            if (in_array($key, ['avatar', 'cover'], true)) {
                if ($is_abs($val) || strpos($val, '//') === 0) {
                    // leave as-is
                } else {
                    $val = '/../' . ltrim($val, '/');
                }
            }

            $qd_data[$key] = $val;
        }

        if (empty($qd_data)) {
            error_log("[sync_user_to_quickdate] No data to sync for $wp_user_email");
            return false;
        }

        return qd_update_user($wp_user_email, $qd_data);
    }
}

/**
 * Generic database update function for both WoWonder and QuickDate
 */
if (!function_exists('do_platform_update')) {
    /**
     * Origin-aware do_platform_update
     * See proposal: only updates columns supported by target, optionally creating WP xProfile fields
     */
    function do_platform_update($conn, $table, $id_field, $id, $fields, $platform = 'General', $origin = 'WordPress') {
        if (!$conn || !$table || !$id_field || !$id || !is_array($fields) || empty($fields)) return false;

        $allowed = [];
        foreach ($fields as $k => $v) {
            if ($v === null) continue;
            $should = _wwqd_can_update_target($origin, $conn, $table, $k);
            if (!$should) continue;
            $allowed[$k] = $v;
        }

        // If origin is WordPress, enforce a whitelist and exclude sensitive keys
        if (strtolower(trim((string)$origin)) === 'wordpress') {
            $maps = _wwqd_field_maps();
            $whitelist = array_merge(array_keys($maps['public_open_fields']), array_keys($maps['private_secure_fields']));
            $sensitive = _wwqd_sensitive_fields();
            foreach ($allowed as $k => $_v) {
                if (!in_array($k, $whitelist, true) || in_array($k, $sensitive, true)) {
                    wwqd_debug("Dropping WP-origin field '{$k}' because it's not whitelisted or is sensitive", 'do_platform_update');
                    unset($allowed[$k]);
                }
            }
        }

        if (empty($allowed)) {
            return ['success' => false, 'reason' => 'no_supported_fields'];
        }

        $set_parts = [];
        $types = '';
        $values = [];
        foreach ($allowed as $k => $v) {
            $set_parts[] = "`" . $conn->real_escape_string($k) . "` = ?";
            $types .= (is_int($v) || (is_string($v) && ctype_digit($v))) ? 'i' : 's';
            $values[] = $v;
        }

        $set_sql = implode(', ', $set_parts);
        $sql = "UPDATE `{$table}` SET {$set_sql} WHERE `{$id_field}` = ?";
        $stmt = @ $conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'reason' => 'prepare_failed', 'error' => $conn->error];
        }

        $types .= (is_int($id) || (is_string($id) && ctype_digit($id))) ? 'i' : 's';
        $bind_params = array_merge([$types], $values, [$id]);

        // mysqli bind_param requires references
        $refs = [];
        foreach ($bind_params as $i => $p) $refs[$i] = &$bind_params[$i];
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        return ['success' => (bool)$ok, 'error' => $err ?: ''];
    }
}

/**
 * Unified sync function to update user data across all platforms
 */
/* Replacement: sync_wp_user_to_platforms() — whitelist-only, lock-wrapped, debug-enabled.
   This function intentionally only forwards username, email and public xProfile fields.
*/
if (!function_exists('sync_wp_user_to_platforms')) {
    function sync_wp_user_to_platforms($user_id, $sync_type = 'both') {
        if (empty($user_id)) return false;

        // Acquire a WordPress-origin per-user lock to prevent recursion/races
        if (function_exists('_wwqd_acquire_sync_lock')) {
            if (!_wwqd_acquire_sync_lock('WordPress', (int)$user_id)) {
                wwqd_debug('sync_wp_user_to_platforms_locked', ['user'=>$user_id]);
                return false;
            }
        }

        try {
            // Get runtime user info if available
            $user_login = null;
            $user_email = null;
            if (function_exists('get_userdata')) {
                $u = @get_userdata($user_id);
                if ($u) {
                    $user_login = $u->user_login ?? null;
                    $user_email = $u->user_email ?? null;
                }
            }

            // DB fallback if runtime not available
            if ((empty($user_login) || empty($user_email)) && function_exists('get_wp_db_conn')) {
                $wp_conn = get_wp_db_conn();
                if ($wp_conn) {
                    $users_table = (defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_') . 'users';
                    $users_table = $wp_conn->real_escape_string($users_table);
                    $q = "SELECT ID, user_login, user_email FROM `{$users_table}` WHERE ID = " . intval($user_id) . " LIMIT 1";
                    $r = @$wp_conn->query($q);
                    if ($r && $r->num_rows > 0) {
                        $row = $r->fetch_assoc();
                        $user_login = $row['user_login'];
                        $user_email = $row['user_email'];
                    }
                }
            }

            if (empty($user_login) || empty($user_email)) {
                wwqd_debug('sync_wp_user_to_platforms_missing_user', ['user_id'=>$user_id]);
                return false;
            }

            $maps = _wwqd_field_maps();
            $public_fields_map = is_array($maps['public_open_fields']) ? $maps['public_open_fields'] : [];
            $sensitive = _wwqd_sensitive_fields();

            // Build a strict whitelist payload
            $payload = [
                'username' => (string)$user_login,
                'email'    => (string)$user_email
            ];

            // Only include public xProfile fields defined in metadata (defensive)
            foreach (array_keys($public_fields_map) as $field) {
                $kl = strtolower((string)$field);
                if (in_array($kl, $sensitive, true)) continue;

                $val = null;
                if (function_exists('xprofile_get_field_data')) {
                    $val = @xprofile_get_field_data($field, $user_id);
                }

                // DB fallback to xprofile storage if runtime not available
                if (($val === null || $val === '') && function_exists('get_wp_db_conn')) {
                    $wp_conn = get_wp_db_conn();
                    if ($wp_conn) {
                        $fields_table = (defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_') . 'bp_xprofile_fields';
                        $data_table   = (defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_') . 'bp_xprofile_data';
                        $f_safe = $wp_conn->real_escape_string($field);
                        $fid_r = @ $wp_conn->query("SELECT id FROM `{$fields_table}` WHERE `name` = '{$f_safe}' LIMIT 1");
                        if ($fid_r && $fid_r->num_rows > 0) {
                            $fr = $fid_r->fetch_assoc();
                            $field_id = (int)$fr['id'];
                            $val_r = @ $wp_conn->query("SELECT `value` FROM `{$data_table}` WHERE `field_id` = {$field_id} AND `user_id` = " . intval($user_id) . " LIMIT 1");
                            if ($val_r && $val_r->num_rows > 0) {
                                $vr = $val_r->fetch_assoc();
                                $val = $vr['value'];
                            }
                        }
                    }
                }

                if ($val !== null && $val !== '') {
                    if ($field === 'avatar' && function_exists('normalize_avatar_url')) {
                        $val = normalize_avatar_url($val);
                    }
                    $payload[$field] = $val;
                }
            }

            // Defensive removal of any password or auth-like keys
            foreach (['_password','password','user_pass','pass'] as $forbidden) {
                if (isset($payload[$forbidden])) unset($payload[$forbidden]);
            }

            wwqd_debug('sync_wp_user_to_platforms_payload', ['user'=>$user_id, 'payload_keys'=>array_keys($payload)]);

            // Send only to mapped targets (if mapping helpers present)
            $wowonder_id = null;
            if (function_exists('ww_get_user_id_by_email')) {
                $wowonder_id = @ww_get_user_id_by_email($user_email);
            }
            if ($wowonder_id) {
                @do_platform_update(get_wowonder_db(), 'Wo_Users', 'user_id', $wowonder_id, $payload, 'WoWonder', 'WordPress');
            }

            $qd_id = null;
            if (function_exists('qd_get_user_id_by_email')) {
                $qd_id = @qd_get_user_id_by_email($user_email);
            }
            if ($qd_id) {
                @do_platform_update(get_qd_db_conn(), defined('QD_USERS_TABLE') ? QD_USERS_TABLE : 'users', 'id', $qd_id, $payload, 'QuickDate', 'WordPress');
            }

            return true;
        } finally {
            if (function_exists('_wwqd_release_sync_lock')) {
                _wwqd_release_sync_lock('WordPress', (int)$user_id);
            }
        }
    }
}

/* --------------------------------------------------------------------------
 * Helper: get_quickdate_user_id_by_wp_email (compat)
 * -------------------------------------------------------------------------- */
if (!function_exists('get_quickdate_user_id_by_wp_email')) {
    function get_quickdate_user_id_by_wp_email($wp_user_id) {
        $wp_db = get_wp_db_conn();
        $wp_user_id = (int) $wp_user_id;
        if (!$wp_db || $wp_user_id <= 0) {
            error_log("[get_quickdate_user_id_by_wp_email] Invalid parameters.");
            return false;
        }
        $query = "SELECT user_email FROM " . wp_table('users') . " WHERE ID = $wp_user_id LIMIT 1";
        $result = mysqli_query($wp_db, $query);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $wp_user_email = mysqli_real_escape_string($wp_db, $row['user_email']);
        } else {
            error_log("[get_quickdate_user_id_by_wp_email] Failed to retrieve WP email for user_id $wp_user_id");
            return false;
        }
        $qd_db = get_qd_db_conn();
        if (!$qd_db) {
            error_log("[get_quickdate_user_id_by_wp_email] Failed to connect to QuickDate DB");
            return false;
        }
        $query_qd = "SELECT id FROM users WHERE email = '$wp_user_email' LIMIT 1";
        $result_qd = mysqli_query($qd_db, $query_qd);
        if ($result_qd && $row_qd = mysqli_fetch_assoc($result_qd)) {
            return (int)$row_qd['id'];
        } else {
            error_log("[get_quickdate_user_id_by_wp_email] No QuickDate user found with email $wp_user_email");
            return false;
        }
    }
}

/**
 * Helper function to normalize avatar URLs
 */
if (!function_exists('normalize_avatar_url')) {
    function normalize_avatar_url($url) {
        $val = trim($url);
        if (!preg_match('/^https?:\/\//i', $val)) {
            $val = rtrim(home_url(), '/') . '/' . ltrim($val, '/');
        }
        if (!preg_match('/\.(jpg|jpeg|png|gif)(\?|$)/i', $val)) {
            $val .= '.png';
        }
        return $val;
    }
}

/* --------------------------------------------------------------------------
 * Endpoint / simple router for remote systems (QuickDate, WoWonder) to call
 * - Authenticated by BUZZ_SSO_SECRET (from environment via db_helpers)
 * - Supports single and bulk operations
 *
 * Actions:
 *  - sync_quickdate_to_wordpress      (POST) body: { quickdate_user: { ... } }
 *  - bulk_sync_quickdate              (POST) body: { users: [ { ... }, ... ] }
 *  - qd_update_user                   (POST) body: { email: "...", data: { ... } }
 *  - qd_bulk_update                   (POST) body: { updates: [ { email: "...", data: { ... } }, ... ] }
 *
 * NOTE: This endpoint is optional. If you prefer to wire your own endpoints or call functions directly,
 *       you can skip using the HTTP interface and call the functions above from other code.
 * -------------------------------------------------------------------------- */

function _wwqd_get_request_secret() {
    // Accept secret in POST 'sso_secret' or Authorization: Bearer <secret>
    $secret = null;
    if (!empty($_POST['sso_secret'])) $secret = $_POST['sso_secret'];
    if (!$secret && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $secret = trim($matches[1]);
        }
    }
    // Also accept JSON body request with sso_secret when content-type is application/json
    if (!$secret && in_array($_SERVER['CONTENT_TYPE'] ?? '', ['application/json', 'application/json; charset=utf-8'])) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['sso_secret'])) $secret = $data['sso_secret'];
    }
    return $secret;
}

function _wwqd_validate_secret($provided) {
    if (!defined('BUZZ_SSO_SECRET')) return false;
    if (!$provided) return false;
    return hash_equals((string)BUZZ_SSO_SECRET, (string)$provided);
}

// Only run HTTP router when accessed directly (not included) and request has action
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $action = $_REQUEST['action'] ?? ($_GET['action'] ?? null);
    if ($action) {
        // Validate secret
        $provided = _wwqd_get_request_secret();
        if (!_wwqd_validate_secret($provided)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        switch ($action) {
            case 'sync_quickdate_to_wordpress':
                if (empty($payload['quickdate_user'])) {
                    echo json_encode(['success' => false, 'error' => 'Missing quickdate_user']);
                    exit;
                }
                $ok = sync_quickdate_to_wordpress($payload['quickdate_user']);
                echo json_encode(['success' => (bool)$ok]);
                exit;

            case 'bulk_sync_quickdate':
                if (empty($payload['users']) || !is_array($payload['users'])) {
                    echo json_encode(['success' => false, 'error' => 'Missing users array']);
                    exit;
                }
                $results = [];
                foreach ($payload['users'] as $u) {
                    $results[] = (bool) sync_quickdate_to_wordpress($u);
                }
                echo json_encode(['success' => true, 'results' => $results]);
                exit;

            case 'qd_update_user':
                if (empty($payload['email']) || empty($payload['data'])) {
                    echo json_encode(['success' => false, 'error' => 'Missing email or data']);
                    exit;
                }
                $ok = qd_update_user($payload['email'], $payload['data']);
                echo json_encode(['success' => (bool)$ok]);
                exit;

            case 'qd_bulk_update':
                if (empty($payload['updates']) || !is_array($payload['updates'])) {
                    echo json_encode(['success' => false, 'error' => 'Missing updates array']);
                    exit;
                }
                $results = [];
                foreach ($payload['updates'] as $u) {
                    if (empty($u['email']) || empty($u['data'])) { $results[] = false; continue; }
                    $results[] = (bool) qd_update_user($u['email'], $u['data']);
                }
                echo json_encode(['success' => true, 'results' => $results]);
                exit;

            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
                exit;
        }
    }
}

/* --------------------------- Cache-clear & redirect helper --------------------------- */
/**
 * Emit a small HTML page + JS that:
 *  - clears cookies for .buzzjuice.net
 *  - clears caches, service workers, storage, indexedDB
 *  - then navigates the browser to $redirect_to (absolute URL) when provided,
 *    or to the WordPress homepage by default.
 *
 * Usage:
 *   bz_clear_cache_after_logout_html(); // finalizes logout & redirects to https://buzzjuice.net/
 *   bz_clear_cache_after_logout_html('https://buzzjuice.net/shared/sso-logout.php?sso_secret=...&from=wowonder&logged_out=1');
 *
 * Note: This function echoes HTML and exits the script.
 */
if (!function_exists('bz_clear_cache_after_logout_html')) {
    /**
     * Emit HTML/JS to clear client storage/caches/cookies and then redirect to $nextUrl.
     *
     * @param string $nextUrl Absolute URL to redirect to after clearing (default: site home).
     * @param int    $delayMs Milliseconds fallback delay before forcing location change (default: 150)
     */
    function bz_clear_cache_after_logout_html(string $nextUrl = 'https://buzzjuice.net/', int $delayMs = 150) {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Expires: 0');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
        }

        $next = filter_var($nextUrl, FILTER_VALIDATE_URL) ? $nextUrl : 'https://buzzjuice.net/';
        $delay = max(0, (int)$delayMs);
        $sharedDomain = '.buzzjuice.net';

        // JSON-encode values for safe inlining into JS
        $safeNextJs = json_encode($next, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $safeDomainJs = json_encode($sharedDomain);
        $safeDelayJs = json_encode($delay);

        echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0" />
<meta http-equiv="Pragma" content="no-cache" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Signing out…</title>
</head>
<body>
<script>
(function(next, domain, delay){
  try {
    // Delete cookies for current host and shared domain
    var cookies = (document.cookie || '').split('; ');
    for (var i = 0; i < cookies.length; i++) {
      var parts = cookies[i].split('=');
      var name = parts.shift();
      if (!name) continue;
      // Attempt to remove cookie for both path variants and the shared domain
      try { document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;"; } catch(e){}
      try { document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;domain=" + domain + ";"; } catch(e){}
      try { document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;domain=" + location.hostname + ";"; } catch(e){}
    }

    // Clear caches (if supported)
    if ('caches' in window && caches.keys) {
      caches.keys().then(function(names){ for (var n of names) caches.delete(n); }).catch(function(){});
    }
    // Unregister service workers
    if ('serviceWorker' in navigator && navigator.serviceWorker.getRegistrations) {
      navigator.serviceWorker.getRegistrations().then(function(regs){ for (var r of regs) r.unregister(); }).catch(function(){});
    }
    // Storage
    try { localStorage.clear(); } catch(e) {}
    try { sessionStorage.clear(); } catch(e) {}
    // IndexedDB (best-effort)
    try {
      if (indexedDB && indexedDB.databases) {
        indexedDB.databases().then(function(dbs){ dbs.forEach(function(db){ try { indexedDB.deleteDatabase(db.name); } catch(e){} }); }).catch(function(){});
      }
    } catch(e) {}

  } catch(e) {
    // Defensive - ignore errors during client cleanup
  }

  // Always navigate to next immediately (replace avoids history entry)
  try { window.location.replace(next); } catch(e) { window.location.href = next; }
  // Also set a fallback timed navigation to handle rare replace failures
  setTimeout(function(){ try { window.location.href = next; } catch(e){} }, delay || 150);

  // If page is shown from bfcache, force a reload that will re-run this script
  window.onpageshow = function(ev) { if (ev && ev.persisted) { try { window.location.replace(next); } catch(e){} } };
})({$safeNextJs}, {$safeDomainJs}, {$safeDelayJs});
</script>

<noscript><meta http-equiv="refresh" content="0;url={$next}" /></noscript>

<h2>Signing out…</h2>
<p>If you are not automatically redirected, <a href="{$next}">click here to continue</a>.</p>
</body>
</html>
HTML;
        exit;
    }
}

/* --------------------------------------------------------------------------
 * End of file
 * -------------------------------------------------------------------------- */