<?php
// Must be in your site's root, or use a WP page template with similar logic
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}
$redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : site_url('/');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Finishing Secure Login…</title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      body { font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif; background:#0b1020; color:#e9eef7; margin:0; }
      .wrap { max-width:520px; margin:10vh auto; background:#131a33; border-radius:16px; padding:2rem 2.5rem; box-shadow:0 4px 30px #0008;}
      .title { font-size:1.3rem; margin-bottom:.75rem;}
      .status { margin-top:1rem; font-size:1.05rem;}
    </style>
</head>
<body>
  <div class="wrap">
    <div class="title">Securing your BuzzJuice login…</div>
    <div id="sso-status" class="status">Please wait while we securely log you in on all platforms.</div>
  </div>
  <script>
    (function(){
      // Your bridge JS as above
      var endpoints = [
        { name: 'WoWonder', url: 'https://buzzjuice.net/streams/ww-sso-bridge.php?from_wp=1', beacon: 'https://buzzjuice.net/streams/ww-sso-bridge.php?sso_client_log=1' },
        { name: 'QuickDate', url: 'https://buzzjuice.net/social/qd-sso-bridge.php?from_wp=1', beacon: 'https://buzzjuice.net/social/qd-sso-bridge.php?sso_client_log=1' }
      ];
      var completed = {};
      var statusEl = document.getElementById('sso-status');
      var redirectTo = <?php echo json_encode($redirect_to); ?>;
      function sendBeacon(url, data) {
        try {
          var payload = JSON.stringify(data || {});
          if (navigator.sendBeacon) navigator.sendBeacon(url, payload);
          else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'text/plain');
            xhr.send(payload);
          }
        } catch (e) { console.error('Beacon failed', e); }
      }
      function logError(context, error, endpoint) {
        var errMsg = '';
        try {
          if (error instanceof Error) {
            errMsg = error.message + (error.stack ? '\n' + error.stack : '');
          } else if (typeof error === 'string') {
            errMsg = error;
          } else {
            errMsg = JSON.stringify(error);
          }
        } catch (e) { errMsg = 'Unknown error'; }
        sendBeacon(endpoint.beacon, {
          event: 'js_error',
          context: context,
          error: errMsg,
          name: endpoint.name,
          ts: Date.now(),
          url: endpoint.url
        });
        console.error('[BuzzJuice SSO][Bridge][' + endpoint.name + '][' + context + ']', errMsg);
      }
      function checkComplete() {
        if (completed['WoWonder'] && completed['QuickDate']) {
          statusEl.textContent = 'Login complete! Redirecting…';
          setTimeout(function(){
            window.location.href = redirectTo;
          }, 600);
        }
      }
      function injectBridges() {
        endpoints.forEach(function(endpoint){
          try {
            var iframe = document.createElement('iframe');
            iframe.src = endpoint.url;
            iframe.style.display = 'none';
            iframe.setAttribute('aria-hidden', 'true');
            iframe.onload = function() {
              sendBeacon(endpoint.beacon, {
                event: 'iframe_load',
                name: endpoint.name,
                ts: Date.now(),
                url: endpoint.url
              });
              completed[endpoint.name] = true;
              checkComplete();
            };
            iframe.onerror = function(e) {
              logError('iframe_onerror', e, endpoint);
              statusEl.textContent = 'Error loading SSO bridge for ' + endpoint.name + '. Try again.';
            };
            setTimeout(function(){
              if (!iframe.contentWindow || !iframe.contentDocument || iframe.contentDocument.readyState !== 'complete') {
                sendBeacon(endpoint.beacon, {
                  event: 'iframe_timeout',
                  name: endpoint.name,
                  ts: Date.now(),
                  url: endpoint.url
                });
                statusEl.textContent = 'Timeout signing in to ' + endpoint.name + '. Try again.';
              }
            }, 20000);
            document.body.appendChild(iframe);
          } catch (err) {
            logError('iframe_creation', err, endpoint);
          }
        });
        sendBeacon(endpoints[0].beacon, {event: 'bridge_injected', msg: 'Background SSO bridge iframes injected', ts: Date.now()});
        console.log('[BuzzJuice SSO] Background bridge iframes injected');
      }
      if (document.readyState === 'complete' || document.readyState === 'interactive') {
        injectBridges();
      } else {
        document.addEventListener('DOMContentLoaded', injectBridges);
      }
    })();
  </script>
</body>
</html>