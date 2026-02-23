<?php
/**
 * sso-landing.php - Stateless SSO fan-out orchestrator
 * Works with stateless sso-session-sync.php and bridges.
 */

if (!function_exists('is_user_logged_in')) {
    header('Location: /');
    exit;
}
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

if (!defined('BUZZ_SSO_COOKIE'))    define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_SSO_TTL'))       define('BUZZ_SSO_TTL', 900);
if (!defined('BUZZ_COOKIE_DOMAIN')) define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');

// Lookup secret (from env or define)
$secret = getenv('BUZZ_SSO_SECRET') ?: (defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : null);
if (!$secret) {
    wp_die('BuzzJuice SSO misconfiguration: secret missing.');
}

$redirect_to = site_url('/');
if (!empty($_GET['redirect_to']) && is_string($_GET['redirect_to'])) {
    $redirect_to = esc_url_raw(wp_unslash($_GET['redirect_to'])) ?: site_url('/');
}

$token = '';
if (!empty($_GET['token']) && is_string($_GET['token'])) {
    $token = trim(wp_unslash($_GET['token']));
} elseif (!empty($_GET['sso_token']) && is_string($_GET['sso_token'])) {
    $token = trim(wp_unslash($_GET['sso_token']));
}
if (!$token) wp_die('No SSO token provided.');

function bz_validate_token($token, $secret) {
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return false;
    $json = base64_decode(strtr($parts[0], '-_', '+/'));
    $sig  = base64_decode(strtr($parts[1], '-_', '+/'));
    if ($json === false || $sig === false) return false;
    $calc = hash_hmac('sha256', $json, (string)$secret, true);
    if (!hash_equals($calc, $sig)) return false;
    $payload = json_decode($json, true);
    if (!is_array($payload)) return false;
    if (isset($payload['exp']) && time() > intval($payload['exp'])) return false;
    return $payload;
}

$payload = bz_validate_token($token, $secret);
if (!$payload) wp_die('Invalid or expired SSO token.');

$current_user = wp_get_current_user();
if (!empty($payload['wp_user_id']) && (int)$current_user->ID !== (int)$payload['wp_user_id']) {
    wp_die('SSO token user mismatch.');
}

setcookie(BUZZ_SSO_COOKIE, $token, [
    'expires'  => time() + BUZZ_SSO_TTL,
    'path'     => '/',
    'domain'   => BUZZ_COOKIE_DOMAIN,
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
$_COOKIE[BUZZ_SSO_COOKIE] = $token; // For current request if needed

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Finishing Secure Login…</title>
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>/* ...keep your CSS content here... */</style>
</head>
<body>
<div class="wrap" role="main" aria-labelledby="sso-title">
  <div id="sso-title" class="title">Securing your BuzzJuice login…</div>
  <div id="sso-status" class="status">Signing you in across integrated platforms.</div>
  <div id="sso-actions" class="actions" aria-hidden="true">
    <a href="#" id="retry-btn" class="btn alt">Retry</a>
    <a href="<?php echo esc_attr($redirect_to); ?>" id="continue-btn" class="btn">Continue</a>
  </div>
  <div class="note">This happens in the background. You can continue to the site at any time.</div>
</div>
<script>
(function(){
  var endpoints = [
    { name: 'WoWonder',  url: 'https://buzzjuice.net/streams/ww-sso-bridge.php?from_wp=1' },
    { name: 'QuickDate', url: 'https://buzzjuice.net/social/qd-sso-bridge.php?from_wp=1' }
  ];
  var redirectTo = <?php echo json_encode($redirect_to); ?>;
  var token = <?php echo json_encode($token); ?>;
  function attachParams(url) {
    var sep = (url.indexOf('?') === -1) ? '?' : '&';
    return url + sep + 'sso_action=do_login&token=' + encodeURIComponent(token);
  }
  endpoints.forEach(function(ep){
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = attachParams(ep.url);
    document.body.appendChild(iframe);
  });
  setTimeout(function(){
    window.location = redirectTo;
  }, 800);
})();
</script>
</body>
</html>