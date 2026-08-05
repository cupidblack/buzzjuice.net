<?php
declare(strict_types=1);

/*
 * shared/logout-common.php
 * Minimal neutral logout helpers for BuzzJuice SSO cleanup.
 */

if (!defined('BZ_LOGOUT_COMMON_LOADED')) {
    define('BZ_LOGOUT_COMMON_LOADED', true);
}

if (!defined('BZ_LOGOUT_COOKIE_DOMAIN')) {
    define('BZ_LOGOUT_COOKIE_DOMAIN', '.buzzjuice.net');
}
if (!defined('BZ_LOGOUT_LOG')) {
    define('BZ_LOGOUT_LOG', __DIR__ . '/logout-debug.log');
}

/**
 * Structured logger for logout events.
 */
function bz_logout_log($app, $user_id = null, $event = '', $status = 'info', $extra = []) {
    $entry = [
        'ts'     => gmdate('Y-m-d H:i:s'),
        'app'    => $app,
        'user'   => $user_id ?? null,
        'event'  => $event,
        'status' => $status,
        'remote' => $_SERVER['REMOTE_ADDR'] ?? null,
        'uri'    => $_SERVER['REQUEST_URI'] ?? null,
    ];
    if (!empty($_SERVER['HTTP_USER_AGENT'])) $entry['ua'] = substr($_SERVER['HTTP_USER_AGENT'], 0, 200);
    if (!empty($extra)) $entry['extra'] = $extra;
    @file_put_contents(BZ_LOGOUT_LOG, json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Ensure PHP session is started (idempotent).
 */
function bz_ensure_session_started(): bool {
    if (session_status() === PHP_SESSION_ACTIVE) return true;
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
        return session_status() === PHP_SESSION_ACTIVE;
    }
    return false;
}

/**
 * Capture a best-effort session identifier BEFORE clearing session/cookies.
 * Returns empty string if none found.
 */
function bz_capture_session_id(): string {
    if (!empty($_SESSION['user_id'])) return (string) $_SESSION['user_id'];
    if (!empty($_SESSION['JWT'])) return (string) $_SESSION['JWT'];
    if (!empty($_COOKIE['user_id'])) return (string) $_COOKIE['user_id'];
    if (!empty($_COOKIE['JWT'])) return (string) $_COOKIE['JWT'];
    if (!empty($_COOKIE[session_name()])) return (string) $_COOKIE[session_name()];
    if (!empty($_COOKIE['PHPSESSID'])) return (string) $_COOKIE['PHPSESSID'];
    return '';
}

/**
 * Return a best-effort "is_ssl" boolean (works on WP and non-WP).
 */
function bz_is_ssl(): bool {
    if (function_exists('is_ssl')) return is_ssl();
    return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

/**
 * Clear a cookie reliably for domain and host variants.
 */
function bz_clear_cookie(string $name, string $path = '/', ?string $domain = null): void {
    if ($name === '') return;
    $domain = $domain ?? BZ_LOGOUT_COOKIE_DOMAIN;
    $expiry = time() - 3600;
    $secure = bz_is_ssl();

    // With secure+httponly
    @setcookie($name, '', $expiry, $path, $domain, $secure, true);
    @setcookie($name, '', $expiry, $path, '', $secure, true);
    // Without httponly (older variants)
    @setcookie($name, '', $expiry, $path, $domain);
    @setcookie($name, '', $expiry, $path, '');
    if (isset($_COOKIE[$name])) unset($_COOKIE[$name]);
}

/**
 * Clear a list of cookies and session cookie name.
 */
function bz_clear_cookies(array $names, string $path = '/', ?string $domain = null): void {
    foreach ($names as $n) {
        if ($n === '' || $n === null) continue;
        bz_clear_cookie((string)$n, $path, $domain);
    }
    // Clear session cookie name too
    $sname = session_name();
    if ($sname) {
        bz_clear_cookie($sname, $path, $domain);
    }
}

/**
 * Destroy PHP session in a robust, idempotent way.
 */
function bz_destroy_php_session(): void {
    $active = (session_status() === PHP_SESSION_ACTIVE) || (function_exists('session_id') && session_id());
    if ($active) {
        try {
            $_SESSION = [];
            @session_unset();
            @session_destroy();
        } catch (Throwable $e) {
            // ignore
        }
    }
    // Always try to clear session cookie
    $sname = session_name();
    if ($sname) {
        bz_clear_cookie($sname, '/');
    }
}