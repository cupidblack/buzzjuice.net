<?php
/**
 * BuzzJuice Unified SSO Bridge Helpers (WordPress / WoWonder / QuickDate)
 *
 * - Host normalization & bridge detection
 * - Bridge loop counter (cookie-based)
 * - Base64URL encode/decode for JWT transport (RFC 7519)
 * - JWT encode & validate (HS256 stateless SSO)
 * - JTI replay prevention (file-based, pluggable directory)
 * - Logging helper
 * - Remote location fetch (cURL > get_headers > fallback)
 * - Stateless WP payload fetch & validate (JTI-protected optional)
 *
 * For use in: WordPress sso-session-sync.php, streams/ww-sso-bridge.php, social/qd-sso-bridge.php
 */

// ---------------------
// Host/Bridge Utilities
// ---------------------
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

// ---------------------
// Bridge Loop Counter
// ---------------------
if (!function_exists('bz_bridge_loop_count')) {
    function bz_bridge_loop_count($bump = false, $clear = false) {
        $name = 'bz_bridge_loop';
        if ($clear) {
            @setcookie($name, '', time() - 3600, '/');
            unset($_COOKIE[$name]);
            return 0;
        }
        $cnt = isset($_COOKIE[$name]) ? intval($_COOKIE[$name]) : 0;
        if ($bump) $cnt++;
        @setcookie($name, (string)$cnt, time() + 300, '/');
        $_COOKIE[$name] = (string)$cnt;
        return $cnt;
    }
}

// ---------------------
// Logging Helper
// ---------------------
if (!function_exists('bz_sso_bridge_log')) {
    function bz_sso_bridge_log($msg, $ctx = [], $log_file = null) {
        $file = $log_file ?: (sys_get_temp_dir() . '/ww_sso_bridge.log');
        $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $msg . ' | ' . json_encode($ctx, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND);
    }
}




// ---------------------
// JTI Replay Store Helpers (file-based, per-platform directory)
// ---------------------
if (!function_exists('bz_sso_is_jti_used')) {
    function bz_sso_is_jti_used($store_dir, $jti) {
        return $jti && file_exists($store_dir . '/' . sha1($jti));
    }
}
if (!function_exists('bz_sso_mark_jti_used')) {
    function bz_sso_mark_jti_used($store_dir, $jti) {
        @file_put_contents($store_dir . '/' . sha1($jti), time(), LOCK_EX);
    }
}
if (!function_exists('bz_sso_cleanup_jti_store')) {
    function bz_sso_cleanup_jti_store($store_dir, $ttl = 1800) {
        $expire = time() - $ttl;
        foreach (glob($store_dir . '/*') ?: [] as $file) {
            if (filemtime($file) < $expire) @unlink($file);
        }
    }
}

// ---------------------
// Robust Location Header Fetch (remote)
// ---------------------
if (!function_exists('bz_fetch_remote_location')) {
    function bz_fetch_remote_location(string $url, int $timeout = 5) {
        // cURL preferred
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $header = curl_exec($ch);
            curl_close($ch);
            if ($header !== false) {
                foreach (preg_split("/\r\n|\n|\r/", $header) as $line) {
                    if (stripos($line, 'Location:') === 0) return trim(substr($line, 9));
                }
            }
        }
        // get_headers fallback
        if (function_exists('get_headers')) {
            $headers = @get_headers($url, 1);
            if ($headers !== false && isset($headers['Location'])) {
                return is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
            }
        }
        // last resort
        @file_get_contents($url, false, stream_context_create(['http'=>['method'=>'GET','timeout'=>$timeout,'ignore_errors'=>true]]));
        if (!empty($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (stripos($h, 'Location:') === 0) return trim(substr($h, 9));
            }
        }
        return false;
    }
}

// ---------------------
// Universal: Fetch/validate stateless WP JWT payload (for bridges/clients)
// $endpoint: WP endpoint (?sso_action=get_token), $sso_token: JWT, $secret: SSO secret, $jti_store_dir: directory for JTI replay protection
// Returns array['payload'], optional ['refresh_token'] on success, or false
// ---------------------
if (!function_exists('bz_sso_fetch_wp_stateless_payload')) {
    function bz_sso_fetch_wp_stateless_payload($endpoint, $sso_token, $secret, $jti_store_dir = null) {
        $q = parse_url($endpoint, PHP_URL_QUERY);
        $url = $endpoint . ($q ? '&' : '?') . 'sso_token=' . urlencode($sso_token);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        if (!empty($_SERVER['HTTP_COOKIE'])) curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
        $result = curl_exec($ch);
        $err    = curl_errno($ch) ? ('cURL: ' . curl_error($ch)) : false;
        curl_close($ch);
        if (!$result || $err) return false;

        $json = json_decode($result, true);
        if (!is_array($json) || empty($json['token'])) return false;

        $payload = bz_sso_jwt_validate($json['token'], $secret);
        if (!$payload) return false;

        if ($jti_store_dir) {
            if (empty($payload['jti']) || bz_sso_is_jti_used($jti_store_dir, $payload['jti'])) return false;
            bz_sso_mark_jti_used($jti_store_dir, $payload['jti']);
        }

        return ['payload' => $payload, 'refresh_token' => $json['refresh_token'] ?? null];
    }
}