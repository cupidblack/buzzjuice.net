<?php
/**
 * qd-sso-bridge.php
 *
 * BuzzJuice → QuickDate SSO bridge (updated)
 *
 * Responsibilities:
 *  - Bootstrap QuickDate session (SessionStart())
 *  - Normalize SSO info from WP (session or buzz_sso cookie)
 *  - Map or auto-register QuickDate user id (qd_user_id)
 *  - Render a small JS bridge page which POSTs a signed token to do_login endpoint
 *  - Accept do_login POST and perform the >=3-identifier acceptance logic (without rotating PHPSESSID)
 *  - After successful login, sync WordPress user metadata into QuickDate using shared/wwqd_bridge.php helpers
 *
 * Notes:
 *  - This file expects shared/wwqd_bridge.php to be available (provides get_user_field_metadata,
 *    wp_get_full_user_data, sync_user_to_quickdate, qd_update_user, etc.).
 *  - The sync performed after successful login will prefer WordPress xprofile values, then usermeta,
 *    and will update QuickDate columns that exist in the users table (overwriting QD values with WP values).
 *  - This revision ensures buzz_sso cookie is issued as long-lived ("never-expire") so that
 *    the cookie/session pair remain valid until an explicit logout clears them.
 */

require_once __DIR__ . '/bootstrap.php';

// Use the shared bridging utilities (WordPress <-> QuickDate <-> WoWonder)
if (file_exists(__DIR__ . '/../shared/wwqd_bridge.php')) {
    require_once __DIR__ . '/../shared/wwqd_bridge.php';
} else {
    // Fallback to legacy path where this file historically lived in the QD repo
    if (file_exists(__DIR__ . '/requests/wp_user_bridge.php')) {
        require_once __DIR__ . '/requests/wp_user_bridge.php';
    }
}

// Aj class and useractions endpoints are used by other parts of QD; ensure they are available.
if (file_exists(__DIR__ . '/controllers/aj.php')) {
    require_once __DIR__ . '/controllers/aj.php';
}
if (file_exists(__DIR__ . '/requests/ajax/useractions.php')) {
    require_once __DIR__ . '/requests/ajax/useractions.php';
}

/* CONFIG */
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/qd_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$BUZZ_SSO_SECRET = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

/* LOGGING */
function qd_bridge_log($msg, $ctx = []) {
    $data = [
        'ts' => gmdate('Y-m-d H:i:s'),
        'php_session_id' => function_exists('session_id') ? session_id() : null,
        'session_name' => function_exists('session_name') ? session_name() : null,
        'shadow_session_id' => isset($_COOKIE['PHPSESSID']) ? 'shadow_' . $_COOKIE['PHPSESSID'] : null,
        'buzz_sso_len' => isset($_COOKIE[BUZZ_SSO_COOKIE]) ? strlen($_COOKIE[BUZZ_SSO_COOKIE]) : 0,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    if (qd_is_debug()) {
        $data['cookies'] = $_COOKIE ?? [];
        $data['session'] = $_SESSION ?? [];
        $data['server'] = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'HTTPS' => $_SERVER['HTTPS'] ?? null
        ];
        $data['sess_cookie_params'] = function_exists('session_get_cookie_params') ? session_get_cookie_params() : null;
    }
    if ($ctx) $data['ctx'] = $ctx;
    @file_put_contents(BUZZ_SSO_BRIDGE_LOG, '['.$data['ts'].'] '.$msg.' | '.json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
}
function qd_is_debug() {
    return (bool)((isset($_GET['sso_debug']) && $_GET['sso_debug'] === '1') || (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG));
}

/* Helpers: base64url */
function _qd_b64url_decode($str) {
    $p = strtr((string)$str, '-_', '+/');
    $m = strlen($p) % 4; if ($m) $p .= str_repeat('=', 4 - $m);
    return base64_decode($p);
}
function _qd_b64url_encode($bin) {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

/* Parse/verify SSO cookie / password tokens */
function qd_sso_verify_token($token, $secret) {
    if (!$token || !$secret) return false;
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return false;
    $json = _qd_b64url_decode($parts[0]);
    $sig  = _qd_b64url_decode($parts[1]);
    if ($json === false || $sig === false) return false;
    $calc = hash_hmac('sha256', $json, (string)$secret, true);
    if (!hash_equals((string)$calc, (string)$sig)) return false;
    $payload = json_decode($json, true);
    if (!is_array($payload)) return false;
    if (!empty($payload['exp']) && time() > (int)$payload['exp']) return false;
    return $payload;
}
function qd_parse_sso_password_token($token, $secret) {
    if (!$token || !$secret) return false;
    if (strpos($token, 'WPSSO.v1.') !== 0) return false;
    $body = substr($token, strlen('WPSSO.v1.'));
    $parts = explode('.', $body, 2);
    if (count($parts) !== 2) return false;
    $json = _qd_b64url_decode($parts[0]);
    $sig  = _qd_b64url_decode($parts[1]);
    if ($json === false || $sig === false) return false;
    $calc = hash_hmac('sha256', $json, (string)$secret, true);
    if (!hash_equals((string)$calc, (string)$sig)) return false;
    $payload = json_decode($json, true);
    if (!is_array($payload)) return false;
    if (!empty($payload['exp']) && time() > (int)$payload['exp']) return false;
    return $payload;
}

/**
 * Issue a long-lived buzz_sso cookie (effectively "never expire" for practical purposes).
 * We set a distant expiry (10 years) when issuing the cookie. The bridge will still explicitly
 * clear this cookie during logout (qd_clear_and_logout).
 *
 * Payload should contain at least: wp_user_id, wp_user_login, wp_user_email, qd_user_id.
 */
function qd_issue_buzz_sso_cookie(array $payload) {
    global $BUZZ_SSO_SECRET;
    if (!$BUZZ_SSO_SECRET) {
        qd_bridge_log('qd_issue_buzz_sso_cookie: missing BUZZ_SSO_SECRET', ['payload_keys'=>array_keys($payload)]);
        return false;
    }
    $now = time();
    // 10 years in seconds
    $ten_years = 10 * 365 * 24 * 60 * 60;
    $exp = $now + $ten_years;
    $payload['iat'] = $now;
    $payload['exp'] = $exp;
    $json = json_encode($payload);
    $sig  = hash_hmac('sha256', $json, (string)$BUZZ_SSO_SECRET, true);
    $token = _qd_b64url_encode($json) . '.' . _qd_b64url_encode($sig);

    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, $token, ['expires'=>$exp,'path'=>'/','domain'=>BUZZ_COOKIE_DOMAIN,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    } else {
        setcookie(BUZZ_SSO_COOKIE, $token, $exp, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    $_COOKIE[BUZZ_SSO_COOKIE] = $token;
    qd_bridge_log('Issued long-lived buzz_sso cookie', ['expires'=>date('c', $exp), 'payload_subset'=>array_intersect_key($payload,array_flip(['wp_user_id','wp_user_login','wp_user_email','qd_user_id']))]);
    return $token;
}

/* ----------------------------- Added: Shadow reconciliation helpers for QuickDate bridge -------------
   Goals:
   - If browser/QuickDate is using a different shadow file than WordPress canonical shadow,
     remove mismatched shadow(s) that reference the same WP user id and create the canonical shadow
     file sess_shadow_shadow_{wp_sid} (.ser/.json siblings), so QuickDate will hydrate against
     the same canonical WP shadow.
   - Do not modify or delete WordPress PHPSESSID cookie.
-----------------------------------------------------------------------------*/

/**
 * Best-effort decode of buzz_sso cookie payload without requiring the secret (or with it when provided).
 * Returns array payload or null.
 */
function qd_parse_buzz_sso_cookie_payload($token, $secret = null) {
    if (!$token) return null;
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    $json = _qd_b64url_decode($parts[0]);
    $sig  = _qd_b64url_decode($parts[1]);
    if ($json === false) return null;
    if ($secret) {
        $calc = hash_hmac('sha256', $json, (string)$secret, true);
        if (!hash_equals($calc, (string)$sig)) {
            qd_bridge_log('buzz_sso cookie HMAC mismatch (bridge)', ['token_preview' => substr($token,0,24)]);
            return null;
        }
    }
    $payload = @json_decode($json, true);
    if (!is_array($payload)) return null;
    return $payload;
}

/**
 * Locate shared shadow directories (candidates). Returns array of writable dirs.
 */
function qd_locate_shadow_dirs() {
    $candidates = [];
    // prefer configured path if any (shared/wwqd_bridge or WP mu-plugin may define a constant)
    if (defined('BUZZ_SSO_SHADOW_PATH') && BUZZ_SSO_SHADOW_PATH) $candidates[] = rtrim(BUZZ_SSO_SHADOW_PATH, DIRECTORY_SEPARATOR);
    $candidates[] = realpath(__DIR__ . '/../shared/sso_sessions') ?: (__DIR__ . '/../shared/sso_sessions');
    $candidates[] = realpath(__DIR__ . '/../../shared/sso_sessions') ?: (__DIR__ . '/../../shared/sso_sessions');
    $candidates[] = realpath(__DIR__ . '/shared/sso_sessions') ?: (__DIR__ . '/shared/sso_sessions');

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

/**
 * Remove shadow files in candidate directories that reference the same wp_user_id but are NOT the expected canonical shadow filename.
 * Returns true if any removed.
 */
function qd_cleanup_shadow_mismatches($payload) {
    if (empty($payload) || !is_array($payload)) return false;
    $expected_wp_user_id = isset($payload['wp_user_id']) ? (int)$payload['wp_user_id'] : 0;
    $expected_session_id = isset($payload['session_id']) ? (string)$payload['session_id'] : ($payload['wp_php_session_id'] ?? '');
    if (!$expected_wp_user_id || !$expected_session_id) return false;

    $expected_shadow_filename = 'sess_' . 'shadow_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $expected_session_id);

    $dirs = qd_locate_shadow_dirs();
    if (empty($dirs)) {
        qd_bridge_log('No shadow dirs found for cleanup', ['candidates_checked' => 0]);
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
            // skip expected file
            if ($f === $expected_shadow_filename) continue;

            $content = @file_get_contents($full, false, null, 0, 65536);
            if ($content === false || $content === '') continue;

            $found_wp_id = null;
            $maybe = @unserialize($content);
            if ($maybe !== false && is_array($maybe) && array_key_exists('wp_user_id', $maybe)) {
                $found_wp_id = (int)$maybe['wp_user_id'];
            } else {
                $maybe_json = @json_decode($content, true);
                if (is_array($maybe_json) && array_key_exists('wp_user_id', $maybe_json)) {
                    $found_wp_id = (int)$maybe_json['wp_user_id'];
                } else {
                    if (preg_match('/["\']wp_user_id["\']\s*[:=]\s*([0-9]+)/i', $content, $m)) {
                        $found_wp_id = (int)$m[1];
                    }
                }
            }

            if ($found_wp_id === $expected_wp_user_id) {
                $deleted_any = false;
                @unlink($full) && $deleted_any = true;
                $siblings = [$full . '.ser', $full . '.json'];
                foreach ($siblings as $s) { if (is_file($s)) { @unlink($s); $deleted_any = true; } }
                if ($deleted_any) {
                    $removed[] = $full;
                    qd_bridge_log('Removed mismatched shadow file', ['removed'=>$full,'expected'=>$expected_shadow_filename,'shadow_dir'=>$dir]);
                } else {
                    qd_bridge_log('Failed to remove mismatched shadow file (permission?)', ['file'=>$full,'shadow_dir'=>$dir]);
                }
            }
        }
    }
    return !empty($removed);
}

/**
 * Write canonical shadow file for WP session id derived from payload.
 * Returns true if written to at least one dir.
 */
function qd_write_canonical_shadow_file(array $payload) {
    $wp_sid = $payload['session_id'] ?? $payload['wp_php_session_id'] ?? null;
    if (!$wp_sid) return false;
    $shadow_id = 'shadow_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$wp_sid);
    $dirs = qd_locate_shadow_dirs();
    if (empty($dirs)) {
        qd_bridge_log('No writable shadow dirs to write canonical shadow', ['shadow_id'=>$shadow_id]);
        return false;
    }

    $shadow = [];
    $allow_keys = [
        'wp_user_id','wp_user_login','wp_user_email',
        'wo_user_id','qd_user_id','qd_ready','expected_user_id',
        'buzz_sso_last_sync','wp_php_session_id','wp_session_name'
    ];
    foreach ($allow_keys as $k) {
        if (array_key_exists($k, $payload)) $shadow[$k] = $payload[$k];
    }
    $shadow['wp_php_session_id'] = $payload['session_id'] ?? ($shadow['wp_php_session_id'] ?? null);
    $shadow['wp_session_name'] = $payload['session_name'] ?? ($shadow['wp_session_name'] ?? session_name());
    if (empty($shadow['buzz_sso_last_sync'])) $shadow['buzz_sso_last_sync'] = time();

    $payload_ser = @serialize($shadow);
    if ($payload_ser === false) {
        qd_bridge_log('Failed to serialize canonical shadow payload', ['shadow_id'=>$shadow_id]);
        return false;
    }
    $json_payload = @json_encode($shadow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $written_any = false;
    foreach ($dirs as $dir) {
        @mkdir($dir, 0750, true);
        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $shadow_id;
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $payload_ser, LOCK_EX) === false) {
            @unlink($tmp);
            qd_bridge_log('Failed to write canonical shadow tmp file', ['path'=>$path,'dir'=>$dir]);
            continue;
        }
        @chmod($tmp, 0640);
        if (!@rename($tmp, $path)) {
            if (!@copy($tmp, $path) || !@unlink($tmp)) {
                @unlink($tmp);
                qd_bridge_log('Failed to atomically move canonical shadow tmp', ['tmp'=>$tmp,'path'=>$path]);
                continue;
            }
        }
        @chmod($path, 0640);
        // .ser copy
        @file_put_contents($path . '.ser', $payload_ser, LOCK_EX);
        @chmod($path . '.ser', 0640);
        if ($json_payload !== false) {
            @file_put_contents($path . '.json', $json_payload, LOCK_EX);
            @chmod($path . '.json', 0640);
        }
        qd_bridge_log('Wrote canonical shadow file', ['path'=>$path,'shadow_id'=>$shadow_id,'dir'=>$dir]);
        $written_any = true;
        break;
    }
    return $written_any;
}

/**
 * Attempt reconciliation if QuickDate runtime session differs from WordPress canonical session.
 * - Uses buzz_sso cookie payload (verified when secret present, else best-effort)
 * - Cleans mismatched shadow files and writes canonical shadow
 * - Rehydrates local QuickDate session from payload if mismatch found (best-effort)
 */
function qd_attempt_session_reconciliation_if_required() {
    global $BUZZ_SSO_SECRET;
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) return;

    $current_name = session_name();
    $current_sid  = session_id();

    // Try to get canonical WP payload (verify if secret available)
    $wp_payload = qd_parse_buzz_sso_cookie_payload($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
    if (!$wp_payload) {
        $wp_payload = qd_parse_buzz_sso_cookie_payload($_COOKIE[BUZZ_SSO_COOKIE], null);
        if ($wp_payload) qd_bridge_log('Using buzz_sso cookie payload without verification (bridge)', ['preview'=>substr($_COOKIE[BUZZ_SSO_COOKIE],0,24)]);
    }
    if (!$wp_payload) return;

    $wp_sname = $wp_payload['session_name'] ?? $wp_payload['wp_session_name'] ?? null;
    $wp_sid   = $wp_payload['session_id']   ?? $wp_payload['wp_php_session_id'] ?? null;

    // If cookie lacks wp_user_id but we have wp_sid, attempt to load shadow payload to enrich
    if (empty($wp_payload['wp_user_id']) && !empty($wp_sid)) {
        $shadow = qd_find_wp_shadow_payload($wp_sid);
        if (is_array($shadow) && !empty($shadow)) {
            $wp_payload = array_merge($wp_payload, $shadow);
            $wp_sname = $wp_payload['session_name'] ?? $wp_sname;
            $wp_sid   = $wp_payload['session_id'] ?? $wp_sid;
            qd_bridge_log('Loaded WP shadow to enrich payload (bridge)', ['wp_sid_preview'=>substr($wp_sid,0,12)]);
        }
    }

    $mismatch = false;
    if ($wp_sname && $current_name !== $wp_sname) $mismatch = true;
    if ($wp_sid && $current_sid !== $wp_sid) $mismatch = true;

    if (!$mismatch) {
        qd_bridge_log('No session mismatch detected (bridge)', ['current_name'=>$current_name,'current_sid_preview'=>substr($current_sid,0,12),'wp_name'=>$wp_sname,'wp_sid_preview'=>substr($wp_sid ?? '',0,12)]);
        return;
    }

    qd_bridge_log('Session mismatch detected; reconciling (bridge)', ['current_name'=>$current_name,'current_sid_preview'=>substr($current_sid,0,12),'wp_name'=>$wp_sname,'wp_sid_preview'=>substr($wp_sid ?? '',0,12)]);

    // 1) Cleanup mismatched shadow files that reference the same WP user
    try {
        qd_cleanup_shadow_mismatches($wp_payload);
    } catch (Throwable $e) {
        qd_bridge_log('Error cleaning shadow mismatches', ['err'=>$e->getMessage()]);
    }

    // 2) Write canonical shadow file for WP sid so other apps pick it up
    try {
        qd_write_canonical_shadow_file($wp_payload);
    } catch (Throwable $e) {
        qd_bridge_log('Error writing canonical shadow', ['err'=>$e->getMessage()]);
    }

    // 3) Best-effort remove current local session file (so new session starts clean)
    qd_unlink_local_session_file_if_exists($current_sid);

    // 4) Reset runtime session and rehydrate from WP payload
    $_SESSION = [];
    @session_unset();
    @session_destroy();

    @session_start();
    // Rehydrate a minimal set of keys into QuickDate session
    $rehyd_keys = ['wp_user_id','wp_user_login','wp_user_email','wo_user_id','qd_user_id','buzz_sso_last_sync','wp_php_session_id','wp_session_name','session_id','session_name'];
    foreach ($rehyd_keys as $k) {
        if (isset($wp_payload[$k])) {
            $t = $k;
            if ($k === 'session_id' || $k === 'wp_php_session_id') $t = 'wp_php_session_id';
            if ($k === 'session_name' || $k === 'wp_session_name') $t = 'wp_session_name';
            $_SESSION[$t] = $wp_payload[$k];
        }
    }
    if (!empty($wp_payload['wp_user_id'])) $_SESSION['wp_user_id'] = (int)$wp_payload['wp_user_id'];
    if (!empty($wp_payload['wp_user_login'])) $_SESSION['wp_user_login'] = (string)$wp_payload['wp_user_login'];
    if (!empty($wp_payload['wp_user_email'])) $_SESSION['wp_user_email'] = (string)$wp_payload['wp_user_email'];
    if (!empty($wp_payload['qd_user_id'])) $_SESSION['qd_user_id'] = (int)$wp_payload['qd_user_id'];

    qd_bridge_log('Rehydrated local QuickDate session from WP payload (bridge)', ['new_local_sid'=>session_id()]);
}

/**
 * Helper: find WP shadow payload (serialized/json) for a given wp_session_id in candidate shadow dirs.
 */
function qd_find_wp_shadow_payload($wp_session_id) {
    if (!$wp_session_id) return null;
    $dirs = qd_locate_shadow_dirs();
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
                $maybe = @json_decode($content, true);
                if (is_array($maybe)) return $maybe;
                $un = @unserialize($content);
                if (is_array($un)) return $un;
            }
        }
    }
    return null;
}

/**
 * Remove local QuickDate session file (best-effort). Do NOT touch WP cookie.
 */
function qd_unlink_local_session_file_if_exists($sid) {
    if (!$sid) return false;
    $save_path = (string)ini_get('session.save_path');
    if (trim($save_path) === '') $save_path = sys_get_temp_dir();
    if (preg_match('#^N;(.+)#', $save_path, $m)) $save_path = $m[1];
    $save_path = rtrim($save_path, DIRECTORY_SEPARATOR);
    $file = $save_path . DIRECTORY_SEPARATOR . 'sess_' . $sid;
    if (is_file($file)) {
        @unlink($file);
        qd_bridge_log('Removed local session file (bridge reconcile)', ['file'=>$file,'sid'=>$sid]);
        return true;
    }
    return false;
}
/* ----------------------------- End added helpers ----------------------------- */

/* SESSION BOOTSTRAP: use QuickDate SessionStart() which was included by bootstrap.php
   Make bootstrap idempotent per-request to avoid double starts when included multiple times. */
static $qd_session_bootstrapped = false;
if (!$qd_session_bootstrapped) {
    try {
        if (function_exists('SessionStart')) {
            SessionStart();
        } else {
            // Fallback: start native session but we prefer SessionStart
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }
    } catch (Throwable $e) {
        qd_bridge_log('SessionStart() exception', ['ex'=>$e->getMessage()]);
    }
    $qd_session_bootstrapped = true;
}

/* Log bootstrap */
qd_bridge_log('SessionStart() called', ['phpSessionId'=>session_id(), 'shadow_session_id'=> (isset($_COOKIE['PHPSESSID']) ? 'shadow_'.$_COOKIE['PHPSESSID'] : null)]);

// Immediately attempt reconciliation with WordPress canonical session (best-effort).
try {
    qd_attempt_session_reconciliation_if_required();
} catch (Throwable $e) {
    qd_bridge_log('Session reconciliation attempt threw', ['err'=>$e->getMessage()]);
}

/* Defensive sync: run once every 4 hours per session */
if (!isset($_SESSION['buzz_sso_defensive_last']) || (time() - (int)$_SESSION['buzz_sso_defensive_last']) > 4*3600) {
    $_SESSION['buzz_sso_defensive_last'] = time();
    $errs = [];
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) $errs[] = 'buzz_sso_cookie_missing';
    if (empty($_SESSION['wp_user_login'])) $errs[] = 'wp_user_login_missing';
    if (empty($_SESSION['qd_user_id']) || !is_numeric($_SESSION['qd_user_id'])) $errs[] = 'qd_user_id_missing_or_invalid';
    if ($errs) qd_bridge_log('Defensive sync checks', ['errs'=>$errs]);
}

/* Normalize: prefer serialized session block, else cookie payload */
function normalize_sso_session() {
    global $BUZZ_SSO_SECRET;
    // If mu-plugin exported serialized block into session (buzz_sso_serialized) — hydrate
    if (!empty($_SESSION['buzz_sso_serialized']) && is_string($_SESSION['buzz_sso_serialized'])) {
        $decoded = @unserialize($_SESSION['buzz_sso_serialized']);
        if (is_array($decoded)) {
            foreach ($decoded as $k => $v) {
                if (!isset($_SESSION[$k])) $_SESSION[$k] = $v;
            }
            qd_bridge_log('Session normalized from buzz_sso_serialized', ['decoded_keys'=>array_keys($decoded)]);
        } else {
            qd_bridge_log('Failed to unserialize buzz_sso_serialized');
        }
    }

    // Next fallback: signed buzz_sso cookie
    if (!empty($_COOKIE[BUZZ_SSO_COOKIE]) && $BUZZ_SSO_SECRET) {
        $payload = qd_sso_verify_token($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
        if (is_array($payload)) {
            foreach (['wp_user_id','wp_user_login','wp_user_email','qd_user_id'] as $f) {
                if (!empty($payload[$f]) && !isset($_SESSION[$f])) {
                    $_SESSION[$f] = $payload[$f];
                }
            }
            qd_bridge_log('Session normalized from buzz_sso cookie', ['payload_subset'=>array_intersect_key($payload,array_flip(['wp_user_id','wp_user_login','wp_user_email','qd_user_id']))]);
        }
    }
}
normalize_sso_session();

/* Validate buzz_sso cookie presence/claims — else clear session and redirect to logout */
function qd_clear_and_logout($reason='unknown') {
    global $config;
    qd_bridge_log('Clearing session SSO keys and redirecting to logout', ['reason'=>$reason]);

    // Ensure session started so we can safely clear SSO-related keys without destroying PHPSESSID
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    // Only remove SSO-related keys — do not call session_destroy()
    $sso_keys = [
        'wp_user_id','wp_user_login','wp_user_email',
        'wo_user_id','qd_user_id','qd_ready','expected_user_id',
        'buzz_sso_last_sync','wp_php_session_id','wp_session_name',
        'buzz_sso_last','buzz_sso_serialized','wp_sso_login'
    ];
    foreach ($sso_keys as $k) {
        if (isset($_SESSION[$k])) unset($_SESSION[$k]);
    }

    // Also remove any other QD-specific tokens that may rely on SSO
    if (isset($_SESSION['JWT'])) {
        unset($_SESSION['JWT']);
    }

    // Expire buzz_sso cookie on the shared domain (explicit logout)
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>time()-3600,'path'=>'/','domain'=>BUZZ_COOKIE_DOMAIN,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    } else {
        setcookie(BUZZ_SSO_COOKIE, '', time()-3600, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    // (Do not destroy PHPSESSID — WordPress owns it and QD expects it to exist.)

    // Redirect to WordPress logout/streams page as before
    $base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
    $target = ($base ?: '') . '/../wp-login.php';
    header('Location: ' . $target);
    exit();
}

/* NOTE: Do NOT force immediate logout if buzz_sso cookie is absent.
   The WP mu-plugin may have exported a serialized session (buzz_sso_serialized)
   and can regenerate the buzz_sso cookie. We only enforce logout later when
   both cookie and session are invalid/stale.
*/
if (!$BUZZ_SSO_SECRET) {
    qd_bridge_log('Missing BUZZ_SSO_SECRET');
    qd_clear_and_logout('missing_secret');
}

/* Validate buzz_sso cookie presence/claims — else allow session if it's fresh and has canonical fields */
$cookie_payload = null;
if (!empty($_COOKIE[BUZZ_SSO_COOKIE]) && $BUZZ_SSO_SECRET) {
    $cookie_payload = qd_sso_verify_token($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
    if (!is_array($cookie_payload)) $cookie_payload = null;
} else {
    // Log that cookie was not present (do NOT logout immediately – see above note)
    qd_bridge_log('buzz_sso cookie not present (will attempt session-only flow if session has canonical values)');
}

$session_has_core = (!empty($_SESSION['wp_user_login']) && !empty($_SESSION['wp_user_id']) && !empty($_SESSION['wp_user_email']));
$session_qd_valid = (!empty($_SESSION['qd_user_id']) && is_numeric($_SESSION['qd_user_id']));

// Acceptable if cookie present & valid with required fields
if ($cookie_payload && !empty($cookie_payload['wp_user_id']) && !empty($cookie_payload['wp_user_login']) && !empty($cookie_payload['wp_user_email'])) {
    // OK, cookie valid; let normal flow continue (cookie_payload used below)
} else {
    // No valid cookie — allow continuing if session has canonical SSO fields and was synced recently
    $last_sync = isset($_SESSION['buzz_sso_last_sync']) ? (int)$_SESSION['buzz_sso_last_sync'] : 0;
    $max_age = 1 * 1200; // twenty minutes defensive window
    if ($session_has_core && $session_qd_valid && ($last_sync && (time() - $last_sync) <= $max_age)) {
        qd_bridge_log('No valid buzz_sso cookie but proceeding: session has recent SSO sync', ['last_sync'=>$last_sync,'age'=>time()-$last_sync]);
        // safe to continue using session values
    } else {
        qd_bridge_log('buzz_sso invalid or missing and no fresh session — clearing and logout', ['cookie_present'=>!empty($_COOKIE[BUZZ_SSO_COOKIE]), 'session_has_core'=>$session_has_core, 'last_sync'=>$last_sync]);
        qd_clear_and_logout('invalid_or_incomplete_cookie');
    }
}

/* Canonical fields from session/cookie */
$claim_wp_user_id    = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : (int)($cookie_payload['wp_user_id'] ?? 0);
$claim_wp_user_login = isset($_SESSION['wp_user_login']) ? (string)$_SESSION['wp_user_login'] : (string)($cookie_payload['wp_user_login'] ?? '');
$claim_wp_user_email = isset($_SESSION['wp_user_email']) ? (string)$_SESSION['wp_user_email'] : (string)($cookie_payload['wp_user_email'] ?? '');
$claim_qd_user_id    = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : (int)($cookie_payload['qd_user_id'] ?? 0);

/* Keep wp_user_login immutable once set */
if (!empty($_SESSION['wp_user_login']) && $_SESSION['wp_user_login'] !== $claim_wp_user_login) {
    qd_bridge_log('Attempt to change wp_user_login detected; preserving existing', ['existing'=>$_SESSION['wp_user_login'],'incoming'=>$claim_wp_user_login]);
    $claim_wp_user_login = $_SESSION['wp_user_login'];
}

/* Map / register QuickDate user if necessary */
/* (Helper DB functions: qd_find_user_by_id, qd_get_user_row, qd_find_user_by_login_email defined below) */
function qd_find_user_by_id($id) {
    $db = get_qd_db_conn();
    if (!$db || !$id) return 0;
    $id = (int)$id;
    $res = $db->query("SELECT id FROM users WHERE id={$id} LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return (int)$row['id'];
    return 0;
}
function qd_get_user_row($id) {
    $db = get_qd_db_conn();
    if (!$db || !$id) return false;
    $id = (int)$id;
    $res = $db->query("SELECT * FROM users WHERE id={$id} LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return $row;
    return false;
}
function qd_find_user_by_login_email($login, $email) {
    $db = get_qd_db_conn();
    if (!$db) return 0;
    $escL = $db->real_escape_string((string)$login);
    $escE = $db->real_escape_string((string)$email);
    $res = $db->query("SELECT id FROM users WHERE username='{$escL}' AND email='{$escE}' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return (int)$row['id'];
    return 0;
}

/**
 * qd_register_user() — use QuickDate users endpoint instead of direct DB insert.
 *
 * Returns new QuickDate user id (int) on success, or 0 on failure.
 */
if (!function_exists('qd_register_user')) {
    function qd_register_user($login, $email, $wp_user_id = 0) {
        if (!function_exists('LoadEndPointResource')) {
            qd_bridge_log('qd_register_user: LoadEndPointResource missing');
            return 0;
        }
        $user = LoadEndPointResource('users');
        if (!$user || !method_exists($user, 'register')) {
            qd_bridge_log('qd_register_user: users endpoint missing or register() not available', ['user_resource_exists'=> (bool)$user]);
            return 0;
        }
        $preferred_login = isset($_SESSION['wp_user_login']) && $_SESSION['wp_user_login'] !== '' ? (string)$_SESSION['wp_user_login'] : (string)$login;
        $username = preg_replace('~[^a-z0-9_.-]~i', '', $preferred_login) ?: 'wpuser' . (int)$wp_user_id;

        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        $wp_full = (function_exists('wp_get_full_user_data') && $conn && $wp_user_id) ? wp_get_full_user_data($conn, $wp_user_id) : [];
        $avatar = $wp_full['xprofile']['avatar'] ?? $wp_full['meta']['avatar'] ?? ($GLOBALS['config']->userDefaultAvatar ?? '');
        // DON'T include 'cover' by default — some QuickDate installations lack that column
        $password = bin2hex(random_bytes(8));
        if ($conn && $wp_user_id) {
            $res = @mysqli_query($conn, "SELECT user_pass FROM wp_users WHERE ID='" . intval($wp_user_id) . "' LIMIT 1");
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                if (!empty($row['user_pass'])) $password = $row['user_pass'];
            }
        }
        $imported_avatar = $avatar;
        if (!empty($avatar) && method_exists($user, 'ImportImageFromLogin')) {
            try {
                $imp = $user->ImportImageFromLogin($avatar, 1);
                if (!empty($imp)) $imported_avatar = $imp;
            } catch (Throwable $e) {
                qd_bridge_log('qd_register_user: ImportImageFromLogin failed', ['ex'=>$e->getMessage(),'avatar'=>$avatar]);
            }
        }
        $now = time();
        $re_data = [
            'username'      => $username,
            'password'      => $password,
            'email'         => $email,
            'avatar'        => $imported_avatar,
            'active'        => 1,
            'src'           => 'wp-sso',
            'wp_user_id'    => (int)$wp_user_id,
            'ip_address'    => function_exists('get_ip_address') ? get_ip_address() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            'language'      => $GLOBALS['config']->defualtLang ?? 'english',
            'registered'    => date('Y-m-d H:i:s', $now),
            'social_login'  => 1,
            'start_up'      => 0
        ];
        if (!empty($wp_full['xprofile']['first_name']) || !empty($wp_full['xprofile']['last_name'])) {
            $re_data['first_name'] = $wp_full['xprofile']['first_name'] ?? '';
            $re_data['last_name']  = $wp_full['xprofile']['last_name'] ?? '';
        } elseif (!empty($wp_full['meta']['first_name']) || !empty($wp_full['meta']['last_name'])) {
            $re_data['first_name'] = $wp_full['meta']['first_name'] ?? '';
            $re_data['last_name']  = $wp_full['meta']['last_name'] ?? '';
        }
        try {
            $reg = $user->register($re_data);
        } catch (Throwable $e) {
            qd_bridge_log('qd_register_user: user->register() exception', ['ex'=>$e->getMessage(), 'payload'=>$re_data]);
            return 0;
        }
        if (is_array($reg) && isset($reg['code']) && intval($reg['code']) === 200 && !empty($reg['userId'])) {
            $created_id = (int)$reg['userId'];
        } elseif (is_array($reg) && !empty($reg['id'])) {
            $created_id = (int)$reg['id'];
        } else {
            qd_bridge_log('qd_register_user: register() returned unexpected result', ['result'=>$reg]);
            return 0;
        }
        try {
            if (method_exists($user, 'SetLoginWithSession') && !empty($email)) {
                $user->SetLoginWithSession($email);
            }
        } catch (Throwable $e) {
            qd_bridge_log('qd_register_user: SetLoginWithSession exception', ['ex'=>$e->getMessage()]);
        }
        qd_bridge_log('qd_register_user: Auto-registered QuickDate user', ['id'=>$created_id,'username'=>$username,'email'=>$email,'re_data'=>$re_data]);
        return $created_id;
    }
}

/* Determine final_qd_user_id
 * Behavior:
 * - If session has qd id and the corresponding QuickDate row exists AND matches username+email => accept.
 * - Else attempt to find by username+email.
 * - Else auto-register (if enabled) and set session qd_user_id to created id (and try to update WP usermeta).
 * - If still nothing, fallback to original session qd if it now exists.
 * - Otherwise logout.
 */
$final_qd_user_id = 0;
$orig_session_qd = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : 0;

qd_bridge_log('Mapping start', ['claim_qd'=>$claim_qd_user_id,'session_qd'=>$orig_session_qd,'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email]);

// If session contains all four canonical values, perform strict verification first
$has_all_canonical = ($claim_qd_user_id && $claim_wp_user_id && $claim_wp_user_login && $claim_wp_user_email);
if ($has_all_canonical) {
    qd_bridge_log('All canonical SSO values present — performing strict qd_user_id verification', [
        'claim_qd'=>$claim_qd_user_id,
        'wp_user_id'=>$claim_wp_user_id,
        'wp_user_login'=>$claim_wp_user_login,
        'wp_user_email'=>$claim_wp_user_email
    ]);
    $row = qd_get_user_row($claim_qd_user_id);
    if ($row) {
        $db_un = isset($row['username']) ? (string)$row['username'] : '';
        $db_em = isset($row['email']) ? (string)$row['email'] : '';
        // require both username and email to match (case-insensitive)
        if (strcasecmp($db_un, $claim_wp_user_login) === 0 && strcasecmp($db_em, $claim_wp_user_email) === 0) {
            // Good — use this qd id
            $final_qd_user_id = (int)$claim_qd_user_id;
            qd_bridge_log('Strict verification successful — qd_user_id accepted', ['qd_user_id'=>$final_qd_user_id]);
        } else {
            // Mismatch: prefer to clear stale qd id and perform mapping/registration
            qd_bridge_log('Strict verification failed — qd_user_id exists but username/email mismatch; clearing session qd_user_id and forcing re-map/register', [
                'qd_user_id'=>$claim_qd_user_id,
                'db_username'=>$db_un,
                'db_email'=>$db_em,
                'session_login'=>$claim_wp_user_login,
                'session_email'=>$claim_wp_user_email
            ]);
            // Clear session qd id so subsequent flow will try login+email and register
            if (isset($_SESSION['qd_user_id'])) unset($_SESSION['qd_user_id']);
            $claim_qd_user_id = 0;
            $orig_session_qd = 0;
        }
    } else {
        // qd id missing from DB — clear and force re-map/register
        qd_bridge_log('Strict verification failed — qd_user_id not found in DB; clearing session qd_user_id and forcing re-map/register', ['qd_user_id'=>$claim_qd_user_id]);
        if (isset($_SESSION['qd_user_id'])) unset($_SESSION['qd_user_id']);
        $claim_qd_user_id = 0;
        $orig_session_qd = 0;
    }
}

// If not resolved by strict check above, follow the normal mapping/registration flow
if (!$final_qd_user_id) {
    // 1) If cookie/session claim contains qd_user_id and it exists in DB => use it
    if ($claim_qd_user_id && qd_find_user_by_id($claim_qd_user_id)) {
        $final_qd_user_id = $claim_qd_user_id;
        qd_bridge_log('Using qd_user_id from cookie/session (exists in DB)', ['qd_user_id'=>$final_qd_user_id]);
    } else {
        // 2) Try to find by login+email
        $found = qd_find_user_by_login_email($claim_wp_user_login, $claim_wp_user_email);
        if ($found) {
            $final_qd_user_id = $found;
            qd_bridge_log('Mapped qd_user_id via login+email', ['qd_user_id'=>$final_qd_user_id]);
            // Persist mapping to WP usermeta if we have a WP user id
            if (!empty($claim_wp_user_id)) {
                $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
                if ($wp_conn && function_exists('wp_update_usermeta')) {
                    try {
                        // wp_update_usermeta in shared/wwqd_bridge supports array form or key/value
                        wp_update_usermeta($wp_conn, (int)$claim_wp_user_id, ['qd_user_id' => (int)$final_qd_user_id], null);
                        qd_bridge_log('Persisted mapped qd_user_id to WordPress usermeta', ['wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id]);
                    } catch (Throwable $e) {
                        qd_bridge_log('Exception persisting qd_user_id to WP usermeta', ['ex'=>$e->getMessage(),'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id]);
                    }
                } else {
                    qd_bridge_log('WP DB connection or wp_update_usermeta() not available; cannot persist qd_user_id', ['wp_conn'=>(bool)$wp_conn,'has_wp_update_func'=>function_exists('wp_update_usermeta')]);
                }
            }
        } else {
            // 3) Nothing found — attempt auto-register (when allowed)
            if (BUZZ_SSO_AUTO_REGISTER) {
                qd_bridge_log('No mapping found — attempting auto-register', ['login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email,'orig_session_qd'=>$orig_session_qd]);
                $created = qd_register_user($claim_wp_user_login, $claim_wp_user_email, $claim_wp_user_id);
                if ($created) {
                    $final_qd_user_id = (int)$created;
                    qd_bridge_log('Auto-register created QuickDate user', ['created_id'=>$created]);

                    // Ensure the canonical session value is updated immediately
                    $_SESSION['qd_user_id'] = $final_qd_user_id;
                    $claim_qd_user_id = $final_qd_user_id;

                    // Try to persist qd_user_id into WordPress usermeta when WP id exists.
                    if (!empty($claim_wp_user_id)) {
                        $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
                        if ($wp_conn && function_exists('wp_update_usermeta')) {
                            try {
                                wp_update_usermeta($wp_conn, (int)$claim_wp_user_id, ['qd_user_id' => (int)$final_qd_user_id], null);
                                qd_bridge_log('Persisted auto-registered qd_user_id to WordPress usermeta', ['wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id]);
                            } catch (Throwable $e) {
                                qd_bridge_log('Exception persisting auto-registered qd_user_id to WP usermeta', ['ex'=>$e->getMessage(),'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id]);
                            }
                        } else {
                            qd_bridge_log('WP DB connection or wp_update_usermeta() not available after auto-register', ['wp_conn'=>(bool)$wp_conn,'has_wp_update_func'=>function_exists('wp_update_usermeta')]);
                        }
                    }
                } else {
                    qd_bridge_log('Auto-register failed (no created id returned)', ['login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email]);
                }
            } else {
                qd_bridge_log('Auto registration disabled and no mapping found', ['login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email]);
            }

            // 4) As last resort, if session had a qd id that now exists, preserve it
            if (!$final_qd_user_id && $orig_session_qd && qd_find_user_by_id($orig_session_qd)) {
                $final_qd_user_id = $orig_session_qd;
                qd_bridge_log('Preserving original session qd_user_id after attempts', ['qd_user_id'=>$final_qd_user_id]);
            }
        }
    }
}

// 5) If still no final id, fail and logout
if (!$final_qd_user_id) {
    qd_bridge_log('Unable to determine QuickDate user id after mapping/registration', ['session'=>$_SESSION,'cookie_payload'=>$cookie_payload ?? null]);
    qd_clear_and_logout('no_qd_user_after_mapping');
}

/* Persist canonical session values (do not overwrite wp_user_login if already set) */
if (!isset($_SESSION['wp_user_login'])) $_SESSION['wp_user_login'] = $claim_wp_user_login;
$_SESSION['wp_user_id'] = (int)$claim_wp_user_id;
$_SESSION['wp_user_email'] = $claim_wp_user_email;
// Ensure session qd_user_id is set to final value (requirement iii)
$_SESSION['qd_user_id'] = (int)$final_qd_user_id;

/*
 * Ensure buzz_sso cookie is present and contains canonical identifiers.
 * - We issue a long-lived buzz_sso cookie when needed (e.g., after auto-register,
 *   or when the existing cookie does not contain the canonical qd_user_id).
 * - The cookie is intentionally long-lived (10 years) and will only be cleared by explicit logout.
 */
try {
    $need_issue = false;
    // If no cookie present, issue one
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) {
        $need_issue = true;
    } else {
        // If cookie exists but missing qd_user_id or mismatched, re-issue with canonical values
        if (!is_array($cookie_payload)) {
            $cookie_payload = qd_sso_verify_token($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET) ?: null;
        }
        if (!is_array($cookie_payload) || empty($cookie_payload['qd_user_id']) || (int)$cookie_payload['qd_user_id'] !== (int)$final_qd_user_id) {
            $need_issue = true;
        }
    }

    if ($need_issue) {
        $new_payload = [
            'wp_user_id'    => (int)$_SESSION['wp_user_id'],
            'wp_user_login' => (string)$_SESSION['wp_user_login'],
            'wp_user_email' => (string)$_SESSION['wp_user_email'],
            'qd_user_id'    => (int)$_SESSION['qd_user_id']
        ];
        // Use helper to issue long-lived cookie
        qd_issue_buzz_sso_cookie($new_payload);
    }
} catch (Throwable $e) {
    qd_bridge_log('Exception while ensuring long-lived buzz_sso cookie', ['ex'=>$e->getMessage()]);
}

/* If already logged in according to QuickDate, DO NOT perform a server-side redirect.
   A server-side redirect will cause the client AJAX to receive HTML/302 instead of the
   expected JSON response. Instead, preserve the intended redirect target (steps),
   log the fact that QuickDate is already logged and continue to render the bridge page.
   The client JS will post to the do_login endpoint and receive proper JSON with location. */
$deferred_redirect_target = null;
if (defined('IS_LOGGED') && IS_LOGGED === true) {
    $deferred_redirect_target = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/steps';
    qd_bridge_log('IS_LOGGED true — NOT redirecting server-side; deferring to client flow', ['user_id'=>$_SESSION['qd_user_id'], 'target'=>$deferred_redirect_target]);
    // ensure last_url will be set to $deferred_redirect_target later when building client payload
}

/* Build SSO password token used by JS bridge */
function qd_build_sso_password_token($qd_user_id, $wp_user_id, $wp_user_login, $wp_user_email, $secret) {
    $claims = [
        'ver'=>1,
        'qd_user_id'=>(int)$qd_user_id,
        'wp_user_id'=>(int)$wp_user_id,
        'wp_user_login'=>(string)$wp_user_login,
        'wp_user_email'=>(string)$wp_user_email,
        'iat'=>time(),
        'exp'=>time() + BUZZ_SSO_TTL,
        'nonce'=>bin2hex(random_bytes(8)),
    ];
    $json = json_encode($claims);
    $sig  = hash_hmac('sha256', $json, (string)$secret, true);
    return 'WPSSO.v1.' . _qd_b64url_encode($json) . '.' . _qd_b64url_encode($sig);
}
$sso_username = $_SESSION['wp_user_login'];
$sso_password = qd_build_sso_password_token($_SESSION['qd_user_id'], $_SESSION['wp_user_id'], $_SESSION['wp_user_login'], $_SESSION['wp_user_email'], $BUZZ_SSO_SECRET);

$site_base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
$last_url = '/';
foreach (['last_url'] as $k) {
    if (!empty($_GET[$k]))  { $last_url = (string)$_GET[$k]; break; }
    if (!empty($_POST[$k])) { $last_url = (string)$_POST[$k]; break; }
    if (!empty($_COOKIE[$k])) { $last_url = (string)$_COOKIE[$k]; break; }
}
// If bridge code decided earlier to defer a redirect because IS_LOGGED was true, prefer that target
if (!empty($deferred_redirect_target)) {
    $last_url = $deferred_redirect_target;
}
if (!$last_url || ($site_base && strpos($last_url, $site_base) !== 0)) $last_url = '/';
$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php') . '?sso_action=do_login';

qd_bridge_log('SSO client payload prepared', [
    'sso_username'=>$sso_username,
    'sso_password_len'=>strlen($sso_password),
    'ajax_url'=>$ajax_url,
    'last_url'=>$last_url
]);

/* --- LOGIN ENDPOINT: accepts POST from JS bridge --- */
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    QD_SSO_Login();
    exit;
}

/**
 * Helper: get QuickDate table columns (cached) - used to filter wp meta keys sent to QuickDate.
 */
if (!function_exists('qd_get_columns')) {
    function qd_get_columns($conn, $table) {
        static $cache = [];
        $key = $table;
        if (isset($cache[$key])) return $cache[$key];
        $cols = [];
        if (!$conn) return $cols;
        $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
        if ($res) {
            while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
        }
        $cache[$key] = $cols;
        return $cols;
    }
}

function QD_SSO_Login() {
    global $BUZZ_SSO_SECRET, $config;
    header('Content-Type: application/json; charset=utf-8');

    $username = isset($_POST['username']) ? (string)$_POST['username'] : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $last_url = isset($_POST['last_url']) ? (string)$_POST['last_url'] : '/';

    qd_bridge_log('QD_SSO_Login called', ['post_username'=>$username, 'pw_len'=>strlen($password)]);

    if (!$BUZZ_SSO_SECRET) {
        qd_bridge_log('QD_SSO_Login: BUZZ_SSO_SECRET missing');
        echo json_encode(['status'=>500,'errors'=>['Server misconfiguration']]); exit;
    }
    // token must be WPSSO.v1.<b64json>.<b64sig>
    $claims = qd_parse_sso_password_token($password, $BUZZ_SSO_SECRET);
    if (!$claims) {
        qd_bridge_log('QD_SSO_Login: invalid SSO password token', ['token_preview'=>substr($password,0,40)]);
        echo json_encode(['status'=>401,'errors'=>['Invalid or expired SSO token']]); exit;
    }

    // Expectations: prefer session values (trusted) else claims
    $sess_qd    = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : 0;
    $sess_wp    = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : 0;
    $sess_login = isset($_SESSION['wp_user_login']) ? (string)$_SESSION['wp_user_login'] : '';
    $sess_email = isset($_SESSION['wp_user_email']) ? (string)$_SESSION['wp_user_email'] : '';

    $exp_qd    = $sess_qd    ?: (int)($claims['qd_user_id'] ?? 0);
    $exp_wp    = $sess_wp    ?: (int)($claims['wp_user_id'] ?? 0);
    $exp_login = $sess_login ?: (string)($claims['wp_user_login'] ?? '');
    $exp_email = $sess_email ?: (string)($claims['wp_user_email'] ?? '');

    qd_bridge_log('QD_SSO_Login expectations', ['qd'=>$exp_qd,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email]);

    // Find candidate QuickDate users (≥1 candidate)
    $db = get_qd_db_conn();
    $candidates = [];
    if ($db) {
        if ($exp_qd) {
            $q = $db->query("SELECT * FROM users WHERE id=".(int)$exp_qd." LIMIT 1");
            if ($q && $r = $q->fetch_assoc()) $candidates[] = $r;
        }
        if (!$candidates && $exp_email) {
            $esc = $db->real_escape_string($exp_email);
            $q = $db->query("SELECT * FROM users WHERE email='{$esc}' LIMIT 1");
            if ($q && $r = $q->fetch_assoc()) $candidates[] = $r;
        }
        if (!$candidates && $exp_login) {
            $esc = $db->real_escape_string($exp_login);
            $q = $db->query("SELECT * FROM users WHERE username='{$esc}' LIMIT 1");
            if ($q && $r = $q->fetch_assoc()) $candidates[] = $r;
        }
        if (!$candidates && $exp_wp) {
            $q = $db->query("SELECT * FROM users WHERE wp_user_id=".(int)$exp_wp." LIMIT 1");
            if ($q && $r = $q->fetch_assoc()) $candidates[] = $r;
        }
    }

    qd_bridge_log('QD_SSO_Login candidates count', ['count'=>count($candidates)]);

    // Accept user if ≥3 identifier matches
    $accepted_user = null;
    $accepted_matches = [];
    foreach ($candidates as $row) {
        $db_id  = (int)$row['id'];
        $db_un  = (string)$row['username'];
        $db_em  = (string)$row['email'];
        $db_wpu = (int)($row['wp_user_id'] ?? 0);

        $m_id  = ($exp_qd && $db_id === $exp_qd) ? 1 : 0;
        $m_em  = ($exp_email && strcasecmp($db_em,$exp_email)===0) ? 1 : 0;
        $m_un  = ($exp_login && strcasecmp($db_un,$exp_login)===0) ? 1 : 0;
        $m_wpu = ($exp_wp && $db_wpu === $exp_wp) ? 1 : 0;

        $cnt = $m_id + $m_em + $m_un + $m_wpu;

        if ($cnt >= 3) {
            $accepted_user = $row;
            $accepted_matches = ['id'=>$m_id,'email'=>$m_em,'username'=>$m_un,'wp_user_id'=>$m_wpu];
            break;
        }
    }

    if (!$accepted_user) {
        qd_bridge_log('QD_SSO_Login: no accepted candidate (>=3 required)', [
            'expected' => ['qd'=>$exp_qd,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email],
            'candidates' => array_map(function($c){ return ['id'=>$c['id'],'username'=>$c['username'],'email'=>$c['email'],'wp_user_id'=>$c['wp_user_id'] ?? null];}, $candidates)
        ]);
        // Do not rotate PHPSESSID. Clear QD session to be safe.
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION = [];
            @session_unset();
            // keep PHPSESSID cookie as-is (WordPress owns it)
        }
        echo json_encode(['status'=>401,'errors'=>['No matching QuickDate account for SSO.']]); exit;
    }

    // Set QuickDate session values — preserve PHPSESSID (do NOT regenerate)
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

    // Important: do NOT call session_regenerate_id(true) — WordPress manages PHPSESSID
    $_SESSION['qd_user_id']    = (int)$accepted_user['id'];
    $_SESSION['user_id']       = $accepted_user['web_token'] ?? (int)$accepted_user['id'];
    $_SESSION['wp_sso_login']  = true;
    $_SESSION['wp_user_id']    = $exp_wp;
    $_SESSION['wp_user_email'] = $exp_email;
    if (!isset($_SESSION['wp_user_login'])) $_SESSION['wp_user_login'] = $exp_login;

    // Trigger QuickDate's SetLoginWithSession if available to complete framework login actions
    if (function_exists('LoadEndPointResource')) {
        $usersRes = LoadEndPointResource('users');
        if ($usersRes && method_exists($usersRes, 'SetLoginWithSession') && !empty($exp_email)) {
            // This should set JWT, user session records, etc.
            try {
                $usersRes->SetLoginWithSession($exp_email);
                qd_bridge_log('SetLoginWithSession invoked', ['email'=>$exp_email]);
            } catch (Throwable $e) {
                qd_bridge_log('SetLoginWithSession exception', ['ex'=>$e->getMessage()]);
            }
        }
    }

    // ----------------------------
    // SYNC: Update QuickDate user with WordPress metadata AFTER successful login
    // - Use shared/wwqd_bridge.php functions when available:
    //     - wp_get_full_user_data (returns ['meta'=>..., 'xprofile'=>...])
    //     - sync_user_to_quickdate($wp_email, $usermeta, $xprofile) -- builds qd payload and calls qd_update_user
    // - This will overwrite QuickDate fields (present in the payload) with WordPress values.
    // ----------------------------
    try {
        qd_bridge_log('Preparing to sync WordPress metadata into QuickDate', ['wp_user_id'=>$exp_wp,'wp_email'=>$exp_email]);

        $did_sync = false;
        // Prefer the shared helper sync_user_to_quickdate if present
        if (!empty($exp_email) && !empty($exp_wp) && function_exists('sync_user_to_quickdate') && function_exists('wp_get_full_user_data')) {
            $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
            if ($wp_conn) {
                $wp_full = wp_get_full_user_data($wp_conn, $exp_wp);
                if ($wp_full && is_array($wp_full)) {
                    $usermeta = $wp_full['meta'] ?? [];
                    $xprofile = $wp_full['xprofile'] ?? [];
                    // sync_user_to_quickdate will prefer xprofile values and then usermeta;
                    // it calls qd_update_user which filters by QuickDate columns.
                    $ok = sync_user_to_quickdate($exp_email, $usermeta, $xprofile);
                    qd_bridge_log('sync_user_to_quickdate result', ['email'=>$exp_email,'wp_user_id'=>$exp_wp,'ok'=>(bool)$ok]);
                    $did_sync = (bool)$ok;
                } else {
                    qd_bridge_log('wp_get_full_user_data returned empty/invalid', ['wp_user_id'=>$exp_wp]);
                }
            } else {
                qd_bridge_log('WP DB connection not available for sync', []);
            }
        } elseif (!empty($exp_email) && function_exists('get_user_field_metadata') && function_exists('wp_get_full_user_data') && function_exists('qd_update_user')) {
            // Fallback: replicate behavior using lower-level helpers (in case sync_user_to_quickdate is not available)
            $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
            $wp_full = $wp_conn ? wp_get_full_user_data($wp_conn, $exp_wp) : null;
            if ($wp_full && is_array($wp_full)) {
                $metadata = get_user_field_metadata();
                $public_fields = $metadata['public_open_fields'] ?? [];
                $private_fields = $metadata['private_secure_fields'] ?? [];
                $qd_candidate = [];

                // Prefer xprofile values for public fields
                foreach ($public_fields as $qd_key => $map) {
                    if (isset($wp_full['xprofile'][$qd_key]) && $wp_full['xprofile'][$qd_key] !== '') {
                        $qd_candidate[$qd_key] = $wp_full['xprofile'][$qd_key];
                    } elseif (isset($wp_full['meta'][$qd_key]) && $wp_full['meta'][$qd_key] !== '') {
                        $qd_candidate[$qd_key] = $wp_full['meta'][$qd_key];
                    }
                }

                // Private fields from usermeta if not already set
                foreach ($private_fields as $qd_key => $map) {
                    if (!isset($qd_candidate[$qd_key]) && isset($wp_full['meta'][$qd_key]) && $wp_full['meta'][$qd_key] !== '') {
                        $qd_candidate[$qd_key] = $wp_full['meta'][$qd_key];
                    }
                }

                // Always include certain canonical fields if available
                if (!isset($qd_candidate['username']) && !empty($wp_full['user_login'])) $qd_candidate['username'] = $wp_full['user_login'];
                if (!isset($qd_candidate['email']) && !empty($wp_full['user_email'])) $qd_candidate['email'] = $wp_full['user_email'];
                if (!isset($qd_candidate['first_name']) && !empty($wp_full['meta']['first_name'])) $qd_candidate['first_name'] = $wp_full['meta']['first_name'];
                if (!isset($qd_candidate['last_name']) && !empty($wp_full['meta']['last_name'])) $qd_candidate['last_name'] = $wp_full['meta']['last_name'];
                if (!isset($qd_candidate['avatar'])) {
                    $avatar = $wp_full['xprofile']['avatar'] ?? $wp_full['meta']['avatar'] ?? '';
                    if ($avatar) $qd_candidate['avatar'] = $avatar;
                }

                // Filter to QuickDate user table columns
                $qd_conn = get_qd_db_conn();
                $qd_cols = qd_get_columns($qd_conn, 'users');
                $qd_update = [];
                foreach ($qd_candidate as $k => $v) {
                    if (in_array($k, $qd_cols, true)) {
                        $qd_update[$k] = $v;
                    }
                }

                if (!empty($qd_update)) {
                    $ok = qd_update_user($exp_email, $qd_update);
                    qd_bridge_log('qd_update_user (fallback) result', ['email'=>$exp_email,'update_keys'=>array_keys($qd_update),'result'=> (bool)$ok]);
                    $did_sync = (bool)$ok;
                } else {
                    qd_bridge_log('No QuickDate-updatable fields found in WP user data (fallback)', ['email'=>$exp_email,'candidate_keys'=>array_keys($qd_candidate)]);
                }
            } else {
                qd_bridge_log('wp_get_full_user_data returned empty/invalid for fallback sync', ['wp_user_id'=>$exp_wp]);
            }
        } else {
            qd_bridge_log('Skipping QuickDate sync - missing prerequisites', ['has_email'=>!empty($exp_email),'has_wp_id'=>!empty($exp_wp),'functions'=>[
                'sync_user_to_quickdate'=>function_exists('sync_user_to_quickdate'),
                'get_user_field_metadata'=>function_exists('get_user_field_metadata'),
                'wp_get_full_user_data'=>function_exists('wp_get_full_user_data'),
                'qd_update_user'=>function_exists('qd_update_user')
            ]]);
        }
        if (!$did_sync) {
            qd_bridge_log('Post-login QuickDate sync did not run or reported failure', ['wp_user_id'=>$exp_wp,'email'=>$exp_email]);
        }
    } catch (Throwable $e) {
        qd_bridge_log('Exception during QuickDate sync', ['ex'=>$e->getMessage()]);
    }

    // Decide redirect URL
    $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/steps';
    if (!empty($accepted_user['start_up']) && $accepted_user['start_up'] == 3 && !empty($accepted_user['verified'])) {
        $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/find-matches';
    }
    if (!empty($last_url) && $last_url !== '//' ) {
        // Only accept relative or same-site last_url
        $site_base = isset($config->uri) ? rtrim($config->uri,'/') : '';
        if ($last_url && (!$site_base || strpos($last_url, $site_base) === 0)) {
            $url = $last_url;
        } elseif ($last_url === '/') {
            // keep default
        }
    }

    qd_bridge_log('QD_SSO_Login success', ['user_id'=>$accepted_user['id'],'matches'=>$accepted_matches,'redirect'=>$url,'session_id'=>session_id()]);

    http_response_code(200);
    echo json_encode(['status'=>200,'location'=>$url]);
    exit;
}

/* Render bridge page */
qd_bridge_log('Rendering QD SSO bridge page', [
    'sso_username'=>$sso_username,
    'sso_password_len'=>strlen($sso_password),
    'last_url'=>$last_url,
    'final_qd_user_id'=>$final_qd_user_id,
    'php_session_id'=>session_id(),
    'shadow_session_id'=> (isset($_COOKIE['PHPSESSID']) ? 'shadow_'.$_COOKIE['PHPSESSID'] : null),
    'session_vars'=> $_SESSION,
    'cookies'=> $_COOKIE
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Signing you in…</title>
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:2rem;background:#0b1020;color:#e9eef7}
.card{max-width:560px;margin:10vh auto;padding:1.5rem 1.75rem;background:#131a33;border-radius:10px;box-shadow:0 4px 32px #0008}
.title{font-size:1.45rem;font-weight:700;margin-bottom:.5em}
.status{font-size:1.05rem;margin-top:1em}
.status.ok{color:#6f6}.status.err{color:#e88}
.dbg{font-size:.9em;margin-top:2em;word-break:break-all}
</style>
</head>
<body>
  <div class="card">
    <div class="title">Signing you in…</div>
    <div id="status" class="status">Preparing secure session…</div>
    <?php if (qd_is_debug()): ?>
      <div class="dbg"><pre><?php echo htmlspecialchars(print_r([
          'ajax_url'=>$ajax_url,
          'post'=>['username'=>$sso_username,'password'=>'(sso-token)','last_url'=>$last_url,'remember_device'=>'on'],
          'session'=>$_SESSION,
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
    var beaconUrl = <?php
      $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php';
      echo json_encode($self . '?sso_client_log=1');
    ?>;
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

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.withCredentials = true;
    xhr.timeout = 20000;
    xhr.onreadystatechange = function(){
      if (xhr.readyState === 4) {
        var ok=false, locationUrl=null, errors=null, res=null;
        try { res = JSON.parse(xhr.responseText); } catch(e) {
          // Parsing failed: log and attempt a safe fallback.
          beacon('bridge:parse_error', {http: xhr.status, text: xhr.responseText});
        }
        if (res) { ok = !!(res.status===200 || res.status===600) && !!res.location; locationUrl = res.location; errors = res.errors || null; }
        beacon('bridge:response', {status: res && res.status, location: locationUrl, errors: errors, http: xhr.status});
        if (ok) {
          statusEl && (statusEl.className='status ok', statusEl.textContent='Welcome back! Redirecting…');
          setTimeout(function(){ window.location.href = locationUrl; }, 400);
        } else {
          // If parse failed and response looks like HTML redirect (server-side redirect), fall back to last_url
          var body = xhr.responseText || '';
          var looksLikeHtml = body.indexOf('<!DOCTYPE') !== -1 || body.indexOf('<html') !== -1;
          if (!res && looksLikeHtml && payload.last_url) {
            beacon('bridge:fallback_html_redirect', {http: xhr.status, fallback: payload.last_url});
            window.location.href = payload.last_url;
            return;
          }
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