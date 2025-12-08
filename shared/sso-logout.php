<?php
declare(strict_types=1);
// Central SSO logout proxy (client-side orchestrator + fallback chain).
// Now issues WP logout URLs with fresh _wpnonce (no buzz_sso_secret required).

if (!headers_sent()) {
    header('Expires: 0');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

$LOG = __DIR__ . '/sso-logout-debug.log';
function sso_log($msg, $ctx = []) {
    global $LOG;
    $meta = ['ts'=>gmdate('Y-m-d H:i:s'), 'remote'=>$_SERVER['REMOTE_ADDR'] ?? null, 'uri'=>$_SERVER['REQUEST_URI'] ?? null, 'request'=>$_REQUEST ?? null];
    if ($ctx) $meta['ctx'] = $ctx;
    @file_put_contents($LOG, "[$meta[ts]] $msg | " . json_encode($meta, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

// Load WordPress core for nonce
$wp_bootstrap = dirname(__DIR__) . '/wp-load.php';
if (file_exists($wp_bootstrap)) {
    require_once $wp_bootstrap;
}

function issue_wp_logout_url() {
    // Use WordPress's core logout URL helper with nonce
    if (function_exists('wp_logout_url')) {
        $url = wp_logout_url('https://buzzjuice.net/');
        return $url;
    }
    // Fallback: static logout URL
    return 'https://buzzjuice.net/wp-login.php?action=logout';
}

// --- Fallback GET-based orchestrator chain ---
if (isset($_GET['cabin']) && $_GET['cabin'] === 'home') {
    sso_log('orchestrator: WP→WW(cabin=home)', []);
    header('Location: https://buzzjuice.net/streams/logout/?cabin=home');
    exit();
}
if (isset($_GET['cache'])) {
    sso_log('orchestrator: WW→QD(cache)', []);
    header('Location: https://buzzjuice.net/social/logout.php?cache=' . urlencode($_GET['cache']));
    exit();
}
if (isset($_GET['social']) && $_GET['social'] === 'home') {
    sso_log('orchestrator: QD→WW(social=home)', []);
    header('Location: https://buzzjuice.net/streams/logout/?social=home');
    exit();
}

// On final return, issue WP logout URL and redirect
if (isset($_GET['wp_final_logout'])) {
    $logout_url = issue_wp_logout_url();
    sso_log('orchestrator: final WP logout (_wpnonce)', []);
    header("Location: $logout_url");
    exit();
}

// Default: serve JS orchestrator (background POST invalidate) — no secret required
$streams_invalidate = 'https://buzzjuice.net/streams/logout/';
$social_invalidate  = 'https://buzzjuice.net/social/logout.php';

$home_js   = json_encode('https://buzzjuice.net/?logged_out=1');
$end1_js   = json_encode($streams_invalidate);
$end2_js   = json_encode($social_invalidate);
$timeoutMs = 8000;

// Serve background POST invalidate JS page
echo '<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0"><meta http-equiv="Pragma" content="no-cache"><title>Signing out…</title></head><body>';
echo '<script>(function(){';
echo "var home = {$home_js};";
echo "var endpoints = [{$end1_js}, {$end2_js}];";
echo "var timeoutMs = " . intval($timeoutMs) . ";";
echo "function delay(ms){return new Promise(function(r){setTimeout(r,ms);});}";
echo "function postInvalidateWithRetry(url){return new Promise(function(resolve){var attempts=0;var maxAttempts=2;function attempt(){attempts++;fetch(url,{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({invalidate:1})}).then(function(resp){ if(!resp||!resp.ok) { if(attempts<maxAttempts){return delay(300).then(attempt);} return resolve({ok:false,status:resp?resp.status:0}); } resp.json().then(function(j){ resolve({ok:true,json:j}); }).catch(function(){ resolve({ok:true,json:null}); }); }).catch(function(err){ if(attempts<maxAttempts){ return delay(300).then(attempt);} resolve({ok:false,err:String(err)}); }); } attempt(); }); }";
echo "function clearClientAndGoHome(){try{(function(domain){try{var cookies=(document.cookie||'').split('; ');for(var i=0;i<cookies.length;i++){var n=cookies[i].split('=')[0];if(!n) continue;try{document.cookie=n+'=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;domain='+domain+';';}catch(e){}try{document.cookie=n+'=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;';}catch(e){}}}catch(e){} })('.buzzjuice.net'); if('caches' in window && caches.keys) caches.keys().then(function(names){names.forEach(function(n){try{caches.delete(n);}catch(_){} });}).catch(function(){}); if('serviceWorker' in navigator && navigator.serviceWorker.getRegistrations) navigator.serviceWorker.getRegistrations().then(function(rs){rs.forEach(function(r){try{r.unregister();}catch(_){} });}).catch(function(){}); try{ if(window.localStorage) localStorage.clear(); }catch(e){} try{ if(window.sessionStorage) sessionStorage.clear(); }catch(e){} try{ if(window.indexedDB && indexedDB.databases) indexedDB.databases().then(function(dbs){dbs.forEach(function(db){try{indexedDB.deleteDatabase(db.name);}catch(_){} });}).catch(function(){}); }catch(e){} }catch(e){} try{ window.location.replace(home);}catch(e){window.location.href=home;} setTimeout(function(){try{window.location.href=home;}catch(e){}},350); window.onpageshow=function(ev){if(ev&&ev.persisted){try{window.location.replace(home);}catch(e){}}}; }";
echo "var ps = endpoints.map(function(ep){ return postInvalidateWithRetry(ep).catch(function(e){return {ok:false,err:String(e)};}); }); var globalTimeout = new Promise(function(res){setTimeout(res, timeoutMs);}); Promise.race([ Promise.all(ps), globalTimeout ]).then(function(){ clearClientAndGoHome(); }).catch(function(){ clearClientAndGoHome(); });";
echo "})();</script>";
echo '<h2>Signing out…</h2><p>If you are not redirected automatically, <a href="https://buzzjuice.net/">click here</a>.</p>';
echo '</body></html>';
exit;
?>