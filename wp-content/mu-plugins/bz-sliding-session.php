<?php
/**
 * Plugin Name: Buzzjuice Sliding Session Expiration
 * Description: True per-session sliding WordPress authentication with stable session tokens, 60-hour normal sessions and 15-day Remember Me sessions.
 * Version: 3.0
 * Author: Buzzjuice Team
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * -------------------------------------------------------------------------
 * CONFIGURATION
 * -------------------------------------------------------------------------
 */

if (!defined('BZ_SLIDING_NORMAL_HOURS')) {
    define('BZ_SLIDING_NORMAL_HOURS', 60);
}

if (!defined('BZ_SLIDING_REMEMBER_DAYS')) {
    define('BZ_SLIDING_REMEMBER_DAYS', 15);
}

if (!defined('BZ_SLIDING_REFRESH_INTERVAL')) {
    define('BZ_SLIDING_REFRESH_INTERVAL', 5 * MINUTE_IN_SECONDS);
}

if (!defined('BZ_SLIDING_TOUCH_COOKIE')) {
    define('BZ_SLIDING_TOUCH_COOKIE', 'bz_sliding_touch');
}

if (!defined('BZ_SLIDING_META_REMEMBER')) {
    /*
     * Legacy compatibility only.
     *
     * New sessions store remember state inside the WP session token.
     */
    define(
        'BZ_SLIDING_META_REMEMBER',
        'buzzjuice_remember_me'
    );
}

if (!defined('BZ_SLIDING_SESSION_REMEMBER_KEY')) {
    define(
        'BZ_SLIDING_SESSION_REMEMBER_KEY',
        'bz_remember'
    );
}

if (!defined('BZ_SLIDING_SESSION_LAST_KEY')) {
    define(
        'BZ_SLIDING_SESSION_LAST_KEY',
        'bz_last_activity'
    );
}

/**
 * -------------------------------------------------------------------------
 * SESSION LENGTH
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sliding_normal_lifetime')) {
    function bz_sliding_normal_lifetime(): int {
        return BZ_SLIDING_NORMAL_HOURS * HOUR_IN_SECONDS;
    }
}

if (!function_exists('bz_sliding_remember_lifetime')) {
    function bz_sliding_remember_lifetime(): int {
        return BZ_SLIDING_REMEMBER_DAYS * DAY_IN_SECONDS;
    }
}

/**
 * -------------------------------------------------------------------------
 * INITIAL WORDPRESS SESSION EXPIRATION
 * -------------------------------------------------------------------------
 *
 * WordPress's auth_cookie_expiration filter is the correct control point
 * for the initial session lifetime.
 */
add_filter(
    'auth_cookie_expiration',
    function ($expiration, $user_id, $remember) {

        return $remember
            ? bz_sliding_remember_lifetime()
            : bz_sliding_normal_lifetime();

    },
    10,
    3
);

/*
 * Retain compatibility with any project code that references this filter.
 */
add_filter(
    'logged_in_cookie_expiration',
    function ($expiration, $user_id, $remember) {

        return $remember
            ? bz_sliding_remember_lifetime()
            : bz_sliding_normal_lifetime();

    },
    10,
    3
);

/**
 * -------------------------------------------------------------------------
 * DETERMINE LOGOUT REQUEST
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sliding_is_logout_request')) {
    function bz_sliding_is_logout_request(): bool {

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url(
            $uri,
            PHP_URL_PATH
        ) ?: '';

        if (
            rtrim($path, '/') === '/sso/logout'
        ) {
            return true;
        }

        if (
            isset($_GET['buzz_logout']) &&
            (string) $_GET['buzz_logout'] === '1'
        ) {
            return true;
        }

        if (
            isset($_GET['action']) &&
            (string) $_GET['action'] === 'logout'
        ) {
            return true;
        }

        return false;
    }
}

/**
 * -------------------------------------------------------------------------
 * DETERMINE CURRENT WP SESSION TOKEN
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sliding_current_token')) {
    function bz_sliding_current_token(
        ?int $user_id = null
    ): string {

        if (!function_exists('wp_get_session_token')) {
            return '';
        }

        $token = wp_get_session_token();

        return is_string($token)
            ? $token
            : '';
    }
}

/**
 * -------------------------------------------------------------------------
 * GET SESSION DATA
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sliding_get_session')) {
    function bz_sliding_get_session(
        int $user_id,
        string $token
    ): ?array {

        if (
            !$user_id ||
            $token === '' ||
            !class_exists('WP_Session_Tokens')
        ) {
            return null;
        }

        try {

            $manager = WP_Session_Tokens::get_instance(
                $user_id
            );

            $session = $manager->get($token);

            return is_array($session)
                ? $session
                : null;

        } catch (Throwable $e) {

            return null;
        }
    }
}

/**
 * -------------------------------------------------------------------------
 * DETERMINE REMEMBER-ME STATE
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sliding_get_remember_state')) {
    function bz_sliding_get_remember_state(
        int $user_id,
        string $token = '',
        ?array $session = null
    ): bool {

        if (!$user_id) {
            return false;
        }

        if ($session === null && $token !== '') {
            $session = bz_sliding_get_session(
                $user_id,
                $token
            );
        }

        /*
         * New architecture: per-session state.
         */
        if (
            is_array($session) &&
            array_key_exists(
                BZ_SLIDING_SESSION_REMEMBER_KEY,
                $session
            )
        ) {
            return !empty(
                $session[BZ_SLIDING_SESSION_REMEMBER_KEY]
            );
        }

        /*
         * Legacy fallback.
         */
        $legacy = get_user_meta(
            $user_id,
            BZ_SLIDING_META_REMEMBER,
            true
        );

        return $legacy === 'yes';
    }
}

/**
 * -------------------------------------------------------------------------
 * STORE REMEMBER-ME STATE AT LOGIN
 * -------------------------------------------------------------------------
 *
 * wp_login fires after WordPress has created the session token, allowing us
 * to attach the remember state to that specific session.
 */
add_action(
    'wp_login',
    function ($user_login, $user) {

        if (
            !$user ||
            empty($user->ID) ||
            !class_exists('WP_Session_Tokens')
        ) {
            return;
        }

        $user_id = (int) $user->ID;

        $token = bz_sliding_current_token(
            $user_id
        );

        if ($token === '') {
            return;
        }

        $manager = WP_Session_Tokens::get_instance(
            $user_id
        );

        $session = $manager->get($token);

        if (!is_array($session)) {
            return;
        }

        $remember = !empty(
            $_POST['rememberme']
        );

        $session[
            BZ_SLIDING_SESSION_REMEMBER_KEY
        ] = $remember ? 1 : 0;

        $session[
            BZ_SLIDING_SESSION_LAST_KEY
        ] = time();

        $manager->update(
            $token,
            $session
        );

        /*
         * Keep legacy state for compatibility with older SSO tokens
         * and older code paths.
         */
        update_user_meta(
            $user_id,
            BZ_SLIDING_META_REMEMBER,
            $remember ? 'yes' : 'no'
        );

    },
    20,
    2
);

/**
 * -------------------------------------------------------------------------
 * TOUCH-COOKIE THROTTLING
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sliding_touch_cookie_recent')) {
    function bz_sliding_touch_cookie_recent(): bool {

        if (
            empty($_COOKIE[BZ_SLIDING_TOUCH_COOKIE])
        ) {
            return false;
        }

        $last = (int) $_COOKIE[
            BZ_SLIDING_TOUCH_COOKIE
        ];

        if ($last <= 0) {
            return false;
        }

        return (
            time() - $last
        ) < BZ_SLIDING_REFRESH_INTERVAL;
    }
}

if (!function_exists('bz_sliding_set_touch_cookie')) {
    function bz_sliding_set_touch_cookie(): void {

        $now = time();

        $domain = defined('COOKIE_DOMAIN') &&
                  COOKIE_DOMAIN
            ? COOKIE_DOMAIN
            : '.buzzjuice.net';

        $secure = function_exists('is_ssl')
            ? is_ssl()
            : true;

        if (PHP_VERSION_ID >= 70300) {

            @setcookie(
                BZ_SLIDING_TOUCH_COOKIE,
                (string) $now,
                [
                    'expires'  => $now + BZ_SLIDING_REFRESH_INTERVAL,
                    'path'     => '/',
                    'domain'   => $domain,
                    'secure'   => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );

        } else {

            @setcookie(
                BZ_SLIDING_TOUCH_COOKIE,
                (string) $now,
                $now + BZ_SLIDING_REFRESH_INTERVAL,
                '/',
                $domain,
                $secure,
                true
            );
        }

        $_COOKIE[BZ_SLIDING_TOUCH_COOKIE] = (string) $now;
    }
}

/**
 * -------------------------------------------------------------------------
 * REFRESH THE CURRENT WP SESSION IN PLACE
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sliding_refresh_session')) {
    function bz_sliding_refresh_session(
        int $user_id,
        string $token = '',
        ?bool $remember_override = null
    ): bool {

        if (
            !$user_id ||
            !class_exists('WP_Session_Tokens') ||
            !function_exists('wp_set_auth_cookie')
        ) {
            return false;
        }

        if ($token === '') {
            $token = bz_sliding_current_token(
                $user_id
            );
        }

        if ($token === '') {
            return false;
        }

        try {

            $manager = WP_Session_Tokens::get_instance(
                $user_id
            );

            $session = $manager->get($token);

            if (
                !is_array($session) ||
                empty($session['expiration'])
            ) {
                return false;
            }

            /*
             * Do not resurrect an already-invalid session.
             */
            if (
                (int) $session['expiration'] < time()
            ) {
                return false;
            }

            $remember = $remember_override !== null
                ? (bool) $remember_override
                : bz_sliding_get_remember_state(
                    $user_id,
                    $token,
                    $session
                );

            $lifetime = $remember
                ? bz_sliding_remember_lifetime()
                : bz_sliding_normal_lifetime();

            $new_expiration = time() + $lifetime;

            $session['expiration'] = $new_expiration;

            $session[
                BZ_SLIDING_SESSION_LAST_KEY
            ] = time();

            $session[
                BZ_SLIDING_SESSION_REMEMBER_KEY
            ] = $remember ? 1 : 0;

            /*
             * CRITICAL:
             * Update the SAME session token.
             */
            $manager->update(
                $token,
                $session
            );

            /*
             * CRITICAL:
             * Reissue cookies using the SAME token.
             *
             * WordPress supports the token argument specifically for this
             * purpose.
             */
            wp_set_auth_cookie(
                $user_id,
                $remember,
                '',
                $token
            );

            bz_sliding_set_touch_cookie();

            return true;

        } catch (Throwable $e) {

            return false;
        }
    }
}

/**
 * -------------------------------------------------------------------------
 * SLIDE WHEN WORDPRESS VALIDATES A SESSION COOKIE
 * -------------------------------------------------------------------------
 *
 * This hook covers frontend, admin, REST, AJAX and normal requests.
 *
 * Unlike the previous implementation, POST requests are also valid
 * activity. A successful authenticated request is activity.
 */
add_action(
    'auth_cookie_valid',
    function ($cookie_elements, $user) {

        if (
            bz_sliding_is_logout_request()
        ) {
            return;
        }

        if (
            !empty($GLOBALS['bz_logout_in_progress'])
        ) {
            return;
        }

        if (
            !$user ||
            empty($user->ID) ||
            !is_array($cookie_elements)
        ) {
            return;
        }

        if (
            bz_sliding_touch_cookie_recent()
        ) {
            return;
        }

        $user_id = (int) $user->ID;

        $token = !empty(
            $cookie_elements['token']
        )
            ? (string) $cookie_elements['token']
            : '';

        if ($token === '') {
            return;
        }

        $session = bz_sliding_get_session(
            $user_id,
            $token
        );

        if (!is_array($session)) {
            return;
        }

        /*
         * If the token is already comfortably alive, the touch cookie
         * still prevents excessive DB operations.
         *
         * Because the touch cookie is only a performance optimization,
         * we still slide every time its interval has elapsed.
         */
        bz_sliding_refresh_session(
            $user_id,
            $token
        );

    },
    20,
    2
);

/**
 * -------------------------------------------------------------------------
 * FALLBACK ACTIVITY HOOK
 * -------------------------------------------------------------------------
 *
 * Hydrated sessions do not pass through auth_cookie_valid on the same
 * request because the cookie is created during the request. This hook
 * handles that case.
 */
add_action(
    'init',
    function () {

        if (
            bz_sliding_is_logout_request()
        ) {
            return;
        }

        if (
            !empty($GLOBALS['bz_logout_in_progress'])
        ) {
            return;
        }

        if (
            !function_exists('is_user_logged_in') ||
            !is_user_logged_in()
        ) {
            return;
        }

        if (
            bz_sliding_touch_cookie_recent()
        ) {
            return;
        }

        $user_id = (int) get_current_user_id();

        if (!$user_id) {
            return;
        }

        bz_sliding_refresh_session(
            $user_id
        );
    },
    30
);

/**
 * -------------------------------------------------------------------------
 * LOGOUT CLEANUP
 * -------------------------------------------------------------------------
 *
 * We deliberately DO NOT delete per-user remember metadata here.
 *
 * Session-specific state disappears automatically when the session token
 * is destroyed.
 *
 * The legacy user meta is retained only as a compatibility fallback for
 * older SSO tokens.
 */
add_action(
    'wp_logout',
    function ($user_id = 0) {

        $GLOBALS['bz_logout_in_progress'] = true;

        /*
         * Do not destroy or modify session metadata here.
         *
         * sso-session-sync.php is responsible for global SSO revocation.
         */
    },
    1,
    1
);

/**
 * -------------------------------------------------------------------------
 * REMEMBER-ME DEFAULT UI
 * -------------------------------------------------------------------------
 */
add_action(
    'login_form',
    function () {

        if (
            !apply_filters(
                'bz_remember_me_default_enabled',
                true
            )
        ) {
            return;
        }

        global $rememberme;

        $rememberme = true;
    },
    5
);

/**
 * -------------------------------------------------------------------------
 * REMEMBER-ME FRONTEND ENFORCEMENT
 * -------------------------------------------------------------------------
 */
add_action(
    'wp_footer',
    function () {

        if (
            is_user_logged_in()
        ) {
            return;
        }

        if (
            !apply_filters(
                'bz_remember_me_default_enabled',
                true
            )
        ) {
            return;
        }

        ?>
        <script>
        (function () {

            function enableRemember(root) {

                (root || document)
                    .querySelectorAll(
                        'input[name="rememberme"]'
                    )
                    .forEach(function (box) {

                        if (!box.checked) {
                            box.checked = true;
                        }

                    });
            }

            function initialize() {
                enableRemember(document);

                if (!document.body) {
                    return;
                }

                new MutationObserver(function (mutations) {

                    mutations.forEach(function (mutation) {

                        mutation.addedNodes.forEach(function (node) {

                            if (node.nodeType === 1) {
                                enableRemember(node);
                            }

                        });

                    });

                }).observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            if (
                document.readyState === 'loading'
            ) {
                document.addEventListener(
                    'DOMContentLoaded',
                    initialize
                );
            } else {
                initialize();
            }

        })();
        </script>
        <?php
    },
    100
);