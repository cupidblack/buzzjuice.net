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

function bz_is_debug() {
    return (
        (isset($_GET['sso_debug']) && $_GET['sso_debug'] === '1') ||
        (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG === true)
    );
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














// -----------------------------
// Unified Logging + Graceful Failure
// -----------------------------

function bz_debug_page($title, $blocks = []) {
    if (!bz_is_debug()) return;
    header('Content-Type: text/html; charset=utf-8');
    echo "<!doctype html><meta charset='utf-8'><title>SSO Bridge Debug</title>";
    echo "<style>body{font-family:system-ui;background:#0b1020;color:#e9eef7;padding:24px}
            .blk{background:#131a33;margin:16px 0;padding:12px;border-radius:10px}
            pre{white-space:pre-wrap}
        </style>";
    echo "<h2>SSO Bridge Debug — ".htmlspecialchars($title, ENT_QUOTES)."</h2>";
    $default = [
        'REQUEST' => $_REQUEST ?? [],
        'SERVER'  => [
            'HTTP_HOST'   => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null
        ]
    ];
    $blocks = array_merge($blocks, $default);
    foreach ($blocks as $k => $v) {
        echo "<div class='blk'><strong>".htmlspecialchars($k)."</strong><pre>", htmlspecialchars(print_r($v, true)), "</pre></div>";
    }
    exit;
}


if (!function_exists('bz_bridge_fail_gracefully')) {
    function bz_bridge_fail_gracefully($msg = '', $context = [], $bridge_name = 'Single Sign-on') {
        // ---- Logging ----
        $log_message = "SSO FAIL: " . $msg;
        if (function_exists('bz_bridge_log')) {
            bz_bridge_log($log_message, $context);
        } else {
            error_log('[bz_bridge] ' . $log_message . ' | ' . json_encode($context));
        }
        // ---- Safe Output ----
        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        $safe_reason = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE);
        echo '<!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="robots" content="noindex,nofollow">
            <title>' . htmlspecialchars($bridge_name, ENT_QUOTES | ENT_SUBSTITUTE) . '</title>
            <style>
                body {
                    font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
                    background: #fff;
                    color: #111;
                    padding: 28px;
                    max-width: 720px;
                    margin: 40px auto;
                }
                a { color: #0073aa; text-decoration: none; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <h2>' . htmlspecialchars($bridge_name, ENT_QUOTES | ENT_SUBSTITUTE) . ' — helper</h2>
            <p>We were unable to complete the cross-site sign-in.</p>';
        if (!empty($safe_reason)) {
            echo '<p><strong>Reason:</strong> ' . $safe_reason . '</p>';
        }
        echo '<p><a href="/">Return to the site</a></p>
        </body>
        </html>';
        exit;
    }
}





if (!function_exists('bz_validate_stateless_sso')) {
    function bz_validate_stateless_sso($token, $secret) {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || !$secret) return false;
        $json = base64_decode(strtr($parts[0], '-_', '+/'));
        $sig  = base64_decode(strtr($parts[1], '-_', '+/'));
        if ($json === false || $sig === false) return false;
        $calc = hash_hmac('sha256', $json, $secret, true);
        if (!hash_equals($calc, $sig)) return false;
        $payload = json_decode($json, true);
        if (!$payload || !is_array($payload)) return false;
        if (isset($payload['exp']) && time() > intval($payload['exp'])) return false;
        return $payload;
    }
}



// -----------------------------
// Hardened base64url decode (never null)
// -----------------------------
// ---------------------
// Base64URL Encode/Decode (RFC 7519/JWT)
// ---------------------
if (!function_exists('bz_sso_b64url_encode')) {
    function bz_sso_b64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
if (!function_exists('bz_sso_b64url_decode')) {
    function bz_sso_b64url_decode($str) {
        if ($str === null || $str === '') return '';
        $s = strtr($str, '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad) $s .= str_repeat('=', 4 - $pad);
        $out = base64_decode($s, true);
        return $out === false ? '' : $out;
    }
}
// --- JWT HELPERS ---
// ---------------------
// JWT Encode/Validate (HS256, RFC 7519)
// ---------------------
if (!function_exists('bz_sso_jwt_encode')) {
    function bz_sso_jwt_encode($payload, $secret, $aud = 'buzznet', $ttl = 1200) {
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttl;
        $payload['iss'] = 'buzzjuice.net';
        $payload['aud'] = $aud;
        $payload['jti'] = bin2hex(random_bytes(16));
        $header = ['alg'=>'HS256','typ'=>'JWT'];
        $segments = [
            bz_sso_b64url_encode(json_encode($header)),
            bz_sso_b64url_encode(json_encode($payload))
        ];
        $sig = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = bz_sso_b64url_encode($sig);
        return implode('.', $segments);
    }
}
if (!function_exists('bz_sso_jwt_validate')) {
    function bz_sso_jwt_validate($jwt, $secret, $aud = 'buzznet') {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;
        list($h, $p, $s) = $parts;
        $payload = json_decode(bz_sso_b64url_decode($p), true);
        $sig     = bz_sso_b64url_decode($s);
        $expected = hash_hmac('sha256', "$h.$p", $secret, true);
        if (!hash_equals($expected, $sig)) return false;
        if (!$payload || !isset($payload['exp']) || time() > $payload['exp']) return false;
        if ($aud && (!isset($payload['aud']) || $payload['aud'] !== $aud)) return false;
        return $payload;
    }
}

// -----------------------------
// RFC 7519 JWT validation
// -----------------------------
function bz_validate_jwt($jwt, $BUZZ_SSO_SECRET) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    list($h, $p, $s) = $parts;
    $header  = json_decode(bz_sso_b64url_decode($h), true);
    $payload = json_decode(bz_sso_b64url_decode($p), true);
    if (!$header || !$payload || ($header['alg'] ?? '') !== 'HS256') return false;
    $expected_sig = hash_hmac('sha256', "$h.$p", $BUZZ_SSO_SECRET, true);
    $actual_sig  = bz_sso_b64url_decode($s);
    if (!hash_equals($expected_sig, $actual_sig)) return false;
    $now = time();
    if (!empty($payload['nbf']) && $now < $payload['nbf']) return false;
    if (!empty($payload['exp']) && $now > $payload['exp']) return false;
    if (($payload['iss'] ?? '') !== 'buzzjuice.net') return false;
    if (($payload['aud'] ?? '') !== 'buzznet') return false;
    if (empty($payload['jti'])) return false;
    // You may want to check more claims here as needed
    return $payload;
}




// Centralized, loggable WordPress login redirect
function bz_redirect_to_wp_login($reason = 'unknown') {
    global $site_base;
    $go_pro_target = $site_base . '/ww-sso-bridge.php?redirect_to=go-pro';
    bz_bridge_log('Redirecting to WP login', ['reason' => $reason, 'wp_login_url' => $go_pro_target]);
    header('Location: https://buzzjuice.net/wp-login.php?redirect_to=' . rawurlencode($go_pro_target));
    exit;
}










// -----------------------------
// Fetch stateless payload from WP
// -----------------------------
function bz_fetch_wp_stateless_payload($sso_token, $secret) {
    $endpoint = 'https://buzzjuice.net/?sso_action=get_token';
    if (!empty($sso_token)) {
        $endpoint .= '&sso_token=' . urlencode($sso_token);
    }
    $ch = curl_init($endpoint);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ];
    if (!empty($_SERVER['HTTP_COOKIE'])) {
        $options[CURLOPT_COOKIE] = $_SERVER['HTTP_COOKIE'];
    }
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $err    = curl_errno($ch) ? ('cURL: ' . curl_error($ch)) : false;
    curl_close($ch);
    if (!$result || $err) {
        return false;
    }
    $json = json_decode($result, true);
    if (!is_array($json) || empty($json['access_token'])) {
        return false;
    }
    $payload = bz_validate_jwt($json['access_token'], $secret);
    if (!$payload) {
        return false;
    }
    if (empty($payload['jti']) || bz_is_jti_used($payload['jti'])) {
        return false;
    }
    bz_mark_jti_used($payload['jti']);
    return [
        'payload'       => $payload,
        'refresh_token' => $json['refresh_token'] ?? null
    ];
}