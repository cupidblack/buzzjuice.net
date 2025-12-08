<?php
/**
 * ww-sso-bridge.php  (updated v4.09 -> 2025-09-23)
 *
 * Purpose:
 *  - Bridge WordPress canonical session (shadow + buzz_sso) into WoWonder runtime.
 * Full replacement implementing robust last_url preservation and redirect precedence:
 *  * session reconciliation and safe redirect_to passthrough.
 *  - Preserve last_url passed from Welcome page (GET) — content.phtml should append it to WordpressLoginUrl.
 *  - Accept last_url from POST (JS bridge), GET, COOKIE, HTTP_REFERER, or derive from REQUEST_URI (validated).
 *  - Auto-registering a new WoWonder user sets $_SESSION['wo_auto_registered']=1 so the redirect goes to start-up.
 *  - Redirect precedence:
 *      1) newly auto-registered users -> index.php?link1=start-up
 *      2) membership override (go-pro) if configured and user is not pro
 *      3) explicit posted last_url (validated same-site)
 *      4) computed last_url fallback
 *      5) default site_url/?cache=...
 *
 *  - Provide a lightweight "check" endpoint for client-side guards.
 *  - Provide the do_login endpoint which accepts a short-lived SSO password token (WPSSO.v1)
 *    and hydrates/creates a WoWonder session and redirects the browser to the appropriate location.
 *  - Attempt to reconcile mismatched local session vs WordPress canonical session (best-effort).
 *  - Create canonical WP shadow session files where needed for other apps.
 *
 * Important notes:
 *  - This script deliberately avoids modifying the WordPress PHPSESSID cookie.
 *  - When called with ?redirect_to=go-pro the bridge will attempt to redirect back to index.php?link1=go-pro
 *    after hydration (this overrides the membership/start-up redirect rules when redirect_to is present).
 *
 * Usage:
 *  - To run full server-side hydration: GET /ww-sso-bridge.php?redirect_to=go-pro
 *  - To POST SSO login (internal): POST /ww-sso-bridge.php?sso_action=do_login
 *  - To check state from client-side: GET /ww-sso-bridge.php?sso_action=check
 *  - WordPress remains the PHPSESSID owner. This bridge uses the shadow/session values only.
 *  - buzz_sso cookie is authoritative; when missing/invalid we clear SSO keys and redirect to logout.
 *  - The file preserves previous mapping/registration logic and only adds the last_url handling and new-user marker.
 *
 * Updated: 2025-09-17 — added session reconciliation:
 *  - If an active WoWonder session exists but does not match WordPress' canonical session name/id/shadow,
 *    the WoWonder cookie session is destroyed and a fresh local session is created and rehydrated using
 *    WordPress canonical values (buzz_sso cookie payload or shadow session file). IMPORTANT: do not touch
 *    or modify the WordPress PHPSESSID cookie in any way — WP owns that cookie.
 *
 * Further update (2025-09-23) — added canonical shadow write:
 *  - When a WordPress canonical shadow id can be determined from the buzz_sso payload, the bridge will:
 *      1) remove any WoWonder-side shadow files that reference the same WP user but are for a different shadow id,
 *      2) create the canonical shadow file sess_shadow_{shadow_<wp_sid>} (and .ser/.json siblings) using a
 *         minimal, compatible payload so other apps can pick it up,
 *      3) log actions (bridge log).
 *    This implements the requested reconciliation: if the WoWonder shadow session id differs from the WP one,
 *    destroy the wrong one and create the canonical shadow id file.
 */

require_once __DIR__ . '/assets/init.php';
require_once __DIR__ . '/../shared/db_helpers.php'; // adjust if shared path differs

// Insert after the other require_once lines near the top of the file
require_once __DIR__ . '/../shared/sso_bridge_helpers.php';

// -----------------------------
// Config & defaults
// -----------------------------
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/ww_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);

// -----------------------------
// Helpers: logging & debug
// -----------------------------
function bz_is_debug() {
    return (bool)((isset($_GET['sso_debug']) && $_GET['sso_debug'] === '1') || (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG));
}
function bz_bridge_log($msg, $ctx = []) {
    $is_debug = bz_is_debug();
    $data = [
        'ts' => gmdate('Y-m-d H:i:s'),
        'sid' => (function_exists('session_id') ? session_id() : null),
        'sname' => (function_exists('session_name') ? session_name() : null),
        'buzz_sso_len' => isset($_COOKIE[BUZZ_SSO_COOKIE]) ? strlen($_COOKIE[BUZZ_SSO_COOKIE]) : 0,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ];
    if ($is_debug) {
        $data['cookies'] = $_COOKIE ?? [];
        $data['session'] = $_SESSION ?? [];
        $data['server'] = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'HTTPS' => $_SERVER['HTTPS'] ?? null,
        ];
        $data['sess_cookie_params'] = function_exists('session_get_cookie_params') ? session_get_cookie_params() : null;
    }
    if ($ctx) $data['ctx'] = $ctx;
    $line = '['.$data['ts'].'] '.$msg.' | '.json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
    @file_put_contents(BUZZ_SSO_BRIDGE_LOG, $line, FILE_APPEND);
}

function bz_debug_page($title, $blocks = []) {
    if (!bz_is_debug()) return;
    header('Content-Type: text/html; charset=utf-8');
    echo "<!doctype html><meta charset='utf-8'><title>SSO Bridge Debug</title>";
    echo "<style>body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#0b1020;color:#e9eef7;padding:24px} .blk{background:#131a33;margin:16px 0;padding:12px;border-radius:10px} pre{white-space:pre-wrap}</style>";
    echo "<h2>SSO Bridge Debug — ".htmlspecialchars($title, ENT_QUOTES)."</h2>";
    $default = [ 'SESSION' => $_SESSION ?? [], 'COOKIES' => $_COOKIE ?? [], 'SERVER' => ['HTTP_HOST'=> $_SERVER['HTTP_HOST'] ?? null, 'REQUEST_URI'=> $_SERVER['REQUEST_URI'] ?? null, 'REMOTE_ADDR'=> $_SERVER['REMOTE_ADDR'] ?? null] ];
    $blocks = array_merge($blocks, $default);
    foreach ($blocks as $k => $v) {
        echo "<div class='blk'><strong>".htmlspecialchars($k)."</strong><pre>", htmlspecialchars(print_r($v, true)), "</pre></div>";
    }
    exit;
}

// client-side debug beacon receiver
if (!empty($_GET['sso_client_log']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    @file_put_contents(BUZZ_SSO_BRIDGE_LOG, '['.gmdate('Y-m-d H:i:s').'] CLIENT ' . $raw . PHP_EOL, FILE_APPEND);
    http_response_code(204); exit;
}

// detect runaway loops early
$loop_count = function_exists('bz_bridge_loop_count') ? bz_bridge_loop_count(true) : 0;
if ($loop_count > 4) {
    bz_bridge_log('bridge loop suspected: breaking loop, using site base', ['loop_count' => $loop_count]);
    // clear the loop counter and force fallback behavior later
    if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
    $forced_last_url_fallback = true;
} else {
    $forced_last_url_fallback = false;
}

// -----------------------------
// Token helpers (b64url, sign/verify)
// -----------------------------
function _bz_b64url_decode($str) {
    $p = strtr($str, '-_', '+/');
    $m = strlen($p) % 4; if ($m) $p .= str_repeat('=', 4 - $m);
    return base64_decode($p);
}
function _bz_b64url_encode($bin) {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}
function bz_parse_sso_password_token($token, $secret) {
    if (!$token || !$secret) return false;
    if (strpos($token, 'WPSSO.v1.') !== 0) return false;
    $body = substr($token, strlen('WPSSO.v1.'));
    $parts = explode('.', $body, 2);
    if (count($parts) !== 2) return false;
    $json = _bz_b64url_decode($parts[0]);
    $sig  = _bz_b64url_decode($parts[1]);
    if ($json === false || $sig === false) return false;
    $calc = hash_hmac('sha256', $json, (string)$secret, true);
    if (!hash_equals($calc, $sig)) return false;
    $payload = json_decode($json, true);
    if (!is_array($payload)) return false;
    if (!empty($payload['exp']) && time() > (int)$payload['exp']) return false;
    return $payload;
}
function bz_sso_verify_token($token, $secret) {
    if (!$token || !$secret) return false;
    if (strpos($token, 'WPSSO.v1.') === 0) return bz_parse_sso_password_token($token, $secret);
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return false;
    $json = _bz_b64url_decode($parts[0]);
    $sig  = _bz_b64url_decode($parts[1]);
    if ($json === false || $sig === false) return false;
    $calc = hash_hmac('sha256', $json, (string)$secret, true);
    if (!hash_equals($calc, (string)$sig)) return false;
    $payload = json_decode($json, true);
    if (!is_array($payload)) return false;
    if (!empty($payload['exp']) && time() > (int)$payload['exp']) return false;
    return $payload;
}
function bz_issue_buzz_sso_cookie($payload, $secret, $override = []) {
    $now = time();
    $merged = array_merge($payload, $override);
    $merged['iat'] = $now;
    $merged['exp'] = min(($payload['exp'] ?? $now + BUZZ_SSO_TTL), $now + BUZZ_SSO_TTL);
    $json = json_encode($merged);
    $sig  = hash_hmac('sha256', $json, (string)$secret, true);
    $token = _bz_b64url_encode($json) . '.' . _bz_b64url_encode($sig);
    setcookie(BUZZ_SSO_COOKIE, $token, $merged['exp'], '/', BUZZ_COOKIE_DOMAIN, true, true);
    $_COOKIE[BUZZ_SSO_COOKIE] = $token;
    return $token;
}
function bz_build_sso_password_token($wo_user_id, $wp_user_id, $wp_user_login, $wp_user_email, $secret) {
    $claims = [
        'ver' => 1,
        'wo_user_id' => (int)$wo_user_id,
        'wp_user_id' => (int)$wp_user_id,
        'wp_user_login' => (string)$wp_user_login,
        'wp_user_email' => (string)$wp_user_email,
        'iat' => time(),
        'exp' => time() + BUZZ_SSO_TTL,
        'nonce' => bin2hex(random_bytes(8)),
    ];
    $json = json_encode($claims);
    $sig  = hash_hmac('sha256', $json, (string)$secret, true);
    return 'WPSSO.v1.' . _bz_b64url_encode($json) . '.' . _bz_b64url_encode($sig);
}

// -----------------------------
// Session reconciliation helpers
// (these functions attempt to read WP shadow files, write canonical shadow, reconcile mismatch)
// -----------------------------
// Parse buzz_sso cookie payload without requiring secret (best-effort).
function bz_parse_buzz_sso_cookie_payload($token, $secret = null) {
    if (!$token) return null;
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    $json = _bz_b64url_decode($parts[0]);
    $sig  = _bz_b64url_decode($parts[1]);
    if ($json === false) return null;
    if ($secret) {
        $calc = hash_hmac('sha256', $json, (string)$secret, true);
        if (!hash_equals($calc, (string)$sig)) {
            bz_bridge_log('buzz_sso cookie HMAC mismatch (bridge)', ['token_preview' => substr($token,0,24)]);
            return null;
        }
    }
    $payload = @json_decode($json, true);
    if (!is_array($payload)) return null;
    return $payload;
}

// Try to find WP shadow session payload for wp_sid in common shared locations.
function bz_find_wp_shadow_payload($wp_session_id) {
    if (!$wp_session_id) return null;
    $dirs = [
        __DIR__ . '/../shared/sso_sessions',
        __DIR__ . '/../../shared/sso_sessions',
        __DIR__ . '/shared/sso_sessions',
        dirname(__DIR__, 2) . '/shared/sso_sessions',
        dirname(__DIR__, 3) . '/shared/sso_sessions',
    ];
    $derived = 'shadow_' . $wp_session_id;
    $filenames = [
        'sess_' . $derived,
        'sess_' . $derived . '.ser',
        'sess_' . $derived . '.json',
    ];
    foreach ($dirs as $dir) {
        foreach ($filenames as $fn) {
            $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fn;
            if (is_file($path) && is_readable($path)) {
                $content = @file_get_contents($path);
                if ($content === false) continue;
                // try JSON first
                $maybe = @json_decode($content, true);
                if (is_array($maybe)) return $maybe;
                // try unserialize
                $un = @unserialize($content);
                if (is_array($un)) return $un;
                // try PHP session decode heuristic: some shims store raw "key|serialized" strings; give up if none matched
            }
        }
    }
    return null;
}

// Remove local session file for a given sid (best-effort). Do NOT touch WP cookie.
function bz_unlink_local_session_file_if_exists($sid) {
    if (!$sid) return false;
    $save_path = (string)ini_get('session.save_path');
    if (trim($save_path) === '') $save_path = sys_get_temp_dir();
    if (preg_match('#^N;(.+)#', $save_path, $m)) $save_path = $m[1];
    $save_path = rtrim($save_path, DIRECTORY_SEPARATOR);
    $file = $save_path . DIRECTORY_SEPARATOR . 'sess_' . $sid;
    if (is_file($file)) {
        @unlink($file);
        bz_bridge_log('Removed local session file (bridge reconcile)', ['file' => $file, 'sid' => $sid]);
        return true;
    }
    return false;
}

// Locate shared shadow directories (candidates)
function bz_locate_shadow_dirs() {
    $candidates = [];
    if (defined('BUZZ_SSO_SHADOW_PATH') && BUZZ_SSO_SHADOW_PATH) $candidates[] = rtrim(BUZZ_SSO_SHADOW_PATH, DIRECTORY_SEPARATOR);
    $candidates[] = realpath(__DIR__ . '/../shared/sso_sessions') ?: (__DIR__ . '/../shared/sso_sessions');
    $candidates[] = realpath(__DIR__ . '/../../shared/sso_sessions') ?: (__DIR__ . '/../../shared/sso_sessions');
    $candidates[] = realpath(__DIR__ . '/shared/sso_sessions') ?: (__DIR__ . '/shared/sso_sessions');
    $candidates[] = realpath(dirname(__DIR__, 2) . '/shared/sso_sessions') ?: (dirname(__DIR__, 2) . '/shared/sso_sessions');

    $result = [];
    foreach ($candidates as $c) {
        if (!$c) continue;
        $c = rtrim($c, DIRECTORY_SEPARATOR);
        if (is_dir($c) && is_readable($c) && is_writable($c)) {
            $result[] = $c;
        }
    }
    return array_values(array_unique($result));
}

// Write canonical WP shadow file (sess_shadow_{shadow_<wp_sid>}) based on payload
function bz_write_canonical_shadow_file(array $payload) {
    // Determine wp sid from payload fields
    $wp_sid = $payload['session_id'] ?? $payload['wp_php_session_id'] ?? null;
    if (!$wp_sid) return false;
    $shadow_id = 'shadow_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$wp_sid);
    $dirs = bz_locate_shadow_dirs();
    if (empty($dirs)) {
        bz_bridge_log('No writable shadow dirs available to write canonical shadow file', ['shadow_id'=>$shadow_id]);
        return false;
    }

    // Build minimal shadow array similar to WP mu-plugin
    $shadow = [];
    $allow_keys = [
        'wp_user_id','wp_user_login','wp_user_email',
        'wo_user_id','qd_user_id','qd_ready','expected_user_id',
        'buzz_sso_last_sync','wp_php_session_id','wp_session_name'
    ];
    foreach ($allow_keys as $k) {
        if (array_key_exists($k, $payload)) $shadow[$k] = $payload[$k];
    }
    // Ensure canonical session fields are present
    $shadow['wp_php_session_id'] = $payload['session_id'] ?? ($shadow['wp_php_session_id'] ?? null);
    $shadow['wp_session_name'] = $payload['session_name'] ?? ($shadow['wp_session_name'] ?? session_name());
    // ensure timestamps
    if (empty($shadow['buzz_sso_last_sync'])) $shadow['buzz_sso_last_sync'] = time();

    $payload_ser = @serialize($shadow);
    if ($payload_ser === false) {
        bz_bridge_log('Failed to serialize canonical shadow payload', ['shadow_id'=>$shadow_id]);
        return false;
    }
    $json_payload = @json_encode($shadow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $written_any = false;
    foreach ($dirs as $dir) {
        @mkdir($dir, 0750, true);
        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $shadow_id;

        $write_atomic = function($target_path, $contents) use ($shadow_id) {
            $tmp = $target_path . '.tmp';
            if (@file_put_contents($tmp, $contents, LOCK_EX) === false) {
                @unlink($tmp);
                return false;
            }
            @chmod($tmp, 0640);
            if (!@rename($tmp, $target_path)) {
                if (!@copy($tmp, $target_path) || !@unlink($tmp)) {
                    @unlink($tmp);
                    return false;
                }
            }
            @chmod($target_path, 0640);
            return true;
        };

        $ok = $write_atomic($path, $payload_ser);
        if ($ok) {
            // write .ser and .json copies
            $write_atomic($path . '.ser', $payload_ser);
            if ($json_payload) $write_atomic($path . '.json', $json_payload);
            $written_any = true;
            bz_bridge_log('Wrote canonical shadow file', ['path'=>$path,'shadow_id'=>$shadow_id]);
            // break after first successful write to avoid duplicates in multiple dirs
            break;
        } else {
            bz_bridge_log('Failed to write canonical shadow file to dir', ['dir'=>$dir,'shadow_id'=>$shadow_id]);
        }
    }

    return $written_any;
}

// Cleanup shadow mismatches: remove existing shadow files for same wp_user_id but different shadow id(s)
function bz_cleanup_shadow_mismatches($payload) {
    if (empty($payload) || !is_array($payload)) return false;
    $expected_wp_user_id = isset($payload['wp_user_id']) ? (int)$payload['wp_user_id'] : 0;
    $expected_session_id = isset($payload['session_id']) ? (string)$payload['session_id'] : ($payload['wp_php_session_id'] ?? '');
    if (!$expected_wp_user_id || !$expected_session_id) return false;

    $expected_shadow_filename = 'sess_' . 'shadow_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $expected_session_id);

    $dirs = bz_locate_shadow_dirs();
    if (empty($dirs)) {
        bz_bridge_log('No shadow dirs found for cleanup', ['candidates_checked' => 0]);
        return false;
    }

    $removed = [];
    foreach ($dirs as $dir) {
        $files = @scandir($dir);
        if (!$files) continue;
        foreach ($files as $f) {
            if (!preg_match('/^sess_/', $f)) continue;
            $full = $dir . DIRECTORY_SEPARATOR . $f;
            if (!is_file($full)) continue;

            // If it's already the expected file, skip
            if ($f === $expected_shadow_filename) continue;

            // Read a limited amount to avoid huge files
            $content = @file_get_contents($full, false, null, 0, 65536);
            if ($content === false || $content === '') continue;

            $found_wp_id = null;
            // Try unserialize
            $maybe = @unserialize($content);
            if ($maybe !== false && is_array($maybe) && array_key_exists('wp_user_id', $maybe)) {
                $found_wp_id = (int)$maybe['wp_user_id'];
            } else {
                // Try JSON
                $maybe_json = @json_decode($content, true);
                if (is_array($maybe_json) && array_key_exists('wp_user_id', $maybe_json)) {
                    $found_wp_id = (int)$maybe_json['wp_user_id'];
                } else {
                    // Last resort: text search
                    if (preg_match('/["\']wp_user_id["\']\s*[:=]\s*([0-9]+)/i', $content, $m)) {
                        $found_wp_id = (int)$m[1];
                    }
                }
            }

            if ($found_wp_id === $expected_wp_user_id) {
                // Remove this file and siblings (.ser/.json)
                $deleted_any = false;
                try {
                    @unlink($full);
                    $deleted_any = true;
                } catch (Throwable $e) {
                    // ignore
                }
                $siblings = [$full . '.ser', $full . '.json'];
                foreach ($siblings as $s) { if (is_file($s)) {@unlink($s); $deleted_any = true;} }
                if ($deleted_any) {
                    $removed[] = $full;
                    bz_bridge_log('Removed mismatched shadow file', ['removed'=>$full,'expected'=>$expected_shadow_filename,'shadow_dir'=>$dir]);
                } else {
                    bz_bridge_log('Failed to remove mismatched shadow file (no permission?)', ['file'=>$full,'expected'=>$expected_shadow_filename,'shadow_dir'=>$dir]);
                }
            }
        }
    }
    return !empty($removed);
}

// Rehydrate a fresh local WoWonder session from WP payload (best-effort).
function bz_rehydrate_session_from_wp_payload_bridge(array $wp_payload = null) {
    if (empty($wp_payload)) return false;
    $allowed = [
        'wp_user_id','wp_user_login','wp_user_email','wo_user_id',
        'qd_user_id','qd_ready','expected_user_id','buzz_sso_last_sync',
        'wp_php_session_id','wp_session_name','session_id','session_name'
    ];
    try {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
    } catch (Throwable $e) {}
    @session_start();
    $_SESSION = [];
    @session_unset();
    foreach ($allowed as $k) {
        if (array_key_exists($k, $wp_payload)) {
            $target_key = $k;
            if ($k === 'session_id' || $k === 'wp_php_session_id') $target_key = 'wp_php_session_id';
            if ($k === 'session_name' || $k === 'wp_session_name') $target_key = 'wp_session_name';
            $_SESSION[$target_key] = $wp_payload[$k];
        }
    }
    if (!empty($wp_payload['wp_user_id'])) $_SESSION['wp_user_id'] = (int)$wp_payload['wp_user_id'];
    if (!empty($wp_payload['wp_user_login'])) $_SESSION['wp_user_login'] = (string)$wp_payload['wp_user_login'];
    if (!empty($wp_payload['wp_user_email'])) $_SESSION['wp_user_email'] = (string)$wp_payload['wp_user_email'];
    if (!empty($wp_payload['wo_user_id'])) $_SESSION['wo_user_id'] = (int)$wp_payload['wo_user_id'];

    $_SESSION['buzz_rehydrated_from_wp'] = 1;
    $_SESSION['buzz_rehydrated_at'] = time();

    try { session_write_close(); @session_start(); } catch (Throwable $e) {}

    bz_bridge_log('Rehydrated local WoWonder session from WP payload (bridge)', [
        'rehydrated_preview' => [
            'wp_user_id' => $_SESSION['wp_user_id'] ?? null,
            'wp_user_login' => $_SESSION['wp_user_login'] ?? null,
            'wp_user_email' => $_SESSION['wp_user_email'] ?? null,
            'wo_user_id' => $_SESSION['wo_user_id'] ?? null,
            'local_sid' => session_id()
        ]
    ]);
    return true;
}

// Decide if current runtime needs reconciliation and perform it if so.
// IMPORTANT: must not alter or remove the WordPress PHPSESSID cookie.
function bz_attempt_session_reconciliation_if_required_bridge() {
    global $BUZZ_SSO_SECRET;
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) return;

    $current_name = session_name();
    $current_sid  = session_id();

    // Try to obtain canonical WP payload from cookie (verified if secret available)
    $wp_payload = bz_parse_buzz_sso_cookie_payload($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
    if (!$wp_payload) {
        // try without verify (best-effort)
        $wp_payload = bz_parse_buzz_sso_cookie_payload($_COOKIE[BUZZ_SSO_COOKIE], null);
        if ($wp_payload) {
            bz_bridge_log('Using buzz_sso cookie payload without verification (bridge)', ['preview' => substr($_COOKIE[BUZZ_SSO_COOKIE],0,24)]);
        }
    }

    // If we still don't have a useful payload, bail
    if (!$wp_payload) return;

    // prefer explicit session_name/session_id keys if present
    $wp_sname = $wp_payload['session_name'] ?? $wp_payload['wp_session_name'] ?? null;
    $wp_sid   = $wp_payload['session_id']   ?? $wp_payload['wp_php_session_id'] ?? null;

    // Load shadow payload to enrich data (if cookie lacks user fields)
    if (empty($wp_payload['wp_user_id']) && !empty($wp_sid)) {
        $shadow = bz_find_wp_shadow_payload($wp_sid);
        if (is_array($shadow) && !empty($shadow)) {
            $wp_payload = array_merge($wp_payload, $shadow);
            $wp_sname = $wp_payload['session_name'] ?? $wp_payload['wp_session_name'] ?? $wp_sname;
            $wp_sid   = $wp_payload['session_id'] ?? $wp_payload['wp_php_session_id'] ?? $wp_sid;
            bz_bridge_log('Loaded WP shadow payload to enrich cookie payload (bridge)', ['wp_sid_preview' => substr($wp_sid ?? '',0,12)]);
        }
    }

    // Determine mismatch heuristics:
    $mismatch = false;
    if ($wp_sname && $current_name !== $wp_sname) $mismatch = true;
    if ($wp_sid && $current_sid !== $wp_sid) $mismatch = true;

    if (!$mismatch) {
        bz_bridge_log('No session mismatch between WoWonder and WordPress detected (bridge)', ['current_name'=>$current_name,'current_sid_preview'=>substr($current_sid,0,12),'wp_name'=>$wp_sname,'wp_sid_preview'=>substr($wp_sid ?? '',0,12)]);
        return;
    }

    // Mismatch detected: destroy local WoWonder session state (but do not touch WP cookie)
    bz_bridge_log('Session mismatch detected; reconciling local WoWonder session to WordPress canonical session (bridge)', [
        'current_name'=>$current_name,
        'current_sid_preview'=>substr($current_sid,0,12),
        'wp_name'=>$wp_sname,
        'wp_sid_preview'=>substr($wp_sid ?? '',0,12)
    ]);

    // Best-effort remove current local session file
    if ($current_sid) {
        bz_unlink_local_session_file_if_exists($current_sid);
    }

    // Destroy runtime session
    $_SESSION = [];
    @session_unset();
    @session_destroy();

    // Start a fresh local session (do NOT set session id to WP sid, do NOT change WP cookie)
    @session_start();

    // Rehydrate local session from available WP payload (cookie or shadow)
    bz_rehydrate_session_from_wp_payload_bridge($wp_payload);

    // Additionally: ensure canonical shadow file exists (write it). This will also remove mismatched shadow files earlier if present.
    try {
        bz_cleanup_shadow_mismatches($wp_payload);
        bz_write_canonical_shadow_file($wp_payload);
    } catch (Throwable $e) {
        bz_bridge_log('Error while reconciling/writing canonical shadow', ['err'=>$e->getMessage()]);
    }

    bz_bridge_log('Reconciliation complete; local session rehydrated from WordPress (bridge)', ['new_local_sid'=>session_id()]);
}

// -----------------------------
// Fail-safe redirect helper (clears session and cookie then redirects to logout)
// -----------------------------
function bz_clear_session_and_redirect($reason = 'unknown') {
    global $wo;

    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/bot|crawl|spider|slurp|mediapartners/i', $ua)) {
        bz_bridge_log('Skipping redirect for bot', ['reason' => $reason, 'ua' => $ua]);
        return;
    }

    $has_session = !empty($_SESSION) || isset($_COOKIE[session_name()]) || isset($_COOKIE[BUZZ_SSO_COOKIE]);
    if (!$has_session) {
        bz_bridge_log('No session/cookie to clear, skipping redirect', ['reason' => $reason]);
        return;
    }

    bz_bridge_log('Clearing session and redirecting', ['reason' => $reason, 'sid' => function_exists('session_id') ? session_id() : null]);

    if (session_status() !== PHP_SESSION_NONE) {
        // clear only SSO/shadow keys where appropriate; for safety we fully destroy here
        $_SESSION = [];
        @session_unset();
        @session_destroy();
    }

    // Expire buzz_sso cookie for domain
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>time()-3600,'path'=>'/','domain'=>BUZZ_COOKIE_DOMAIN,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    } else {
        setcookie(BUZZ_SSO_COOKIE, '', time()-3600, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    // Expire session cookie on shared domain (best-effort)
    setcookie(session_name(), '', time() - 3600, '/', BUZZ_COOKIE_DOMAIN);

    $target = rtrim($wo['config']['site_url'], '/') . '/../wp-login.php';
    header('Location: ' . $target);
    exit;
}

// -----------------------------
// Bootstrap checks
// -----------------------------
global $wo, $sqlConnect;
if (empty($wo['config']['site_url']) || empty($sqlConnect)) {
    bz_bridge_log('Bootstrap incomplete - missing $wo or $sqlConnect');
    bz_debug_page('Bootstrap incomplete', ['$wo' => $wo ?? null, '$sqlConnect' => (bool)$sqlConnect]);
    header('Location: /'); exit;
}

// -----------------------------
// Ensure session is active when required
// If assets/init.php didn't start a session, start only when necessary.
if (session_status() === PHP_SESSION_NONE) {
    $needs = !empty($_COOKIE[BUZZ_SSO_COOKIE]) || (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login') || (!empty($_POST['sso_action']) && $_POST['sso_action'] === 'do_login') || !empty($_GET['sso_debug']);
    if ($needs) {
        @ini_set('session.serialize_handler', 'php_serialize');
        @ini_set('session.cookie_samesite', 'Lax');
        @ini_set('session.cookie_secure', 1);
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.use_only_cookies', 1);
        @ini_set('session.use_strict_mode', 1);

        // preserve incoming sid if present
        $sname = session_name();
        $sid = null;
        if (!empty($_COOKIE[$sname])) {
            $sid = preg_replace('/[^a-zA-Z0-9,-]/', '', (string) $_COOKIE[$sname]);
        } elseif (!empty($_COOKIE['PHPSESSID'])) {
            $sid = preg_replace('/[^a-zA-Z0-9,-]/', '', (string) $_COOKIE['PHPSESSID']);
        }
        if ($sid) { @session_id($sid); bz_bridge_log('Resuming PHP session from cookie (bridge fallback)'); }
        session_start();
        bz_bridge_log('Session started by bridge (fallback)', ['session_id' => session_id()]);

        // Immediately after starting a session, attempt reconciliation with WordPress canonical session.
        // This will NOT modify or remove the WordPress PHPSESSID cookie; it only resets local WoWonder session state
        // and rehydrates using WP canonical values when mismatch is detected.
        try {
            bz_attempt_session_reconciliation_if_required_bridge();
        } catch (Throwable $e) {
            bz_bridge_log('Session reconciliation attempt threw (bridge)', ['err' => $e->getMessage()]);
        }
    } else {
        bz_bridge_log('Session not started (bridge): benign request, no buzz_sso and not an SSO action');
    }
}

// -----------------------------
// Lightweight JSON "check" endpoint
// (returns logged_in / hydrate / wp_login results). Place before cookie verification.
//   // 1) Read & verify buzz_sso cookie (primary authority)
// -----------------------------
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'check') {
    header('Content-Type: application/json; charset=utf-8');

    // 1) If the WoWonder runtime already reports logged in, short-circuit
    $is_logged_in = false;
    if (isset($wo) && is_array($wo) && !empty($wo['loggedin'])) {
        $is_logged_in = true;
    } elseif (!empty($_SESSION['wo_user_id'])) {
        $is_logged_in = true;
    }

    if ($is_logged_in) {
        echo json_encode(['logged_in' => true]);
        exit;
    }

    // 2) If buzz_sso cookie exists and verifies, instruct client to POST to do_login to hydrate
    // preserve redirect_to param if present
    $redirect_to_param = !empty($_GET['redirect_to']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$_GET['redirect_to']) : '';

    // If a buzz_sso cookie exists and verifies, instruct client to POST to do_login
    if (!empty($_COOKIE[BUZZ_SSO_COOKIE]) && !empty($BUZZ_SSO_SECRET)) {
        try { $payload = bz_sso_verify_token($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET); } catch (Throwable $e) { $payload = false; }
        if ($payload) {
            $hydrate_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/ww-sso-bridge.php') . '?sso_action=do_login';
            if ($redirect_to_param) $hydrate_url .= '&redirect_to=' . rawurlencode($redirect_to_param);

            // Build go-pro absolute for WP redirect fallback
            $site_base = rtrim($wo['config']['site_url'] ?? '', '/');
            $go_pro_url = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
            $wp_login = 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_url);

            echo json_encode([
                'logged_in' => false,
                'hydrate' => true,
                'hydrate_url' => $hydrate_url,
                'wp_login' => $wp_login
            ]);
            exit;
        }
    }

    // 3) Otherwise tell client to go to WP login (so WP can create shadow session / buzz_sso)
    $site_base = rtrim($wo['config']['site_url'] ?? '', '/');
    $go_pro_url = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    $wp_login = 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_url);
    echo json_encode(['logged_in' => false, 'wp_login' => $wp_login]);
    exit;
}

// -----------------------------
// 1) Read & verify buzz_sso cookie (primary authority)
// -----------------------------
$payload = null;
$site_base = rtrim($wo['config']['site_url'], '/');
if (!empty($_COOKIE[BUZZ_SSO_COOKIE])) {
    if (!$BUZZ_SSO_SECRET) {
        bz_bridge_log('Missing BUZZ_SSO_SECRET', ['cookie_present'=>true]);
        // If misconfigured, do an explicit redirect to WordPress login (best effort)
        $go_pro_url = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
        header('Location: ' . 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_url));
        exit;
    }
    try { $payload = bz_sso_verify_token($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET); }
    catch (Throwable $e) { bz_bridge_log('Exception during buzz_sso verify', ['ex'=>$e->getMessage()]); $payload = false; }
} else {
    bz_bridge_log('buzz_sso cookie not present');
    // If no cookie, redirect to WP login with redirect_to back to go-pro (if requested) or to site base go-pro
    $redirect_to_param = !empty($_GET['redirect_to']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$_GET['redirect_to']) : '';
    $go_pro_target = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    if ($redirect_to_param === 'go-pro') $go_pro_target = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    header('Location: ' . 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_target));
    exit;
}
if (!$payload) {
    bz_bridge_log('buzz_sso payload invalid/expired');
    // If invalid payload, redirect to WP login with redirect_to back to go-pro
    $go_pro_target = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    header('Location: ' . 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_target));
    exit;
}

// Extract claims (raw)
$claim_wp_user_id    = isset($payload['wp_user_id'])    ? (int)$payload['wp_user_id'] : 0;
$claim_wp_user_login = isset($payload['wp_user_login']) ? (string)$payload['wp_user_login'] : (isset($payload['login']) ? (string)$payload['login'] : '');
$claim_wp_user_email = isset($payload['wp_user_email']) ? (string)$payload['wp_user_email'] : (isset($payload['email']) ? (string)$payload['email'] : '');
$claim_wo_user_id    = isset($payload['wo_user_id'])    ? (int)$payload['wo_user_id'] : 0;

$original_claims = [
    'claim_wp_user_id'=>$claim_wp_user_id,
    'claim_wp_user_login'=>$claim_wp_user_login,
    'claim_wp_user_email'=>$claim_wp_user_email,
    'claim_wo_user_id'=>$claim_wo_user_id
];

bz_bridge_log('buzz_sso claims extracted', array_merge($original_claims, ['raw_payload'=>$payload]));

// -----------------------------
// Ensure canonical shadow exists & cleanup mismatches BEFORE mapping/registration.
// This implements the suggestion: if WoWonder shadow id differs from WP shadow id, remove it and create WP canonical shadow.

try {
    // First remove mismatched shadow files that refer to same wp_user_id
    bz_cleanup_shadow_mismatches($payload);
    // Then create the canonical shadow file (sess_shadow_shadow_{wp_sid}) so all apps can pick it up
    bz_write_canonical_shadow_file($payload);
} catch (Throwable $e) {
    bz_bridge_log('Error during canonical shadow reconciliation', ['ex'=>$e->getMessage()]);
}
// -----------------------------

// -----------------------------
// Required claims guard
// -----------------------------
if (!$claim_wp_user_id || !$claim_wp_user_login || !$claim_wp_user_email) {
    bz_bridge_log('Missing required claims (cookie incomplete)', $original_claims);
    $go_pro_target = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    header('Location: ' . 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_target));
    exit;
}

// -----------------------------
// Canonicalization: prefer server session values (if present) to avoid accidental overwrite.
// - wp_user_login must remain immutable if already present in session
// - wo_user_id can be set only if session had none (0/null) and we compute one here (or was in payload)
$canonical = [];
$canonical['wp_user_id']    = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : $claim_wp_user_id;
$canonical['wp_user_login'] = isset($_SESSION['wp_user_login']) ? (string)$_SESSION['wp_user_login'] : $claim_wp_user_login;
$canonical['wp_user_email'] = isset($_SESSION['wp_user_email']) ? (string)$_SESSION['wp_user_email'] : $claim_wp_user_email;
$canonical['wo_user_id']    = isset($_SESSION['wo_user_id']) ? (int)$_SESSION['wo_user_id'] : $claim_wo_user_id;

bz_bridge_log('Canonical pre-mapping values', ['canonical'=>$canonical,'session'=>$_SESSION ?? []]);

// -----------------------------
// helper functions (mapping/registration helpers used previously)
// -----------------------------
function bz_safe_username_from_login($login, $email = '') {
    $u = preg_replace('~[^a-z0-9_.-]~i', '', (string)$login);
    if (!$u && $email) { $local = strstr($email, '@', true); $u = preg_replace('~[^a-z0-9_.-]~i', '', (string)$local); }
    return $u ?: 'wpuser';
}
function bz_find_wo_user_by_id($wo_user_id) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if (!$wo_user_id) return 0;
    $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE user_id=".(int)$wo_user_id." LIMIT 1");
    if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    return 0;
}
function bz_find_wo_user_by_login_email($login, $email) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if ($login && $email) {
        $escL = mysqli_real_escape_string($sqlConnect, (string)$login);
        $escE = mysqli_real_escape_string($sqlConnect, (string)$email);
        $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE username='{$escL}' AND email='{$escE}' LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    }
    return 0;
}
function bz_find_wo_user_any($wp_id, $email, $login) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if ($wp_id) {
        $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE wp_user_id=".(int)$wp_id." LIMIT 1"); if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    }
    if ($email) {
        $esc = mysqli_real_escape_string($sqlConnect, (string)$email);
        $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE email='{$esc}' LIMIT 1"); if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    }
    if ($login) {
        $esc = mysqli_real_escape_string($sqlConnect, (string)$login);
        $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE username='{$esc}' LIMIT 1"); if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    }
    return 0;
}
function bz_update_wo_mapping($wo_user_id, $wp_user_id) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if ($wo_user_id && $wp_user_id) {
        @mysqli_query($sqlConnect, "UPDATE {$tbl} SET wp_user_id=".(int)$wp_user_id." WHERE user_id=".(int)$wo_user_id." LIMIT 1");
        bz_bridge_log('Updated Wo->WP mapping', ['wo_user_id'=>$wo_user_id,'wp_user_id'=>$wp_user_id]);
    }
}
function bz_register_wo_user($wp_user_id, $login, $email) {
    global $wo, $conn;
    $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
    if (!function_exists('Wo_RegisterUser')) { bz_bridge_log('Wo_RegisterUser missing'); return 0; }
    $username = preg_replace('~[^a-z0-9_.-]~i','',(string)$login);
    if (!$username) $username = bz_safe_username_from_login($login, $email);
    $base = substr($username,0,20); $i = 0;
    while (function_exists('Wo_UsernameExists') && Wo_UsernameExists($username)) {
        $i++; $username = $base . '-' . $i; if ($i > 200) { $username = $base . '-' . bin2hex(random_bytes(3)); break; }
    }

    $password = bin2hex(random_bytes(8));
    if ($conn && $wp_user_id) {
        $res = @mysqli_query($conn, "SELECT user_pass FROM wp_users WHERE ID='" . intval($wp_user_id) . "' LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $password = $row['user_pass'];
        }
    }

    $ip = function_exists('get_ip_address') ? get_ip_address() : '0.0.0.0';
    $language = $wo['config']['defualtLang'] ?? 'en';
    if (!empty($_SESSION['lang'])) {
        $lang_name = strtolower($_SESSION['lang']);
        $langs = function_exists('Wo_LangsNamesFromDB') ? Wo_LangsNamesFromDB() : [];
        if (in_array($lang_name, $langs)) {
            $language = Wo_Secure($lang_name);
        }
    }

    $user_data = function_exists('wp_get_full_user_data') && $conn ? wp_get_full_user_data($conn, $wp_user_id) : [];
    $avatar = $user_data['xprofile']['avatar'] ?? $user_data['meta']['avatar'] ?? ($wo['config']['userDefaultAvatar'] ?? '');
    $cover  = $user_data['xprofile']['cover'] ?? $user_data['meta']['cover'] ?? '';

    $re_data = [
        'username'      => $username,
        'password'      => $password,
        'email'         => $email,
        'avatar'        => $avatar,
        'cover'         => $cover,
        'active'        => 1,
        'src'           => 'wp-sso',
        'wp_user_id'    => (int)$wp_user_id,
        'ip_address'    => Wo_Secure($ip),
        'language'      => $language,
        'order_posts_by'=> $wo['config']['order_posts_by'] ?? '',
        'registered'    => date('n') . '/' . date("Y"),
        'joined'        => time(),
    ];

    $created = Wo_RegisterUser($re_data);
    if ($created) {
        $wo_user_id = function_exists('Wo_UserIdFromEmail') ? Wo_UserIdFromEmail($email) : 0;
        if ($wo_user_id) {
            bz_update_wo_mapping($wo_user_id, $wp_user_id);
            if (function_exists('Wo_UpdateUserData')) {
                Wo_UpdateUserData($wo_user_id, ['wp_user_id'=>(int)$wp_user_id,'src'=>'wp-sso']);
            }
            if (!empty($wo['config']['auto_friend_users'])) Wo_AutoFollow($wo_user_id);
            if (!empty($wo['config']['auto_page_like'])) Wo_AutoPageLike($wo_user_id);
            if (!empty($wo['config']['auto_group_join'])) Wo_AutoGroupJoin($wo_user_id);
            bz_bridge_log('Auto-registered Wo user (success)', ['wp_user_id'=>$wp_user_id,'wo_user_id'=>$wo_user_id,'username'=>$username]);
            return (int)$wo_user_id;
        }
    }
    bz_bridge_log('Auto-register failed', ['attempt'=>$re_data]);
    return 0;
}

// -----------------------------
// Mapping & registration flow (use canonical values to determine Wo user id)
// -----------------------------
$session_existing_wo = isset($_SESSION['wo_user_id']) ? (int)$_SESSION['wo_user_id'] : 0;
$final_wo_user_id = 0;

// If payload provided a wo_user_id (or session already had one), attempt to verify
if ($canonical['wo_user_id']) {
    $verify = bz_find_wo_user_by_id($canonical['wo_user_id']);
    if ($verify) {
        bz_bridge_log('Payload/session wo_user_id verified', ['wo_user_id'=>$canonical['wo_user_id']]);
        $final_wo_user_id = $canonical['wo_user_id'];
    } else {
        bz_bridge_log('Provided wo_user_id not found; attempting to find by login/email or wp mapping', ['payload_wo'=>$canonical['wo_user_id'],'login'=>$canonical['wp_user_login'],'email'=>$canonical['wp_user_email'],'session_wo'=>$session_existing_wo]);
        $found = bz_find_wo_user_by_login_email($canonical['wp_user_login'], $canonical['wp_user_email']);
        if ($found) {
            $final_wo_user_id = $found;
            bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
            bz_bridge_log('Found Wo user by login+email (using that)', ['wo_user_id'=>$final_wo_user_id]);
        } else {
            $found_any = bz_find_wo_user_any($canonical['wp_user_id'], $canonical['wp_user_email'], $canonical['wp_user_login']);
            if ($found_any) {
                $final_wo_user_id = $found_any;
                bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
                bz_bridge_log('Found Wo user by alternate mapping', ['wo_user_id'=>$final_wo_user_id]);
            } elseif (BUZZ_SSO_AUTO_REGISTER) {
                if (!$session_existing_wo) {
                    bz_bridge_log('Auto-registering Wo user (payload wo provided but not found)', ['login'=>$canonical['wp_user_login'],'email'=>$canonical['wp_user_email']]);
                    $created = bz_register_wo_user($canonical['wp_user_id'], $canonical['wp_user_login'], $canonical['wp_user_email']);
                    if ($created) {
                        $final_wo_user_id = $created;
                        // update cookie payload and re-issue
                        $payload['wo_user_id'] = (int)$final_wo_user_id;
                        bz_issue_buzz_sso_cookie($payload, $BUZZ_SSO_SECRET, ['wo_user_id'=>$final_wo_user_id]);
                        // mark auto-registered in session so redirect goes to start-up
                        $_SESSION['wo_auto_registered'] = 1;
                        bz_bridge_log('Auto-registered and re-issued buzz_sso (auto_registered flag set)', ['wo_user_id'=>$final_wo_user_id]);
                    } else {
                        bz_bridge_log('Auto-register failed (payload wo provided and not found)');
                    }
                } else {
                    $final_wo_user_id = $session_existing_wo;
                    bz_bridge_log('Conflict: payload wo_user_id missing in DB but session had one; preserving session wo_user_id', ['session_wo'=>$session_existing_wo]);
                }
            } else {
                bz_bridge_log('Auto registration disabled and no mapping found for provided wo_user_id');
            }
        }
    }
} else {
    // payload has no wo_user_id -> attempt find by login+email, then alternatives, then register
    $found = bz_find_wo_user_by_login_email($canonical['wp_user_login'], $canonical['wp_user_email']);
    if ($found) {
        $final_wo_user_id = $found;
        bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
        bz_bridge_log('Found Wo user by login+email (no payload wo provided)', ['wo_user_id'=>$final_wo_user_id]);
    } else {
        $found_any = bz_find_wo_user_any($canonical['wp_user_id'], $canonical['wp_user_email'], $canonical['wp_user_login']);
        if ($found_any) {
            $final_wo_user_id = $found_any;
            bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
            bz_bridge_log('Found Wo user by alternate mapping (no payload wo provided)', ['wo_user_id'=>$final_wo_user_id]);
        } elseif (BUZZ_SSO_AUTO_REGISTER) {
            if (!$session_existing_wo) {
                bz_bridge_log('No mapping found; auto-registering new Wo user', ['login'=>$canonical['wp_user_login'],'email'=>$canonical['wp_user_email']]);
                $created = bz_register_wo_user($canonical['wp_user_id'], $canonical['wp_user_login'], $canonical['wp_user_email']);
                if ($created) {
                    $final_wo_user_id = $created;
                    $payload['wo_user_id'] = (int)$final_wo_user_id;
                    bz_issue_buzz_sso_cookie($payload, $BUZZ_SSO_SECRET, ['wo_user_id'=>$final_wo_user_id]);
                    // mark auto-registered in session so redirect goes to start-up
                    $_SESSION['wo_auto_registered'] = 1;
                    bz_bridge_log('Auto-registered and re-issued buzz_sso (auto_registered flag set)', ['wo_user_id'=>$final_wo_user_id]);
                } else {
                    bz_bridge_log('Auto-register failed (no mapping found)');
                }
            } else {
                $final_wo_user_id = $session_existing_wo;
                bz_bridge_log('Session already had wo_user_id; preserving it instead of auto-register', ['session_wo'=>$session_existing_wo]);
            }
        } else {
            bz_bridge_log('Auto registration disabled and no mapping found (no payload wo provided)');
        }
    }
}

if (!$final_wo_user_id) {
    bz_bridge_log('Unable to determine wo_user_id after mapping/registration', ['canonical'=>$canonical,'session'=>$_SESSION ?? []]);
    $go_pro_target = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    header('Location: ' . 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_target));
    exit;
}

// Persist canonical session values (set wp_user_login only if not set already to keep it immutable)
if (!isset($_SESSION['wp_user_login'])) $_SESSION['wp_user_login'] = (string)$canonical['wp_user_login'];
$_SESSION['wp_user_id']    = (int)$canonical['wp_user_id'];
$_SESSION['wp_user_email'] = (string)$canonical['wp_user_email'];
if (empty($_SESSION['wo_user_id'])) {
    $_SESSION['wo_user_id'] = (int)$final_wo_user_id;
    bz_bridge_log('Set session wo_user_id from mapping/registration', ['wo_user_id'=>$_SESSION['wo_user_id']]);
} else {
    bz_bridge_log('Preserving existing session wo_user_id', ['wo_user_id'=>$_SESSION['wo_user_id']]);
}

// fetch Wo username if available and store it as wo_username (do not overwrite wp_user_login)
// Minimal fetch to avoid heavy Wo_UserData() code paths
$wo_username = '';
try {
    if (!empty($_SESSION['wo_user_id'])) {
        $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
        $q = @mysqli_query($sqlConnect, "SELECT username FROM {$tbl} WHERE user_id=".(int)$_SESSION['wo_user_id']." LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q) && !empty($r['username'])) {
            $wo_username = $r['username'];
            $_SESSION['wo_username'] = $wo_username;
        }
    }
} catch (Throwable $e) {
    bz_bridge_log('Minimal Wo username fetch failed', ['ex'=>$e->getMessage()]);
}

bz_bridge_log('After mapping/registration - canonical session snapshot', [
    'wp_user_id' => $_SESSION['wp_user_id'],
    'wp_user_login' => $_SESSION['wp_user_login'],
    'wp_user_email' => $_SESSION['wp_user_email'],
    'wo_user_id' => $_SESSION['wo_user_id'],
    'wo_username' => $_SESSION['wo_username'] ?? null
]);

// -----------------------------
// Build SSO token and choose username for the client (username = wp_user_login)
$sso_username = $_SESSION['wp_user_login'];
$sso_password = bz_build_sso_password_token($_SESSION['wo_user_id'], $_SESSION['wp_user_id'], $_SESSION['wp_user_login'], $_SESSION['wp_user_email'], $BUZZ_SSO_SECRET);

// -----------------------------
// last_url derivation & normalization
$site_base = rtrim($wo['config']['site_url'], '/');
$last_url = '';

// 1) explicit last_url param
if (!empty($_GET['last_url'])) {
    $last_url = (string)$_GET['last_url'];
} elseif (!empty($_POST['last_url'])) {
    $last_url = (string)$_POST['last_url'];
} elseif (!empty($_COOKIE['last_url'])) {
    $last_url = (string)$_COOKIE['last_url'];
}

// 2) fallback to HTTP_REFERER (likely when Welcome redirected via header)
if (empty($last_url) && !empty($_SERVER['HTTP_REFERER'])) {
    $last_url = trim((string)$_SERVER['HTTP_REFERER']);
}

// 3) if still empty, derive from REQUEST_URI as before (avoid taking bridge path)
if (empty($last_url)) {
    $req_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $bridge_path = parse_url($_SERVER['PHP_SELF'] ?? ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if ($req_uri && $bridge_path && $req_uri !== $bridge_path && strpos($req_uri, basename(__FILE__)) === false) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? parse_url($site_base, PHP_URL_HOST);
        $candidate = rtrim($scheme . '://' . $host, '/') . $req_uri;
        $ok = false;
        if ($site_base && strpos($candidate, $site_base) === 0) $ok = true;
        if (!$ok) {
            $path_only = parse_url($candidate, PHP_URL_PATH) ?: '/';
            if (strpos($path_only, '/streams') === 0) $ok = true;
        }
        if ($ok) $last_url = $candidate;
    }
}

// Normalize last_url: if relative convert to absolute; if not same-site fallback to site base
if ($last_url) {
    // If relative path like '/streams/messages', convert to absolute
    if (strpos($last_url, 'http://') !== 0 && strpos($last_url, 'https://') !== 0) {
        // allow root-relative paths
        if (strpos($last_url, '/') === 0) {
            $last_url = $site_base . $last_url;
        } else {
            $last_url = $site_base . '/' . ltrim($last_url, '/');
        }
    }
    // Ensure same-site
    if ($site_base && strpos($last_url, $site_base) !== 0) {
        // Not same site; drop it
        $last_url = '';
    }
}
if (!$last_url) $last_url = $site_base . '/';

// Insert immediately after the block that normalizes $last_url (after "if (!$last_url) $last_url = $site_base . '/';")
if (!empty($last_url) && function_exists('bz_is_bridge_url') && bz_is_bridge_url($last_url, $site_base)) {
    bz_bridge_log('last_url rejected: bridge/self-reference detected', ['last_url' => $last_url, 'site_base' => $site_base]);
    $last_url = rtrim($site_base, '/') . '/';
}
if (!empty($forced_last_url_fallback)) {
    $last_url = rtrim($site_base, '/') . '/';
}

// -----------------------------
// Build $ajax_url for the bridge page so the client POST preserves redirect_to
// Place this after $last_url / $sso_username / $sso_password are set and BEFORE the HTML render.
// -----------------------------
$ajax_url_base = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/ww-sso-bridge.php') . '?sso_action=do_login';
$ajax_url = $ajax_url_base;

// Preserve redirect_to from the incoming GET (so POST carries it through).
if (!empty($_GET['redirect_to'])) {
    // allow safe chars, remove anything suspicious
    $rt = preg_replace('/[^\w\-\/:.@]/u', '', (string) $_GET['redirect_to']);
    if ($rt !== '') {
        $ajax_url .= '&redirect_to=' . rawurlencode($rt);
    }
}

// Echo or inject $ajax_url into your HTML/JS as needed (use json_encode when embedding).

bz_bridge_log('SSO session prepared', ['sso_username'=>$sso_username,'sso_password_len'=>strlen($sso_password),'ajax_url'=>$ajax_url,'last_url'=>$last_url]);



// ------------------------------
// Deferred redirect_to handling (replacement)
// Replace the existing Immediate GET redirect_to override block with this.
// ------------------------------
if (!empty($_GET['redirect_to'])) {
    $raw_requested = (string) $_GET['redirect_to'];
    // Sanitize but keep readable
    $requested = preg_replace('/[^\w\-\/:.\@]/u', '', $raw_requested);

    // ajax_url should have been built earlier; include preview for debugging
    $ajax_preview = isset($ajax_url) ? $ajax_url : '(ajax_url not set)';

    bz_bridge_log('redirect_to present; deferring server redirect so bridge HTML can render', [
        'raw'              => $raw_requested,
        'sanitized'        => $requested,
        'ajax_url_preview' => $ajax_preview,
        'session_preview'  => isset($_SESSION) ? [
            'wp_user_id' => ($_SESSION['wp_user_id'] ?? null),
            'wo_user_id' => ($_SESSION['wo_user_id'] ?? null)
        ] : null
    ]);

    // DO NOT call header('Location: ...') or exit() here.
    // The bridge HTML will render and client JS will POST to $ajax_url (which includes redirect_to).
    // Wo_SSO_Login() will return JSON { location: "..." } and the client JS will perform the final redirect.
}
// ------------------------------



// -----------------------------
// Wo_SSO_Login endpoint (POST)
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') { Wo_SSO_Login(); exit; }

function Wo_SSO_Login() {
    global $wo, $sqlConnect, $BUZZ_SSO_SECRET, $last_url;
    header('Content-Type: application/json; charset=utf-8');
    $errors = [];

    $username = isset($_POST['username']) ? Wo_Secure($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $posted_last_url = isset($_POST['last_url']) ? (string)$_POST['last_url'] : '';

    bz_bridge_log('Wo_SSO_Login: credentials received', ['username'=>$username,'password_len'=>is_string($password)?strlen($password):0,'session'=>$_SESSION ?? []]);

    if (!is_string($password) || strpos($password,'WPSSO.v1.') !== 0 || !$BUZZ_SSO_SECRET) {
        $errors[] = 'Invalid SSO token or misconfigured secret';
        bz_bridge_log('Wo_SSO_Login: invalid token format or missing secret', ['username'=>$username]);
        echo json_encode(['errors'=>$errors]); exit;
    }

    $claims = bz_parse_sso_password_token($password, $BUZZ_SSO_SECRET);
    if (!$claims) { $errors[]='Invalid or expired SSO token'; bz_bridge_log('Wo_SSO_Login: token parse/verify failed'); echo json_encode(['errors'=>$errors]); exit; }

    // Prefer authoritative server session values when present
    $sess_wo = isset($_SESSION['wo_user_id']) ? (int)$_SESSION['wo_user_id'] : 0;
    $sess_wp = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : 0;
    $sess_login = isset($_SESSION['wp_user_login']) ? (string)$_SESSION['wp_user_login'] : '';
    $sess_email = isset($_SESSION['wp_user_email']) ? (string)$_SESSION['wp_user_email'] : '';

    $exp_wo = $sess_wo ?: (isset($claims['wo_user_id']) ? (int)$claims['wo_user_id'] : 0);
    $exp_wp = $sess_wp ?: (isset($claims['wp_user_id']) ? (int)$claims['wp_user_id'] : 0);
    $exp_login = $sess_login ?: (isset($claims['wp_user_login']) ? (string)$claims['wp_user_login'] : '');
    $exp_email = $sess_email ?: (isset($claims['wp_user_email']) ? (string)$claims['wp_user_email'] : '');

    bz_bridge_log('Wo_SSO_Login: expected (auth) values', ['exp_wo'=>$exp_wo,'exp_wp'=>$exp_wp,'exp_login'=>$exp_login,'exp_email'=>$exp_email,'session_snapshot'=>$_SESSION ?? [],'claims'=>$claims]);

    $candidates = [];
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if ($exp_wo) {
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE user_id=".(int)$exp_wo." LIMIT 1"); if ($q && $r=mysqli_fetch_assoc($q)) $candidates[]=$r;
    }
    if (empty($candidates) && $exp_email) {
        $esc = mysqli_real_escape_string($sqlConnect, $exp_email);
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE email='{$esc}' LIMIT 1"); if ($q && $r=mysqli_fetch_assoc($q)) $candidates[]=$r;
    }
    if (empty($candidates) && $exp_login) {
        $esc = mysqli_real_escape_string($sqlConnect, $exp_login);
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE username='{$esc}' LIMIT 1"); if ($q && $r=mysqli_fetch_assoc($q)) $candidates[]=$r;
    }
    if (empty($candidates) && $exp_wp) {
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE wp_user_id=".(int)$exp_wp." LIMIT 1"); if ($q && $r=mysqli_fetch_assoc($q)) $candidates[]=$r;
    }

    bz_bridge_log('Wo_SSO_Login: candidates fetched', ['count'=>count($candidates),'candidates'=>$candidates]);

    $accepted_user_id = 0; $accepted_reason = ''; $accepted_matches = [];
    $accepted_row = null;
    foreach ($candidates as $row) {
        $db_user_id = (int)$row['user_id'];
        $db_username = (string)$row['username'];
        $db_email = (string)$row['email'];
        $db_wp_userid = (int)$row['wp_user_id'];

        $cmp_user_id = ($exp_wo && $db_user_id === $exp_wo) ? 1 : 0;
        $cmp_email = ($exp_email && strcasecmp($db_email, $exp_email) === 0) ? 1 : 0;
        $cmp_username = ($exp_login && strcasecmp($db_username, $exp_login) === 0) ? 1 : 0;
        $cmp_wp_userid = ($exp_wp && $db_wp_userid === $exp_wp) ? 1 : 0;

        $match_count = $cmp_user_id + $cmp_email + $cmp_username + $cmp_wp_userid;

        bz_bridge_log('Wo_SSO_Login: compare row', [
            'db'=>['user_id'=>$db_user_id,'username'=>$db_username,'email'=>$db_email,'wp_user_id'=>$db_wp_userid],
            'cmp'=>['user_id'=>$cmp_user_id,'email'=>$cmp_email,'username'=>$cmp_username,'wp_user_id'=>$cmp_wp_userid],
            'match_count'=>$match_count
        ]);

        if ($match_count >= 3) {
            $accepted_user_id = $db_user_id;
            $accepted_reason = implode('|', array_filter([
                $cmp_user_id ? 'user_id' : null,
                $cmp_email ? 'email' : null,
                $cmp_username ? 'username' : null,
                $cmp_wp_userid ? 'wp_user_id' : null,
            ]));
            $accepted_matches = ['user_id'=>$cmp_user_id,'email'=>$cmp_email,'username'=>$cmp_username,'wp_user_id'=>$cmp_wp_userid];
            $accepted_row = $row;
            break;
        }
    }

    if (!$accepted_user_id) {
        $errors[] = 'No matching WoWonder account for SSO (>=3 identifiers required).';
        bz_bridge_log('Wo_SSO_Login: no match (>=3 required)', ['expected'=>['wo'=>$exp_wo,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email],'session'=>$_SESSION ?? [],'claims'=>$claims]);
        echo json_encode(['errors'=>$errors]); exit;
    }

    $ip = function_exists('get_ip_address') ? Wo_Secure(get_ip_address()) : '0.0.0.0';
    @mysqli_query($sqlConnect, "UPDATE {$tbl} SET `ip_address` = '".Wo_Secure($ip)."' WHERE `user_id` = '".intval($accepted_user_id)."'");
    cache($accepted_user_id, 'users', 'delete');

    $session_token = Wo_CreateLoginSession($accepted_user_id);

    $_SESSION['user_id'] = $session_token;
    $_SESSION['wo_user_id'] = (int)$accepted_user_id;
    $_SESSION['wp_Wo_SSO_Login'] = true;

    //
    // IMPORTANT: mark request-local $wo as logged in and provide a minimal $wo['user']
    // snapshot so the rest of the Wo code can run without heavy initialization.
    //
    try {
        if (!is_array($wo)) $wo = [];
        $wo['loggedin'] = true;

        $minimal = [
            'user_id'  => (int)$accepted_user_id,
            'id'       => (int)$accepted_user_id,
            'username' => $accepted_row['username'] ?? '',
            'email'    => $accepted_row['email'] ?? '',
            'admin'    => 0,
            'is_pro'   => 0,
            'verified' => 0,
            'active'   => 1,
            'type'     => $accepted_row['type'] ?? 'user',
            'lastseen' => time()
        ];

        try {
            $safe_q = @mysqli_query($sqlConnect, "SELECT is_pro,admin FROM {$tbl} WHERE user_id=".(int)$accepted_user_id." LIMIT 1");
            if ($safe_q && $r_safe = mysqli_fetch_assoc($safe_q)) {
                if (isset($r_safe['is_pro'])) $minimal['is_pro'] = (int)$r_safe['is_pro'];
                if (isset($r_safe['admin']))   $minimal['admin']  = (int)$r_safe['admin'];
            }
        } catch (Throwable $e) {
            // ignore
        }

        $wo['user'] = $minimal;
        bz_bridge_log('Wo_SSO_Login: set $wo[\'loggedin\']=true and minimal user snapshot', ['user'=>$wo['user'],'session_token_preview'=>substr((string)$session_token,0,40)]);
    } catch (Throwable $e) {
        bz_bridge_log('Wo_SSO_Login: error while creating minimal $wo user snapshot', ['ex'=>$e->getMessage()]);
        if (!is_array($wo)) $wo = [];
        $wo['loggedin'] = true;
        $wo['user'] = ['user_id' => (int)$accepted_user_id, 'id' => (int)$accepted_user_id, 'admin'=>0, 'is_pro'=>0];
    }

    // Consider the login established when we have a created session token and user id.
    if (!empty($session_token) && !empty($accepted_user_id) && !empty($wo['loggedin'])) {

        // --- update Wo user data (sync WP fields) ---
        $update = [];
        if (!empty($_SESSION['wp_user_id']))    $update['wp_user_id'] = (int)$_SESSION['wp_user_id'];
        if (!empty($_SESSION['wp_user_email'])) $update['email']      = (string)$_SESSION['wp_user_email'];
        if (!empty($_SESSION['wp_user_login'])) $update['username']   = (string)$_SESSION['wp_user_login'];

        $metadata = function_exists('get_user_field_metadata') ? get_user_field_metadata() : [];
        $wp_usermeta_fields = $metadata['private_secure_fields'] ?? [];
        $wp_xprofile_fields = $metadata['public_open_fields'] ?? [];

        foreach ($_SESSION as $field => $value) {
            if (in_array($field, $wp_usermeta_fields, true) || in_array($field, $wp_xprofile_fields, true)) {
                if (!empty($value)) {
                    $update[$field] = is_string($value) ? trim($value) : $value;
                }
            }
        }

        if (!empty($update) && function_exists('Wo_UpdateUserData')) {
            // Suppress notices/warnings during Wo_UpdateUserData call to avoid polluting JSON
            $old_level = error_reporting();
            error_reporting($old_level & ~E_NOTICE & ~E_WARNING);
            try {
                $result = Wo_UpdateUserData($accepted_user_id, $update);
                bz_bridge_log('Wo_UpdateUserData post-login sync', [
                    'user_id' => $accepted_user_id,
                    'update'  => $update,
                    'result'  => $result
                ]);
            } catch (Throwable $e) {
                bz_bridge_log('Wo_UpdateUserData exception', ['ex'=>$e->getMessage(),'user_id'=>$accepted_user_id,'update'=>$update]);
            } finally {
                error_reporting($old_level);
            }
        }

        if (!empty($_POST['remember_device']) && $_POST['remember_device'] == 'on' && !empty($wo['config']['remember_device']) && $wo['config']['remember_device'] == 1) {
            setcookie('user_id', $session_token, time() + (10*365*24*60*60), '/', BUZZ_COOKIE_DOMAIN, true, true);
        }

        // ------------------------------
        // Wo_SSO_Login() — JSON redirect resolution (replace existing redirect-building block)
        // Priority:
        //  1) $_REQUEST['redirect_to'] override (highest priority, sanitized + mapped)
        //  2) new auto-registered users -> start-up
        //  3) membership override -> go-pro (if membership enabled and user is not pro)
        //  4) posted_last_url (validated same-site)
        //  5) last_url or fallback
        // ------------------------------
        $site_base = rtrim($wo['config']['site_url'] ?? '', '/');
        
        // Default fallback location
        $data = [
            'status'   => 200,
            'location' => $site_base . '/?cache=' . time(),
        ];
        
        // Helper: resolve a safe redirect_to token or path to an absolute location on this site
        $resolve_redirect_to = function($token) use ($site_base) {
            $token_raw = (string)$token;
            $token_safe = preg_replace('/[^\w\-\/:.\@]/u', '', $token_raw);
            if ($token_safe === '') return '';
        
            // Known mapping
            $map = [
                'go-pro'   => 'index.php?link1=go-pro',
                'start-up' => 'index.php?link1=start-up',
                'home'     => '/',
            ];
        
            if (isset($map[$token_safe])) {
                $internal = $map[$token_safe];
                if (function_exists('Wo_SeoLink')) {
                    return Wo_SeoLink($internal);
                } else {
                    return (strpos($internal, '/') === 0)
                        ? rtrim($site_base, '/') . $internal
                        : rtrim($site_base, '/') . '/' . ltrim($internal, '/');
                }
            }
        
            // Absolute URL — allow only same-site host
            if (preg_match('#^https?://#i', $token_safe)) {
                $parts = @parse_url($token_safe);
                $site_host = parse_url($site_base, PHP_URL_HOST);
                if (!empty($parts['host']) && strcasecmp($parts['host'], $site_host) === 0) {
                    return $token_safe;
                }
                return '';
            }
        
            // Treat as path or short path under site root
            if (strpos($token_safe, '/') === 0) {
                $candidate = rtrim($site_base, '/') . $token_safe;
            } else {
                $candidate = rtrim($site_base, '/') . '/' . ltrim($token_safe, '/');
            }
            if (strpos($candidate, $site_base) === 0) {
                return $candidate;
            }
            return '';
        };
        
        // 1) REQUEST redirect_to override (highest priority for JSON flows)
        if (!empty($_REQUEST['redirect_to'])) {
            $resolved = $resolve_redirect_to($_REQUEST['redirect_to']);
            if ($resolved) {
                $data['location'] = $resolved;
                bz_bridge_log('Wo_SSO_Login: redirect_to override applied', ['redirect_to' => $_REQUEST['redirect_to'], 'resolved' => $resolved]);
                echo json_encode($data); exit;
            } else {
                bz_bridge_log('Wo_SSO_Login: redirect_to present but could not resolve to safe location', ['raw' => $_REQUEST['redirect_to']]);
                // fall through to normal rules
            }
        }
        
        // 2) New auto-registered user -> start-up
        if (!empty($_SESSION['wo_auto_registered'])) {
            $start_up = function_exists('Wo_SeoLink') ? Wo_SeoLink('index.php?link1=start-up') : rtrim($site_base, '/') . '/index.php?link1=start-up';
            $data['location'] = $start_up;
            unset($_SESSION['wo_auto_registered']);
            bz_bridge_log('Wo_SSO_Login: new auto-registered user; redirecting to start-up', ['redirect' => $data['location']]);
            echo json_encode($data); exit;
        }
        
        // 3) Membership override -> go-pro (if membership enabled & user is not pro)
        $user_is_pro = null;
        if (!empty($wo['config']['membership_system']) && (int)$wo['config']['membership_system'] === 1) {
            $user_is_pro = isset($wo['user']['is_pro']) ? (int)$wo['user']['is_pro'] : null;
            if ($user_is_pro === null) {
                // fallback DB check (safe)
                $safe_q2 = @mysqli_query($sqlConnect, "SELECT is_pro FROM {$tbl} WHERE user_id=" . (int)$accepted_user_id . " LIMIT 1");
                if ($safe_q2 && $r2 = mysqli_fetch_assoc($safe_q2)) {
                    $user_is_pro = (int)($r2['is_pro'] ?? 0);
                } else {
                    $user_is_pro = 0;
                }
            }
            if ($user_is_pro === 0) {
                $data['location'] = function_exists('Wo_SeoLink') ? Wo_SeoLink('index.php?link1=go-pro') : rtrim($site_base, '/') . '/index.php?link1=go-pro';
                bz_bridge_log('Wo_SSO_Login: membership go-pro override applied', ['user_id' => $accepted_user_id, 'redirect' => $data['location']]);
                echo json_encode($data); exit;
            }
        }
        
        // 4) posted_last_url (if provided and valid)
        // REPLACE existing posted_last_url acceptance code with this hardened validator
        
        // --- Hardened posted_last_url handling (replace existing posted_last_url block) ---
        if (!empty($posted_last_url)) {
            $candidate_raw = trim((string)$posted_last_url);
        
            // Reject protocol-relative (//...) — avoid host ambiguity
            if (strpos($candidate_raw, '//') === 0) {
                bz_bridge_log('posted_last_url rejected: protocol-relative URL', ['posted' => $candidate_raw]);
            } else {
                // Normalize path-only to absolute using $site_base
                if (strpos($candidate_raw, '/') === 0) {
                    $candidate_abs = rtrim($site_base, '/') . $candidate_raw;
                } else {
                    $candidate_abs = $candidate_raw;
                }
        
                // Basic parse and scheme validation
                $scheme = @parse_url($candidate_abs, PHP_URL_SCHEME);
                if (!in_array($scheme, ['http', 'https'], true)) {
                    bz_bridge_log('posted_last_url rejected: invalid scheme', ['posted' => $candidate_raw, 'scheme' => $scheme]);
                } else {
                    // Ensure same-site host
                    $site_host = bz_normalize_host(parse_url($site_base, PHP_URL_HOST) ?: '');
                    $candidate_host = bz_normalize_host(parse_url($candidate_abs, PHP_URL_HOST) ?: '');
        
                    // Reject if it references the bridge or SSO paths
                    $is_bridge_ref = bz_is_bridge_url($candidate_abs, $site_base);
        
                    if ($candidate_abs && $site_host && $candidate_host === $site_host && !$is_bridge_ref) {
                        $data['location'] = $candidate_abs;
                        bz_bridge_log('Wo_SSO_Login: using posted_last_url as redirect', ['posted_last_url' => $candidate_raw, 'final' => $data['location']]);
                        // success — clear loop counter cookie
                        if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
                        echo json_encode($data); exit;
                    } else {
                        bz_bridge_log('Wo_SSO_Login: posted_last_url rejected', [
                            'posted' => $candidate_raw,
                            'candidate' => $candidate_abs,
                            'candidate_host' => $candidate_host,
                            'site_host' => $site_host,
                            'is_bridge_ref' => $is_bridge_ref
                        ]);
                        // fall through to last_url fallback
                    }
                }
            }
        }
        // --- end hardened posted_last_url handling ---
        
        // 5) last_url fallback or default
        $data['location'] = !empty($last_url) && strpos($last_url, $site_base) === 0 ? $last_url : ($site_base . '/?cache=' . time());
        
        bz_bridge_log('Wo_SSO_Login: final redirect chosen', ['final' => $data['location']]);
        
        // Final safety: never return the bridge itself as redirect
        if (function_exists('bz_is_bridge_url') && !empty($data['location']) && bz_is_bridge_url($data['location'], $site_base)) {
            bz_bridge_log('Final redirect would go to bridge; replacing with site base to avoid loop', ['chosen' => $data['location']]);
            $data['location'] = rtrim($site_base, '/') . '/';
            if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
        }
        
        echo json_encode($data); exit;
        // ------------------------------

        bz_bridge_log('Wo_SSO_Login: success', ['user_id'=>$accepted_user_id,'session'=>$session_token,'reason'=>$accepted_reason,'matches'=>$accepted_matches,'redirect'=>$data['location']]);

        echo json_encode($data); exit;

    } else {
        $errors[] = 'WoWonder session not established after login.';
        bz_bridge_log('Wo_SSO_Login: WoWonder session not established', ['user_id'=>$accepted_user_id,'session'=>$session_token,'reason'=>$accepted_reason,'matches'=>$accepted_matches,'wo_loggedin'=>!empty($wo['loggedin'])]);
    }

    $data = ['status' => 500, 'message' => 'Session not established.'];
    echo json_encode($data); exit;
}

// -----------------------------
// Render bridge page -> POSTs to internal Wo_SSO_Login endpoint
bz_bridge_log('Rendering bridge page', ['sso_username'=>$sso_username,'sso_password_len'=>strlen($sso_password),'last_url'=>$last_url]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Signing you in…</title>
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:2rem;background:#0b1020;color:#e9eef7} .card{max-width:560px;margin:10vh auto;padding:1.5rem 1.75rem;background:#131a33;border-radius:14px} .title{font-size:1.25rem;margin:0 0 .5rem} .status{margin-top:1rem;padding:.75rem 1rem;border-radius:10px;background:#0e1530} .ok{background:#10351f} .err{background:#3a1414} .dbg pre{background:#0e1530;padding:.75rem;border-radius:8px;overflow:auto}</style>
</head>
<body>
  <div class="card">
    <div class="title">Signing you in…</div>
    <div id="status" class="status">Preparing secure session…</div>
    <?php if (bz_is_debug()): ?>
      <div class="dbg"><pre><?php echo htmlspecialchars(print_r([
          'ajax_url'=>$ajax_url,
          'post'=>['username'=>$sso_username,'password'=>'(sso-token)','last_url'=>$last_url,'remember_device'=>'on'],
          'session'=>$_SESSION ?? [],
          'cookies'=>$_COOKIE
      ], true)); ?></pre></div>
    <?php endif; ?>
  </div>
  <script>
  (function(){
    var ajaxUrl = <?php echo json_encode($ajax_url); ?>;
    var payload = {
      username: <?php echo json_encode($sso_username); ?>,
      password: <?php echo json_encode($sso_password); ?>,
      remember_device: 'on',
      last_url: <?php echo json_encode($last_url); ?>
    };
    var beaconUrl = <?php echo json_encode((isset($_SERVER['PHP_SELF'])?$_SERVER['PHP_SELF']:'/ww-sso-bridge.php') . '?sso_client_log=1'); ?>;
    var statusEl = document.getElementById('status');
    function beacon(msg, extra){
      try{
        var data = JSON.stringify({msg:msg,extra:extra||{},when:Date.now()});
        if (navigator.sendBeacon) navigator.sendBeacon(beaconUrl, data);
        else { var x = new XMLHttpRequest(); x.open('POST', beaconUrl, true); x.setRequestHeader('Content-Type','text/plain'); x.send(data); }
      }catch(e){}
    }
    statusEl && (statusEl.textContent = 'Contacting server…');
    beacon('bridge:init', {ajaxUrl: ajaxUrl, u: payload.username, last: payload.last_url});
    
      // Inside the existing immediate-invoked function where payload is built:
      // Defensive: do not post bridge as last_url
      try {
        if (payload.last_url && payload.last_url.indexOf('ww-sso-bridge.php') !== -1) {
          delete payload.last_url;
        }
      } catch(e) {}
    
      // After success redirect, clear client-side loop cookie
      function clearLoopCookie() {
        try { document.cookie = 'bz_bridge_loop=;path=/;Max-Age=0'; } catch(e) {}
      }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.withCredentials = true;
    xhr.timeout = 20000;
    xhr.onreadystatechange = function(){
      if (xhr.readyState === 4) {
        var ok=false, locationUrl=null, errors=null, res=null;
        try { res = JSON.parse(xhr.responseText); } catch(e) {}
        if (res) { ok = !!(res.status===200 || res.status===600) && !!res.location; locationUrl = res.location; errors = res.errors || null; }
        beacon('bridge:response', {status: res && res.status, location: locationUrl, errors: errors});
          if (ok) {
            statusEl && (statusEl.className='status ok', statusEl.textContent='Welcome back! Redirecting…');
            setTimeout(function(){ clearLoopCookie(); window.location.href = locationUrl; }, 450);
          } else {
          statusEl && (statusEl.className='status err', statusEl.textContent=(errors && errors.join ? errors.join(', ') : 'Unexpected response.'));
          beacon('bridge:failed', {http: xhr.status, response: xhr.responseText});
        }
      }
    };
    xhr.onerror = function(){ beacon('bridge:error', {http: xhr.status}); statusEl && (statusEl.className='status err', statusEl.textContent='Network or server error.'); };
    xhr.ontimeout = function(){ beacon('bridge:timeout', {}); statusEl && (statusEl.className='status err', statusEl.textContent='Request timed out.'); };
    var body = 'username=' + encodeURIComponent(payload.username)
             + '&password=' + encodeURIComponent(payload.password)
             + '&remember_device=on'
             + '&last_url=' + encodeURIComponent(payload.last_url);
    xhr.send(body);
  })();
  </script>
</body>
</html>