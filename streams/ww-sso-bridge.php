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

// =========================================================
// START: CONFIGURATIONS / DEFAULTS
// =========================================================
if (!defined('WP_BASE_SITE_URL'))        define('WP_BASE_SITE_URL', 'https://buzzjuice.net');
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/ww_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$base_site_url = defined('WP_BASE_SITE_URL') ? WP_BASE_SITE_URL : (getenv('WP_BASE_SITE_URL') ?: null);
$base_streams_url   = rtrim($wo['config']['site_url'], '/');
$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);																																			 
$BUZZ_SSO_SECRET = (string)($BUZZ_SSO_SECRET ?? '');
$sso_token  = trim($_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? ''));
$sso_action = $_REQUEST['sso_action'] ?? '';

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
define('BUZZ_JTI_STORE', __DIR__ . '/data/sso_jti_store');
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



// =================================================================
// START: SESSION MANAGEMENT / ENDPOINTS / PAYLOAD / DATA MAPPING
// =================================================================
// SSO SESSION SAFETY GUARD: If user already logged in, abort bridge
// Detect WordPress login cookie
$wordpress_logged_in_ = false;
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'wordpress_logged_in_') === 0) {
        $wordpress_logged_in_ = true;
        break;
    }
}
// Safely fetch session / bridge state
$wo_loggedin        = !empty($wo['loggedin']);
$wo_user_id         = $wo['user']['user_id'] ?? null;
$session_wo_user_id = $_SESSION['wo_user_id'] ?? null;
$session_user_id    = $_SESSION['user_id'] ?? null;

// Determine if already logged in
$already_logged_in = $wordpress_logged_in_ && $wo_loggedin && !empty($session_user_id) && ($session_wo_user_id == $wo_user_id);

// Abort SSO if already logged in
if ($already_logged_in) {

    bz_bridge_log(
        'Already logged in; aborting SSO bridge and redirecting.',
        [
            'wo_loggedin'        => $wo_loggedin,
            'wordpress_cookie'   => $wordpress_logged_in_,
            'wo_user_id'         => $wo_user_id,
            'session_wo_user_id' => $session_wo_user_id,
            'session_user_id'    => $session_user_id,
            'request_uri'        => $_SERVER['REQUEST_URI'] ?? null,
            'http_referer'       => $_SERVER['HTTP_REFERER'] ?? null,
            'redirect_to'        => $_GET['redirect_to'] ?? null
        ]
    );

    // Safe redirect fallback
    $redirect = $_SERVER['HTTP_REFERER'] ?? '/streams';
    $redirect = (strpos($redirect, '/') === 0) ? $redirect : '/streams';

    header('Location: ' . $redirect);
    exit;
}

// SESSION: SSR/LOGIN ONLY (STATLESS SSO - JWT IS AUTHORITY - Ensure session is active when required)
// If assets/init.php didn't start a session, start only when necessary.
/* if (session_status() === PHP_SESSION_NONE) {
    $needs = 
			!empty($_COOKIE[BUZZ_SSO_COOKIE]) ||
			(!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login') || 
			(!empty($_POST['sso_action']) && $_POST['sso_action'] === 'do_login') || 
			!empty($_GET['sso_debug']);

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
        if ($sid) { @session_id($sid); 
						bz_bridge_log('Resuming PHP session from cookie (bridge fallback)'); 
				}
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
*/

// =============================================================================
// BuzzStreams Fetch Stateless SSO Payload Orchestration (WordPress → WoWonder)
// =============================================================================
// 1. Define the bridge path and query parameters
$bridge_path = 'ww-sso-bridge.php';
$query_params = ['sso_action' => 'do_login',];
$bridge_base = rtrim($base_streams_url ?? '', '/');
$bridge_url = $bridge_base . '/' . $bridge_path . '?' . http_build_query($query_params);

// 4. Debug log (optional)
if (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG) {
    error_log("Bridge URL built: $bridge_url");
}

$payload = null;

// 1) Read & verify buzz_sso cookie (primary authority)
if (!empty($sso_token) && $BUZZ_SSO_SECRET !== '') {
		$payload = bz_validate_jwt($sso_token, $BUZZ_SSO_SECRET);
} else {
		bz_bridge_log('sso_token or secret ERROR!', [
				'token' => !empty($sso_token),
				'secret'  => !empty($BUZZ_SSO_SECRET),
				
				'last_url'   => $last_url ?? null,
				'requested'     => $requested ?? null,
				'bridge_base'   => $bridge_base ?? null,
				'bridge_url'    => $bridge_url ?? null,
    ]);
		bz_redirect_to_wp_login($bridge_url, 'streams');
		exit;
}

// Try Login from WordPress Endpoint
if (!empty($sso_token) && $BUZZ_SSO_SECRET !== '') {
    $payload_arr = bz_fetch_wp_stateless_payload($sso_token, $BUZZ_SSO_SECRET);
    // If WP explicitly says "not logged in" → redirect to WP login
    if (isset($payload_arr['status']) && (int)$payload_arr['status'] === 401) {
        bz_bridge_log('WP endpoint returned 401. Redirecting to WP login.', [
            'redirect to $bridge_url' => $bridge_url
        ]);
        bz_redirect_to_wp_login($bridge_url, 'streams');
        exit;
    }
    // If valid payload received → use it
    if (!empty($payload_arr['payload'])) {
        $payload = $payload_arr['payload'];
						bz_bridge_log('WP endpoint successful!', [
								'payload' => $payload
						]);     
    }
}

// Try Login from BuzzSocial Endpoint
if (!is_array($payload) && !empty($sso_token) && $BUZZ_SSO_SECRET !== '') {
    $qd_url = 'https://buzzjuice.net/social/qd-sso-bridge.php?sso_action=get_payload_for_streams';
    $signature = hash_hmac('sha256', 'get_payload_for_streams', $BUZZ_SSO_SECRET);
    $ch = curl_init($qd_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Buzzjuice-Signature: ' . $signature]);
    $result = curl_exec($ch);
    $curl_error = curl_error($ch);
    $curl_errno  = curl_errno($ch);
    curl_close($ch);
    if ($curl_errno) {
        bz_bridge_log('BuzzSocial SSO curl error', [
            'error' => $curl_error,
            'url'   => $qd_url
        ]);
    } else {
        $resp = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            bz_bridge_log('BuzzSocial SSO JSON decode error', [
                'result' => $result,
                'error'  => json_last_error_msg()
            ]);
        } elseif (!empty($resp['payload']) && is_array($resp['payload'])) {
            $payload = $resp['payload'];
            bz_bridge_log('BuzzSocial endpoint successful', ['payload'=>$payload]);
        }
    }
}

// Redirect to WordPress Login
if (!is_array($payload)) {
    bz_bridge_log('No BuzzSocial JWT/buzz_sso cookie present or missing secret', [
        'sso_token' => !empty($sso_token),
        'secret' => (bool)$BUZZ_SSO_SECRET
    ]);
    bz_redirect_to_wp_login($bridge_url, 'streams');
		exit;
}

// ==================================================================
// SSO JWT CANONICAL CLAIM EXTRACTION & REQUIRED CLAIMS VALIDATION
// ==================================================================
// Extract claims (raw)
$claim_wp_user_id    = isset($payload['wp_user_id'])    ? (int)$payload['wp_user_id'] : 0;
$claim_wp_user_login = isset($payload['wp_user_login']) ? (string)$payload['wp_user_login'] : '';
$claim_wp_user_email = isset($payload['wp_user_email']) ? (string)$payload['wp_user_email'] : '';
$claim_wo_user_id    = isset($payload['wo_user_id'])    ? (int)$payload['wo_user_id'] : 0;

$original_claims = [
    'claim_wp_user_id'=>$claim_wp_user_id,
    'claim_wp_user_login'=>$claim_wp_user_login,
    'claim_wp_user_email'=>$claim_wp_user_email,
    'claim_wo_user_id'=>$claim_wo_user_id
];

bz_bridge_log('buzz_sso claims extracted', array_merge($original_claims, ['raw_payload'=>$payload]));

// Required claims guard													
if (!$claim_wp_user_id || !$claim_wp_user_login || !$claim_wp_user_email) {
    bz_bridge_log('Missing required claims (cookie incomplete)', $original_claims);
    bz_redirect_to_wp_login($bridge_url, 'streams');																																																				
    exit;
}
														
// Canonicalization: prefer server session values (if present) to avoid accidental overwrite.																																																																																					
$canonical = [];
$canonical['wp_user_id']    = $claim_wp_user_id;
$canonical['wp_user_login'] = $claim_wp_user_login;
$canonical['wp_user_email'] = $claim_wp_user_email;
$canonical['wo_user_id']    = $claim_wo_user_id;

bz_bridge_log('Canonical pre-mapping values', ['canonical'=>$canonical,'session'=>$_SESSION ?? []]);



// =========================================================
// Auto-register if no user exists and auto-registration allowed
// =========================================================
if ((!$canonical['wo_user_id'] || !$claim_wo_user_id) && BUZZ_SSO_AUTO_REGISTER) {
    $registration = Wo_RegisterUser([
        'username' => $jwt_payload['wp_user_login'],
        'email'    => $jwt_payload['wp_user_email'],
        'password' => bin2hex(random_bytes(16)), // random password; login only via SSO
        'active'   => 1
    ]);
    if ($registration && isset($registration['user_id'])) {
        $wo_user_id = (int) $registration['user_id'];
        bz_bridge_log('Auto-registered WoWonder user from SSO', ['user_id'=>$wo_user_id, 'wp_user'=>$jwt_payload['wp_user_id']]);
    }
}

$final_wo_user_id = $canonical['wo_user_id'] ?? $wo_user_id;



// Persist canonical session values (set wp_user_login only if not set already to keep it immutable)
// -- 2. HYDRATE CANONICAL SESSION FIELDS FOR CURRENT UI
if (!isset($_SESSION['wp_user_login'])) 
$_SESSION['wp_user_login']  = (string)$canonical['wp_user_login'];
$_SESSION['wp_user_id']     = (int)$canonical['wp_user_id'];
$_SESSION['wp_user_email']  = (string)$canonical['wp_user_email'];
$_SESSION['wo_user_id']     = (int)$final_wo_user_id;

bz_bridge_log('After mapping/registration - canonical session snapshot', [
    'wp_user_id' => $_SESSION['wp_user_id'],
    'wp_user_login' => $_SESSION['wp_user_login'],
    'wp_user_email' => $_SESSION['wp_user_email'],
    'wo_user_id' => $_SESSION['wo_user_id'],
]);

// -----------------------------
// Build SSO token and choose username for the client (username = wp_user_login)
$sso_username = $_SESSION['wp_user_login'];











// -----------------------------
// last_url derivation & normalization
// -----------------------------
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
        $host = $_SERVER['HTTP_HOST'] ?? parse_url($base_streams_url, PHP_URL_HOST);
        $candidate = rtrim($scheme . '://' . $host, '/') . $req_uri;
        $ok = false;
        if ($base_streams_url && strpos($candidate, $base_streams_url) === 0) $ok = true;
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
            $last_url = $base_streams_url . $last_url;
        } else {
            $last_url = $base_streams_url . '/' . ltrim($last_url, '/');
        }

    }
																											
    // Ensure same-site
    if ($base_streams_url && strpos($last_url, $base_streams_url) !== 0) {
        // Not same site; drop it
																		
									
        $last_url = '';
    }
}
if (!$last_url) $last_url = $base_streams_url . '/';

// Insert immediately after the block that normalizes $last_url (after "if (!$last_url) $last_url = $base_streams_url . '/';")
if (!empty($last_url) && function_exists('bz_is_bridge_url') && bz_is_bridge_url($last_url, $base_streams_url)) {
    bz_bridge_log('last_url rejected: bridge/self-reference detected', ['last_url' => $last_url, 'site_base' => $base_streams_url]);
    $last_url = rtrim($base_streams_url, '/') . '/';
}
				
																										
if (!empty($forced_last_url_fallback)) {
    $last_url = rtrim($base_streams_url, '/') . '/';
}













// -----------------------------
// Build $ajax_url for the bridge page so the client POST preserves redirect_to
// Place this after $last_url / $sso_username / $sso_token are set and BEFORE the HTML render.
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

bz_bridge_log('SSO session prepared', ['sso_username'=>$sso_username,'sso_token_len'=>strlen($sso_token),'ajax_url'=>$ajax_url,'last_url'=>$last_url]);



// -----------------------------
// Wo_SSO_Login endpoint (POST)
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') { Wo_SSO_Login(); exit; }
//Wo_SSO_Login();

function Wo_SSO_Login() {
    global $wo, $sqlConnect, $BUZZ_SSO_SECRET, $last_url, $canonical;
//    header('Content-Type: application/json; charset=utf-8');
    $errors = [];

    $posted_last_url = isset($_POST['last_url']) ? (string)$_POST['last_url'] : '';
    bz_bridge_log('Wo_SSO_Login: credentials received', ['posted_last_url'=>$posted_last_url,'session'=>$_SESSION ?? []]);

    $claims = $canonical;
    if (!$claims) { $errors[]='Invalid or expired SSO token'; bz_bridge_log('Wo_SSO_Login: token parse/verify failed'); echo json_encode(['errors'=>$errors]); exit; }

    $exp_wo = (isset($claims['wo_user_id']) ? (int)$claims['wo_user_id'] : 0);
    $exp_wp = (isset($claims['wp_user_id']) ? (int)$claims['wp_user_id'] : 0);
    $exp_login = (isset($claims['wp_user_login']) ? (string)$claims['wp_user_login'] : '');
    $exp_email = (isset($claims['wp_user_email']) ? (string)$claims['wp_user_email'] : '');

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
        $base_streams_url = rtrim($wo['config']['site_url'] ?? '', '/');
        
        // Default fallback location
        $data = [
            'status'   => 200,
            'location' => $base_streams_url . '/?cache=' . time(),
        ];
        
        // Helper: resolve a safe redirect_to token or path to an absolute location on this site
        $resolve_redirect_to = function($token) use ($base_streams_url) {
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
                        ? rtrim($base_streams_url, '/') . $internal
                        : rtrim($base_streams_url, '/') . '/' . ltrim($internal, '/');
                }
            }
        
            // Absolute URL — allow only same-site host
            if (preg_match('#^https?://#i', $token_safe)) {
                $parts = @parse_url($token_safe);
                $site_host = parse_url($base_streams_url, PHP_URL_HOST);
                if (!empty($parts['host']) && strcasecmp($parts['host'], $site_host) === 0) {
                    return $token_safe;
														 
								 
                }
                return '';
            }
        
            // Treat as path or short path under site root
            if (strpos($token_safe, '/') === 0) {
                $candidate = rtrim($base_streams_url, '/') . $token_safe;
            } else {
                $candidate = rtrim($base_streams_url, '/') . '/' . ltrim($token_safe, '/');
																				
								 
																	 
            }
            if (strpos($candidate, $base_streams_url) === 0) {
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
                   $is_ajax = (         !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'     );
            } else {
                bz_bridge_log('Wo_SSO_Login: redirect_to present but could not resolve to safe location', ['raw' => $_REQUEST['redirect_to']]);
                // fall through to normal rules
            }
        }
        
        // 2) New auto-registered user -> start-up
        if (!empty($_SESSION['wo_auto_registered'])) {
            $start_up = function_exists('Wo_SeoLink') ? Wo_SeoLink('index.php?link1=start-up') : rtrim($base_streams_url, '/') . '/index.php?link1=start-up';
            $data['location'] = $start_up;
            unset($_SESSION['wo_auto_registered']);
            bz_bridge_log('Wo_SSO_Login: new auto-registered user; redirecting to start-up', ['redirect' => $data['location']]);
               $is_ajax = (         !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'     );
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
                $data['location'] = function_exists('Wo_SeoLink') ? Wo_SeoLink('index.php?link1=go-pro') : rtrim($base_streams_url, '/') . '/index.php?link1=go-pro';
                bz_bridge_log('Wo_SSO_Login: membership go-pro override applied', ['user_id' => $accepted_user_id, 'redirect' => $data['location']]);
                   $is_ajax = (         !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'     );
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
                // Normalize path-only to absolute using $base_streams_url
                if (strpos($candidate_raw, '/') === 0) {
                    $candidate_abs = rtrim($base_streams_url, '/') . $candidate_raw;
                } else {
                    $candidate_abs = $candidate_raw;
                }
        
                // Basic parse and scheme validation
                $scheme = @parse_url($candidate_abs, PHP_URL_SCHEME);
                if (!in_array($scheme, ['http', 'https'], true)) {
                    bz_bridge_log('posted_last_url rejected: invalid scheme', ['posted' => $candidate_raw, 'scheme' => $scheme]);
                } else {
                    // Ensure same-site host
                    $site_host = bz_normalize_host(parse_url($base_streams_url, PHP_URL_HOST) ?: '');
                    $candidate_host = bz_normalize_host(parse_url($candidate_abs, PHP_URL_HOST) ?: '');
        
                    // Reject if it references the bridge or SSO paths
                    $is_bridge_ref = bz_is_bridge_url($candidate_abs, $base_streams_url);
        
                    if ($candidate_abs && $site_host && $candidate_host === $site_host && !$is_bridge_ref) {
                        $data['location'] = $candidate_abs;
                        bz_bridge_log('Wo_SSO_Login: using posted_last_url as redirect', ['posted_last_url' => $candidate_raw, 'final' => $data['location']]);
                        // success — clear loop counter cookie
                        if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
                           $is_ajax = (         !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'     );
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
        $data['location'] = !empty($last_url) && strpos($last_url, $base_streams_url) === 0 ? $last_url : ($base_streams_url . '/?cache=' . time());
        
        bz_bridge_log('Wo_SSO_Login: final redirect chosen', ['final' => $data['location']]);
        
        // Final safety: never return the bridge itself as redirect
        if (function_exists('bz_is_bridge_url') && !empty($data['location']) && bz_is_bridge_url($data['location'], $base_streams_url)) {
								 
						 
            bz_bridge_log('Final redirect would go to bridge; replacing with site base to avoid loop', ['chosen' => $data['location']]);
            $data['location'] = rtrim($base_streams_url, '/') . '/';
            if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
        }
        
        
        
        
        
        
        
        
        
        
           $is_ajax = (         !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'     );
        // ------------------------------

        bz_bridge_log('Wo_SSO_Login: success', ['user_id'=>$accepted_user_id,'session'=>$session_token,'reason'=>$accepted_reason,'matches'=>$accepted_matches,'redirect'=>$data['location']]);

           $is_ajax = (         !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'     );

    } else {
        $errors[] = 'WoWonder session not established after login.';
        bz_bridge_log('Wo_SSO_Login: WoWonder session not established', ['user_id'=>$accepted_user_id,'session'=>$session_token,'reason'=>$accepted_reason,'matches'=>$accepted_matches,'wo_loggedin'=>!empty($wo['loggedin'])]);
														
    }
																		
    $data = ['status' => 500, 'message' => 'Session not established.'];
       $is_ajax = (         !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'     );
}



bz_bridge_log('Rendering bridge page', [
    'sso_username'    => $sso_username,
    'sso_token_len'=> strlen($sso_token),
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
    formParams.push('sso_token=' + encodeURIComponent(ssoPwd));
    formParams.push('remember_device=on');
    if (typeof lastUrl === 'string') formParams.push('last_url=' + encodeURIComponent(lastUrl));
    xhr.send(formParams.join('&'));
  })();
  </script>
</body>
</html>