<?php
/**
 * BuzzJuice.net WordPress ↔ WoWonder Stateless SSO Bridge
 * Production-Grade: Stateless SSO, robust mapping, replay protection, metadata sync, auto-registration, secure redirect.
 * Supports GET → COOKIE → WP endpoint fallback, session-authoritative POST login, and safe redirects.
 */

require_once __DIR__ . '/assets/init.php';
require_once __DIR__ . '/../shared/db_helpers.php';
require_once dirname(__DIR__) . '/shared/sso_bridge_helpers.php';

/* =========================================================
   CONFIGURATION
========================================================= */
if (!defined('BUZZ_SSO_COOKIE'))          define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))       define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))           define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))      define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/ww_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER'))   define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))             define('BUZZ_SSO_TTL', 900);
if (!defined('BUZZ_SSO_MATCH_THRESHOLD')) define('BUZZ_SSO_MATCH_THRESHOLD', 2);

/* =========================================================
   UTILITY FUNCTIONS
========================================================= */
function bz_safe_session_start() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'cookie_samesite' => 'Lax'
        ]);
    }
}

function bz_is_debug() {
    return (bool)(
        (isset($_GET['sso_debug']) && $_GET['sso_debug'] === '1') ||
        (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG)
    );
}

function bz_bridge_log($msg, $ctx = []) {
    $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $msg . ' | ' . json_encode($ctx, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents(BUZZ_SSO_BRIDGE_LOG, $line, FILE_APPEND);
}

/**
 * Attempt WordPress token fallback via official endpoint.
 */
function bz_request_wp_token() {
    if (!function_exists('curl_init')) return false;
    $url = 'https://buzzjuice.net/?sso_action=get_token';
    $headers = [];
    if (!empty($_SERVER['HTTP_COOKIE'])) $headers[] = 'Cookie: ' . $_SERVER['HTTP_COOKIE'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 7
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    if (!$response) return false;
    $data = json_decode($response, true);
    return (!empty($data['token']) && is_string($data['token'])) ? trim($data['token']) : false;
}

/* =========================================================
   SESSION START & BASE URL
========================================================= */
bz_safe_session_start();
$site_base = rtrim($wo['config']['site_url'] ?? '', '/');

/* =========================================================
   ACQUIRE SSO TOKEN: GET → COOKIE → WP ENDPOINT
========================================================= */
$sso_token = $_GET['sso_token'] ?? $_COOKIE[BUZZ_SSO_COOKIE] ?? null;
if (!$sso_token) $sso_token = bz_request_wp_token();

if (!$sso_token) {
    bz_bridge_log('No SSO token available');
    header('Location: ' . $site_base . '/');
    exit;
}

/* =========================================================
   LOAD SECRET
========================================================= */
$secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);
if (!$secret || strlen($secret) < 16) {
    bz_bridge_log('BUZZ_SSO_SECRET missing/invalid', ['env'=>$secret]);
    header('Content-Type: text/plain');
    echo "Fatal: SSO secret misconfigured.";
    exit;
}

/* =========================================================
   VALIDATE TOKEN (ONCE)
========================================================= */
$claims = bz_validate_stateless_sso($sso_token, $secret);
if (!$claims || !is_array($claims)) {
    bz_bridge_log('Invalid or expired SSO token');
    header('Location: ' . $site_base . '/');
    exit;
}

/* =========================================================
   REPLAY PROTECTION (JTI)
========================================================= */
if (!empty($claims['jti'])) {
    $_SESSION['used_jti'] ??= [];
    // prune expired JTIs
    foreach ($_SESSION['used_jti'] as $hash => $ts) {
        if (time() - $ts > BUZZ_SSO_TTL) unset($_SESSION['used_jti'][$hash]);
    }
    $jti_hash = hash('sha256', $claims['jti']);
    if (!empty($_SESSION['used_jti'][$jti_hash])) {
        bz_bridge_log('Replay detected', ['jti'=>$claims['jti']]);
        header('Location: ' . $site_base . '/');
        exit;
    }
    $_SESSION['used_jti'][$jti_hash] = time();
}

/* =========================================================
   STORE CLAIMS (SESSION AUTHORITY)
========================================================= */
$_SESSION['bz_sso_claims'] = $claims;
$_SESSION['wp_user_id']    = $claims['wp_user_id'] ?? null;
$_SESSION['wp_user_email'] = $claims['wp_user_email'] ?? null;
$_SESSION['wp_user_login'] = $claims['wp_user_login'] ?? null;

/* =========================================================
   POST LOGIN HANDLER (AJAX POST)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['sso_action'] ?? '') === 'do_login') {
    header('Content-Type: application/json');

    if (empty($_SESSION['bz_sso_claims'])) {
        echo json_encode(['errors'=>['Session expired. Please retry login.']]);
        exit;
    }

    $claims = $_SESSION['bz_sso_claims'];
    $tbl    = defined('T_USERS') ? T_USERS : 'Wo_Users';

    $exp_wp    = (int)($claims['wp_user_id'] ?? 0);
    $exp_email = (string)($claims['wp_user_email'] ?? '');
    $exp_login = (string)($claims['wp_user_login'] ?? '');

    $candidates = [];

    if ($exp_wp) {
        $q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE wp_user_id={$exp_wp} LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) $candidates[] = $r;
    }
    if (!$candidates && $exp_email) {
        $esc = mysqli_real_escape_string($sqlConnect, $exp_email);
        $q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE email='{$esc}' LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) $candidates[] = $r;
    }
    if (!$candidates && $exp_login) {
        $esc = mysqli_real_escape_string($sqlConnect, $exp_login);
        $q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE username='{$esc}' LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) $candidates[] = $r;
    }

    $accepted = null;
    foreach ($candidates as $row) {
        $matches = 0;
        if ($exp_wp && (int)$row['wp_user_id'] === $exp_wp) $matches++;
        if ($exp_email && strcasecmp($row['email'], $exp_email) === 0) $matches++;
        if ($exp_login && strcasecmp($row['username'], $exp_login) === 0) $matches++;
        if ($matches >= BUZZ_SSO_MATCH_THRESHOLD) {
            $accepted = $row;
            break;
        }
    }

    if (!$accepted && BUZZ_SSO_AUTO_REGISTER && function_exists('bz_register_wo_user')) {
        $new_id = bz_register_wo_user($claims);
        if ($new_id) {
            $q = mysqli_query($sqlConnect, "SELECT * FROM {$tbl} WHERE user_id=".(int)$new_id." LIMIT 1");
            if ($q) $accepted = mysqli_fetch_assoc($q);
        }
    }

    if (!$accepted) {
        echo json_encode(['errors'=>['No matching WoWonder account (minimum '.BUZZ_SSO_MATCH_THRESHOLD.' identifiers required).']]);
        exit;
    }

    /* Metadata sync */
    if (function_exists('Wo_UpdateUserData')) {
        $update = [
            'wp_user_id' => $exp_wp,
            'email'      => $exp_email,
            'username'   => $exp_login
        ];
        if (function_exists('get_user_field_metadata')) {
            $meta = get_user_field_metadata();
            $allowed = array_merge($meta['private_secure_fields'] ?? [], $meta['public_open_fields'] ?? []);
            foreach ($claims as $field => $value) {
                if (in_array($field, $allowed, true) && !empty($value)) {
                    $update[$field] = is_string($value) ? trim($value) : $value;
                }
            }
        }
        Wo_UpdateUserData($accepted['user_id'], $update);
    }

    /* WoWonder session creation */
    $session_token = Wo_CreateLoginSession($accepted['user_id']);
    $_SESSION['user_id']     = $session_token;
    $_SESSION['wo_user_id']  = (int)$accepted['user_id'];
    $_SESSION['wp_Wo_SSO_Login'] = true;

    /* Safe redirect */
    $redirect = $site_base . '/';
    $last_url = $_POST['last_url'] ?? '';
    if ($last_url && strpos($last_url, $site_base) === 0 && !str_contains($last_url, 'ww-sso-bridge') && !str_contains($last_url, 'sso-logout')) {
        $redirect = $last_url;
    }

    echo json_encode(['status'=>200, 'location'=>$redirect]);
    exit;
}

/* =========================================================
   BRIDGE PAGE (GET)
========================================================= */
$last_url = $_GET['last_url'] ?? $_POST['last_url'] ?? $_COOKIE['last_url'] ?? $_SERVER['HTTP_REFERER'] ?? $site_base;
if (strpos($last_url, $site_base) !== 0) $last_url = $site_base . '/';

$ajax_url = ($_SERVER['PHP_SELF'] ?? '/ww-sso-bridge.php') . '?sso_action=do_login';
if (!empty($_GET['redirect_to'])) $ajax_url .= '&redirect_to=' . rawurlencode(preg_replace('/[^\w\-\/:.@]/u', '', (string)$_GET['redirect_to']));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Signing you in…</title>
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:2rem;background:#0b1020;color:#e9eef7}
.card{max-width:560px;margin:10vh auto;padding:1.5rem 1.75rem;background:#131a33;border-radius:14px}
.title{font-size:1.25rem;margin:0 0 .5rem}
.status{margin-top:1rem;padding:.75rem 1rem;border-radius:10px;background:#0e1530}
.ok{background:#10351f}
.err{background:#3a1414}
.dbg pre{background:#0e1530;padding:.75rem;border-radius:8px;overflow:auto}
</style>
</head>
<body>
<div class="card">
  <div class="title">Signing you in…</div>
  <div id="status" class="status">Preparing secure session…</div>
  <?php if (bz_is_debug()): ?>
  <div class="dbg"><pre><?php echo htmlspecialchars(print_r([
      'ajax_url'=>$ajax_url,
      'session'=>$_SESSION ?? [],
      'cookies'=>$_COOKIE
  ], true)); ?></pre></div>
  <?php endif; ?>
</div>
<script>
(function(){
    var ajaxUrl = <?php echo json_encode($ajax_url); ?>;
    var payload = { last_url: <?php echo json_encode($last_url); ?> };
    var statusEl = document.getElementById('status');
    statusEl && (statusEl.textContent = 'Contacting server…');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.withCredentials = true;
    xhr.timeout = 20000;

    xhr.onreadystatechange = function(){
        if(xhr.readyState===4){
            var ok=false,res=null,locationUrl=null,errors=null;
            try{ res = JSON.parse(xhr.responseText); }catch(e){}
            if(res){ ok = !!(res.status===200 || res.status===600) && !!res.location; locationUrl=res.location; errors=res.errors||null; }
            if(ok){ statusEl && (statusEl.className='status ok', statusEl.textContent='Welcome back! Redirecting…'); setTimeout(()=>window.location.href=locationUrl,450);}
            else{ statusEl && (statusEl.className='status err', statusEl.textContent=(errors?.join?.() || 'Unexpected response.'));}
        }
    };
    xhr.onerror = ()=>{ statusEl && (statusEl.className='status err', statusEl.textContent='Network or server error.'); };
    xhr.send('last_url=' + encodeURIComponent(payload.last_url));
})();
</script>
</body>
</html>