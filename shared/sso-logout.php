<?php
declare(strict_types=1);
// Stateless SSO logout orchestrator for BuzzJuice

if (!headers_sent()) {
    header('Expires: 0');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// WP load for BuddyBoss customized landing
$wp_bootstrap = dirname(__DIR__) . '/wp-load.php';
if (file_exists($wp_bootstrap)) require_once $wp_bootstrap;
$logout_url = function_exists('wp_logout_url')
    ? wp_logout_url(home_url())
    : 'https://buzzjuice.net/';

$streams_invalidate = 'https://buzzjuice.net/streams/logout/';
$social_invalidate  = 'https://buzzjuice.net/social/logout.php';

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Signing out…</title>
</head>
<body>
<script>
(function(){
  var endpoints = [
    <?php echo json_encode($streams_invalidate); ?>,
    <?php echo json_encode($social_invalidate); ?>
  ];
  var finalUrl = <?php echo json_encode($logout_url); ?>;

  function post(url){
    return fetch(url, {method:'POST',credentials:'include'}).catch(function(){});
  }

  Promise.all(endpoints.map(post)).finally(function(){
    try {
      document.cookie.split(';').forEach(function(c) {
        document.cookie = c.trim().split('=')[0] +
          '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;domain=.buzzjuice.net';
      });
    } catch(e){}
    window.location = finalUrl;
  });
})();
</script>
<p>Signing out…</p>
</body>
</html>