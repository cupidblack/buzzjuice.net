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

// ======================================
// START: CONFIGURATIONS / DEFAULTS
// ======================================
if (!defined('WP_BASE_SITE_URL'))       define('WP_BASE_SITE_URL', 'https://buzzjuice.net');
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/qd_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL_ACCESS'))    define('BUZZ_SSO_TTL_ACCESS', 12345);
if (!defined('BUZZ_SSO_TTL_REFRESH'))   define('BUZZ_SSO_TTL_REFRESH', 216000);

$base_site_url      = defined('WP_BASE_SITE_URL') ? WP_BASE_SITE_URL : (getenv('WP_BASE_SITE_URL') ?: null);
$base_social_url    = rtrim($config->uri ?? QUICKDATE_SITE_URL ?? '', '/');

$BUZZ_SSO_SECRET    = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);																																			 
$BUZZ_SSO_SECRET    = (string)($BUZZ_SSO_SECRET ?? '');
$sso_token          = trim($_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? ''));
$sso_action         = $_REQUEST['sso_action'] ?? '';

        $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        $qd_conn = function_exists('get_qd_db_conn') ? get_qd_db_conn() : null;

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

// LOOP PROTECTION
$loop_count = function_exists('bz_bridge_loop_count') ? bz_bridge_loop_count(true) : 0;

if ($loop_count > 5) {
    bz_bridge_log('Bridge loop suspected — forcing fallback', [
        'loop_count' => $loop_count
    ]);
    if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
    $forced_last_url_fallback = true;
} else {
    $forced_last_url_fallback = false;
}

// ***** Replay protection: JTI store (20-30 min, 60 minute cleanup) *****
define('QD_SSO_JTI_STORE', __DIR__ . '/../data/.bz_sso_jti_store');
if (!is_dir(QD_SSO_JTI_STORE)) @mkdir(QD_SSO_JTI_STORE, 0755, true);
if (mt_rand(1, 30) === 15) bz_cleanup_jti_store();

// -------------------------------------------------------
// BOOTSTRAP CHECKS — REQUIRE QD CONFIG AND SQL
// -------------------------------------------------------
global $config, $conn, $qd_conn, $wp_conn;
if (empty($config->uri) || empty($qd_conn)) {
    bz_bridge_log('Bootstrap incomplete - missing $config or $conn');
    bz_debug_page('Bootstrap incomplete', ['$config' => $config ?? null, '$qd_conn' => (bool)$conn]);
    bz_redirect_to_wp_login($base_site_url, 'social');
    exit;
}

// ===============================
// last_url Derivation & Normalization Block
// ===============================

// Ensure site base is known
$site_host = parse_url($base_social_url, PHP_URL_HOST) ?: '';

// 1) Check explicit last_url/redirect_to from GET, POST, COOKIE
$last_url = '';
foreach (['last_url', 'redirect_to'] as $param) {
    if (!empty($_GET[$param]))  { $last_url = (string)$_GET[$param]; break; }
    if (!empty($_POST[$param])) { $last_url = (string)$_POST[$param]; break; }
    if (!empty($_COOKIE[$param])) { $last_url = (string)$_COOKIE[$param]; break; }
}

// 2) Fallback to REQUEST_URI (not bridge/self-reference)
if (!$last_url) {
    $req_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $bridge_path = parse_url($_SERVER['PHP_SELF'] ?? '', PHP_URL_PATH);
    if ($req_uri && $req_uri !== $bridge_path && strpos($req_uri, basename(__FILE__)) === false) {
        $candidate = $base_social_url . $req_uri;
        $candidate_host = parse_url($candidate, PHP_URL_HOST);
        if ($candidate_host && strcasecmp($candidate_host, $site_host) === 0) {
            $last_url = $candidate;
        }
    }
}

// 3) Fallback to HTTP_REFERER ONLY if same-site
if (!$last_url && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = trim((string)$_SERVER['HTTP_REFERER']);
    $referer_host = parse_url($referer, PHP_URL_HOST);
    if ($referer_host && strcasecmp($referer_host, $site_host) === 0) {
        $last_url = $referer;
    }
}

// 4) Normalize relative paths to absolute; enforce same-site
if ($last_url) {
    if (!preg_match('#^https?://#i', $last_url)) {
        $last_url = strpos($last_url, '/') === 0
            ? $base_social_url . $last_url
            : $base_social_url . '/' . ltrim($last_url, '/');
    }
    $candidate_host = parse_url($last_url, PHP_URL_HOST);
    if (!$candidate_host || strcasecmp($candidate_host, $site_host) !== 0) {
        $last_url = '';
    }
}

// 5) Prevent bridge/self-reference loop
if (!empty($last_url) && function_exists('bz_is_bridge_url') && bz_is_bridge_url($last_url, $base_social_url)) {
//    bz_bridge_log('last_url rejected: bridge/self-reference detected', ['last_url'=>$last_url, 'site_base'=>$base_social_url]);
    $last_url = $base_social_url . '/';
}

// 6) Final fallback
if (!$last_url || !empty($forced_last_url_fallback)) {
    $last_url = $base_social_url . '/';
}

// 7) Persist for use after SSO login
$_SESSION['last_url'] = $last_url;
//bz_bridge_log('last_url derivation complete', ['last_url' => $last_url]);

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



// ===================================================================
// START: SESSION MANAGEMENT / ENDPOINTS / PAYLOAD / DATA MAPPING
// ===================================================================
// -----------------------------------------------------
// STEP 1: SESSION SAFETY GUARD FOR DUAL-TOKEN JWT SSO
// -----------------------------------------------------
// --- Current WoWonder authentication state ---
$qd_loggedin        = !empty(IS_LOGGED);
$qd_user_id 	    = !empty(auth()->id) ?? null;

// --- Current PHP session state ---
$session_qd_user_id = $_SESSION['qd_user_id'] ?? null;
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
if (!$wordpress_logged_in_) {

    // --- CASE A.1: Both WP and WoWonder session fully active ---
    $already_logged_in = (
        $qd_loggedin &&
        !empty($qd_user_id) &&
        !empty($session_user_id) &&
        !empty($session_qd_user_id) &&
        ((string)$session_qd_user_id === (string)$qd_user_id)
    );

    if ($already_logged_in) {
        bz_bridge_log(
            'Already logged in; Both WordPress & QuickDate sessions confirmed; safe redirect.',
            [
                'qd_loggedin'        => $qd_loggedin,
                'qd_user_id'         => $qd_user_id,
                'session_qd_user_id' => $session_qd_user_id,
                'session_user_id'    => $session_user_id,
                'request_uri'        => $_SERVER['REQUEST_URI'] ?? null,
                'http_referer'       => $_SERVER['HTTP_REFERER'] ?? null,
                'redirect_to'        => $_GET['redirect_to'] ?? null
            ]
        );

        header('Location: ' . $last_url);
        exit;
    }

    // --- CASE A.2: WP logged in but QuickDate session not active ---
//bz_bridge_log('WP session active, QD session inactive — proceeding to SSO bootstrap.', ['qd_loggedin' => $qd_loggedin, 'qd_user_id' => $qd_user_id, 'session_user_id' => $session_user_id, 'session_qd_user_id' => $session_qd_user_id, ]); 
    // Allow SSO bootstrap code (dual-token flow) to run next
}

// ---------------------------------------------------------------
// CASE B: WordPress not logged in — clear any stale QuickDate session
// ---------------------------------------------------------------
bz_bridge_log(
    'WP or QD session inactive — proceeding to SSO login.',
    [
        'qd_loggedin'        => $qd_loggedin,
        'qd_user_id'         => $qd_user_id,
        'session_user_id'    => $session_user_id,
        'session_qd_user_id' => $session_qd_user_id,
    ]
);

// --- Explicitly destroy stale QuickDate session ---
if (isset($_SESSION) && !empty($session_qd_user_id) || !empty($session_user_id)) {
    session_unset();
    @session_destroy();
}

// Proceed to SSO bootstrap — user must re-login

// =============================================================================
// BuzzStreams Fetch Stateless SSO Payload Orchestration (WordPress → QuickDate)
// =============================================================================
$audience = 'social';
$BUZZ_SSO_SECRET = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

$access_token  = $_COOKIE['buzz_access'] ?? $_REQUEST['buzz_access'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? $_REQUEST[BUZZ_SSO_COOKIE] ?? null);
$refresh_token = $_COOKIE['buzz_refresh'] ?? $_REQUEST['buzz_refresh'] ?? null;

$access_payload = false;

// 1. Try validating access token for current bridge OR universal audience
if ($access_token) {
    $access_payload = bz_sso_jwt_validate($access_token, $BUZZ_SSO_SECRET, $audience, 'access');
    if (!$access_payload) {
        $access_payload = bz_sso_jwt_validate($access_token, $BUZZ_SSO_SECRET, 'buzznet', 'access');
    }
}

// 2. If access still invalid, try silent local minting using refresh token
if (!$access_payload && $refresh_token) {
    $refresh_payload = bz_sso_jwt_validate($refresh_token, $BUZZ_SSO_SECRET, $audience, 'refresh');
    if (!$refresh_payload) {
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

// 3. If still invalid, call WordPress endpoint using server-side request *with correct Cookie/Header*
if (!$access_payload && $wordpress_logged_in_) {
    $wp_token_url = 'https://buzzjuice.net/?sso_action=issue_tokens&aud=' . urlencode($audience);

    // Build correct Cookie header (only WP auth cookies)
    $cookies = '';
    foreach ($_COOKIE as $name => $val) {
        if (strpos($name, 'wordpress_logged_in_') === 0 || strpos($name, 'wordpress_sec_') === 0) {
            $cookies .= "$name=$val; ";
        }
    }
    $cookies = trim($cookies);

    $headers = [
        'Cookie: ' . $cookies,
        'User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'BuzzSSO/1.0')
    ];

    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
            'timeout' => 5
        ]
    ]);

    // Suppress warnings with @ and detect HTTP status
    $resp = @file_get_contents($wp_token_url, false, $context);
    $http_code = 0;
    if (isset($http_response_header[0])) {
        if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $matches)) {
            $http_code = (int)$matches[1];
        }
    }

    // Handle 200 response
    if ($resp !== false && $http_code === 200) {
        $data = json_decode($resp, true);
        if (!empty($data['access'])) {
            bz_sso_set_cookie('buzz_access', $data['access'], time()+BUZZ_SSO_TTL_ACCESS);
            if (!empty($data['refresh'])) {
                bz_sso_set_cookie('buzz_refresh', $data['refresh'], time()+BUZZ_SSO_TTL_REFRESH);
            }
            $access_payload =
                bz_sso_jwt_validate($data['access'], $BUZZ_SSO_SECRET, $audience, 'access')
                ?: bz_sso_jwt_validate($data['access'], $BUZZ_SSO_SECRET, 'buzznet', 'access');
        }
    }
    
    // Handle 401 Unauthorized explicitly
    elseif ($http_code === 401) {
        bz_bridge_log('WP SSO endpoint returned 401 — user not logged in');
        $redirect_to = $_SERVER['REQUEST_URI'] ?? '/social';
        header('Location: /wp-login.php?try=qd00&redirect_to=/social/qd-sso-bridge.php?last_url=' . urlencode($last_url));
        exit;
    }
    
}

// 4. If still invalid, use browser JS fallback SSO
if (!$access_payload && $wordpress_logged_in_) {
    $aud = htmlspecialchars($audience, ENT_QUOTES, 'UTF-8');
    $redirect = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Authorizing...</title>
    </head>
    <body>
    <div id="status">Attempting secure SSO authorization…</div>
    <script>
    (function() {
        if (window.sessionStorage && sessionStorage.getItem('sso_js_fallback_tried')) {
            document.getElementById('status').textContent =
                "SSO failed. Please login via WordPress or try again.";
            return;
        }
        if (window.sessionStorage) sessionStorage.setItem('sso_js_fallback_tried', '1');

        fetch('https://buzzjuice.net/?sso_action=issue_tokens&aud=<?php echo $aud; ?>', {
            credentials: 'include'
        })
        .then(function(resp) {
            if (!resp.ok) throw new Error("HTTP " + resp.status);
            return resp.json();
        })
        .then(function(data) {
            if (!data.access) throw new Error("No access token received");
            document.cookie = 'buzz_access=' + data.access + '; path=/; domain=.buzzjuice.net; secure; samesite=lax';
            if (data.refresh)
                document.cookie = 'buzz_refresh=' + data.refresh + '; path=/; domain=.buzzjuice.net; secure; samesite=lax';
            document.getElementById('status').textContent = "Token received. Redirecting…";
            window.location.href = '<?php echo $redirect; ?>';
        })
        .catch(function(e) {
            document.getElementById('status').textContent =
                "Network or authentication error during SSO. Please try again.";
        });
    })();
    </script>
    </body>
    </html>
    <?php
    exit;
}

// 5. If all failed, redirect to login
if (!$access_payload) {
    bz_bridge_log('Dual-token bootstrap failed — redirecting to login');
    $redirect_to = $_SERVER['REQUEST_URI'] ?? '/social';
    header('Location: /wp-login.php?try=qd01&redirect_to=/social/qd-sso-bridge.php?last_url=' . urlencode($last_url));
    exit;
}

// Hydrate canonical session for downstream mapping
$_SESSION['wp_user_id']    = (int)($access_payload['wp_user_id'] ?? 0);
$_SESSION['wp_user_login'] = (string)($access_payload['wp_user_login'] ?? '');
$_SESSION['wp_user_email'] = (string)($access_payload['wp_user_email'] ?? '');
$_SESSION['qd_user_id']    = (int)($access_payload['qd_user_id'] ?? 0);
$_SESSION['wp_qd_SSO_Login'] = true;

// -----------------------------
// Required claims guard
// -----------------------------
if (!$_SESSION['wp_user_id'] || !$_SESSION['wp_user_login'] || !$_SESSION['wp_user_email']) {
    bz_bridge_log('Missing required claims (cookie incomplete)', $access_payload);
    header('Location: /wp-login.php?try=qd02&redirect_to=/social/qd-sso-bridge.php?last_url=' . urlencode($last_url));
    exit;
}

/* bz_bridge_log('buzz_access token claims hydrated into session', [
    'wp_user_id'      => $_SESSION['wp_user_id'],
    'wp_user_login'   => $_SESSION['wp_user_login'],
    'wp_user_email'   => $_SESSION['wp_user_email'],
    'qd_user_id'      => $_SESSION['qd_user_id'],
    'raw_payload'     => $access_payload
]);
*/


// =======================================================================================
// SSO: Auto-register WoWonder user if missing
// Updates WordPress usermeta 'qd_user_id' after successful registration
// Redirects to /wp-login.php?redirect_to=/members/me/settings/ if registration fails
// QUICKDATE SSO IDENTITY RESOLVER + AUTO REGISTRATION (robust, unified, no forced redirects)
// =======================================================================================

// ---------------------------
// HELPERS (safe for all contexts)
// ---------------------------
if (!function_exists('bz_clean_username')) {
    function bz_clean_username($username) {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username ?? '');
        if (strlen($username) < 5) $username .= rand(1000,9999);
        return substr($username, 0, 32);
    }
}

if (!function_exists('bz_generate_unique_username')) {
    function bz_generate_unique_username($base, $conn, $table = 'users') {
        $base = bz_clean_username($base);
        $candidate = $base; $i = 1;
        while (true) {
            $esc = mysqli_real_escape_string($conn, $candidate);
            $q = mysqli_query($conn, "SELECT id FROM {$table} WHERE username='{$esc}' LIMIT 1");
            if (!$q || mysqli_num_rows($q) === 0) return $candidate;
            $candidate = substr($base . $i++, 0, 32);
        }
    }
}

if (!function_exists('bz_fetch_wp_qd_user_id')) {
    function bz_fetch_wp_qd_user_id($wp_user_id) {
        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        if (!$conn || !$wp_user_id) return 0;
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        $q = mysqli_query($conn, "SELECT meta_value FROM {$table} WHERE user_id={$wp_user_id} AND meta_key='qd_user_id' LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) return (int)$row['meta_value'];
        return 0;
    }
}

if (!function_exists('bz_update_wp_qd_user_id')) {
    function bz_update_wp_qd_user_id($wp_user_id, $qd_user_id) {
        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        if (!$conn) return false;
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        // MySQL 5.7+/InnoDB will upsert here, otherwise fallback is safe (INSERT, fail = ignore)
        @mysqli_query($conn, "
            INSERT INTO {$table} (user_id, meta_key, meta_value)
            VALUES ($wp_user_id, 'qd_user_id', '$qd_user_id')
            ON DUPLICATE KEY UPDATE meta_value='$qd_user_id'
        ");
        return true;
    }
}

// =======================================================================================
// MAIN FLOW: NO REDIRECTS TO wp-login.php, FULL CONFLICT RECONCILIATION
// =======================================================================================
if (
    empty($access_payload['qd_user_id']) &&
    BUZZ_SSO_AUTO_REGISTER &&
    !empty($access_payload['wp_user_id']) &&
    !empty($access_payload['wp_user_login']) &&
    !empty($access_payload['wp_user_email'])
) {
    $wp_user_id  = (int)$access_payload['wp_user_id'];
    $wp_username = trim($access_payload['wp_user_login']);
    $wp_email    = trim($access_payload['wp_user_email']);
    $qd_conn     = function_exists('get_qd_db_conn') ? get_qd_db_conn() : null;
    $table       = 'users';

    $qd_user_id   = 0;
    $max_attempts = 5;
    $attempt      = 0;
    $fatal_error  = null;

    while ($attempt < $max_attempts) {
        // 1. CHECK EXISTING MAP
        $existing_qd_id = bz_fetch_wp_qd_user_id($wp_user_id);
        if ($existing_qd_id) {
            $qd_user_id = $access_payload['qd_user_id'] = $existing_qd_id;
            bz_bridge_log('QD SSO: Used prior mapping from usermeta', [
                'wp_user_id' => $wp_user_id,
                'qd_user_id' => $qd_user_id
            ]);
            break;
        }

        // 2. SEARCH QUICKDATE USER DB FOR USERNAME/EMAIL
        $username_esc = mysqli_real_escape_string($qd_conn, $wp_username);
        $email_esc    = mysqli_real_escape_string($qd_conn, $wp_email);
        $q = mysqli_query($qd_conn, "SELECT id,username,email FROM {$table} WHERE username='{$username_esc}' OR email='{$email_esc}'");
        $rows = [];
        while ($r = $q ? mysqli_fetch_assoc($q) : null) $rows[] = $r;

        $user_id_by_username = null; $user_id_by_email = null;
        foreach ($rows as $r) {
            if (strcasecmp($r['username'], $wp_username) === 0) $user_id_by_username = (int)$r['id'];
            if (strcasecmp($r['email'], $wp_email)    === 0) $user_id_by_email    = (int)$r['id'];
        }

        // 3. PERFECT MATCH
        if ($user_id_by_username && $user_id_by_email && $user_id_by_username === $user_id_by_email) {
            $qd_user_id = $user_id_by_username;
            bz_update_wp_qd_user_id($wp_user_id, $qd_user_id);
            $access_payload['qd_user_id'] = $qd_user_id;
            bz_bridge_log('QD SSO: PERFECT MATCH (username & email)', [
                'wp_user_id'=>$wp_user_id, 'qd_user_id'=>$qd_user_id
            ]);
            break;
        }

        // 4. EMAIL MATCH (username differs): update username
        if (
            $user_id_by_email &&
            (
                !$user_id_by_username ||
                strcasecmp($rows[array_search($user_id_by_email, array_column($rows, 'id'))]['username'], $wp_username) !== 0
            )
        ) {
            $qd_user_id = $user_id_by_email;
            $desired_username = bz_generate_unique_username($wp_username, $qd_conn, $table);

            $reserved_usernames = $qd['reserved_usernames'] ?? [];
            $is_reserved = in_array($desired_username, $reserved_usernames) ||
                (function_exists('QD_IsNameExist') && QD_IsNameExist($desired_username));

            if ($is_reserved) {
                $fatal_error = 'Your desired QuickDate username is reserved or already exists. Please contact <a href="mailto:support@buzzjuice.net">support@buzzjuice.net</a> or change your WordPress username.';
                break;
            }
            if (strlen($desired_username) < 5) {
                $fatal_error = 'Your username is too short for QuickDate. Please update it in WordPress.';
                break;
            }
            // Use QuickDate's API if available, else SQL fallback
            $update_success = false;
            if (function_exists('LoadEndPointResource')) {
                $user_api = LoadEndPointResource('users');
                if ($user_api && method_exists($user_api, 'update_general_setting')) {
                    $update = $user_api->update_general_setting([
                        'user_id'  => $qd_user_id,
                        'username' => $desired_username
                    ]);
                    $update_success = ($update && isset($update['code']) && $update['code'] == 200);
                }
            }
            if (!$update_success)
                $update_success = mysqli_query($qd_conn, "UPDATE {$table} SET username='" . mysqli_real_escape_string($qd_conn,$desired_username) . "' WHERE id=".(int)$qd_user_id);

            bz_bridge_log('QD SSO: Username sync (email match)', [
                'qd_user_id'  => $qd_user_id,
                'desired_username' => $desired_username,
                'result'      => $update_success
            ]);
            if ($update_success) {
                bz_update_wp_qd_user_id($wp_user_id, $qd_user_id);
                $access_payload['qd_user_id'] = $qd_user_id;
                break;
            } else {
                $fatal_error = 'A server error occurred updating your QuickDate username. Contact <a href="mailto:support@buzzjuice.net">support@buzzjuice.net</a>.';
                break;
            }
        }

        // 5. USERNAME/EMAIL SPLIT: quarantine both, retry
        if ($user_id_by_username && $user_id_by_email && $user_id_by_username !== $user_id_by_email) {
            $prefix = 'conflict_' . rand(10000,99999) . '_';
            mysqli_query($qd_conn, "UPDATE {$table} SET username='" . mysqli_real_escape_string($qd_conn,$prefix.$wp_username) . "' WHERE id=" . intval($user_id_by_username));
            $new_email = preg_replace('/^([^@]+)/', $prefix.'$1', $wp_email);
            mysqli_query($qd_conn, "UPDATE {$table} SET email='" . mysqli_real_escape_string($qd_conn,$new_email) . "' WHERE id=" . intval($user_id_by_email));
            bz_bridge_log('QD SSO: Username/email split conflict: both legacy', [
                'username_user' => $user_id_by_username,
                'email_user'    => $user_id_by_email
            ]);
            // continue, will retry loop
        }

        // 6. USERNAME ONLY: quarantine that legacy user
        if ($user_id_by_username && !$user_id_by_email) {
            $prefix = 'legacy_' . rand(1000,9999) . '_';
            mysqli_query($qd_conn, "UPDATE {$table} SET username='" . mysqli_real_escape_string($qd_conn, $prefix.$wp_username) . "' WHERE id=" . intval($user_id_by_username));
            bz_bridge_log('QD SSO: Username-only collision resolved by rename', [
                'old_user_id' => $user_id_by_username
            ]);
            // continue, will retry loop
        }

        // 7. REGISTER NEW QUICKDATE USER
        $final_username = bz_generate_unique_username($wp_username, $qd_conn, $table);
        $user_api = function_exists('LoadEndPointResource') ? LoadEndPointResource('users') : null;
        $registered_user = $user_api && method_exists($user_api, 'register')
            ? $user_api->register([
                'username' => $final_username,
                'email'    => $wp_email,
                'password' => bin2hex(random_bytes(16)),
                'active'   => 1
            ])
            : null;
        if ($registered_user && isset($registered_user['code']) && $registered_user['code'] == 200) {
            $qd_user_id = (int)$registered_user['userId'];
            $access_payload['qd_user_id'] = $qd_user_id;
            bz_update_wp_qd_user_id($wp_user_id, $qd_user_id);
            $_SESSION['qd_auto_registered'] = true;
            bz_bridge_log('QD SSO: New user registered', [
                'qd_user_id' => $qd_user_id, 'username' => $final_username, 'email' => $wp_email
            ]);
            break;
        }

        usleep(100000);
        $attempt++;
    } // while

    // 8. SURFACE FATAL ERROR (NO REDIRECTS)
    if (empty($qd_user_id) || $fatal_error) {
        bz_bridge_log('QD SSO: Registration failed', [
            'payload'    => $access_payload,
            'attempts'   => $attempt,
            'fatal_err'  => $fatal_error
        ]);
        $err_message = $fatal_error ?: "We're unable to create or connect your QuickDate account at this time.<br>
        Please contact <a href='mailto:support@buzzjuice.net'>support@buzzjuice.net</a>
        or return to the <a href='https://buzzjuice.net/dashboard'>dashboard</a>.";
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($is_ajax) {
            echo json_encode(['status'=>500, 'error'=>$err_message]);
        } else {
            echo "<div class='status err'>{$err_message}</div>";
        }
        exit;
    }
}

// SESSION FINALIZATION
$final_qd_user_id = $access_payload['qd_user_id'] ?? $qd_user_id ?? null;
if (!isset($_SESSION['wp_user_login']))
    $_SESSION['wp_user_login'] = (string)($access_payload['wp_user_login'] ?? '');
$_SESSION['wp_user_id']    = (int)($access_payload['wp_user_id'] ?? 0);
$_SESSION['wp_user_email'] = (string)($access_payload['wp_user_email'] ?? '');
$_SESSION['qd_user_id']    = (int)($final_qd_user_id ?? 0);

/* bz_bridge_log('After mapping/registration - canonical session snapshot', [
    'wp_user_id'    => $_SESSION['wp_user_id'],
    'wp_user_login' => $_SESSION['wp_user_login'],
    'wp_user_email' => $_SESSION['wp_user_email'],
    'qd_user_id'    => $_SESSION['qd_user_id'],
]);
*/

// -----------------------------
// Build SSO token and choose username for the client (username = wp_user_login)
$sso_username = $_SESSION['wp_user_login'];



// -----------------------------
// Build $ajax_url for the bridge page so the client POST preserves redirect_to
// Place this after $last_url / $sso_username / $sso_token are set and BEFORE the HTML render.
// -----------------------------

$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php') . '?sso_action=do_login';

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
            'qd_user_id'    => ($_SESSION['qd_user_id'] ?? null)
        ] : null
    ]);

    // DO NOT call header('Location: ...') or exit() here.
    // The bridge HTML will render and client JS will POST to $ajax_url (which includes redirect_to).
    // QD_SSO_Login() will return JSON { location: "..." } and the client JS will perform the final redirect.
}
// ------------------------------
//bz_bridge_log('SSO session prepared', ['sso_username'=>$sso_username,'sso_token_len'=>strlen($sso_token),'ajax_url'=>$ajax_url,'last_url'=>$last_url]);

// Helper function: place in shared/wwqd_bridge.php or above Wo_SSO_Login()
if (!function_exists('bz_clear_wp_qd_user_id')) {
    /**
     * Removes 'qd_user_id' usermeta for WordPress user.
     * On success: reloads page.
     * On failure: redirects to WP login with /social redirect.
     */
    function bz_clear_wp_qd_user_id($wp_user_id) {
        $wp_conn = get_wp_db_conn();
        if (!$wp_conn || empty($wp_user_id)) {
            header('Location: /wp-login.php?try=qd06&redirect_to=/social/qd-sso-bridge.php?last_url=' . urlencode($last_url));
            exit;
        }
        $wp_user_id = (int)$wp_user_id;
        $meta_key = 'qd_user_id';
        $key_esc = mysqli_real_escape_string($wp_conn, $meta_key);
        // Defensive: Use table prefix if available
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        $del_query = "DELETE FROM {$table} WHERE user_id = $wp_user_id AND meta_key = '$key_esc'";
        $result = mysqli_query($wp_conn, $del_query);
        $affected = mysqli_affected_rows($wp_conn);
        
        bz_bridge_log('should redirect or reload around here:', [$wp_user_id, $meta_key, $key_esc, $table, $del_query, $result, $affected]);
        
        if ($result /*&& mysqli_affected_rows($wp_conn) > 0*/) {
            // Success: meta deleted, reload page (AJAX-safe)
            header('Location: /social');
            exit;
        } else {
            // Failed to clear mapping, redirect to login
            header('Location: /wp-login.php?try=qd07&redirect_to=/social/qd-sso-bridge.php?last_url=' . urlencode($last_url));
            exit;
        }
    }
}

// -----------------------------
// QD_SSO_Login endpoint (POST)
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
    global $BUZZ_SSO_SECRET, $sso_token, $config, $access_payload;

    // token must be WPSSO.v1.<b64json>.<b64sig>
    // Expectations: prefer session values (trusted) else claims
    $exp_qd     = (isset($access_payload['qd_user_id']) ? (int)$access_payload['qd_user_id'] : 0);
    $exp_wp     = (isset($access_payload['wp_user_id']) ? (int)$access_payload['wp_user_id'] : 0);
    $exp_login  = (isset($access_payload['wp_user_login']) ? (string)$access_payload['wp_user_login'] : '');
    $exp_email  = (isset($access_payload['wp_user_email']) ? (string)$access_payload['wp_user_email'] : '');

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
        $errors[] = 'No matching BuzzStreams account for SSO (>=3 identifiers required).';

        // PATCH: Orphan WoWonder ID in WP usermeta, clear it and reload/redirect
        //if (empty($db_user_id) || $db_user_id === 0 || $db_user_id == '' || $db_user_id == null) {
            if (!empty($exp_wp && $exp_login && $exp_email)) {
                bz_bridge_log('Wo_SSO_Login: orphan WoWonder ID detected, clearing WordPress usermeta', [
                    'wp_user_id' => $exp_wp,
                    'qd_user_id' => $exp_qd
                ]);
                bz_clear_wp_qd_user_id($exp_wp);
            }
            // No need to continue further; this function will exit after clearing.
        //}


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

    // ====================== WordPress → QuickDate Metadata Sync (Canonical Source) ======================

    try {
        bz_bridge_log('Preparing to sync WordPress metadata to QuickDate', [
            'wp_user_id' => $exp_wp,
            'wp_email'   => $exp_email
        ]);
        $did_sync = false;
    
        if (empty($exp_email) || empty($exp_wp)) {
            bz_bridge_log('QuickDate sync skipped: missing wp_user_id or wp_email');
            return;
        }
    
        // 1. Load full WordPress profile and metadata registry
        $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        $qd_conn = function_exists('get_qd_db_conn') ? get_qd_db_conn() : null;
        if (!$wp_conn || !$qd_conn) {
            bz_bridge_log('QuickDate sync skipped: missing WP or QD DB connection');
            return;
        }
    
        $wp_full = function_exists('wp_get_full_user_data') ? wp_get_full_user_data($wp_conn, $exp_wp) : null;
        if (!$wp_full || !is_array($wp_full)) {
            bz_bridge_log('QuickDate sync aborted: wp_get_full_user_data failed', ['wp_user_id'=>$exp_wp]);
            return;
        }
    
        $metadata = function_exists('get_user_field_metadata') ? get_user_field_metadata() : [];
        $public_fields  = $metadata['public_open_fields'] ?? [];
        $private_fields = $metadata['private_secure_fields'] ?? [];
        $field_map      = $metadata['field_map'] ?? [];
    
        // 2. Aggregate WordPress meta/xprofile/core fields
        $wp_all_meta = [];
        foreach ($wp_full['meta'] ?? [] as $k => $v) $wp_all_meta[$k] = $v;
    
        foreach ($wp_full['xprofile'] ?? [] as $k => $v) {
            $norm = strtolower(str_replace([' ','-'],'_',trim($k)));
            $wp_all_meta[$norm] = $v;
        }
    
        foreach (['user_login', 'user_email', 'first_name', 'last_name'] as $core_field) {
            if (!empty($wp_full[$core_field])) $wp_all_meta[$core_field] = $wp_full[$core_field];
        }
    
    
        // ======================================================
        // WordPress → QuickDate Role & Subscription Sync (Authoritative)
        // ======================================================
        
        // 1. Role → pro_type mapping (Edit here if you add roles!)
        $role_map = [
            'classic_lifestyle'  => 1,
            'silver_lifestyle'   => 2,
            'rockstar_lifestyle' => 3,
            'premium_lifestyle'  => 4,
            'jewel_affiliate'    => 2,
        ];
        // 2. Role priority (first found wins if multiple assigned)
        $role_priority = [
            'premium_lifestyle',
            'rockstar_lifestyle',
            'silver_lifestyle',
            'classic_lifestyle',
            'jewel_affiliate'
        ];
        
        // 3. Extract normalized user roles from WP data
        $wp_roles = [];
        // Preferred: $wp_full['roles'] from wp_get_full_user_data
        if (!empty($wp_full['roles']) && is_array($wp_full['roles'])) {
            $wp_roles = array_map('strtolower', $wp_full['roles']);
        } elseif (!empty($wp_full['meta']['wp_capabilities'])) {
            $maybe_caps = @unserialize($wp_full['meta']['wp_capabilities']);
            if (is_array($maybe_caps)) {
                $wp_roles = array_map('strtolower', array_keys(array_filter($maybe_caps)));
            } elseif (is_string($wp_full['meta']['wp_capabilities']) && strpos($wp_full['meta']['wp_capabilities'], '{') === 0) {
                $maybe_caps = json_decode($wp_full['meta']['wp_capabilities'], true);
                if (is_array($maybe_caps)) {
                    $wp_roles = array_map('strtolower', array_keys(array_filter($maybe_caps)));
                }
            }
        }
        
        // 4. Find highest-priority mapped role present
        $matched_roles = array_values(array_intersect($role_priority, $wp_roles));
        $is_pro = 0;
        $pro_type = 0;
        $pro_time = 0;
        if (!empty($matched_roles)) {
            $role = $matched_roles[0]; // Highest priority
            $pro_type = $role_map[$role];
            $is_pro   = 1;
            $pro_time = time();
        }
        
        // 5. Lookup current QuickDate state (for downgrade logic)
        $current_qd = [];
        $q = mysqli_query($qd_conn, "SELECT is_pro, pro_type FROM users WHERE email='".mysqli_real_escape_string($qd_conn, $exp_email)."' LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) {
            $current_qd = $row;
        }
        
        // 6. Inject authoritative values directly into $wp_all_meta
        if ($is_pro) {
            $wp_all_meta['is_pro']   = 1;
            $wp_all_meta['pro_type'] = $pro_type;
            $wp_all_meta['pro_time'] = $pro_time;
        } elseif (!empty($current_qd) && (int)$current_qd['is_pro'] === 1) {
            // If user no longer has mapped WP pro role, forcibly downgrade in QuickDate
            $wp_all_meta['is_pro']   = 0;
            $wp_all_meta['pro_type'] = 0;
            $wp_all_meta['pro_time'] = 0;
        }
        
        // 7. Log every entitlement sync step for trace/audit
        bz_bridge_log('WP→QuickDate subscription sync', [
            'wp_roles'      => $wp_roles,
            'matched_roles' => $matched_roles,
            'final_is_pro'  => $is_pro,
            'final_pro_type'=> $pro_type,
            'current_qd'    => $current_qd,
            'exp_email'     => $exp_email
        ]);
    
    
    
        // =========================================================
        // 🔥 [NEW] STRICT AVATAR SANITIZER (FINAL AUTHORITY)
        // =========================================================
        function bz_normalize_avatar_strict($value) {
            $value = trim((string)$value);
            if ($value === '') return '';
    
            // 1. Allow external URLs unchanged
            if (preg_match('#^https?://#i', $value)) {
                return $value;
            }
    
            // 2. Normalize slashes and remove leading junk
            $value = str_replace('\\', '/', $value);
            $value = preg_replace('#^(\.\./)+#', '', $value);
            $value = ltrim($value, '/');
    
            // 3. Remove known prefixes
            $value = preg_replace('#^(streams/|social/)?upload/photos/#i', '', $value);
    
            // 4. Prevent malformed duplication like photos/photos/
            $value = preg_replace('#^(photos/)+#i', '', $value);
    
            // 5. Ensure valid structure
            if (!preg_match('#^[0-9]{4}/[0-9]{2}/#', $value)) {
                // fallback: just ensure it's under upload/photos
                $value = ltrim($value, '/');
            }
    
            // 6. Build canonical path
            $value = '../streams/upload/photos/' . $value;
    
            // 7. Final cleanup (prevent duplicates)
            $value = preg_replace('#(\.\./streams/)+#', '../streams/', $value);
    
            return $value;
        }
        // =========================================================
    
        // 3. Normalize avatar/cover (initial pass)
        $site_base = 'https://buzzjuice.net';
    
        if (!empty($wp_full['meta']['bp_profile_avatar'])) {
            $wp_all_meta['avatar'] = $wp_full['meta']['bp_profile_avatar'];
        }
        if (!empty($wp_full['meta']['bp_profile_cover'])) {
            $wp_all_meta['cover'] = $wp_full['meta']['bp_profile_cover'];
        }
    
        // 4. Build QuickDate candidate fields
        $qd_candidate = [];
    
        foreach ($public_fields as $qd_key => $map) {
            $wp_field = $field_map[$qd_key] ?? $qd_key;
            if (isset($wp_all_meta[$wp_field]) && $wp_all_meta[$wp_field] !== '') {
                $qd_candidate[$qd_key] = trim($wp_all_meta[$wp_field]);
            }
        }
    
        foreach ($private_fields as $qd_key => $map) {
            $wp_field = $field_map[$qd_key] ?? $qd_key;
            if (!isset($qd_candidate[$qd_key]) && isset($wp_all_meta[$wp_field]) && $wp_all_meta[$wp_field] !== '') {
                $qd_candidate[$qd_key] = trim($wp_all_meta[$wp_field]);
            }
        }
    
        // Canonical fields
        foreach(['username','email','first_name','last_name','avatar','cover'] as $f) {
            if (!isset($qd_candidate[$f]) && !empty($wp_all_meta[$f])) {
                $qd_candidate[$f] = $wp_all_meta[$f];
            }
        }
    
        // =========================================================
        // 🔥 [NEW] FINAL AVATAR ENFORCEMENT (CRITICAL FIX)
        // =========================================================
        if (isset($qd_candidate['avatar'])) {
            $qd_candidate['avatar'] = bz_normalize_avatar_strict($qd_candidate['avatar']);
        }
        // =========================================================
    
        // 5. Load QuickDate schema cache
        $schema_cache_folder = $_SERVER['DOCUMENT_ROOT'] . '/data/schema_cache/';
        $schema_cache_file   = $schema_cache_folder . 'qd_users_schema.json';
    
        if (!is_dir($schema_cache_folder)) @mkdir($schema_cache_folder, 0755, true);
    
        static $qd_schema = null;
        if ($qd_schema === null) {
            if (file_exists($schema_cache_file)) {
                $qd_schema = json_decode(file_get_contents($schema_cache_file), true) ?: [];
            } else {
                $qd_schema = [];
                $q = mysqli_query($qd_conn, "SHOW COLUMNS FROM users");
                while ($row = mysqli_fetch_assoc($q)) {
                    $qd_schema[$row['Field']] = true;
                }
                @file_put_contents($schema_cache_file, json_encode($qd_schema));
            }
        }
    
        // 6. Filter valid fields
        $qd_update = [];
        foreach ($qd_candidate as $k => $v) {
            if ($k === 'avatar' && $v === '') continue; // prevent overwrite
    
            if (isset($qd_schema[$k])) {
                $qd_update[$k] = $v;
            } else {
                bz_bridge_log('QuickDate sync skipped unsupported field', ['field'=>$k]);
            }
        }
    
        // 7. Hash optimization
        $hash_payload = $qd_update;
        unset($hash_payload['lastseen'], $hash_payload['session'], $hash_payload['ip_address']);
    
        $new_hash = md5(json_encode($hash_payload));
        $old_hash = '';
    
        if (isset($qd_schema['wp_meta_hash'])) {
            $q = mysqli_query($qd_conn, "SELECT wp_meta_hash FROM users WHERE email='".mysqli_real_escape_string($qd_conn, $exp_email)."' LIMIT 1");
            if ($q && $row = mysqli_fetch_assoc($q)) {
                $old_hash = $row['wp_meta_hash'] ?? '';
            }
        }
    
        // 8. Update QuickDate
        if ($new_hash !== $old_hash && !empty($qd_update)) {
            $qd_update['wp_meta_hash'] = $new_hash;
    
            $ok = function_exists('qd_update_user') ? qd_update_user($exp_email, $qd_update) : false;
    
            bz_bridge_log('QuickDate sync: updated metadata', [
                'email' => $exp_email,
                'wp_user_id' => $exp_wp,
                'fields' => array_keys($qd_update),
                'result' => (bool) $ok
            ]);
    
            $did_sync = (bool) $ok;
        } else {
            bz_bridge_log('QuickDate sync skipped (meta hash unchanged or no updatable fields)', [
                'email' => $exp_email,
                'wp_user_id' => $exp_wp
            ]);
        }
    
        if (!$did_sync) {
            bz_bridge_log('QuickDate sync did not run or failed', [
                'email' => $exp_email,
                'wp_user_id' => $exp_wp
            ]);
        }
    
    } catch (Throwable $e) {
        bz_bridge_log('Exception during QuickDate sync', ['ex' => $e->getMessage()]);
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