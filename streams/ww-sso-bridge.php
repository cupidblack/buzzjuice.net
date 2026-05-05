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
$base_streams_url   = rtrim($wo['config']['site_url'] ?? WOWONDER_SITE_URL ?? '', '/');
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
define('BUZZ_JTI_STORE', __DIR__ . '/../data/.sso_jti_store');
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

// 2) Fallback to REQUEST_URI (not bridge/self-reference)
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
// ===================================================================
// UNIFIED SSO SESSION MANAGEMENT / DUAL TOKEN BOOTSTRAP (RECOMMENDED)
// ===================================================================

// 1. FAST PATH: Already logged into WoWonder in canonical session
if (
    !empty($wo['loggedin']) &&
    !empty($wo['user']['user_id']) &&
    !empty($_SESSION['wo_user_id']) &&
    (string)$_SESSION['wo_user_id'] === (string)$wo['user']['user_id']
) {
    bz_bridge_log('User already fully logged in to WoWonder, fast redirect.', [
        'wo_user_id'         => $wo['user']['user_id'],
        'session_wo_user_id' => $_SESSION['wo_user_id'],
        'last_url'           => $last_url,
    ]);
    header('Location: ' . $last_url);
    exit;
}

// 2. Check for WordPress authority 
$wordpress_logged_in = false;
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'wordpress_logged_in_') === 0) {
        $wordpress_logged_in = true;
        break;
    }
}

// 3. If no WordPress, destroy any lingering session and force login round-trip
if (!$wordpress_logged_in) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_unset();
        @session_destroy();
    }
    bz_bridge_log('No WordPress session present. Redirecting to WordPress login.', ['last_url' => $last_url]);
    header('Location: /wp-login.php?redirect_to=/streams/ww-sso-bridge.php?last_url=' . urlencode($last_url));
    exit;
}

// 4. Centralized SSO payload resolver
$audience = 'streams';
$BUZZ_SSO_SECRET = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

function bz_resolve_sso_payload($audience, $secret) {
    $access_token  = $_COOKIE['buzz_access'] ?? $_REQUEST['buzz_access'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? $_REQUEST[BUZZ_SSO_COOKIE] ?? null);
    $refresh_token = $_COOKIE['buzz_refresh'] ?? $_REQUEST['buzz_refresh'] ?? null;

    // a. Try access token (preferred, local-first)
    if ($access_token) {
        $payload = bz_sso_jwt_validate($access_token, $secret, $audience, 'access')
            ?: bz_sso_jwt_validate($access_token, $secret, 'buzznet', 'access');
        if ($payload) return $payload;
    }

    // b. If that's expired/invalid/missing, try the refresh token to mint new access
    if ($refresh_token) {
        $refresh_payload = bz_sso_jwt_validate($refresh_token, $secret, $audience, 'refresh')
            ?: bz_sso_jwt_validate($refresh_token, $secret, 'buzznet', 'refresh');
        if ($refresh_payload) {
            $new_payload = [
                'wp_user_id'    => $refresh_payload['wp_user_id'] ?? null,
                'wp_user_login' => $refresh_payload['wp_user_login'] ?? null,
                'wp_user_email' => $refresh_payload['wp_user_email'] ?? null,
                'wo_user_id'    => $refresh_payload['wo_user_id'] ?? null,
                'qd_user_id'    => $refresh_payload['qd_user_id'] ?? null,
            ];
            $new_access = bz_sso_jwt_encode($new_payload, $secret, $audience, BUZZ_SSO_TTL_ACCESS, 'access');
            bz_sso_set_cookie('buzz_access', $new_access, time()+BUZZ_SSO_TTL_ACCESS);
            return bz_sso_jwt_validate($new_access, $secret, $audience, 'access');
        }
    }

    // c. As last resort, call server-side to WordPress to issue new tokens using WP cookies
    $wp_token_url = 'https://buzzjuice.net/?sso_action=issue_tokens&aud=' . urlencode($audience);

    $cookies = '';
    foreach ($_COOKIE as $name => $val) {
        // Only send WP auth cookies for authority!
        if (strpos($name, 'wordpress_logged_in_') === 0 || strpos($name, 'wordpress_sec_') === 0) {
            $cookies .= "$name=$val; ";
        }
    }
    $headers = [
        'Cookie: ' . trim($cookies),
        'User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'BuzzSSO/1.0')
    ];
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
            'timeout' => 5
        ]
    ]);
    $resp = @file_get_contents($wp_token_url, false, $context);
    $http_code = 0;
    if (isset($http_response_header[0])
        && preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $matches)
    ) $http_code = (int)$matches[1];

    if ($resp !== false && $http_code === 200) {
        $data = json_decode($resp, true);
        if (!empty($data['access'])) {
            bz_sso_set_cookie('buzz_access', $data['access'], time()+BUZZ_SSO_TTL_ACCESS);
            if (!empty($data['refresh'])) {
                bz_sso_set_cookie('buzz_refresh', $data['refresh'], time()+BUZZ_SSO_TTL_REFRESH);
            }
            return bz_sso_jwt_validate($data['access'], $secret, $audience, 'access')
                ?: bz_sso_jwt_validate($data['access'], $secret, 'buzznet', 'access');
        }
    }
    return false;
}

// 5. Actually use the resolver. Try all above paths.
$access_payload = bz_resolve_sso_payload($audience, $BUZZ_SSO_SECRET);

// 6. If server-side failed but browser still has WP session, use client-side JS fallback.
if (!$access_payload && $wordpress_logged_in) {
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
            document.getElementById('status').textContent = "SSO failed. Please login via WordPress or try again.";
            return;
        }
        if (window.sessionStorage) sessionStorage.setItem('sso_js_fallback_tried', '1');
        fetch('https://buzzjuice.net/?sso_action=issue_tokens&aud=<?php echo $aud; ?>', {credentials:'include' })
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
            document.getElementById('status').textContent = "Network or authentication error during SSO. Please try again.";
        });
    })();
    </script>
    </body>
    </html>
    <?php
    exit; // Ensure no further PHP output.
}

// 7. If still nothing, fallback to login for manual re-authentication.
if (!$access_payload) {
    bz_bridge_log('Dual-token bootstrap failed — redirecting to login');
    header('Location: /wp-login.php?try=ww01&redirect_to=/streams/ww-sso-bridge.php?last_url=' . urlencode($last_url));
    exit;
}

// 8. Always hydrate canonical session—this makes the rest of the bridge and autologin work
$_SESSION['wp_user_id']    = (int)($access_payload['wp_user_id'] ?? 0);
$_SESSION['wp_user_login'] = (string)($access_payload['wp_user_login'] ?? '');
$_SESSION['wp_user_email'] = (string)($access_payload['wp_user_email'] ?? '');
$_SESSION['wo_user_id']    = (int)($access_payload['wo_user_id'] ?? 0);
$_SESSION['wp_Wo_SSO_Login'] = true;

bz_bridge_log('WoWonder SSO session hydrated from JWT claims', [
    'session' => [
        'wp_user_id'    => $_SESSION['wp_user_id'],
        'wp_user_login' => $_SESSION['wp_user_login'],
        'wo_user_id'    => $_SESSION['wo_user_id'],
        'wp_Wo_SSO_Login' => $_SESSION['wp_Wo_SSO_Login'],
    ]
]);

// -----------------------------
// Required claims guard
// -----------------------------
if (!$_SESSION['wp_user_id'] || !$_SESSION['wp_user_login'] || !$_SESSION['wp_user_email']) {
    bz_bridge_log('Missing required claims (cookie incomplete)', $access_payload);
    header('Location: /wp-login.php?try=ww02&redirect_to=/streams/ww-sso-bridge.php?last_url=' . urlencode($last_url));
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
// WoWonder SSO Identity Resolver + Auto Registration (FINAL, PRODUCTION-READY)
// =======================================================================================

// ---------------------------
// HELPERS (global safe)
// ---------------------------
if (!function_exists('bz_clean_username')) {
    function bz_clean_username($username) {
        $username = (string)$username;
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        if (strlen($username) < 5) $username .= rand(1000,9999);
        return substr($username, 0, 32);
    }
}

if (!function_exists('bz_generate_unique_username')) {
    function bz_generate_unique_username($base, $sqlConn, $tbl) {
        $base = bz_clean_username($base);
        $candidate = $base;
        $i = 1;
        while (true) {
            $esc = mysqli_real_escape_string($sqlConn, $candidate);
            $q = mysqli_query($sqlConn, "SELECT user_id FROM {$tbl} WHERE username='{$esc}' LIMIT 1");
            if (!$q || mysqli_num_rows($q) === 0) return $candidate;
            $candidate = substr($base . $i++, 0, 32);
        }
    }
}

if (!function_exists('bz_fetch_wp_wo_user_id')) {
    function bz_fetch_wp_wo_user_id($wp_user_id) {
        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        if (!$conn || !$wp_user_id) return 0;
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        $q = mysqli_query($conn, "SELECT meta_value FROM {$table} WHERE user_id={$wp_user_id} AND meta_key='wo_user_id' LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) return (int)$row['meta_value'];
        return 0;
    }
}

if (!function_exists('bz_update_wp_wo_user_id')) {
    function bz_update_wp_wo_user_id($wp_user_id, $wo_user_id) {
        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        if (!$conn) return false;
        $table = function_exists('wp_table') ? wp_table('usermeta') : 'wp_usermeta';
        $wp_user_id = (int)$wp_user_id; $wo_user_id = (int)$wo_user_id;
        // Try upsert (ON DUPLICATE KEY) or manual logic (MySQL config dependent)
        // Most WP installs (>=5.0) support ON DUPLICATE KEY
        @mysqli_query($conn, "
            INSERT INTO {$table} (user_id, meta_key, meta_value)
            VALUES ($wp_user_id, 'wo_user_id', '$wo_user_id')
            ON DUPLICATE KEY UPDATE meta_value='$wo_user_id'
        ");
        return true;
    }
}

// ---------------------------
// MAIN FLOW (idempotent, no unnecessary redirects)
// ---------------------------
if (
    empty($access_payload['wo_user_id']) &&
    BUZZ_SSO_AUTO_REGISTER &&
    !empty($access_payload['wp_user_id']) &&
    !empty($access_payload['wp_user_login']) &&
    !empty($access_payload['wp_user_email'])
) {
    $wp_user_id  = (int)$access_payload['wp_user_id'];
    $wp_username = trim($access_payload['wp_user_login']);
    $wp_email    = trim($access_payload['wp_user_email']);
    $sqlConn     = $GLOBALS['sqlConnect'];
    $tbl         = defined('T_USERS') ? T_USERS : 'Wo_Users';

    $wo_user_id   = 0;
    $max_attempts = 5;
    $attempt      = 0;
    $fatal_error  = null;

    while ($attempt < $max_attempts) {
        // ---- 1: Existing mapping? ----
        $existing_wo_id = bz_fetch_wp_wo_user_id($wp_user_id);
        if ($existing_wo_id) {
            $wo_user_id = $access_payload['wo_user_id'] = $existing_wo_id;
            bz_bridge_log('SSO: Used prior mapping from usermeta', [
                'wp_user_id' => $wp_user_id,
                'wo_user_id' => $wo_user_id
            ]);
            break;
        }

        // ---- 2: Try to find existing WW user by username or email ----
        $username_esc = mysqli_real_escape_string($sqlConn, $wp_username);
        $email_esc    = mysqli_real_escape_string($sqlConn, $wp_email);
        $q = mysqli_query($sqlConn, "SELECT user_id,username,email FROM {$tbl} WHERE username='{$username_esc}' OR email='{$email_esc}'");
        $rows = [];
        while ($r = $q ? mysqli_fetch_assoc($q) : null) $rows[] = $r;

        $user_id_by_username = null; $user_id_by_email = null;
        foreach ($rows as $r) {
            if (strcasecmp($r['username'], $wp_username) === 0) $user_id_by_username = (int)$r['user_id'];
            if (strcasecmp($r['email'], $wp_email) === 0)    $user_id_by_email = (int)$r['user_id'];
        }

        // ---- [Case 1] Both username/email point to the same user ----
        if ($user_id_by_username && $user_id_by_email && $user_id_by_username === $user_id_by_email) {
            $wo_user_id = $user_id_by_username;
            bz_update_wp_wo_user_id($wp_user_id, $wo_user_id);
            $access_payload['wo_user_id'] = $wo_user_id;
            bz_bridge_log('SSO: PERFECT MATCH (username & email)', [
                'wp_user_id'=>$wp_user_id, 'wo_user_id'=>$wo_user_id
            ]);
            break;
        }

        // ---- [Case 2] Email match (username differs or doesn't exist) — try sync username ----
        if (
            $user_id_by_email &&
            (
                !$user_id_by_username ||
                strcasecmp($rows[array_search($user_id_by_email, array_column($rows, 'user_id'))]['username'], $wp_username) !== 0
            )
        ) {
            $wo_user_id = (int)$user_id_by_email;
            $desired_username = bz_generate_unique_username($wp_username, $sqlConn, $tbl);

            $reserved_usernames = $wo['reserved_usernames'] ?? [];
            $is_reserved = in_array($desired_username, $wo['site_pages'] ?? []) ||
                           in_array($desired_username, $reserved_usernames) ||
                           (function_exists('Wo_IsNameExist') && Wo_IsNameExist($desired_username));

            if ($is_reserved) {
                $fatal_error = 'Your desired username is reserved or already taken. Please contact support@buzzjuice.net or edit your name in WordPress.';
                break;
            }
            if (strlen($desired_username) < 5) {
                $fatal_error = 'Your username is too short for Streams. Please update your name in WordPress.';
                break;
            }
            $update_success = false;
            if (function_exists('Wo_UpdateUserData')) {
                $update_success = Wo_UpdateUserData($wo_user_id, ['username' => $desired_username]);
            }
            bz_bridge_log('SSO: Username sync (email match)', [
                'wo_user_id'=>$wo_user_id, 'desired'=>$desired_username, 'result'=>$update_success
            ]);
            if ($update_success) {
                bz_update_wp_wo_user_id($wp_user_id, $wo_user_id);
                $access_payload['wo_user_id'] = $wo_user_id;
                break;
            } else {
                $fatal_error = 'A server error occurred during username sync. Please contact support@buzzjuice.net.';
                break;
            }
        }

        // ---- [Case 3] Username/email point to different users: quarantine both, retry ----
        if ($user_id_by_username && $user_id_by_email && $user_id_by_username !== $user_id_by_email) {
            $prefix = 'conflict_' . rand(10000,99999) . '_';
            // Rename username
            $new_username = $prefix . $wp_username;
            mysqli_query($sqlConn, "UPDATE {$tbl} SET username='" . mysqli_real_escape_string($sqlConn, $new_username) . "' WHERE user_id=" . intval($user_id_by_username));
            // Rename email (prefix before @)
            $new_email = preg_replace('/^([^@]+)/', $prefix.'$1', $wp_email);
            mysqli_query($sqlConn, "UPDATE {$tbl} SET email='" . mysqli_real_escape_string($sqlConn, $new_email) . "' WHERE user_id=" . intval($user_id_by_email));
            bz_bridge_log('SSO: Username/email split conflict: both legacy', [
                'username_user' => $user_id_by_username,
                'email_user'    => $user_id_by_email
            ]);
            // continue to next attempt, as now neither match
        }

        // ---- [Case 4] Username match only ("quarantine") ----
        if ($user_id_by_username && !$user_id_by_email) {
            $prefix = 'legacy_' . rand(1000,9999) . '_';
            mysqli_query($sqlConn, "UPDATE {$tbl} SET username='" . mysqli_real_escape_string($sqlConn, $prefix.$wp_username) . "' WHERE user_id=" . intval($user_id_by_username));
            bz_bridge_log('SSO: Username-only collision resolved by rename', [
                'old_user_id' => $user_id_by_username
            ]);
            // continue, as now username will be available on next loop
        }

        // ---- 3: Register new WoWonder user ----
        $final_username = bz_generate_unique_username($wp_username, $sqlConn, $tbl);
        $registration = Wo_RegisterUser([
            'username' => $final_username,
            'email'    => $wp_email,
            'password' => bin2hex(random_bytes(16)),
            'active'   => 1
        ]);
        if ($registration && !empty($registration['user_id'])) {
            $wo_user_id = (int)$registration['user_id'];
            bz_update_wp_wo_user_id($wp_user_id, $wo_user_id);
            $access_payload['wo_user_id'] = $wo_user_id;
            $_SESSION['wo_auto_registered'] = true;
            bz_bridge_log('SSO: NEW WoWonder user registered', [
                'wo_user_id' => $wo_user_id, 'username' => $final_username, 'email' => $wp_email
            ]);
            break;
        }

        // Registration failed, retry up to max_attempts, then error out
        usleep(100000);
        $attempt++;
    } // while

    // -------------- If all reconciliation fails --------------
    if (empty($wo_user_id) || $fatal_error) {
        bz_bridge_log('SSO: WoWonder registration failed', [
            'payload'    => $access_payload,
            'attempts'   => $attempt,
            'fatal_error'=> $fatal_error,
            'username'   => $wp_username,
            'email'      => $wp_email
        ]);
        $err_message = $fatal_error ?: "We're unable to create or connect your Streams account at this time.<br>
        Please contact <a href='mailto:support@buzzjuice.net'>support@buzzjuice.net</a>
        or return to the <a href='https://buzzjuice.net/dashboard'>dashboard</a>.";
        // AJAX or bridge page: show error (never redirect to login)
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($is_ajax) {
            echo json_encode(['status'=>500, 'error'=>$err_message]);
        } else {
            echo "<div class='status err'>" . $err_message . "</div>";
        }
        exit;
    }
}

// SESSION HYDRATION (always finish in all flows)
$final_wo_user_id = $access_payload['wo_user_id'] ?? $wo_user_id ?? null;
if (!isset($_SESSION['wp_user_login']))
    $_SESSION['wp_user_login'] = (string)($access_payload['wp_user_login'] ?? '');
$_SESSION['wp_user_id']    = (int)($access_payload['wp_user_id'] ?? 0);
$_SESSION['wp_user_email'] = (string)($access_payload['wp_user_email'] ?? '');
$_SESSION['wo_user_id']    = (int)($final_wo_user_id ?? 0);

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
            header('Location: /wp-login.php?try=ww09&redirect_to=/streams/ww-sso-bridge.php?last_url=' . urlencode($last_url));
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
            header('Location: /wp-login.php?try=ww10&redirect_to=/streams/ww-sso-bridge.php?last_url=' . urlencode($last_url));
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
        // WordPress → WoWonder Metadata Sync (Buzzjuice Platform Sync v3)
        // Safely normalizes, maps, and updates only supported fields
        // =========================================================
        
// ==================================================================
// WordPress → WoWonder User Metadata Sync (Robust, Hardened Version)
// ==================================================================

// 1. Gather identifiers safely.
$wp_user_id = (int)($_SESSION['wp_user_id'] ?? 0);
$wo_user_id = (int)($_SESSION['wo_user_id'] ?? 0);
if (!$wp_user_id || !$wo_user_id) {
    bz_bridge_log('WP→Wo sync aborted: missing user ids', [
        'wp_user_id'=>$wp_user_id, 'wo_user_id'=>$wo_user_id
    ]);
    return;
}
$wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
$sqlConn = $GLOBALS['sqlConnect'] ?? null;
if (!$wp_conn || !$sqlConn) {
    bz_bridge_log('WP→Wo sync aborted: missing DB connection');
    return;
}
$wo_table = defined('T_USERS') ? T_USERS : 'Wo_Users';

// 2. Metadata/field registry.
$metadata     = function_exists('get_user_field_metadata') ? get_user_field_metadata() : [];
$field_map    = $metadata['field_map'] ?? [];
$sync_fields  = array_unique(array_merge(
    $metadata['private_secure_fields'] ?? [],
    $metadata['public_open_fields'] ?? []
));

// 3. Pull full WP profile/meta and normalize values to always be scalars.
$wp_data     = function_exists('wp_get_full_user_data') ? wp_get_full_user_data($wp_conn, $wp_user_id) : [];
$wp_meta     = $wp_data['meta']     ?? [];
$wp_xprofile = $wp_data['xprofile'] ?? [];
$wp_core     = $wp_data;

function bz_safe_value($val) {
    // Handles unserialization and JSON auto-conversion.
    if (is_array($val) || is_object($val)) return $val;
    if (!is_string($val)) return $val;
    if (function_exists('is_serialized') && is_serialized($val)) {
        $tmp = @unserialize($val);
        if ($tmp !== false || $val === 'b:0;') return $tmp;
    }
    $json = json_decode($val, true);
    if (json_last_error() === JSON_ERROR_NONE) return $json;
    return $val;
}
function bz_scalar($v) {
    return (is_array($v) || is_object($v)) ? json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : (string)$v;
}

// Build normalized meta.
$wp_all_meta = [];
foreach ($wp_meta as $k=>$v)
    $wp_all_meta[strtolower(trim($k))] = bz_scalar(bz_safe_value($v));
foreach ($wp_xprofile as $k=>$v) {
    $norm = strtolower(str_replace([' ','-'],'_',trim($k)));
    $wp_all_meta[$norm] = bz_scalar(bz_safe_value($v));
}
foreach (['user_login','user_email','display_name','user_registered'] as $f)
    if (!empty($wp_core[$f])) $wp_all_meta[$f] = $wp_core[$f];

// 4. Avatar and cover image normalization, with multi-fallbacks and existence checks.
function bz_media_abs_url($url) {
    $url = trim((string)$url);
    if (!$url) return '';
    // Strip accidental /streams or /social from beginning of wp-content URLs:
    if (preg_match('#^https?://#i', $url)) {
        $url = preg_replace('#^(https?://[^/]+)/(streams|social)/wp-content/#i', '$1/wp-content/', $url);
        return $url;
    }
    $url = preg_replace('#^/(streams|social)/wp-content/#i', '/wp-content/', $url);
    if (strpos($url, '/wp-content/') === 0) return 'https://buzzjuice.net' . $url;
    if (strpos($url, 'wp-content/') === 0)   return 'https://buzzjuice.net/' . $url;
    // If the URL looks like WoWonder media, leave as-is:
    if (preg_match('#/streams/upload/#', $url)) return $url;
    // Root-relative fallback:
    return 'https://buzzjuice.net/' . ltrim($url, '/');
}
function bz_wp_file_exists($url) {
    if (!preg_match('#^https?://buzzjuice\.net(/.*)$#i', $url, $m)) return true; // allow any offsite
    $doc_path = $_SERVER['DOCUMENT_ROOT'] . $m[1];
    return file_exists($doc_path);
}
$default_icon        = 'https://buzzjuice.net/wp-content/uploads/2026/04/BuzzJuice-Logo-2.03-icon192x192.png';
$bb_avatar_default   = '/wp-content/plugins/buddyboss-platform/bp-core/images/profile-avatar-buddyboss.png';
$bb_cover_primary    = '/wp-content/uploads/buddypress/members/0/cover-image/69dd867faacfb-bp-cover-image.jpg';
$bb_cover_fallback   = '/wp-content/plugins/buddyboss-platform/bp-core/images/cover-image.png';
// --- COVER CHAIN ---
$wp_cover_raw     = $wp_meta['bp_profile_cover'] ?? '';
$cover_candidates = [
    bz_media_abs_url($wp_cover_raw),
    bz_media_abs_url($bb_cover_primary),
    bz_media_abs_url($bb_cover_fallback)
];
$cover_url = $default_icon;
foreach ($cover_candidates as $cand) {
    if ($cand && bz_wp_file_exists($cand)) {
        $cover_url = $cand;
        break;
    }
}
// --- AVATAR CHAIN ---
$wp_avatar_raw     = $wp_meta['bp_profile_avatar'] ?? '';
$avatar_candidates = [
    bz_media_abs_url($wp_avatar_raw),
    bz_media_abs_url($bb_avatar_default)
];
$avatar_url = $default_icon;
foreach ($avatar_candidates as $cand) {
    if ($cand && bz_wp_file_exists($cand)) {
        $avatar_url = $cand;
        break;
    }
}
$wp_all_meta['avatar'] = $avatar_url;
$wp_all_meta['cover']  = $cover_url;

// 5. WP role to WW pro field logic.
$role_map = [
    'classic_lifestyle'  => 1,
    'silver_lifestyle'   => 2,
    'rockstar_lifestyle' => 3,
    'premium_lifestyle'  => 4,
    'jewel_affiliate'    => 2,
];
$role_priority = [
    'premium_lifestyle',
    'rockstar_lifestyle',
    'silver_lifestyle',
    'classic_lifestyle',
    'jewel_affiliate'
];
$wp_roles = [];
if (!empty($wp_core['roles']) && is_array($wp_core['roles'])) {
    $wp_roles = array_map('strtolower', $wp_core['roles']);
} elseif (!empty($wp_meta['wp_capabilities'])) {
    $maybe_caps = bz_safe_value($wp_meta['wp_capabilities']);
    if (is_array($maybe_caps)) $wp_roles = array_map('strtolower', array_keys(array_filter($maybe_caps)));
}
$matched_roles = array_values(array_intersect($role_priority, $wp_roles));
$is_pro   = !empty($matched_roles);
$pro_type = $is_pro ? ($role_map[$matched_roles[0]] ?? 0) : 0;
$pro_time = $is_pro ? time() : 0;

// 6. Load WW schema cache to skip missing fields.
static $wo_schema = null;
$cache_dir = $_SERVER['DOCUMENT_ROOT'] . '/data/schema_cache/';
$schema_file = $cache_dir . 'wo_users_schema.json';
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
if ($wo_schema === null) {
    if (file_exists($schema_file)) $wo_schema = json_decode(file_get_contents($schema_file), true) ?: [];
    else {
        $wo_schema = [];
        $q = mysqli_query($sqlConn, "SHOW COLUMNS FROM {$wo_table}");
        while ($q && $row = mysqli_fetch_assoc($q)) $wo_schema[$row['Field']] = true;
        @file_put_contents($schema_file, json_encode($wo_schema));
    }
}

// 7. Build final update.
$update = [];
foreach ($sync_fields as $field) {
    if (!array_key_exists($field, $wp_all_meta)) continue;
    $wo_field = $field_map[$field] ?? $field;
    if (!isset($wo_schema[$wo_field])) continue;
    $val = $wp_all_meta[$field];
    if ($wo_field === 'avatar') $val = $avatar_url;
    if ($wo_field === 'cover')  $val = $cover_url;
    if ($val === '' || $val === null) continue;
    $update[$wo_field] = is_string($val) ? trim($val) : $val;
}
$update['wp_user_id'] = $wp_user_id;
if (!empty($_SESSION['wp_user_email']))  $update['email']    = trim($_SESSION['wp_user_email']);
if (!empty($_SESSION['wp_user_login']))  $update['username'] = trim($_SESSION['wp_user_login']);
if ($is_pro) {
    $update['is_pro']   = 1;
    $update['pro_type'] = $pro_type;
    if (isset($wo_schema['pro_time'])) $update['pro_time'] = $pro_time;
} else {
    $update['is_pro']   = 0;
    $update['pro_type'] = 0;
    if (isset($wo_schema['pro_time'])) $update['pro_time'] = 0;
}

// 8. Write only if changed (meta hash filter for churn prevention).
$q = mysqli_query($sqlConn, "SELECT wp_meta_hash FROM {$wo_table} WHERE user_id=".(int)$wo_user_id." LIMIT 1");
$old_hash = '';
if ($q && $row = mysqli_fetch_assoc($q)) $old_hash = $row['wp_meta_hash'] ?? '';
$hash_payload = $update;
unset($hash_payload['lastseen'], $hash_payload['session'], $hash_payload['ip_address']);
$new_hash = md5(json_encode($hash_payload));

if ($new_hash !== $old_hash) {
    $update['wp_meta_hash'] = $new_hash;
    if (!empty($update) && function_exists('Wo_UpdateUserData')) {
        $old_level = error_reporting();
        error_reporting($old_level & ~E_NOTICE & ~E_WARNING);
        try {
            $result = Wo_UpdateUserData($wo_user_id, $update);
            bz_bridge_log('WP→Wo sync: metadata updated', [
                'user_id'=>$wo_user_id,
                'fields'=>array_keys($update),
                'result'=>$result,
                'meta_hash'=>$new_hash
            ]);
        } catch (Throwable $e) {
            bz_bridge_log('WP→Wo sync: ERROR during Wo_UpdateUserData', [
                'user_id'=>$wo_user_id,
                'error'=>$e->getMessage()
            ]);
        } finally {
            error_reporting($old_level);
        }
    }
} else {
    bz_bridge_log('WP→Wo sync skipped (meta hash unchanged)', ['user_id'=>$wo_user_id]);
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