<?php
/**
 * buzz_sso_safe_session.php
 *
 * Must-use plugin to perform a guarded/session-start early in WP bootstrap so
 * unguarded session_start() calls in plugins (eg. WooCommerce-Currency-Switcher)
 * won't repeatedly trigger "Failed to decode session object" warnings when a
 * corrupted session file exists.
 *
 * - Avoids modifying plugins.
 * - Only attempts to start a session when an incoming session cookie exists
 *   (so it does not create many anonymous sessions).
 * - If session decode fails it will attempt best-effort recovery by removing
 *   the corrupted session file and retrying a fresh session.
 *
 * Install: place in wp-content/mu-plugins/ (mu-plugins load before normal plugins).
 */

if (defined('BUZZ_SSO_SAFE_SESSION_MU_LOADED')) return;
define('BUZZ_SSO_SAFE_SESSION_MU_LOADED', true);

// Prefer php_serialize to decode WordPress-style shadows
@ini_set('session.serialize_handler', 'php_serialize');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.cookie_httponly', '1');

/**
 * Minimal throttled marker to avoid repeated unlink storms.
 */
function bz_mu_throttle_check($key, $ttl = 300) {
    if (empty($key) || $ttl <= 0) return false;
    $tmp = sys_get_temp_dir();
    $file = $tmp . DIRECTORY_SEPARATOR . 'bz_mu_sess_' . preg_replace('/[^a-z0-9._-]/i', '', substr($key,0,32));
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

/**
 * Try to compute the session file path for a given sid.
 */
function bz_mu_session_file_path_for_sid($sid) {
    if (!$sid) return '';
    $save_path = (string) ini_get('session.save_path');
    if (trim($save_path) === '') $save_path = sys_get_temp_dir();
    if (preg_match('#^N;(.+)#', $save_path, $m)) $save_path = $m[1];
    $save_path = rtrim($save_path, DIRECTORY_SEPARATOR);
    return $save_path . DIRECTORY_SEPARATOR . 'sess_' . $sid;
}

/**
 * Guarded session start: attempt to start, on decode error try removing corrupted file and retry.
 * Preserve incoming SID when present.
 */
function bz_mu_safe_session_start() {
    // Only run if session not already active
    if (session_status() === PHP_SESSION_ACTIVE) return true;

    // Determine incoming SID if any
    $sname = session_name();
    $sid = null;
    if (!empty($_COOKIE[$sname])) {
        $sid = preg_replace('/[^a-zA-Z0-9,_-]/', '', (string) $_COOKIE[$sname]);
    } elseif (!empty($_COOKIE['PHPSESSID'])) {
        $sid = preg_replace('/[^a-zA-Z0-9,_-]/', '', (string) $_COOKIE['PHPSESSID']);
    }

    // Only attempt to start a session when an incoming cookie is present, to avoid creating many anonymous sessions.
    if (!$sid && empty($_COOKIE['buzz_sso']) && empty($_COOKIE['BUZZ_SSO'])) {
        // Nothing to do
        return false;
    }

    if ($sid) {
        @session_id($sid);
    }

    // Intercept PHP warnings about failed decode and convert to Exception so we can recover.
    $prev = set_error_handler(function($errno, $errstr) {
        if (stripos($errstr, 'Failed to decode session object') !== false || stripos($errstr, 'Failed to decode') !== false || stripos($errstr, 'Session has been destroyed') !== false) {
            throw new Exception($errstr);
        }
        // Not ours — let normal handler run
        return false;
    });

    try {
        session_start();
        if ($prev) set_error_handler($prev);
        return true;
    } catch (Throwable $e) {
        if ($prev) set_error_handler($prev);

        // Compute session file path
        $session_file = bz_mu_session_file_path_for_sid($sid);
        $recent_key = $sid ? 'bz_mu_recover_' . preg_replace('/[^a-zA-Z0-9_-]/', '', substr($sid, 0, 64)) : '';

        // If we recovered recently, try starting fresh without unlinking to reduce churn
        if ($recent_key && bz_mu_throttle_check($recent_key, 300)) {
            try {
                @session_id(''); // start new id
                session_start();
                return true;
            } catch (Throwable $e2) {
                // fall through
            }
        }

        // Attempt best-effort recovery: remove corrupted session file and retry
        if ($session_file && is_file($session_file)) {
            @unlink($session_file);
            @unlink($session_file . '.ser');
            @unlink($session_file . '.json');
            // mark throttle so we don't repeatedly remove for same sid
            if ($recent_key) bz_mu_throttle_check($recent_key, 300);
        }

        // Retry starting fresh
        try {
            @session_id('');
            session_start();
            return true;
        } catch (Throwable $e3) {
            // couldn't start — give up silently
            return false;
        }
    }
}

/**
 * Early guarded start on mu-plugin load.
 * We call it now so subsequent plugin calls to session_start() run while session is active.
 */
try {
    bz_mu_safe_session_start();
} catch (Throwable $ignore) {
    // Never break WP bootstrap for session issues — just continue.
    unset($ignore);
}