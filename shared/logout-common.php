<?php
declare(strict_types=1);

/*
 * shared/logout-common.php
 *
 * BuzzJuice neutral logout/session helpers.
 *
 * IMPORTANT:
 * This file must NOT contain application-specific logout logic.
 *
 * Responsibilities:
 * - Structured logout logging
 * - PHP-session detection/start
 * - PHP-session ID capture
 * - Application-session token capture
 * - Safe cookie clearing
 * - PHP-session destruction
 *
 * It intentionally does NOT clear PHP session cookies from
 * bz_clear_cookies(). PHP session destruction is explicit through
 * bz_destroy_php_session().
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
 * -------------------------------------------------------------------------
 * STRUCTURED LOGGING
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_logout_log')) {
    function bz_logout_log(
        $app,
        $user_id = null,
        $event = '',
        $status = 'info',
        $extra = []
    ): void {
        $entry = [
            'ts'     => gmdate('Y-m-d H:i:s'),
            'app'    => (string) $app,
            'user'   => $user_id !== null ? $user_id : null,
            'event'  => (string) $event,
            'status' => (string) $status,
            'remote' => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri'    => $_SERVER['REQUEST_URI'] ?? null,
        ];

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $entry['ua'] = substr(
                (string) $_SERVER['HTTP_USER_AGENT'],
                0,
                200
            );
        }

        if (!empty($extra)) {
            $entry['extra'] = $extra;
        }

        @file_put_contents(
            BZ_LOGOUT_LOG,
            json_encode(
                $entry,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

/**
 * -------------------------------------------------------------------------
 * SSL DETECTION
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_is_ssl')) {
    function bz_is_ssl(): bool {
        if (function_exists('is_ssl')) {
            return (bool) is_ssl();
        }

        if (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
        ) {
            return true;
        }

        if (
            !empty($_SERVER['HTTPS']) &&
            strtolower((string) $_SERVER['HTTPS']) !== 'off'
        ) {
            return true;
        }

        return (
            !empty($_SERVER['SERVER_PORT']) &&
            (int) $_SERVER['SERVER_PORT'] === 443
        );
    }
}

/**
 * -------------------------------------------------------------------------
 * PHP SESSION HELPERS
 * -------------------------------------------------------------------------
 */

/**
 * Start a PHP session only when one already appears to exist.
 *
 * This avoids creating a brand-new anonymous PHP session during logout.
 */
if (!function_exists('bz_ensure_session_started')) {
    function bz_ensure_session_started(): bool {
        if (!function_exists('session_status')) {
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        if (session_status() !== PHP_SESSION_NONE) {
            return false;
        }

        $session_name = function_exists('session_name')
            ? session_name()
            : 'PHPSESSID';

        $has_cookie = false;

        if (
            $session_name !== '' &&
            !empty($_COOKIE[$session_name])
        ) {
            $has_cookie = true;
        }

        if (!empty($_COOKIE['PHPSESSID'])) {
            $has_cookie = true;
        }

        if (!$has_cookie) {
            return false;
        }

        @session_start();

        return session_status() === PHP_SESSION_ACTIVE;
    }
}

/**
 * Capture the actual PHP session identifier.
 */
if (!function_exists('bz_capture_php_session_id')) {
    function bz_capture_php_session_id(): string {
        if (
            function_exists('session_status') &&
            session_status() === PHP_SESSION_ACTIVE &&
            function_exists('session_id')
        ) {
            $sid = (string) session_id();

            if ($sid !== '') {
                return $sid;
            }
        }

        $session_name = function_exists('session_name')
            ? session_name()
            : '';

        if (
            $session_name !== '' &&
            !empty($_COOKIE[$session_name])
        ) {
            return (string) $_COOKIE[$session_name];
        }

        if (!empty($_COOKIE['PHPSESSID'])) {
            return (string) $_COOKIE['PHPSESSID'];
        }

        return '';
    }
}

/**
 * Capture the application's authentication/session credential.
 *
 * Priority:
 * 1. JWT cookie
 * 2. Session JWT
 * 3. Explicit application token
 * 4. PHP session ID
 *
 * This prevents QuickDate's numeric user_id session value from being
 * incorrectly treated as its database session_id when JWT exists.
 */
if (!function_exists('bz_capture_app_session_token')) {
    function bz_capture_app_session_token(): string {

        if (
            !empty($_COOKIE['JWT']) &&
            is_scalar($_COOKIE['JWT'])
        ) {
            return (string) $_COOKIE['JWT'];
        }

        if (
            function_exists('session_status') &&
            session_status() === PHP_SESSION_ACTIVE
        ) {
            foreach (
                ['JWT', 'web_token', 'session_token', 'access_token']
                as $key
            ) {
                if (
                    isset($_SESSION[$key]) &&
                    is_scalar($_SESSION[$key]) &&
                    (string) $_SESSION[$key] !== ''
                ) {
                    return (string) $_SESSION[$key];
                }
            }
        }

        if (
            !empty($_POST['access_token']) &&
            is_scalar($_POST['access_token'])
        ) {
            return (string) $_POST['access_token'];
        }

        return bz_capture_php_session_id();
    }
}

/**
 * Backward-compatible alias.
 *
 * Existing application code may call this function.
 */
if (!function_exists('bz_capture_session_id')) {
    function bz_capture_session_id(): string {
        return bz_capture_app_session_token();
    }
}

/**
 * Capture best-effort numeric application user ID.
 */
if (!function_exists('bz_capture_user_id')) {
    function bz_capture_user_id(): int {

        if (
            function_exists('session_status') &&
            session_status() === PHP_SESSION_ACTIVE &&
            !empty($_SESSION['user_id']) &&
            is_numeric($_SESSION['user_id'])
        ) {
            return (int) $_SESSION['user_id'];
        }

        if (
            !empty($_COOKIE['user_id']) &&
            is_numeric($_COOKIE['user_id'])
        ) {
            return (int) $_COOKIE['user_id'];
        }

        return 0;
    }
}

/**
 * -------------------------------------------------------------------------
 * COOKIE CLEARING
 * -------------------------------------------------------------------------
 */

if (!function_exists('bz_clear_cookie')) {
    function bz_clear_cookie(
        string $name,
        string $path = '/',
        ?string $domain = null
    ): void {

        if ($name === '') {
            return;
        }

        $domain = $domain ?? BZ_LOGOUT_COOKIE_DOMAIN;
        $expiry = time() - 3600;
        $secure = bz_is_ssl();

        if (PHP_VERSION_ID >= 70300) {

            $options = [
                'expires'  => $expiry,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ];

            @setcookie($name, '', $options);

            /*
             * Also clear a host-only variant.
             */
            $options['domain'] = '';
            @setcookie($name, '', $options);

        } else {

            @setcookie(
                $name,
                '',
                $expiry,
                $path,
                $domain,
                $secure,
                true
            );

            @setcookie(
                $name,
                '',
                $expiry,
                $path,
                '',
                $secure,
                true
            );
        }

        unset($_COOKIE[$name]);
    }
}

if (!function_exists('bz_clear_cookies')) {
    function bz_clear_cookies(
        array $names,
        string $path = '/',
        ?string $domain = null
    ): void {

        foreach ($names as $name) {

            if ($name === null || $name === '') {
                continue;
            }

            bz_clear_cookie(
                (string) $name,
                $path,
                $domain
            );
        }

        /*
         * Deliberately DO NOT clear the PHP session cookie here.
         *
         * PHP session destruction is handled separately by:
         * bz_destroy_php_session()
         */
    }
}

/**
 * -------------------------------------------------------------------------
 * PHP SESSION DESTRUCTION
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_destroy_php_session')) {
    function bz_destroy_php_session(): void {

        $session_name = function_exists('session_name')
            ? session_name()
            : 'PHPSESSID';

        $active = false;

        if (function_exists('session_status')) {
            $active = (
                session_status() === PHP_SESSION_ACTIVE
            );
        }

        if ($active) {

            try {
                $_SESSION = [];

                if (function_exists('session_unset')) {
                    @session_unset();
                }

                @session_destroy();

            } catch (Throwable $e) {
                // Logout must remain idempotent.
            }
        }

        /*
         * Clear the configured session cookie.
         */
        if ($session_name !== '') {
            bz_clear_cookie(
                $session_name,
                '/'
            );
        }

        /*
         * Legacy PHPSESSID fallback.
         */
        if ($session_name !== 'PHPSESSID') {
            bz_clear_cookie(
                'PHPSESSID',
                '/'
            );
        }
    }
}