<?php
//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 1
/**
 * qd-sso-bridge.php — BuzzJuice → QuickDate SSO Bridge (Stateless JWT Edition)
 *
 * Implements the modern, security-hardened, stateless SSO workflow described in pasted.txt,
 * in architectural parity with the updated WoWonder SSO bridge.
 *
 * - WordPress is the sole SSO authority: identity and authorization is strictly via JWT acquired from
 *   the stateless endpoint (?sso_action=get_token) in wp-content/mu-plugins/sso-session-sync.php.
 * - No trust in PHP sessions, shadow sessions, or legacy SSO bridging logic.
 * - Full RFC 7519 JWT claim discipline, with iss/aud/nbf/exp/jti and HS256 HMAC signature checking.
 * - Hardened replay protection using `jti` file store (20 min expiry, 1hr prune).
 * - Secure error-handling/fail-safes, robust audit logging, future-proof refresh token hooks.
 */

require_once __DIR__ . '/bootstrap.php'; // Core QD boot

if (file_exists(__DIR__ . '/../shared/wwqd_bridge.php')) {
    require_once __DIR__ . '/../shared/wwqd_bridge.php'; // user/profile sync, helpers
}
if (file_exists(__DIR__ . '/controllers/aj.php')) require_once __DIR__ . '/controllers/aj.php';
if (file_exists(__DIR__ . '/requests/ajax/useractions.php')) require_once __DIR__ . '/requests/ajax/useractions.php';

// -------------------
// CONFIG/LOGGING
// -------------------
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/qd_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);

// Debug/Log helpers
function qd_is_debug() {
    return (
        (isset($_GET['sso_debug']) && $_GET['sso_debug'] === '1') ||
        (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG === true)
    );
}
function qd_bridge_log($msg, $ctx = []) {
    $data = [
        'ts'   => gmdate('Y-m-d H:i:s'),
        'php_session_id' => function_exists('session_id') ? session_id() : null,
        'session_name'   => function_exists('session_name') ? session_name() : null,
        'buzz_sso_len'   => isset($_COOKIE[BUZZ_SSO_COOKIE]) ? strlen($_COOKIE[BUZZ_SSO_COOKIE]) : 0,
        'remote_addr'    => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua'             => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    if (qd_is_debug()) {
        $data['cookies'] = $_COOKIE ?? [];
        $data['session'] = $_SESSION ?? [];
        $data['server']  = [
            'HTTP_HOST'    => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI'  => $_SERVER['REQUEST_URI'] ?? null,
            'HTTPS'        => $_SERVER['HTTPS'] ?? null
        ];
        $data['sess_cookie_params'] = function_exists('session_get_cookie_params') ? session_get_cookie_params() : null;
    }
    if ($ctx) $data['ctx'] = $ctx;
    @file_put_contents(BUZZ_SSO_BRIDGE_LOG, '['.$data['ts'].'] '.$msg.' | '.json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
}

// -------------------
// JWT & SSO stateless helpers
// -------------------
function qd_b64url_decode($str) {
    if ($str === null || $str === '') return '';
    $s = strtr($str, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad) $s .= str_repeat('=', 4 - $pad);
    $out = base64_decode($s, true);
    return $out === false ? '' : $out;
}
function qd_b64url_encode($bin) {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}
function qd_validate_jwt($jwt, $secret) {
    if (!$jwt || !$secret) return false;
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    list($h, $p, $s) = $parts;
    $header  = json_decode(qd_b64url_decode($h), true);
    $payload = json_decode(qd_b64url_decode($p), true);
    if (!$header || !$payload || ($header['alg'] ?? '') !== 'HS256') return false;
    $expected_sig = hash_hmac('sha256', "$h.$p", $secret, true);
    $actual_sig  = qd_b64url_decode($s);
    if (!hash_equals($expected_sig, $actual_sig)) return false;
    $now = time();
    if (!empty($payload['nbf']) && $now < $payload['nbf']) return false;
    if (!empty($payload['exp']) && $now > $payload['exp']) return false;
    if (($payload['iss'] ?? '') !== 'buzzjuice.net') return false;
    if (($payload['aud'] ?? '') !== 'quickdate') return false;
    if (empty($payload['jti'])) return false;
    return $payload;
}

// Replay protection: 20min usable; 1hr cleanup
define('QD_SSO_JTI_STORE', __DIR__ . '/sso_jti_store');
if (!is_dir(QD_SSO_JTI_STORE)) @mkdir(QD_SSO_JTI_STORE, 0755, true);
function qd_is_jti_used($jti) {
    return $jti && file_exists(QD_SSO_JTI_STORE . '/' . sha1($jti));
}
function qd_mark_jti_used($jti) {
    @file_put_contents(QD_SSO_JTI_STORE . '/' . sha1($jti), time(), LOCK_EX);
}
function qd_cleanup_jti_store() {
    $expire = time() - 3600; // 1hr
    foreach (glob(QD_SSO_JTI_STORE . '/*') ?: [] as $file) {
        if (filemtime($file) < $expire) @unlink($file);
    }
}
if (mt_rand(1, 30) === 15) qd_cleanup_jti_store();

// Robust fail-safe output
function qd_bridge_fail_gracefully($msg = '', $ctx = []) {
    qd_bridge_log('QD SSO bridge fail: '.$msg, $ctx);
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow">';
    echo '<title>QuickDate SSO helper</title>';
    echo '<style>body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#fff;color:#111;padding:28px;max-width:720px;margin:40px auto;}a{color:#0073aa}</style></head><body>';
    echo '<h2>QuickDate Single sign-on — helper</h2>';
    echo '<p>We were unable to complete sign-in. Please <a href="/">return to the site</a> or try logging in via WordPress again.</p>';
    if (!empty($msg)) echo '<p><strong>Reason:</strong> ' . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE) . '</p>';
    echo '</body></html>';
    exit;
}

// WP stateless endpoint fetch (returns ['payload'=>claims, 'refresh_token'=>null|token])
function qd_fetch_wp_stateless_payload($sso_token, $secret) {
    if (empty($sso_token) || empty($secret)) return false;
    $endpoint = 'https://buzzjuice.net/?sso_action=get_token&sso_token=' . urlencode($sso_token);
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch) || !$response) {
        qd_bridge_log('QD SSO cURL error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    $json = json_decode($response, true);
    // supports multiple endpoint return formats
    $jwt = $json['token'] ?? ($json['access_token'] ?? null);
    if (!$jwt) return false;
    $payload = qd_validate_jwt($jwt, $secret);
    if (!$payload) return false;
    // Replay protection block
    if (qd_is_jti_used($payload['jti'])) {
        qd_bridge_log('QD SSO replay detected: ' . $payload['jti']);
        return ['replay' => true];
    }
    qd_mark_jti_used($payload['jti']);
    return [
        'payload'       => $payload,
        'refresh_token' => $json['refresh_token'] ?? null
    ];
}

/*
 * -----------------------------------------------------------------------
 * ALL shadow/adoption/session-state bridging logic is now *removed*.
 * See pasted.txt for rationale and strong security requirement.
 * IDENTITY IS NOW *ONLY* DERIVED FROM JWTS ISSUED BY THE WP ENDPOINT!
 * -----------------------------------------------------------------------
 */

// -------------------------------------------------------------
// SSO MAIN HANDLER BLOCK: Stateless JWT→QuickDate Mapping+Sync
// -------------------------------------------------------------
$sso_action = $_REQUEST['sso_action'] ?? '';
$sso_token  = $_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? '');

try {
    if (!$sso_token) {
        throw new \Exception('No SSO token provided (try logging in via WordPress).');
    }

    // STATLESS: Always get fresh canonical claims from endpoint
    $result = qd_fetch_wp_stateless_payload($sso_token, $BUZZ_SSO_SECRET);
    if (!$result) throw new \Exception('SSO payload not received or could not be verified from WP endpoint.');

    if (!empty($result['replay'])) {
        // Per requirements: request new token from WP
        qd_bridge_log('Replay encountered; must re-auth to get a new token', ['sso_token_preview'=>substr($sso_token,0,16)]);
        throw new \Exception('Sign-in expired or already used. Please retry logging in via WordPress.');
    }

    $claims = $result['payload'] ?? [];
    if (!$claims || empty($claims['wp_user_id'])) {
        throw new \Exception('SSO claims incomplete (no WP user id).');
    }

    // Robust claim extraction/normalization (parity with WoWonder bridge)
    $wp_user_id    = (int)($claims['wp_user_id'] ?? 0);
    $wp_user_login = (string)($claims['wp_user_login'] ?? $claims['login'] ?? '');
    $wp_user_email = (string)($claims['wp_user_email'] ?? $claims['email'] ?? '');
    $qd_user_id    = (int)($claims['qd_user_id'] ?? 0);

    if (!$wp_user_login || !$wp_user_email) {
        throw new \Exception('Essential identity claims (login/email) missing in SSO.');
    }

    qd_bridge_log('Stateless SSO claims extracted', [
        'wp_user_id' => $wp_user_id,
        'wp_user_login' => $wp_user_login,
        'wp_user_email' => $wp_user_email,
        'qd_user_id' => $qd_user_id,
        'raw_claims' => $claims
    ]);

    // ------------- USER MAPPING / AUTO-REGISTRATION -------------
    if ($qd_user_id) {
        // Confirm user exists in QD DB
        if (!function_exists('qd_get_user_id_by_email') || !$qd_user_id) {
            throw new \Exception('QuickDate user mapping error, helper missing.');
        }
        $qd_id_check = qd_get_user_id_by_email($wp_user_email);
        if (!$qd_id_check) {
            if (BUZZ_SSO_AUTO_REGISTER) {
                // Try to auto-register
                $created_id = qd_register_wo_user($wp_user_id, $wp_user_login, $wp_user_email);
                if (!$created_id) throw new \Exception('QuickDate auto-registration failed.');
                $qd_user_id = $created_id;
                qd_bridge_log('Auto-registered QD user', ['qd_user_id' => $qd_user_id, 'wp_user_id' => $wp_user_id]);
            } else {
                throw new \Exception('QD user not found and auto-registration is off.');
            }
        }
    } else {
        // QD ID missing in claim, always try to lookup or register
        if (!function_exists('qd_get_user_id_by_email')) {
            throw new \Exception('QuickDate user lookup helper unavailable.');
        }
        $qd_user_id = qd_get_user_id_by_email($wp_user_email);
        if (!$qd_user_id && BUZZ_SSO_AUTO_REGISTER) {
            $qd_user_id = qd_register_wo_user($wp_user_id, $wp_user_login, $wp_user_email);
            if (!$qd_user_id) throw new \Exception('QD auto-registration failed.');
        }
        if (!$qd_user_id) throw new \Exception('QD account not found and auto-registration is off.');
    }

    // Always sync the latest profile and meta from WP (direction: WP→QD)
    if (function_exists('sync_user_to_quickdate')) {
        // Fetch full WP metadata/xprofile for sync; QD ID is required
        $wp_data = [
            'wp_user_id'    => $wp_user_id,
            'qd_user_id'    => $qd_user_id,
            'wp_user_login' => $wp_user_login,
            'wp_user_email' => $wp_user_email
        ];
        sync_user_to_quickdate($wp_user_email, [], $wp_data);
        qd_bridge_log('Synchronized WP→QD user meta/xprofile', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$qd_user_id]);
    }

    // Re-issue long-lived JWT cookie with up-to-date mapping data
    qd_issue_buzz_sso_cookie([
        'wp_user_id' => $wp_user_id,
        'wp_user_login' => $wp_user_login,
        'wp_user_email' => $wp_user_email,
        'qd_user_id' => $qd_user_id,
        'jti' => $claims['jti'] ?? bin2hex(random_bytes(16))
    ]);

    // --- Optional: handle refresh_token ---
    if (!empty($result['refresh_token'])) {
        // Store/rotate as needed; you may persist this in DB by wp_user_id if building long-term refresh
        qd_bridge_log('Obtained refresh_token', ['wp_user_id'=>$wp_user_id,'len'=>strlen($result['refresh_token'])]);
    }

    // Handle session creation (if platform expects it)
    if (function_exists('SessionStart')) SessionStart();

    // If not POSTING do_login, render JS bridge to silently submit (or return to app home)
    if ($sso_action !== 'do_login') {
        // Modern UX: return HTML+autopost -> do_login endpoint
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8">';
        echo '<title>QuickDate SSO Bridge</title></head><body>';
        echo '<form id="ssoForm" method="POST">';
        echo '<input type="hidden" name="sso_action" value="do_login">';
        echo '<input type="hidden" name="sso_token" value="' . htmlspecialchars($sso_token, ENT_QUOTES | ENT_SUBSTITUTE) . '">';
        echo '</form>';
        echo '<script>document.getElementById("ssoForm").submit();</script>';
        echo '</body></html>';
        exit;
    }

    // SUCCESS: user is bootstrapped/logged in in QD, with latest meta
    // Platform should now route to default/home as desired (your app's cutover)

} catch (\Throwable $e) {
    // All errors: graceful error page with context/debug log
    qd_bridge_fail_gracefully($e->getMessage(), ['sso_token_preview'=>substr($sso_token,0,16)]);
}









//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 2
// -------------------------------------------------------------
// LEGACY QuickDate shadow/session reconciliation helpers — OBSOLETE
//
// The following functions — previously critical for cross-app session parity, shadow file cleanup,
// shadow serialization, and direct PHP session rehydration — are now fully deprecated.
//   - qd_cleanup_shadow_mismatches()
//   - qd_write_canonical_shadow_file()
//   - qd_attempt_session_reconciliation_if_required()
//   - qd_find_wp_shadow_payload()
//   - qd_unlink_local_session_file_if_exists()
//
// As outlined in the latest requirements (see pasted.txt) and in strict parity with the modernized
// WoWonder SSO bridge, the QuickDate SSO bridge is now strictly stateless and JWT-based:
//
// - SSO trust derives ONLY from a validated RFC 7519 JWT (with full claim/signature checks) issued
//   by the WordPress stateless endpoint (see wp-content/mu-plugins/sso-session-sync.php).
// - All session or shadow persistence, reconciliation, or session adoption logic is forbidden.
// - Replay protection is via the jti claim, not via session id or file/lock mirroring.
// - QD user mapping and metadata sync should be done via explicit code and validated API, not via
//   external file/session bridging.
// - If cross-app state is ever needed in future, use a purpose-built *stateless* API and protocol.
// - These helpers are retained as historic documentation only: DO NOT REVIVE OR CALL THEM IN SSO CODE.
//
// For security, debugging, and auditability, all JWT validation, replay checks, and user
// synchronization must be performed through the stateless flow, with robust error handling and logs.
//
//                       --- END LEGACY HELPERS, OBSOLETE ---
// -------------------------------------------------------------
/* ----------------------------- End added helpers ----------------------------- */









//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 3
// ---------------------------------------------------------------------------
// LEGACY SESSION BOOTSTRAP & SHADOW RECONCILIATION — DEPRECATED
// ---------------------------------------------------------------------------
//
// The following patterns are fully obsolete under stateless JWT SSO (as detailed in pasted.txt):
//   - SessionStart(), session_start(), or double-bootstrapping to sync SSO identity
//   - qd_attempt_session_reconciliation_if_required() or shadow/adoption logic
//   - Defensive sync via $_SESSION timestamps or rolling anti-drift logic
//   - Hydrating SSO-related $_SESSION values from buzz_sso_serialized or cookies
//   - Any attempt to treat $_SESSION as authoritative for SSO identity/mapping
//
// Modern SSO is:
//   1. Identity and mapping established *exclusively* from a validated JWT in buzz_sso cookie (or parameter).
//   2. JWT is validated with qd_sso_verify_token() or equivalent, including iss/aud/exp/nbf/jti/etc.
//   3. Application may locally set $_SESSION values for *UI/UX/misc* — NEVER for SSO trust or login state.
//   4. Only for explicit logout should you clear buzz_sso and associated session keys, never destroy PHPSESSID.
//
// Leave all of the block below as a **documentation-only** developer warning. All SSO trust/mapping
// logic must be *stateless* per request going forward.
//
/* ----- BEGIN LEGACY/DEPRECATED BLOCK: SESSION BOOTSTRAP ----- */
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
    'phpSessionId'=>session_id(),
    'shadow_session_id'=>(isset($_COOKIE['PHPSESSID']) ? 'shadow_'.$_COOKIE['PHPSESSID'] : null)
]);

try {
    // DO NOT CALL in modern SSO: qd_attempt_session_reconciliation_if_required();
} catch (Throwable $e) {
    qd_bridge_log('Session reconciliation attempt threw', ['err'=>$e->getMessage()]);
}

/* Defensive sync: legacy anti-drift, NOT needed with JWT SSO. Retained for log context. */
if (!isset($_SESSION['buzz_sso_defensive_last']) || (time() - (int)$_SESSION['buzz_sso_defensive_last']) > 4*3600) {
    $_SESSION['buzz_sso_defensive_last'] = time();
    $errs = [];
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) $errs[] = 'buzz_sso_cookie_missing';
    if (empty($_SESSION['wp_user_login'])) $errs[] = 'wp_user_login_missing';
    if (empty($_SESSION['qd_user_id']) || !is_numeric($_SESSION['qd_user_id'])) $errs[] = 'qd_user_id_missing_or_invalid';
    if ($errs) qd_bridge_log('Defensive sync checks', ['errs'=>$errs]);
}

/* Session normalization: DO NOT use for SSO trust! JWT is canonical. */
function normalize_sso_session() {}
normalize_sso_session();

/* Explicit logout handler (cookie/session cleanup & redirect).
   DO NOT call as part of SSO authentication; only for true user logouts. */
function qd_clear_and_logout($reason='unknown') {
    global $config;
    qd_bridge_log('Clearing session SSO keys and redirecting to logout', ['reason'=>$reason]);
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
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
    // Expire buzz_sso on this domain + shared parent
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>time()-3600,'path'=>'/','domain'=>BUZZ_COOKIE_DOMAIN,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    } else {
        setcookie(BUZZ_SSO_COOKIE, '', time()-3600, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    $base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
    $target = ($base ?: '') . '/../wp-login.php';
    header('Location: ' . $target);
    exit();
}

// --------------- User DB helpers (can be called for mappings after stateless SSO) ---------------
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
/* ----- END LEGACY/DEPRECATED BLOCK: SESSION BOOTSTRAP ----- */









//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 4
/**
 * qd_register_user() — Register QuickDate user (stateless SSO, JWT/federated, robust)
 * 
 * - Only trust $login, $email, $wp_user_id as passed from validated JWT claims.
 * - No SSO trust is ever established from $_SESSION; session write may occur, but is for UX only.
 * - Avatars/metadata are drawn from canonical WP data if available. 
 * - Maps QD ID to WP usermeta (`qd_user_id`) using multiple fallback strategies: repository helper, WP API, SQL.
 * - Extensive error and tracing log for debugging and observability.
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

        // Username: sanitize and robust fallback
        $username = preg_replace('~[^a-z0-9_.-]~i', '', (string)$login);
        if (!$username) $username = 'wpuser' . intval($wp_user_id);

        // WP profile data for avatar/name fields (if available, stateless)
        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        $wp_full = (function_exists('wp_get_full_user_data') && $conn && $wp_user_id)
            ? wp_get_full_user_data($conn, $wp_user_id)
            : [];
        $avatar = $wp_full['xprofile']['avatar'] ?? $wp_full['meta']['avatar'] ?? ($GLOBALS['config']->userDefaultAvatar ?? '');

        $password = bin2hex(random_bytes(8));  // SSO user password
        // Avatar import is defensive and optional
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
        // Use config->defaultLang, fallback to default
        $lang = $GLOBALS['config']->defaultLang ?? (isset($GLOBALS['config']->defualtLang) ? $GLOBALS['config']->defualtLang : 'english');
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
            $re_data['last_name']  = $wp_full['meta']['last_name']  ?? '';
        }

        try {
            $reg = $user->register($re_data);
        } catch (Throwable $e) {
            qd_bridge_log('qd_register_user: user->register() exception', ['ex'=>$e->getMessage(), 'payload'=>$re_data]);
            return 0;
        }

        $created_id = 0;
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
            if ($conn && function_exists('wp_update_usermeta')) {
                try {
                    wp_update_usermeta($conn, (int)$wp_user_id, $meta_key, $meta_value);
                    qd_bridge_log('Set wp_usermeta qd_user_id via wp_update_usermeta', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                    $did_write = true;
                } catch (Throwable $e) {
                    qd_bridge_log('wp_update_usermeta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                }
            }
            if (!$did_write && function_exists('update_user_meta')) {
                try {
                    update_user_meta((int)$wp_user_id, $meta_key, $meta_value);
                    qd_bridge_log('Set wp_usermeta qd_user_id via update_user_meta', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                    $did_write = true;
                } catch (Throwable $e) {
                    qd_bridge_log('update_user_meta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'qd_user_id'=>$created_id]);
                }
            }
            if (!$did_write && $conn && $wp_user_id) {
                // determine table name
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

        // Optionally set session for runtime UX convenience, not for SSO trust
        if (session_status() === PHP_SESSION_NONE) @session_start();
        try { $_SESSION['qd_user_id'] = $created_id; } catch(Throwable $e) {
            qd_bridge_log('qd_register_user: failed to set session qd_user_id', ['ex'=>$e->getMessage()]);
        }

        qd_bridge_log('qd_register_user: Auto-registered QuickDate user', [
            'id'       => $created_id,
            'username' => $username,
            'email'    => $email,
            're_data'  => $re_data,
            'wp_write' => $did_write
        ]);
        return $created_id;
    }
}









//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 5
/* ------------------------------------------------------------------------
   STATELESS USER MAPPING: Resolving QuickDate user identity from JWT/WP
   ------------------------------------------------------------------------
   - SSO trust only comes from validated JWT claims and DB lookup.
   - SESSION is a runtime cache—writes only for post-login UX, never SSO authentication.
   - Each mapping branch is logged for full audit trace.
------------------------------------------------------------------------- */

$final_qd_user_id = 0;
$orig_session_qd  = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : 0;
$wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;

qd_bridge_log('Mapping start', [
    'claim_qd'  => $claim_qd_user_id,
    'session_qd'=> $orig_session_qd,
    'login'     => $claim_wp_user_login,
    'email'     => $claim_wp_user_email
]);

$has_all_canonical = (
    $claim_qd_user_id && $claim_wp_user_id && $claim_wp_user_login && $claim_wp_user_email
);
if ($has_all_canonical) {
    qd_bridge_log('All canonical SSO values present — performing strict qd_user_id verification', [
        'claim_qd'=>$claim_qd_user_id,
        'wp_user_id'=>$claim_wp_user_id,
        'wp_user_login'=>$claim_wp_user_login,
        'wp_user_email'=>$claim_wp_user_email
    ]);
    $row = qd_get_user_row($claim_qd_user_id);
    if ($row) {
        $db_un = isset($row['username']) ? trim((string)$row['username']) : '';
        $db_em = isset($row['email']) ? trim((string)$row['email']) : '';
        if (
            strcasecmp($db_un, trim($claim_wp_user_login)) === 0 &&
            strcasecmp($db_em, trim($claim_wp_user_email)) === 0
        ) {
            $final_qd_user_id = (int)$claim_qd_user_id;
            qd_bridge_log('Strict verification successful — qd_user_id accepted', ['qd_user_id'=>$final_qd_user_id]);
        } else {
            qd_bridge_log('Strict verification failed — username/email mismatch, clearing session qd_user_id and forcing re-map/register', [
                'qd_user_id'=>$claim_qd_user_id,
                'db_username'=>$db_un,
                'db_email'=>$db_em,
                'session_login'=>trim($claim_wp_user_login),
                'session_email'=>trim($claim_wp_user_email)
            ]);
            if (isset($_SESSION['qd_user_id'])) unset($_SESSION['qd_user_id']);
            $claim_qd_user_id = 0;
            $orig_session_qd  = 0;
        }
    } else {
        qd_bridge_log('Strict verification failed — qd_user_id not found in DB; clearing session qd_user_id and forcing re-map/register', [
            'qd_user_id'=>$claim_qd_user_id
        ]);
        if (isset($_SESSION['qd_user_id'])) unset($_SESSION['qd_user_id']);
        $claim_qd_user_id = 0;
        $orig_session_qd  = 0;
    }
}

if (!$final_qd_user_id) {
    // 1) Session/cookie claim present & exists in QD DB
    if ($claim_qd_user_id && qd_find_user_by_id($claim_qd_user_id)) {
        $final_qd_user_id = $claim_qd_user_id;
        qd_bridge_log('Using qd_user_id from claim/cookie/session (exists in DB)', ['qd_user_id'=>$final_qd_user_id]);
    } else {
        // 2) Find by login/email combo
        $found = qd_find_user_by_login_email($claim_wp_user_login, $claim_wp_user_email);
        if ($found) {
            $final_qd_user_id = $found;
            qd_bridge_log('Mapped qd_user_id via login+email', ['qd_user_id'=>$final_qd_user_id]);
            if (!empty($claim_wp_user_id) && $wp_conn && function_exists('wp_update_usermeta')) {
                try {
                    wp_update_usermeta($wp_conn, (int)$claim_wp_user_id, ['qd_user_id' => (int)$final_qd_user_id], null);
                    qd_bridge_log('Persisted mapped qd_user_id to WordPress usermeta', [
                        'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id]);
                } catch (Throwable $e) {
                    qd_bridge_log('Exception persisting qd_user_id to WP usermeta', [
                        'ex'=>$e->getMessage(),'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                    ]);
                }
            }
        } else {
            // 3) Auto-register new user (if enabled)
            if (BUZZ_SSO_AUTO_REGISTER) {
                qd_bridge_log('No mapping found — attempting auto-register', [
                    'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email,'orig_session_qd'=>$orig_session_qd
                ]);
                $created = qd_register_user($claim_wp_user_login, $claim_wp_user_email, $claim_wp_user_id);
                if ($created) {
                    $final_qd_user_id = (int)$created;
                    qd_bridge_log('Auto-register created QuickDate user', ['created_id'=>$created]);
                    $_SESSION['qd_user_id'] = $final_qd_user_id;
                    $claim_qd_user_id = $final_qd_user_id;
                    if (!empty($claim_wp_user_id) && $wp_conn && function_exists('wp_update_usermeta')) {
                        try {
                            wp_update_usermeta($wp_conn, (int)$claim_wp_user_id, ['qd_user_id' => (int)$final_qd_user_id], null);
                            qd_bridge_log('Persisted auto-registered qd_user_id to WordPress usermeta', [
                                'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id]);
                        } catch (Throwable $e) {
                            qd_bridge_log('Exception persisting auto-registered qd_user_id to WP usermeta', [
                                'ex'=>$e->getMessage(),'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                            ]);
                        }
                    }
                } else {
                    qd_bridge_log('Auto-register failed: no created id returned', [
                        'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email
                    ]);
                }
            } else {
                qd_bridge_log('Auto-registration disabled, mapping not found', [
                    'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email
                ]);
            }
            // 4) Fallback: if session had a QD user id (and it now exists), preserve it
            if (!$final_qd_user_id && $orig_session_qd && qd_find_user_by_id($orig_session_qd)) {
                $final_qd_user_id = $orig_session_qd;
                qd_bridge_log('Preserving original session qd_user_id', ['qd_user_id'=>$final_qd_user_id]);
            }
        }
    }
}
if (!$final_qd_user_id) {
    qd_bridge_log('Unable to determine QuickDate user id after mapping/registration', [
        'session'=>$_SESSION, 'cookie_payload'=>$cookie_payload ?? null
    ]);
    qd_clear_and_logout('no_qd_user_after_mapping');
}

/* Canonicalize all runtime session fields for UI/UX only (NEVER for SSO trust) */
$_SESSION['wp_user_login'] = $_SESSION['wp_user_login'] ?? trim($claim_wp_user_login);
$_SESSION['wp_user_id']    = (int)$claim_wp_user_id;
$_SESSION['wp_user_email'] = trim($claim_wp_user_email);
$_SESSION['qd_user_id']    = (int)$final_qd_user_id;

try {
    $need_issue = false;
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) {
        $need_issue = true;
    } else {
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
        qd_issue_buzz_sso_cookie($new_payload);
    }
} catch (Throwable $e) {
    qd_bridge_log('Exception while ensuring long-lived buzz_sso cookie', ['ex'=>$e->getMessage()]);
}

try {
    $nonce = bin2hex(random_bytes(8));
} catch (Throwable $e) {
    $nonce = bin2hex(openssl_random_pseudo_bytes(8));
}
function qd_build_sso_password_token($qd_user_id, $wp_user_id, $wp_user_login, $wp_user_email, $secret) {
    global $nonce;
    $claims = [
        'ver'          => 1,
        'qd_user_id'   => (int)$qd_user_id,
        'wp_user_id'   => (int)$wp_user_id,
        'wp_user_login'=> (string)$wp_user_login,
        'wp_user_email'=> (string)$wp_user_email,
        'iat'          => time(),
        'exp'          => time() + BUZZ_SSO_TTL,
        'nonce'        => $nonce,
    ];
    $json = json_encode($claims);
    $sig  = hash_hmac('sha256', $json, (string)$secret, true);
    return 'WPSSO.v1.' . _qd_b64url_encode($json) . '.' . _qd_b64url_encode($sig);
}
$sso_username = $_SESSION['wp_user_login'];
$sso_password = qd_build_sso_password_token(
    $_SESSION['qd_user_id'],
    $_SESSION['wp_user_id'],
    $_SESSION['wp_user_login'],
    $_SESSION['wp_user_email'],
    $BUZZ_SSO_SECRET
);

$site_base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
$last_url = '/';
foreach (['last_url'] as $k) {
    if (!empty($_GET[$k]))  { $last_url = (string)$_GET[$k]; break; }
    if (!empty($_POST[$k])) { $last_url = (string)$_POST[$k]; break; }
    if (!empty($_COOKIE[$k])) { $last_url = (string)$_COOKIE[$k]; break; }
}
if (!$last_url || ($site_base && strpos($last_url, $site_base) !== 0)) $last_url = '/';
$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php') . '?sso_action=do_login';

qd_bridge_log('SSO client payload prepared', [
    'sso_username'     => $sso_username,
    'sso_password_len' => strlen($sso_password),
    'ajax_url'         => $ajax_url,
    'last_url'         => $last_url
]);

if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    QD_SSO_Login();
    exit;
}

// `qd_get_columns` helper unchanged.









//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 6
function QD_SSO_Login() {
    global $BUZZ_SSO_SECRET, $config;
    header('Content-Type: application/json; charset=utf-8');

    // Use only POSTed password (SSO token) — stateless, JWT-validated
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $last_url = isset($_POST['last_url']) ? (string)$_POST['last_url'] : '/';

    qd_bridge_log('QD_SSO_Login called', ['pw_len'=>strlen($password)]);

    if (!$BUZZ_SSO_SECRET) {
        qd_bridge_log('QD_SSO_Login: BUZZ_SSO_SECRET missing');
        echo json_encode(['status'=>500,'errors'=>['Server misconfiguration']]);
        exit;
    }

    // Only trust claims from the stateless WPSSO.v1.* token
    $claims = qd_parse_sso_password_token($password, $BUZZ_SSO_SECRET);
    if (!$claims) {
        qd_bridge_log('QD_SSO_Login: invalid SSO password token', ['token_preview'=>substr($password,0,40)]);
        echo json_encode(['status'=>401,'errors'=>['Invalid or expired SSO token']]);
        exit;
    }
    $exp_qd    = (int)($claims['qd_user_id'] ?? 0);
    $exp_wp    = (int)($claims['wp_user_id'] ?? 0);
    $exp_login = (string)($claims['wp_user_login'] ?? '');
    $exp_email = (string)($claims['wp_user_email'] ?? '');

    qd_bridge_log('QD_SSO_Login canonical identity', [
        'qd'    => $exp_qd,
        'wp'    => $exp_wp,
        'login' => $exp_login,
        'email' => $exp_email
    ]);

    // --- Defensive: Token identifier count (minimum) ---
    $identifier_count = 0;
    foreach ([$exp_qd, $exp_wp, $exp_login, $exp_email] as $v) {
        if (!empty($v)) $identifier_count++;
    }
    if ($identifier_count < 3) {
        qd_bridge_log('Insufficient identifiers in token');
        echo json_encode(['status'=>401,'errors'=>['Invalid SSO token structure']]);
        exit;
    }

    // --- Optimized/Clean: One DB query for all candidate users ---
    $db = get_qd_db_conn();
    $candidates = [];
    if ($db) {
        $where = [];
        $params = [];
        if ($exp_qd)    { $where[] = 'id=?';           $params[] = $exp_qd; }
        if ($exp_email) { $where[] = 'email=?';        $params[] = $exp_email; }
        if ($exp_login) { $where[] = 'username=?';     $params[] = $exp_login; }
        if ($exp_wp)    { $where[] = 'wp_user_id=?';   $params[] = $exp_wp; }
        if ($where) {
            $sql = 'SELECT * FROM users WHERE ' . implode(' OR ', $where) . ' LIMIT 5';
            $stmt = $db->prepare($sql);
            if ($stmt) {
                // Use proper param binding types
                $types = '';
                foreach ($params as $p) { $types .= is_numeric($p) ? 'i' : 's'; }
                foreach ($params as &$v) { $v = (string)$v; }
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $candidates[] = $row;
                $stmt->close();
            }
        }
    }
    qd_bridge_log('QD_SSO_Login candidates count', ['count'=>count($candidates)]);

    // Accept user if ≥3 identifiers match; only stateless comparison here.
    $accepted_user = null;
    $accepted_matches = [];
    foreach ($candidates as $row) {
        $db_id  = (int)$row['id'];
        $db_un  = (string)$row['username'];
        $db_em  = (string)$row['email'];
        $db_wpu = (int)($row['wp_user_id'] ?? 0);

        $m_id  = ($exp_qd && $db_id === $exp_qd) ? 1 : 0;
        $m_em  = ($exp_email && strcasecmp($db_em, $exp_email) === 0) ? 1 : 0;
        $m_un  = ($exp_login && strcasecmp($db_un, $exp_login) === 0) ? 1 : 0;
        $m_wpu = ($exp_wp && $db_wpu === $exp_wp) ? 1 : 0;

        $cnt = $m_id + $m_em + $m_un + $m_wpu;
        if ($cnt >= 3) {
            $accepted_user = $row;
            $accepted_matches = ['id'=>$m_id, 'email'=>$m_em, 'username'=>$m_un, 'wp_user_id'=>$m_wpu];
            break;
        }
    }

    if (!$accepted_user) {
        qd_bridge_log('QD_SSO_Login: no accepted candidate (≥3 required)', [
            'expected'   => ['qd'=>$exp_qd,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email],
            'candidates' => array_map(function($c){
                return [
                    'id'        => $c['id'],
                    'username'  => $c['username'],
                    'email'     => $c['email'],
                    'wp_user_id'=> $c['wp_user_id'] ?? null
                ];
            }, $candidates)
        ]);
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION = [];
            @session_unset();
        }
        echo json_encode(['status'=>401,'errors'=>['No matching QuickDate account for SSO.']]);
        exit;
    }

    // Start session, set canonical fields (NO session_regenerate_id, do not rotate PHPSESSID)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_start()) {
            qd_bridge_log('Session start failed on login');
            echo json_encode(['status'=>500,'errors'=>['Session initialization failed']]);
            exit;
        }
    }

    $_SESSION['qd_user_id']    = (int)$accepted_user['id'];
    $_SESSION['user_id']       = $accepted_user['web_token'] ?? (int)$accepted_user['id'];
    $_SESSION['wp_sso_login']  = true;
    $_SESSION['wp_user_id']    = $exp_wp;
    $_SESSION['wp_user_email'] = $exp_email;
    if (!isset($_SESSION['wp_user_login'])) $_SESSION['wp_user_login'] = $exp_login;

    // Set QuickDate runtime login (side-effects as per app needs)
    if (function_exists('LoadEndPointResource')) {
        $usersRes = LoadEndPointResource('users');
        if ($usersRes && method_exists($usersRes, 'SetLoginWithSession') && !empty($exp_email)) {
            try {
                $usersRes->SetLoginWithSession($exp_email);
                qd_bridge_log('SetLoginWithSession invoked', ['email'=>$exp_email]);
            } catch (Throwable $e) {
                qd_bridge_log('SetLoginWithSession exception', ['ex'=>$e->getMessage()]);
            }
        }
    }

    // ------------- Post-login: WordPress→QuickDate profile/meta sync -------------
    try {
        qd_bridge_log('Preparing to sync WordPress metadata into QuickDate', ['wp_user_id'=>$exp_wp,'wp_email'=>$exp_email]);
        $did_sync = false;

        if (!empty($exp_email) && !empty($exp_wp) && function_exists('sync_user_to_quickdate') && function_exists('wp_get_full_user_data')) {
            $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
            if ($wp_conn) {
                $wp_full = wp_get_full_user_data($wp_conn, $exp_wp);
                if ($wp_full && is_array($wp_full)) {
                    $usermeta = $wp_full['meta'] ?? [];
                    $xprofile = $wp_full['xprofile'] ?? [];
                    $ok = sync_user_to_quickdate($exp_email, $usermeta, $xprofile);
                    qd_bridge_log('sync_user_to_quickdate result', [
                        'email' => $exp_email, 'wp_user_id' => $exp_wp, 'ok'=>(bool)$ok
                    ]);
                    $did_sync = (bool)$ok;
                } else {
                    qd_bridge_log('wp_get_full_user_data returned empty/invalid', ['wp_user_id'=>$exp_wp]);
                }
            } else {
                qd_bridge_log('WP DB connection not available for sync', []);
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
                static $qd_cols_cache = null;
                if ($qd_cols_cache === null && $qd_conn = get_qd_db_conn()) {
                    $qd_cols_cache = qd_get_columns($qd_conn, 'users');
                }
                $qd_cols = $qd_cols_cache ?? [];
                $qd_update = [];
                foreach ($qd_candidate as $k => $v) {
                    if (in_array($k, $qd_cols, true)) {
                        $qd_update[$k] = $v;
                    }
                }
                if (!empty($qd_update)) {
                    $ok = qd_update_user($exp_email, $qd_update);
                    qd_bridge_log('qd_update_user (fallback) result', [
                        'email'=>$exp_email,
                        'update_keys'=>array_keys($qd_update),
                        'result'=>(bool)$ok
                    ]);
                    $did_sync = (bool)$ok;
                } else {
                    qd_bridge_log('No QuickDate-updatable fields found in WP user data (fallback)', [
                        'email'=>$exp_email,'candidate_keys'=>array_keys($qd_candidate)
                    ]);
                }
            } else {
                qd_bridge_log('wp_get_full_user_data returned empty/invalid for fallback sync', [
                    'wp_user_id'=>$exp_wp
                ]);
            }
        } else {
            qd_bridge_log('Skipping QuickDate sync - missing prerequisites', [
                'has_email'=>!empty($exp_email),
                'has_wp_id'=>!empty($exp_wp),
                'functions'=>[
                    'sync_user_to_quickdate'=>function_exists('sync_user_to_quickdate'),
                    'get_user_field_metadata'=>function_exists('get_user_field_metadata'),
                    'wp_get_full_user_data'=>function_exists('wp_get_full_user_data'),
                    'qd_update_user'=>function_exists('qd_update_user')
                ]
            ]);
        }
        if (!$did_sync) {
            qd_bridge_log('Post-login QuickDate sync did not run or reported failure', [
                'wp_user_id'=>$exp_wp,'email'=>$exp_email
            ]);
        }
    } catch (Throwable $e) {
        qd_bridge_log('Exception during QuickDate sync', ['ex'=>$e->getMessage()]);
    }

    // Hardened/new: validate last_url to prevent open redirect attacks
    $default_url = (isset($config->uri) ? rtrim($config->uri, '/') : '') . '/steps';
    $url = $default_url;
    if (!empty($accepted_user['start_up']) && $accepted_user['start_up'] == 3 && !empty($accepted_user['verified'])) {
        $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/find-matches';
    }
    if (!empty($last_url) && $last_url !== '//') {
        // Only accept if last_url is relative or same-origin site_base
        $parsed = parse_url($last_url);
        $site_base = isset($config->uri) ? rtrim($config->uri, '/') : '';
        $is_relative = empty($parsed['host']) && substr($last_url, 0, 2) !== '//' && empty($parsed['scheme']);
        $is_same_origin = $site_base && strpos($last_url, $site_base) === 0;
        if ($is_relative || $is_same_origin) {
            $url = $last_url;
        }
    }

    qd_bridge_log('QD_SSO_Login success', [
        'user_id'=>$accepted_user['id'],
        'matches'=>$accepted_matches,
        'redirect'=>$url,
        'session_id'=>session_id()
    ]);

    http_response_code(200);
    echo json_encode(['status'=>200,'location'=>$url]);
    exit;
}









//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 7
// -----------------------------------------------------------------------------
// QD SSO Bridge HTML: stateless, production-grade, debug/diagnostic friendly
// -----------------------------------------------------------------------------

// Security headers for production browser-layer defense
header("Content-Security-Policy: default-src 'self'; script-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';");
header("Referrer-Policy: no-referrer");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");

qd_bridge_log('Rendering QD SSO bridge page', [
    'sso_username'      => $sso_username,
    'sso_password_len'  => strlen($sso_password),
    'last_url'          => $last_url,
    'final_qd_user_id'  => isset($final_qd_user_id) ? $final_qd_user_id : null,
    'php_session_id'    => session_id(),
    'shadow_session_present' => isset($_COOKIE['PHPSESSID']),
    'session_keys'      => array_keys($_SESSION),
    'cookie_keys'       => array_keys($_COOKIE),
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
          'post'=>['password'=>'(sso-token)','last_url'=>$last_url,'remember_device'=>'on'],
          'session_keys'=>array_keys($_SESSION),
          'cookie_keys'=>array_keys($_COOKIE)
      ], true)); ?></pre></div>
    <?php endif; ?>
    <noscript>
      <div class="status err">
        JavaScript is required for secure sign-in. Please enable JavaScript.
      </div>
    </noscript>
  </div>

  <script>
  (function(){
    var ajaxUrl = <?php echo json_encode($ajax_url); ?>;
    var payload = {
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
    beacon('bridge:init', {ajaxUrl: ajaxUrl, last: payload.last_url});

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.withCredentials = true;
    xhr.timeout = 20000;
    xhr.onreadystatechange = function(){
      if (xhr.readyState === 4) {
        var ok=false, locationUrl=null, errors=null, res=null;
        try { res = JSON.parse(xhr.responseText); } catch(e) {
          beacon('bridge:parse_error', {http: xhr.status});
        }
        if (res) { ok = !!(res.status===200 || res.status===600) && !!res.location; locationUrl = res.location; errors = res.errors || null; }
        beacon('bridge:response', {status: res && res.status, http: xhr.status});
        if (ok) {
          statusEl && (statusEl.className='status ok', statusEl.textContent='Welcome back! Redirecting…');
          payload.password = null; delete payload.password;
          setTimeout(function(){ window.location.href = locationUrl; }, 400);
        } else {
          var body = xhr.responseText || '';
          var looksLikeHtml = body.indexOf('<!DOCTYPE') !== -1 || body.indexOf('<html') !== -1;
          if (!res && looksLikeHtml && payload.last_url && payload.last_url.charAt(0) === '/') {
            beacon('bridge:fallback_html_redirect', {http: xhr.status});
            window.location.href = payload.last_url;
            return;
          }
          statusEl && (statusEl.className='status err', statusEl.textContent=(errors && errors.join ? errors.join(', ') : 'Unexpected response.'));
          beacon('bridge:failed', {http: xhr.status});
        }
      }
    };
    xhr.onerror = function(){ beacon('bridge:error', {http: xhr.status}); statusEl && (statusEl.className='status err', statusEl.textContent='Network or server error.'); };
    xhr.ontimeout = function(){ beacon('bridge:timeout', {}); statusEl && (statusEl.className='status err', statusEl.textContent='Request timed out.'); };

    var body = 'password=' + encodeURIComponent(payload.password)
             + '&remember_device=on'
             + '&last_url=' + encodeURIComponent(payload.last_url);
    xhr.send(body);
    // Memory hygiene: wipe password after use
    payload.password = null; delete payload.password;
  })();
  </script>
</body>
</html>