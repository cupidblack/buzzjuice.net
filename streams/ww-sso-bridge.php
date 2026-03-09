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

// ======================================
// START: CONFIGURATIONS / DEFAULTS
// ======================================
if (!defined('WP_BASE_SITE_URL'))       define('WP_BASE_SITE_URL', 'https://buzzjuice.net');
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/ww_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL_ACCESS'))    define('BUZZ_SSO_TTL_ACCESS', 12345);
if (!defined('BUZZ_SSO_TTL_REFRESH'))   define('BUZZ_SSO_TTL_REFRESH', 216000);

$base_site_url      = defined('WP_BASE_SITE_URL') ? WP_BASE_SITE_URL : (getenv('WP_BASE_SITE_URL') ?: null);
$base_streams_url   = rtrim($wo['config']['site_url'] ?? WP_BASE_SITE_URL ?? '', '/');
$BUZZ_SSO_SECRET    = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);																																			 
$BUZZ_SSO_SECRET    = (string)($BUZZ_SSO_SECRET ?? '');
$sso_token          = trim($_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? ''));
$sso_action         = $_REQUEST['sso_action'] ?? '';

// -----------------------------------------------
// HELPERS: LOGGING, DEBUG + SESSION VISIBILITY
// -----------------------------------------------
// client-side debug beacon receiver
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

// ***** Replay protection: JTI store (30 min) ***** TODO																																																										
define('BUZZ_JTI_STORE', __DIR__ . '/../data/sso_jti_store');
if (!is_dir(BUZZ_JTI_STORE)) @mkdir(BUZZ_JTI_STORE, 0755, true);
if (mt_rand(1, 35) === 9) bz_cleanup_jti_store();

// -------------------------------------------------------
// BOOTSTRAP CHECKS — REQUIRE Wo CONFIG AND SQL
// -------------------------------------------------------
global $wo, $sqlConnect;
if (empty($wo['config']['site_url']) || empty($sqlConnect) || empty($BUZZ_SSO_SECRET)) {
    bz_bridge_log('Bootstrap incomplete - Missing critical SSO configuration $wo/$sqlConnect/secret.');
    bz_debug_page('Bootstrap incomplete', ['$wo' => $wo ?? null, '$sqlConnect' => (bool)$sqlConnect]);
    bz_redirect_to_wp_login($base_site_url, 'streams');
    exit;			 
}

// ===============================
// last_url Derivation & Normalization Block
// ===============================

// Ensure site base is known
$site_host = parse_url($base_streams_url, PHP_URL_HOST) ?: '';

// 1) Check explicit last_url/redirect_to from GET, POST, COOKIE
$last_url = '';
foreach (['last_url', 'redirect_to'] as $param) {
    if (!empty($_GET[$param]))  { $last_url = (string)$_GET[$param]; break; }
    if (!empty($_POST[$param])) { $last_url = (string)$_POST[$param]; break; }
    if (!empty($_COOKIE[$param])) { $last_url = (string)$_COOKIE[$param]; break; }
}

// 2) Fallback to HTTP_REFERER ONLY if same-site
if (!$last_url && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = trim((string)$_SERVER['HTTP_REFERER']);
    $referer_host = parse_url($referer, PHP_URL_HOST);
    if ($referer_host && strcasecmp($referer_host, $site_host) === 0) {
        $last_url = $referer;
    }
}

// 3) Fallback to REQUEST_URI (not bridge/self-reference)
if (!$last_url) {
    $req_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $bridge_path = parse_url($_SERVER['PHP_SELF'] ?? '', PHP_URL_PATH);
    if ($req_uri && $req_uri !== $bridge_path && strpos($req_uri, basename(__FILE__)) === false) {
        $candidate = $base_streams_url . $req_uri;
        $candidate_host = parse_url($candidate, PHP_URL_HOST);
        if ($candidate_host && strcasecmp($candidate_host, $site_host) === 0) {
            $last_url = $candidate;
        }
    }
}

// 4) Normalize relative paths to absolute; enforce same-site
if ($last_url) {
    if (!preg_match('#^https?://#i', $last_url)) {
        $last_url = strpos($last_url, '/') === 0
            ? $base_streams_url . $last_url
            : $base_streams_url . '/' . ltrim($last_url, '/');
    }
    $candidate_host = parse_url($last_url, PHP_URL_HOST);
    if (!$candidate_host || strcasecmp($candidate_host, $site_host) !== 0) {
        $last_url = '';
    }
}

// 5) Prevent bridge/self-reference loop
if (!empty($last_url) && function_exists('bz_is_bridge_url') && bz_is_bridge_url($last_url, $base_streams_url)) {
//    bz_bridge_log('last_url rejected: bridge/self-reference detected', ['last_url'=>$last_url, 'site_base'=>$base_streams_url]);
    $last_url = $base_streams_url . '/';
}

// 6) Final fallback
if (!$last_url || !empty($forced_last_url_fallback)) {
    $last_url = $base_streams_url . '/';
}

// 7) Persist for use after SSO login
$_SESSION['last_url'] = $last_url;
//bz_bridge_log('last_url derivation complete', ['last_url' => $last_url]);

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

// =====================================================================================================
// END: CONFIGURATIONS + LOGGING + graceful failure (no legacy HMAC/token helpers below this point!)
// =====================================================================================================



// ===================================================================
// START: SESSION MANAGEMENT / ENDPOINTS / PAYLOAD / DATA MAPPING
// ===================================================================
// -----------------------------------------------------
// STEP 1: SESSION SAFETY GUARD FOR DUAL-TOKEN JWT SSO
// -----------------------------------------------------
// --- Current WoWonder authentication state ---
$wo_loggedin        = !empty($wo['loggedin']);
$wo_user_id         = $wo['user']['user_id'] ?? null;

// --- Current PHP session state ---
$session_wo_user_id = $_SESSION['wo_user_id'] ?? null;
$session_user_id    = $_SESSION['user_id'] ?? null;

// --- Detect WordPress login cookie (authority signal for SSO) ---
$wordpress_logged_in_ = false;
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'wordpress_logged_in_') === 0) {
        $wordpress_logged_in_ = true;
        break;
    }
}

// ---------------------------------------------------------------
// CASE A: WordPress logged in — check WoWonder session
// ---------------------------------------------------------------
if ($wordpress_logged_in_) {

    // --- CASE A.1: Both WP and WoWonder session fully active ---
    $already_logged_in = (
        $wo_loggedin &&
        !empty($wo_user_id) &&
        !empty($session_user_id) &&
        !empty($session_wo_user_id) &&
        ((string)$session_wo_user_id === (string)$wo_user_id)
    );

    if ($already_logged_in) {
        bz_bridge_log(
            'Both WordPress & WoWonder sessions confirmed; safe redirect.',
            [
                'wo_loggedin'        => $wo_loggedin,
                'wo_user_id'         => $wo_user_id,
                'session_wo_user_id' => $session_wo_user_id,
                'session_user_id'    => $session_user_id,
                'request_uri'        => $_SERVER['REQUEST_URI'] ?? null,
                'http_referer'       => $_SERVER['HTTP_REFERER'] ?? null,
                'redirect_to'        => $_GET['redirect_to'] ?? null
            ]
        );

        $redirect = $_SESSION['last_url'] ?? '/streams';
        if (!is_string($redirect) || strpos($redirect, '/') !== 0) {
            $redirect = '/streams';
        }

        header('Location: ' . $redirect);
        exit;
    }

    // --- CASE A.2: WP logged in but WoWonder session not active ---
//bz_bridge_log('WP session active, WW session inactive — proceeding to SSO bootstrap.', ['wo_loggedin' => $wo_loggedin, 'wo_user_id' => $wo_user_id, 'session_user_id' => $session_user_id, 'session_wo_user_id' => $session_wo_user_id, ]); 
    // Allow SSO bootstrap code (dual-token flow) to run next
}

// ---------------------------------------------------------------
// CASE B: WordPress not logged in — clear any stale WoWonder session
// ---------------------------------------------------------------
bz_bridge_log(
    'WP or WW session inactive — proceeding to SSO login.',
    [
        'wo_loggedin'        => $wo_loggedin,
        'wo_user_id'         => $wo_user_id,
        'session_user_id'    => $session_user_id,
        'session_wo_user_id' => $session_wo_user_id,
    ]
);

// --- Explicitly destroy stale WoWonder session ---
/*if (!empty($session_wo_user_id) || !empty($session_user_id)) {
    session_destroy();
}
*/
// Proceed to SSO bootstrap — user must re-login

// =============================================================================
// BuzzStreams Fetch Stateless SSO Payload Orchestration (WordPress → WoWonder)
// =============================================================================
$audience = 'streams';
$BUZZ_SSO_SECRET = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

$access_token  = $_COOKIE['buzz_access'] ?? $_REQUEST['buzz_access'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? $_REQUEST[BUZZ_SSO_COOKIE] ?? null);
$refresh_token = $_COOKIE['buzz_refresh'] ?? $_REQUEST['buzz_refresh'] ?? null;

$access_payload = false;

// 1. Try validating access token for current bridge OR universal audience
if ($access_token) {
    $access_payload = bz_sso_jwt_validate($access_token, $BUZZ_SSO_SECRET, $audience, 'access');
    if (!$access_payload) {
        // Accept universal 'buzznet' audience token as fallback
        $access_payload = bz_sso_jwt_validate($access_token, $BUZZ_SSO_SECRET, 'buzznet', 'access');
    }
}

// 2. If access still invalid, try silent local minting using refresh token
if (!$access_payload && $refresh_token) {
    $refresh_payload = bz_sso_jwt_validate($refresh_token, $BUZZ_SSO_SECRET, $audience, 'refresh');
    if (!$refresh_payload) {
        // Accept universal audience for refresh token as fallback
        $refresh_payload = bz_sso_jwt_validate($refresh_token, $BUZZ_SSO_SECRET, 'buzznet', 'refresh');
    }
    if ($refresh_payload) {
        $new_payload = [
            'wp_user_id'    => $refresh_payload['wp_user_id'] ?? null,
            'wp_user_login' => $refresh_payload['wp_user_login'] ?? null,
            'wp_user_email' => $refresh_payload['wp_user_email'] ?? null,
            'wo_user_id'    => $refresh_payload['wo_user_id'] ?? null,
            'qd_user_id'    => $refresh_payload['qd_user_id'] ?? null
        ];
        $new_access = bz_sso_jwt_encode($new_payload, $BUZZ_SSO_SECRET, $audience, BUZZ_SSO_TTL_ACCESS, 'access');
        bz_sso_set_cookie('buzz_access', $new_access, time()+BUZZ_SSO_TTL_ACCESS);
        $access_payload = bz_sso_jwt_validate($new_access, $BUZZ_SSO_SECRET, $audience, 'access');
    }
}

// 3. If still invalid, call WordPress endpoint. Use streams OR buzznet for /issue_tokens
if (!$access_payload && $wordpress_logged_in_) {
    $wp_token_url = 'https://buzzjuice.net/?sso_action=issue_tokens&aud=' . urlencode($audience);
    $context = stream_context_create([
        'http'=>['cookie'=>$_SERVER['HTTP_COOKIE'] ?? '']
    ]);
    $resp = @file_get_contents($wp_token_url, false, $context);
    $data = json_decode($resp, true);
    if (!empty($data['access'])) {
        bz_sso_set_cookie('buzz_access',$data['access'],time()+BUZZ_SSO_TTL_ACCESSL);
        if (!empty($data['refresh'])) {
            bz_sso_set_cookie('buzz_refresh',$data['refresh'],time()+BUZZ_SSO_TTL_REFRESH);
        }
        $access_payload = bz_sso_jwt_validate($data['access'], $BUZZ_SSO_SECRET, $audience, 'access');
        if (!$access_payload) {
            $access_payload = bz_sso_jwt_validate($data['access'], $BUZZ_SSO_SECRET, 'buzznet', 'access');
        }
    }
}

// 4. Fail → redirect user to login
if (!$access_payload) {
    bz_bridge_log('Dual-token bootstrap failed — redirecting to login');
    header('Location: /wp-login.php?try=1&redirect_to=/wp-login.php?redirect_to=/streams');
    exit;
}

// Hydrate canonical session for downstream mapping
$_SESSION['wp_user_id']    = (int)($access_payload['wp_user_id'] ?? 0);
$_SESSION['wp_user_login'] = (string)($access_payload['wp_user_login'] ?? '');
$_SESSION['wp_user_email'] = (string)($access_payload['wp_user_email'] ?? '');
$_SESSION['wo_user_id']    = (int)($access_payload['wo_user_id'] ?? 0);
$_SESSION['wp_Wo_SSO_Login'] = true;

// -----------------------------
// Required claims guard
// -----------------------------
if (!$_SESSION['wp_user_id'] || !$_SESSION['wp_user_login'] || !$_SESSION['wp_user_email']) {
    bz_bridge_log('Missing required claims (cookie incomplete)', $access_payload);
    header('Location: /wp-login.php?try=2&redirect_to=/wp-login.php?redirect_to=/streams');
    exit;
}

/* bz_bridge_log('buzz_access token claims hydrated into session', [
    'wp_user_id'      => $_SESSION['wp_user_id'],
    'wp_user_login'   => $_SESSION['wp_user_login'],
    'wp_user_email'   => $_SESSION['wp_user_email'],
    'wo_user_id'      => $_SESSION['wo_user_id'],
    'raw_payload'     => $access_payload
]);
*/


// =======================================================================================
// SSO: Auto-register WoWonder user if missing
// Updates WordPress usermeta 'wo_user_id' after successful registration
// Redirects to /wp-login.php?redirect_to=/members/me/settings/ if registration fails
// =======================================================================================

if (empty($access_payload['wo_user_id']) && BUZZ_SSO_AUTO_REGISTER) {
    $wp_user_id   = !empty($access_payload['wp_user_id']) ? (int)$access_payload['wp_user_id'] : 0;
    $max_attempts = 5;
    $attempt      = 0;
    $wo_user_id   = 0;

    // Helper: fetch WordPress usermeta 'wo_user_id'
    function bz_fetch_wp_wo_user_id($wp_user_id) {
        $wp_conn = get_wp_db_conn();
        if (!$wp_conn || empty($wp_user_id)) return 0;
        $meta_key = 'wo_user_id';
        $key_esc = mysqli_real_escape_string($wp_conn, $meta_key);
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        $query = "SELECT meta_value FROM {$table} WHERE user_id = $wp_user_id AND meta_key = '$key_esc' LIMIT 1";
        $result = mysqli_query($wp_conn, $query);
        if ($result && $row = mysqli_fetch_assoc($result)) return (int)$row['meta_value'];
        return 0;
    }

    // Helper: upsert 'wo_user_id' usermeta in WordPress
    function bz_update_wp_wo_user_id($wp_user_id, $wo_user_id) {
        $wp_conn = get_wp_db_conn();
        if (!$wp_conn || empty($wp_user_id) || empty($wo_user_id)) return false;
        $wp_user_id = (int)$wp_user_id;
        $wo_user_id = (int)$wo_user_id;
        $meta_key = 'wo_user_id';
        $key_esc = mysqli_real_escape_string($wp_conn, $meta_key);
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        $check_query = "SELECT umeta_id FROM {$table} WHERE user_id = $wp_user_id AND meta_key = '$key_esc' LIMIT 1";
        $check_result = mysqli_query($wp_conn, $check_query);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $row = mysqli_fetch_assoc($check_result);
            $umeta_id = (int)$row['umeta_id'];
            return (bool)mysqli_query($wp_conn, "UPDATE {$table} SET meta_value = '$wo_user_id' WHERE umeta_id = $umeta_id");
        } else {
            return (bool)mysqli_query($wp_conn, "INSERT INTO {$table} (user_id, meta_key, meta_value) VALUES ($wp_user_id, '$key_esc', '$wo_user_id')");
        }
    }

    while ($attempt < $max_attempts) {
        // Defensive check for existing mapping (race safety)
        $current_wo_id = ($wp_user_id ? bz_fetch_wp_wo_user_id($wp_user_id) : 0);
        if (!empty($current_wo_id)) {
            $wo_user_id = $current_wo_id;
            $access_payload['wo_user_id'] = $wo_user_id;
            bz_bridge_log('SSO registration race detected: using existing WoWonder user', [
                'wo_user_id' => $wo_user_id,
                'wp_user_id' => $wp_user_id
            ]);
            break;
        }

        // Check WoWonder for existing username/email
        $sqlConn = $GLOBALS['sqlConnect'];
        $username_esc = mysqli_real_escape_string($sqlConn, $access_payload['wp_user_login']);
        $email_esc    = mysqli_real_escape_string($sqlConn, $access_payload['wp_user_email']);
        $tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';

        $q = mysqli_query($sqlConn, "SELECT user_id,username,email FROM {$tbl} WHERE username='{$username_esc}' OR email='{$email_esc}'");
        $rows = [];
        while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;

        // Find user IDs for username/email
        $user_id_by_username = null;
        $user_id_by_email = null;
        foreach ($rows as $r) {
            if (strcasecmp($r['username'], $access_payload['wp_user_login']) === 0) $user_id_by_username = (int)$r['user_id'];
            if (strcasecmp($r['email'], $access_payload['wp_user_email']) === 0) $user_id_by_email = (int)$r['user_id'];
        }

        // If username/email exist and belong to same user, map that user_id and update WP
        if ($user_id_by_username && $user_id_by_email && $user_id_by_username === $user_id_by_email) {
            $wo_user_id = $user_id_by_username;
            if ($wp_user_id && $wo_user_id) {
                bz_update_wp_wo_user_id($wp_user_id, $wo_user_id);
            }
            header('Location: /wp-login.php?try=3&redirect_to=/wp-login.php?redirect_to=/streams');
            exit;
        }
        
        // -------------------------------------------------------------------
        // CASE: WoWonder email matches WordPress email, username differs
        // Update WoWonder username to match WordPress, then sync wo_user_id
        // -------------------------------------------------------------------
        if (
            $user_id_by_email &&
            (
                !$user_id_by_username ||
                strcasecmp($rows[array_search($user_id_by_email, array_column($rows, 'user_id'))]['username'], $access_payload['wp_user_login']) !== 0
            )
        ) {
            $wo_user_id = (int)$user_id_by_email;
            $desired_username = preg_replace('/[^a-zA-Z0-9_]/', '', $access_payload['wp_user_login']);
            $desired_username = substr($desired_username, 0, 32);
        
            // Check minimum username length (WoWonder requires 5)
            if (strlen($desired_username) < 5) {
                bz_bridge_log('SSO: Desired username too short', ['attempted_username' => $desired_username]);
                header('Location: /wp-login.php?try=4&redirect_to=/wp-login.php?redirect_to=/members/me/settings/');
                exit;
            }
        
            // Check WoWonder reserved/disallowed usernames and existence
            $reserved_usernames = $wo['reserved_usernames'] ?? [];
            if (
                (function_exists('Wo_IsNameExist') && Wo_IsNameExist($desired_username)) ||
                in_array($desired_username, $wo['site_pages'] ?? []) ||
                in_array($desired_username, $reserved_usernames)
            ) {
                bz_bridge_log('SSO: Desired WoWonder username already exists or is reserved', ['username' => $desired_username]);
                header('Location: /wp-login.php?try=5&redirect_to=/wp-login.php?redirect_to=/members/me/settings/');
                exit;
            }
        
            // Official WoWonder update method
            $update_success = false;
            if (function_exists('Wo_UpdateUserData')) {
                $update_success = Wo_UpdateUserData($wo_user_id, ['username' => $desired_username]);
            }
            bz_bridge_log('SSO: WoWonder username sync attempt', [
                'wo_user_id' => $wo_user_id,
                'old_username' => $rows[array_search($wo_user_id, array_column($rows, 'user_id'))]['username'] ?? '',
                'new_username' => $desired_username,
                'result' => $update_success
            ]);
            if ($update_success) {
                if ($wp_user_id && $wo_user_id) {
                    bz_update_wp_wo_user_id($wp_user_id, $wo_user_id);
                }
                $access_payload['wo_user_id'] = $wo_user_id;
                header('Location: /wp-login.php?try=6&redirect_to=/wp-login.php?redirect_to=/streams');
                exit;
            } else {
                bz_bridge_log('SSO: WoWonder username update failed', [
                    'wo_user_id' => $wo_user_id,
                    'desired_username' => $desired_username
                ]);
                header('Location: /wp-login.php?try=7&redirect_to=/wp-login.php?redirect_to=/members/me/settings/');
                exit;
            }
        }

        // If username/email exist but belong to different users, rename both and continue registration
        if ($user_id_by_username && $user_id_by_email && $user_id_by_username !== $user_id_by_email) {
            $prefix = 'error'.rand(10000,99999).'-';
            // Rename username
            $new_username = $prefix.$access_payload['wp_user_login'];
            mysqli_query($sqlConn, "UPDATE {$tbl} SET username='".mysqli_real_escape_string($sqlConn,$new_username)."' WHERE user_id=".intval($user_id_by_username));
            // Rename email (prefix before @)
            $new_email = preg_replace('/^([^@]+)/', $prefix.'$1', $access_payload['wp_user_email']);
            mysqli_query($sqlConn, "UPDATE {$tbl} SET email='".mysqli_real_escape_string($sqlConn,$new_email)."' WHERE user_id=".intval($user_id_by_email));
            // continue registration
        }

        // Register new WoWonder user
        $registration = Wo_RegisterUser([
            'username' => $access_payload['wp_user_login'],
            'email'    => $access_payload['wp_user_email'],
            'password' => bin2hex(random_bytes(16)), // random password; login only via SSO
            'active'   => 1
        ]);

        if ($registration && isset($registration['user_id'])) {
            $wo_user_id = (int)$registration['user_id'];
            $access_payload['wo_user_id'] = $wo_user_id;
            // Update WP mapping
            if ($wp_user_id && $wo_user_id) {
                bz_update_wp_wo_user_id($wp_user_id, $wo_user_id);
            }
            $_SESSION['wo_auto_registered'] = true;
            bz_bridge_log('Auto-registered WoWonder user from SSO', [
                'user_id' => $wo_user_id,
                'wp_user' => $wp_user_id
            ]);
            break;
        }

        // Registration failed, wait and retry up to max_attempts
        usleep(100000);
        $attempt++;
    }

    // Failed after all attempts → redirect
    if (empty($wo_user_id)) {
        bz_bridge_log('WoWonder auto-registration failed after retries', [
            'payload'  => $access_payload,
            'attempts' => $attempt
        ]);
        header('Location: /wp-login.php?try=8&redirect_to=/wp-login.php?redirect_to=/members/me/settings/');
        exit;
    }
}



// Persist canonical session values (set wp_user_login only if not set already to keep it immutable)
// -- 2. HYDRATE CANONICAL SESSION FIELDS FOR CURRENT UI
$final_wo_user_id = $access_payload['wo_user_id'] ?? $wo_user_id;
if (!isset($_SESSION['wp_user_login'])) 
$_SESSION['wp_user_login']  = (string)$access_payload['wp_user_login'];
$_SESSION['wp_user_id']     = (int)$access_payload['wp_user_id'];
$_SESSION['wp_user_email']  = (string)$access_payload['wp_user_email'];
$_SESSION['wo_user_id']     = (int)$final_wo_user_id;

/* bz_bridge_log('After mapping/registration - canonical session snapshot', [
    'wp_user_id'    => $_SESSION['wp_user_id'],
    'wp_user_login' => $_SESSION['wp_user_login'],
    'wp_user_email' => $_SESSION['wp_user_email'],
    'wo_user_id'    => $_SESSION['wo_user_id'],
]);
*/

// -----------------------------
// Build SSO token and choose username for the client (username = wp_user_login)
$sso_username = $_SESSION['wp_user_login'];



// -----------------------------
// Build $ajax_url for the bridge page so the client POST preserves redirect_to
// Place this after $last_url / $sso_username / $sso_token are set and BEFORE the HTML render.
// -----------------------------
$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/ww-sso-bridge.php') . '?sso_action=do_login';

// Preserve redirect_to from the incoming GET (so POST carries it through).
if (!empty($_GET['redirect_to'])) {
    // allow safe chars, remove anything suspicious
    $rt = preg_replace('/[^\w\-\/:.@]/u', '', (string) $_GET['redirect_to']);
    if ($rt !== '') {
        $ajax_url .= '&redirect_to=' . rawurlencode($rt);
    }
}

// Echo or inject $ajax_url into your HTML/JS as needed (use json_encode when embedding).
																																		


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
        'raw'               => $raw_requested,
        'sanitized'         => $requested,
        'ajax_url_preview'  => $ajax_preview,
        'session_preview'   => isset($_SESSION) ? [
            'wp_user_id'    => ($_SESSION['wp_user_id'] ?? null),
            'wo_user_id'    => ($_SESSION['wo_user_id'] ?? null)
        ] : null
    ]);

    // DO NOT call header('Location: ...') or exit() here.
    // The bridge HTML will render and client JS will POST to $ajax_url (which includes redirect_to).
    // Wo_SSO_Login() will return JSON { location: "..." } and the client JS will perform the final redirect.
}
// ------------------------------
//bz_bridge_log('SSO session prepared', ['sso_username'=>$sso_username,'sso_token_len'=>strlen($sso_token),'ajax_url'=>$ajax_url,'last_url'=>$last_url]);

// Helper function: place in shared/wwqd_bridge.php or above Wo_SSO_Login()
if (!function_exists('bz_clear_wp_wo_user_id')) {
    /**
     * Removes 'wo_user_id' usermeta for WordPress user.
     * On success: reloads page.
     * On failure: redirects to WP login with /streams redirect.
     */
    function bz_clear_wp_wo_user_id($wp_user_id) {
        $wp_conn = get_wp_db_conn();
        if (!$wp_conn || empty($wp_user_id)) {
            header('Location: /wp-login.php?try=9&redirect_to=/wp-login.php?redirect_to=/streams');
            exit;
        }
        $wp_user_id = (int)$wp_user_id;
        $meta_key = 'wo_user_id';
        $key_esc = mysqli_real_escape_string($wp_conn, $meta_key);
        // Defensive: Use table prefix if available
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        $del_query = "DELETE FROM {$table} WHERE user_id = $wp_user_id AND meta_key = '$key_esc'";
        $result = mysqli_query($wp_conn, $del_query);
        $affected = mysqli_affected_rows($wp_conn);
        
        bz_bridge_log('should redirect or reload around here:', [$wp_user_id, $meta_key, $key_esc, $table, $del_query, $result, $affected]);
        
        if ($result /*&& mysqli_affected_rows($wp_conn) > 0*/) {
            // Success: meta deleted, reload page (AJAX-safe)
            header('Location: /streams');
            exit;
        } else {
            // Failed to clear mapping, redirect to login
            header('Location: /wp-login.php?try=10&redirect_to=/wp-login.php?redirect_to=/streams');
            exit;
        }
    }
}

// -----------------------------
// Wo_SSO_Login endpoint (POST)
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') { Wo_SSO_Login(); exit; }
//Wo_SSO_Login();

function Wo_SSO_Login() {
    header('Content-Type: application/json; charset=utf-8');
    global $wo, $sqlConnect, $BUZZ_SSO_SECRET, $last_url, $access_payload;
    $errors = [];

    // Optionally suppress PHP notices/warnings to prevent output corruption
    $old_err = error_reporting();
    error_reporting($old_err & ~E_NOTICE & ~E_WARNING);
    
    $exp_wo     = (isset($access_payload['wo_user_id']) ? (int)$access_payload['wo_user_id'] : 0);
    $exp_wp     = (isset($access_payload['wp_user_id']) ? (int)$access_payload['wp_user_id'] : 0);
    $exp_login  = (isset($access_payload['wp_user_login']) ? (string)$access_payload['wp_user_login'] : '');
    $exp_email  = (isset($access_payload['wp_user_email']) ? (string)$access_payload['wp_user_email'] : '');

//    bz_bridge_log('Wo_SSO_Login: expected (auth) values', ['exp_wo'=>$exp_wo,'exp_wp'=>$exp_wp,'exp_login'=>$exp_login,'exp_email'=>$exp_email,'session_snapshot'=>$_SESSION ?? [],'claims'=>$access_payload]);

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
//    bz_bridge_log('Wo_SSO_Login: candidates fetched', ['count'=>count($candidates),'candidates'=>$candidates]);

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
/*        bz_bridge_log('Wo_SSO_Login: compare row', [
            'db'=>['user_id'=>$db_user_id,'username'=>$db_username,'email'=>$db_email,'wp_user_id'=>$db_wp_userid],
            'cmp'=>['user_id'=>$cmp_user_id,'email'=>$cmp_email,'username'=>$cmp_username,'wp_user_id'=>$cmp_wp_userid],
            'match_count'=>$match_count
        ]);
*/

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
        $errors[] = 'No matching BuzzStreams account for SSO (>=3 identifiers required).';

        // PATCH: Orphan WoWonder ID in WP usermeta, clear it and reload/redirect
        //if (empty($db_user_id) || $db_user_id === 0 || $db_user_id == '' || $db_user_id == null) {
            if (!empty($exp_wp && $exp_login && $exp_email)) {
                bz_bridge_log('Wo_SSO_Login: orphan WoWonder ID detected, clearing WordPress usermeta', [
                    'wp_user_id' => $exp_wp,
                    'wo_user_id' => $exp_wo
                ]);
                bz_clear_wp_wo_user_id($exp_wp);
            }
            // No need to continue further; this function will exit after clearing.
        //}
        
        
        bz_bridge_log('Wo_SSO_Login: no match (>=3 required)', [
            'expected'=>['wo'=>$exp_wo,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email],
            'session'=>$_SESSION ?? [],
            'claims'=>$access_payload
        ]);
        echo json_encode(['status'=>401, 'errors'=>$errors]);
        error_reporting($old_err);
        exit;
    }

    $ip = function_exists('get_ip_address') ? Wo_Secure(get_ip_address()) : '0.0.0.0';
    @mysqli_query($sqlConnect, "UPDATE {$tbl} SET `ip_address` = '".Wo_Secure($ip)."' WHERE `user_id` = '".intval($accepted_user_id)."'");
    cache($accepted_user_id, 'users', 'delete');

    $session_token = Wo_CreateLoginSession($accepted_user_id);

    $_SESSION['user_id']            = $session_token;
    $_SESSION['wo_user_id']         = (int)$accepted_user_id;
    $_SESSION['wp_Wo_SSO_Login']    = true;

    //====================================================================================
    // IMPORTANT: mark request-local $wo as logged in and provide a minimal $wo['user']
    // snapshot so the rest of the Wo code can run without heavy initialization.
    // Bridge: minimal user for $wo
    // ===================================================================================
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
        // DB lookup for is_pro/admin
        try {
            $safe_q = @mysqli_query($sqlConnect, "SELECT is_pro,admin FROM {$tbl} WHERE user_id=".(int)$accepted_user_id." LIMIT 1");
            if ($safe_q && $r_safe = mysqli_fetch_assoc($safe_q)) {
                if (isset($r_safe['is_pro'])) $minimal['is_pro'] = (int)$r_safe['is_pro'];
                if (isset($r_safe['admin']))   $minimal['admin']  = (int)$r_safe['admin'];
            }
        } catch (Throwable $e) {}
        $wo['user'] = $minimal;
    } catch (Throwable $e) {
        bz_bridge_log('Wo_SSO_Login: error creating $wo user', ['ex'=>$e->getMessage()]);
        if (!is_array($wo)) $wo = [];
        $wo['loggedin'] = true;
        $wo['user'] = ['user_id' => (int)$accepted_user_id, 'id' => (int)$accepted_user_id, 'admin'=>0, 'is_pro'=>0];
    }

    // Consider the login established when we have a created session token and user id.
    if (!empty($session_token) || !empty($accepted_user_id) || !empty($wo['loggedin'])) {

        if (!empty($_POST['remember_device']) && $_POST['remember_device'] == 'on' && !empty($wo['config']['remember_device']) && $wo['config']['remember_device'] == 1) {
            setcookie('user_id', $session_token, time() + (10*365*24*60*60), '/', BUZZ_COOKIE_DOMAIN, true, true);
        }





        // =========================================================
        // WordPress → WoWonder User Metadata Sync (Canonical Source)
        // Buzzjuice: after successful login, replace WoWonder meta with WordPress meta
        // =========================================================
        
        // 1. PRECONDITION: Must have valid login
        $wp_user_id = isset($_SESSION['wp_user_id']) ? (int)$_SESSION['wp_user_id'] : 0;
        if (!$wp_user_id) {
            bz_bridge_log('WP→Wo sync skipped: missing wp_user_id');
            return;
        }
        $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        if (!$wp_conn) {
            bz_bridge_log('WP→Wo sync skipped: WP DB unavailable');
            return;
        }
        $sqlConn  = $GLOBALS['sqlConnect'];
        $wo_table = defined('T_USERS') ? T_USERS : 'Wo_Users';
        
        // 2. LOAD BUZZ METADATA REGISTRY
        $metadata = function_exists('get_user_field_metadata') ? get_user_field_metadata() : [];
        $wp_usermeta_fields = $metadata['private_secure_fields'] ?? [];
        $wp_xprofile_fields = $metadata['public_open_fields'] ?? [];
        $field_map          = $metadata['field_map'] ?? [];
        $sync_fields = array_unique(array_merge($wp_usermeta_fields, $wp_xprofile_fields));
        
        // 3. FETCH FULL WORDPRESS PROFILE
        $wp_data = function_exists('wp_get_full_user_data')
            ? wp_get_full_user_data($wp_conn, $wp_user_id)
            : false;
        if (!$wp_data || !is_array($wp_data)) {
            bz_bridge_log('WP→Wo sync aborted: wp_get_full_user_data failed', ['wp_user_id'=>$wp_user_id]);
            return;
        }
        $wp_meta     = $wp_data['meta'] ?? [];
        $wp_xprofile = $wp_data['xprofile'] ?? [];
        $wp_core     = $wp_data;
        
        // 4. NORMALIZE & AGGREGATE WP METADATA (usermeta, xprofile, core)
        $wp_all_meta = [];
        foreach ($wp_meta as $key => $value)
            $wp_all_meta[$key] = $value;
        foreach ($wp_xprofile as $key => $value) {
            $norm = strtolower(str_replace([' ','-'],'_',trim($key)));
            $wp_all_meta[$norm] = $value;
        }
        foreach (['user_login','user_email','display_name','user_registered'] as $field)
            if (isset($wp_core[$field]) && !empty($wp_core[$field]))
                $wp_all_meta[$field] = $wp_core[$field];
        
        // 5. Avatar/Cover: BuddyBoss plugin normalization and fix
        // Helper: Normalize avatar and cover paths for WoWonder display.
        function bz_normalize_avatar_cover($url, $site_base = 'https://buzzjuice.net/streams', $type = 'avatar') {
            $url = trim($url);
            if (!$url) return '';
            if ($type === 'cover') {
                // Remove leading /streams/ for relative path
                $url = preg_replace('#^/?streams/#', '', $url);
                // If full URL, extract path after site_base
                if (preg_match('#^https?://#', $url)) {
                    $parsed = parse_url($url, PHP_URL_PATH);
                    if ($parsed && strpos($parsed, '/streams/') === 0) {
                        $url = substr($parsed, strlen('/streams/'));
                    } elseif ($parsed && strpos($parsed, '/') === 0) {
                        $url = ltrim($parsed, '/');
                    }
                }
                // Remove remaining /streams/ prefix if present
                $url = preg_replace('#^/?streams/#', '', $url);
                // Ensure starts with upload/photos/
                if (strpos($url, 'upload/photos/') !== 0) {
                    $url = 'upload/photos/' . ltrim($url, '/');
                }
                return $url;
            }
            // AVATAR: Always full URL
            if (!preg_match('#^https?://#', $url)) {
                $url = preg_replace('#^/?streams/#', '', $url);
                $url = rtrim($site_base, '/') . '/' . ltrim($url, '/');
            }
            return $url;
        }
        
        $site_base = rtrim($base_streams_url ?? 'https://buzzjuice.net/streams', '/');
        if (!empty($wp_meta['bp_profile_avatar'])) {
            $wp_all_meta['avatar'] = bz_normalize_avatar_cover($wp_meta['bp_profile_avatar'], $site_base, 'avatar');
        }
        if (!empty($wp_meta['bp_profile_cover'])) {
            $wp_all_meta['cover']  = bz_normalize_avatar_cover($wp_meta['bp_profile_cover'], $site_base, 'cover');
        }
        
        // 6. BUILD WoWonder UPDATE PAYLOAD (schema mapping)
        $update = [];
        foreach ($sync_fields as $field) {
            if (!array_key_exists($field, $wp_all_meta)) continue;
            $val = $wp_all_meta[$field];
            if ($val === null || (is_string($val) && trim($val) === '')) continue;
            // Normalize avatar/cover as you push
            if ($field === 'avatar') $val = bz_normalize_avatar_cover($val, $site_base, 'avatar');
            if ($field === 'cover')  $val = bz_normalize_avatar_cover($val, $site_base, 'cover');
            $wo_field = $field_map[$field] ?? $field;
            $update[$wo_field] = is_string($val) ? trim($val) : $val;
        }
        
        // Canonical identity always synced from session
        $update['wp_user_id'] = $wp_user_id;
        if (!empty($_SESSION['wp_user_email']))  $update['email']    = trim($_SESSION['wp_user_email']);
        if (!empty($_SESSION['wp_user_login']))  $update['username'] = trim($_SESSION['wp_user_login']);
        
        // 7. LOAD WoWonder SCHEMA CACHE (efficient, reloads only if missing)
        $schema_cache_folder = $_SERVER['DOCUMENT_ROOT'] . '/data/schema_cache/';
        $schema_cache_file   = $schema_cache_folder . 'wo_users_schema.json';
        if (!is_dir($schema_cache_folder)) @mkdir($schema_cache_folder, 0755, true);
        static $wo_schema = null;
        if ($wo_schema === null) {
            if (file_exists($schema_cache_file)) {
                $wo_schema = json_decode(file_get_contents($schema_cache_file), true) ?: [];
            } else {
                $wo_schema = [];
                $q = mysqli_query($sqlConn, "SHOW COLUMNS FROM {$wo_table}");
                while ($row = mysqli_fetch_assoc($q)) $wo_schema[$row['Field']] = true;
                @file_put_contents($schema_cache_file, json_encode($wo_schema));
            }
        }
        
        // 8. FILTER UNSUPPORTED FIELDS (future-proof)
        $update_filtered = [];
        foreach ($update as $field => $value) {
            if (isset($wo_schema[$field])) {
                $update_filtered[$field] = $value;
            } else {
 //               bz_bridge_log('WP→Wo sync skipped field (not in WoWonder schema)', ['field'=>$field]);
            }
        }
        
        // 9. METADATA HASH OPTIMIZATION — Only write if changed
        $hash_payload = $update_filtered;
        unset($hash_payload['lastseen'], $hash_payload['session'], $hash_payload['ip_address']);
        $new_hash = md5(json_encode($hash_payload));
        $old_hash = '';
        if (isset($wo_schema['wp_meta_hash'])) {
            $q = mysqli_query($sqlConn, "SELECT wp_meta_hash FROM {$wo_table} WHERE user_id=".(int)$accepted_user_id." LIMIT 1");
            if ($q && $row = mysqli_fetch_assoc($q)) $old_hash = $row['wp_meta_hash'] ?? '';
        }
        
        // 10. PUSH TO WoWonder ONLY IF CHANGED; Extensive Error Logging
        if ($new_hash !== $old_hash) {
            $update_filtered['wp_meta_hash'] = $new_hash;
            if (!empty($update_filtered) && function_exists('Wo_UpdateUserData')) {
                $old_level = error_reporting();
                error_reporting($old_level & ~E_NOTICE & ~E_WARNING);
                try {
                    $result = Wo_UpdateUserData($accepted_user_id, $update_filtered);
/*                    bz_bridge_log('WP→Wo sync: metadata updated', [
                        'user_id'=>$accepted_user_id,
                        'fields'=>array_keys($update_filtered),
                        'result'=>$result
                    ]);
*/
                } catch (Throwable $e) {
                    bz_bridge_log('WP→Wo sync: ERROR during Wo_UpdateUserData', [
                        'user_id'=>$accepted_user_id,
                        'error'=>$e->getMessage()
                    ]);
                } finally {
                    error_reporting($old_level);
                }
            }
        } else {
            bz_bridge_log('WP→Wo sync skipped (meta hash unchanged)', ['user_id'=>$accepted_user_id]);
        }
        // todo: Advanced field mapping implementation and 
        // helper utilities for WordPress/BuddyPress meta extraction and transformation
        // WP->WW API meta sync, Buzzjuice Identity Sync Engine -> central identity authority, Sync Queue, Worker Processes Sync Queue, Push data....





        // ------------------------------
        // Wo_SSO_Login() — JSON redirect resolution (replace existing redirect-building block)
        // last_url fallback or default
        // ------------------------------
        $base_streams_url = rtrim($wo['config']['site_url'] ?? '', '/');
        $data = [
            'status'=>200,
            'location'=>$_SESSION['last_url'] // Overwrite this with your final redirect logic as in main file
        ];
        
        // New auto-registered user -> start-up
        if (!empty($_SESSION['wo_auto_registered'])) {
            $start_up = function_exists('Wo_SeoLink') ? Wo_SeoLink('index.php?link1=start-up') : rtrim($site_base, '/') . '/index.php?link1=start-up';
            $data['location'] = $start_up;
            unset($_SESSION['wo_auto_registered']);
            bz_bridge_log('Wo_SSO_Login: new auto-registered user; redirecting to start-up', ['redirect' => $data['location']]);
            if ($is_ajax) {
            echo json_encode($data);
            } else {
                header('Location: ' . $data['location']);
            }
            exit;
        }
        $data['location'] = !empty($_SESSION['last_url']) && strpos($_SESSION['last_url'], $base_streams_url) === 0 ? $_SESSION['last_url'] : ($base_streams_url . '/?cache=' . time());
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        bz_bridge_log('Wo_SSO_Login: success', [
            'user_id'=>$accepted_user_id,'session'=>$session_token,
            'reason'=>$accepted_reason,'matches'=>$accepted_matches,
            'final redirect'=>$data['location']
        ]);
        echo json_encode($data);
        error_reporting($old_err);
        exit;
    } else {
        $errors[] = 'WoWonder session not established after login.';
        bz_bridge_log('Wo_SSO_Login: WoWonder session not established', ['user_id'=>$accepted_user_id,'session'=>$session_token,'reason'=>$accepted_reason,'matches'=>$accepted_matches,'wo_loggedin'=>!empty($wo['loggedin'])]);
        echo json_encode(['status'=>500, 'errors'=>$errors]);
        error_reporting($old_err);
    }
    $data = ['status' => 500, 'message' => 'Session not established.'];
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}



/*bz_bridge_log('Rendering bridge page', [
    'sso_username'    => $sso_username,
    'sso_token_len'   => strlen($sso_token),
    'last_url'        => $last_url
]);
*/
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
    <div class="title">BuzzStreams...… </div>
    <div id="status" class="status">Preparing secure session…</div>
    <?php if (bz_is_debug()): ?>
      <div class="dbg"><pre><?php echo htmlspecialchars(print_r([
          'ajax_url' => $ajax_url,
          'post' => [
              'username' => $sso_username,
              'sso_token' => '(sso-token:len='.strlen($sso_token).')',
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
          statusEl && (statusEl.className='status ok', statusEl.textContent='Connected to streams! Redirecting...…');
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
    formParams.push('sso_token=' + encodeURIComponent(ssoPwd));
    formParams.push('remember_device=on');
    if (typeof lastUrl === 'string') formParams.push('last_url=' + encodeURIComponent(lastUrl));
    xhr.send(formParams.join('&'));
  })();
  </script>
</body>
</html>