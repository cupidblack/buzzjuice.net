<?php
// Minimal WoWonder init.php with guarded session start to support BuzzJuice SSO
// Updated: 2025-10-07 — reduced session churn, added recovery throttling, prevented sessions for high-frequency streams/beacons.
// - Do NOT modify or destroy WordPress-owned PHPSESSID cookie.
// - Use the WP-produced shadow session files (sess_shadow_...) for rehydration and reconciliation.
// - Throttle repeated recovery/unlink operations to avoid I/O/log storms (BUZZ_SSO_RECOVERY_THROTTLE).
// - Avoid starting sessions for streams polling endpoints or client beacons unless debug or explicit SSO action.

@ini_set('session.cookie_httponly', 1);
@ini_set('session.use_only_cookies', 1);

// Ensure we use the 'php_serialize' handler so session_start() can decode php_serialize-format session blobs
@ini_set('session.serialize_handler', 'php_serialize');

if (!version_compare(PHP_VERSION, '7.1.0', '>=')) {
    exit("Required PHP_VERSION >= 7.1.0 , Your PHP_VERSION is : " . PHP_VERSION . "\n");
}
if (!function_exists("mysqli_connect")) {
    exit("MySQLi is required to run the application, please contact your hosting to enable php mysqli.");
}
date_default_timezone_set('UTC');

// Basic SSO constants (can be overridden elsewhere)
if (!defined('BUZZ_SSO_COOKIE'))   define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN')) define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))    define('BUZZ_SSO_DEBUG', false);
// Throttle window (seconds) to suppress repeated recovery/unlink + logging for the same session id.
// Set to 0 to disable throttling (legacy behavior).
if (!defined('BUZZ_SSO_RECOVERY_THROTTLE')) define('BUZZ_SSO_RECOVERY_THROTTLE', 300);

// Small helpers
function bz_is_debug_init() {
    return (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG) || !empty($_GET['sso_debug']);
}

// Throttle helper using temp files. Returns true if a key was logged recently (within $ttl seconds).
function bz_log_throttle_check($key, $ttl = 300) {
    if (empty($key) || $ttl <= 0) return false;
    $tmp = sys_get_temp_dir();
    $file = $tmp . DIRECTORY_SEPARATOR . 'bz_sso_log_' . preg_replace('/[^a-z0-9._-]/i', '', substr($key,0,32));
    $now = time();
    if (is_file($file)) {
        $ts = @file_get_contents($file);
        if ($ts !== false && is_numeric($ts) && ($now - (int)$ts) < (int)$ttl) {
            @touch($file);
            return true;
        }
    }
    @file_put_contents($file, (string)$now, LOCK_EX);
    @chmod($file, 0600);
    return false;
}

// compact debug logger — minimise noise in non-debug mode and throttle repeated recovery lines
function bz_debug_log_init($msg, $extra = []) {
    $is_debug = bz_is_debug_init();

    // In non-debug mode only keep critical/recovery lines
    if (!$is_debug) {
        $keywords = ['ERROR','recover','Recovered','Skipping','Session started after recovery','Failed to start session after recovery','Session decode error'];
        $ok = false;
        foreach ($keywords as $k) {
            if (stripos($msg, $k) !== false) { $ok = true; break; }
        }
        if (!$ok) return;
    }

    // Throttle recovery-related messages to avoid log/I/O storms
    if (!$is_debug && (stripos($msg, 'recover') !== false || stripos($msg, 'Session started after recovery') !== false || stripos($msg, 'Failed to start session after recovery') !== false)) {
        $sid = $_COOKIE[session_name()] ?? $_COOKIE['PHPSESSID'] ?? '';
        $sid_preview = is_string($sid) ? substr($sid,0,24) : '';
        $key = md5($msg . '|' . $sid_preview);
        if (bz_log_throttle_check($key, BUZZ_SSO_RECOVERY_THROTTLE)) {
            // skip duplicate recovery log within throttle window
            return;
        }
    }

    $ts = gmdate('Y-m-d H:i:s');
    $meta = [
        'msg' => $msg,
        'server' => [
            'HTTP_HOST'    => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI'  => $_SERVER['REQUEST_URI'] ?? null,
            'REMOTE_ADDR'  => $_SERVER['REMOTE_ADDR'] ?? null,
        ],
    ];
    if ($is_debug) {
        $meta['server']['UA'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $meta['cookies'] = $_COOKIE ?? [];
        if ($extra) $meta['extra'] = $extra;
    } else {
        // keep only small extra bits non-debug
        if (!empty($extra) && is_array($extra)) {
            $meta['extra'] = array_intersect_key($extra, array_flip(['session_id','file','error']));
        }
    }

    @file_put_contents(__DIR__ . '/init_debug_buzz_sso.log', "[$ts] " . json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

// crawler detection (compact)
function bz_is_crawler_init() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!$ua) return false;
    $bots = ['bot','crawl','spider','slurp','bingpreview','mediapartners','facebookexternalhit','applebot','yandex','baiduspider','bingbot','duckduckbot','petalbot','exabot','semrushbot','ahrefsbot','pinterest','twitterbot','discordbot','telegrambot','whatsapp','curl','wget'];
    foreach ($bots as $b) {
        if (strpos($ua, $b) !== false) return true;
    }
    return false;
}

// Heuristic to decide whether to start/resume a PHP session for this request.
// Start only for SSO-relevant actions, non-GET state changes, admin/debug, or explicit bridge flows.
function bz_request_needs_session_init() {
    $has_sso_cookie = !empty($_COOKIE[BUZZ_SSO_COOKIE]);

    $is_sso_action = (
        (isset($_GET['sso_action']) && $_GET['sso_action'] === 'do_login') ||
        (isset($_POST['sso_action']) && $_POST['sso_action'] === 'do_login')
    );

    // client-side beacon should NOT start session in production
    $is_client_beacon = (!empty($_GET['sso_client_log']) && $_SERVER['REQUEST_METHOD'] === 'POST');
    if ($is_client_beacon && !bz_is_debug_init()) $is_client_beacon = false;

    $is_debug = bz_is_debug_init();
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $non_get = ($method !== 'GET');

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path_only = parse_url($request_uri, PHP_URL_PATH) ?: $request_uri;
    $is_admin_path = strpos($path_only, '/wp-admin') === 0 || strpos($path_only, '/wp-login.php') === 0;

    // Deny starting sessions for high-frequency streams polling / beacon endpoints unless debug or SSO action/non-GET
    $no_session_prefixes = [
        '/streams/requests.php',
        '/streams/ww-sso-bridge.php', // check/beacon variants
        '/streams/welcome',
        '/streams/',   // any other streams polling endpoints
        '/wp-json/',
        '/static/',
    ];
    foreach ($no_session_prefixes as $p) {
        if ($path_only && strpos($path_only, $p) === 0) {
            if ($is_debug || $is_sso_action || $is_client_beacon) return true;
            if ($non_get) return true;
            return false; // skip session for high-frequency polling
        }
    }

    // If a buzz_sso cookie exists, don't auto-start session for benign GETs; require explicit SSO action/admin/debug/non-GET.
    if ($has_sso_cookie) {
        if ($is_debug || $is_sso_action || $is_client_beacon || $non_get || $is_admin_path) return true;
        return false;
    }

    // Default: session required for non-GET, debug, admin paths, or explicit sso actions/beacons.
    return $is_sso_action || $is_client_beacon || $non_get || $is_debug || $is_admin_path;
}

// Helper: attempt to start session safely and recover from "Failed to decode session object" by removing corrupted file.
// Improvements:
// - If the same SID was recovered recently (throttle), try starting a fresh session without unlinking file first.
// - Only unlink the session file when retrying could help; record throttle marker after unlink.
function bz_safe_session_start($preserve_sid = true) {
    @ini_set('session.serialize_handler', 'php_serialize');

    $sname = session_name();
    $sid = null;
    if (!empty($_COOKIE[$sname])) {
        $sid = preg_replace('/[^a-zA-Z0-9,_-]/', '', (string) $_COOKIE[$sname]);
    } elseif (!empty($_COOKIE['PHPSESSID'])) {
        $sid = preg_replace('/[^a-zA-Z0-9,_-]/', '', (string) $_COOKIE['PHPSESSID']);
    }

    if ($preserve_sid && $sid) {
        @session_id($sid);
    }

    // Convert PHP warnings about failed decode into Exceptions so we can recover
    $prevHandler = set_error_handler(function($errno, $errstr, $errfile = null, $errline = null) {
        if (stripos($errstr, 'Failed to decode session object') !== false || stripos($errstr, 'Failed to decode') !== false || stripos($errstr, 'Session has been destroyed') !== false) {
            throw new Exception($errstr);
        }
        return false;
    });

    try {
        session_start();
        if ($prevHandler) set_error_handler($prevHandler);
        if (bz_is_debug_init()) bz_debug_log_init('Session started (safe)', ['session_id' => session_id(), 'preserve_sid' => (bool)$preserve_sid]);
        return true;
    } catch (Throwable $e) {
        if ($prevHandler) set_error_handler($prevHandler);

        // Compute session file path
        $save_path = (string)ini_get('session.save_path');
        if (trim($save_path) === '') $save_path = sys_get_temp_dir();
        if (preg_match('#^N;(.+)#', $save_path, $m)) $save_path = $m[1];
        $save_path = rtrim($save_path, DIRECTORY_SEPARATOR);

        $session_file = '';
        if (!empty($sid)) $session_file = $save_path . DIRECTORY_SEPARATOR . 'sess_' . $sid;

        // If we recently recovered this SID, avoid unlinking repeatedly.
        $recent_key = $sid ? 'recover_sid_' . preg_replace('/[^a-zA-Z0-9_-]/', '', substr($sid, 0, 64)) : '';
        if ($recent_key && BUZZ_SSO_RECOVERY_THROTTLE > 0 && bz_log_throttle_check($recent_key, BUZZ_SSO_RECOVERY_THROTTLE)) {
            // recent recovery performed -> try fresh session without unlinking
            bz_debug_log_init('Recent recovery detected for SID; skipping unlink and starting fresh session', ['sid_preview' => substr($sid, 0, 12)]);
            try {
                @session_id('');
                session_start();
                if (bz_is_debug_init()) bz_debug_log_init('Session started after recovery (skipped unlink, fresh sid)', ['session_id' => session_id()]);
                return true;
            } catch (Throwable $e2) {
                bz_debug_log_init('Attempt to start fresh session after recent recovery failed', ['ex' => $e2->getMessage()]);
                // fall through to unlink attempt below
            }
        }

        // Attempt recovery: remove corrupted session file (best-effort) and retry
        if (!empty($sid) && $session_file) {
            @unlink($session_file);
            @unlink($session_file . '.ser');
            @unlink($session_file . '.json');

            // register throttle marker so we don't repeatedly unlink for same SID
            if ($recent_key) bz_log_throttle_check($recent_key, BUZZ_SSO_RECOVERY_THROTTLE);

            bz_debug_log_init('Recovered from session decode error: removed corrupted session file', ['file' => $session_file, 'error' => $e->getMessage()]);
        } else {
            bz_debug_log_init('Session decode error but no SID available to remove session file', ['error' => $e->getMessage()]);
        }

        // Try starting a fresh session now
        try {
            if ($preserve_sid) @session_id('');
            session_start();
            if (bz_is_debug_init()) bz_debug_log_init('Session started after recovery', ['session_id' => session_id()]);
            return true;
        } catch (Throwable $e2) {
            bz_debug_log_init('Failed to start session after recovery attempt', ['ex1' => $e->getMessage(), 'ex2' => $e2->getMessage()]);
            return false;
        }
    }
}

// Guarded session start
if (session_status() === PHP_SESSION_NONE) {
    $is_crawler = bz_is_crawler_init();
    $needs_session = bz_request_needs_session_init();
    $is_debug = bz_is_debug_init();

    if ($is_crawler && !$needs_session && !$is_debug) {
        // Avoid starting sessions for crawlers on benign requests
        bz_debug_log_init('Skipping session_start for crawler on benign request', ['ua' => $_SERVER['HTTP_USER_AGENT'] ?? null, 'uri' => $_SERVER['REQUEST_URI'] ?? null]);
    } elseif (!$needs_session && !$is_debug) {
        // Benign anonymous GET — skip session to avoid creating/resuming many session files
        // intentionally silent to reduce log noise
    } else {
        // Configure session settings compatible with WP mu-plugin and other platforms (defensive)
        @ini_set('session.cookie_samesite', 'Lax');
        @ini_set('session.cookie_secure', 1);
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.use_only_cookies', 1);
        @ini_set('session.use_strict_mode', 1);
        @ini_set('session.serialize_handler', 'php_serialize');

        // Attempt to preserve incoming SID if provided and sanitized
        $sname = session_name();
        $sid = null;
        if (!empty($_COOKIE[$sname])) {
            $sid = preg_replace('/[^a-zA-Z0-9,_-]/', '', (string) $_COOKIE[$sname]);
        } elseif (!empty($_COOKIE['PHPSESSID'])) {
            $sid = preg_replace('/[^a-zA-Z0-9,_-]/', '', (string) $_COOKIE['PHPSESSID']);
        }
        if ($sid) {
            // Only resume when needed (SSO action, admin, non-GET) — otherwise avoid touching WP-owned session.
            if ($needs_session || $is_debug) {
                @session_id($sid);
                if ($is_debug) bz_debug_log_init('Resuming PHP session from cookie (pre-start)', ['sid_preview'=>substr($sid,0,16).'...']);
            } else {
                bz_debug_log_init('Incoming session cookie present but request does not need session; skipping resume', ['sid_preview'=>substr($sid,0,16).'...']);
                $sid = null;
            }
        } else {
            if ($is_debug) bz_debug_log_init('No incoming PHP session cookie found; starting new session (required by request)');
        }

        // Start session with safe wrapper that can recover corrupted session files
        $ok = bz_safe_session_start(true);
        if (!$ok) {
            // If we failed to get a session, continue without session — many flows work anonymously.
            bz_debug_log_init('bz_safe_session_start failed; continuing without PHP session (benign fallback)', ['uri'=>$_SERVER['REQUEST_URI'] ?? null]);
        }
    }
}

// Keep original behavior for other includes
@ini_set('gd.jpeg_ignore_warning', 1);

require_once('assets/libraries/DB/vendor/joshcam/mysqli-database-class/MySQL-Maria.php');
require_once('includes/cache.php');
require_once('includes/functions_general.php');
require_once('includes/tabels.php');
require_once('includes/functions_one.php');
require_once('includes/functions_two.php');
require_once('includes/functions_three.php');