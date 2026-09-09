<?php
/*
 * QuickDate /social logout
 *
 * Responsibilities:
 * 1. Destroy QuickDate application session.
 * 2. Clear QuickDate-owned cookies.
 * 3. Destroy the PHP session.
 * 4. Preserve shared buzz_sso.
 * 5. Redirect to canonical WordPress /sso/logout.
 */

$bootstrap = __DIR__ . '/bootstrap.php';

if (file_exists($bootstrap)) {
    require_once $bootstrap;
}

$logout_common =
    __DIR__ . '/../shared/logout-common.php';

if (file_exists($logout_common)) {
    require_once $logout_common;
}

/**
 * Start an existing PHP session so its application credentials
 * can be captured before destruction.
 */
bz_ensure_session_started();

$php_session_id =
    function_exists('bz_capture_php_session_id')
        ? bz_capture_php_session_id()
        : '';

$app_session_token =
    function_exists('bz_capture_app_session_token')
        ? bz_capture_app_session_token()
        : bz_capture_session_id();

bz_logout_log(
    'quickdate',
    null,
    'logout_start',
    'initiated',
    [
        'php_session_id' =>
            $php_session_id ?: null,

        'app_session_token' =>
            $app_session_token ?: null,

        'method' =>
            $_SERVER['REQUEST_METHOD']
            ?? 'GET',
    ]
);

/**
 * -------------------------------------------------------------------------
 * QUICKDATE DATABASE CLEANUP
 * -------------------------------------------------------------------------
 */
if (
    !empty($app_session_token) &&
    isset($db)
) {

    try {

        if (
            method_exists($db, 'where') &&
            method_exists($db, 'delete')
        ) {

            /*
             * QuickDate application session.
             */
            $db
                ->where(
                    'session_id',
                    $app_session_token
                )
                ->delete(
                    'sessions'
                );

            /*
             * Remove the matching web token.
             */
            $db
                ->where(
                    'web_token',
                    $app_session_token
                )
                ->update(
                    'users',
                    [
                        'web_token' =>
                            null,

                        'web_token_created_at' =>
                            '0',

                        'web_device' =>
                            null,
                    ]
                );

            bz_logout_log(
                'quickdate',
                null,
                'db_cleanup',
                'success'
            );
        }

    } catch (Throwable $e) {

        bz_logout_log(
            'quickdate',
            null,
            'db_cleanup',
            'error',
            [
                'err' =>
                    $e->getMessage(),
            ]
        );
    }
}

/**
 * -------------------------------------------------------------------------
 * QUICKDATE-OWNED COOKIES ONLY
 * -------------------------------------------------------------------------
 *
 * DO NOT include:
 * - buzz_sso
 * - buzz_access
 * - buzz_refresh
 * - bbj_sso_ready
 *
 * Those belong to the global SSO layer and must survive this hop.
 */
bz_clear_cookies([
    'JWT',
    'quickdating',
    'verify_email',
    'verify_phone',
    'src',
    'mode',
]);

/**
 * -------------------------------------------------------------------------
 * DESTROY QUICKDATE PHP SESSION
 * -------------------------------------------------------------------------
 */
bz_destroy_php_session();

bz_logout_log(
    'quickdate',
    null,
    'logout_complete',
    'redirecting_to_wp'
);

/**
 * -------------------------------------------------------------------------
 * CANONICAL GLOBAL LOGOUT
 * -------------------------------------------------------------------------
 */
header(
    'Location: https://buzzjuice.net/sso/logout',
    true,
    302
);

exit;