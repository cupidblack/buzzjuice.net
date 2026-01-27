<?php
/**
 * sso-landing.php
 *
 * Deterministic SSO fan-out orchestrator.
 * Works in lockstep with wp-content/mu-plugins/sso-session-sync.php
 *
 * Key rules:
 * - Must not create/modify PHP sessions
 * - Must only be visited by authenticated WP users
 * - Accepts one-time token (token OR sso_token) and forwards it to bridges
 * - Stateless, bounded retries, and best-effort beaconing for telemetry
 */

if (!function_exists('is_user_logged_in')) {
    $wp = __DIR__ . '/wp-load.php';
    if (is_file($wp)) {
        // Boot WP so we can check auth; silent fallback if missing
        require_once $wp;
    }
}

// If WP bootstrap wasn't available, redirect to root (safe fallback)
if (!function_exists('is_user_logged_in')) {
    header('Location: /');
    exit;
}

// Only allow authenticated users here.
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

/* ---------------- Sanitization ---------------- */

$redirect_to = site_url('/');
if (!empty($_GET['redirect_to']) && is_string($_GET['redirect_to'])) {
    $redirect_to = esc_url_raw(wp_unslash($_GET['redirect_to'])) ?: site_url('/');
}

// Accept either 'token' or 'sso_token' (normalize to $token)
$token = '';
if (!empty($_GET['token']) && is_string($_GET['token'])) {
    $token = trim(wp_unslash($_GET['token']));
} elseif (!empty($_GET['sso_token']) && is_string($_GET['sso_token'])) {
    $token = trim(wp_unslash($_GET['sso_token']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Finishing Secure Login…</title>
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif; background:#0b1020; color:#e9eef7; margin:0; }
.wrap { max-width:620px; margin:8vh auto; background:#131a33; border-radius:14px; padding:1.75rem; box-shadow:0 6px 36px rgba(0,0,0,.6); }
.title { font-size:1.3rem; margin-bottom:.6rem; }
.status { font-size:1.05rem; margin-top:.75rem; color:#cfe0ff; }
.actions { margin-top:1.2rem; display:none; }
.btn { display:inline-block; padding:.55rem 1rem; border-radius:8px; background:#2b6cff; color:#fff; text-decoration:none; }
.btn.alt { background:#445; margin-right:.5rem; }
.note { margin-top:.8rem; font-size:.9rem; color:#a9b6d8; }
</style>
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
  // Keep endpoints in sync with your bridges; same-origin endpoints assumed for beaconing.
  var endpoints = [
    { name: 'WoWonder',  url: 'https://buzzjuice.net/streams/ww-sso-bridge.php?from_wp=1', beacon: 'https://buzzjuice.net/streams/ww-sso-bridge.php?sso_client_log=1' },
    { name: 'QuickDate', url: 'https://buzzjuice.net/social/qd-sso-bridge.php?from_wp=1',  beacon: 'https://buzzjuice.net/social/qd-sso-bridge.php?sso_client_log=1' }
  ];

  var MAX_ATTEMPTS = 3;
  var TIMEOUT_MS = 20000; // per iframe attempt
  var completed = {};     // endpointName => true
  var attempts = {};      // endpointName => number

  var statusEl = document.getElementById('sso-status');
  var actionsEl = document.getElementById('sso-actions');
  var retryBtn = document.getElementById('retry-btn');
  var redirectTo = <?php echo json_encode($redirect_to); ?>;
  var token = <?php echo json_encode($token); ?>;

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
    } catch (e) {
      // best-effort only
      console && console.debug && console.debug('beacon send failed', e);
    }
  }

  // Attach required query params and forward both token names for bridge compatibility
  function attachParams(url) {
    var sep = (url.indexOf('?') === -1) ? '?' : '&';
    var s = url + sep + 'sso_action=do_login' + '&from_wp=1';
    if (token) {
      s += '&token=' + encodeURIComponent(token) + '&sso_token=' + encodeURIComponent(token);
    }
    return s;
  }

  function checkAllComplete() {
    for (var i = 0; i < endpoints.length; i++) {
      if (!completed[endpoints[i].name]) return false;
    }
    statusEl.textContent = 'Login complete — redirecting…';
    setTimeout(function () { window.location.href = redirectTo; }, 600);
    return true;
  }

  function createIframeFor(endpoint) {
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.src = attachParams(endpoint.url);

    var timedOut = false;
    var t = setTimeout(function () {
      timedOut = true;
      sendBeacon(endpoint.beacon, { event: 'iframe_timeout', name: endpoint.name, ts: Date.now(), url: endpoint.url });
      statusEl.textContent = 'Timeout signing in to ' + endpoint.name + '.';
      attempts[endpoint.name] = (attempts[endpoint.name] || 0) + 1;
      if ((attempts[endpoint.name] || 0) < MAX_ATTEMPTS) {
        setTimeout(function () { tryInjectEndpoint(endpoint); }, 800 + (attempts[endpoint.name] * 300));
      } else {
        actionsEl.style.display = 'block';
        actionsEl.setAttribute('aria-hidden', 'false');
      }
    }, TIMEOUT_MS);

    iframe.onload = function () {
      if (timedOut) return; // ignore late onload after timeout
      clearTimeout(t);
      completed[endpoint.name] = true;
      statusEl.textContent = 'Signed in to ' + endpoint.name + '.';
      sendBeacon(endpoint.beacon, { event: 'iframe_load', name: endpoint.name, ts: Date.now(), url: endpoint.url });
      checkAllComplete();
    };

    iframe.onerror = function (e) {
      clearTimeout(t);
      sendBeacon(endpoint.beacon, { event: 'iframe_error', name: endpoint.name, ts: Date.now(), url: endpoint.url });
      attempts[endpoint.name] = (attempts[endpoint.name] || 0) + 1;
      if (attempts[endpoint.name] < MAX_ATTEMPTS) {
        setTimeout(function () { tryInjectEndpoint(endpoint); }, 700);
      } else {
        statusEl.textContent = 'Could not sign in to ' + endpoint.name + '.';
        actionsEl.style.display = 'block';
        actionsEl.setAttribute('aria-hidden', 'false');
      }
    };

    return iframe;
  }

  function tryInjectEndpoint(endpoint) {
    if (completed[endpoint.name]) return;
    attempts[endpoint.name] = attempts[endpoint.name] || 0;
    if (attempts[endpoint.name] >= MAX_ATTEMPTS) return;
    attempts[endpoint.name]++;

    try {
      var ifr = createIframeFor(endpoint);
      document.body.appendChild(ifr);
    } catch (err) {
      sendBeacon(endpoint.beacon, { event: 'iframe_creation_error', name: endpoint.name, ts: Date.now(), error: err && err.message ? err.message : String(err) });
      attempts[endpoint.name] = (attempts[endpoint.name] || 0) + 1;
      if (attempts[endpoint.name] >= MAX_ATTEMPTS) {
        actionsEl.style.display = 'block';
        actionsEl.setAttribute('aria-hidden', 'false');
      } else {
        setTimeout(function () { tryInjectEndpoint(endpoint); }, 600);
      }
    }
  }

  function injectAll() {
    // initial beacon for observability
    if (endpoints && endpoints.length) {
      sendBeacon(endpoints[0].beacon, { event: 'bridge_injected', ts: Date.now(), token_present: !!token });
    }
    for (var i = 0; i < endpoints.length; i++) {
      tryInjectEndpoint(endpoints[i]);
    }

    // If nothing completes quickly, show Continue/Retry control.
    setTimeout(function () {
      var anyCompleted = Object.keys(completed).length > 0;
      if (!anyCompleted) {
        actionsEl.style.display = 'block';
        actionsEl.setAttribute('aria-hidden', 'false');
      }
    }, 7000);
  }

  retryBtn.addEventListener('click', function (ev) {
    ev && ev.preventDefault();
    actionsEl.style.display = 'none';
    actionsEl.setAttribute('aria-hidden', 'true');
    statusEl.textContent = 'Retrying background SSO…';
    // reset attempts for incomplete endpoints
    for (var i = 0; i < endpoints.length; i++) {
      if (!completed[endpoints[i].name]) attempts[endpoints[i].name] = 0;
    }
    injectAll();
  }, false);

  // global error reporting — best-effort send to each beacon
  window.addEventListener('error', function (e) {
    endpoints.forEach(function (ep) { sendBeacon(ep.beacon, { event: 'js_error', ts: Date.now(), error: e && e.message ? e.message : String(e) }); });
  });
  window.addEventListener('unhandledrejection', function (e) {
    endpoints.forEach(function (ep) { sendBeacon(ep.beacon, { event: 'js_unhandled_rejection', ts: Date.now(), error: e && e.reason ? (e.reason.message || String(e.reason)) : String(e) }); });
  });

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    injectAll();
  } else {
    document.addEventListener('DOMContentLoaded', injectAll);
  }
})();
</script>
</body>
</html>