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
if (file_exists(__DIR__ . '/controllers/aj.php')) require_once __DIR__ . '/controllers/aj.php';
if (file_exists(__DIR__ . '/requests/ajax/useractions.php')) require_once __DIR__ . '/requests/ajax/useractions.php';

if (file_exists(__DIR__ . '/../shared/wwqd_bridge.php')) require_once __DIR__ . '/../shared/wwqd_bridge.php';
require_once __DIR__ . '/../shared/sso_bridge_helpers.php';
if (!function_exists('bz_fetch_wp_stateless_payload')) {
    exit('SSO helper not loaded.');
}

// ======================================================
// START: CONFIGURATIONS + LOGGING + graceful failure
// ======================================================
if (!defined('WP_BASE_SITE_URL'))        define('WP_BASE_SITE_URL', 'https://buzzjuice.net');
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/qd_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$base_site_url      = defined('WP_BASE_SITE_URL') ? WP_BASE_SITE_URL : (getenv('WP_BASE_SITE_URL') ?: null);
$base_social_url   = rtrim($config->uri, '/');
$BUZZ_SSO_SECRET    = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);																																			 
$BUZZ_SSO_SECRET    = (string)($BUZZ_SSO_SECRET ?? '');
$sso_token          = trim($_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? ''));
$sso_action         = $_REQUEST['sso_action'] ?? '';

// -----------------------------------------------
// HELPERS: LOGGING, DEBUG + SESSION VISIBILITY
// -----------------------------------------------
// CLIENT DEBUG BEACON
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

// LOOP PROTECTION
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

// ***** Replay protection: JTI store (20-30 min, 60 minute cleanup) ***** TODO
define('QD_SSO_JTI_STORE', __DIR__ . '/data/.bz_sso_jti_store');
if (!is_dir(QD_SSO_JTI_STORE)) @mkdir(QD_SSO_JTI_STORE, 0755, true);
if (mt_rand(1, 30) === 15) bz_cleanup_jti_store();

// -------------------------------------------------------
// BOOTSTRAP CHECKS — REQUIRE QD CONFIG AND SQL
// -------------------------------------------------------
global $config, $conn;
if (empty($config->uri) || empty($conn)) {
    bz_bridge_log('Bootstrap incomplete - missing $config or $conn');
    bz_debug_page('Bootstrap incomplete', ['$config' => $config ?? null, '$conn' => (bool)$conn]);
    bz_redirect_to_wp_login($base_site_url, 'social');
    exit;
}

// -------------------------------------------------------------
//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 2
// ===========================================================================
// START: LEGACY SESSION BOOTSTRAP & SHADOW RECONCILIATION — DEPRECATED
// ===========================================================================
// - Legacy QuickDate session/shadow functions are fully deprecated:
//     qd_cleanup_shadow_mismatches(), qd_write_canonical_shadow_file(),
//     qd_attempt_session_reconciliation_if_required(), qd_find_wp_shadow_payload(),
//     qd_unlink_local_session_file_if_exists()
// - Modern SSO is strictly stateless and JWT-based (RFC 7519):
//     • Trust derives ONLY from validated JWT issued by WordPress.
//     • No session persistence, reconciliation, or adoption logic allowed.
//     • Replay protection via JWT jti claim, not session files.
//     • User mapping and metadata sync via explicit code/API only.
// - $_SESSION may be used only for UI/UX; NEVER for SSO trust or login state.
// - Only clear buzz_sso and session keys on explicit logout; do NOT destroy PHPSESSID.
// - These helpers are retained for historical reference; DO NOT REINSTATE.
// ===========================================================================
/* ----- BEGIN LEGACY/DEPRECATED BLOCK: SESSION BOOTSTRAP ----- */
/*static $qd_session_bootstrapped = false;
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
        bz_bridge_log('SessionStart() exception', ['ex'=>$e->getMessage()]);
    }
    $qd_session_bootstrapped = true;
}
bz_bridge_log('SessionStart() called', [
    'phpSessionId'=>session_id(),
    'shadow_session_id'=>(isset($_COOKIE['PHPSESSID']) ? 'shadow_'.$_COOKIE['PHPSESSID'] : null)
]);

try {
    // DO NOT CALL in modern SSO: qd_attempt_session_reconciliation_if_required();
} catch (Throwable $e) {
    bz_bridge_log('Session reconciliation attempt threw', ['err'=>$e->getMessage()]);
}

// Defensive sync: legacy anti-drift, NOT needed with JWT SSO. Retained for log context.
if (!isset($_SESSION['buzz_sso_defensive_last']) || (time() - (int)$_SESSION['buzz_sso_defensive_last']) > 4*3600) {
    $_SESSION['buzz_sso_defensive_last'] = time();
    $errs = [];
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) $errs[] = 'buzz_sso_cookie_missing';
    if (empty($_SESSION['wp_user_login'])) $errs[] = 'wp_user_login_missing';
    if (empty($_SESSION['qd_user_id']) || !is_numeric($_SESSION['qd_user_id'])) $errs[] = 'qd_user_id_missing_or_invalid';
    if ($errs) bz_bridge_log('Defensive sync checks', ['errs'=>$errs]);
}

// Session normalization: DO NOT use for SSO trust! JWT is canonical.
function normalize_sso_session() {}
normalize_sso_session();

// Explicit logout handler (cookie/session cleanup & redirect).
//   DO NOT call as part of SSO authentication; only for true user logouts.
function qd_clear_and_logout($reason='unknown') {
    global $config;
    bz_bridge_log('Clearing session SSO keys and redirecting to logout', ['reason'=>$reason]);
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
*/
/* ----- END LEGACY SESSION BOOTSTRAP & SHADOW RECONCILIATION — DEPRECATED BLOCK ----- */
// ===================================================================================================
// END: CONFIGURATIONS + LOGGING + graceful failure (no legacy HMAC/token helpers below this point!)
// ===================================================================================================



// =============================================
// START: ENDPOINTS + PAYLOAD + DATA MAPPING
// =============================================
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
$u = auth();
$qd_loggedin        = !empty(IS_LOGGED);
$qd_user_id 				= (!empty($u) && !empty($u->id)) ?? null;
$session_qd_user_id = $_SESSION['qd_user_id'] ?? null;
$session_user_id    = $_SESSION['user_id'] ?? null;

// Determine if already logged in
$already_logged_in = $wordpress_logged_in_ && $qd_loggedin && !empty($session_user_id) && ($session_qd_user_id == $qd_user_id);

// Abort SSO if already logged in
if (!$already_logged_in) {

    bz_bridge_log(
        'Already logged in; aborting SSO bridge and redirecting.',
        [
            'u'                  => $u,
            'qd_loggedin'        => $qd_loggedin,
            'wordpress_cookie'   => $wordpress_logged_in_,
            'qd_user_id'         => $qd_user_id,
            'session_qd_user_id' => $session_qd_user_id,
            'session_user_id'    => $session_user_id,
            'request_uri'        => $_SERVER['REQUEST_URI'] ?? null,
            'http_referer'       => $_SERVER['HTTP_REFERER'] ?? null,
            'redirect_to'        => $_GET['redirect_to'] ?? null
        ]
    );

    // Safe redirect fallback
    $redirect = $_SERVER['HTTP_REFERER'] ?? '/social';
    $redirect = (strpos($redirect, '/') === 0) ? $redirect : '/social';

//    header('Location: ' . $redirect);
//    exit;
}

// ------------------------------------------
// Fetch Secure Payload From BuzzStreams Endpoint
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

// ==============================================================================
// QuickDate Fetch Stateless SSO Payload Orchestration (WordPress → QuickDate)
// ==============================================================================
// 1. Define the bridge path and query parameters
$bridge_path = 'qd-sso-bridge.php';

$bridge_base = rtrim($base_social_url ?? '', '/');

$bridge_url = $bridge_base . '/' . $bridge_path;

if (!empty($last_url)) {
    $bridge_url .= '?last_url=' . urlencode($last_url);
}

// preserve last_url parameter for POST-login destination (optional)
if (!empty($last_url)) {
    $bridge_url .= '?last_url=' . urlencode($last_url);
}

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
				'token'         => !empty($sso_token),
				'secret'        => !empty($BUZZ_SSO_SECRET),
				'last_url'      => $last_url ?? null,
				'requested'     => $requested ?? null,
				'bridge_base'   => $bridge_base ?? null,
				'bridge_url'    => $bridge_url ?? null,
    ]);
		//bz_redirect_to_wp_login($bridge_url, 'social');
		//exit;
}

// Try Login from WordPress Endpoint
if (!is_array($payload) && !empty($sso_token) && $BUZZ_SSO_SECRET !== '') {
    $payload_arr = bz_fetch_wp_stateless_payload($sso_token ?? null, $BUZZ_SSO_SECRET);
    // If WP explicitly says "not logged in" → redirect to WP login
    if (isset($payload_arr['status']) && (int)$payload_arr['status'] === 401) {
        bz_bridge_log('WP endpoint returned 401. Redirecting to WP login.', [
            'redirect_to' => $redirect_target
        ]);
        bz_redirect_to_wp_login($bridge_url, 'social');
        exit;
    }
    // If valid payload received → use it
    if (!empty($payload_arr['payload'])) {
        $payload = $payload_arr['payload'];
						bz_bridge_log('WP endpoint successful!', [
								'redirect_to' => $payload
						]);
    }
}

// Try Login from BuzzStreams Endpoint
if (!$payload) {
    $ww_url = 'https://buzzjuice.net/streams/ww-sso-bridge.php?sso_action=get_payload_for_social';
    $signature = hash_hmac('sha256', 'get_payload_for_social', $BUZZ_SSO_SECRET);
    $ch = curl_init($ww_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Buzzjuice-Signature: ' . $signature]);
    $result = curl_exec($ch);
		$curl_error = curl_error($ch);
    $curl_errno  = curl_errno($ch);
    curl_close($ch);
    if ($curl_errno) {
        bz_bridge_log('BuzzStreams SSO curl error', [
            'error' => $curl_error,
            'url'   => $qd_url
        ]);
    } else {
        $resp = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            bz_bridge_log('BuzzStreams SSO JSON decode error', [
                'result' => $result,
                'error'  => json_last_error_msg()
            ]);
        } elseif (!empty($resp['payload']) && is_array($resp['payload'])) {
            $payload = $resp['payload'];
            bz_bridge_log('BuzzStreams endpoint successful', ['payload'=>$payload]);
        }
    }
}

// Redirect to WordPress Login
if (!is_array($payload)) {
    bz_bridge_log('No BuzzSocial JWT/buzz_sso cookie present or missing secret', [
        'sso_token' => !empty($sso_token),
        'secret' => (bool)$BUZZ_SSO_SECRET
    ]);
    bz_redirect_to_wp_login($bridge_url, 'social');
		exit;
}

// =========================================================
// SSO JWT CLAIM EXTRACTION & REQUIRED CLAIMS VALIDATION
// =========================================================
// 2. Extract claims (use legacy keys as fallback for backwards compatibility)
$claim_wp_user_id    = isset($payload['wp_user_id'])    ? (int)$payload['wp_user_id'] : 0;
$claim_wp_user_login = isset($payload['wp_user_login']) ? (string)$payload['wp_user_login'] : '';
$claim_wp_user_email = isset($payload['wp_user_email']) ? (string)$payload['wp_user_email'] : '';
$claim_qd_user_id    = isset($payload['qd_user_id'])    ? (int)$payload['qd_user_id'] : 0;

$original_claims = [
    'claim_wp_user_id'    => $claim_wp_user_id,
    'claim_wp_user_login' => $claim_wp_user_login,
    'claim_wp_user_email' => $claim_wp_user_email,
    'claim_qd_user_id'    => $claim_qd_user_id
];

bz_bridge_log('JWT SSO claims extracted', array_merge($original_claims, ['raw_payload' => $payload]));

// 3. Required claims guard
if (!$claim_wp_user_id || !$claim_wp_user_login || !$claim_wp_user_email) {
    bz_bridge_log('Missing "original_claims" required claims (JWT incomplete)', $original_claims);
    bz_redirect_to_wp_login($bridge_url, 'social');
    exit;
}

// 4. Canonicalization: prefer already set session fields for UI only (do NOT trust for SSO)
$canonical = [
    'wp_user_id'    => $claim_wp_user_id,
    'wp_user_login' => $claim_wp_user_login,
    'wp_user_email' => $claim_wp_user_email,
    'qd_user_id'    => $claim_qd_user_id,
];

bz_bridge_log('Canonical pre-mapping values', [
    'canonical' => $canonical,
    'session'   => $_SESSION ?? [],
]);



// =========================================================
// Auto-register if no user exists and auto-registration allowed
// =========================================================
// Patch: auto-register if required, using correct variable
if (($canonical['wp_user_id'] && !$canonical['qd_user_id']) && BUZZ_SSO_AUTO_REGISTER) {
		$user 		= LoadEndPointResource('users');		
		$re_data 	= [
				'username' => $canonical['wp_user_login'],
				'email'    => $canonical['wp_user_email'],
				'password' => bin2hex(random_bytes(16)), // random password; login only via SSO
				'active'   => 1
		];
		$regestered_user = $user->register($re_data);
    if ($regestered_user['code'] == 200) {
        // Save new user_id in both canonical and session for subsequent matching
        $qd_user_id = (int) $regestered_user['userId'];
        $canonical['qd_user_id'] = $qd_user_id;
        $_SESSION['qd_auto_registered'] = true;
        bz_bridge_log('Auto-registered BuzzSocial user from SSO', [
            'user_id' => $qd_user_id,
            'wp_user' => $canonical['wp_user_id']
        ]);
    } else {
        $errors[] = 'Could not auto-register BuzzSocial user.';
        bz_bridge_log('QD_SSO_Login: registration failed', ['canonical'=>$canonical]);
        echo json_encode(['status'=>500, 'errors'=>$errors]);
        error_reporting($old_err);
        exit;
    }
}



// Persist canonical session values (set wp_user_login only if not set already to keep it immutable)
// -- 2. HYDRATE CANONICAL SESSION FIELDS FOR CURRENT UI
$final_qd_user_id = $canonical['qd_user_id'] ?? $qd_user_id;
if (!isset($_SESSION['wp_user_login'])) 
$_SESSION['wp_user_login'] = $_SESSION['wp_user_login'] ?? trim($claim_wp_user_login);
$_SESSION['wp_user_id']    = (int)$claim_wp_user_id;
$_SESSION['wp_user_email'] = trim($claim_wp_user_email);
$_SESSION['qd_user_id']    = (int)$final_qd_user_id;

bz_bridge_log('After mapping/registration - canonical session snapshot', [
    'wp_user_id'    => $_SESSION['wp_user_id'],
    'wp_user_login' => $_SESSION['wp_user_login'],
    'wp_user_email' => $_SESSION['wp_user_email'],
    'qd_user_id'    => $_SESSION['qd_user_id'],
]);

// Build SSO token and choose username for the client (username = wp_user_login)
$sso_username = $_SESSION['wp_user_login'];











// -----------------------------
// last_url derivation & normalization
// -----------------------------
$site_base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
$last_url = '/';
foreach (['last_url'] as $k) {
    if (!empty($_GET[$k]))  { $last_url = (string)$_GET[$k]; break; }
    if (!empty($_POST[$k])) { $last_url = (string)$_POST[$k]; break; }
    if (!empty($_COOKIE[$k])) { $last_url = (string)$_COOKIE[$k]; break; }
}
if (!$last_url || ($site_base && strpos($last_url, $site_base) !== 0)) $last_url = '/';








































































// -----------------------------
// Build $ajax_url for the bridge page so the client POST preserves redirect_to
// Place this after $last_url / $sso_username / $sso_token are set and BEFORE the HTML render.
// -----------------------------

$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php') . '?sso_action=do_login';

bz_bridge_log('SSO client payload prepared', [
    'sso_username'     => $sso_username,
    'sso_token_len'    => strlen($sso_token),
    'ajax_url'         => $ajax_url,
    'last_url'         => $last_url
]);









































if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login') {
    QD_SSO_Login();
    exit;
}
//QD_SSO_Login();
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

//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 6
function QD_SSO_Login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: /social/qd-sso-bridge.php", true, 302);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    global $BUZZ_SSO_SECRET, $sso_token, $config, $canonical;

    $username = isset($_POST['username']) ? (string)$_POST['username'] : '';
    $last_url = isset($_POST['last_url']) ? (string)$_POST['last_url'] : '/';

    bz_bridge_log('QD_SSO_Login called', ['post_username'=>$username, 'pw_len'=>strlen($sso_token)]);

    if (!$BUZZ_SSO_SECRET) {
        bz_bridge_log('QD_SSO_Login: BUZZ_SSO_SECRET missing');
        echo json_encode(['status'=>500,'errors'=>['Server misconfiguration']]); exit;
    }
    // token must be WPSSO.v1.<b64json>.<b64sig>
    // Expectations: prefer session values (trusted) else claims
    $exp_qd    = (int)($canonical['qd_user_id'] ?? 0);
    $exp_wp    = (int)($canonical['wp_user_id'] ?? 0);
    $exp_login = (string)($canonical['wp_user_login'] ?? '');
    $exp_email = (string)($canonical['wp_user_email'] ?? '');

    bz_bridge_log('QD_SSO_Login expectations', ['qd'=>$exp_qd,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email]);

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

    bz_bridge_log('QD_SSO_Login candidates count', ['count'=>count($candidates)]);

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
        bz_bridge_log('QD_SSO_Login: no accepted candidate (>=3 required)', [
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
                bz_bridge_log('SetLoginWithSession invoked', ['email'=>$exp_email]);
            } catch (Throwable $e) {
                bz_bridge_log('SetLoginWithSession exception', ['ex'=>$e->getMessage()]);
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
        bz_bridge_log('Preparing to sync WordPress metadata into QuickDate', ['wp_user_id'=>$exp_wp,'wp_email'=>$exp_email]);

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
                    bz_bridge_log('sync_user_to_quickdate result', ['email'=>$exp_email,'wp_user_id'=>$exp_wp,'ok'=>(bool)$ok]);
                    $did_sync = (bool)$ok;
                } else {
                    bz_bridge_log('wp_get_full_user_data returned empty/invalid', ['wp_user_id'=>$exp_wp]);
                }
            } else {
                bz_bridge_log('WP DB connection not available for sync', []);
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
                    bz_bridge_log('qd_update_user (fallback) result', ['email'=>$exp_email,'update_keys'=>array_keys($qd_update),'result'=> (bool)$ok]);
                    $did_sync = (bool)$ok;
                } else {
                    bz_bridge_log('No QuickDate-updatable fields found in WP user data (fallback)', ['email'=>$exp_email,'candidate_keys'=>array_keys($qd_candidate)]);
                }
            } else {
                bz_bridge_log('wp_get_full_user_data returned empty/invalid for fallback sync', ['wp_user_id'=>$exp_wp]);
            }
        } else {
            bz_bridge_log('Skipping QuickDate sync - missing prerequisites', ['has_email'=>!empty($exp_email),'has_wp_id'=>!empty($exp_wp),'functions'=>[
                'sync_user_to_quickdate'=>function_exists('sync_user_to_quickdate'),
                'get_user_field_metadata'=>function_exists('get_user_field_metadata'),
                'wp_get_full_user_data'=>function_exists('wp_get_full_user_data'),
                'qd_update_user'=>function_exists('qd_update_user')
            ]]);
        }
        if (!$did_sync) {
            bz_bridge_log('Post-login QuickDate sync did not run or reported failure', ['wp_user_id'=>$exp_wp,'email'=>$exp_email]);
        }
    } catch (Throwable $e) {
        bz_bridge_log('Exception during QuickDate sync', ['ex'=>$e->getMessage()]);
    }

    // Decide redirect URL
    $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/find-matches';
    if (!empty($accepted_user['start_up']) && $accepted_user['start_up'] == 3 && !empty($accepted_user['verified'])) {
        $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/steps';
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

    bz_bridge_log('QD_SSO_Login success', ['user_id'=>$accepted_user['id'],'matches'=>$accepted_matches,'redirect'=>$url,'session_id'=>session_id()]);

    http_response_code(200);
    echo json_encode(['status'=>200,'location'=>$url]);
    exit;
}



//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 7
// -----------------------------------------------------------------------------
// QD SSO Bridge HTML: stateless, production-grade, debug/diagnostic friendly
// -----------------------------------------------------------------------------

/* Render bridge page */
bz_bridge_log('Rendering QD SSO bridge page', [
    'sso_username'=>$sso_username,
    'sso_token_len'=>strlen($sso_token),
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
    <div class="title">BuzzSocial...…</div>
    <div id="status" class="status">Preparing secure session…</div>
    <?php if (bz_is_debug()): ?>
      <div class="dbg"><pre><?php echo htmlspecialchars(print_r([
          'ajax_url'=>$ajax_url,
          'post'=>['username'=>$sso_username,'sso_token'=>'($sso-token)','last_url'=>$last_url,'remember_device'=>'on'],
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
      sso_token: <?php echo json_encode($sso_token); ?>,
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
          statusEl && (statusEl.className='status ok', statusEl.textContent='Connected to socials! Redirecting...…');
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
             + '&sso_token=' + encodeURIComponent(payload.sso_token)
             + '&remember_device=on'
             + '&last_url=' + encodeURIComponent(payload.last_url);
    xhr.send(body);
  })();
  </script>
</body>
</html>