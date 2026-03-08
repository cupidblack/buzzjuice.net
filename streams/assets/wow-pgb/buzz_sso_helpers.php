<?php
if (!function_exists('_bz_b64url_decode')) {
    function _bz_b64url_decode($str) {
        return base64_decode(strtr($str, '-_', '+/'));
    }
}
if (!function_exists('bz_sso_verify_token')) {
    function bz_sso_verify_token($token, $secret) {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return false;
        list($b64json, $b64sig) = $parts;
        $json = _bz_b64url_decode($b64json);
        $sig  = _bz_b64url_decode($b64sig);
        $calc = hash_hmac('sha256', $json, (string) $secret, true);
        if (!hash_equals($calc, $sig)) return false;
        $payload = json_decode($json, true);
        if (!is_array($payload)) return false;
        if (empty($payload['exp']) || time() > $payload['exp']) return false;
        return $payload;
    }
}