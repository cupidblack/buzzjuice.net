<?php
/**
 * ww-sso-bridge.php (Stateless SSO, JWT, replay protection, 2026+)
 *
 * Bridges WordPress identity (stateless JWT) into WoWonder runtime.
 * - All SSO data comes ONLY from a JWT acquired via the WP endpoint.
 * - No session/shadow file/cookie trust.
 * - Robust error handling, replay protection, log discipline.
 * - Extensible for refresh token rotation.
 */
 
require_once __DIR__ . '/assets/init.php';

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
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/ww_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

// Get secret/SSO token from config request or cookie
$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);
$sso_token  = $_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? '');
if (!empty($sso_token)) {
    error_log('[BuzzStreams SSO DEBUG] BUZZ_SSO_COOKIE payload: ' . $sso_token);
} else {
    error_log('[BuzzStreams SSO DEBUG] BUZZ_SSO_COOKIE not set.');
}
$sso_action = $_REQUEST['sso_action'] ?? '';

// -------------------------------------------------------
// BOOTSTRAP CHECKS — REQUIRE Wo CONFIG AND SQL
// -------------------------------------------------------
global $wo, $sqlConnect;

$site_url = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($wo['config']['site_url']) ? rtrim($wo['config']['site_url']) : '');

if (empty($site_url) || empty($sqlConnect)) {
    bz_bridge_log('Bootstrap incomplete - missing $wo or $sqlConnect');
    bz_debug_page('Bootstrap incomplete', ['$wo' => $wo ?? null, '$sqlConnect' => (bool)$sqlConnect]);
    header('Location: /');
    exit;
}

// -------------------------------------------------------
// LOGGING, DEBUG, CLIENT DEBUG BEACON, LOOP PROTECTION + SESSION VISIBILITY
// -------------------------------------------------------

    $site_root = 'https://buzzjuice.net';

    // Build the full current request including query string
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/streams';
    $full_deeplink = $site_root . $request_uri;

    $bridge_url = $site_root . '/streams/ww-sso-bridge.php?sso_action=do_login&last_urlo=' . rawurlencode($full_deeplink);


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
            bz_bridge_log('Resuming PHP session from cookie (bridge fallback)');
        }
        session_start();
        bz_bridge_log('Session started by bridge (fallback)', ['session_id' => session_id()]);
        // CRITICAL: this session is *never* a trust anchor for SSO
    } else {
        bz_bridge_log('Session not started (bridge): benign request, no buzz_sso and not an SSO action');
    }
}

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
    bz_bridge_log('Bridge loop suspected — forcing fallback', [
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
define('BUZZ_JTI_STORE', __DIR__ . '/sso_jti_store');
if (!is_dir(BUZZ_JTI_STORE)) @mkdir(BUZZ_JTI_STORE, 0755, true);
if (mt_rand(1, 35) === 9) bz_cleanup_jti_store();

// -------------------------------------------------------------

/* ----- START LEGACY SESSION BOOTSTRAP & SHADOW RECONCILIATION — DEPRECATED BLOCK ----- */
// -------------------------------------------------------------
// SHADOW SESSION & FILE-BASED RECONCILIATION HELPERS REMOVED
// -------------------------------------------------------------
// All functions attempting to read/write/parity-check shadow session
// files or reconcile WoWonder/PHP/WordPress cookies/sessions
// have been deprecated as of the migration to stateless SSO.
//
// The bridge is now stateless. The *only* trusted source of identity
// is an RFC 7519 JWT, validated on this server, and issued by
// the WordPress stateless SSO endpoint (`sso-session-sync.php`).
//
// USER MAPPING WORKFLOW, MODERN REPLACEMENT:
//   1. Accept JWT (`sso_token`) from client (cookie, GET, or POST param)
//   2. Validate JWT signature, claims, exp, nbf, iss, aud, jti (replay)
//   3. Map JWT claims (WP user id/email/login) to a WoWonder user (DB lookup)
//   4. If no matching WoWonder account is found, optionally auto-register
//   5. Start a *fresh* and normal WoWonder session for that user only
//   6. Optionally push metadata to the user profile (name, avatar, etc.)
//   7. Do NOT persist or trust any legacy shadow/cookie/session/file
//   8. Redirect to the app using standard WoWonder routing/redirect rules
// -------------------------------------------------------------
/* ----- END LEGACY SESSION BOOTSTRAP & SHADOW RECONCILIATION — DEPRECATED BLOCK ----- */

// ===================================================================================================
// END: CONFIGURATIONS + LOGGING + graceful failure (no legacy HMAC/token helpers below this point!)
// ===================================================================================================



// =============================================
// START: ENDPOINTS + PAYLOAD + DATA MAPPING
// =============================================

// -------------------------------------------------------
// LIGHTWEIGHT "CHECK" ENDPOINT (validates logged-in/JWT/redirect)
// -------------------------------------------------------
/*if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'check') {
    header('Content-Type: application/json; charset=utf-8');

    $is_logged_in = !empty($wo['loggedin']) || !empty($_SESSION['wo_user_id']);
    if ($is_logged_in) {
        echo json_encode(['logged_in' => true]);
        exit;
    }

    $redirect_to_param = !empty($_GET['redirect_to']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$_GET['redirect_to']) : '';
    if (!empty($_COOKIE[BUZZ_SSO_COOKIE]) && !empty($BUZZ_SSO_SECRET)) {
        try {
            $payload = bz_validate_jwt($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
        } catch (Throwable $e) {
            bz_bridge_log('JWT validation error in check endpoint', ['e' => $e->getMessage()]);
            $payload = false;
        }
        if ($payload) {
            $hydrate_url = ($_SERVER['PHP_SELF'] ?? '/ww-sso-bridge.php') . '?sso_action=do_login';
            if ($redirect_to_param) $hydrate_url .= '&redirect_to=' . rawurlencode($redirect_to_param);

            $site_base  = rtrim($wo['config']['site_url'] ?? '', '/');
            $go_pro_url = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
            $wp_login   = 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_url);

            echo json_encode([
                'logged_in'   => false,
                'hydrate'     => true,
                'hydrate_url' => $hydrate_url,
                'wp_login'    => $wp_login,
            ]);
            exit;
        }
    }

    // JWT missing/invalid: force SSO (wp-login)
    $site_base  = rtrim($wo['config']['site_url'] ?? '', '/');
    $go_pro_url = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    $wp_login   = 'https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_url);
    echo json_encode(['logged_in' => false, 'wp_login' => $wp_login]);
    exit;
}
*/

// ------------------------------------------
// Remote Synced WordPress Login Endpoint
// ------------------------------------------
if (!empty($_REQUEST['sso_action']) && $_REQUEST['sso_action'] === 'remote_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['sso_token'] ?? '';
    $signature = $_SERVER['HTTP_X_BUZZJUICE_SIGNATURE'] ?? '';
    $expected_signature = hash_hmac('sha256', $token, $BUZZ_SSO_SECRET);

    // Only allow if signed correctly by WP (add IP allowlist or referer if desired)
    if (!hash_equals($signature, $expected_signature)) {
        bz_bridge_fail_gracefully('Remote SSO login — signature mismatch');
    }

    $payload = bz_validate_jwt($token, $BUZZ_SSO_SECRET);
    if (!$payload) bz_bridge_fail_gracefully('Remote SSO login — JWT invalid/expired');

    // Map/create WoWonder user
    $wo_user_id = bz_find_wo_user_any($payload['wp_user_id'], $payload['wp_user_email'], $payload['wp_user_login']);
    if (!$wo_user_id && BUZZ_SSO_AUTO_REGISTER) {
        $wo_user_id = bz_register_wo_user($payload['wp_user_id'], $payload['wp_user_login'], $payload['wp_user_email']);
    }
    if (!$wo_user_id) {
        bz_bridge_fail_gracefully('Remote SSO login — Unable to map/register user');
    }

    // Set session fields
    $_SESSION['wo_user_id']    = $wo_user_id;
    $_SESSION['wp_user_id']    = $payload['wp_user_id'];
    $_SESSION['wp_user_login'] = $payload['wp_user_login'];
    $_SESSION['wp_user_email'] = $payload['wp_user_email'];
    $_SESSION['qd_user_id']    = $payload['qd_user_id'];

    // Issue BUZZ_SSO_COOKIE
    setcookie(BUZZ_SSO_COOKIE, $token, time()+BUZZ_SSO_TTL, '/', BUZZ_COOKIE_DOMAIN, true, true);
    send_json_response(['status'=>200, 'logged_in'=>true, 'wo_user_id'=>$wo_user_id]);
}

// ------------------------------------------
// BuzzSocial Secure Payload Fetch Endpoint
// ------------------------------------------
if (!empty($_REQUEST['sso_action']) && $_REQUEST['sso_action'] === 'get_payload_for_social' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $signature = $_SERVER['HTTP_X_BUZZJUICE_SIGNATURE'] ?? '';
    $expected_signature = hash_hmac('sha256', 'get_payload_for_social', $BUZZ_SSO_SECRET); // QD secret if needed

    if (!hash_equals($signature, $expected_signature)) {
        bz_bridge_fail_gracefully('QuickDate SSO payload fetch — signature mismatch');
    }

    $wo_user_id = $_SESSION['wo_user_id'] ?? 0;
    if (!$wo_user_id) send_json_response(['status'=>403, 'error'=>'No WoWonder session']);

    $user_payload = [
        'wo_user_id'    => $wo_user_id,
        'wp_user_id'    => $_SESSION['wp_user_id'] ?? null,
        'wp_user_login' => $_SESSION['wp_user_login'] ?? null,
        'wp_user_email' => $_SESSION['wp_user_email'] ?? null,
        'qd_user_id'    => $_SESSION['qd_user_id'] ?? null,
    ];
    send_json_response(['status'=>200, 'payload'=>$user_payload]);
}

// =============================================================================
// WoWonder Fetch Stateless SSO Payload Orchestration (WordPress → WoWonder)
// =============================================================================
// Try Login from BuzzSSO Cookie
$payload = null;

if (empty($sso_token)) {
    // Preserve deep link
    $requested = $_GET['redirect_to'] ?? $_SERVER['REQUEST_URI'] ?? '/streams/';
    $redirect_target = 'https://buzzjuice.net' . $requested;
    bz_bridge_log('No SSO token. Redirecting to WP login.', [
        'redirect_to' => $redirect_target
    ]);
    bz_redirect_to_wp_login($bridge_url, 'streams');
    exit;
}

if (!empty($sso_token) && $BUZZ_SSO_SECRET) {
    $payload = bz_validate_jwt($sso_token, $BUZZ_SSO_SECRET);
}
    if (!empty($payload)) {
        bz_bridge_log('bz_validate_jwt successful!', [
            'payload' => $payload
        ]);
    }

// ---------------------------------------------
// Try Login from WordPress Endpoint
// ---------------------------------------------
if (!$payload) {
    $payload_arr = bz_fetch_wp_stateless_payload($sso_token ?? null, $BUZZ_SSO_SECRET);
    // If WP explicitly says "not logged in" → redirect to WP login
    if (
        isset($payload_arr['status']) &&
        (int)$payload_arr['status'] === 401
    ) {
        // Preserve deep link
        $requested = $_GET['redirect_to'] ?? $_SERVER['REQUEST_URI'] ?? '/streams/';
        $redirect_target = 'https://buzzjuice.net' . $requested;
        bz_bridge_log('WP endpoint returned 401. Redirecting to WP login.', [
            'redirect_to' => $redirect_target
        ]);
        bz_redirect_to_wp_login($redirect_target);
        exit;
    }
    // If valid payload received → use it
    if (!empty($payload_arr['payload'])) {
        $payload = $payload_arr['payload'];
        
            if (!empty($payload)) {
                bz_bridge_log('WP endpoint successful!', [
                    'redirect_to' => $payload
                ]);
            }
            
    }
}
// ---------------------------------------------
// Try Login from BuzzSocial Endpoint
// ---------------------------------------------
if (!$payload) {
    $qd_url = 'https://buzzjuice.net/social/qd-sso-bridge.php?sso_action=get_payload_for_streams';
    $signature = hash_hmac('sha256', 'get_payload_for_streams', $BUZZ_SSO_SECRET);
    $ch = curl_init($qd_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Buzzjuice-Signature: ' . $signature]);
    $result = curl_exec($ch);
    curl_close($ch);
    $resp = json_decode($result, true);
    if (isset($resp['payload'])) $payload = $resp['payload'];
}
// Redirect to WordPress Login
if (!$payload) {
    bz_bridge_log('No BuzzSocial JWT/buzz_sso cookie present or missing secret', [
        'cookie_present' => !empty($sso_token),
        'BUZZ_SSO_SECRET' => (bool)$BUZZ_SSO_SECRET
    ]);
    bz_redirect_to_wp_login($bridge_url, 'streams');
}

// =========================================================
// SSO JWT CLAIM EXTRACTION & REQUIRED CLAIMS VALIDATION
// =========================================================
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



// ========================================================
// WoWonder <-> WordPress Mapping & Registration Helpers
// ========================================================

function bz_safe_username_from_login($login, $email = '') {
    $u = preg_replace('~[^a-z0-9_.-]~i', '', (string)$login);
    if (!$u && $email) {
        $local = strstr($email, '@', true);
        $u = preg_replace('~[^a-z0-9_.-]~i', '', (string)$local);
    }
    return substr($u ?: 'wpuser', 0, 20);
}

function bz_find_wo_user_by_id($wo_user_id) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if (!$wo_user_id) return 0;
    $stmt = mysqli_prepare($sqlConnect, "SELECT user_id FROM {$tbl} WHERE user_id=? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $wo_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id);
        $found = mysqli_stmt_fetch($stmt) ? (int)$id : 0;
        mysqli_stmt_close($stmt);
        return $found;
    }
    return 0;
}

function bz_find_wo_user_by_login_email($login, $email) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if ($login && $email) {
        $stmt = mysqli_prepare($sqlConnect, "SELECT user_id FROM {$tbl} WHERE username=? AND email=? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $login, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $id);
            $found = mysqli_stmt_fetch($stmt) ? (int)$id : 0;
            mysqli_stmt_close($stmt);
            return $found;
        }
    }
    return 0;
}

function bz_find_wo_user_any($wp_id, $email, $login) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    // Try by mapped WP user id
    if ($wp_id) {
        $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE wp_user_id=".(int)$wp_id." LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    }
    // Try by email
    if ($email) {
        $esc = mysqli_real_escape_string($sqlConnect, (string)$email);
        $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE email='{$esc}' LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    }
    // Try by login/username
    if ($login) {
        $esc = mysqli_real_escape_string($sqlConnect, (string)$login);
        $q = mysqli_query($sqlConnect, "SELECT user_id FROM {$tbl} WHERE username='{$esc}' LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['user_id'];
    }
    return 0;
}

function bz_update_wo_mapping($wo_user_id, $wp_user_id) {
    global $sqlConnect;
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    if ($wo_user_id && $wp_user_id) {
        @mysqli_query($sqlConnect, "UPDATE {$tbl} SET wp_user_id=".(int)$wp_user_id." WHERE user_id=".(int)$wo_user_id." LIMIT 1");
        bz_bridge_log('Updated Wo→WP mapping', ['wo_user_id'=>$wo_user_id,'wp_user_id'=>$wp_user_id]);
    }
}

// Helper for updating WP usermeta wo_user_id (tries WP helper, then fallback SQL)
function bz_set_wp_usermeta($wp_user_id, $meta_key, $meta_value) {
    global $conn;
    $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : ($conn ?? null);
    $did_write = false;
    // Preferred: plugin helper
    if ($wp_conn && function_exists('wp_update_usermeta')) {
        try { wp_update_usermeta($wp_conn, $wp_user_id, $meta_key, $meta_value); $did_write = true; } catch(Throwable $e){ bz_bridge_log('wp_update_usermeta error',['error'=>$e->getMessage()]); }
    }
    // WP runtime
    if (!$did_write && function_exists('update_user_meta')) {
        try { update_user_meta($wp_user_id, $meta_key, $meta_value); $did_write = true; } catch(Throwable $e){ bz_bridge_log('update_user_meta error',['error'=>$e->getMessage()]); }
    }
    // Fallback: direct SQL (still with basic escapes)
    if (!$did_write && $wp_conn && $wp_user_id) {
        $prefix = defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_';
        $um_table = defined('WP_DB_NAME') ? '`' . WP_DB_NAME . '`.`' . $prefix . 'usermeta`' : '`' . $prefix . 'usermeta`';
        $esc_val = mysqli_real_escape_string($wp_conn, $meta_value);
        $esc_key = mysqli_real_escape_string($wp_conn, $meta_key);
        $check_raw = "SELECT umeta_id FROM $um_table WHERE user_id = " . intval($wp_user_id) . " AND meta_key = '$esc_key' LIMIT 1";
        $res = @$wp_conn->query($check_raw);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $umeta_id = intval($row['umeta_id']);
            $raw_update = "UPDATE $um_table SET meta_value = '$esc_val' WHERE umeta_id = $umeta_id";
            @$wp_conn->query($raw_update);
            bz_bridge_log('Updated wp_usermeta wo_user_id (raw)', ['wp_user_id' => $wp_user_id, 'meta_key' => $meta_key]);
            $did_write = true;
        } else {
            $raw_insert = "INSERT INTO $um_table (user_id, meta_key, meta_value) VALUES (" . intval($wp_user_id) . ", '$esc_key', '$esc_val')";
            @$wp_conn->query($raw_insert);
            bz_bridge_log('Inserted wp_usermeta wo_user_id (raw)', ['wp_user_id' => $wp_user_id, 'meta_key' => $meta_key]);
            $did_write = true;
        }
    }
    if (!$did_write) bz_bridge_log('Failed to write wp_usermeta', ['wp_user_id'=>$wp_user_id,'meta_key'=>$meta_key]);
    return $did_write;
}

function bz_register_wo_user($wp_user_id, $login, $email) {
    global $wo, $conn;
    $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : ($conn ?? null);
    if (!function_exists('Wo_RegisterUser')) {
        bz_bridge_log('Wo_RegisterUser missing');
        return 0;
    }
    $username = bz_safe_username_from_login($login, $email);
    $base = substr($username, 0, 20);
    $i = 0;
    while (function_exists('Wo_UsernameExists') && Wo_UsernameExists($username)) {
        $i++;
        $username = $base . '-' . $i;
        if ($i > 200) { $username = $base . '-' . bin2hex(random_bytes(3)); break; }
    }
    // Attempt to fetch WordPress-side password hash for interop
    $password = bin2hex(random_bytes(8));
    if ($wp_conn && $wp_user_id) {
        $res = @mysqli_query($wp_conn, "SELECT user_pass FROM wp_users WHERE ID='" . intval($wp_user_id) . "' LIMIT 1");
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
    $user_data = (function_exists('wp_get_full_user_data') && $wp_conn) ? wp_get_full_user_data($wp_conn, $wp_user_id) : [];
    $avatar = $user_data['xprofile']['avatar'] ?? $user_data['meta']['avatar'] ?? ($wo['config']['userDefaultAvatar'] ?? '');
    $cover  = $user_data['xprofile']['cover'] ?? $user_data['meta']['cover'] ?? '';
    $re_data = [
        'username'       => $username,
        'password'       => $password,
        'email'          => $email,
        'avatar'         => $avatar,
        'cover'          => $cover,
        'active'         => 1,
        'src'            => 'wp-sso',
        'wp_user_id'     => (int)$wp_user_id,
        'ip_address'     => Wo_Secure($ip),
        'language'       => $language,
        'order_posts_by' => $wo['config']['order_posts_by'] ?? '',
        'registered'     => date('n') . '/' . date("Y"),
        'joined'         => time(),
    ];
    $created = Wo_RegisterUser($re_data);
    if ($created) {
        $wo_user_id = function_exists('Wo_UserIdFromEmail') ? Wo_UserIdFromEmail($email) : 0;
        if ($wo_user_id) {
            bz_set_wp_usermeta($wp_user_id, 'wo_user_id', (string)$wo_user_id);
            bz_update_wo_mapping($wo_user_id, $wp_user_id);
            if (function_exists('Wo_UpdateUserData')) {
                Wo_UpdateUserData($wo_user_id, ['wp_user_id' => (int)$wp_user_id, 'src' => 'wp-sso']);
            }
            if (!empty($wo['config']['auto_friend_users'])) Wo_AutoFollow($wo_user_id);
            if (!empty($wo['config']['auto_page_like']))  Wo_AutoPageLike($wo_user_id);
            if (!empty($wo['config']['auto_group_join'])) Wo_AutoGroupJoin($wo_user_id);
            bz_bridge_log('Auto-registered Wo user (success)', [
                'wp_user_id' => $wp_user_id,
                'wo_user_id' => $wo_user_id,
                'username'   => $username
            ]);
            return (int)$wo_user_id;
        }
    }
    bz_bridge_log('Auto-register failed', ['attempt' => $re_data]);
    return 0;
}



// --------------------------------------------------
// WO USER MAPPING & AUTO-REGISTRATION LOGIC
// --------------------------------------------------
$session_existing_wo = isset($_SESSION['wo_user_id']) ? (int)$_SESSION['wo_user_id'] : 0;
$final_wo_user_id = 0;

// -- 1. MAPPING BY PAYLOAD CLAIMS, LOGIN+EMAIL, FALLBACK
if ($canonical['wo_user_id']) {
    $verify = bz_find_wo_user_by_id($canonical['wo_user_id']);
    if ($verify) {
        bz_bridge_log('Payload/session wo_user_id verified', ['wo_user_id'=>$canonical['wo_user_id']]);
        $final_wo_user_id = $canonical['wo_user_id'];
    } else {
        bz_bridge_log('Payload wo_user_id not found; fallback to login/email or mapping', ['payload_wo'=>$canonical['wo_user_id']]);
        $found = bz_find_wo_user_by_login_email($canonical['wp_user_login'], $canonical['wp_user_email']);
        if ($found) {
            $final_wo_user_id = $found;
            bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
        } else {
            $found_any = bz_find_wo_user_any($canonical['wp_user_id'], $canonical['wp_user_email'], $canonical['wp_user_login']);
            if ($found_any) {
                $final_wo_user_id = $found_any;
                bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
            } elseif (BUZZ_SSO_AUTO_REGISTER) {
                if (!$session_existing_wo) {
                    bz_bridge_log('Auto-register Wo user (payload wo_user_id not found)', ['login'=>$canonical['wp_user_login'],'email'=>$canonical['wp_user_email']]);
                    $created = bz_register_wo_user($canonical['wp_user_id'], $canonical['wp_user_login'], $canonical['wp_user_email']);
                    if ($created) {
                        $final_wo_user_id = $created;
                        $payload['wo_user_id'] = (int)$final_wo_user_id;
                        // bz_issue_buzz_sso_cookie($payload, $BUZZ_SSO_SECRET, ['wo_user_id'=>$final_wo_user_id]);
                        $_SESSION['wo_auto_registered'] = 1;
                    }
                } else {
                    $final_wo_user_id = $session_existing_wo;
                    bz_bridge_log('Session conflict: preserve session wo_user_id', ['session_wo'=>$session_existing_wo]);
                }
            }
        }
    }
} else {
    $found = bz_find_wo_user_by_login_email($canonical['wp_user_login'], $canonical['wp_user_email']);
    if ($found) {
        $final_wo_user_id = $found;
        bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
    } else {
        $found_any = bz_find_wo_user_any($canonical['wp_user_id'], $canonical['wp_user_email'], $canonical['wp_user_login']);
        if ($found_any) {
            $final_wo_user_id = $found_any;
            bz_update_wo_mapping($final_wo_user_id, $canonical['wp_user_id']);
        } elseif (BUZZ_SSO_AUTO_REGISTER) {
            if (!$session_existing_wo) {
                bz_bridge_log('No user mapping found; auto-registering', ['login'=>$canonical['wp_user_login'],'email'=>$canonical['wp_user_email']]);
                $created = bz_register_wo_user($canonical['wp_user_id'], $canonical['wp_user_login'], $canonical['wp_user_email']);
                if ($created) {
                    $final_wo_user_id = $created;
                    $payload['wo_user_id'] = (int)$final_wo_user_id;
                    // bz_issue_buzz_sso_cookie($payload, $BUZZ_SSO_SECRET, ['wo_user_id'=>$final_wo_user_id]);
                    $_SESSION['wo_auto_registered'] = 1;
                }
            } else {
                $final_wo_user_id = $session_existing_wo;
                bz_bridge_log('Session already had wo_user_id', ['session_wo'=>$session_existing_wo]);
            }
        }
    }
}
if (!$final_wo_user_id) {
    bz_bridge_log('Unable to determine wo_user_id after mapping/registration', [
        'canonical'=>$canonical,'session'=>$_SESSION ?? []]);
    bz_redirect_to_wp_login('Unable to determine WoWonder account');
}

// -- 2. HYDRATE CANONICAL SESSION FIELDS FOR CURRENT UI
if (!isset($_SESSION['wp_user_login'])) $_SESSION['wp_user_login'] = (string)$canonical['wp_user_login'];
$_SESSION['wp_user_id']    = (int)$canonical['wp_user_id'];
$_SESSION['wp_user_email'] = (string)$canonical['wp_user_email'];
if (empty($_SESSION['wo_user_id'])) {
    $_SESSION['wo_user_id'] = (int)$final_wo_user_id;
    bz_bridge_log('Set session wo_user_id from mapping/registration', ['wo_user_id'=>$_SESSION['wo_user_id']]);
}

// -- 3. PULL WO USERNAME (NON-FATAL)
try {
    if (!empty($_SESSION['wo_user_id'])) {
        $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
        $q = @mysqli_query($sqlConnect, "SELECT username FROM {$tbl} WHERE user_id=".(int)$_SESSION['wo_user_id']." LIMIT 1");
        if ($q && ($r = mysqli_fetch_assoc($q)) && !empty($r['username'])) {
            $_SESSION['wo_username'] = $r['username'];
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

// -- 4. BUILD SSO TOKEN FOR BRIDGE/JS CLIENT (NOT FOR TRUST, FOR FORM SIMPLICITY)
$sso_username = $_SESSION['wp_user_login'];

// -- 5. ROBUST LAST_URL DERIVATION/NORMALIZATION (PRESERVED FROM ALL SOURCES BUT GUARDS AGAINST EXTERNAL/UNSAFE/RECURSION)
// SINGLE-PASS SAFE LAST_URL DERIVATION
$site_base = rtrim($wo['config']['site_url'], '/');
$last_url = '';

// Helper function: sanitize paths
function bz_sanitize_path($url) {
    return preg_replace('/[^\w\-\/:.@]/u', '', (string)$url);
}

// 1. Determine candidate URL
$candidate = null;

// Priority 1: redirect_to overrides everything
if (!empty($_GET['redirect_to'])) {
    $candidate = bz_sanitize_path($_GET['redirect_to']);
    // Never allow bridge self-reference
    if (strpos($candidate, 'ww-sso-bridge.php') !== false) $candidate = '/';
    // Ensure leading slash
    if ($candidate && $candidate[0] !== '/') $candidate = '/' . ltrim($candidate, '/');
}

// Priority 2: GET/POST/COOKIE last_url
if (!$candidate) {
    foreach (['GET' => $_GET, 'POST' => $_POST, 'COOKIE' => $_COOKIE] as $src_name => $src) {
        if (!empty($src['last_url'])) {
            $candidate = bz_sanitize_path($src['last_url']);
            break;
        }
    }
}

// Priority 3: HTTP_REFERER fallback
if (!$candidate && !empty($_SERVER['HTTP_REFERER'])) {
    $candidate = bz_sanitize_path($_SERVER['HTTP_REFERER']);
}

// Priority 4: fallback to site base
if (!$candidate) {
    $candidate = '/';
}

// 2. Normalize to absolute URL
if (strpos($candidate, 'http://') !== 0 && strpos($candidate, 'https://') !== 0) {
    $last_url = (strpos($candidate, '/') === 0)
        ? $site_base . $candidate
        : $site_base . '/' . ltrim($candidate, '/');
} else {
    $last_url = $candidate;
}

// 3. Enforce same-site constraint
$parsed_site = parse_url($site_base, PHP_URL_HOST);
$parsed_last = parse_url($last_url, PHP_URL_HOST);
if ($parsed_last && strcasecmp($parsed_last, $parsed_site) !== 0) {
    bz_bridge_log('last_url rejected: outside site', ['last_url' => $last_url, 'site_base' => $site_base]);
    $last_url = $site_base . '/';
}

// 4. Prevent bridge/self-reference
if (!empty($last_url) && function_exists('bz_is_bridge_url') && bz_is_bridge_url($last_url, $site_base)) {
    bz_bridge_log('last_url rejected: bridge/self-reference detected', ['last_url' => $last_url, 'site_base' => $site_base]);
    $last_url = $site_base . '/';
}

// 5. Forced fallback override
if (!empty($forced_last_url_fallback)) $last_url = $site_base . '/';

// -- 6. AJAX URL FOR CLIENT POST
$ajax_url_base = ($_SERVER['PHP_SELF'] ?? '/ww-sso-bridge.php') . '?sso_action=do_login';
$ajax_url = $ajax_url_base;
if (!empty($_GET['redirect_to'])) {
    $rt = bz_sanitize_path($_GET['redirect_to']);
    if ($rt !== '') $ajax_url .= '&redirect_to=' . rawurlencode($rt);
}

bz_bridge_log('SSO session prepared', [
    'sso_username'    => $sso_username,
    'sso_password_len'=> strlen($sso_token),
    'ajax_url'        => $ajax_url,
    'last_url'        => $last_url
]);

// -- 7. CLIENT HANDLES DEFERRED REDIRECT
if (!empty($_GET['redirect_to'])) {
    $raw_requested = (string)$_GET['redirect_to'];
    $requested = bz_sanitize_path($raw_requested);
    $ajax_preview = $ajax_url ?? '(not set)';
    bz_bridge_log('redirect_to present; deferring to HTML/JS client', [
        'raw'              => $raw_requested,
        'sanitized'        => $requested,
        'ajax_url_preview' => $ajax_preview,
        'session_preview'  => [
            'wp_user_id' => $_SESSION['wp_user_id'] ?? null,
            'wo_user_id' => $_SESSION['wo_user_id'] ?? null
        ]
    ]);
    // The redirect is handed off to the JS bridge/client
}



// -----------------------------
// Wo_SSO_Login endpoint (POST)
// if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') { Wo_SSO_Login(); exit; }
Wo_SSO_Login();

function send_json_response($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function Wo_SSO_Login() {
    global $wo, $sqlConnect, $BUZZ_SSO_SECRET, $last_url;
    $errors = [];

    // 1. Basic POST parsing 
    $username        = isset($_POST['username']) ? Wo_Secure($_POST['username']) : '';
    $password        = isset($_POST['password']) ? trim($_POST['password']) : '';
    $posted_last_url = isset($_POST['last_url']) ? trim((string)$_POST['last_url']) : '';

    bz_bridge_log('Wo_SSO_Login: credentials received', [
        'username'     => $username,
        'password_len' => is_string($password) ? strlen($password) : 0,
        'session'      => $_SESSION ?? []
    ]);

    // 2. JWT token validation (stateless SSO)
    // Accept JWT as 'sso_token' in POST, fallback to buzz_sso cookie.
    $token = $_POST['sso_token'] ?? $_COOKIE[BUZZ_SSO_COOKIE] ?? '';
    if (!is_string($token) || !$BUZZ_SSO_SECRET) {
        bz_bridge_log('Wo_SSO_Login: missing or invalid JWT token or SSO secret', ['username' => $username]);
        send_json_response(['errors' => ['Invalid SSO token or misconfigured secret']]);
    }
    $claims = bz_validate_jwt($token, $BUZZ_SSO_SECRET);
    if (!$claims) {
        bz_bridge_log('Wo_SSO_Login: JWT token validation failed', ['username' => $username]);
        send_json_response(['errors' => ['Invalid or expired SSO token']]);
    }

    // 3. Prefer canonical session values if set
    $sess_wo    = isset($_SESSION['wo_user_id']) ? (int)$_SESSION['wo_user_id'] : 0;
    $sess_wp    = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : 0;
    $sess_login = isset($_SESSION['wp_user_login']) ? (string)$_SESSION['wp_user_login'] : '';
    $sess_email = isset($_SESSION['wp_user_email']) ? (string)$_SESSION['wp_user_email'] : '';
    $exp_wo     = $sess_wo ?: (isset($claims['wo_user_id'])    ? (int)$claims['wo_user_id']    : 0);
    $exp_wp     = $sess_wp ?: (isset($claims['wp_user_id'])     ? (int)$claims['wp_user_id']     : 0);
    $exp_login  = $sess_login ?: (isset($claims['wp_user_login']) ? (string)$claims['wp_user_login'] : '');
    $exp_email  = $sess_email ?: (isset($claims['wp_user_email']) ? (string)$claims['wp_user_email'] : '');

    bz_bridge_log('Wo_SSO_Login: expected values', [
        'exp_wo'=>$exp_wo,'exp_wp'=>$exp_wp,'exp_login'=>$exp_login,'exp_email'=>$exp_email
    ]);

    // 4. Find a matching WoWonder user: ≥2 identifier fields must match
    $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';
    $candidates = [];
    if ($exp_wo) {
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE user_id={$exp_wo} LIMIT 1"); 
        if ($q && $r = mysqli_fetch_assoc($q)) $candidates[] = $r;
    }
    if (empty($candidates) && $exp_email) {
        $esc = mysqli_real_escape_string($sqlConnect, $exp_email);
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE email='{$esc}' LIMIT 1"); 
        if ($q && $r = mysqli_fetch_assoc($q)) $candidates[] = $r;
    }
    if (empty($candidates) && $exp_login) {
        $esc = mysqli_real_escape_string($sqlConnect, $exp_login);
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE username='{$esc}' LIMIT 1"); 
        if ($q && $r = mysqli_fetch_assoc($q)) $candidates[] = $r;
    }
    if (empty($candidates) && $exp_wp) {
        $q = mysqli_query($sqlConnect, "SELECT user_id,username,email,wp_user_id FROM {$tbl} WHERE wp_user_id={$exp_wp} LIMIT 1"); 
        if ($q && $r = mysqli_fetch_assoc($q)) $candidates[] = $r;
    }
    bz_bridge_log('Wo_SSO_Login: candidates fetched', ['count'=>count($candidates),'candidates'=>$candidates]);

    $accepted_user_id = 0; $accepted_row = null; $accepted_reason = [];
    foreach ($candidates as $row) {
        $db_user_id   = (int)$row['user_id'];
        $db_username  = (string)$row['username'];
        $db_email     = (string)$row['email'];
        $db_wp_userid = (int)$row['wp_user_id'];

        $matches = [
            ($exp_wo    && $db_user_id === $exp_wo),
            ($exp_email && strcasecmp($db_email, $exp_email) === 0),
            ($exp_login && strcasecmp($db_username, $exp_login) === 0),
            ($exp_wp    && $db_wp_userid === $exp_wp),
        ];
        $match_count = 0;
        foreach ($matches as $m) $match_count += $m ? 1 : 0;
        if ($match_count >= 2) {
            $accepted_user_id = $db_user_id;
            $accepted_row = $row;
            $accepted_reason = $matches;
            break;
        }
    }

    if (!$accepted_user_id) {
        bz_bridge_log('Wo_SSO_Login: no match (≥2 required)', [
            'expected'=>['wo'=>$exp_wo,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email]
        ]);
        send_json_response(['errors'=>['No matching WoWonder account for SSO (≥2 identifiers required). Please contact support.']]);
    }

    // 5. Set session, minimal $wo runtime
    $ip = function_exists('get_ip_address') ? Wo_Secure(get_ip_address()) : '0.0.0.0';
    @mysqli_query($sqlConnect, "UPDATE {$tbl} SET `ip_address` = '".Wo_Secure($ip)."' WHERE `user_id` = '".intval($accepted_user_id)."'");
    cache($accepted_user_id, 'users', 'delete');

    $session_token = Wo_CreateLoginSession($accepted_user_id);
    $_SESSION['wo_session_token'] = $session_token;
    $_SESSION['wo_user_id'] = (int)$accepted_user_id;
    $_SESSION['wp_Wo_SSO_Login'] = true;

    // Minimal $wo state for the rest of Wo code
    try {
        if (!is_array($wo)) $wo = [];
        $wo['loggedin'] = true;
        $wo['user'] = [
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
        $safe_q = @mysqli_query($sqlConnect, "SELECT is_pro,admin FROM {$tbl} WHERE user_id=".(int)$accepted_user_id." LIMIT 1");
        if ($safe_q && $r_safe = mysqli_fetch_assoc($safe_q)) {
            if (isset($r_safe['is_pro'])) $wo['user']['is_pro'] = (int)$r_safe['is_pro'];
            if (isset($r_safe['admin']))  $wo['user']['admin']  = (int)$r_safe['admin'];
        }
    } catch (Throwable $e) {
        bz_bridge_log('Wo_SSO_Login: error user snapshot', ['ex'=>$e->getMessage()]);
        $wo['loggedin'] = true;
        $wo['user'] = ['user_id' => (int)$accepted_user_id, 'id' => (int)$accepted_user_id];
    }

    // ----------------------------------------------------------------------------
    // 6. FULL WORDPRESS → WOWONDER PROFILE METADATA SYNC POST-LOGIN
    //    - Source of truth: WordPress (via JWT/session)
    //    - Schema: shared/buzz_metadata.json (public_open_fields, private_secure_fields)
    //    - Only existing WoWonder columns updated (bz_column_exists)
    //    - Logs every action/skipped field/error with robust error control
    // POST-SSO METADATA SYNC: WordPress → WoWonder (always after Wo_CreateLoginSession())
    // ---------------------------------------------------------------------------
    try {
        // 1. Resolve WoWonder user ID (from session or WordPress linkage)
        $wo_user_id = null;
        if (!empty($_SESSION['wo_user_id'])) {
            $wo_user_id = (int)$_SESSION['wo_user_id'];
        } elseif (!empty($_SESSION['wp_user_id'])) {
            // Use meta bridge if available
            if (function_exists('get_user_meta')) {
                $wo_user_id = (int)get_user_meta($_SESSION['wp_user_id'], 'wo_user_id', true);
            }
        }
        if (!$wo_user_id || $wo_user_id < 1) {
            bz_bridge_log('SSO metadata sync ABORT: No wo_user_id found', [
                'wp_user_id' => $_SESSION['wp_user_id'] ?? null
            ]);
            return;
        }
    
        // 2. Gather all user fields to sync, from buzz_metadata.json (helper preferred)
        $meta_fields = [];
        if (function_exists('get_user_field_metadata')) {
            $meta_def = get_user_field_metadata();
            $public_fields = $meta_def['public_open_fields'] ?? [];
            $private_fields = $meta_def['private_secure_fields'] ?? [];
            $meta_fields = array_unique(array_merge($public_fields, $private_fields));
        } else {
            $meta_json_file = dirname(__DIR__).'/shared/buzz_metadata.json';
            if (is_file($meta_json_file)) {
                $meta_json = json_decode(@file_get_contents($meta_json_file), true);
                $meta_fields = array_unique(array_merge(
                    $meta_json['public_open_fields'] ?? [],
                    $meta_json['private_secure_fields'] ?? []
                ));
            }
        }
        if (empty($meta_fields)) {
            bz_bridge_log('SSO metadata sync ABORT: No fields loaded from buzz_metadata.json');
            return;
        }
    
        // 3. Obtain WordPress-as-truth values from SSO session
        $sync_data = [];
        foreach ($meta_fields as $k) {
            if (!array_key_exists($k, $_SESSION)) continue;
            $val = $_SESSION[$k];
            if ($val === '' || $val === null) continue;
            $sync_data[$k] = is_string($val) ? trim($val) : $val;
        }
        // Always sync core identity if present
        foreach (['wp_user_id','wp_user_login','wp_user_email'] as $ck) {
            if (!empty($_SESSION[$ck])) $sync_data[$ck] = $_SESSION[$ck];
        }
        if (empty($sync_data)) {
            bz_bridge_log('SSO metadata sync: No non-empty values in session for mapped fields', ['wo_user_id'=>$wo_user_id]);
            return;
        }
    
        // 4. Ensure fields exist in WoWonder user table
        $final_data = [];
        if (function_exists('bz_column_exists')) {
            foreach ($sync_data as $k => $v) {
                if (bz_column_exists(T_USERS, $k)) {
                    $final_data[$k] = $v;
                } else {
                    bz_bridge_log('SSO metadata sync: Skipped field not in WoWonder', ['field'=>$k]);
                }
            }
        } else {
            $final_data = $sync_data;
            bz_bridge_log('SSO metadata sync: bz_column_exists unavailable, skipping column checks');
        }
        if (empty($final_data)) {
            bz_bridge_log('SSO metadata sync: No valid fields after WoWonder check', [
                'wp_user_id'=>$_SESSION['wp_user_id'] ?? null, 'wo_user_id'=>$wo_user_id
            ]);
            return;
        }
    
        // 5. Update WoWonder profile
        if (function_exists('Wo_UpdateUserData')) {
            try {
                $result = Wo_UpdateUserData($wo_user_id, $final_data);
                bz_bridge_log('SSO metadata sync: WoWonder update complete', [
                    'wo_user_id' => $wo_user_id,
                    'fields'     => array_keys($final_data),
                    'result'     => $result
                ]);
            } catch (Throwable $e) {
                bz_bridge_log('SSO metadata sync: WoWonder update ERROR', [
                    'wo_user_id' => $wo_user_id,
                    'error'      => $e->getMessage(),
                ]);
            }
        } else {
            bz_bridge_log('SSO metadata sync: Wo_UpdateUserData unavailable, skipped Wo update', ['wo_user_id' => $wo_user_id]);
        }
    
        // 6. Mirror to WP usermeta (bi-directional consistency, if desired)
        if (!empty($_SESSION['wp_user_id']) && function_exists('bz_set_wp_usermeta')) {
            foreach ($final_data as $meta_key => $meta_value) {
                try {
                    bz_set_wp_usermeta($_SESSION['wp_user_id'], $meta_key, $meta_value);
                } catch (Throwable $ex) {
                    bz_bridge_log('SSO metadata sync: Failed wp_usermeta update', [
                        'wp_user_id' => $_SESSION['wp_user_id'],
                        'meta_key'   => $meta_key,
                        'err'        => $ex->getMessage()
                    ]);
                }
            }
        }
    
    } catch (Throwable $ex) {
        bz_bridge_log('SSO metadata sync: Fatal uncaught exception', [
            'error'      => $ex->getMessage(),
            'trace'      => $ex->getTraceAsString()
        ]);
    }



    // 7. Remember-me device cookie
    if (!empty($_POST['remember_device']) && $_POST['remember_device'] == 'on' && !empty($wo['config']['remember_device']) && $wo['config']['remember_device'] == 1) {
        setcookie('user_id', $session_token, time() + (10*365*24*60*60), '/', BUZZ_COOKIE_DOMAIN, true, true);
    }

    // ------------------------------
    // Wo_SSO_Login() — JSON redirect resolution
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
    
    // ------------------------------
    // 1) REQUEST redirect_to override (highest priority)
    // ------------------------------
    if (!empty($_REQUEST['redirect_to'])) {
        $candidate = (string)$_REQUEST['redirect_to'];
        // Never allow the bridge file as redirect
        if (strpos($candidate, 'ww-sso-bridge.php') !== false) {
            $candidate = '/';
        }
        if ($candidate[0] !== '/') $candidate = '/' . ltrim($candidate, '/');
        $location = rtrim($site_base, '/') . $candidate;
    
        // Log and return immediately for JSON
        bz_bridge_log('Wo_SSO_Login: redirect_to override applied', [
            'redirect_to' => $_REQUEST['redirect_to'],
            'resolved' => $location
        ]);
        echo json_encode(['status' => 200, 'location' => $location]);
        exit;
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
    if (!empty($posted_last_url)) {
        $candidate_raw = trim((string)$posted_last_url);
    
        if (strpos($candidate_raw, '//') === 0) {
            bz_bridge_log('posted_last_url rejected: protocol-relative URL', ['posted' => $candidate_raw]);
        } else {
            if (strpos($candidate_raw, '/') === 0) {
                $candidate_abs = rtrim($site_base, '/') . $candidate_raw;
            } else {
                $candidate_abs = $candidate_raw;
            }
    
            $scheme = @parse_url($candidate_abs, PHP_URL_SCHEME);
            $site_host = bz_normalize_host(parse_url($site_base, PHP_URL_HOST) ?: '');
            $candidate_host = bz_normalize_host(parse_url($candidate_abs, PHP_URL_HOST) ?: '');
            $is_bridge_ref = bz_is_bridge_url($candidate_abs, $site_base);
    
            if ($candidate_abs && in_array($scheme, ['http','https'], true) && $candidate_host === $site_host && !$is_bridge_ref) {
                $data['location'] = $candidate_abs;
                bz_bridge_log('Wo_SSO_Login: using posted_last_url as redirect', ['posted_last_url' => $candidate_raw, 'final' => $data['location']]);
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
            }
        }
    }
    
    // 5) last_url fallback or default
    $data['location'] = !empty($last_url) && strpos($last_url, $site_base) === 0 ? $last_url : ($site_base . '/?cache=' . time());
    
    // Final safety: never return the bridge itself as redirect
    if (function_exists('bz_is_bridge_url') && !empty($data['location']) && bz_is_bridge_url($data['location'], $site_base)) {
        bz_bridge_log('Final redirect would go to bridge; replacing with site base to avoid loop', ['chosen' => $data['location']]);
        $data['location'] = rtrim($site_base, '/') . '/';
        if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
    }
    
    bz_bridge_log('Wo_SSO_Login: final redirect chosen', ['final' => $data['location']]);
    echo json_encode($data); exit;
}



bz_bridge_log('Rendering bridge page', [
    'sso_username'    => $sso_username,
    'sso_password_len'=> strlen($sso_token),
    'last_url'        => $last_url
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
              'username' => $sso_username,
              'password' => '(sso-token:len='.strlen($sso_token).')',
              'last_url' => $last_url,
              'remember_device' => 'on'
          ],
          'session' => $_SESSION ?? [],
          'cookies' => $_COOKIE
      ], true)); ?></pre></div>
    <?php endif; ?>
  </div>
  <script>
  (function(){
    var ajaxUrl    = <?php echo json_encode($ajax_url); ?>;
    var ssoUser    = <?php echo json_encode($sso_username); ?>;
    var ssoPwd     = <?php echo json_encode($sso_token); ?>;
    var lastUrl    = <?php echo json_encode($last_url); ?>;
    var beaconUrl  = <?php echo json_encode((isset($_SERVER['PHP_SELF'])?$_SERVER['PHP_SELF']:'/ww-sso-bridge.php') . '?sso_client_log=1'); ?>;
    var statusEl   = document.getElementById('status');

    // Prevent bridge infinite loops
    if (lastUrl && lastUrl.indexOf('ww-sso-bridge.php') !== -1) lastUrl = undefined;

    function beacon(msg, extra){
      try{
        var data = JSON.stringify({msg:msg,extra:extra||{},when:Date.now()});
        if (navigator.sendBeacon) navigator.sendBeacon(beaconUrl, data);
        else { var x = new XMLHttpRequest(); x.open('POST', beaconUrl, true); x.setRequestHeader('Content-Type','text/plain'); x.send(data); }
      }catch(e){}
    }
    statusEl && (statusEl.textContent = 'Contacting server…');
    beacon('bridge:init', {ajaxUrl: ajaxUrl, u: ssoUser, last: lastUrl});

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
    xhr.onerror   = function(){ beacon('bridge:error', {http: xhr.status}); statusEl && (statusEl.className='status err', statusEl.textContent='Network or server error.'); };
    xhr.ontimeout = function(){ beacon('bridge:timeout', {}); statusEl && (statusEl.className='status err', statusEl.textContent='Request timed out.'); };

    // encode only valid params
    var formParams = [];
    formParams.push('username=' + encodeURIComponent(ssoUser));
    formParams.push('password=' + encodeURIComponent(ssoPwd));
    formParams.push('remember_device=on');
    if (typeof lastUrl === 'string') formParams.push('last_url=' + encodeURIComponent(lastUrl));
    xhr.send(formParams.join('&'));
  })();
  </script>
</body>
</html>