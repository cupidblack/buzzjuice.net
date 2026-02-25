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
require_once __DIR__ . '/../shared/db_helpers.php';
require_once __DIR__ . '/../shared/sso_bridge_helpers.php';
if (!function_exists('bz_fetch_wp_stateless_payload')) {
    exit('SSO helper not loaded.');
}
// -----------------------------
// Config
// -----------------------------
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/ww_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);

$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET')
    ? BUZZ_SSO_SECRET
    : (getenv('BUZZ_SSO_SECRET') ?: null);

// -----------------------------
// Hardened base64url decode (never null)
// -----------------------------
function bz_b64url_decode_safe($str) {
    if ($str === null || $str === '') return '';
    $s = strtr($str, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad) $s .= str_repeat('=', 4 - $pad);
    $out = base64_decode($s, true);
    return $out === false ? '' : $out;
}

// -----------------------------
// RFC 7519 JWT validation
// -----------------------------
function bz_validate_jwt($jwt, $secret) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    list($h, $p, $s) = $parts;
    $header  = json_decode(bz_b64url_decode_safe($h), true);
    $payload = json_decode(bz_b64url_decode_safe($p), true);
    if (!$header || !$payload || ($header['alg'] ?? '') !== 'HS256') return false;
    $expected_sig = hash_hmac('sha256', "$h.$p", $secret, true);
    $actual_sig  = bz_b64url_decode_safe($s);
    if (!hash_equals($expected_sig, $actual_sig)) return false;
    $now = time();
    if (!empty($payload['nbf']) && $now < $payload['nbf']) return false;
    if (!empty($payload['exp']) && $now > $payload['exp']) return false;
    if (($payload['iss'] ?? '') !== 'buzzjuice.net') return false;
    if (($payload['aud'] ?? '') !== 'buzznet') return false;
    if (empty($payload['jti'])) return false;
    // You may want to check more claims here as needed
    return $payload;
}

// -----------------------------
// Replay protection: JTI store (30 min)
// -----------------------------
define('BUZZ_JTI_STORE', __DIR__ . '/sso_jti_store');
if (!is_dir(BUZZ_JTI_STORE)) @mkdir(BUZZ_JTI_STORE, 0755, true);

function bz_is_jti_used($jti) {
    return $jti && file_exists(BUZZ_JTI_STORE . '/' . sha1($jti));
}
function bz_mark_jti_used($jti) {
    @file_put_contents(BUZZ_JTI_STORE . '/' . sha1($jti), time(), LOCK_EX);
}
function bz_cleanup_jti_store() {
    $expire = time() - 1800; // 30 min
    foreach (glob(BUZZ_JTI_STORE . '/*') ?: [] as $file) if (filemtime($file) < $expire) @unlink($file);
}
if (mt_rand(1, 35) === 9) bz_cleanup_jti_store();

// -----------------------------
// Logging + graceful failure
// -----------------------------
function bz_bridge_fail_gracefully($msg, $context = []) {
    if (function_exists('bz_bridge_log')) {
        bz_bridge_log("SSO FAIL: $msg", $context);
    } else {
        error_log("[bz_bridge] $msg | " . json_encode($context));
    }
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow">';
    echo '<title>Single sign-on</title><style>body{font-family:sans-serif;max-width:650px;margin:40px auto;}</style></head><body>';
    echo '<h2>Single sign-on — WoWonder Bridge</h2>';
    echo '<p>We were unable to complete the cross-site sign in.</p>';
    if (!empty($msg)) echo '<p><b>Reason:</b> ' . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE) . '</p>';
    echo '<p><a href="/">Continue to the site</a></p>';
    echo '</body></html>';
    exit;
}


// -----------------------------
// Fetch stateless payload from WP
// -----------------------------
// Define both variables before using them
$sso_token = $_REQUEST['sso_token'] ?? ($_COOKIE['buzz_sso'] ?? null);
$secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);
$payload = bz_fetch_wp_stateless_payload($sso_token, $secret);



// -------------------------------------------------------
// LOGGING, DEBUG, CLIENT DEBUG BEACON, LOOP PROTECTION
// -------------------------------------------------------

function bz_is_debug() {
    return (
        (isset($_GET['sso_debug']) && $_GET['sso_debug'] === '1') ||
        (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG === true)
    );
}

function bz_bridge_log($msg, $ctx = []) {
    $data = [
        'ts'  => gmdate('Y-m-d H:i:s'),
        'ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'ua'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ];
    if (!empty($ctx)) $data['ctx'] = $ctx;
    $line = '['.$data['ts'].'] '.$msg.' | '.json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
    @file_put_contents(BUZZ_SSO_BRIDGE_LOG, $line, FILE_APPEND | LOCK_EX);
}

function bz_debug_page($title, $blocks = []) {
    if (!bz_is_debug()) return;
    header('Content-Type: text/html; charset=utf-8');
    echo "<!doctype html><meta charset='utf-8'><title>SSO Bridge Debug</title>";
    echo "<style>body{font-family:system-ui;background:#0b1020;color:#e9eef7;padding:24px}
            .blk{background:#131a33;margin:16px 0;padding:12px;border-radius:10px}
            pre{white-space:pre-wrap}
        </style>";
    echo "<h2>SSO Bridge Debug — ".htmlspecialchars($title, ENT_QUOTES)."</h2>";
    $default = [
        'REQUEST' => $_REQUEST ?? [],
        'SERVER'  => [
            'HTTP_HOST'   => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null
        ]
    ];
    $blocks = array_merge($blocks, $default);
    foreach ($blocks as $k => $v) {
        echo "<div class='blk'><strong>".htmlspecialchars($k)."</strong><pre>", htmlspecialchars(print_r($v, true)), "</pre></div>";
    }
    exit;
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

if ($loop_count > 4) {
    bz_bridge_log('Bridge loop suspected — forcing fallback', [
        'loop_count' => $loop_count
    ]);
    if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
    $forced_last_url_fallback = true;
} else {
    $forced_last_url_fallback = false;
}

// -------------------------------------------------------
// END: no legacy HMAC/token helpers below this point!
// -------------------------------------------------------





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





// -------------------------------------------------------
// FAIL-SAFE LOGOUT REDIRECT (stateless, JWT-SSO only)
// -------------------------------------------------------
function bz_clear_session_and_redirect($reason = 'unknown') {
    global $wo;

    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/bot|crawl|spider|slurp|mediapartners/i', $ua)) {
        bz_bridge_log('Skipping redirect for bot', ['reason' => $reason, 'ua' => $ua]);
        return;
    }

    if (session_status() !== PHP_SESSION_NONE) {
        $_SESSION = [];
        @session_unset();
        @session_destroy();
    }

    // Expire SSO and session cookies
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => BUZZ_COOKIE_DOMAIN,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        setcookie(BUZZ_SSO_COOKIE, '', time() - 3600, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    setcookie(session_name(), '', time() - 3600, '/', BUZZ_COOKIE_DOMAIN);
    $target = rtrim($wo['config']['site_url'] ?? '', '/') . '/../wp-login.php';
    bz_bridge_log('Clearing session and redirecting', [
        'reason' => $reason,
        'target' => $target,
        'sid'    => function_exists('session_id') ? session_id() : null
    ]);
    header('Location: ' . $target);
    exit;
}

// -------------------------------------------------------
// BOOTSTRAP CHECKS — REQUIRE Wo CONFIG AND SQL
// -------------------------------------------------------
global $wo, $sqlConnect;
if (empty($wo['config']['site_url']) || empty($sqlConnect)) {
    bz_bridge_log('Bootstrap incomplete - missing $wo or $sqlConnect');
    bz_debug_page('Bootstrap incomplete', ['$wo' => $wo ?? null, '$sqlConnect' => (bool)$sqlConnect]);
    header('Location: /');
    exit;
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
            bz_bridge_log('Resuming PHP session from cookie (bridge fallback)');
        }
        session_start();
        bz_bridge_log('Session started by bridge (fallback)', ['session_id' => session_id()]);
        // CRITICAL: this session is *never* a trust anchor for SSO
    } else {
        bz_bridge_log('Session not started (bridge): benign request, no buzz_sso and not an SSO action');
    }
}

// -------------------------------------------------------
// LIGHTWEIGHT "CHECK" ENDPOINT (validates logged-in/JWT/redirect)
// -------------------------------------------------------
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'check') {
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





// -------------------------------------------------------
// SSO JWT CLAIM EXTRACTION & REQUIRED CLAIMS VALIDATION
// -------------------------------------------------------

$payload = null;
$site_base = rtrim($wo['config']['site_url'] ?? '', '/');

// Centralized, loggable WordPress login redirect
function bz_redirect_to_wp_login($reason = 'unknown') {
    global $site_base;
    $go_pro_target = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    bz_bridge_log('Redirecting to WP login', ['reason' => $reason, 'wp_login_url' => $go_pro_target]);
    header('Location: https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_target));
    exit;
}

// 1. Is the buzz_sso JWT cookie present and secret configured?
if (!empty($_COOKIE[BUZZ_SSO_COOKIE]) && $BUZZ_SSO_SECRET) {
    try {
        $payload = bz_validate_jwt($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET);
    } catch (Throwable $e) {
        bz_bridge_log('Exception during JWT validation', ['ex' => $e->getMessage()]);
        $payload = false;
    }
} else {
    bz_bridge_log('No JWT/buzz_sso cookie present or missing secret', [
        'cookie_present' => !empty($_COOKIE[BUZZ_SSO_COOKIE]),
        'BUZZ_SSO_SECRET' => (bool)$BUZZ_SSO_SECRET
    ]);
    bz_redirect_to_wp_login('Missing JWT SSO token or secret');
}

if (!$payload) {
    bz_bridge_log('JWT SSO payload invalid/expired');
    bz_redirect_to_wp_login('JWT SSO payload invalid or expired');
}

// 2. Extract claims (use legacy keys as fallback for backwards compatibility)
$claim_wp_user_id    = (int)($payload['wp_user_id'] ?? 0);
$claim_wp_user_login = (string)($payload['wp_user_login'] ?? $payload['login'] ?? '');
$claim_wp_user_email = (string)($payload['wp_user_email'] ?? $payload['email'] ?? '');
$claim_wo_user_id    = (int)($payload['wo_user_id'] ?? 0);

$original_claims = [
    'claim_wp_user_id'    => $claim_wp_user_id,
    'claim_wp_user_login' => $claim_wp_user_login,
    'claim_wp_user_email' => $claim_wp_user_email,
    'claim_wo_user_id'    => $claim_wo_user_id
];
bz_bridge_log('JWT SSO claims extracted', array_merge($original_claims, ['raw_payload' => $payload]));

// 3. Required claims guard
if (!$claim_wp_user_id || !$claim_wp_user_login || !$claim_wp_user_email) {
    bz_bridge_log('Missing required claims (JWT incomplete)', $original_claims);
    bz_redirect_to_wp_login('JWT missing required claims');
}

// 4. Canonicalization: prefer already set session fields for UI only (do NOT trust for SSO)
$canonical = [
    'wp_user_id'    => $_SESSION['wp_user_id']    ?? $claim_wp_user_id,
    'wp_user_login' => $_SESSION['wp_user_login'] ?? $claim_wp_user_login,
    'wp_user_email' => $_SESSION['wp_user_email'] ?? $claim_wp_user_email,
    'wo_user_id'    => $_SESSION['wo_user_id']    ?? $claim_wo_user_id,
];

bz_bridge_log('Canonical pre-mapping values', [
    'canonical' => $canonical,
    'session'   => $_SESSION ?? [],
]);





// --------------------------------------------------
// WoWonder <-> WordPress Mapping & Registration Helpers
// --------------------------------------------------

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
    bz_bridge_log('Unable to determine wo_user_id after mapping/registration', ['canonical'=>$canonical,'session'=>$_SESSION ?? []]);
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
$sso_token = $_COOKIE[BUZZ_SSO_COOKIE] ?? null;

// -- 5. ROBUST LAST_URL DERIVATION/NORMALIZATION (PRESERVED FROM ALL SOURCES BUT GUARDS AGAINST EXTERNAL/UNSAFE/RECURSION)
$site_base = rtrim($wo['config']['site_url'], '/');
$last_url = '';
if (!empty($_GET['last_url'])) {
    $last_url = (string)$_GET['last_url'];
} elseif (!empty($_POST['last_url'])) {
    $last_url = (string)$_POST['last_url'];
} elseif (!empty($_COOKIE['last_url'])) {
    $last_url = (string)$_COOKIE['last_url'];
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $last_url = trim((string)$_SERVER['HTTP_REFERER']);
} else {
    $req_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $bridge_path = parse_url($_SERVER['PHP_SELF'] ?? $req_uri, PHP_URL_PATH);
    if ($req_uri && $bridge_path && $req_uri !== $bridge_path && strpos($req_uri, basename(__FILE__)) === false) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? parse_url($site_base, PHP_URL_HOST);
        $candidate = rtrim($scheme . '://' . $host, '/') . $req_uri;
        $ok = false;
        if ($site_base && strpos($candidate, $site_base) === 0) $ok = true;
        if (!$ok) {
            $path_only = parse_url($candidate, PHP_URL_PATH) ?: '/';
            if (strpos($path_only, '/streams') === 0) $ok = true;
        }
        if ($ok) $last_url = $candidate;
    }
}
// If relative, make absolute; else fallback to site base. Only allow local/same-site.
if ($last_url) {
    if (strpos($last_url, 'http://') !== 0 && strpos($last_url, 'https://') !== 0) {
        $last_url = (strpos($last_url, '/') === 0)
            ? $site_base . $last_url
            : $site_base . '/' . ltrim($last_url, '/');
    }
    if ($site_base && strpos($last_url, $site_base) !== 0) $last_url = '';
}
if (!$last_url) $last_url = $site_base . '/';
if (!empty($last_url) && function_exists('bz_is_bridge_url') && bz_is_bridge_url($last_url, $site_base)) {
    bz_bridge_log('last_url rejected: bridge/self-reference detected', ['last_url' => $last_url, 'site_base' => $site_base]);
    $last_url = rtrim($site_base, '/') . '/';
}
if (!empty($forced_last_url_fallback)) $last_url = rtrim($site_base, '/') . '/';

// -- 6. AJAX URL FOR CLIENT POST
$ajax_url_base = ($_SERVER['PHP_SELF'] ?? '/ww-sso-bridge.php') . '?sso_action=do_login';
$ajax_url = $ajax_url_base;
if (!empty($_GET['redirect_to'])) {
    $rt = preg_replace('/[^\w\-\/:.@]/u', '', (string)$_GET['redirect_to']);
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
    $requested = preg_replace('/[^\w\-\/:.\@]/u', '', $raw_requested);
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
if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') { Wo_SSO_Login(); exit; }

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
        $ww_user_id = null;
        if (!empty($_SESSION['wo_user_id'])) {
            $ww_user_id = (int)$_SESSION['wo_user_id'];
        } elseif (!empty($_SESSION['wp_user_id'])) {
            // Use meta bridge if available
            if (function_exists('get_user_meta')) {
                $ww_user_id = (int)get_user_meta($_SESSION['wp_user_id'], 'wo_user_id', true);
            }
        }
        if (!$ww_user_id || $ww_user_id < 1) {
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
            bz_bridge_log('SSO metadata sync: No non-empty values in session for mapped fields', ['ww_user_id'=>$ww_user_id]);
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
                'wp_user_id'=>$_SESSION['wp_user_id'] ?? null, 'ww_user_id'=>$ww_user_id
            ]);
            return;
        }
    
        // 5. Update WoWonder profile
        if (function_exists('Wo_UpdateUserData')) {
            try {
                $result = Wo_UpdateUserData($ww_user_id, $final_data);
                bz_bridge_log('SSO metadata sync: WoWonder update complete', [
                    'ww_user_id' => $ww_user_id,
                    'fields'     => array_keys($final_data),
                    'result'     => $result
                ]);
            } catch (Throwable $e) {
                bz_bridge_log('SSO metadata sync: WoWonder update ERROR', [
                    'ww_user_id' => $ww_user_id,
                    'error'      => $e->getMessage(),
                ]);
            }
        } else {
            bz_bridge_log('SSO metadata sync: Wo_UpdateUserData unavailable, skipped Wo update', ['ww_user_id' => $ww_user_id]);
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

    // 8. Decide redirect
    $site_base = rtrim($wo['config']['site_url'] ?? '', '/');
    $location = $site_base . '/?cache=' . time();
    $resolve_redirect_to = function($token) use ($site_base) {
        $token_raw = (string)$token;
        $token_safe = preg_replace('/[^\w\-\/:.\@]/u', '', $token_raw);
        if ($token_safe === '') return '';
        $map = [
            'go-pro'   => 'index.php?link1=go-pro',
            'start-up' => 'index.php?link1=start-up',
            'home'     => '/',
        ];
        if (isset($map[$token_safe])) {
            return function_exists('Wo_SeoLink')
                ? Wo_SeoLink($map[$token_safe])
                : rtrim($site_base, '/') . '/' . ltrim($map[$token_safe], '/');
        }
        if (preg_match('#^https?://#i', $token_safe)) {
            $parts = @parse_url($token_safe);
            $site_host = parse_url($site_base, PHP_URL_HOST);
            if (!empty($parts['host']) && $site_host && strcasecmp($parts['host'], $site_host) === 0) return $token_safe;
            return '';
        }
        if (strpos($token_safe, '/') === 0) return $site_base . $token_safe;
        return $site_base . '/' . ltrim($token_safe, '/');
    };

    // redirect_to param (override)
    if (!empty($_REQUEST['redirect_to'])) {
        $resolved = $resolve_redirect_to($_REQUEST['redirect_to']);
        if ($resolved) $location = $resolved;
        bz_bridge_log('Wo_SSO_Login: redirect_to override', ['redirect_to'=>$_REQUEST['redirect_to'], 'resolved'=>$location]);
    } elseif (!empty($_SESSION['wo_auto_registered'])) {
        $location = function_exists('Wo_SeoLink')
            ? Wo_SeoLink('index.php?link1=start-up')
            : $site_base . '/index.php?link1=start-up';
        unset($_SESSION['wo_auto_registered']);
        bz_bridge_log('Wo_SSO_Login: new auto-registered user, redirecting to start-up', ['location'=>$location]);
    } elseif (!empty($posted_last_url)) {
        $candidate_raw = $posted_last_url;
        if ($candidate_raw && strpos($candidate_raw, '//') !== 0) {
            $candidate_abs = (strpos($candidate_raw, '/') === 0) ? $site_base . $candidate_raw : $candidate_raw;
            $scheme = @parse_url($candidate_abs, PHP_URL_SCHEME);
            $site_host = bz_normalize_host(parse_url($site_base, PHP_URL_HOST) ?: '');
            $candidate_host = bz_normalize_host(parse_url($candidate_abs, PHP_URL_HOST) ?: '');
            $is_bridge_ref = function_exists('bz_is_bridge_url') && bz_is_bridge_url($candidate_abs, $site_base);
            if (in_array($scheme, ['http','https'], true) && $candidate_host === $site_host && !$is_bridge_ref) {
                $location = $candidate_abs;
                bz_bridge_log('Wo_SSO_Login: posted_last_url accepted', ['posted'=>$candidate_raw, 'final'=>$location]);
            } else {
                bz_bridge_log('Wo_SSO_Login: posted_last_url rejected as unsafe', ['posted'=>$candidate_raw]);
            }
        }
    } elseif (!empty($last_url) && strpos($last_url, $site_base) === 0) {
        $location = $last_url;
    }
    // Last guard: prevent self-redirects
    if (function_exists('bz_is_bridge_url') && bz_is_bridge_url($location, $site_base)) {
        bz_bridge_log('Wo_SSO_Login: bridge self-redirect fallback', ['chosen'=>$location]);
        $location = $site_base . '/';
        if (function_exists('bz_bridge_loop_count')) bz_bridge_loop_count(false, true);
    }

    bz_bridge_log('Wo_SSO_Login: completed, redirect', [
        'user_id'=>$accepted_user_id,
        'redirect'=>$location
    ]);
    send_json_response(['status'=>200, 'location'=>$location]);
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