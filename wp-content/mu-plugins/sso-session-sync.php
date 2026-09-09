<?php
/*
 * BuzzJuice Enterprise Stateless SSO Authority
 *
 * Platforms:
 *   WordPress  -> /
 *   WoWonder   -> /streams
 *   QuickDate  -> /social
 *
 * Responsibilities:
 * - WP -> SSO issuance
 * - SSO -> WP hydration
 * - SSO continuity repair
 * - SSO JWT sliding refresh
 * - Global logout
 * - Logout epoch revocation
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * -------------------------------------------------------------------------
 * SAFE PHP SESSION CONFIGURATION
 * -------------------------------------------------------------------------
 */
add_action(
    'init',
    function () {

        if (
            !headers_sent() &&
            function_exists('session_status') &&
            session_status() === PHP_SESSION_NONE
        ) {
            @ini_set(
                'session.cookie_domain',
                '.buzzjuice.net'
            );
        }
    },
    0
);

/**
 * -------------------------------------------------------------------------
 * LOAD HELPERS
 * -------------------------------------------------------------------------
 */
require_once ABSPATH . '/shared/sso_bridge_helpers.php';

if (
    file_exists(
        ABSPATH . '/shared/logout-common.php'
    )
) {
    require_once ABSPATH . '/shared/logout-common.php';
}

/**
 * -------------------------------------------------------------------------
 * CONFIGURATION
 * -------------------------------------------------------------------------
 */
if (!defined('BUZZ_SSO_COOKIE')) {
    define('BUZZ_SSO_COOKIE', 'buzz_sso');
}

if (!defined('BUZZ_SSO_TTL')) {
    define('BUZZ_SSO_TTL', 1200); // 20 minutes
}

if (!defined('BUZZ_COOKIE_DOMAIN')) {
    define(
        'BUZZ_COOKIE_DOMAIN',
        '.buzzjuice.net'
    );
}

if (!defined('BUZZ_SSO_DEBUG')) {
    define('BUZZ_SSO_DEBUG', false);
}

if (!defined('BUZZ_DEBUG_LOG')) {
    define(
        'BUZZ_DEBUG_LOG',
        __DIR__ . '/wp_debug_buzz_sso.log'
    );
}

if (!defined('BUZZ_SSO_REFRESH_THRESHOLD')) {
    define(
        'BUZZ_SSO_REFRESH_THRESHOLD',
        5 * MINUTE_IN_SECONDS
    );
}

if (!defined('BUZZ_SSO_HYDRATION_ENABLED')) {
    define(
        'BUZZ_SSO_HYDRATION_ENABLED',
        true
    );
}

if (!defined('BUZZ_LOGOUT_EPOCH_META')) {
    define(
        'BUZZ_LOGOUT_EPOCH_META',
        'bbj_logout_epoch'
    );
}

$__buzz_sso_secret =
    getenv('BUZZ_SSO_SECRET')
    ?: (
        defined('BUZZ_SSO_SECRET')
            ? BUZZ_SSO_SECRET
            : null
    );

$GLOBALS['__bz_sso_secret_runtime'] =
    $__buzz_sso_secret;

/**
 * -------------------------------------------------------------------------
 * DEBUG LOGGER
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_debug_log')) {
    function bz_debug_log(
        $message,
        $extra = []
    ): void {

        if (!BUZZ_SSO_DEBUG) {
            return;
        }

        if (
            function_exists('bz_sso_bridge_log')
        ) {
            bz_sso_bridge_log(
                $message,
                $extra,
                BUZZ_DEBUG_LOG
            );
            return;
        }

        @file_put_contents(
            BUZZ_DEBUG_LOG,
            gmdate('Y-m-d H:i:s')
            . ' '
            . $message
            . ' '
            . json_encode(
                $extra,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            )
            . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

/**
 * -------------------------------------------------------------------------
 * LOGOUT REQUEST DETECTION
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sso_is_logout_request')) {
    function bz_sso_is_logout_request(): bool {

        $request_uri =
            $_SERVER['REQUEST_URI'] ?? '';

        $path = parse_url(
            $request_uri,
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

if (!function_exists('bz_sso_logout_in_progress')) {
    function bz_sso_logout_in_progress(): bool {
        return !empty(
            $GLOBALS['bz_logout_in_progress']
        );
    }
}

/**
 * -------------------------------------------------------------------------
 * LOGOUT EPOCH
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sso_mark_logout_epoch')) {
    function bz_sso_mark_logout_epoch(
        int $user_id
    ): void {

        if (!$user_id) {
            return;
        }

        /*
         * +1 prevents a token issued during the exact logout second from
         * being considered valid.
         */
        update_user_meta(
            $user_id,
            BUZZ_LOGOUT_EPOCH_META,
            time() + 1
        );
    }
}

if (!function_exists('bz_sso_token_revoked_by_epoch')) {
    function bz_sso_token_revoked_by_epoch(
        array $payload
    ): bool {

        $user_id = !empty(
            $payload['wp_user_id']
        )
            ? (int) $payload['wp_user_id']
            : 0;

        $issued_at = !empty(
            $payload['iat']
        )
            ? (int) $payload['iat']
            : 0;

        if (
            !$user_id ||
            !$issued_at
        ) {
            return true;
        }

        $logout_epoch = (int) get_user_meta(
            $user_id,
            BUZZ_LOGOUT_EPOCH_META,
            true
        );

        if (!$logout_epoch) {
            return false;
        }

        return $issued_at < $logout_epoch;
    }
}

/**
 * -------------------------------------------------------------------------
 * REMEMBER STATE
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sso_get_remember_state')) {
    function bz_sso_get_remember_state(
        int $user_id
    ): bool {

        if (!$user_id) {
            return false;
        }

        if (
            function_exists('wp_get_session_token') &&
            class_exists('WP_Session_Tokens')
        ) {

            $token = wp_get_session_token();

            if ($token !== '') {

                try {

                    $session =
                        WP_Session_Tokens::get_instance(
                            $user_id
                        )->get($token);

                    if (
                        is_array($session) &&
                        array_key_exists(
                            'bz_remember',
                            $session
                        )
                    ) {
                        return !empty(
                            $session['bz_remember']
                        );
                    }

                } catch (Throwable $e) {
                    // Continue to legacy fallback.
                }
            }
        }

        /*
         * Legacy fallback.
         */
        return (
            get_user_meta(
                $user_id,
                'buzzjuice_remember_me',
                true
            ) === 'yes'
        );
    }
}

/**
 * -------------------------------------------------------------------------
 * BUILD USER PAYLOAD
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sso_build_user_payload')) {
    function bz_sso_build_user_payload(
        $user
    ): array {

        $user_id = (int) $user->ID;

        return [
            'wp_user_id'    => $user_id,
            'wp_user_login' => (string) $user->user_login,
            'wp_user_email' => (string) $user->user_email,
            'wo_user_id'    => (string) get_user_meta(
                $user_id,
                'wo_user_id',
                true
            ),
            'qd_user_id'    => (string) get_user_meta(
                $user_id,
                'qd_user_id',
                true
            ),
            'bz_remember'   => bz_sso_get_remember_state(
                $user_id
            ) ? 1 : 0,
        ];
    }
}

/**
 * -------------------------------------------------------------------------
 * ISSUE BUZZ_SSO
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sso_issue_user_cookie')) {
    function bz_sso_issue_user_cookie(
        $user,
        string $audience = 'buzznet',
        int $ttl = BUZZ_SSO_TTL
    ): string {

        if (
            !$user ||
            empty($user->ID)
        ) {
            return '';
        }

        if (
            !in_array(
                $audience,
                ['buzznet', 'streams', 'social'],
                true
            )
        ) {
            $audience = 'buzznet';
        }

        $secret =
            $GLOBALS['__bz_sso_secret_runtime']
            ?? '';

        if ($secret === '') {
            return '';
        }

        $payload =
            bz_sso_build_user_payload(
                $user
            );

        if (
            !function_exists(
                'bz_sso_jwt_encode'
            )
        ) {
            return '';
        }

        $token = bz_sso_jwt_encode(
            $payload,
            $secret,
            $audience,
            $ttl,
            'access'
        );

        if (
            $token &&
            function_exists('bz_sso_set_cookie')
        ) {
            bz_sso_set_cookie(
                $token,
                $ttl
            );
        }

        return $token;
    }
}

/**
 * -------------------------------------------------------------------------
 * TOKEN ENDPOINT
 * -------------------------------------------------------------------------
 */
add_action(
    'init',
    function () use ($__buzz_sso_secret) {

        if (
            empty($_GET['sso_action']) ||
            $_GET['sso_action'] !== 'get_token'
        ) {
            return;
        }

        nocache_headers();

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        if (!$__buzz_sso_secret) {

            status_header(500);

            echo wp_json_encode([
                'status' => 500,
                'error'  => 'SSO secret not configured',
            ]);

            exit;
        }

        if (!is_user_logged_in()) {

            status_header(401);

            echo wp_json_encode([
                'status' => 401,
                'error'  => 'User not logged in',
            ]);

            exit;
        }

        $user = wp_get_current_user();

        if (
            !$user ||
            empty($user->ID)
        ) {

            status_header(401);

            echo wp_json_encode([
                'status' => 401,
                'error'  => 'Invalid WP session',
            ]);

            exit;
        }

        $aud =
            isset($_REQUEST['aud'])
                ? preg_replace(
                    '/[^a-zA-Z0-9_-]/',
                    '',
                    (string) $_REQUEST['aud']
                )
                : 'buzznet';

        if (
            !in_array(
                $aud,
                ['buzznet', 'streams', 'social'],
                true
            )
        ) {
            $aud = 'buzznet';
        }

        $payload =
            bz_sso_build_user_payload(
                $user
            );

        $token = bz_sso_jwt_encode(
            $payload,
            $__buzz_sso_secret,
            $aud,
            BUZZ_SSO_TTL,
            'access'
        );

        bz_debug_log(
            "Bridge token issued for aud=$aud",
            [
                'wp_user_id' =>
                    $user->ID,
                'aud' =>
                    $aud,
                'ip' =>
                    $_SERVER['REMOTE_ADDR']
                    ?? 'CLI',
            ]
        );

        status_header(200);

        echo wp_json_encode([
            'status'  => 200,
            'token'   => $token,
            'payload' => $payload,
            'exp'     => time() + BUZZ_SSO_TTL,
        ]);

        exit;
    }
);

/**
 * -------------------------------------------------------------------------
 * ISSUE ACCESS + REFRESH TOKENS
 * -------------------------------------------------------------------------
 */
add_action(
    'init',
    function () use ($__buzz_sso_secret) {

        if (
            empty($_GET['sso_action']) ||
            $_GET['sso_action'] !== 'issue_tokens'
        ) {
            return;
        }

        nocache_headers();

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        if (!$__buzz_sso_secret) {

            status_header(500);

            echo wp_json_encode([
                'status' => 500,
                'error'  => 'SSO secret not configured',
            ]);

            exit;
        }

        if (!is_user_logged_in()) {

            status_header(401);

            echo wp_json_encode([
                'status' => 401,
                'error'  => 'User not logged in',
            ]);

            exit;
        }

        $user = wp_get_current_user();

        if (
            !$user ||
            empty($user->ID)
        ) {

            status_header(401);

            echo wp_json_encode([
                'status' => 401,
                'error'  => 'Invalid WP session',
            ]);

            exit;
        }

        $aud =
            isset($_REQUEST['aud'])
                ? preg_replace(
                    '/[^a-zA-Z0-9_-]/',
                    '',
                    (string) $_REQUEST['aud']
                )
                : 'buzznet';

        if (
            !in_array(
                $aud,
                ['buzznet', 'streams', 'social'],
                true
            )
        ) {
            $aud = 'buzznet';
        }

        $payload =
            bz_sso_build_user_payload(
                $user
            );

        $access_token =
            bz_sso_jwt_encode(
                $payload,
                $__buzz_sso_secret,
                $aud,
                600,
                'access'
            );

        $refresh_token =
            bz_sso_jwt_encode(
                $payload,
                $__buzz_sso_secret,
                $aud,
                216000,
                'refresh'
            );

        /*
         * IMPORTANT:
         * Use the helper's three-argument form for named cookies.
         */
        if (function_exists('bz_sso_set_cookie')) {

            bz_sso_set_cookie(
                'buzz_access',
                $access_token,
                time() + 600
            );

            bz_sso_set_cookie(
                'buzz_refresh',
                $refresh_token,
                time() + 216000
            );
        }

        status_header(200);

        echo wp_json_encode([
            'status'  => 200,
            'access'  => $access_token,
            'refresh' => $refresh_token,
            'exp'     => time() + 600,
        ]);

        exit;
    }
);

/**
 * -------------------------------------------------------------------------
 * WP LOGIN -> ISSUE SSO
 * -------------------------------------------------------------------------
 */
add_action(
    'wp_login',
    function ($user_login, $user) use ($__buzz_sso_secret) {

        if (
            !$__buzz_sso_secret ||
            !$user ||
            empty($user->ID)
        ) {
            return;
        }

        bz_sso_issue_user_cookie(
            $user,
            'buzznet',
            BUZZ_SSO_TTL
        );
    },
    30,
    2
);

/**
 * -------------------------------------------------------------------------
 * SSO HYDRATION
 * -------------------------------------------------------------------------
 *
 * If the WP session disappears but buzz_sso remains valid, rebuild the
 * WP session instead of forcing a login.
 */
add_action(
    'init',
    function () use ($__buzz_sso_secret) {

        if (
            !BUZZ_SSO_HYDRATION_ENABLED ||
            !$__buzz_sso_secret
        ) {
            return;
        }

        if (
            bz_sso_logout_in_progress() ||
            bz_sso_is_logout_request()
        ) {
            return;
        }

        if (
            function_exists('is_user_logged_in') &&
            is_user_logged_in()
        ) {
            return;
        }

        if (
            empty($_COOKIE[BUZZ_SSO_COOKIE])
        ) {
            return;
        }

        if (
            !function_exists('bz_sso_jwt_validate')
        ) {
            return;
        }

        $payload =
            bz_sso_jwt_validate(
                $_COOKIE[BUZZ_SSO_COOKIE],
                $__buzz_sso_secret,
                'buzznet',
                'access'
            );

        if (
            !is_array($payload) ||
            empty($payload['wp_user_id']) ||
            empty($payload['iat']) ||
            empty($payload['exp'])
        ) {
            return;
        }

        if (
            bz_sso_token_revoked_by_epoch(
                $payload
            )
        ) {

            bz_debug_log(
                'SSO hydration rejected by logout epoch',
                [
                    'wp_user_id' =>
                        $payload['wp_user_id'],
                ]
            );

            return;
        }

        $user_id =
            (int) $payload['wp_user_id'];

        $user =
            get_user_by(
                'id',
                $user_id
            );

        if (
            !$user ||
            empty($user->ID)
        ) {
            return;
        }

        $remember = !empty(
            $payload['bz_remember']
        );

        /*
         * Recreate a fresh WP session.
         */
        wp_set_auth_cookie(
            $user_id,
            $remember
        );

        wp_set_current_user(
            $user_id
        );

        /*
         * Persist the remember state onto the newly-created token.
         */
        if (
            class_exists('WP_Session_Tokens') &&
            function_exists('wp_get_session_token')
        ) {

            $token =
                wp_get_session_token();

            if ($token !== '') {

                try {

                    $manager =
                        WP_Session_Tokens::get_instance(
                            $user_id
                        );

                    $session =
                        $manager->get($token);

                    if (is_array($session)) {

                        $session['bz_remember'] =
                            $remember ? 1 : 0;

                        $session['bz_last_activity'] =
                            time();

                        $manager->update(
                            $token,
                            $session
                        );
                    }

                } catch (Throwable $e) {
                    // Best effort only.
                }
            }
        }

        /*
         * Immediately refresh the SSO cookie.
         */
        bz_sso_issue_user_cookie(
            $user,
            'buzznet',
            BUZZ_SSO_TTL
        );

        $GLOBALS['wp_hydration_complete'] = true;

        bz_debug_log(
            'WP session rehydrated from valid SSO',
            [
                'wp_user_id' =>
                    $user_id,
                'remember' =>
                    $remember,
            ]
        );
    },
    12
);

/**
 * -------------------------------------------------------------------------
 * SSO SLIDING REFRESH
 * -------------------------------------------------------------------------
 */
add_action(
    'init',
    function () use ($__buzz_sso_secret) {

        if (
            !$__buzz_sso_secret ||
            bz_sso_logout_in_progress() ||
            bz_sso_is_logout_request()
        ) {
            return;
        }

        if (
            !is_user_logged_in()
        ) {
            return;
        }

        if (
            empty($_COOKIE[BUZZ_SSO_COOKIE])
        ) {
            return;
        }

        if (
            !function_exists('bz_sso_jwt_validate')
        ) {
            return;
        }

        $payload =
            bz_sso_jwt_validate(
                $_COOKIE[BUZZ_SSO_COOKIE],
                $__buzz_sso_secret,
                'buzznet',
                'access'
            );

        if (
            !is_array($payload) ||
            bz_sso_token_revoked_by_epoch(
                $payload
            )
        ) {
            return;
        }

        $exp = (int) (
            $payload['exp'] ?? 0
        );

        if (!$exp) {
            return;
        }

        if (
            ($exp - time()) >
            BUZZ_SSO_REFRESH_THRESHOLD
        ) {
            return;
        }

        $user = wp_get_current_user();

        if (
            !$user ||
            empty($user->ID)
        ) {
            return;
        }

        bz_sso_issue_user_cookie(
            $user,
            'buzznet',
            BUZZ_SSO_TTL
        );

        bz_debug_log(
            'BUZZ_SSO sliding refresh',
            [
                'wp_user_id' =>
                    $user->ID,
                'new_exp' =>
                    time() + BUZZ_SSO_TTL,
            ]
        );
    },
    25
);

/**
 * -------------------------------------------------------------------------
 * SSO CONTINUITY REPAIR
 * -------------------------------------------------------------------------
 *
 * A valid WordPress session is authoritative. If the shared SSO cookie
 * is missing or invalid, issue a new one.
 *
 * This is what prevents the user from being forced through the SSO login
 * cycle merely because buzz_sso expired while the WP session remained valid.
 */
add_action(
    'init',
    function () use ($__buzz_sso_secret) {

        if (
            !$__buzz_sso_secret ||
            bz_sso_logout_in_progress() ||
            bz_sso_is_logout_request()
        ) {
            return;
        }

        if (
            !is_user_logged_in()
        ) {
            return;
        }

        $user =
            wp_get_current_user();

        if (
            !$user ||
            empty($user->ID)
        ) {
            return;
        }

        $needs_issue = true;

        if (
            !empty($_COOKIE[BUZZ_SSO_COOKIE]) &&
            function_exists('bz_sso_jwt_validate')
        ) {

            $payload =
                bz_sso_jwt_validate(
                    $_COOKIE[BUZZ_SSO_COOKIE],
                    $__buzz_sso_secret,
                    'buzznet',
                    'access'
                );

            if (
                is_array($payload) &&
                !bz_sso_token_revoked_by_epoch(
                    $payload
                )
            ) {
                $needs_issue = false;
            }
        }

        if ($needs_issue) {

            bz_sso_issue_user_cookie(
                $user,
                'buzznet',
                BUZZ_SSO_TTL
            );

            bz_debug_log(
                'SSO continuity repaired',
                [
                    'wp_user_id' =>
                        $user->ID,
                ]
            );
        }
    },
    35
);

/**
 * -------------------------------------------------------------------------
 * GLOBAL LOGOUT
 * -------------------------------------------------------------------------
 */
if (!function_exists('bz_sso_global_logout')) {
    function bz_sso_global_logout(
        int $user_id = 0
    ): void {

        $GLOBALS['bz_logout_in_progress'] = true;

        if (
            function_exists('bz_logout_log')
        ) {
            bz_logout_log(
                'wordpress',
                $user_id ?: null,
                'global_logout',
                'initiated'
            );
        }

        /*
         * 1. Revoke every previously issued SSO JWT.
         */
        if ($user_id) {

            bz_sso_mark_logout_epoch(
                $user_id
            );

            if (
                function_exists('bz_logout_log')
            ) {
                bz_logout_log(
                    'wordpress',
                    $user_id,
                    'logout_epoch',
                    'updated'
                );
            }
        }

        /*
         * 2. Destroy every WordPress session.
         */
        if (
            $user_id &&
            class_exists('WP_Session_Tokens')
        ) {

            try {

                WP_Session_Tokens::get_instance(
                    $user_id
                )->destroy_all();

                if (
                    function_exists('bz_logout_log')
                ) {
                    bz_logout_log(
                        'wordpress',
                        $user_id,
                        'wp_session_tokens',
                        'destroyed_all'
                    );
                }

            } catch (Throwable $e) {

                if (
                    function_exists('bz_logout_log')
                ) {
                    bz_logout_log(
                        'wordpress',
                        $user_id,
                        'wp_session_tokens',
                        'error',
                        [
                            'err' =>
                                $e->getMessage(),
                        ]
                    );
                }
            }
        }

        /*
         * 3. Clear WordPress authentication cookies.
         */
        if (
            function_exists('wp_clear_auth_cookie')
        ) {
            try {
                wp_clear_auth_cookie();
            } catch (Throwable $e) {
                // Idempotent logout.
            }
        }

        /*
         * 4. Clear shared SSO credentials.
         */
        if (
            function_exists('bz_clear_cookies')
        ) {

            bz_clear_cookies([
                'buzz_sso',
                'buzz_access',
                'buzz_refresh',
                'bbj_sso_ready',
                'JWT',
            ]);

        }

        /*
         * 5. Destroy PHP application session.
         */
        if (
            function_exists('bz_destroy_php_session')
        ) {
            bz_destroy_php_session();
        }

        /*
         * 6. Remove transient SSO state.
         */
        if (
            $user_id &&
            function_exists('delete_transient')
        ) {

            delete_transient(
                'bbj_sso_ready_' . $user_id
            );

            delete_transient(
                'bbj_sso_verified_' . $user_id
            );
        }

        if (
            function_exists('bz_logout_log')
        ) {
            bz_logout_log(
                'wordpress',
                $user_id ?: null,
                'global_logout',
                'complete'
            );
        }
    }
}

/**
 * -------------------------------------------------------------------------
 * CANONICAL /sso/logout
 * -------------------------------------------------------------------------
 *
 * This endpoint is intentionally authoritative.
 *
 * It identifies the user BEFORE destroying the credentials.
 */
add_action(
    'init',
    function () use ($__buzz_sso_secret) {

        if (
            !bz_sso_is_logout_request()
        ) {
            return;
        }

        $GLOBALS['bz_logout_in_progress'] = true;

        /*
         * Identify the user BEFORE cookies/session destruction.
         */
        $user_id = 0;

        if (
            is_user_logged_in()
        ) {
            $user_id =
                (int) get_current_user_id();
        }

        /*
         * If WP session is already gone, recover from buzz_sso.
         */
        if (
            !$user_id &&
            $__buzz_sso_secret &&
            !empty($_COOKIE[BUZZ_SSO_COOKIE]) &&
            function_exists('bz_sso_jwt_validate')
        ) {

            $payload =
                bz_sso_jwt_validate(
                    $_COOKIE[BUZZ_SSO_COOKIE],
                    $__buzz_sso_secret,
                    'buzznet',
                    'access'
                );

            if (
                is_array($payload) &&
                !empty($payload['wp_user_id']) &&
                !bz_sso_token_revoked_by_epoch(
                    $payload
                )
            ) {
                $user_id =
                    (int) $payload['wp_user_id'];
            }
        }

        /*
         * Perform global destruction.
         */
        bz_sso_global_logout(
            $user_id
        );

        /*
         * Determine safe redirect.
         */
        $requested =
            !empty($_REQUEST['redirect_to'])
                ? esc_url_raw(
                    (string) $_REQUEST['redirect_to']
                )
                : home_url('/');

        $redirect =
            function_exists('wp_validate_redirect')
                ? wp_validate_redirect(
                    $requested,
                    home_url('/')
                )
                : home_url('/');

        if (
            function_exists('wp_safe_redirect')
        ) {
            wp_safe_redirect(
                $redirect
            );
        } else {
            header(
                'Location: ' . $redirect,
                true,
                302
            );
        }

        exit;
    },
    1
);

/**
 * -------------------------------------------------------------------------
 * NORMAL WORDPRESS LOGOUT -> GLOBAL LOGOUT
 * -------------------------------------------------------------------------
 *
 * wp_logout() supplies the user ID because by the time the hook executes
 * WordPress has already cleared the current user.
 */
add_action(
    'wp_logout',
    function ($user_id = 0) {

        $GLOBALS['bz_logout_in_progress'] = true;

        bz_sso_global_logout(
            (int) $user_id
        );
    },
    10,
    1
);

/**
 * -------------------------------------------------------------------------
 * WORDPRESS LOGOUT CONFIRMATION BYPASS
 * -------------------------------------------------------------------------
 */
add_action(
    'check_admin_referer',
    function ($action, $result) {

        if (
            $action !== 'log-out' ||
            isset($_GET['_wpnonce'])
        ) {
            return;
        }

        $redirect_to =
            isset($_REQUEST['redirect_to'])
                ? esc_url_raw(
                    (string) $_REQUEST['redirect_to']
                )
                : home_url('/');

        $location =
            wp_logout_url(
                $redirect_to
            );

        $location =
            str_replace(
                '&amp;',
                '&',
                $location
            );

        header(
            'Location: ' . $location,
            true,
            302
        );

        exit;
    },
    10,
    2
);

/**
 * -------------------------------------------------------------------------
 * LAST_URL COOKIE SANITIZATION
 * -------------------------------------------------------------------------
 */
add_action(
    'init',
    function () {

        if (
            empty($_COOKIE['last_url'])
        ) {
            return;
        }

        $last =
            wp_unslash(
                $_COOKIE['last_url']
            );

        $probe =
            strtolower(
                (string) $last
            );

        $markers = [
            'ww-sso-bridge.php',
            'qd-sso-bridge.php',
            'sso_action=do_login',
            'sso_client_log',
            'from_wp=1',
            '/shared/sso-logout.php',
        ];

        foreach ($markers as $marker) {

            if (
                strpos(
                    $probe,
                    $marker
                ) !== false
            ) {

                @setcookie(
                    'last_url',
                    '',
                    time() - 3600,
                    '/'
                );

                unset(
                    $_COOKIE['last_url']
                );

                bz_debug_log(
                    'Removed suspicious last_url cookie',
                    [
                        'original' => $last,
                    ]
                );

                return;
            }
        }
    },
    5
);

/**
 * -------------------------------------------------------------------------
 * SSO READY MARKER
 * -------------------------------------------------------------------------
 */
add_action(
    'init',
    function () {

        if (
            !is_user_logged_in() ||
            bz_sso_logout_in_progress()
        ) {
            return;
        }

        $user_id =
            (int) get_current_user_id();

        if (!$user_id) {
            return;
        }

        set_transient(
            'bbj_sso_ready_' . $user_id,
            1,
            120
        );

        $domain =
            defined('BUZZ_COOKIE_DOMAIN')
                ? BUZZ_COOKIE_DOMAIN
                : '.buzzjuice.net';

        $secure =
            function_exists('is_ssl')
                ? is_ssl()
                : true;

        if (PHP_VERSION_ID >= 70300) {

            @setcookie(
                'bbj_sso_ready',
                '1',
                [
                    'expires' =>
                        time() + 120,
                    'path' =>
                        '/',
                    'domain' =>
                        $domain,
                    'secure' =>
                        $secure,
                    'httponly' =>
                        true,
                    'samesite' =>
                        'Lax',
                ]
            );

        } else {

            @setcookie(
                'bbj_sso_ready',
                '1',
                time() + 120,
                '/',
                $domain,
                $secure,
                true
            );
        }

        $_COOKIE['bbj_sso_ready'] = '1';
    },
    50
);