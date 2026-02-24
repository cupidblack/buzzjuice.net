<?php
/**
 * WoWonder Stateless SSO Bridge for BuzzJuice.net
 * Handles SSO login/bridge from WordPress with full stateless logic.
 * - If token missing, requests it from WordPress (using browser WP login session)
 * - Validates token and maps/creates WoWonder user, writes mapping back to WordPress
 * - Complete logging for all phases, all critical flows
 */

require_once __DIR__ . '/assets/init.php';
require_once __DIR__ . '/../shared/db_helpers.php';
require_once dirname(__DIR__) . '/shared/sso_bridge_helpers.php';

define('BZ_BRIDGE_LOG', __DIR__ . '/ww_sso_bridge.log');
// Update this endpoint according to actual WP endpoint you expose:
define('WP_SSO_ENDPOINT', 'https://buzzjuice.net/?sso_action=get_token');

function bz_bridge_log($msg, $ctx = []) {
    $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $msg . ' | ' . json_encode($ctx, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents(BZ_BRIDGE_LOG, $line, FILE_APPEND);
}

if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

// =======================================================
// REQUEST SSO TOKEN FROM WORDPRESS (cookie/session pass-through)
// =======================================================
function bz_request_token_from_wp() {
    bz_bridge_log('Requesting SSO token from WordPress', []);
    $ch = curl_init(WP_SSO_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_COOKIE         => $_SERVER['HTTP_COOKIE'] ?? '',
        CURLOPT_USERAGENT      => 'BUZZJUICE/SSO-Bridge/1.0'
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        bz_bridge_log('cURL error on token fetch', ['error'=>$error]);
        return false;
    }
    $data = json_decode($response, true);
    bz_bridge_log('Token response from WP', ['body'=>$response]);
    return (isset($data['token']) && is_string($data['token'])) ? $data['token'] : false;
}

// =======================================================
// TOKEN VALIDATION (returns claims or false)
// =======================================================
function bz_validate_token($token, $secret) {
    bz_bridge_log('Validating token', ['preview'=>substr($token,0,12).'...']);
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2 || !$secret) { bz_bridge_log('Malformed token', []); return false; }
    $json = base64_decode(strtr($parts[0], '-_', '+/'));
    $sig  = base64_decode(strtr($parts[1], '-_', '+/'));
    if ($json === false || $sig === false) { bz_bridge_log('Base64 decode fail', []); return false; }
    $calc = hash_hmac('sha256', $json, $secret, true);
    if (!hash_equals($calc, $sig)) { bz_bridge_log('HMAC verify fail', ['calc'=>bin2hex($calc),'sig'=>bin2hex($sig)]); return false; }
    $claims = json_decode($json, true);
    if (!$claims || !is_array($claims)) { bz_bridge_log('Malformed payload', ['payload'=>$json]); return false; }
    if (isset($claims['exp']) && time() > intval($claims['exp'])) { bz_bridge_log('Token expired', ['exp'=>$claims['exp'],'now'=>time()]); return false; }
    bz_bridge_log('Token valid', $claims);
    return $claims;
}

// =======================================================
// MAIN SSO BRIDGE FLOW
// =======================================================
global $sqlConnect, $wo;

$secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);
if (!$secret || strlen($secret) < 16) {
    bz_bridge_log('BUZZ_SSO_SECRET invalid or missing', []);
    header('Content-Type: text/plain; charset=utf-8');
    echo "SSO FATAL: Secret not configured";
    exit;
}

$sso_token   = $_GET['sso_token'] ?? $_COOKIE['buzz_sso'] ?? null;
$redirect_to = $_GET['redirect_to'] ?? '';
$last_url    = $_GET['last_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '');

// First step: If no token, request from WP, reload with result
if (!$sso_token && $_SERVER['REQUEST_METHOD'] === 'GET') {
    bz_bridge_log('No SSO token, querying WordPress', ['uri'=>$_SERVER['REQUEST_URI']]);
    $token = bz_request_token_from_wp();
    if (!$token) {
        bz_bridge_log('Unable to get token from WordPress', []);
        header('Content-Type: text/plain; charset=utf-8');
        echo "SSO failed: could not acquire WordPress login. Please login at buzzjuice.net and try again.";
        exit;
    }
    $params = [
        'sso_token'=>$token,
        'last_url'=>$last_url,
        'redirect_to'=>$redirect_to
    ];
    $url = $_SERVER['PHP_SELF'].'?'.http_build_query(array_filter($params));
    bz_bridge_log('Reloading bridge with SSO token', ['reload'=>$url]);
    header("Location: $url");
    exit;
}

// Now: validate token (for GET or POST flow)
if (!isset($sso_token) || !is_string($sso_token)) {
    bz_bridge_log('Missing SSO token at validation phase', []);
    header('Content-Type: text/plain; charset=utf-8');
    echo "SSO failed: token missing.";
    exit;
}
$claims = bz_validate_token($sso_token, $secret);
if (!$claims) {
    bz_bridge_log('Token validation failed', []);
    header('Content-Type: text/plain; charset=utf-8');
    echo "SSO failed: Invalid or expired login token. Please login at buzzjuice.net and try again.";
    exit;
}

$wp_user_id = (int)($claims['wp_user_id'] ?? 0);
$wp_login   = (string)($claims['wp_user_login'] ?? '');
$wp_email   = (string)($claims['wp_user_email'] ?? '');

if (!$wp_user_id || !$wp_login || !$wp_email) {
    bz_bridge_log('Required WP user claims missing after token validation', $claims);
    header('Content-Type: text/plain; charset=utf-8');
    echo "SSO failed: token missing required fields.";
    exit;
}

// Find mapped WoWonder user (mapping rules)
$tbl = defined('T_USERS') ? T_USERS : 'Wo_Users';

$q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE wp_user_id={$wp_user_id} LIMIT 1");
$user = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;

if (!$user) {
    $esc_email = mysqli_real_escape_string($sqlConnect, $wp_email);
    $q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE email='{$esc_email}' LIMIT 1");
    $user = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
}
if (!$user) {
    $esc_login = mysqli_real_escape_string($sqlConnect, $wp_login);
    $q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE username='{$esc_login}' LIMIT 1");
    $user = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
}

$wo_user_id = $user ? (int)$user['user_id'] : null;

// AUTO-REGISTER WoWonder user if needed
if (!$wo_user_id) {
    bz_bridge_log('No mapped WW user, auto-registering', ['wp_user_id'=>$wp_user_id, 'login'=>$wp_login, 'email'=>$wp_email]);
    $wo_user_id = bz_register_wo_user($wp_user_id, $wp_login, $wp_email);
    if (!$wo_user_id) {
        bz_bridge_log('Auto-registration failed', []);
        header('Content-Type: text/plain; charset=utf-8');
        echo "SSO failed: cannot create account";
        exit;
    }
    $_SESSION['wo_auto_registered'] = true;
    $q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE user_id=".(int)$wo_user_id." LIMIT 1");
    $user = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;

    // Save mapping back to WP usermeta
    require_once __DIR__ . '/../shared/db_helpers.php';
    $meta_key = 'wo_user_id';
    $meta_value = (string)$wo_user_id;
    $did_write = false;
    $wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
    if ($wp_conn && function_exists('wp_update_usermeta')) {
        try { wp_update_usermeta($wp_conn, $wp_user_id, $meta_key, $meta_value); $did_write = true; bz_bridge_log('Mapped via wp_update_usermeta', []);}
        catch (Throwable $e) { bz_bridge_log('wp_update_usermeta failed', ['error'=>$e->getMessage()]); }
    }
    if (!$did_write && function_exists('update_user_meta')) {
        try { update_user_meta($wp_user_id, $meta_key, $meta_value); $did_write = true; bz_bridge_log('Mapped via update_user_meta', []);}
        catch (Throwable $e) { bz_bridge_log('update_user_meta failed', ['error'=>$e->getMessage()]); }
    }
    if (!$did_write && $wp_conn && $wp_user_id) {
        $prefix = defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_';
        $um_table_sql = (defined('WP_DB_NAME')) ? '`' . WP_DB_NAME . '`.`' . $prefix . 'usermeta`' : '`' . $prefix . 'usermeta`';
        $esc_val = mysqli_real_escape_string($wp_conn, $meta_value);
        $esc_key = mysqli_real_escape_string($wp_conn, $meta_key);
        $check_raw = "SELECT umeta_id FROM $um_table_sql WHERE user_id = " . intval($wp_user_id) . " AND meta_key = '$esc_key' LIMIT 1";
        $res = @$wp_conn->query($check_raw);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $umeta_id = intval($row['umeta_id']);
            $raw_update = "UPDATE $um_table_sql SET meta_value = '$esc_val' WHERE umeta_id = $umeta_id";
            @$wp_conn->query($raw_update);
            bz_bridge_log('Mapped with raw update', []);
        } else {
            $raw_insert = "INSERT INTO $um_table_sql (user_id, meta_key, meta_value) VALUES (" . intval($wp_user_id) . ", '$esc_key', '$esc_val')";
            @$wp_conn->query($raw_insert);
            bz_bridge_log('Mapped with raw insert', []);
        }
    }
    bz_update_wo_mapping($wo_user_id, $wp_user_id);
    if (function_exists('Wo_UpdateUserData')) {
        Wo_UpdateUserData($wo_user_id, ['wp_user_id' => $wp_user_id, 'src'=>'wp-sso']);
    }
    if (!empty($wo['config']['auto_friend_users'])) Wo_AutoFollow($wo_user_id);
    if (!empty($wo['config']['auto_page_like']))  Wo_AutoPageLike($wo_user_id);
    if (!empty($wo['config']['auto_group_join'])) Wo_AutoGroupJoin($wo_user_id);
    bz_bridge_log('Auto-registered, mapped & initialized user', ['wp_user_id'=>$wp_user_id,'wo_user_id'=>$wo_user_id]);
}

// -- Profile/meta sync (from claims)
$meta = [
    'wp_user_id'=>$wp_user_id,
    'email'=>$wp_email,
    'username'=>$wp_login
];
$metadata = function_exists('get_user_field_metadata') ? get_user_field_metadata() : [];
$private_fields = $metadata['private_secure_fields'] ?? [];
$public_fields  = $metadata['public_open_fields'] ?? [];
foreach ($claims as $field => $value) {
    if ((in_array($field, $private_fields, true) || in_array($field, $public_fields, true)) && !empty($value)) {
        $meta[$field] = is_string($value) ? trim($value) : $value;
    }
}
if (!empty($meta) && function_exists('Wo_UpdateUserData')) {
    Wo_UpdateUserData($wo_user_id, $meta);
}

// === Login local Wo session, set cookies if needed ===
$session_token = Wo_CreateLoginSession($wo_user_id);
$_SESSION['user_id'] = $session_token;
$_SESSION['wo_user_id'] = $wo_user_id;
$_SESSION['wp_Wo_SSO_Login'] = true;

// === Redirect logic ===
$site_base = rtrim($wo['config']['site_url'] ?? '', '/');
$final_url = $site_base . '/?cache=' . time();

$resolve_redirect_to = function($token) use ($site_base) {
    $token_raw = (string)$token;
    $token_safe = preg_replace('/[^\w\-\/:.\@]/u', '', $token_raw);
    if ($token_safe === '') return '';
    $mapping = ['go-pro'=>'index.php?link1=go-pro','start-up'=>'index.php?link1=start-up','home'=>'/'];
    if (isset($mapping[$token_safe])) {
        return function_exists('Wo_SeoLink') ? Wo_SeoLink($mapping[$token_safe]) : $site_base . '/' . ltrim($mapping[$token_safe], '/');
    }
    if (preg_match('#^https?://#i', $token_safe)) {
        $parts = @parse_url($token_safe);
        $site_host = parse_url($site_base, PHP_URL_HOST);
        if (!empty($parts['host']) && strcasecmp($parts['host'], $site_host) === 0) return $token_safe;
        return '';
    }
    if (strpos($token_safe, '/') === 0) return $site_base . $token_safe;
    return $site_base . '/' . ltrim($token_safe, '/');
};

if ($redirect_to) {
    $resolved = $resolve_redirect_to($redirect_to);
    if ($resolved) $final_url = $resolved;
} elseif (!empty($_SESSION['wo_auto_registered'])) {
    $final_url = function_exists('Wo_SeoLink') ? Wo_SeoLink('index.php?link1=start-up') : $site_base . '/index.php?link1=start-up';
    unset($_SESSION['wo_auto_registered']);
}

bz_bridge_log('Final user login and redirect', ['user'=>$wo_user_id, 'redirect'=>$final_url]);
header("Location: $final_url");
exit;