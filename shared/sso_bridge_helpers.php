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