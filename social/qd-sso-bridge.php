<?php
/**
 * qd-sso-bridge.php — BuzzJuice → QuickDate SSO Bridge (Stateless JWT Edition)
 * Implements robust, future-proof JWT SSO with replay protection, defensive logging,
 * refresh-token fallback, and clear separation from legacy PHP/session bridging.
 */

require_once __DIR__ . '/bootstrap.php';
//if (file_exists(__DIR__ . '/controllers/aj.php')) require_once __DIR__ . '/controllers/aj.php';
//if (file_exists(__DIR__ . '/requests/ajax/useractions.php')) require_once __DIR__ . '/requests/ajax/useractions.php';

if (file_exists(__DIR__ . '/../shared/wwqd_bridge.php')) require_once __DIR__ . '/../shared/wwqd_bridge.php';
require_once __DIR__ . '/../shared/sso_bridge_helpers.php';
if (!function_exists('bz_fetch_wp_stateless_payload')) {
    exit('SSO helper not loaded.');
}

// ======================================================
// START: CONFIGURATIONS + LOGGING + graceful failure
// ======================================================
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/qd_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET')
    ? BUZZ_SSO_SECRET
    : (getenv('BUZZ_SSO_SECRET') ?: null);

// Get SSO token from request or cookie
$sso_token = $_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? '');
$sso_action = $_REQUEST['sso_action'] ?? '';
// --- DEBUG: log the raw cookie payload ---
/* if (!empty($_COOKIE[BUZZ_SSO_COOKIE])) {
    error_log('[QuickDate SSSO DEBUG] BUZZ_SSO_COOKIE payload: ' . $_COOKIE[BUZZ_SSO_COOKIE]);
} else {
    error_log('[QuickDate SSO DEBUG] BUZZ_SSO_COOKIE not set.');
}
*/

// -------------------------------------------------------
// LOGGING, DEBUG, CLIENT DEBUG BEACON, LOOP PROTECTION + SESSION VISIBILITY
// -------------------------------------------------------
if (!function_exists('qd_bridge_log')) {

    function qd_bridge_log($msg, $ctx = []) {

        $ts = gmdate('Y-m-d H:i:s');

        $data = [
            'ts'              => $ts,
            'ip'              => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri'             => $_SERVER['REQUEST_URI'] ?? null,
            'ua'              => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'php_session_id'  => function_exists('session_id') ? session_id() : null,
            'session_name'    => function_exists('session_name') ? session_name() : null,
            'buzz_sso_len'    => isset($_COOKIE[BUZZ_SSO_COOKIE]) ? strlen($_COOKIE[BUZZ_SSO_COOKIE]) : 0,
        ];

        // Extended debug data (only if debug enabled)
        if (function_exists('bz_is_debug') && bz_is_debug()) {

            $data['cookies'] = $_COOKIE ?? [];
            $data['session'] = $_SESSION ?? [];

            $data['server'] = [
                'HTTP_HOST'   => $_SERVER['HTTP_HOST'] ?? null,
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
                'HTTPS'       => $_SERVER['HTTPS'] ?? null,
            ];

            $data['sess_cookie_params'] =
                function_exists('session_get_cookie_params')
                ? session_get_cookie_params()
                : null;
        }

        if (!empty($ctx)) {
            $data['ctx'] = $ctx;
        }

        $line = '[' . $ts . '] ' . $msg . ' | ' .
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
            PHP_EOL;

        @file_put_contents(
            BUZZ_SSO_BRIDGE_LOG,
            $line,
            FILE_APPEND | LOCK_EX
        );
    }
}

// -------------------------------------------------------
// SESSION: SSR/LOGIN ONLY (STATLESS SSO - JWT IS AUTHORITY)
// -------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $needs_session =
        !empty($_COOKIE[BUZZ_SSO_COOKIE]) ||
        (isset($_GET['sso_action']) && $_GET['sso_action'] === 'do_login') ||
        (isset($_POST['sso_action']) && $_POST['sso_action'] === 'do_login') ||
        isset($_GET['sso_debug']);

    if ($needs_session) {
        @ini_set('session.serialize_handler', 'php_serialize');
        @ini_set('session.cookie_samesite', 'Lax');
        @ini_set('session.cookie_secure', 1);
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.use_only_cookies', 1);
        @ini_set('session.use_strict_mode', 1);

        $sname = session_name();
        $sid = null;
        if (isset($_COOKIE[$sname]))
            $sid = preg_replace('/[^a-zA-Z0-9,-]/', '', (string)$_COOKIE[$sname]);
        elseif (!empty($_COOKIE['PHPSESSID']))
            $sid = preg_replace('/[^a-zA-Z0-9,-]/', '', (string)$_COOKIE['PHPSESSID']);
        if ($sid) {
            @session_id($sid);
            qd_bridge_log('Resuming PHP session from cookie (bridge fallback)');
        }
        session_start();
        qd_bridge_log('Session started by bridge (fallback)', ['session_id' => session_id()]);
        // CRITICAL: this session is *never* a trust anchor for SSO
    } else {
        qd_bridge_log('Session not started (bridge): benign request, no buzz_sso and not an SSO action');
    }
}

// -------------------------------------------------------
// BOOTSTRAP CHECKS — REQUIRE Wo CONFIG AND SQL
// -------------------------------------------------------
/*global $config, $sqlConnect;
$site_base = isset($config->uri) ? rtrim($config->uri, '/') : '';
if (empty($site_base) || empty($sqlConnect)) {
    qd_bridge_log('Bootstrap incomplete - missing $site_base or $sqlConnect');
    bz_debug_page('Bootstrap incomplete', ['$site_base' => $site_base ?? null, '$sqlConnect' => (bool)$sqlConnect]);
    header('Location: /');
    exit;
}
*/
// -------------------
// CLIENT DEBUG BEACON
// -------------------
if (!empty($_GET['sso_client_log']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    @file_put_contents(
        BUZZ_SSO_BRIDGE_LOG,
        '[' . gmdate('Y-m-d H:i:s') . '] CLIENT ' . substr($raw, 0, 2000) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    http_response_code(204);
    exit;
}

// -------------------
// LOOP PROTECTION
// -------------------
$loop_count = function_exists('bz_bridge_loop_count')
    ? bz_bridge_loop_count(true)
    : 0;

if ($loop_count > 5) {
    qd_bridge_log('Bridge loop suspected — forcing fallback', [
        'loop_count' => $loop_count
    ]);
    if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
    $forced_last_url_fallback = true;
} else {
    $forced_last_url_fallback = false;
}

// -----------------------------
// ***** Replay protection: JTI store (30 min) ***** TODO
// -----------------------------
define('QD_SSO_JTI_STORE', __DIR__ . '/sso_jti_store');
if (!is_dir(QD_SSO_JTI_STORE)) @mkdir(QD_SSO_JTI_STORE, 0755, true);
function qd_is_jti_used($jti) { return $jti && file_exists(QD_SSO_JTI_STORE . '/' . sha1($jti)); }
function qd_mark_jti_used($jti) { @file_put_contents(QD_SSO_JTI_STORE . '/' . sha1($jti), time(), LOCK_EX); }
function qd_cleanup_jti_store() {
    $expire = time() - 3600; // 1hr
    foreach (glob(QD_SSO_JTI_STORE . '/*') ?: [] as $file) if (filemtime($file) < $expire) @unlink($file);
}
if (mt_rand(1, 30) === 15) qd_cleanup_jti_store();

//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 2
// -------------------------------------------------------------
// LEGACY QuickDate SSO / Session Helpers — OBSOLETE
//
// These functions and patterns are fully deprecated and retained only for historical documentation:
//   - qd_cleanup_shadow_mismatches()
//   - qd_write_canonical_shadow_file()
//   - qd_attempt_session_reconciliation_if_required()
//   - qd_find_wp_shadow_payload()
//   - qd_unlink_local_session_file_if_exists()
//   - SessionStart(), session_start(), or any double-bootstrapping
//   - Defensive $_SESSION sync / rolling anti-drift logic
//   - Hydrating $_SESSION from buzz_sso_serialized or cookies
//   - Treating $_SESSION as authoritative for SSO identity
//
/* ----------------------------- End added helpers ----------------------------- */

//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 3
//
// Modern QuickDate SSO is strictly stateless and JWT-based:
//   - Identity and mapping come *only* from a validated RFC 7519 JWT (iss/aud/exp/nbf/jti checks).
//   - Replay protection is via the JWT jti claim, not session or file locks.
//   - User mapping and metadata sync must use explicit code and validated API calls.
//   - For explicit logout, clear buzz_sso and related keys; never destroy PHPSESSID.
//   - Any future cross-app state should use a purpose-built stateless API.
//
// ⚠ DO NOT revive or call legacy shadow/session functions.
// ⚠ All SSO trust, mapping, and replay checks must flow through the stateless JWT mechanism.
//
/* ----- BEGIN LEGACY/DEPRECATED BLOCK: SESSION BOOTSTRAP ----- */
/* static $qd_session_bootstrapped = false;
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
*/
/* Defensive sync: legacy anti-drift, NOT needed with JWT SSO. Retained for log context. */
/*if (!isset($_SESSION['buzz_sso_defensive_last']) || (time() - (int)$_SESSION['buzz_sso_defensive_last']) > 4*3600) {
    $_SESSION['buzz_sso_defensive_last'] = time();
    $errs = [];
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) $errs[] = 'buzz_sso_cookie_missing';
    if (empty($_SESSION['wp_user_login'])) $errs[] = 'wp_user_login_missing';
    if (empty($_SESSION['qd_user_id']) || !is_numeric($_SESSION['qd_user_id'])) $errs[] = 'qd_user_id_missing_or_invalid';
    if ($errs) qd_bridge_log('Defensive sync checks', ['errs'=>$errs]);
}
*/
/* Session normalization: DO NOT use for SSO trust! JWT is canonical. */
/*function normalize_sso_session() {}
normalize_sso_session();
*/
// -------------------------------------------------------------

// ===================================================================================================
// END: CONFIGURATIONS + LOGGING + graceful failure (no legacy HMAC/token helpers below this point!)
// ===================================================================================================



// =============================================
// START: ENDPOINTS + PAYLOAD + DATA MAPPING
// =============================================
// ------------------------------------------
// Remote Synced WordPress Login Endpoint
// ------------------------------------------
if (!empty($_REQUEST['sso_action']) && $_REQUEST['sso_action'] === 'remote_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['sso_token'] ?? '';
    $signature = $_SERVER['HTTP_X_BUZZJUICE_SIGNATURE'] ?? '';
    $expected_signature = hash_hmac('sha256', $token, $BUZZ_SSO_SECRET);

    
    if (!hash_equals($signature, $expected_signature)) {
        bz_bridge_fail_gracefully('Remote SSO login — signature mismatch');
    }

    $payload = bz_validate_jwt($token, $BUZZ_SSO_SECRET); // stateless - uses WP as truth source
    if (!$payload) bz_bridge_fail_gracefully('Remote SSO login — JWT invalid/expired');

    // Find or register QuickDate user based on WP identity claims
    $qd_user_id = qd_find_user_by_login_email($payload['wp_user_login'], $payload['wp_user_email']);
    if (!$qd_user_id && BUZZ_SSO_AUTO_REGISTER) {
        $qd_user_id = qd_register_user($payload['wp_user_login'], $payload['wp_user_email'], $payload['wp_user_id']);
    }
    if (!$qd_user_id) bz_bridge_fail_gracefully('Remote SSO login — Unable to map/register user');

    $_SESSION['qd_user_id']    = $qd_user_id;
    $_SESSION['wp_user_id']    = $payload['wp_user_id'];
    $_SESSION['wp_user_login'] = $payload['wp_user_login'];
    $_SESSION['wp_user_email'] = $payload['wp_user_email'];
    $_SESSION['wo_user_id']    = $payload['wo_user_id'];
    
    setcookie(BUZZ_SSO_COOKIE, $token, time()+BUZZ_SSO_TTL, '/', BUZZ_COOKIE_DOMAIN, true, true);

    header('Content-Type: application/json');
    echo json_encode(['status'=>200, 'logged_in'=>true, 'qd_user_id'=>$qd_user_id]);
}

// ------------------------------------------
// Secure Payload Endpoint for WoWonder
// ------------------------------------------
if (!empty($_REQUEST['sso_action']) && $_REQUEST['sso_action'] === 'get_payload_for_streams' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $signature = $_SERVER['HTTP_X_BUZZJUICE_SIGNATURE'] ?? '';
    $expected_signature = hash_hmac('sha256', 'get_payload_for_streams', $BUZZ_SSO_SECRET);

    if (!hash_equals($signature, $expected_signature)) {
        bz_bridge_fail_gracefully('WW SSO payload fetch — signature mismatch');
    }

    $qd_user_id = $_SESSION['qd_user_id'] ?? 0;
    if (!$qd_user_id) {
        header('Content-Type: application/json');
        echo json_encode(['status'=>403, 'error'=>'No QuickDate session']);
    }

    $user_payload = [
        'qd_user_id'    => $qd_user_id,
        'wp_user_id'    => $_SESSION['wp_user_id'] ?? null,
        'wp_user_login' => $_SESSION['wp_user_login'] ?? null,
        'wp_user_email' => $_SESSION['wp_user_email'] ?? null,
        'wo_user_id'    => $_SESSION['wo_user_id'] ?? null,
    ];
    header('Content-Type: application/json');
    echo json_encode(['status'=>200, 'payload'=>$user_payload]);
}

// --------------------------------------
// Stateless SSO Orchestration (QuickDate)
// --------------------------------------
// Loop protection (helps block infinite SSO relay between QD & WP)
if (!isset($_SESSION)) session_start();
$_SESSION['qd_sso_attempts'] = ($_SESSION['qd_sso_attempts'] ?? 0) + 1;
if ($_SESSION['qd_sso_attempts'] > 3) {
    bz_bridge_log('SSO loop protection triggered', ['attempts' => $_SESSION['qd_sso_attempts']]);
    unset($_SESSION['qd_sso_attempts']);
    bz_redirect_to_wp_login('/');
    exit;
}

$sso_token = $_COOKIE[BUZZ_SSO_COOKIE] ?? null;
$payload = null;

// 1. Try BUZZ_SSO_COOKIE first
if (!empty($sso_token) && $BUZZ_SSO_SECRET) {
    try {
        $payload = bz_validate_jwt($sso_token, $BUZZ_SSO_SECRET);
        if ($payload) {
            bz_bridge_log('Valid JWT from BUZZ_SSO_COOKIE', ['exp'=>$payload['exp']??null]);
            // If expiring within 5 minutes, force silent refresh from WP
            if (!empty($payload['exp']) && ($payload['exp'] - time()) < 300) {
                bz_bridge_log('JWT nearing expiry; refreshing from WP endpoint', []);
                $payload = null;
            }
        }
    } catch (Throwable $e) {
        qd_bridge_log('Exception during JWT validation', ['ex' => $e->getMessage()]);
        $payload = null;
    }
} else {
    bz_bridge_log('No BUZZ_SSO_COOKIE present or missing secret', [
        'cookie_present' => !empty($sso_token),
        'BUZZ_SSO_SECRET' => (bool)$BUZZ_SSO_SECRET
    ]);
}

// 2. Try Login from WordPress Endpoint
if (!$payload) {
    bz_bridge_log('Attempting WordPress endpoint fetch');
    $payload_arr = bz_fetch_wp_stateless_payload($sso_token ?? null, $BUZZ_SSO_SECRET);

    // If WP explicitly says "not logged in" → redirect immediately to WP
    if (
        isset($payload_arr['status']) &&
        (int)$payload_arr['status'] === 401
    ) {
        $requested = $_GET['redirect_to'] ?? $_SERVER['REQUEST_URI'] ?? '/social/';
        $redirect_target = 'https://buzzjuice.net' . $requested;
        bz_bridge_log('WP endpoint returned 401. Redirecting to WP login.', [
            'redirect_to' => $redirect_target
        ]);
        unset($_SESSION['qd_sso_attempts']);
        bz_redirect_to_wp_login($redirect_target);
        exit;
    }
    // If WP returned payload → use it
    if (!empty($payload_arr['payload'])) {
        $payload = $payload_arr['payload'];
        bz_bridge_log('Payload obtained from WP endpoint');
    }
}

// 3. Try Login from WoWonder, only if WP didn't say 401 explicitly
if (!$payload) {
    bz_bridge_log('Attempting WoWonder endpoint fallback');
    $ww_url = 'https://buzzjuice.net/streams/ww-sso-bridge.php?sso_action=get_payload_for_social';
    $signature = hash_hmac('sha256', 'get_payload_for_social', $BUZZ_SSO_SECRET);
    $ch = curl_init($ww_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Buzzjuice-Signature: ' . $signature]);
    $result = curl_exec($ch);
    $error  = curl_error($ch);
    curl_close($ch);
    if (!$error && $result) {
        $resp = json_decode($result, true);
        if (!empty($resp['payload'])) {
            $payload = $resp['payload'];
            bz_bridge_log('Payload obtained from WoWonder endpoint');
        }
    } else {
        bz_bridge_log('WoWonder fetch error', ['error'=>$error]);
    }
}

// Final: If all fail, redirect to WordPress login
if (!$payload) {
    bz_bridge_log('No valid SSO payload from BUZZ_SSO_COOKIE, WP endpoint, or WoWonder endpoint.');
    unset($_SESSION['qd_sso_attempts']);
    $requested = $_GET['redirect_to'] ?? $_SERVER['REQUEST_URI'] ?? '/social/';
    $redirect_target = 'https://buzzjuice.net' . $requested;
    bz_redirect_to_wp_login($redirect_target);
    exit;
} else {
    unset($_SESSION['qd_sso_attempts']); // reset loop counter on success
}

// -------------------------------------------------------
// SSO JWT CLAIM EXTRACTION & REQUIRED CLAIMS VALIDATION
// -------------------------------------------------------
$claim_wp_user_id    = (int)($payload['wp_user_id'] ?? 0);
$claim_wp_user_login = (string)($payload['wp_user_login'] ?? $payload['login'] ?? '');
$claim_wp_user_email = (string)($payload['wp_user_email'] ?? $payload['email'] ?? '');
$claim_qd_user_id    = (int)($payload['qd_user_id'] ?? 0);

$original_claims = [
    'claim_wp_user_id'    => $claim_wp_user_id,
    'claim_wp_user_login' => $claim_wp_user_login,
    'claim_wp_user_email' => $claim_wp_user_email,
    'claim_qd_user_id'    => $claim_qd_user_id
];
qd_bridge_log('JWT SSO claims extracted', array_merge($original_claims, ['raw_payload' => $payload]));

// Required claims guard
if (!$claim_wp_user_id || !$claim_wp_user_login || !$claim_wp_user_email) {
    qd_bridge_log('Missing required claims (JWT incomplete)', $original_claims);
    $requested = $_GET['redirect_to'] ?? $_SERVER['REQUEST_URI'] ?? '/social/';
    $redirect_target = 'https://buzzjuice.net' . $requested;
    bz_redirect_to_wp_login($redirect_target);
    exit;
}

// Canonicalization: prefer already set session fields for UI only (do NOT trust for SSO)
$cookie_payload = [
    'wp_user_id'    => $_SESSION['wp_user_id']    ?? $claim_wp_user_id,
    'wp_user_login' => $_SESSION['wp_user_login'] ?? $claim_wp_user_login,
    'wp_user_email' => $_SESSION['wp_user_email'] ?? $claim_wp_user_email,
    'qd_user_id'    => $_SESSION['qd_user_id']    ?? $claim_qd_user_id,
];

qd_bridge_log('Canonical pre-mapping values', [
    'canonical' => $cookie_payload,
    'session'   => $_SESSION ?? [],
]);



// ========================================================
// QuickDate <-> WordPress Mapping & Registration Helpers
// ========================================================
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


/**
 * qd_register_user() — Register QuickDate user (stateless SSO, JWT/federated, robust)
 * Returns new QuickDate user id (int) on success, 0 on failure.
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

        // Username fallback + collision safety
        $username = preg_replace('~[^a-z0-9_.-]~i', '', (string)$login);
        if (!$username) $username = 'wpuser' . intval($wp_user_id) . '_' . random_int(1000,9999);

        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        $wp_full = (function_exists('wp_get_full_user_data') && $conn && $wp_user_id)
            ? wp_get_full_user_data($conn, $wp_user_id)
            : [];
        $avatar = $wp_full['xprofile']['avatar'] ?? $wp_full['meta']['avatar'] ?? ($GLOBALS['config']->userDefaultAvatar ?? '');

        $password = bin2hex(random_bytes(8));
        $imported_avatar = $avatar;
        if (!empty($avatar) && method_exists($user, 'ImportImageFromLogin')) {
            try {
                $imp = $user->ImportImageFromLogin($avatar, 1);
                if (!empty($imp)) $imported_avatar = $imp;
                else qd_bridge_log('qd_register_user: ImportImageFromLogin returned empty, using fallback avatar', ['avatar'=>$avatar]);
            } catch (Throwable $e) {
                qd_bridge_log('qd_register_user: ImportImageFromLogin failed, using fallback avatar', ['ex'=>$e->getMessage(),'avatar'=>$avatar]);
            }
        }

        $now = time();
        $lang = 'english';
        if (!empty($GLOBALS['config']->defaultLang)) {
            $lang = $GLOBALS['config']->defaultLang;
        } elseif (!empty($GLOBALS['config']->defualtLang)) {
            $lang = $GLOBALS['config']->defualtLang;
            qd_bridge_log('qd_register_user: using legacy config key "defualtLang"', ['lang'=>$lang]);
        }

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
            'registered'    => gmdate('Y-m-d H:i:s', $now),
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

        if (!empty($wp_user_id) && $wp_user_id > 0) {
            $meta_key = 'qd_user_id';
            $meta_value = (string)$created_id;
            $did_write = qd_persist_wp_usermeta($wp_user_id, $meta_key, $meta_value, $conn);
            if (!$did_write) {
                qd_bridge_log('qd_register_user: could not set WP usermeta qd_user_id', [
                    'wp_user_id'=>$wp_user_id, 'qd_user_id'=>$created_id
                ]);
            }
        } else {
            qd_bridge_log('qd_register_user: no wp_user_id provided — skipping WP usermeta write', [
                'wp_user_id'=>$wp_user_id, 'created_qd_id'=>$created_id
            ]);
        }

        if (session_status() === PHP_SESSION_NONE) @session_start();
        try { $_SESSION['qd_user_id'] = $created_id; } catch(Throwable $e) {
            qd_bridge_log('qd_register_user: failed to set session qd_user_id', ['ex'=>$e->getMessage()]);
        }

        qd_bridge_log('qd_register_user: Auto-registered QuickDate user', [
            'id'        => $created_id,
            'username'  => $username,
            'email'     => $email,
            're_data'   => $re_data
        ]);
        return $created_id;
    }
}

/** Helper to persist custom meta value to WP usermeta with fallback strategies. */
if (!function_exists('qd_persist_wp_usermeta')) {
    function qd_persist_wp_usermeta($wp_user_id, $meta_key, $meta_value, $conn = null) {
        if ($conn && function_exists('wp_update_usermeta')) {
            try {
                wp_update_usermeta($conn, (int)$wp_user_id, $meta_key, $meta_value);
                qd_bridge_log('Set wp_usermeta '.$meta_key.' via wp_update_usermeta', ['wp_user_id'=>$wp_user_id,'meta_value'=>$meta_value]);
                return true;
            } catch (Throwable $e) {
                qd_bridge_log('wp_update_usermeta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'meta_key'=>$meta_key]);
            }
        }
        if (function_exists('update_user_meta')) {
            try {
                update_user_meta((int)$wp_user_id, $meta_key, $meta_value);
                qd_bridge_log('Set wp_usermeta '.$meta_key.' via update_user_meta', ['wp_user_id'=>$wp_user_id,'meta_value'=>$meta_value]);
                return true;
            } catch (Throwable $e) {
                qd_bridge_log('update_user_meta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'meta_key'=>$meta_key]);
            }
        }
        if ($conn && $wp_user_id) {
            $um_table_sql = null;
            if (function_exists('wp_table')) {
                $um_table_sql = wp_table('usermeta');
            } else {
                $prefix = defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_';
                $um_table_sql = defined('WP_DB_NAME')
                    ? ('`' . WP_DB_NAME . '`.`' . $prefix . 'usermeta`')
                    : ('`' . $prefix . 'usermeta`');
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
                        return true;
                    }
                } else {
                    mysqli_stmt_close($stmt);
                    $insert_sql = "INSERT INTO $um_table_sql (user_id, meta_key, meta_value) VALUES (?, ?, ?)";
                    $ins = @mysqli_prepare($conn, $insert_sql);
                    if ($ins) {
                        mysqli_stmt_bind_param($ins, 'iss', $wp_user_id, $meta_key, $meta_value);
                        mysqli_stmt_execute($ins);
                        mysqli_stmt_close($ins);
                        return true;
                    }
                }
            }
            $esc_val = mysqli_real_escape_string($conn, $meta_value);
            $esc_key = mysqli_real_escape_string($conn, $meta_key);
            $check_raw = "SELECT umeta_id FROM $um_table_sql WHERE user_id = " . intval($wp_user_id) . " AND meta_key = '$esc_key' LIMIT 1";
            $res = @$conn->query($check_raw);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $umeta_id = intval($row['umeta_id']);
                $raw_update = "UPDATE $um_table_sql SET meta_value = '$esc_val' WHERE umeta_id = $umeta_id";
                @$conn->query($raw_update);
                return true;
            } else {
                $raw_insert = "INSERT INTO $um_table_sql (user_id, meta_key, meta_value) VALUES (" . intval($wp_user_id) . ", '$esc_key', '$esc_val')";
                @$conn->query($raw_insert);
                return true;
            }
        }
        return false;
    }
}



//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 5
/* ------------------------------------------------------------------------ 
   STATELESS USER MAPPING: Resolving QuickDate user identity from JWT/WP 
   ------------------------------------------------------------------------ */

if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

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
            qd_bridge_log('Strict verification failed — username/email mismatch, clearing session', [
                'qd_user_id'=>$claim_qd_user_id,
                'db_username'=>$db_un,
                'db_email'=>$db_em,
                'session_login'=>trim($claim_wp_user_login),
                'session_email'=>trim($claim_wp_user_email)
            ]);
            unset($_SESSION['qd_user_id']);
            $claim_qd_user_id = 0;
            $orig_session_qd  = 0;
        }
    } else {
        qd_bridge_log('Strict verification failed — qd_user_id not found in DB, clearing session', [
            'qd_user_id'=>$claim_qd_user_id
        ]);
        unset($_SESSION['qd_user_id']);
        $claim_qd_user_id = 0;
        $orig_session_qd  = 0;
    }
}

if (!$final_qd_user_id) {
    if ($claim_qd_user_id && qd_find_user_by_id($claim_qd_user_id)) {
        $final_qd_user_id = $claim_qd_user_id;
        qd_bridge_log('Using qd_user_id from claim/cookie/session (exists in DB)', ['qd_user_id'=>$final_qd_user_id]);
    } else {
        $found = qd_find_user_by_login_email($claim_wp_user_login, $claim_wp_user_email);
        if ($found) {
            $final_qd_user_id = $found;
            qd_bridge_log('Mapped qd_user_id via login+email', ['qd_user_id'=>$final_qd_user_id]);
            if (!empty($claim_wp_user_id) && $wp_conn && function_exists('qd_persist_wp_usermeta')) {
                try {
                    qd_persist_wp_usermeta($claim_wp_user_id, 'qd_user_id', $final_qd_user_id, $wp_conn);
                    qd_bridge_log('Persisted mapped qd_user_id to WordPress usermeta', [
                        'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                    ]);
                } catch (Throwable $e) {
                    qd_bridge_log('Exception persisting qd_user_id to WP usermeta', [
                        'ex'=>$e->getMessage(),'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                    ]);
                }
            }
        } else {
            if (BUZZ_SSO_AUTO_REGISTER && filter_var($claim_wp_user_email, FILTER_VALIDATE_EMAIL)) {
                qd_bridge_log('No mapping found — attempting auto-register', [
                    'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email,'orig_session_qd'=>$orig_session_qd
                ]);
                $created = qd_register_user($claim_wp_user_login, $claim_wp_user_email, $claim_wp_user_id);
                if ($created) {
                    $final_qd_user_id = (int)$created;
                    qd_bridge_log('Auto-register created QuickDate user', ['created_id'=>$created]);
                    $_SESSION['qd_user_id'] = $final_qd_user_id;
                    $claim_qd_user_id = $final_qd_user_id;
                    if (!empty($claim_wp_user_id) && $wp_conn && function_exists('qd_persist_wp_usermeta')) {
                        try {
                            qd_persist_wp_usermeta($claim_wp_user_id, 'qd_user_id', $final_qd_user_id, $wp_conn);
                            qd_bridge_log('Persisted auto-registered qd_user_id to WP usermeta', [
                                'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                            ]);
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
                qd_bridge_log('Auto-registration disabled or invalid email, mapping not found', [
                    'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email
                ]);
            }
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
    bz_redirect_to_wp_login('no_qd_user_after_mapping');
}

$_SESSION['wp_user_login'] = $_SESSION['wp_user_login'] ?? trim($claim_wp_user_login);
$_SESSION['wp_user_id']    = (int)$claim_wp_user_id;
$_SESSION['wp_user_email'] = trim($claim_wp_user_email);
$_SESSION['qd_user_id']    = (int)$final_qd_user_id;

try {
    $need_issue = false;
    if (empty($sso_token)) {
        $need_issue = true;
    } else {
        if (!is_array($cookie_payload)) {
            $cookie_payload = qd_sso_verify_token($sso_token, $BUZZ_SSO_SECRET) ?: null;
        }
        if (!is_array($cookie_payload)
            || empty($cookie_payload['qd_user_id'])
            || (int)$cookie_payload['qd_user_id'] !== (int)$final_qd_user_id
            || (!empty($cookie_payload['exp']) && $cookie_payload['exp'] < time())
        ) {
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

/* If already logged in according to QuickDate, DO NOT perform a server-side redirect.
   A server-side redirect will cause the client AJAX to receive HTML/302 instead of the
   expected JSON response. Instead, preserve the intended redirect target (steps),
   log the fact that QuickDate is already logged and continue to render the bridge page.
   The client JS will post to the do_login endpoint and receive proper JSON with location. */
$deferred_redirect_target = null;
if (defined('IS_LOGGED') && IS_LOGGED === true) {
    $deferred_redirect_target = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/find-matches';
    qd_bridge_log('IS_LOGGED true — NOT redirecting server-side; deferring to client flow', ['user_id'=>$_SESSION['qd_user_id'], 'target'=>$deferred_redirect_target]);
    // ensure last_url will be set to $deferred_redirect_target later when building client payload
}

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
if (!empty($_REQUEST['last_url'])) {
    $parsed = parse_url((string)$_REQUEST['last_url']);
    $path = $parsed['path'] ?? '/';
    $last_url = (strpos($path, '/') === 0) ? $path : '/';
}
if (!$last_url || ($site_base && strpos($last_url, $site_base) !== 0)) $last_url = '/';
$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php') . '?sso_action=do_login';


$sso_username = $_SESSION['wp_user_login'];
qd_bridge_log('SSO client payload prepared', [
    'sso_username'     => $sso_username,
    'sso_token_len' => strlen($sso_token),
    'ajax_url'         => $ajax_url,
    'last_url'         => $last_url
]);

if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    QD_SSO_Login();
    exit;
}
//END QuickDate 'social/qd-sso-bridge.php' UPDATED PART 5 - FINAL



//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 6
function QD_SSO_Login() {
    global $BUZZ_SSO_SECRET, $config, $sso_token;
    header('Content-Type: application/json; charset=utf-8');

    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $last_url = isset($_POST['last_url']) ? (string)$_POST['last_url'] : '/';

    if (!$BUZZ_SSO_SECRET) {
        qd_bridge_log('QD_SSO_Login: BUZZ_SSO_SECRET missing');
        http_response_code(500);
        echo json_encode(['status'=>500,'errors'=>['Server misconfiguration']]);
        exit;
    }

    if (strlen($sso_token) < 40 || strlen($sso_token) > 4096) {
        qd_bridge_log('QD_SSO_Login: invalid token length', ['token_len'=>strlen($password)]);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid token format']]);
        exit;
    }

    $claims = bz_validate_jwt($sso_token, $BUZZ_SSO_SECRET);
    if (!$claims) {
        qd_bridge_log('QD_SSO_Login: invalid SSO password token', ['token_preview'=>substr($password,0,40)]);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid or expired SSO token']]);
        exit;
    }

    // Replay protection (if needed)
    if (!empty($claims['jti']) && function_exists('qd_register_jti')) {
        $exp = $claims['exp'] ?? (time()+BUZZ_SSO_TTL);
        if (!qd_register_jti($claims['jti'], $exp)) {
            qd_bridge_log('Replay detected', ['jti'=>$claims['jti']]);
            http_response_code(401);
            echo json_encode(['status'=>401,'errors'=>['Replay detected']]);
            exit;
        }
    }

    $exp_qd    = (int)($claims['qd_user_id'] ?? 0);
    $exp_wp    = (int)($claims['wp_user_id'] ?? 0);
    $exp_login = (string)($claims['wp_user_login'] ?? '');
    $exp_email = (string)($claims['wp_user_email'] ?? '');

    // Enterprise: required anchor (email or wp_user_id)
    if (empty($exp_email) && empty($exp_wp)) {
        qd_bridge_log('Missing strong anchor', []);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid SSO identity']]);
        exit;
    }

    qd_bridge_log('QD_SSO_Login canonical identity', [
        'qd'    => $exp_qd,
        'wp'    => $exp_wp,
        'login' => $exp_login,
        'email' => $exp_email
    ]);
    $identifier_count = 0;
    foreach ([$exp_qd, $exp_wp, $exp_login, $exp_email] as $v) if (!empty($v)) $identifier_count++;
    if ($identifier_count < 3) {
        qd_bridge_log('Insufficient identifiers in token');
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid SSO token structure']]);
        exit;
    }

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
                $types = '';
                foreach ($params as $p) { $types .= is_int($p) ? 'i' : 's'; }
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $candidates[] = $row;
                $stmt->close();
            }
        }
    }
    qd_bridge_log('QD_SSO_Login candidates count', ['count'=>count($candidates)]);

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
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['No matching QuickDate account for SSO.']]);
        exit;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_start()) {
            qd_bridge_log('Session start failed on login');
            http_response_code(500);
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

    // Post-login WP→QD sync logic as previously implemented

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



    // Hardened redirect
    $default_url = (isset($config->uri) ? rtrim($config->uri, '/') : '') . '/find-matches';
    $url = $default_url;
    if (!empty($accepted_user['start_up']) && $accepted_user['start_up'] == 3 && !empty($accepted_user['verified'])) {
        $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/steps';
    }
    if (!empty($last_url) && $last_url !== '//') {
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

// Generate CSP nonce for inline script
$nonce = bin2hex(random_bytes(16));

// Security headers for production browser-layer defense
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';");
header("Referrer-Policy: no-referrer");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");

// Log bridge page render
qd_bridge_log('Rendering QD SSO bridge page', [
    'sso_username'      => $sso_username,
    'sso_token_len'  => strlen($sso_token),
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
.card{max-width:560px;margin:10vh auto;padding:1.5rem 1.75rem;background:#131a33;border-radius:14px}
.title{font-size:1.25rem;margin:0 0 .5rem}
.status{margin-top:1rem;padding:.75rem 1rem;border-radius:10px;background:#0e1530}
.ok{background:#10351f}
.err{background:#3a1414}
.dbg pre{background:#0e1530;padding:.75rem;border-radius:8px;overflow:auto}
</style>
</head>
<body>
  <div class="card">
    <div class="title">Signing you in…</div>
    <div id="status" class="status">Preparing secure session…</div>
    <?php if (bz_is_debug()): ?>
      <div class="dbg"><pre><?php echo htmlspecialchars(print_r([
          'ajax_url' => $ajax_url,
          'post' => [
            'token'=>'(token:len='.strlen($sso_token).')',
            'last_url'=>$last_url,
            'remember_device'=>'on'
          ],
          'session_keys' => array_keys($_SESSION),
          'cookie_keys' => array_keys($_COOKIE)
      ], true)); ?></pre></div>
    <?php endif; ?>
    <noscript>
      <div class="status err">
        JavaScript is required for secure sign-in. Please enable JavaScript.
      </div>
    </noscript>
  </div> 
  <script nonce="<?php echo htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8'); ?>">
  (function(){
    if (window.__qd_sso_executed) return;
    window.__qd_sso_executed = true;

    var ajaxUrl = <?php echo json_encode($ajax_url); ?>;
    var payload = {
      token: <?php echo json_encode($sso_token); ?>,
      remember_device: 'on',
      last_url: <?php echo json_encode($last_url); ?>
    };
    var beaconUrl = <?php
      $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php';
      echo json_encode($self . '?sso_client_log=1');
    ?>;
    var statusEl = document.getElementById('status');

    // Prevent bridge infinite loops
    if (lastUrl && lastUrl.indexOf('qd-sso-bridge.php') !== -1) lastUrl = undefined;

    function beacon(msg, extra){
      try{
        var dataObj = {msg:msg,extra:extra||{},when:Date.now()};
        var data = JSON.stringify(dataObj);
        if (data.length > 2000) data = data.substring(0,2000);
        if (navigator.sendBeacon) navigator.sendBeacon(beaconUrl, data);
        else { var x = new XMLHttpRequest(); x.open('POST', beaconUrl, true); x.setRequestHeader('Content-Type','text/plain'); x.send(data); }
      }catch(e){}
    }

    statusEl && (statusEl.textContent = 'Contacting server…');
    beacon('bridge:init', {ajaxUrl: ajaxUrl, last: payload.last_url});

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
        try { res = JSON.parse(xhr.responseText); } catch(e) {
          beacon('bridge:parse_error', {http: xhr.status});
        }
        if (res) { ok = !!(res.status===200 || res.status===600) && !!res.location; locationUrl = res.location; errors = res.errors || null; }
        beacon('bridge:response', {status: res && res.status, http: xhr.status});
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

    xhr.onerror = function(){ beacon('bridge:error', {http: xhr.status}); statusEl && (statusEl.className='status err', statusEl.textContent='Network or server error.'); xhr=null; };
    xhr.ontimeout = function(){ beacon('bridge:timeout', {}); statusEl && (statusEl.className='status err', statusEl.textContent='Request timed out.'); xhr=null; };

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