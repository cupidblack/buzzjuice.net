<?php
/*
 * WoWonder /streams logout
 *
 * Responsibilities:
 * 1. Destroy WoWonder application session.
 * 2. Clear WoWonder-owned cookies.
 * 3. Destroy PHP session.
 * 4. Preserve shared buzz_sso.
 * 5. Redirect to canonical WordPress /sso/logout.
 */

require_once __DIR__ . '/../assets/init.php';

require_once __DIR__ . '/../../shared/db_helpers.php';

$logout_common =
    __DIR__ . '/../../shared/logout-common.php';

if (file_exists($logout_common)) {
    require_once $logout_common;
}

/**
 * Start an existing PHP session.
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
    'wowonder',
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
 * WOWONDER APPLICATION SESSION CLEANUP
 * -------------------------------------------------------------------------
 */
if (
    !empty($app_session_token) &&
    !empty($sqlConnect) &&
    defined('T_APP_SESSIONS')
) {

    $sid =
        (string) $app_session_token;

    if (
        function_exists(
            'mysqli_real_escape_string'
        )
    ) {
        $sid =
            mysqli_real_escape_string(
                $sqlConnect,
                $sid
            );
    } else {
        $sid =
            addslashes($sid);
    }

    $table =
        T_APP_SESSIONS;

    @mysqli_query(
        $sqlConnect,
        "DELETE FROM `{$table}`
         WHERE `session_id` = '{$sid}'"
    );

    bz_logout_log(
        'wowonder',
        null,
        'db_session_delete',
        'attempted'
    );
}

/**
 * -------------------------------------------------------------------------
 * WOWONDER-OWNED COOKIES ONLY
 * -------------------------------------------------------------------------
 *
 * DO NOT clear buzz_sso here.
 */
bz_clear_cookies([
    'user_id',
    'switched_accounts',
]);

/**
 * -------------------------------------------------------------------------
 * DESTROY PHP SESSION
 * -------------------------------------------------------------------------
 */
bz_destroy_php_session();

bz_logout_log(
    'wowonder',
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