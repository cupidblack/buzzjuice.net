<?php /*
@ini_set('session.cookie_httponly',1);
@ini_set('session.use_only_cookies',1);
if (!version_compare(PHP_VERSION, '7.1.0', '>=')) {
    exit("Required PHP_VERSION >= 7.1.0 , Your PHP_VERSION is : " . PHP_VERSION . "\n");
}
if (!function_exists("mysqli_connect")) {
    exit("MySQLi is required to run the application, please contact your hosting to enable php mysqli.");
}
date_default_timezone_set('UTC');

function bz_safe_session_start() {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    } catch (Throwable $e) {
        ini_set('session.gc_probability', 100);
        @session_destroy();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        @session_start();
    }
}
bz_safe_session_start();

@ini_set('gd.jpeg_ignore_warning', 1);
require_once __DIR__ . '/../../shared/palmier/palmier_logger.php';
require_once __DIR__ . '/libraries/DB/vendor/joshcam/mysqli-database-class/MySQL-Maria.php';
require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/functions_general.php';
require_once __DIR__ . '/includes/tabels.php';
require_once __DIR__ . '/includes/functions_one.php';
require_once __DIR__ . '/includes/functions_two.php';
require_once __DIR__ . '/includes/functions_three.php';

if (!isset($wo['user']) || !is_array($wo['user'])) {
    $wo['user'] = [
        'id' => 0,
        'admin' => 0
    ];
}
*/
/**
 * Buzzjuice Streams bootstrap.
 *
 * Session policy:
 * - Use one stable BUZZSTREAMSESSID cookie.
 * - Never destroy an existing PHP session because session_start() failed.
 * - Never expire the session cookie as automatic recovery.
 * - Never regenerate the session automatically here.
 * - Keep diagnostic logging in /data/logs/buzzjuice-streams.log.
 */

@ini_set('session.cookie_httponly', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.cookie_secure', '1');
@ini_set('session.cookie_samesite', 'Lax');
@ini_set('session.cookie_path', '/');
@ini_set('session.cookie_domain', '.buzzjuice.net');

if (!version_compare(PHP_VERSION, '7.1.0', '>=')) {
    exit(
        "Required PHP_VERSION >= 7.1.0, Your PHP_VERSION is: "
        . PHP_VERSION
        . PHP_EOL
    );
}

if (!function_exists('mysqli_connect')) {
    exit(
        'MySQLi is required to run the application. '
        . 'Please contact your hosting provider to enable mysqli.'
    );
}

date_default_timezone_set('UTC');

/*
 * The Streams application owns this PHP session.
 *
 * This MUST happen before session_start().
 */
if (
    function_exists('session_status') &&
    session_status() !== PHP_SESSION_ACTIVE
) {
    @session_name('BUZZSTREAMSESSID');
}

if (!defined('BZ_STREAMS_LOGGING')) {
    define('BZ_STREAMS_LOGGING', false);
}

/**
 * Central Streams diagnostic logger.
 *
 * IMPORTANT:
 * Never write raw PHP session IDs, authentication cookies,
 * JWTs, SSO secrets, hash_id values, or main_hash_id values.
 *
 * @param string $event
 * @param array  $context
 * @return void
 */
if (!function_exists('bz_streams_log')) {
    function bz_streams_log($event, $context = array())
    {
        if (!BZ_STREAMS_LOGGING) {
            return;
        }

        static $log_file = null;

        if ($log_file === null) {
            /*
             * __DIR__:
             * /buzzjuice.net/streams/assets
             *
             * Two levels up:
             * /buzzjuice.net
             */
            $root_dir = dirname(__DIR__, 2);

            $log_dir =
                $root_dir
                . DIRECTORY_SEPARATOR
                . 'data'
                . DIRECTORY_SEPARATOR
                . 'logs';

            if (!is_dir($log_dir)) {
                @mkdir($log_dir, 0750, true);
            }

            $log_file =
                $log_dir
                . DIRECTORY_SEPARATOR
                . 'buzzjuice-streams.log';
        }

        $request_id = '';

        if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            $request_id = substr(
                preg_replace(
                    '/[^a-zA-Z0-9._:-]/',
                    '',
                    (string) $_SERVER['HTTP_X_REQUEST_ID']
                ),
                0,
                128
            );
        }

        if ($request_id === '') {
            try {
                $request_id = bin2hex(random_bytes(8));
            } catch (Throwable $exception) {
                $request_id = substr(
                    hash(
                        'sha256',
                        uniqid('', true) . mt_rand()
                    ),
                    0,
                    16
                );
            }
        }

        $entry = array(
            'time' => gmdate('Y-m-d\TH:i:s\Z'),
            'event' => (string) $event,
            'request_id' => $request_id,
            'method' => isset($_SERVER['REQUEST_METHOD'])
                ? (string) $_SERVER['REQUEST_METHOD']
                : '',
            'uri' => isset($_SERVER['REQUEST_URI'])
                ? (string) $_SERVER['REQUEST_URI']
                : '',
            'script' => isset($_SERVER['SCRIPT_NAME'])
                ? (string) $_SERVER['SCRIPT_NAME']
                : '',
            'remote_ip' => isset($_SERVER['REMOTE_ADDR'])
                ? (string) $_SERVER['REMOTE_ADDR']
                : '',
            'context' => is_array($context)
                ? $context
                : array('value' => $context)
        );

        @file_put_contents(
            $log_file,
            json_encode(
                $entry,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

/**
 * Non-reversible diagnostic fingerprint.
 *
 * @param mixed $value
 * @return string
 */
if (!function_exists('bz_streams_fingerprint')) {
    function bz_streams_fingerprint($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        return substr(
            hash('sha256', (string) $value),
            0,
            16
        );
    }
}

/**
 * Start the existing PHP session without destructive recovery.
 *
 * @return bool
 */
if (!function_exists('bz_safe_session_start')) {
    function bz_safe_session_start()
    {
        if (!function_exists('session_status')) {
            bz_streams_log('session_api_unavailable');
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        $session_name = function_exists('session_name')
            ? session_name()
            : '';

        $session_id = function_exists('session_id')
            ? session_id()
            : '';

        try {
            $started = @session_start();

            if (
                $started === true &&
                session_status() === PHP_SESSION_ACTIVE
            ) {
                return true;
            }

            $last_error = error_get_last();

            bz_streams_log(
                'session_start_failed',
                array(
                    'session_name' => $session_name,
                    'session_fingerprint' =>
                        bz_streams_fingerprint($session_id),
                    'last_error' => is_array($last_error)
                        ? array(
                            'type' => isset($last_error['type'])
                                ? $last_error['type']
                                : null,
                            'message' => isset($last_error['message'])
                                ? $last_error['message']
                                : null,
                            'file' => isset($last_error['file'])
                                ? $last_error['file']
                                : null,
                            'line' => isset($last_error['line'])
                                ? $last_error['line']
                                : null
                        )
                        : null
                )
            );
        } catch (Throwable $exception) {
            bz_streams_log(
                'session_start_exception',
                array(
                    'session_name' => $session_name,
                    'session_fingerprint' =>
                        bz_streams_fingerprint($session_id),
                    'exception' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                )
            );
        }

        /*
         * Retry without destroying the existing session/cookie.
         *
         * We deliberately do NOT:
         *
         * session_destroy()
         * session_unset()
         * session_regenerate_id()
         * setcookie(...expired...)
         */
        if (session_status() !== PHP_SESSION_ACTIVE) {
            try {
                $retry_started = @session_start();

                if (
                    $retry_started === true &&
                    session_status() === PHP_SESSION_ACTIVE
                ) {
                    bz_streams_log(
                        'session_start_retry_success'
                    );

                    return true;
                }
            } catch (Throwable $exception) {
                bz_streams_log(
                    'session_start_retry_exception',
                    array(
                        'session_name' =>
                            function_exists('session_name')
                                ? session_name()
                                : '',
                        'exception' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine()
                    )
                );
            }
        }

        bz_streams_log(
            'session_unavailable_after_retry',
            array(
                'session_name' =>
                    function_exists('session_name')
                        ? session_name()
                        : ''
            )
        );

        return session_status() === PHP_SESSION_ACTIVE;
    }
}

bz_safe_session_start();

@ini_set('gd.jpeg_ignore_warning', '1');

require_once __DIR__ . '/../../shared/palmier/palmier_logger.php';

require_once
    __DIR__
    . '/libraries/DB/vendor/joshcam/mysqli-database-class/MySQL-Maria.php';

require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/functions_general.php';
require_once __DIR__ . '/includes/tabels.php';
require_once __DIR__ . '/includes/functions_one.php';
require_once __DIR__ . '/includes/functions_two.php';
require_once __DIR__ . '/includes/functions_three.php';

if (!isset($wo['user']) || !is_array($wo['user'])) {
    $wo['user'] = array(
        'id' => 0,
        'user_id' => 0,
        'admin' => 0
    );
}