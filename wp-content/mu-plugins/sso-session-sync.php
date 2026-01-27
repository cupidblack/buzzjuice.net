<?php
/**
 * BuzzJuice SSO (WordPress side) - Hardened MU-plugin
 *
 * Responsibilities:
 *  - Canonicalize WP PHP session on login and write deterministic shadow session files
 *  - Issue long-lived buzz_sso cookie (hint)
 *  - Issue short-lived one-time HMAC token and (optionally) redirect to sso-landing.php
 *  - Perform coordinated Single Log Out (SLO)
 *
 * Notes:
 *  - BUZZ_SSO_SECRET must be set (env or defined) to issue tokens/cookies
 *  - Shadow files written to BUZZ_SSO_SHADOW_PATH (defaults to ABSPATH/shared/sso_sessions)
 *
 * This file is intentionally defensive: it uses function_exists guards where appropriate
 * so it coexists with helpers in shared/ and streams/ which may define the same helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . '/../../shared/db_helpers.php';

/* --------------------------- Config (safeguarded) --------------------------- */
if (!defined('BUZZ_SSO_COOKIE'))    define('BUZZ_SSO_COOKIE', 'buzz_sso');
// One-time token TTL (seconds) for "sso-landing" handoff. Configurable.
if (!defined('BUZZ_SSO_TTL'))       define('BUZZ_SSO_TTL', 900);
if (!defined('BUZZ_SSO_DEBUG'))     define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_DEBUG_LOG'))     define('BUZZ_DEBUG_LOG', __DIR__ . '/wp_debug_buzz_sso.log');
if (!defined('BUZZ_COOKIE_DOMAIN')) define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_SHADOW_PATH')) define('BUZZ_SSO_SHADOW_PATH', rtrim(ABSPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'sso_sessions');
// Optional page-head injector — OFF by default
if (!defined('BUZZ_SSO_INJECTOR')) define('BUZZ_SSO_INJECTOR', false);

// secret lookup
$__buzz_sso_secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);

/* --------------------------- Utilities --------------------------- */
if (!function_exists('bz_debug_log')) {
    function bz_debug_log($msg, $extra = []) {
        if (!defined('BUZZ_SSO_DEBUG') || !BUZZ_SSO_DEBUG) return;
        $ts = gmdate('Y-m-d H:i:s');
        $meta = [
            'msg' => $msg,
            'ts' => $ts,
            'session_name' => session_name(),
            'session_id' => session_id(),
            'cookie_domain' => defined('BUZZ_COOKIE_DOMAIN') ? BUZZ_COOKIE_DOMAIN : null,
        ];
        if ($extra) $meta['ctx'] = $extra;
        @file_put_contents(BUZZ_DEBUG_LOG, '[' . $ts . '] ' . json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }
}

if (!function_exists('bz_long_lived_expiry_seconds')) {
    function bz_long_lived_expiry_seconds() {
        return 10 * 365 * 24 * 60 * 60; // 10 years
    }
}

/* --------------------------- Shadow session helpers --------------------------- */
if (!function_exists('bz_shadow_session_dir')) {
    function bz_shadow_session_dir() {
        $dir = realpath(BUZZ_SSO_SHADOW_PATH) ?: BUZZ_SSO_SHADOW_PATH;
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir;
    }
}

if (!function_exists('bz_shadow_session_id')) {
    function bz_shadow_session_id($wp_sid = null) {
        $wp_sid = $wp_sid ?: session_id();
        if (!$wp_sid) return null;
        return 'shadow_' . $wp_sid;
    }
}

if (!function_exists('bz_shadow_session_path')) {
    function bz_shadow_session_path($derived_id = null) {
        $dir = bz_shadow_session_dir();
        $derived_id = $derived_id ?: bz_shadow_session_id();
        return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $derived_id;
    }
}

if (!function_exists('bz_atomic_write')) {
    function bz_atomic_write($target_path, $contents) {
        $tmp = $target_path . '.tmp';
        if (@file_put_contents($tmp, $contents, LOCK_EX) === false) {
            @unlink($tmp);
            return false;
        }
        @chmod($tmp, 0640);
        if (!@rename($tmp, $target_path)) {
            // fallback to copy
            if (!@copy($tmp, $target_path) || !@unlink($tmp)) {
                @unlink($tmp);
                return false;
            }
        }
        @chmod($target_path, 0640);
        return true;
    }
}

if (!function_exists('bz_write_shadow_session')) {
    function bz_write_shadow_session($wp_sid = null) {
        if (session_status() !== PHP_SESSION_ACTIVE) return false;
        $wp_sid = $wp_sid ?: session_id();
        if (!$wp_sid) return false;

        $shadow_id = bz_shadow_session_id($wp_sid);
        $path = bz_shadow_session_path($shadow_id);

        $allow_keys = [
            'wp_user_id','wp_user_login','wp_user_email',
            'wo_user_id','qd_user_id','qd_ready','expected_user_id',
            'buzz_sso_last_sync','wp_php_session_id','wp_session_name'
        ];
        $shadow = [];
        foreach ($allow_keys as $k) {
            if (array_key_exists($k, $_SESSION)) $shadow[$k] = $_SESSION[$k];
        }

        $payload = @serialize($shadow);
        if ($payload === false) {
            bz_debug_log('bz_write_shadow_session: serialize failed', ['shadow_id'=>$shadow_id]);
            return false;
        }

        if (!bz_atomic_write($path, $payload)) {
            bz_debug_log('bz_write_shadow_session: write failed', ['path'=>$path, 'shadow_id'=>$shadow_id]);
            return false;
        }

        // .ser copy (same content)
        $path_ser = $path . '.ser';
        if (!bz_atomic_write($path_ser, $payload)) {
            bz_debug_log('bz_write_shadow_session: .ser write failed', ['path_ser'=>$path_ser,'shadow_id'=>$shadow_id]);
        }

        // .json copy for easier cross-app pickup/inspection
        $json_payload = @json_encode($shadow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json_payload !== false) {
            $path_json = $path . '.json';
            if (!bz_atomic_write($path_json, $json_payload)) {
                bz_debug_log('bz_write_shadow_session: .json write failed', ['path_json'=>$path_json,'shadow_id'=>$shadow_id]);
            }
        } else {
            bz_debug_log('bz_write_shadow_session: json_encode failed', ['shadow_id'=>$shadow_id]);
        }

        bz_debug_log('bz_write_shadow_session: written', ['path'=>$path,'shadow_id'=>$shadow_id,'wp_sid'=>$wp_sid]);
        return true;
    }
}

if (!function_exists('bz_remove_shadow_session')) {
    function bz_remove_shadow_session($wp_sid = null) {
        $shadow_id = bz_shadow_session_id($wp_sid ?: session_id());
        $path = bz_shadow_session_path($shadow_id);
        $removed = false;

        $candidates = [$path, $path . '.ser', $path . '.json'];
        foreach ($candidates as $f) {
            if (is_file($f)) {
                @unlink($f);
                $removed = true;
            }
        }
        if ($removed) bz_debug_log('bz_remove_shadow_session: removed', ['shadow_id'=>$shadow_id,'paths'=>$candidates]);
        return $removed;
    }
}

/* --------------------------- Token helpers --------------------------- */
if (!function_exists('bz_build_one_time_token')) {
    function bz_build_one_time_token(array $payload, $secret, $ttl = null) {
        if (empty($secret)) return false;
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + (int)($ttl ?: (defined('BUZZ_SSO_TTL') ? BUZZ_SSO_TTL : 900));
        $json = function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload);
        if ($json === false) return false;
        $sig = hash_hmac('sha256', $json, (string)$secret, true);
        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    }
}

/* --------------------------- Teardown helper --------------------------- */
if (!function_exists('bz_teardown_session_and_cookies')) {
    function bz_teardown_session_and_cookies() {
        // remove shadow
        try { bz_remove_shadow_session(session_id()); } catch (Throwable $e) { bz_debug_log('teardown: remove shadow failed', ['err'=>$e->getMessage()]); }

        // Expire buzz_sso cookie on shared domain and current host
        $expiry = time() - 3600;
        $domain = defined('BUZZ_COOKIE_DOMAIN') ? BUZZ_COOKIE_DOMAIN : '.buzzjuice.net';
        if (PHP_VERSION_ID >= 70300) {
            @setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>$expiry,'path'=>'/','domain'=>$domain,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
            @setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>$expiry,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        } else {
            @setcookie(BUZZ_SSO_COOKIE, '', $expiry, '/', $domain, true, true);
            @setcookie(BUZZ_SSO_COOKIE, '', $expiry, '/', '', true, true);
        }
        if (isset($_COOKIE[BUZZ_SSO_COOKIE])) unset($_COOKIE[BUZZ_SSO_COOKIE]);

        // Destroy session and transient mapping
        $_SESSION = [];
        @session_unset();
        @session_destroy();
    }
}

/* --------------------------- Session lifecycle hooks --------------------------- */

/**
 * On wp_login: canonicalize session, write shadow, issue long-lived buzz_sso cookie,
 * optionally issue a one-time token and redirect to sso-landing.php (non-admin).
 */
add_action('wp_login', function ($user_login, \WP_User $user) {
    global $__buzz_sso_secret;

    // Ensure we have a fresh session and remove previous shadow for current old sid
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $old_sid = session_id();
    try { bz_remove_shadow_session($old_sid); } catch (Throwable $e) { bz_debug_log('wp_login: remove old shadow failed', ['err'=>$e->getMessage()]); }

    // Teardown any previous session data and cookies (best-effort)
    $_SESSION = [];
    @session_unset();
    @session_destroy();
    @setcookie(session_name(), '', time() - 3600, '/', '', true, true);
    @setcookie(session_name(), '', time() - 3600, '/', BUZZ_COOKIE_DOMAIN, true, true);

    // Start a fresh session and canonicalize
    @session_start();
    $new_sid = session_id();

    bz_debug_log('wp_login: created fresh session', ['user'=>$user_login, 'id'=>$user->ID, 'old_sid'=>$old_sid, 'new_sid'=>$new_sid]);

    $_SESSION['wp_user_id']    = (int)$user->ID;
    $_SESSION['wp_user_login'] = (string)$user->user_login;
    $_SESSION['wp_user_email'] = (string)$user->user_email;
    $_SESSION['wo_user_id']    = (int)get_user_meta($user->ID, 'wo_user_id', true);
    $_SESSION['qd_user_id']    = (int)get_user_meta($user->ID, 'qd_user_id', true);
    $_SESSION['qd_ready']         = !empty($_SESSION['qd_user_id']);
    $_SESSION['expected_user_id'] = $_SESSION['qd_user_id'] ?: null;
    $_SESSION['buzz_sso_last_sync'] = time();
    $_SESSION['wp_php_session_id'] = session_id();
    $_SESSION['wp_session_name']   = session_name();

    // Write shadow session files
    bz_write_shadow_session(session_id());

    // Issue long-lived buzz_sso cookie if secret available
    if ($__buzz_sso_secret) {
        $now = time();
        $exp = $now + bz_long_lived_expiry_seconds();
        $payload = [
            'ver'           => 1,
            'wp_user_id'    => (int)$_SESSION['wp_user_id'],
            'wp_user_login' => (string)$_SESSION['wp_user_login'],
            'wp_user_email' => (string)$_SESSION['wp_user_email'],
            'wo_user_id'    => (int)$_SESSION['wo_user_id'],
            'qd_user_id'    => (int)$_SESSION['qd_user_id'],
            'cookie_domain' => BUZZ_COOKIE_DOMAIN,
            'session_name'  => session_name(),
            'session_id'    => session_id(),
            'handler'       => ini_get('session.serialize_handler'),
            'iat' => $now,
            'exp' => $exp,
        ];
        $json = wp_json_encode($payload);
        $sig  = hash_hmac('sha256', $json, (string)$__buzz_sso_secret, true);
        $token = rtrim(strtr(base64_encode($json), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        // Set cookie with long-lived expiry
        if (PHP_VERSION_ID >= 70300) {
            setcookie(BUZZ_SSO_COOKIE, $token, [
                'expires'  => $exp,
                'path'     => '/',
                'domain'   => BUZZ_COOKIE_DOMAIN,
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie(BUZZ_SSO_COOKIE, $token, $exp, '/', BUZZ_COOKIE_DOMAIN, true, true);
        }

        $_SESSION['buzz_sso_last'] = $payload;
        bz_debug_log('buzz_sso cookie issued (long-lived)', ['wp_sid'=>session_id(), 'shadow_sid'=>bz_shadow_session_id(session_id()), 'expires'=>date('c', $exp)]);
    }

    // Persist mapping for debugging/troubleshooting in usermeta
    update_user_meta($user->ID, 'buzz_wp_php_sessid', session_id());

    bz_debug_log('wp_login processed', ['user'=>$user_login,'id'=>$user->ID,'wp_sid'=>session_id(),'shadow_sid'=>bz_shadow_session_id(session_id())]);

    // Build short-lived token and redirect to sso-landing.php for orchestrated iframe handoff (skip for admin users)
    try {
        global $__buzz_sso_secret;
        if (!in_array('administrator', (array) $user->roles, true) && $__buzz_sso_secret) {
            $one_payload = [
                'wp_user_id' => (int)$user->ID,
                'wp_user_login' => (string)$user->user_login,
                'wp_user_email' => (string)$user->user_email,
                'wp_sid' => session_id(),
                'session_name' => session_name(),
            ];
            $one_token = bz_build_one_time_token($one_payload, $__buzz_sso_secret, defined('BUZZ_SSO_TTL') ? BUZZ_SSO_TTL : 900);
            if ($one_token) {
                $redirect_to = '/';
                if (!empty($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])) {
                    $redirect_to = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
                }
                $sso_target = site_url('/sso-landing.php?token=' . rawurlencode($one_token) . '&redirect_to=' . rawurlencode($redirect_to));
                wp_safe_redirect($sso_target);
                exit;
            } else {
                bz_debug_log('wp_login: failed to create one-time token', []);
            }
        }
    } catch (Throwable $t) {
        bz_debug_log('wp_login: one-time token redirect failed', ['err' => $t->getMessage()]);
    }
}, 10, 2);

/* --------------------------- Optional injector (disabled by default) --------------------------- */
add_action('wp_head', function() {
    if (!defined('BUZZ_SSO_INJECTOR') || !BUZZ_SSO_INJECTOR) return;
    if (is_user_logged_in()) {
        // Minimal injector — diagnostic only.
        ?>
        <script>
        (function(){ console.log('BuzzJuice SSO injector (debug) active'); })();
        </script>
        <?php
    }
});

/* ---------------- Defensive/init behaviours preserved ------------------ */

/**
 * Defensive fallback: if buzz_sso/shadow missing, regenerate cookie without destroying WP session
 * Only run when user is logged in and canonical session keys exist.
 */
add_action('init', function () {
    bz_shadow_session_dir(); // ensure dir exists
    global $__buzz_sso_secret;
    if ( ! is_user_logged_in() ) return; // only for authenticated users

    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $wp_sid = session_id();

    if ($wp_sid) {
        // If buzz_sso cookie is missing but session is valid, regenerate, do not destroy
        if ($__buzz_sso_secret && empty($_COOKIE[BUZZ_SSO_COOKIE])) {
            if (!empty($_SESSION['wp_user_id']) && !empty($_SESSION['wp_user_login']) && !empty($_SESSION['wp_user_email'])) {
                $now = time();
                $exp = $now + bz_long_lived_expiry_seconds();
                $payload = [
                    'ver'           => 1,
                    'wp_user_id'    => (int)$_SESSION['wp_user_id'],
                    'wp_user_login' => (string)$_SESSION['wp_user_login'],
                    'wp_user_email' => (string)$_SESSION['wp_user_email'],
                    'wo_user_id'    => (int)($_SESSION['wo_user_id'] ?? 0),
                    'qd_user_id'    => (int)($_SESSION['qd_user_id'] ?? 0),
                    'cookie_domain' => BUZZ_COOKIE_DOMAIN,
                    'session_name'  => session_name(),
                    'session_id'    => session_id(),
                    'handler'       => ini_get('session.serialize_handler'),
                    'iat' => $now,
                    'exp' => $exp,
                ];
                $json = wp_json_encode($payload);
                $sig  = hash_hmac('sha256', $json, (string)$__buzz_sso_secret, true);
                $token = rtrim(strtr(base64_encode($json), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

                if (PHP_VERSION_ID >= 70300) {
                    setcookie(BUZZ_SSO_COOKIE, $token, [
                        'expires'  => $exp,
                        'path'     => '/',
                        'domain'   => BUZZ_COOKIE_DOMAIN,
                        'secure'   => true,
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                } else {
                    setcookie(BUZZ_SSO_COOKIE, $token, $exp, '/', BUZZ_COOKIE_DOMAIN, true, true);
                }

                $_SESSION['buzz_sso_last'] = $payload;
                bz_debug_log('init: buzz_sso cookie regenerated (long-lived)', ['wp_sid'=>$wp_sid, 'shadow_sid'=>bz_shadow_session_id($wp_sid), 'expires'=>date('c',$exp)]);
            }
        }
    }
}, 1);

/* --------------------------- Admin debug endpoint & orchestrator logout processing --------------------------- */

/**
 * Process orchestrator-issued WP logout token (sso_one_time). Performs same cleanup as wp_logout.
 */
add_action('login_init', function() {
    // Only act when action=logout and sso_one_time present
    if (empty($_GET['action']) || $_GET['action'] !== 'logout') return;
    if (empty($_GET['sso_one_time'])) return;

    $one = (string) $_GET['sso_one_time'];
    $secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);
    if (!$secret) {
        bz_debug_log('sso_token_login_init: missing BUZZ_SSO_SECRET', []);
        return;
    }

    // Validate one-time token (same format used in shared/sso-logout.php)
    $parts = explode('.', $one, 2);
    if (count($parts) !== 2) {
        bz_debug_log('sso_token_login_init: malformed token', ['token_preview'=>substr($one,0,16)]);
        return;
    }
    $json = base64_decode(strtr($parts[0], '-_', '+/'));
    $sig  = base64_decode(strtr($parts[1], '-_', '+/'));
    if ($json === false || $sig === false) {
        bz_debug_log('sso_token_login_init: token base64 decode failed', []);
        return;
    }
    $calc = hash_hmac('sha256', $json, (string)$secret, true);
    if (!hash_equals($calc, $sig)) {
        bz_debug_log('sso_token_login_init: token HMAC mismatch', []);
        return;
    }
    $payload = @json_decode($json, true);
    if (!is_array($payload) || (isset($payload['exp']) && time() > (int)$payload['exp'])) {
        bz_debug_log('sso_token_login_init: token expired or invalid payload', ['payload'=>$payload ?? null]);
        return;
    }

    // Token valid: perform WP-side SSO cleanup (same actions performed on wp_logout)
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $wp_sid = session_id();

    try { bz_remove_shadow_session($wp_sid); } catch (Throwable $e) { bz_debug_log('sso_token_login_init: remove shadow failed', ['err'=>$e->getMessage()]); }

    // Expire buzz_sso cookie on shared domain and current host
    $expiry = time() - 3600;
    $domain = defined('BUZZ_COOKIE_DOMAIN') ? BUZZ_COOKIE_DOMAIN : '.buzzjuice.net';
    if (PHP_VERSION_ID >= 70300) {
        @setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>$expiry,'path'=>'/','domain'=>$domain,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        @setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>$expiry,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    } else {
        @setcookie(BUZZ_SSO_COOKIE, '', $expiry, '/', $domain, true, true);
        @setcookie(BUZZ_SSO_COOKIE, '', $expiry, '/', '', true, true);
    }
    if (isset($_COOKIE[BUZZ_SSO_COOKIE])) unset($_COOKIE[BUZZ_SSO_COOKIE]);

    // Destroy session and transient mapping
    $_SESSION = [];
    @session_unset();
    @session_destroy();

    try {
        $transient_key = 'buzz_shadow_sid_' . $wp_sid;
        delete_transient($transient_key);
    } catch (Throwable $e) {
        bz_debug_log('sso_token_login_init: delete_transient threw', ['err'=>$e->getMessage()]);
    }

    bz_debug_log('sso_token_login_init: processed token logout', ['wp_sid'=>$wp_sid, 'shadow_sid'=>bz_shadow_session_id($wp_sid)]);

    // Redirect into central orchestrator so it can invalidate other platforms (orchestrator will detect from_wp)
    global $__buzz_sso_secret;
    $ssec = $__buzz_sso_secret ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : getenv('BUZZ_SSO_SECRET'));
    $url = 'https://buzzjuice.net/shared/sso-logout.php?sso_secret=' . rawurlencode((string)$ssec) . '&from_wp=1&logged_out=1';
    wp_safe_redirect($url);
    exit;
}, 1);

/**
 * Add wp_logout hook to trigger orchestrator-initiated Single Log Out when WP user logs out.
 */
add_action('wp_logout', function() {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $wp_sid = session_id();

    try { bz_remove_shadow_session($wp_sid); } catch (Throwable $e) { bz_debug_log('wp_logout: remove shadow failed', ['err'=>$e->getMessage()]); }

    // Expire buzz_sso cookie on shared domain and current host
    $expiry = time() - 3600;
    $domain = defined('BUZZ_COOKIE_DOMAIN') ? BUZZ_COOKIE_DOMAIN : '.buzzjuice.net';
    if (PHP_VERSION_ID >= 70300) {
        @setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>$expiry,'path'=>'/','domain'=>$domain,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        @setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>$expiry,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    } else {
        @setcookie(BUZZ_SSO_COOKIE, '', $expiry, '/', $domain, true, true);
        @setcookie(BUZZ_SSO_COOKIE, '', $expiry, '/', '', true, true);
    }
    if (isset($_COOKIE[BUZZ_SSO_COOKIE])) unset($_COOKIE[BUZZ_SSO_COOKIE]);

    // Destroy session and transient mapping
    $_SESSION = [];
    @session_unset();
    @session_destroy();
    try {
        $transient_key = 'buzz_shadow_sid_' . $wp_sid;
        delete_transient($transient_key);
    } catch (Throwable $e) {
        bz_debug_log('wp_logout: delete_transient threw', ['err'=>$e->getMessage()]);
    }

    bz_debug_log('wp_logout: processed WP-side logout', ['wp_sid'=>$wp_sid, 'shadow_sid'=>bz_shadow_session_id($wp_sid)]);

    // Redirect into orchestrator for SLO chain (fallback: cabin=home triggers chain)
    wp_safe_redirect('https://buzzjuice.net/shared/sso-logout.php?cabin=home');
    exit;
}, 10);

/* ---------------- BuddyBoss redirect compatibility (unchanged) -- */
add_action('plugins_loaded', function() {
    if (function_exists('bb_login_redirect')) {
        remove_filter('bp_login_redirect', 'bb_login_redirect', PHP_INT_MAX);
        remove_filter('login_redirect', 'bb_login_redirect', PHP_INT_MAX);
        add_filter('bp_login_redirect', 'bluecrown_bb_login_redirect', PHP_INT_MAX, 3);
        add_filter('login_redirect', 'bluecrown_bb_login_redirect', PHP_INT_MAX, 3);
    }
});
function bluecrown_bb_login_redirect($redirect_to, $request, $user) {
    if ($user && is_object($user) && is_a($user, 'WP_User')) {
        if (in_array('administrator', (array) $user->roles, true)) {
            return $redirect_to;
        }
        if (function_exists('bb_redirect_after_action')) {
            $redirect_to = bb_redirect_after_action($redirect_to, $user->ID, 'login');
        }
    }
    if (!empty($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])) {
        $redirect_to = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
    } else {
        if (function_exists('bb_redirect_after_action')) {
            $redirect_to = bb_redirect_after_action($redirect_to, null, 'login');
        }
    }
    return $redirect_to;
}

/* ---------------- Optional: logout without confirm -------------- */
add_action('check_admin_referer', 'logout_without_confirm', 10, 2);
function logout_without_confirm($action, $result)
{
    if ($action == "log-out" && !isset($_GET['_wpnonce'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : 'https://buzzjuice.net';
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));
        header("Location: $location");
        exit;
    }
}

/* ---------------- Sanitize inbound last_url cookie (avoid bridge planting) ---------------- */
add_action('init', 'bz_sanitize_last_url_cookie', 5);
function bz_sanitize_last_url_cookie() {
    if (empty($_COOKIE['last_url'])) return;
    $last = wp_unslash($_COOKIE['last_url']);
    $probe = strtolower((string)$last);
    $sso_markers = [
        'ww-sso-bridge.php',
        'qd-sso-bridge.php',
        'sso_action=do_login',
        'sso_client_log',
        'from_wp=1',
        '/shared/sso-logout.php',
    ];
    foreach ($sso_markers as $m) {
        if (strpos($probe, $m) !== false) {
            @setcookie('last_url', '', time() - 3600, '/');
            if (isset($_COOKIE['last_url'])) unset($_COOKIE['last_url']);
            if (function_exists('bz_debug_log')) bz_debug_log('bz_sanitize_last_url_cookie: removed suspicious last_url cookie', ['original' => $last]);
            return;
        }
    }
}

?>