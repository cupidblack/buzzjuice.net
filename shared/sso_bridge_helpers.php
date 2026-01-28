<?php
// Minimal SSO bridge helpers: host normalization, bridge URL detection, loop counter, and logging.
// Place next to ww-sso-bridge.php (e.g. streams/sso_bridge_helpers.php)

if (!function_exists('bz_normalize_host')) {
    function bz_normalize_host($host) {
        if (!$host) return '';
        $h = strtolower((string)$host);
        if (strpos($h, 'www.') === 0) $h = substr($h, 4);
        return rtrim($h, ':');
    }
}

if (!function_exists('bz_is_bridge_url')) {
    function bz_is_bridge_url($candidate, $site_base = null) {
        if (empty($candidate) || !is_string($candidate)) return false;
        $candidate = trim($candidate);
        // Normalize protocol-relative and path-only
        if (strpos($candidate, '//') === 0) $candidate = 'http:' . $candidate;
        if (strpos($candidate, '/') === 0 && $site_base) $candidate = rtrim($site_base, '/') . $candidate;
        $full = strtolower($candidate);
        $markers = [
            'ww-sso-bridge.php',
            'qd-sso-bridge.php',
            'wp-login.php',
            '/shared/sso-logout.php',
            'sso_action=do_login',
            'sso_client_log',
            'from_wp=1',
            'sso_one_time',
            'buzz_sso',
            'do_login',
        ];
        foreach ($markers as $m) {
            if (strpos($full, $m) !== false) return true;
        }
        return false;
    }
}

if (!function_exists('bz_bridge_loop_count')) {
    // bump=true will increment and persist a 5-minute cookie; clear=true will remove cookie.
    function bz_bridge_loop_count($bump = false, $clear = false) {
        $name = 'bz_bridge_loop';
        if ($clear) {
            @setcookie($name, '', time() - 3600, '/');
            if (isset($_COOKIE[$name])) unset($_COOKIE[$name]);
            return 0;
        }
        $cnt = isset($_COOKIE[$name]) ? intval($_COOKIE[$name]) : 0;
        if ($bump) $cnt++;
        // persist for short time
        @setcookie($name, (string)$cnt, time() + 300, '/');
        $_COOKIE[$name] = (string)$cnt;
        return $cnt;
    }
}

if (!function_exists('bz_bridge_log')) {
    function bz_bridge_log($msg, $ctx = []) {
        $log = defined('BUZZ_SSO_BRIDGE_LOG') ? BUZZ_SSO_BRIDGE_LOG : (sys_get_temp_dir() . '/ww_sso_bridge.log');
        $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $msg . ' | ' . json_encode($ctx, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents($log, $line, FILE_APPEND);
    }
}

/**
 * Fetch remote Location header for a URL (robust).
 *
 * Returns the redirect target (string) if found, or false on error.
 * Tries:
 *  - cURL (preferred)
 *  - get_headers()
 *  - file_get_contents() + $http_response_header parsing (last resort)
 */
if (!function_exists('bz_fetch_remote_location')) {
    function bz_fetch_remote_location(string $url, int $timeout = 5) {
        // Try cURL first
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true); // we only need headers
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // do not follow; capture redirect target
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $header = curl_exec($ch);
            if ($header === false) {
                curl_close($ch);
                return false;
            }
            $redirect = false;
            $lines = preg_split("/\r\n|\n|\r/", $header);
            foreach ($lines as $line) {
                if (stripos($line, 'Location:') === 0) {
                    $redirect = trim(substr($line, strlen('Location:')));
                }
            }
            curl_close($ch);
            return $redirect ?: false;
        }

        // Next: get_headers
        if (function_exists('get_headers')) {
            $headers = @get_headers($url, 1);
            if ($headers !== false) {
                if (isset($headers['Location'])) {
                    $loc = $headers['Location'];
                    if (is_array($loc)) {
                        return end($loc);
                    }
                    return $loc;
                }
            }
        }

        // Last resort: file_get_contents() and $http_response_header parsing
        $context = stream_context_create(['http'=>['method'=>'GET','timeout'=>$timeout,'ignore_errors'=>true]]);
        @file_get_contents($url, false, $context);
        if (!empty($http_response_header) && is_array($http_response_header)) {
            $loc = false;
            foreach ($http_response_header as $h) {
                if (stripos($h, 'Location:') === 0) {
                    $loc = trim(substr($h, strlen('Location:')));
                }
            }
            return $loc ?: false;
        }
        return false;
    }
}