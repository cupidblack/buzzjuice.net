<?php
/**
 * qd-sso-bridge.php
 * BuzzJuice → QuickDate SSO bridge (RFC JWT / stateless / replay protection / refresh-token design)
 */

// --- Bootstrap ---
require_once __DIR__ . '/bootstrap.php';

// Shared bridge utilities for user mapping/sync
if (file_exists(__DIR__ . '/../shared/wwqd_bridge.php')) {
    require_once __DIR__ . '/../shared/wwqd_bridge.php';
} elseif (file_exists(__DIR__ . '/requests/wp_user_bridge.php')) {
    require_once __DIR__ . '/requests/wp_user_bridge.php';
}

require_once __DIR__ . '/../shared/sso_bridge_helpers.php';

// Auxiliary controllers if needed
if (file_exists(__DIR__ . '/controllers/aj.php')) {
    require_once __DIR__ . '/controllers/aj.php';
}
if (file_exists(__DIR__ . '/requests/ajax/useractions.php')) {
    require_once __DIR__ . '/requests/ajax/useractions.php';
}

// --- CONFIG ---
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/qd_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$BUZZ_SSO_SECRET = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);
if (!$BUZZ_SSO_SECRET) {
    qd_bridge_log("BuzzJuice SSO misconfiguration: secret missing", []);
    die("BuzzJuice SSO misconfiguration: secret missing.");
}

// --- LOGGING/DEBUG ---
function qd_bridge_log($msg, $ctx = []) {
    $data = [
        'ts' => gmdate('Y-m-d H:i:s'),
        'php_session_id' => function_exists('session_id') ? session_id() : null,
        'session_name' => function_exists('session_name') ? session_name() : null,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'cookies' => isset($_COOKIE) ? array_keys($_COOKIE) : [],
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




// --- Base64 URL-safe encode/decode (RFC 7515/7519) ---
if (!function_exists('qd_b64url_encode')) {
    /**
     * Base64 URL-safe encode.
     *
     * @param string $data Raw data to encode.
     * @return string URL-safe base64 string.
     */
    function qd_b64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('qd_b64url_decode')) {
    /**
     * Base64 URL-safe decode.
     *
     * @param string $data URL-safe base64 string to decode.
     * @return string Decoded raw data.
     */
    function qd_b64url_decode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

// --- JWT parsing and validation (RFC 7519, see also streams/ww-sso-bridge.php) ---
function qd_jwt_parse($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    list($h, $p, $s) = $parts;
    $header = json_decode(qd_b64url_decode($h), true);
    $payload = json_decode(qd_b64url_decode($p), true);
    $sig = qd_b64url_decode($s);
    if (!is_array($header) || !is_array($payload) || $sig === false) return false;
    return [$header, $payload, $sig, $h.'.'.$p];
}

// NOTE: This is for stateless endpoint SSO tokens, not QuickDate sso_token tokens.
function qd_jwt_verify($jwt, $secret, $aud = 'buzznet') {
    $parse = qd_jwt_parse($jwt);
    if (!$parse) return ['ok' => false, 'error' => 'jwt_parse_failed'];
    list($header, $payload, $sig, $signing_input) = $parse;

    // Must be HS256
    if (empty($header['alg']) || $header['alg'] !== 'HS256') return ['ok' => false, 'error' => 'bad_alg'];
    if (empty($header['typ']) || strtolower($header['typ']) !== 'jwt') return ['ok' => false, 'error' => 'bad_typ'];
    // Required fields
    if (empty($payload['exp']) || time() > intval($payload['exp'])) return ['ok' => false, 'error' => 'expired'];
    if (!empty($payload['nbf']) && time() < intval($payload['nbf'])) return ['ok' => false, 'error' => 'not_yet_valid'];
    if (!empty($payload['iss']) && strtolower($payload['iss']) !== 'buzzjuice.net') return ['ok' => false, 'error' => 'bad_issuer'];
    if (!empty($payload['aud']) && strtolower($payload['aud']) !== strtolower($aud)) return ['ok' => false, 'error' => 'bad_audience'];

    // Replay protection (jti, must be unique)
    if (empty($payload['jti'])) return ['ok' => false, 'error' => 'missing_jti'];
    $jti = $payload['jti'];
    $jti_dir = sys_get_temp_dir() . '/buzz_qd_jti_db';
    if (!is_dir($jti_dir)) @mkdir($jti_dir, 0700, true);
    $jti_file = $jti_dir . '/' . sha1($jti);
    if (file_exists($jti_file)) {
        return ['ok' => false, 'error' => 'replay_detected'];
    }
    // Mark jti as used
    @file_put_contents($jti_file, time(), LOCK_EX);

    // Clean up old jtis (every ~1/20 hits)
    if (mt_rand(1, 20) === 1) {
        foreach (glob($jti_dir . '/*') as $f) {
            if (is_file($f) && filemtime($f) < (time() - 1800)) @unlink($f); // 30 min expiry
        }
    }

    // Signature
    $expected_sig = hash_hmac('sha256', $signing_input, $secret, true);
    if (!hash_equals($expected_sig, $sig)) return ['ok' => false, 'error' => 'bad_signature'];

    return ['ok' => true, 'header' => $header, 'payload' => $payload];
}

// --- Legacy/non-RFC sso_token token (from WP, still supported as fallback) ---
function qd_parse_sso_password_token($token, $secret) {
    if (!$token || !$secret) return false;
    if (strpos($token, 'WPSSO.v1.') !== 0) return false;
    $body = substr($token, strlen('WPSSO.v1.'));
    $parts = explode('.', $body, 2);
    if (count($parts) !== 2) return false;
    $json = qd_b64url_decode($parts[0]);
    $sig  = qd_b64url_decode($parts[1]);
    if ($json === false || $sig === false) return false;
    $calc = hash_hmac('sha256', $json, (string)$secret, true);
    if (!hash_equals((string)$calc, (string)$sig)) return false;
    $payload = json_decode($json, true);
    if (!is_array($payload)) return false;
    if (!empty($payload['exp']) && time() > (int)$payload['exp']) return false;
    return $payload;
}

// --- NEW: Accept SSO token from GET/POST, verify, and extract user info ---
// --- WoWonder-style: SSO Token Acquisition and Validation ---
$last_url = $_REQUEST['last_url'] ?? '/';
$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);

// 1. Try to acquire local token from request or cookie
$sso_token = $_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? null);
$sso_payload = null;

// Validate RFC JWT if present (expect audience "buzznet" for WP stateless SSO tokens)
if (!empty($sso_token)) {
    if (substr_count($sso_token, '.') === 2) {
        $res = qd_jwt_verify($sso_token, $BUZZ_SSO_SECRET, 'buzznet');
        if (!$res['ok']) {
            // If error is 'replay_detected', trigger UI reload/request for a fresh SSO token
            qd_bridge_log('JWT validation failed in QD_SSO_Login', ['token'=>$sso_token,'result'=>$res]);
            http_response_code(401);
            echo json_encode(['status'=>401,'errors'=>['SSO error: '.$res['error']]]);
            exit;
        }
        $claims = $res['payload'];
    } else {
        // legacy fallback if absolutely needed
        $claims = qd_parse_sso_password_token($sso_token, $BUZZ_SSO_SECRET);
        if (!$claims) {
            qd_bridge_log('Legacy SSO token validation failed', ['token'=>$sso_token]);
            http_response_code(401);
            echo json_encode(['status'=>401,'errors'=>['Invalid or expired SSO token']]);
            exit;
        } else {
            // Fallback: legacy token format
            $legacy = qd_parse_sso_password_token($sso_token, $BUZZ_SSO_SECRET);
            if ($legacy) $sso_payload = $legacy;
            else qd_bridge_log('Legacy SSO token validation failed', ['token' => $sso_token]);
        }
    }
}

// 2. If not, try to fetch SSO payload from WordPress stateless endpoint
if (empty($sso_payload)) {
    qd_bridge_log('No valid local SSO token; attempting fetch from WP stateless endpoint', [
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'cookies'     => array_keys($_COOKIE),
        'session_id'  => session_id(),
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    $result = bz_fetch_wp_stateless_payload(null, $BUZZ_SSO_SECRET); // The stateless endpoint always returns "buzznet" (not "quickdate") audience tokens
    qd_bridge_log('WP stateless endpoint response', [
        'has_payload' => is_array($result) && !empty($result['payload']),
        'type'        => gettype($result),
    ]);
    if (is_array($result) && !empty($result['payload'])) {
        $sso_payload = $result['payload'];
        // Set cookie for browser to have it available next time
        if (function_exists('qd_issue_buzz_sso_cookie')) {
            qd_issue_buzz_sso_cookie([
                'wp_user_id'    => $sso_payload['wp_user_id'] ?? null,
                'wp_user_login' => $sso_payload['wp_user_login'] ?? null,
                'wp_user_email' => $sso_payload['wp_user_email'] ?? null,
                'qd_user_id'    => $sso_payload['qd_user_id'] ?? null,
            ]);
        }
    }
}

// 3. If STILL not available, redirect to WP login
if (empty($sso_payload)) {
    qd_bridge_log('Unable to acquire SSO payload - redirecting to WP login', ['last_url' => $last_url]);
    $redirect_to = 'https://buzzjuice.net/social/qd-sso-bridge.php?last_url=' . urlencode($last_url);
    $wp_login = 'https://buzzjuice.net/wp-login.php?redirect_to=' . urlencode($redirect_to);
    header("Location: $wp_login");
    exit;
}

// At this point, $sso_payload contains a validated user payload.
// (Next, QD logic can proceed to find/auto-register user & handle login.)

// TODO next: Add support for refresh token rotation
// - If present, handle 'refresh_token' in $sso_payload, and build refresh rotation endpoint
// - When JWT is expired but refresh_token present and valid, issue new JWT and continue





/**
 * Issue a long-lived buzz_sso cookie—stateless RFC JWT with replay & refresh support.
 * - Fields: wp_user_id, wp_user_login, wp_user_email, qd_user_id, iat, exp, iss, aud, jti, refresh_token (optional)
 * - Cookie forcibly cleared during logout
 */
function qd_issue_buzz_sso_cookie(array $payload) {
    global $BUZZ_SSO_SECRET;

    if (!$BUZZ_SSO_SECRET) {
        qd_bridge_log('qd_issue_buzz_sso_cookie: missing BUZZ_SSO_SECRET', ['payload_keys'=>array_keys($payload)]);
        return false;
    }
    $now = time();
    $ten_years = 10 * 365 * 24 * 60 * 60;
    $exp = $now + $ten_years;

    // JWT fields/defaults
    $jwt_header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    $payload['iat'] = $payload['iat'] ?? $now;
    $payload['exp'] = $payload['exp'] ?? $exp;
    $payload['iss'] = $payload['iss'] ?? 'buzzjuice.net';
    $payload['aud'] = $payload['aud'] ?? 'buzznet';
    $payload['jti'] = $payload['jti'] ?? bin2hex(random_bytes(16)); // replay protection

    if (!empty($payload['refresh_token'])) {
        $jwt_header['refresh'] = true;
    }

    $header_enc = qd_b64url_encode(json_encode($jwt_header));
    $payload_enc = qd_b64url_encode(json_encode($payload));
    $signing_input = $header_enc . '.' . $payload_enc;
    $sig_enc = qd_b64url_encode(hash_hmac('sha256', $signing_input, (string)$BUZZ_SSO_SECRET, true));
    $token = $header_enc . '.' . $payload_enc . '.' . $sig_enc;

    // Cookie issue logic
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, $token, [
            'expires'=>$exp,
            'path'=>'/',
            'domain'=>BUZZ_COOKIE_DOMAIN,
            'secure'=>true,
            'httponly'=>true,
            'samesite'=>'Lax'
        ]);
    } else {
        setcookie(BUZZ_SSO_COOKIE, $token, $exp, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    $_COOKIE[BUZZ_SSO_COOKIE] = $token;
    qd_bridge_log('Issued RFC JWT buzz_sso cookie', [
        'expires'=>date('c', $exp),
        'payload_subset'=>array_intersect_key($payload,array_flip(['wp_user_id','wp_user_login','wp_user_email','qd_user_id','jti','refresh_token']))
    ]);
    return $token;
}

/**
 * Best-effort decode/parse buzz_sso cookie payload (RFC JWT or legacy)
 * - Returns payload array or null
 * - If secret provided, checks signature and expiry and logs errors
 */
function qd_parse_buzz_sso_cookie_payload($token, $secret = null) {
    if (!$token) return null;

    // RFC JWT (3 segments)
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        list($header_b64, $payload_b64, $sig_b64) = $parts;
        $header = @json_decode(qd_b64url_decode($header_b64), true);
        $payload = @json_decode(qd_b64url_decode($payload_b64), true);
        $sig = qd_b64url_decode($sig_b64);

        if ($header === false || $payload === false) return null;

        if ($secret) {
            $signing_input = $header_b64 . '.' . $payload_b64;
            $calc = hash_hmac('sha256', $signing_input, (string)$secret, true);
            if (!hash_equals($calc, (string)$sig)) {
                qd_bridge_log('buzz_sso cookie HMAC mismatch (RFC JWT)', [
                    'token_preview' => substr($token,0,36),
                    'expected_sig_b64' => qd_b64url_encode($calc),
                    'header' => $header,
                    'payload' => $payload
                ]);
                return null;
            }
            if (!empty($payload['exp']) && time() > intval($payload['exp'])) {
                qd_bridge_log('buzz_sso cookie expired (RFC JWT)', [
                    'exp' => $payload['exp']
                ]);
                return null;
            }
        }
        return $payload;
    }

    // Legacy 2-part fallback
    $parts2 = explode('.', $token, 2);
    if (count($parts2) !== 2) return null;
    $json = qd_b64url_decode($parts2[0]);
    $sig  = qd_b64url_decode($parts2[1]);
    if ($json === false) return null;
    if ($secret) {
        $calc = hash_hmac('sha256', $json, (string)$secret, true);
        if (!hash_equals($calc, (string)$sig)) {
            qd_bridge_log('buzz_sso cookie HMAC mismatch (legacy)', ['token_preview' => substr($token,0,24)]);
            return null;
        }
    }
    $payload = @json_decode($json, true);
    if (!is_array($payload)) return null;
    return $payload;
}

/**
 * Locate candidate shadow directories for legacy session reconciliation.
 * - Returns writable dirs, preferring configured path (BUZZ_SSO_SHADOW_PATH)
 */
function qd_locate_shadow_dirs() {
    $candidates = [];
    if (defined('BUZZ_SSO_SHADOW_PATH') && BUZZ_SSO_SHADOW_PATH) {
        $candidates[] = rtrim(BUZZ_SSO_SHADOW_PATH, DIRECTORY_SEPARATOR);
    }
    $candidates[] = realpath(__DIR__ . '/../shared/sso_sessions') ?: (__DIR__ . '/../shared/sso_sessions');
    $candidates[] = realpath(__DIR__ . '/../../shared/sso_sessions') ?: (__DIR__ . '/../../shared/sso_sessions');
    $candidates[] = realpath(__DIR__ . '/shared/sso_sessions') ?: (__DIR__ . '/shared/sso_sessions');
    $result = [];
    foreach ($candidates as $c) {
        if (!$c) continue;
        $c = rtrim($c, DIRECTORY_SEPARATOR);
        if (is_dir($c) && is_readable($c) && is_writable($c)) $result[] = $c;
    }
    return array_values(array_unique($result));
}

/**
 * Remove shadow files referencing same wp_user_id but NOT expected canonical shadow filename.
 * - Returns true if any removed; always logs
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
                } else if (preg_match('/["\']wp_user_id["\']\s*[:=]\s*([0-9]+)/i', $content, $m)) {
                    $found_wp_id = (int)$m[1];
                }
            }
            if ($found_wp_id === $expected_wp_user_id) {
                $deleted_any = false;
                @unlink($full) && $deleted_any = true;
                $siblings = [$full . '.ser', $full . '.json'];
                foreach ($siblings as $s) { if (is_file($s)) { @unlink($s); $deleted_any = true; } }
                if ($deleted_any) {
                    $removed[] = $full;
                    qd_bridge_log('Removed mismatched shadow file', [
                        'removed' => $full,
                        'expected' => $expected_shadow_filename,
                        'shadow_dir' => $dir
                    ]);
                } else {
                    qd_bridge_log('Failed to remove mismatched shadow file (permission?)', [
                        'file' => $full,
                        'shadow_dir' => $dir
                    ]);
                }
            }
        }
    }
    return !empty($removed);
}





/**
 * Write the canonical shadow file for the current WordPress session. Ensures other systems (legacy/compat)
 * can resolve to the single "source of truth" regarding which WP session corresponds to a QD account.
 * Returns true if written to at least one dir; logs all actions and errors.
 */
function qd_write_canonical_shadow_file(array $payload) {
    $wp_sid = $payload['session_id'] ?? $payload['wp_php_session_id'] ?? null;
    if (!$wp_sid) {
        qd_bridge_log('qd_write_canonical_shadow_file: missing wp_sid', ['payload_keys' => array_keys($payload)]);
        return false;
    }
    $shadow_id = 'shadow_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$wp_sid);
    $dirs = qd_locate_shadow_dirs();
    if (empty($dirs)) {
        qd_bridge_log('qd_write_canonical_shadow_file: no writable shadow dirs', ['shadow_id' => $shadow_id]);
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
    $shadow['wp_session_name'] = $payload['session_name'] ?? ($shadow['wp_session_name'] ?? (function_exists('session_name') ? session_name() : null));
    if (empty($shadow['buzz_sso_last_sync'])) $shadow['buzz_sso_last_sync'] = time();

    $payload_ser = @serialize($shadow);
    if ($payload_ser === false) {
        qd_bridge_log('qd_write_canonical_shadow_file: failed to serialize', ['shadow_id' => $shadow_id]);
        return false;
    }
    $json_payload = @json_encode($shadow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $written_any = false;
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $shadow_id;
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $payload_ser, LOCK_EX) === false) {
            @unlink($tmp);
            qd_bridge_log('qd_write_canonical_shadow_file: failed to write tmp file', ['path' => $path, 'dir' => $dir]);
            continue;
        }
        @chmod($tmp, 0640);
        if (!@rename($tmp, $path)) {
            if (!@copy($tmp, $path) || !@unlink($tmp)) {
                @unlink($tmp);
                qd_bridge_log('qd_write_canonical_shadow_file: failed atomic move', ['tmp' => $tmp, 'path' => $path]);
                continue;
            }
        }
        @chmod($path, 0640);
        // Also write .ser version for backward compatibility
        @file_put_contents($path . '.ser', $payload_ser, LOCK_EX);
        @chmod($path . '.ser', 0640);
        // JSON version as well
        if ($json_payload !== false) {
            @file_put_contents($path . '.json', $json_payload, LOCK_EX);
            @chmod($path . '.json', 0640);
        }
        qd_bridge_log('qd_write_canonical_shadow_file: wrote shadow', ['path' => $path, 'shadow_id' => $shadow_id, 'dir' => $dir]);
        $written_any = true;
    }
    return $written_any;
}

/**
 * Stateless session reconciliation from SSO; for current QD session, attempts to merge with WordPress session info.
 * - Parses buzz_sso cookie.
 * - If session mismatch: cleans up mismatched shadows, writes new canonical, resets session runtime context to WP.
 * - All errors/failures logged.
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
        if ($wp_payload) qd_bridge_log('qd_attempt_session_reconciliation_if_required: using SSO cookie payload without verification', ['preview' => substr($_COOKIE[BUZZ_SSO_COOKIE],0,24)]);
    }
    if (!$wp_payload) {
        qd_bridge_log('qd_attempt_session_reconciliation_if_required: SSO cookie parse failure', []);
        return;
    }

    $wp_sname = $wp_payload['session_name'] ?? $wp_payload['wp_session_name'] ?? null;
    $wp_sid   = $wp_payload['session_id']   ?? $wp_payload['wp_php_session_id'] ?? null;

    // If cookie lacks wp_user_id but we have wp_sid, try shadow file enrichment
    if (empty($wp_payload['wp_user_id']) && !empty($wp_sid)) {
        $shadow = qd_find_wp_shadow_payload($wp_sid);
        if (is_array($shadow) && !empty($shadow)) {
            $wp_payload = array_merge($wp_payload, $shadow);
            $wp_sname = $wp_payload['session_name'] ?? $wp_sname;
            $wp_sid   = $wp_payload['session_id'] ?? $wp_sid;
            qd_bridge_log('qd_attempt_session_reconciliation_if_required: loaded shadow to enrich payload', ['wp_sid_preview' => substr($wp_sid,0,12)]);
        }
    }

    $mismatch = false;
    if ($wp_sname && $current_name !== $wp_sname) $mismatch = true;
    if ($wp_sid && $current_sid !== $wp_sid) $mismatch = true;

    if (!$mismatch) {
        qd_bridge_log('qd_attempt_session_reconciliation_if_required: no session mismatch', [
            'current_name' => $current_name,
            'current_sid_preview' => substr($current_sid,0,12),
            'wp_name' => $wp_sname,
            'wp_sid_preview' => substr($wp_sid ?? '', 0, 12)
        ]);
        return;
    }

    qd_bridge_log('qd_attempt_session_reconciliation_if_required: session mismatch, reconciling', [
        'current_name' => $current_name,
        'current_sid_preview' => substr($current_sid,0,12),
        'wp_name' => $wp_sname,
        'wp_sid_preview' => substr($wp_sid ?? '', 0, 12)
    ]);

    // 1) Cleanup mismatched shadow files
    try {
        qd_cleanup_shadow_mismatches($wp_payload);
    } catch (\Throwable $e) {
        qd_bridge_log('qd_attempt_session_reconciliation_if_required: error cleaning shadows', ['err' => $e->getMessage()]);
    }

    // 2) Write canonical shadow (all errors logged)
    try {
        qd_write_canonical_shadow_file($wp_payload);
    } catch (\Throwable $e) {
        qd_bridge_log('qd_attempt_session_reconciliation_if_required: error writing canonical shadow', ['err' => $e->getMessage()]);
    }

    // 3) Remove local session file (to enforce clean runtime)
    qd_unlink_local_session_file_if_exists($current_sid);

    // 4) Reset & rehydrate runtime session, aligning session_name
    if ($wp_sname && $current_name !== $wp_sname) {
        session_write_close();
        session_name($wp_sname);
    }
    @session_start();

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

    qd_bridge_log('qd_attempt_session_reconciliation_if_required: rehydrated QuickDate session', ['new_local_sid' => session_id()]);
}

/**
 * Find and return a WP shadow payload for given session id.
 * This function searches all shadow dirs for compatible PHP-serialized or JSON shadow payloads.
 * Returns array payload or null if not found.
 */
function qd_find_wp_shadow_payload($wp_session_id) {
    if (!$wp_session_id) return null;
    $dirs = qd_locate_shadow_dirs();
    $derived = 'shadow_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $wp_session_id);
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





/* SESSION BOOTSTRAP: use QuickDate SessionStart(), included from bootstrap.php.
   Ensures idempotency; logs all attempts; robust against multiple includes. */
static $qd_session_bootstrapped = false;
if (!$qd_session_bootstrapped) {
    try {
        if (function_exists('SessionStart')) {
            SessionStart();
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }
    } catch (Throwable $e) {
        qd_bridge_log('SessionStart() exception', ['ex'=>$e->getMessage()]);
    }
    $qd_session_bootstrapped = true;
}
qd_bridge_log('SessionStart() called', [
    'phpSessionId' => session_id(),
    'shadow_session_id' => (isset($_COOKIE['PHPSESSID']) ? 'shadow_' . $_COOKIE['PHPSESSID'] : null)
]);

// Always attempt reconciliation with WP canonical session at bootstrap.
try {
    qd_attempt_session_reconciliation_if_required();
} catch (Throwable $e) {
    qd_bridge_log('Session reconciliation threw', ['err'=>$e->getMessage()]);
}

/* Defensive sync: run every 4 hours per session, verifying SSO and user id in session/cookie */
if (!isset($_SESSION['buzz_sso_defensive_last']) || (time() - (int)$_SESSION['buzz_sso_defensive_last']) > 4*3600) {
    $_SESSION['buzz_sso_defensive_last'] = time();
    $errs = [];
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) $errs[] = 'buzz_sso_cookie_missing';
    if (empty($_SESSION['wp_user_login'])) $errs[] = 'wp_user_login_missing';
    if (empty($_SESSION['qd_user_id']) || !is_numeric($_SESSION['qd_user_id'])) $errs[] = 'qd_user_id_missing_or_invalid';
    if ($errs) qd_bridge_log('Defensive sync checks', ['errs' => $errs]);
}

/* Normalize: hydrate from serialized session block or signed buzz_sso JWT cookie */
function normalize_sso_session() {
    global $BUZZ_SSO_SECRET;
    // Prefer serialized from mu-plugin if set
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
    // Fallback: parse stateless JWT cookie (RFC-compliant, robust signature check)
    if (!empty($_COOKIE[BUZZ_SSO_COOKIE]) && $BUZZ_SSO_SECRET) {
        $payload = qd_parse_buzz_sso_cookie_payload($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
        if (is_array($payload)) {
            foreach (['wp_user_id','wp_user_login','wp_user_email','qd_user_id'] as $f) {
                if (!empty($payload[$f]) && !isset($_SESSION[$f])) {
                    $_SESSION[$f] = $payload[$f];
                }
            }
            qd_bridge_log(
                'Session normalized from buzz_sso cookie', 
                ['payload_subset'=>array_intersect_key($payload,array_flip(['wp_user_id','wp_user_login','wp_user_email','qd_user_id']))]
            );
        }
    }
}
normalize_sso_session();

/* Secure session clearing & explicit SSO cookie expiry; do NOT destroy PHPSESSID. */
function qd_clear_and_logout($reason='unknown') {
    global $config;
    qd_bridge_log('Clearing session SSO keys and redirecting to logout', ['reason'=>$reason]);
    // Always ensure session is started so we can unset keys
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $sso_keys = [
        'wp_user_id','wp_user_login','wp_user_email',
        'wo_user_id','qd_user_id','qd_ready','expected_user_id',
        'buzz_sso_last_sync','wp_php_session_id','wp_session_name',
        'buzz_sso_last','buzz_sso_serialized','wp_sso_login'
    ];
    foreach ($sso_keys as $k) {
        if (isset($_SESSION[$k])) unset($_SESSION[$k]);
    }
    if (isset($_SESSION['JWT'])) unset($_SESSION['JWT']);
    // Expire cookie on .buzzjuice.net for logout propagation
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, '', [
            'expires'=>time()-3600, 'path'=>'/', 'domain'=>BUZZ_COOKIE_DOMAIN,
            'secure'=>true, 'httponly'=>true, 'samesite'=>'Lax'
        ]);
    } else {
        setcookie(BUZZ_SSO_COOKIE, '', time()-3600, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    // Redirect to WP logout page (should trigger orchestrated cross-platform logout)
    $base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
    $target = ($base ?: '') . '/../wp-login.php';
    header('Location: ' . $target);
    exit();
}

/* NOTE: Do not force immediate logout if buzz_sso cookie is absent.
   Only enforce logout when both cookie and session are invalid/stale.
*/
if (!$BUZZ_SSO_SECRET) {
    qd_bridge_log('Missing BUZZ_SSO_SECRET');
    qd_clear_and_logout('missing_secret');
}

/* Validate buzz_sso cookie presence + claims, fallback to session if recently synced. */
$cookie_payload = null;
if (!empty($_COOKIE[BUZZ_SSO_COOKIE]) && $BUZZ_SSO_SECRET) {
    $cookie_payload = qd_parse_buzz_sso_cookie_payload($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
    if (!is_array($cookie_payload)) $cookie_payload = null;
} else {
    qd_bridge_log('buzz_sso cookie not present (will attempt session-only flow if session has canonical values)');
}

$session_has_core = (!empty($_SESSION['wp_user_login']) && !empty($_SESSION['wp_user_id']) && !empty($_SESSION['wp_user_email']));
$session_qd_valid = (!empty($_SESSION['qd_user_id']) && is_numeric($_SESSION['qd_user_id']));
if ($cookie_payload && !empty($cookie_payload['wp_user_id']) && !empty($cookie_payload['wp_user_login']) && !empty($cookie_payload['wp_user_email'])) {
    // Valid cookie, normal flow (cookie_payload used below)
} else {
    // Allow if session has canonical SSO fields and was synced recently
    $last_sync = isset($_SESSION['buzz_sso_last_sync']) ? (int)$_SESSION['buzz_sso_last_sync'] : 0;
    $max_age = 1200; // 20 minutes defensive window
    if ($session_has_core && $session_qd_valid && ($last_sync && (time() - $last_sync) <= $max_age)) {
        qd_bridge_log('No valid buzz_sso cookie but proceeding: session has recent SSO sync', [
            'last_sync'=>$last_sync, 'age'=>time()-$last_sync
        ]);
        // safe to continue using session values
    } else {
        qd_bridge_log('buzz_sso invalid or missing and no fresh session — clearing and logout', [
            'cookie_present'=>!empty($_COOKIE[BUZZ_SSO_COOKIE]),
            'session_has_core'=>$session_has_core,
            'last_sync'=>$last_sync
        ]);
        qd_clear_and_logout('invalid_or_incomplete_cookie');
    }
}

/* Canonical fields from session/cookie for use in mapping */
$claim_wp_user_id    = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : (int)($cookie_payload['wp_user_id'] ?? 0);
$claim_wp_user_login = isset($_SESSION['wp_user_login']) ? (string)$_SESSION['wp_user_login'] : (string)($cookie_payload['wp_user_login'] ?? '');
$claim_wp_user_email = isset($_SESSION['wp_user_email']) ? (string)$_SESSION['wp_user_email'] : (string)($cookie_payload['wp_user_email'] ?? '');
$claim_qd_user_id    = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : (int)($cookie_payload['qd_user_id'] ?? 0);

/* Keep wp_user_login immutable once set */
if (!empty($_SESSION['wp_user_login']) && $_SESSION['wp_user_login'] !== $claim_wp_user_login) {
    qd_bridge_log('Attempt to change wp_user_login detected; preserving existing', [
        'existing'=>$_SESSION['wp_user_login'],
        'incoming'=>$claim_wp_user_login
    ]);
    $claim_wp_user_login = $_SESSION['wp_user_login'];
}

/* Map/register QuickDate user if necessary; robust DB access with logging */
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
 * qd_register_user() — stateless QuickDate user registration and mapping from WordPress SSO payload.
 * Returns new QuickDate user id (int) on success, or 0 on failure.
 * Defensive, robust, and cross-system compatible.
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

        // Collision check: append suffix if username already exists
        $db = get_qd_db_conn();
        $collision = false;
        $i = 1;
        $unique_username = $username;
        while ($db && $user_id = qd_find_user_by_login_email($unique_username, $email)) {
            $collision = true;
            $unique_username = $username . $i;
            $i++;
            if ($i > 10) { // Prevent infinite loop
                qd_bridge_log('qd_register_user: username collision, too many attempts', ['base'=>$username]);
                break;
            }
        }
        if ($collision) {
            qd_bridge_log('qd_register_user: username collision, using unique', ['base'=>$username, 'final'=>$unique_username]);
            $username = $unique_username;
        }

        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        $wp_full = (function_exists('wp_get_full_user_data') && $conn && $wp_user_id) ? wp_get_full_user_data($conn, $wp_user_id) : [];
        $avatar = $wp_full['xprofile']['avatar'] ?? $wp_full['meta']['avatar'] ?? ($GLOBALS['config']->userDefaultAvatar ?? '');

        $sso_token = bin2hex(random_bytes(8));
        if ($conn && $wp_user_id) {
            $res = @mysqli_query($conn, "SELECT user_pass FROM wp_users WHERE ID='" . intval($wp_user_id) . "' LIMIT 1");
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                if (!empty($row['user_pass'])) $sso_token = $row['user_pass'];
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

        // language fallback: accept both spelled and misspelled for cross-system compatibility
        $lang = 'english';
        if (isset($GLOBALS['config']->defaultLang) && !empty($GLOBALS['config']->defaultLang)) {
            $lang = $GLOBALS['config']->defaultLang;
        } elseif (isset($GLOBALS['config']->defualtLang) && !empty($GLOBALS['config']->defualtLang)) {
            $lang = $GLOBALS['config']->defualtLang;
        }

        // Build QuickDate registration payload from available xprofile/meta fields and canonical SSO core
        $re_data = [
            'username'      => $username,
            'password'      => $password,
            'email'         => $email,
            'avatar'        => $imported_avatar,
            'active'        => 1,
            'src'           => 'wp-sso',
            'wp_user_id'    => (int)$wp_user_id,
            'ip_address'    => function_exists('get_ip_address') ? get_ip_address() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            'language'      => $lang,
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

        // Persist mapping to WP usermeta: meta_key='qd_user_id', user_id=$wp_user_id
        $meta_key = 'qd_user_id';
        $meta_value = (string)$created_id;
        $did_write = false;

        // Only attempt to persist if we have a WP user id
        if (!empty($wp_user_id) && $wp_user_id > 0) {
            // Ensure session started before any session usage
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            // 1) Preferred: use repository helper which understands various runtimes
            if ($conn && function_exists('wp_update_usermeta')) {
                try {
                    // wp_update_usermeta supports signature: ($conn, $user_id, $meta_key, $meta_value)
                    wp_update_usermeta($conn, (int)$wp_user_id, $meta_key, $meta_value);
                    qd_bridge_log('Set wp_usermeta qd_user_id via wp_update_usermeta', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                    $did_write = true;
                } catch (Throwable $e) {
                    qd_bridge_log('wp_update_usermeta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                }
            }

            // 2) If WP runtime present, use WP API
            if (!$did_write && function_exists('update_user_meta')) {
                try {
                    update_user_meta((int)$wp_user_id, $meta_key, $meta_value);
                    qd_bridge_log('Set wp_usermeta qd_user_id via update_user_meta', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                    $did_write = true;
                } catch (Throwable $e) {
                    qd_bridge_log('update_user_meta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                }
            }

            // 3) Fallback: direct DB write (prepared statements), then raw queries
            if (!$did_write && $conn && $wp_user_id) {
                if (function_exists('wp_table')) {
                    $um_table_sql = wp_table('usermeta');
                } else {
                    $prefix = defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_';
                    if (defined('WP_DB_NAME')) {
                        $um_table_sql = '`' . WP_DB_NAME . '`.`' . $prefix . 'usermeta`';
                    } else {
                        $um_table_sql = '`' . $prefix . 'usermeta`';
                    }
                }

                $select_sql = "SELECT umeta_id FROM $um_table_sql WHERE user_id = ? AND meta_key = ? LIMIT 1";
                $stmt = @mysqli_prepare($conn, $select_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'is', $wp_user_id, $meta_key);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        mysqli_stmt_bind_result($stmt, $umeta_id);
                        mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);
                        $update_sql = "UPDATE $um_table_sql SET meta_value = ? WHERE umeta_id = ?";
                        $upd = @mysqli_prepare($conn, $update_sql);
                        if ($upd) {
                            mysqli_stmt_bind_param($upd, 'si', $meta_value, $umeta_id);
                            mysqli_stmt_execute($upd);
                            mysqli_stmt_close($upd);
                            qd_bridge_log('Updated wp_usermeta qd_user_id (direct prepared)', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id,'umeta_id'=>$umeta_id]);
                            $did_write = true;
                        } else {
                            qd_bridge_log('Failed to prepare update for wp_usermeta', ['sql'=>$update_sql,'error'=>$conn->error]);
                        }
                    } else {
                        mysqli_stmt_close($stmt);
                        $insert_sql = "INSERT INTO $um_table_sql (user_id, meta_key, meta_value) VALUES (?, ?, ?)";
                        $ins = @mysqli_prepare($conn, $insert_sql);
                        if ($ins) {
                            mysqli_stmt_bind_param($ins, 'iss', $wp_user_id, $meta_key, $meta_value);
                            mysqli_stmt_execute($ins);
                            mysqli_stmt_close($ins);
                            qd_bridge_log('Inserted wp_usermeta qd_user_id (direct prepared)', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                            $did_write = true;
                        } else {
                            qd_bridge_log('Failed to prepare insert for wp_usermeta', ['sql'=>$insert_sql,'error'=>$conn->error]);
                        }
                    }
                } else {
                    // Last resort: raw escaped queries
                    $esc_val = mysqli_real_escape_string($conn, $meta_value);
                    $esc_key = mysqli_real_escape_string($conn, $meta_key);
                    $check_raw = "SELECT umeta_id FROM $um_table_sql WHERE user_id = " . intval($wp_user_id) . " AND meta_key = '$esc_key' LIMIT 1";
                    $res = @$conn->query($check_raw);
                    if ($res && $res->num_rows > 0) {
                        $row = $res->fetch_assoc();
                        $umeta_id = intval($row['umeta_id']);
                        $raw_update = "UPDATE $um_table_sql SET meta_value = '$esc_val' WHERE umeta_id = $umeta_id";
                        @$conn->query($raw_update);
                        qd_bridge_log('Updated wp_usermeta qd_user_id (raw)', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id,'umeta_id'=>$umeta_id,'error'=>$conn->error]);
                        $did_write = true;
                    } else {
                        $raw_insert = "INSERT INTO $um_table_sql (user_id, meta_key, meta_value) VALUES (" . intval($wp_user_id) . ", '$esc_key', '$esc_val')";
                        @$conn->query($raw_insert);
                        qd_bridge_log('Inserted wp_usermeta qd_user_id (raw)', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id,'error'=>$conn->error]);
                        $did_write = true;
                    }
                }
            }

            if (!$did_write) {
                qd_bridge_log('No WP DB connection or method available to set qd_user_id', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
            }
        } else {
            qd_bridge_log('qd_register_user: no wp_user_id provided — skipping WP usermeta write', ['wp_user_id'=>$wp_user_id,'created_qd_id'=>$created_id]);
        }

        // Set session qd id for later mapping logic (best-effort; ensure session started)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        try {
            $_SESSION['qd_user_id'] = $created_id;
        } catch (Throwable $e) {
            qd_bridge_log('qd_register_user: failed to set session qd_user_id', ['ex'=>$e->getMessage()]);
        }

        // Mask email address in logs for privacy
        $masked_email = !empty($email) ? preg_replace('/(^.).*(.@.*$)/', '$1***$2', $email) : $email;

        qd_bridge_log('qd_register_user: Auto-registered QuickDate user', [
            'id'       => $created_id,
            'username' => $username,
            'email'    => $masked_email,
            're_data'  => $re_data,
            'wp_write' => $did_write
        ]);
        return $created_id;
    }
}





/**
 * Helper: Persist QD user mapping to WordPress usermeta, with defensive logging.
 */
function persist_qd_to_wp($wp_user_id, $qd_user_id) {
    $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
    if ($wp_conn && function_exists('wp_update_usermeta')) {
        try {
            wp_update_usermeta($wp_conn, (int)$wp_user_id, ['qd_user_id' => (int)$qd_user_id], null);
            qd_bridge_log('Persisted qd_user_id to WP usermeta', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$qd_user_id]);
        } catch (Throwable $e) {
            qd_bridge_log('Exception persisting qd_user_id to WP usermeta', ['ex'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'qd_user_id'=>$qd_user_id]);
        }
    } else {
        qd_bridge_log('WP persistence unavailable', ['wp_conn'=> (bool)$wp_conn,'has_wp_update'=>function_exists('wp_update_usermeta')]);
    }
}

/**
 * Normalize login/email claims.
 */
$claim_wp_user_login = strtolower(trim($claim_wp_user_login ?? ''));
$claim_wp_user_email = strtolower(trim($claim_wp_user_email ?? ''));

$final_qd_user_id = 0;
$orig_session_qd = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : 0;

qd_bridge_log('Mapping start', [
    'claim_qd'    => $claim_qd_user_id,
    'session_qd'  => $orig_session_qd,
    'login'       => $claim_wp_user_login,
    'email'       => $claim_wp_user_email
]);

// Strict canonical verification: validate all SSO claims match actual QuickDate DB record.
$has_all_canonical = ($claim_qd_user_id && $claim_wp_user_id && $claim_wp_user_login && $claim_wp_user_email);

if ($has_all_canonical) {
    qd_bridge_log('All canonical SSO values present — strict verification', [
        'claim_qd'      => $claim_qd_user_id,
        'wp_user_id'    => $claim_wp_user_id,
        'wp_user_login' => $claim_wp_user_login,
        'wp_user_email' => $claim_wp_user_email
    ]);
    $row = qd_get_user_row($claim_qd_user_id);
    if ($row) {
        $db_un = isset($row['username']) ? strtolower(trim($row['username'])) : '';
        $db_em = isset($row['email']) ? strtolower(trim($row['email'])) : '';
        if ($db_un === $claim_wp_user_login && $db_em === $claim_wp_user_email) {
            $final_qd_user_id = (int)$claim_qd_user_id;
            qd_bridge_log('Strict verification successful — qd_user_id accepted', ['qd_user_id' => $final_qd_user_id]);
        } else {
            qd_bridge_log('Strict verification failed; clearing session qd_user_id', [
                'session_qd_user_id' => $claim_qd_user_id,
                'db_username'        => $db_un,
                'db_email'           => $db_em,
                'session_login'      => $claim_wp_user_login,
                'session_email'      => $claim_wp_user_email
            ]);
            unset($_SESSION['qd_user_id']);
            $claim_qd_user_id = 0;
            $orig_session_qd = 0;
        }
    } else {
        qd_bridge_log('Strict verification failed: qd_user_id not found in DB; clearing session/qc id', [
            'qd_user_id' => $claim_qd_user_id
        ]);
        unset($_SESSION['qd_user_id']);
        $claim_qd_user_id = 0;
        $orig_session_qd = 0;
    }
}

if (!$final_qd_user_id) {
    // If qd_user_id in session/cookie and exists in DB, use it
    if ($claim_qd_user_id && qd_find_user_by_id($claim_qd_user_id)) {
        $final_qd_user_id = $claim_qd_user_id;
        qd_bridge_log('Using qd_user_id from cookie/session (exists in DB)', ['qd_user_id' => $final_qd_user_id]);
        if (!empty($claim_wp_user_id)) persist_qd_to_wp($claim_wp_user_id, $final_qd_user_id);

    } else {
        // Try to find by login+email
        $found = qd_find_user_by_login_email($claim_wp_user_login, $claim_wp_user_email);
        if ($found) {
            $final_qd_user_id = $found;
            qd_bridge_log('Mapped qd_user_id via login+email', ['qd_user_id' => $final_qd_user_id]);
            if (!empty($claim_wp_user_id)) persist_qd_to_wp($claim_wp_user_id, $final_qd_user_id);

        } else {
            // Auto-registration if allowed
            if (BUZZ_SSO_AUTO_REGISTER) {
                qd_bridge_log('No mapping found — attempting auto-register', [
                    'login'    => $claim_wp_user_login,
                    'email'    => $claim_wp_user_email,
                    'original' => $orig_session_qd
                ]);
                $created = qd_register_user($claim_wp_user_login, $claim_wp_user_email, $claim_wp_user_id);
                if ($created) {
                    $final_qd_user_id = (int)$created;
                    $_SESSION['qd_user_id'] = $final_qd_user_id;
                    $claim_qd_user_id = $final_qd_user_id;
                    qd_bridge_log('Auto-register created QuickDate user', ['created_id' => $created]);
                    if (!empty($claim_wp_user_id)) persist_qd_to_wp($claim_wp_user_id, $final_qd_user_id);
                } else {
                    qd_bridge_log('Auto-register failed (returned id is falsy/zero)', [
                        'login' => $claim_wp_user_login,
                        'email' => $claim_wp_user_email
                    ]);
                }
            } else {
                qd_bridge_log('Auto registration disabled and no mapping found', [
                    'login' => $claim_wp_user_login,
                    'email'=> $claim_wp_user_email
                ]);
            }

            // Fallback: If session originally had qd id and it now exists, use it
            if (!$final_qd_user_id && $orig_session_qd && qd_find_user_by_id($orig_session_qd)) {
                $final_qd_user_id = $orig_session_qd;
                qd_bridge_log('Preserving original session qd_user_id after all attempts', [
                    'qd_user_id' => $final_qd_user_id
                ]);
                if (!empty($claim_wp_user_id)) persist_qd_to_wp($claim_wp_user_id, $final_qd_user_id);
            }
        }
    }
}

// Enforce logout if we still cannot resolve a valid QuickDate user id
if (!$final_qd_user_id) {
    qd_bridge_log('SSO mapping failed — forcing logout', [
        'session_qd' => $orig_session_qd,
        'login'      => $claim_wp_user_login
    ]);
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    header('Location: /login.php'); // You may want to redirect to your actual QD login or error landing page.
    exit;
}





// 5) If still no final id, fail and logout
if (!$final_qd_user_id) {
    qd_bridge_log('Unable to determine QuickDate user id after mapping/registration', [
        'session' => $_SESSION,
        'cookie_payload' => $cookie_payload ?? null,
        'env' => [
            'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ],
    ]);
    qd_clear_and_logout('no_qd_user_after_mapping');
    exit; // safety!
}

// Consolidated session update (atomic session state, timestamped)
$_SESSION = array_merge($_SESSION, [
    'wp_user_login' => $_SESSION['wp_user_login'] ?? $claim_wp_user_login,
    'wp_user_id'    => (int)$claim_wp_user_id,
    'wp_user_email' => $claim_wp_user_email,
    'qd_user_id'    => (int)$final_qd_user_id,
    'qd_user_ts'    => time(),  // useful for staleness detection
]);

/*
 * Ensure buzz_sso cookie contains canonical identifiers.
 * - Only re-issue cookie if any canonical field (wp_user_id, login/email, qd_user_id) differs.
 */
try {
    $need_issue = false;
    $cookie_canonical = [
        'wp_user_id'    => (int)$_SESSION['wp_user_id'],
        'wp_user_login' => (string)$_SESSION['wp_user_login'],
        'wp_user_email' => (string)$_SESSION['wp_user_email'],
        'qd_user_id'    => (int)$_SESSION['qd_user_id'],
    ];

    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) {
        $need_issue = true;
    } else {
        // JWT-first, fallback to legacy
        if (!is_array($cookie_payload)) {
            $cookie_payload = null;
            if (substr_count($_COOKIE[BUZZ_SSO_COOKIE], '.') === 2) {
                $res = qd_jwt_verify($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET, 'quickdate');
                if ($res && !empty($res['ok']) && $res['ok'] && !empty($res['payload'])) {
                    $cookie_payload = $res['payload'];
                }
            }
            if (!is_array($cookie_payload)) {
                $cookie_payload = qd_parse_sso_password_token($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET) ?: null;
            }
        }
        // Compare all canonical keys
        if (!is_array($cookie_payload) ||
            array_intersect_key($cookie_payload, $cookie_canonical) !== $cookie_canonical
        ) {
            $need_issue = true;
        }
    }

    if ($need_issue) {
        qd_issue_buzz_sso_cookie($cookie_canonical);
    }
} catch (Throwable $e) {
    qd_bridge_log('Exception while ensuring long-lived buzz_sso cookie', [
        'ex'    => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// --- Defer server-side redirect if already logged in; let client JS handle it ---
$deferred_redirect_target = null;
if (defined('IS_LOGGED') && IS_LOGGED === true) {
    $deferred_redirect_target = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/steps';
    qd_bridge_log('IS_LOGGED true; deferring redirect to client', [
        'user_id' => $_SESSION['qd_user_id'],
        'target'  => $deferred_redirect_target
    ]);
}

// --- SSO password token (legacy/fallback) ---
function qd_build_sso_password_token($qd_user_id, $wp_user_id, $wp_user_login, $wp_user_email, $secret) {
    try {
        $claims = [
            'ver'            => 1,
            'qd_user_id'     => (int)$qd_user_id,
            'wp_user_id'     => (int)$wp_user_id,
            'wp_user_login'  => (string)$wp_user_login,
            'wp_user_email'  => (string)$wp_user_email,
            'iat'            => time(),
            'exp'            => time() + BUZZ_SSO_TTL,
            'nonce'          => bin2hex(random_bytes(8)),
        ];
    } catch (Exception $e) {
        qd_bridge_log('Failed to build nonce for password token', ['err' => $e->getMessage()]);
        $claims['nonce'] = md5(uniqid('', true));
    }
    $json = json_encode($claims);
    $sig  = hash_hmac('sha256', $json, (string)$secret, true);
    return 'WPSSO.v1.' . qd_b64url_encode($json) . '.' . qd_b64url_encode($sig);
}
$sso_username = $_SESSION['wp_user_login'];
$sso_token = qd_build_sso_password_token(
    $_SESSION['qd_user_id'],
    $_SESSION['wp_user_id'],
    $_SESSION['wp_user_login'],
    $_SESSION['wp_user_email'],
    $BUZZ_SSO_SECRET
);

// Build and validate last_url (defensive)
$site_base = defined('SITE_URL')
    ? rtrim(SITE_URL,'/')
    : (isset($config->uri) ? rtrim($config->uri,'/') : '');
$last_url = '/';
// Prefer GET, POST, then COOKIE for last_url
foreach (['last_url'] as $k) {
    if (!empty($_GET[$k]))    { $last_url = (string)$_GET[$k]; break; }
    if (!empty($_POST[$k]))   { $last_url = (string)$_POST[$k]; break; }
    if (!empty($_COOKIE[$k])) { $last_url = (string)$_COOKIE[$k]; break; }
}

// If server already logged in, override to deferred target
if (!empty($deferred_redirect_target)) {
    $last_url = $deferred_redirect_target;
} else if ($last_url && $site_base) {
    // Strict local enforcement: last_url must match domain, begin at base
    $parsed_base = parse_url($site_base, PHP_URL_HOST);
    $parsed_last = parse_url($last_url, PHP_URL_HOST);
    if ($parsed_last && strtolower($parsed_last) !== strtolower($parsed_base)) {
        $last_url = $site_base . '/';
    }
    // Path safety (last_url must start with site_base or '/')
    if (strpos($last_url, $site_base) !== 0 && strpos($last_url, '/') !== 0) {
        $last_url = '/';
    }
}
if (!$last_url) $last_url = '/';

// AJAX login endpoint for client JS bridge
$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php') . '?sso_action=do_login';

qd_bridge_log('SSO client payload prepared', [
    'sso_username'     => $sso_username,
    'sso_token_len' => strlen($sso_token),
    'ajax_url'         => $ajax_url,
    'last_url'         => $last_url
]);

// --- POST handler for AJAX login endpoint ---
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    QD_SSO_Login();
    exit;
}

/**
 * QuickDate table columns helper (with caching)
 */
if (!function_exists('qd_get_columns')) {
    function qd_get_columns($conn, $table) {
        static $cache = [];
        $key = $table;
        if (isset($cache[$key])) return $cache[$key];
        $cols = [];
        if (!$conn) return $cols;
        try {
            $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
            if ($res) {
                while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
            }
        } catch (Throwable $e) {
            qd_bridge_log('qd_get_columns query failed', [
                'table' => $table,
                'ex'    => $e->getMessage()
            ]);
        }
        $cache[$key] = $cols;
        return $cols;
    }
}





function QD_SSO_Login() {
    global $BUZZ_SSO_SECRET, $config;
    header('Content-Type: application/json; charset=utf-8');

    $username = $_POST['username'] ?? '';
    $sso_token = $_POST['sso_token'] ?? '';
    $last_url = $_POST['last_url'] ?? '/';

    qd_bridge_log('QD_SSO_Login called', [
        'post_username' => $username,
        'pw_len'        => strlen($sso_token),
        'last_url'      => $last_url
    ]);

    if (!$BUZZ_SSO_SECRET) {
        qd_bridge_log('QD_SSO_Login: BUZZ_SSO_SECRET missing');
        http_response_code(500);
        echo json_encode(['status'=>500,'errors'=>['SSO server misconfigured.']]); exit;
    }

    $claims = qd_parse_sso_password_token($sso_token, $BUZZ_SSO_SECRET);
    if (!$claims) {
        qd_bridge_log('QD_SSO_Login: invalid/expired SSO password token', [
            'token_preview' => substr($sso_token,0,36)
        ]);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid or expired login token.']]); exit;
    }

    // Normalize identifiers (case/minimal)
    $norm_email = function($s){ return strtolower(trim((string)$s)); };
    $norm_login = function($s){ return strtolower(trim((string)$s)); };

    $sess_qd    = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : 0;
    $sess_wp    = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : 0;
    $sess_login = isset($_SESSION['wp_user_login']) ? $norm_login($_SESSION['wp_user_login']) : '';
    $sess_email = isset($_SESSION['wp_user_email']) ? $norm_email($_SESSION['wp_user_email']) : '';

    $exp_qd    = $sess_qd    ?: (int)($claims['qd_user_id'] ?? 0);
    $exp_wp    = $sess_wp    ?: (int)($claims['wp_user_id'] ?? 0);
    $exp_login = $sess_login ?: $norm_login($claims['wp_user_login'] ?? '');
    $exp_email = $sess_email ?: $norm_email($claims['wp_user_email'] ?? '');

    // Masked for logging
    $masked_email = $exp_email ? substr($exp_email,0,3) . str_repeat('*',max(0,strlen($exp_email)-6)) . substr($exp_email,-3) : null;

    qd_bridge_log('QD_SSO_Login expectations', [
        'qd_user_id' => $exp_qd,
        'wp_user_id' => $exp_wp,
        'wp_user_login' => $exp_login,
        'wp_user_email_masked' => $masked_email
    ]);

    // Candidate lookup—deduplicated by id
    $db = get_qd_db_conn();
    $candidates = [];
    $seen = [];
    if ($db) {
        $queries = [
            ['id', $exp_qd],
            ['email', $exp_email],
            ['username', $exp_login],
            ['wp_user_id', $exp_wp]
        ];
        foreach ($queries as [$field, $value]) {
            if ($value) {
                $col = $field;
                $esc = $col === 'id' || $col === 'wp_user_id' ? (int)$value : $db->real_escape_string($value);
                $sql = $col === 'id' || $col === 'wp_user_id'
                    ? "SELECT * FROM users WHERE $col=$esc LIMIT 1"
                    : "SELECT * FROM users WHERE $col='{$esc}' LIMIT 1";
                if ($result = $db->query($sql)) {
                    if ($row = $result->fetch_assoc()) {
                        $row_id = (int)$row['id'];
                        if (!isset($seen[$row_id])) {
                            $candidates[] = $row;
                            $seen[$row_id] = true;
                        }
                    }
                }
            }
        }
    } else {
        qd_bridge_log('QD_SSO_Login: QD DB unavailable.');
        http_response_code(503);
        echo json_encode(['status'=>503,'errors'=>['QuickDate is temporarily unavailable.']]); exit;
    }

    qd_bridge_log('QD_SSO_Login candidates', [
        'total' => count($candidates), 'ids' => array_map(function($c){return $c['id'];}, $candidates)
    ]);

    // Accept user if ≥3 identifiers match (case-insensitive)
    $accepted_user = null; $accepted_matches = [];
    foreach ($candidates as $row) {
        $db_id  = (int)$row['id'];
        $db_un  = $norm_login($row['username']);
        $db_em  = $norm_email($row['email']);
        $db_wpu = (int)($row['wp_user_id'] ?? 0);

        $m_id  = ($exp_qd    && $db_id === $exp_qd) ? 1 : 0;
        $m_em  = ($exp_email && $db_em === $exp_email) ? 1 : 0;
        $m_un  = ($exp_login && $db_un === $exp_login) ? 1 : 0;
        $m_wpu = ($exp_wp    && $db_wpu === $exp_wp) ? 1 : 0;

        $cnt = $m_id + $m_em + $m_un + $m_wpu;

        if ($cnt >= 3) {
            $accepted_user = $row;
            $accepted_matches = ['id'=>$m_id,'email'=>$m_em,'username'=>$m_un,'wp_user_id'=>$m_wpu];
            break;
        }
    }

    if (!$accepted_user) {
        qd_bridge_log('QD_SSO_Login: no accepted candidate (need ≥3)', [
            'expected'=>['qd'=>$exp_qd,'wp'=>$exp_wp,'login'=>$exp_login,'email_masked'=>$masked_email],
            'candidates'=>array_map(function($c) {
                return ['id'=>$c['id'],'username'=>$c['username'],'email_masked'=>substr($c['email'],0,3).'***'.substr($c['email'],-3),'wp_user_id'=>$c['wp_user_id'] ?? null];
            }, $candidates)
        ]);
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION = [];
            @session_unset();
        }
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['No matching QuickDate account.']]); exit;
    }

    // --- Session: Write all canonical state (no session_regenerate_id; preserves WP session) ---
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    $_SESSION = array_merge($_SESSION, [
        'qd_user_id'    => (int)$accepted_user['id'],
        'user_id'       => $accepted_user['web_token'] ?? (int)$accepted_user['id'],
        'wp_sso_login'  => true,
        'wp_user_id'    => $exp_wp,
        'wp_user_email' => $exp_email,
        'wp_user_login' => $_SESSION['wp_user_login'] ?? $exp_login,
        'qd_user_ts'    => time()
    ]);

    // --- QuickDate session login (framework/session JWTs) ---
    if (function_exists('LoadEndPointResource')) {
        $usersRes = LoadEndPointResource('users');
        if ($usersRes && method_exists($usersRes, 'SetLoginWithSession') && !empty($exp_email)) {
            try {
                $usersRes->SetLoginWithSession($exp_email);
                qd_bridge_log('QD_SSO_Login: SetLoginWithSession called', ['email_masked' => $masked_email]);
            } catch (Throwable $e) {
                qd_bridge_log('QD_SSO_Login: SetLoginWithSession exception', ['err' => $e->getMessage()]);
            }
        }
    }

    // --- WordPress→QuickDate metadata sync ---
    try {
        qd_bridge_log('QD_SSO_Login: Sync WP→QD', ['wp_user_id' => $exp_wp, 'wp_user_email_masked' => $masked_email]);
        $did_sync = false;
        if (!empty($exp_email) && !empty($exp_wp)
            && function_exists('sync_user_to_quickdate') && function_exists('wp_get_full_user_data')) {
            $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
            if ($wp_conn) {
                $wp_full = wp_get_full_user_data($wp_conn, $exp_wp);
                if ($wp_full && is_array($wp_full)) {
                    $usermeta = $wp_full['meta'] ?? [];
                    $xprofile = $wp_full['xprofile'] ?? [];
                    $ok = sync_user_to_quickdate($exp_email, $usermeta, $xprofile);
                    qd_bridge_log('QD_SSO_Login: sync_user_to_quickdate result', [
                        'email_masked' => $masked_email, 'wp_user_id' => $exp_wp, 'ok' => (bool)$ok
                    ]);
                    $did_sync = (bool)$ok;
                }
            }
        } elseif (!empty($exp_email) && function_exists('get_user_field_metadata') && function_exists('wp_get_full_user_data') && function_exists('qd_update_user')) {
            $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
            $wp_full = $wp_conn ? wp_get_full_user_data($wp_conn, $exp_wp) : null;
            if ($wp_full && is_array($wp_full)) {
                $metadata = get_user_field_metadata();
                $public_fields = $metadata['public_open_fields'] ?? [];
                $private_fields = $metadata['private_secure_fields'] ?? [];
                $qd_candidate = [];
                foreach ($public_fields as $qd_key => $map) {
                    if (isset($wp_full['xprofile'][$qd_key]) && $wp_full['xprofile'][$qd_key] !== '') {
                        $qd_candidate[$qd_key] = $wp_full['xprofile'][$qd_key];
                    } elseif (isset($wp_full['meta'][$qd_key]) && $wp_full['meta'][$qd_key] !== '') {
                        $qd_candidate[$qd_key] = $wp_full['meta'][$qd_key];
                    }
                }
                foreach ($private_fields as $qd_key => $map) {
                    if (!isset($qd_candidate[$qd_key]) && isset($wp_full['meta'][$qd_key]) && $wp_full['meta'][$qd_key] !== '') {
                        $qd_candidate[$qd_key] = $wp_full['meta'][$qd_key];
                    }
                }
                if (!isset($qd_candidate['username']) && !empty($wp_full['user_login'])) $qd_candidate['username'] = $wp_full['user_login'];
                if (!isset($qd_candidate['email']) && !empty($wp_full['user_email'])) $qd_candidate['email'] = $wp_full['user_email'];
                if (!isset($qd_candidate['first_name']) && !empty($wp_full['meta']['first_name'])) $qd_candidate['first_name'] = $wp_full['meta']['first_name'];
                if (!isset($qd_candidate['last_name']) && !empty($wp_full['meta']['last_name'])) $qd_candidate['last_name'] = $wp_full['meta']['last_name'];
                if (!isset($qd_candidate['avatar'])) {
                    $avatar = $wp_full['xprofile']['avatar'] ?? $wp_full['meta']['avatar'] ?? '';
                    if ($avatar) $qd_candidate['avatar'] = $avatar;
                }
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
                    qd_bridge_log('QD_SSO_Login: qd_update_user fallback result', [
                        'email_masked' => $masked_email, 'keys'=>array_keys($qd_update), 'ok'=>(bool)$ok
                    ]);
                    $did_sync = (bool)$ok;
                }
            }
        } else {
            qd_bridge_log('QD_SSO_Login: WP→QD sync not run—missing prerequisites', [
                'has_email' => !empty($exp_email),
                'has_wp_id' => !empty($exp_wp),
                'function_table' => [
                    'sync_user_to_quickdate'=>function_exists('sync_user_to_quickdate'),
                    'get_user_field_metadata'=>function_exists('get_user_field_metadata'),
                    'wp_get_full_user_data'=>function_exists('wp_get_full_user_data'),
                    'qd_update_user'=>function_exists('qd_update_user'),
                ]
            ]);
        }
        if (!$did_sync) {
            qd_bridge_log('QD_SSO_Login: post-login sync did not run or failed', [
                'wp_user_id' => $exp_wp, 'email_masked' => $masked_email
            ]);
        }
    } catch (Throwable $e) {
        qd_bridge_log('QD_SSO_Login: Exception during QD sync', [
            'err' => $e->getMessage(),'trace' => $e->getTraceAsString()
        ]);
    }

    // Internal destination: find-matches if new, else steps, else last_url (internal only)
    $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/steps';
    if (!empty($accepted_user['start_up']) && $accepted_user['start_up'] == 3 && !empty($accepted_user['verified'])) {
        $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/find-matches';
    }
    // Accept last_url only if relative ("/...") or under same-site base
    if (!empty($last_url) && $last_url !== '//') {
        $site_base = isset($config->uri) ? rtrim($config->uri,'/') : '';
        $site_host = parse_url($site_base, PHP_URL_HOST);
        $last_host = parse_url($last_url, PHP_URL_HOST) ?? $site_host;
        if (strpos($last_url, '/') === 0 || (strtolower($site_host) === strtolower($last_host) && strpos($last_url, $site_base) === 0)) {
            $url = $last_url;
        }
    }

    qd_bridge_log('QD_SSO_Login: success', [
        'user_id'   => $accepted_user['id'],
        'matches'   => $accepted_matches,
        'redirect'  => $url,
        'session_id'=> session_id()
    ]);

    http_response_code(200);
    echo json_encode(['status'=>200, 'location'=>$url]);
    exit;
}





qd_bridge_log('Rendering QD SSO bridge page', [
    'sso_username'       => $sso_username,
    'sso_token_len'      => strlen($sso_token), // Use token, NOT password
    'last_url'           => $last_url,
    'final_qd_user_id'   => $final_qd_user_id,
    'php_session_id'     => session_id(),
    'shadow_session_id'  => (isset($_COOKIE['PHPSESSID']) ? 'shadow_' . $_COOKIE['PHPSESSID'] : null),
    'session_vars'       => array_intersect_key($_SESSION, ['qd_user_id'=>1, 'wp_user_id'=>1, 'wp_user_login'=>1]),
    'cookies'            => array_intersect_key($_COOKIE, ['PHPSESSID'=>1, BUZZ_SSO_COOKIE=>1])
]);
$client_nonce = bin2hex(random_bytes(8)); // Replace with backend nonce for CSRF if needed
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
#progress{margin-top:.7em;height:6px;background:#222;border-radius:3px;overflow:hidden}
#progress-inner{height:100%;width:0;background:#345afc;transition:width .4s}
a.fallback-link{display:inline-block;margin-top:2em;color:#fff;text-decoration:underline}
</style>
</head>
<body>
  <div class="card">
    <div class="title">Signing you in…</div>
    <div id="status" class="status">Preparing secure session…</div>
    <div id="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="5"><div id="progress-inner"></div></div>
    <?php if (qd_is_debug()): ?>
      <div class="dbg"><pre><?php echo htmlspecialchars(print_r([
          'ajax_url' => $ajax_url,
          'post' => [
              'username'        => $sso_username,
              'token'           => '(sso-token)',
              'last_url'        => $last_url,
              'remember_device' => 'on'
          ],
          'session' => array_intersect_key($_SESSION, ['qd_user_id'=>1,'wp_user_id'=>1,'wp_user_login'=>1]),
          'cookies' => array_intersect_key($_COOKIE, ['PHPSESSID'=>1, BUZZ_SSO_COOKIE=>1])
      ], true)); ?></pre></div>
    <?php endif; ?>
    <a class="fallback-link" href="<?php echo htmlspecialchars($last_url); ?>" style="display:none" id="fallback-link">Click here if you are not redirected</a>
  </div>

  <script>
  (function(){
    var ajaxUrl = <?php echo json_encode($ajax_url); ?>;
    var payload = {
      username: <?php echo json_encode($sso_username); ?>,
      token: <?php echo json_encode($sso_token); ?>,
      remember_device: 'on',
      last_url: <?php echo json_encode($last_url); ?>,
      nonce: <?php echo json_encode($client_nonce); ?>
    };
    var beaconUrl = <?php
      $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php';
      echo json_encode($self . '?sso_client_log=1');
    ?>;
    var statusEl = document.getElementById('status');
    var progEl = document.getElementById('progress-inner');
    var fallbackLink = document.getElementById('fallback-link');
    var maxRetries = 2, attempts = 0;

    function setProgress(val) {
      progEl && (progEl.style.width = val + '%');
      var pb = document.getElementById('progress');
      if (pb) pb.setAttribute('aria-valuenow', val);
    }

    function beacon(msg, extra){
      try{
        // You can hash payload.username if desired to reduce PII in telemetry
        var _extra = Object.assign({}, extra || {});
        if (_extra.u) _extra.u = (_extra.u && typeof _extra.u === 'string') ? _extra.u.substring(0,3) + '***' : _extra.u;
        var data = JSON.stringify({msg:msg,extra:_extra,when:Date.now(),attempt:attempts});
        if (navigator.sendBeacon) navigator.sendBeacon(beaconUrl, data);
        else {
          var x = new XMLHttpRequest();
          x.open('POST', beaconUrl, true);
          x.setRequestHeader('Content-Type','application/json');
          x.send(data);
        }
      } catch(e){}
    }

    function showFallback() {
      fallbackLink && (fallbackLink.style.display='block');
    }

    function doAjax() {
      attempts++;
      setProgress(20 * attempts);
      statusEl && (statusEl.textContent = 'Contacting server… (#' + attempts + ')');
      beacon('bridge:init', {ajaxUrl: ajaxUrl, u: payload.username, last: payload.last_url, attempt: attempts});

      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.withCredentials = true;
      xhr.timeout = 15000;
      xhr.onreadystatechange = function(){
        if (xhr.readyState === 4) {
          var ok=false, locationUrl=null, errors=null, res=null;
          setProgress(100);
          try { res = JSON.parse(xhr.responseText); }
          catch(e) { beacon('bridge:parse_error', {http: xhr.status, text: xhr.responseText, attempt: attempts}); }
          if (res) {
            ok = !!(res.status===200 || res.status===600) && !!res.location;
            locationUrl = res.location;
            errors = res.errors || null;
          }
          beacon('bridge:response', {status: res && res.status, location: locationUrl, errors: errors, http: xhr.status, attempt: attempts});
          if (ok) {
            statusEl && (statusEl.className='status ok', statusEl.textContent='Welcome back! Redirecting…');
            setTimeout(function(){ window.location.href = locationUrl; }, 400);
          } else {
            var body = xhr.responseText || '';
            var looksLikeHtml = body.indexOf('<!DOCTYPE') !== -1 || body.indexOf('<html') !== -1;
            if (!res && looksLikeHtml && payload.last_url) {
              beacon('bridge:fallback_html_redirect', {http: xhr.status, fallback: payload.last_url});
              window.location.href = payload.last_url;
              return;
            }
            statusEl && (statusEl.className='status err', statusEl.textContent=
              Array.isArray(errors) ? errors.join(', ') : (errors ? JSON.stringify(errors) : 'Unexpected response.'));
            beacon('bridge:failed', {http: xhr.status, response: xhr.responseText, attempt: attempts});
            showFallback();
            if (attempts < maxRetries) {
              setTimeout(doAjax, 900);
            }
          }
        }
      };
      xhr.onerror = function(){ beacon('bridge:error', {http: xhr.status, attempt: attempts}); statusEl && (statusEl.className='status err', statusEl.textContent='Network or server error.'); showFallback(); if (attempts < maxRetries) setTimeout(doAjax, 1000);}
      xhr.ontimeout = function(){ beacon('bridge:timeout', {attempt: attempts}); statusEl && (statusEl.className='status err', statusEl.textContent='Request timed out.'); showFallback(); if (attempts < maxRetries) setTimeout(doAjax, 1000);}
      var body = 'username=' + encodeURIComponent(payload.username)
               + '&sso_token=' + encodeURIComponent(payload.token)
               + '&remember_device=on'
               + '&last_url=' + encodeURIComponent(payload.last_url)
               + '&nonce=' + encodeURIComponent(payload.nonce);
      xhr.send(body);
    }
    setProgress(5);
    setTimeout(doAjax, 300);
  })();
  </script>
</body>
</html>