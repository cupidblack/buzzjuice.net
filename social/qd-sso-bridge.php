<?php
/**
 * qd-sso-bridge.php — BuzzJuice → QuickDate SSO Bridge (Stateless JWT Edition)
 * Implements robust, future-proof JWT SSO with replay protection, defensive logging,
 * refresh-token fallback, and clear separation from legacy PHP/session bridging.
 */

require_once __DIR__ . '/bootstrap.php';

if (file_exists(__DIR__ . '/../shared/wwqd_bridge.php')) require_once __DIR__ . '/../shared/wwqd_bridge.php';
if (file_exists(__DIR__ . '/controllers/aj.php')) require_once __DIR__ . '/controllers/aj.php';
if (file_exists(__DIR__ . '/requests/ajax/useractions.php')) require_once __DIR__ . '/requests/ajax/useractions.php';

// --- CONFIG ---
if (!defined('BUZZ_SSO_COOKIE'))        define('BUZZ_SSO_COOKIE', 'buzz_sso');
if (!defined('BUZZ_COOKIE_DOMAIN'))     define('BUZZ_COOKIE_DOMAIN', '.buzzjuice.net');
if (!defined('BUZZ_SSO_DEBUG'))         define('BUZZ_SSO_DEBUG', false);
if (!defined('BUZZ_SSO_BRIDGE_LOG'))    define('BUZZ_SSO_BRIDGE_LOG', __DIR__ . '/qd_sso_bridge.log');
if (!defined('BUZZ_SSO_AUTO_REGISTER')) define('BUZZ_SSO_AUTO_REGISTER', true);
if (!defined('BUZZ_SSO_TTL'))           define('BUZZ_SSO_TTL', 900);
$BUZZ_SSO_SECRET = defined('BUZZ_SSO_SECRET') ? BUZZ_SSO_SECRET : (getenv('BUZZ_SSO_SECRET') ?: null);

// --- LOGGING ---
function qd_is_debug() {
    return ((isset($_GET['sso_debug']) && $_GET['sso_debug'] === '1') || (defined('BUZZ_SSO_DEBUG') && BUZZ_SSO_DEBUG === true));
}
function qd_bridge_log($msg, $ctx = []) {
    $data = [
        'ts'   => gmdate('Y-m-d H:i:s'),
        'php_session_id' => function_exists('session_id') ? session_id() : null,
        'session_name'   => function_exists('session_name') ? session_name() : null,
        'buzz_sso_len'   => isset($_COOKIE[BUZZ_SSO_COOKIE]) ? strlen($_COOKIE[BUZZ_SSO_COOKIE]) : 0,
        'remote_addr'    => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua'             => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    if (qd_is_debug()) {
        $data['cookies'] = $_COOKIE ?? [];
        $data['session'] = $_SESSION ?? [];
        $data['server']  = [
            'HTTP_HOST'    => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI'  => $_SERVER['REQUEST_URI'] ?? null,
            'HTTPS'        => $_SERVER['HTTPS'] ?? null
        ];
        $data['sess_cookie_params'] = function_exists('session_get_cookie_params') ? session_get_cookie_params() : null;
    }
    if ($ctx) $data['ctx'] = $ctx;
    @file_put_contents(BUZZ_SSO_BRIDGE_LOG, '['.$data['ts'].'] '.$msg.' | '.json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
}

// --- JWT HELPERS ---
function qd_b64url_decode($str) {
    if ($str === null || $str === '') return '';
    $s = strtr($str, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad) $s .= str_repeat('=', 4 - $pad);
    $out = base64_decode($s, true);
    return $out === false ? '' : $out;
}
function qd_b64url_encode($bin) {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}
function qd_validate_jwt($jwt, $secret) {
    if (!$jwt || !$secret) return false;
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    list($h, $p, $s) = $parts;
    $header  = json_decode(qd_b64url_decode($h), true);
    $payload = json_decode(qd_b64url_decode($p), true);
    if (!$header || !$payload || ($header['alg'] ?? '') !== 'HS256') return false;
    $expected_sig = hash_hmac('sha256', "$h.$p", $secret, true);
    $actual_sig  = qd_b64url_decode($s);
    if (!hash_equals($expected_sig, $actual_sig)) return false;
    $now = time();
    if (!empty($payload['nbf']) && $now < $payload['nbf']) return false;
    if (!empty($payload['exp']) && $now > $payload['exp']) return false;
    if (($payload['iss'] ?? '') !== 'buzzjuice.net') return false;
    if (($payload['aud'] ?? '') !== 'quickdate') return false;
    if (empty($payload['jti'])) return false;
    return $payload;
}

// --- REPLAY PROTECTION ---
define('QD_SSO_JTI_STORE', __DIR__ . '/sso_jti_store');
if (!is_dir(QD_SSO_JTI_STORE)) @mkdir(QD_SSO_JTI_STORE, 0755, true);
function qd_is_jti_used($jti) { return $jti && file_exists(QD_SSO_JTI_STORE . '/' . sha1($jti)); }
function qd_mark_jti_used($jti) { @file_put_contents(QD_SSO_JTI_STORE . '/' . sha1($jti), time(), LOCK_EX); }
function qd_cleanup_jti_store() {
    $expire = time() - 3600; // 1hr
    foreach (glob(QD_SSO_JTI_STORE . '/*') ?: [] as $file) if (filemtime($file) < $expire) @unlink($file);
}
if (mt_rand(1, 30) === 15) qd_cleanup_jti_store();

// --- FAIL-SAFE ERROR OUTPUT ---
function qd_bridge_fail_gracefully($msg = '', $ctx = []) {
    qd_bridge_log('QD SSO bridge fail: '.$msg, $ctx);
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow">';
    echo '<title>QuickDate SSO helper</title>';
    echo '<style>body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#fff;color:#111;padding:28px;max-width:720px;margin:40px auto;}a{color:#0073aa}</style></head><body>';
    echo '<h2>QuickDate Single sign-on — helper</h2>';
    echo '<p>We were unable to complete sign-in. Please <a href="/">return to the site</a> or try logging in via WordPress again.</p>';
    if (!empty($msg)) echo '<p><strong>Reason:</strong> ' . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE) . '</p>';
    echo '</body></html>';
    exit;
}

// --- FETCH/JWT PAYLOAD & SUPPORT REFRESH ---
function qd_fetch_wp_stateless_payload($sso_token, $secret, $try_refresh = false, $refresh_token = null) {
    if (empty($sso_token) || empty($secret)) return false;
    $endpoint = 'https://buzzjuice.net/?sso_action=get_token&sso_token=' . urlencode($sso_token);
    if ($try_refresh && !empty($refresh_token)) $endpoint .= '&refresh_token=' . urlencode($refresh_token);
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch); $err = curl_error($ch);
    curl_close($ch);
    if ($errno || !$response) {
        qd_bridge_log('QD SSO cURL error', ['msg'=>$err, 'errno'=>$errno, 'endpoint'=>$endpoint]);
        return false;
    }
    $json = json_decode($response, true);
    $jwt = $json['token'] ?? ($json['access_token'] ?? null);
    if (!$jwt) {
        qd_bridge_log('WP SSO endpoint returned no JWT', ['response'=>$response, 'endpoint'=>$endpoint]);
        return false;
    }
    $payload = qd_validate_jwt($jwt, $secret);
    if (!$payload) {
        qd_bridge_log('JWT failed validation', ['endpoint'=>$endpoint, 'jwt_preview'=>substr($jwt, 0, 24)]);
        return false;
    }
    if (qd_is_jti_used($payload['jti'])) {
        qd_bridge_log('QD SSO replay detected', ['jti'=>$payload['jti']]);
        return ['replay' => true, 'refresh_token' => $json['refresh_token'] ?? null];
    }
    qd_mark_jti_used($payload['jti']);
    return [
        'payload'       => $payload,
        'refresh_token' => $json['refresh_token'] ?? null
    ];
}

// --- MAIN HANDLER ---
$sso_action = $_REQUEST['sso_action'] ?? '';
$sso_token  = $_REQUEST['sso_token'] ?? ($_COOKIE[BUZZ_SSO_COOKIE] ?? '');

try {
    if (!$sso_token) throw new \Exception('No SSO token provided (try logging in via WordPress).');
    $result = qd_fetch_wp_stateless_payload($sso_token, $BUZZ_SSO_SECRET);

    // Refresh-token retry logic if first attempt fails or is a replay
    if (!$result || !empty($result['replay'])) {
        $refresh_token = is_array($result) ? ($result['refresh_token'] ?? null) : null;
        $refresh_attempted = false;
        if ($refresh_token) {
            qd_bridge_log('Token invalid/replayed, retrying fetch with refresh_token', [
                'token_preview'=>substr($sso_token,0,16),
                'refresh_token_len'=>strlen($refresh_token)
            ]);
            $result = qd_fetch_wp_stateless_payload($refresh_token, $BUZZ_SSO_SECRET);
            $refresh_attempted = true;
        }
        if (!$result || !empty($result['replay'])) {
            $err = (!$result)
                ? 'Unable to fetch or validate SSO token from WP endpoint.'
                : 'Sign-in expired or already used. Please retry logging in via WordPress.';
            throw new \Exception($err);
        }
    }

    $claims = $result['payload'] ?? [];
    if (!$claims || empty($claims['wp_user_id'])) throw new \Exception('SSO claims incomplete (no WP user id).');
    $wp_user_id    = (int)($claims['wp_user_id'] ?? 0);
    $wp_user_login = (string)($claims['wp_user_login'] ?? $claims['login'] ?? '');
    $wp_user_email = (string)($claims['wp_user_email'] ?? $claims['email'] ?? '');
    $qd_user_id    = (int)($claims['qd_user_id'] ?? 0);

    if (!$wp_user_login || !$wp_user_email) throw new \Exception('Essential identity claims (login/email) missing in SSO.');
    qd_bridge_log('Stateless SSO claims extracted', [
        'wp_user_id'=>$wp_user_id, 'wp_user_login'=>$wp_user_login, 'wp_user_email'=>$wp_user_email, 'qd_user_id'=>$qd_user_id, 'raw_claims'=>$claims
    ]);

    // --- USER MAPPING / AUTO-REGISTRATION ---
    if (!function_exists('qd_get_user_id_by_email')) throw new \Exception('QuickDate user mapping function qd_get_user_id_by_email() is missing!');
    if (!function_exists('qd_register_user') && !function_exists('qd_register_wo_user')) throw new \Exception('QuickDate user registration helper missing!');
    if (!$qd_user_id) {
        $qd_user_id = qd_get_user_id_by_email($wp_user_email);
        if (!$qd_user_id && BUZZ_SSO_AUTO_REGISTER) {
            $qd_user_id = function_exists('qd_register_user')
                ? qd_register_user($wp_user_login, $wp_user_email, $wp_user_id)
                : qd_register_wo_user($wp_user_id, $wp_user_login, $wp_user_email);
            if (!$qd_user_id) throw new \Exception('QuickDate user auto-registration failed.');
            qd_bridge_log('Auto-registered QuickDate user', ['qd_user_id'=>$qd_user_id, 'wp_user_id'=>$wp_user_id]);
        } elseif (!$qd_user_id) {
            throw new \Exception('No QuickDate account found and auto-registration is off.');
        }
    }

    // --- Metadata/profile sync WP→QD
    if (function_exists('sync_user_to_quickdate')) {
        try {
            $wp_data = [
                'wp_user_id'    => $wp_user_id,
                'qd_user_id'    => $qd_user_id,
                'wp_user_login' => $wp_user_login,
                'wp_user_email' => $wp_user_email
            ];
            sync_user_to_quickdate($wp_user_email, [], $wp_data);
            qd_bridge_log('Synchronized WP→QD user meta/xprofile', ['wp_user_id'=>$wp_user_id,'qd_user_id'=>$qd_user_id]);
        } catch (Throwable $e) {
            qd_bridge_log('FAILED sync_user_to_quickdate', ['err'=>$e->getMessage()]);
        }
    }

    // --- Issue mapping cookie (not fatal on failure)
    if (function_exists('qd_issue_buzz_sso_cookie')) {
        try {
            qd_issue_buzz_sso_cookie([
                'wp_user_id'    => $wp_user_id,
                'wp_user_login' => $wp_user_login,
                'wp_user_email' => $wp_user_email,
                'qd_user_id'    => $qd_user_id,
                'jti'           => $claims['jti'] ?? bin2hex(random_bytes(16))
            ]);
        } catch (Throwable $e) {
            qd_bridge_log('FAILED qd_issue_buzz_sso_cookie', ['err'=>$e->getMessage()]);
        }
    }

    // --- Log refresh_token for observability
    if (!empty($result['refresh_token'])) {
        qd_bridge_log('Obtained refresh_token', ['wp_user_id'=>$wp_user_id, 'len'=>strlen($result['refresh_token'])]);
        // TODO: Optionally persist refresh_token for session continuity.
    }

    // --- Ensure session for UX; fallback to session_start
    if (function_exists('SessionStart')) {
        SessionStart();
    } elseif (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // --- If not a do_login POST, output JS bridge
    if ($sso_action !== 'do_login') {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8">';
        echo '<title>QuickDate SSO Bridge</title></head><body>';
        echo '<form id="ssoForm" method="POST">';
        echo '<input type="hidden" name="sso_action" value="do_login">';
        echo '<input type="hidden" name="sso_token" value="' . htmlspecialchars($sso_token, ENT_QUOTES | ENT_SUBSTITUTE) . '">';
        echo '</form>';
        echo '<script>document.getElementById("ssoForm").submit();</script>';
        echo '<noscript><div style="color:red">JavaScript required. Please enable JavaScript and try again.</div></noscript>';
        echo '</body></html>';
        exit;
    }
    // Success: downstream logic proceeds.
} catch (\Throwable $e) {
    qd_bridge_fail_gracefully($e->getMessage(), [
        'sso_token_preview' => substr($sso_token, 0, 16),
        'file' => __FILE__, 'line' => __LINE__
    ]);
}



//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 2
// -------------------------------------------------------------
// LEGACY QuickDate SSO / Session Helpers — OBSOLETE
//
// These functions and patterns are fully deprecated and retained only for historical documentation:
//   - qd_cleanup_shadow_mismatches()
//   - qd_write_canonical_shadow_file()
//   - qd_attempt_session_reconciliation_if_required()
//   - qd_find_wp_shadow_payload()
//   - qd_unlink_local_session_file_if_exists()
//   - SessionStart(), session_start(), or any double-bootstrapping
//   - Defensive $_SESSION sync / rolling anti-drift logic
//   - Hydrating $_SESSION from buzz_sso_serialized or cookies
//   - Treating $_SESSION as authoritative for SSO identity
//
/* ----------------------------- End added helpers ----------------------------- */



//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 3
//
// Modern QuickDate SSO is strictly stateless and JWT-based:
//   - Identity and mapping come *only* from a validated RFC 7519 JWT (iss/aud/exp/nbf/jti checks).
//   - Replay protection is via the JWT jti claim, not session or file locks.
//   - User mapping and metadata sync must use explicit code and validated API calls.
//   - For explicit logout, clear buzz_sso and related keys; never destroy PHPSESSID.
//   - Any future cross-app state should use a purpose-built stateless API.
//
// ⚠ DO NOT revive or call legacy shadow/session functions.
// ⚠ All SSO trust, mapping, and replay checks must flow through the stateless JWT mechanism.
//
/* ----- BEGIN LEGACY/DEPRECATED BLOCK: SESSION BOOTSTRAP ----- */
/* static $qd_session_bootstrapped = false;
if (!$qd_session_bootstrapped) {
    try {
        if (function_exists('SessionStart')) {
            SessionStart();
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }
    } catch (Throwable $e) {
        qd_bridge_log('SessionStart() exception', ['ex'=>$e->getMessage()]);
    }
    $qd_session_bootstrapped = true;
}
qd_bridge_log('SessionStart() called', [
    'phpSessionId'=>session_id(),
    'shadow_session_id'=>(isset($_COOKIE['PHPSESSID']) ? 'shadow_'.$_COOKIE['PHPSESSID'] : null)
]);

try {
    // DO NOT CALL in modern SSO: qd_attempt_session_reconciliation_if_required();
} catch (Throwable $e) {
    qd_bridge_log('Session reconciliation attempt threw', ['err'=>$e->getMessage()]);
}
*/
/* Defensive sync: legacy anti-drift, NOT needed with JWT SSO. Retained for log context. */
/*if (!isset($_SESSION['buzz_sso_defensive_last']) || (time() - (int)$_SESSION['buzz_sso_defensive_last']) > 4*3600) {
    $_SESSION['buzz_sso_defensive_last'] = time();
    $errs = [];
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) $errs[] = 'buzz_sso_cookie_missing';
    if (empty($_SESSION['wp_user_login'])) $errs[] = 'wp_user_login_missing';
    if (empty($_SESSION['qd_user_id']) || !is_numeric($_SESSION['qd_user_id'])) $errs[] = 'qd_user_id_missing_or_invalid';
    if ($errs) qd_bridge_log('Defensive sync checks', ['errs'=>$errs]);
}
*/
/* Session normalization: DO NOT use for SSO trust! JWT is canonical. */
/*function normalize_sso_session() {}
normalize_sso_session();
*/
//                       --- END LEGACY HELPERS & SESSION PATTERNS ---
// -------------------------------------------------------------



/* Explicit logout handler (cookie/session cleanup & redirect).
   DO NOT call as part of SSO authentication; only for true user logouts. */
function qd_clear_and_logout($reason='unknown') {
    global $config;
    qd_bridge_log('Clearing session SSO keys and redirecting to logout', ['reason'=>$reason]);
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    $sso_keys = [
        'wp_user_id','wp_user_login','wp_user_email',
        'wo_user_id','qd_user_id','qd_ready','expected_user_id',
        'buzz_sso_last_sync','wp_php_session_id','wp_session_name',
        'buzz_sso_last','buzz_sso_serialized','wp_sso_login'
    ];
    foreach ($sso_keys as $k) {
        if (isset($_SESSION[$k])) unset($_SESSION[$k]);
    }
    if (isset($_SESSION['JWT'])) unset($_SESSION['JWT']);
    // Expire buzz_sso on this domain + shared parent
    if (PHP_VERSION_ID >= 70300) {
        setcookie(BUZZ_SSO_COOKIE, '', ['expires'=>time()-3600,'path'=>'/','domain'=>BUZZ_COOKIE_DOMAIN,'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    } else {
        setcookie(BUZZ_SSO_COOKIE, '', time()-3600, '/', BUZZ_COOKIE_DOMAIN, true, true);
    }
    $base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
    $target = ($base ?: '') . '/../wp-login.php';
    header('Location: ' . $target);
    exit();
}

// --------------- User DB helpers (can be called for mappings after stateless SSO) ---------------
function qd_find_user_by_id($id) {
    $db = get_qd_db_conn();
    if (!$db || !$id) return 0;
    $id = (int)$id;
    $res = $db->query("SELECT id FROM users WHERE id={$id} LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return (int)$row['id'];
    return 0;
}
function qd_get_user_row($id) {
    $db = get_qd_db_conn();
    if (!$db || !$id) return false;
    $id = (int)$id;
    $res = $db->query("SELECT * FROM users WHERE id={$id} LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return $row;
    return false;
}
function qd_find_user_by_login_email($login, $email) {
    $db = get_qd_db_conn();
    if (!$db) return 0;
    $escL = $db->real_escape_string((string)$login);
    $escE = $db->real_escape_string((string)$email);
    $res = $db->query("SELECT id FROM users WHERE username='{$escL}' AND email='{$escE}' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return (int)$row['id'];
    return 0;
}
/* ----- END LEGACY/DEPRECATED BLOCK: SESSION BOOTSTRAP ----- */




/**
 * qd_register_user() — Register QuickDate user (stateless SSO, JWT/federated, robust)
 * Returns new QuickDate user id (int) on success, 0 on failure.
 */
if (!function_exists('qd_register_user')) {
    function qd_register_user($login, $email, $wp_user_id = 0) {
        if (!function_exists('LoadEndPointResource')) {
            qd_bridge_log('qd_register_user: LoadEndPointResource missing');
            return 0;
        }
        $user = LoadEndPointResource('users');
        if (!$user || !method_exists($user, 'register')) {
            qd_bridge_log('qd_register_user: users endpoint missing or register() not available', ['user_resource_exists'=> (bool)$user]);
            return 0;
        }

        // Username fallback + collision safety
        $username = preg_replace('~[^a-z0-9_.-]~i', '', (string)$login);
        if (!$username) $username = 'wpuser' . intval($wp_user_id) . '_' . random_int(1000,9999);

        $conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;
        $wp_full = (function_exists('wp_get_full_user_data') && $conn && $wp_user_id)
            ? wp_get_full_user_data($conn, $wp_user_id)
            : [];
        $avatar = $wp_full['xprofile']['avatar'] ?? $wp_full['meta']['avatar'] ?? ($GLOBALS['config']->userDefaultAvatar ?? '');

        $password = bin2hex(random_bytes(8));
        $imported_avatar = $avatar;
        if (!empty($avatar) && method_exists($user, 'ImportImageFromLogin')) {
            try {
                $imp = $user->ImportImageFromLogin($avatar, 1);
                if (!empty($imp)) $imported_avatar = $imp;
                else qd_bridge_log('qd_register_user: ImportImageFromLogin returned empty, using fallback avatar', ['avatar'=>$avatar]);
            } catch (Throwable $e) {
                qd_bridge_log('qd_register_user: ImportImageFromLogin failed, using fallback avatar', ['ex'=>$e->getMessage(),'avatar'=>$avatar]);
            }
        }

        $now = time();
        $lang = 'english';
        if (!empty($GLOBALS['config']->defaultLang)) {
            $lang = $GLOBALS['config']->defaultLang;
        } elseif (!empty($GLOBALS['config']->defualtLang)) {
            $lang = $GLOBALS['config']->defualtLang;
            qd_bridge_log('qd_register_user: using legacy config key "defualtLang"', ['lang'=>$lang]);
        }

        $re_data = [
            'username'      => $username,
            'password'      => $password,
            'email'         => $email,
            'avatar'        => $imported_avatar,
            'active'        => 1,
            'src'           => 'wp-sso',
            'wp_user_id'    => (int)$wp_user_id,
            'ip_address'    => function_exists('get_ip_address') ? get_ip_address() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            'language'      => $lang,
            'registered'    => gmdate('Y-m-d H:i:s', $now),
            'social_login'  => 1,
            'start_up'      => 0
        ];
        if (!empty($wp_full['xprofile']['first_name']) || !empty($wp_full['xprofile']['last_name'])) {
            $re_data['first_name'] = $wp_full['xprofile']['first_name'] ?? '';
            $re_data['last_name']  = $wp_full['xprofile']['last_name'] ?? '';
        } elseif (!empty($wp_full['meta']['first_name']) || !empty($wp_full['meta']['last_name'])) {
            $re_data['first_name'] = $wp_full['meta']['first_name'] ?? '';
            $re_data['last_name']  = $wp_full['meta']['last_name']  ?? '';
        }

        try {
            $reg = $user->register($re_data);
        } catch (Throwable $e) {
            qd_bridge_log('qd_register_user: user->register() exception', ['ex'=>$e->getMessage(), 'payload'=>$re_data]);
            return 0;
        }

        $created_id = 0;
        if (is_array($reg) && isset($reg['code']) && intval($reg['code']) === 200 && !empty($reg['userId'])) {
            $created_id = (int)$reg['userId'];
        } elseif (is_array($reg) && !empty($reg['id'])) {
            $created_id = (int)$reg['id'];
        } else {
            qd_bridge_log('qd_register_user: register() returned unexpected result', ['result'=>$reg]);
            return 0;
        }

        try {
            if (method_exists($user, 'SetLoginWithSession') && !empty($email)) {
                $user->SetLoginWithSession($email);
            }
        } catch (Throwable $e) {
            qd_bridge_log('qd_register_user: SetLoginWithSession exception', ['ex'=>$e->getMessage()]);
        }

        if (!empty($wp_user_id) && $wp_user_id > 0) {
            $meta_key = 'qd_user_id';
            $meta_value = (string)$created_id;
            $did_write = qd_persist_wp_usermeta($wp_user_id, $meta_key, $meta_value, $conn);
            if (!$did_write) {
                qd_bridge_log('qd_register_user: could not set WP usermeta qd_user_id', [
                    'wp_user_id'=>$wp_user_id, 'qd_user_id'=>$created_id
                ]);
            }
        } else {
            qd_bridge_log('qd_register_user: no wp_user_id provided — skipping WP usermeta write', [
                'wp_user_id'=>$wp_user_id, 'created_qd_id'=>$created_id
            ]);
        }

        if (session_status() === PHP_SESSION_NONE) @session_start();
        try { $_SESSION['qd_user_id'] = $created_id; } catch(Throwable $e) {
            qd_bridge_log('qd_register_user: failed to set session qd_user_id', ['ex'=>$e->getMessage()]);
        }

        qd_bridge_log('qd_register_user: Auto-registered QuickDate user', [
            'id'        => $created_id,
            'username'  => $username,
            'email'     => $email,
            're_data'   => $re_data
        ]);
        return $created_id;
    }
}

/** Helper to persist custom meta value to WP usermeta with fallback strategies. */
if (!function_exists('qd_persist_wp_usermeta')) {
    function qd_persist_wp_usermeta($wp_user_id, $meta_key, $meta_value, $conn = null) {
        if ($conn && function_exists('wp_update_usermeta')) {
            try {
                wp_update_usermeta($conn, (int)$wp_user_id, $meta_key, $meta_value);
                qd_bridge_log('Set wp_usermeta '.$meta_key.' via wp_update_usermeta', ['wp_user_id'=>$wp_user_id,'meta_value'=>$meta_value]);
                return true;
            } catch (Throwable $e) {
                qd_bridge_log('wp_update_usermeta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'meta_key'=>$meta_key]);
            }
        }
        if (function_exists('update_user_meta')) {
            try {
                update_user_meta((int)$wp_user_id, $meta_key, $meta_value);
                qd_bridge_log('Set wp_usermeta '.$meta_key.' via update_user_meta', ['wp_user_id'=>$wp_user_id,'meta_value'=>$meta_value]);
                return true;
            } catch (Throwable $e) {
                qd_bridge_log('update_user_meta threw', ['error'=>$e->getMessage(),'wp_user_id'=>$wp_user_id,'meta_key'=>$meta_key]);
            }
        }
        if ($conn && $wp_user_id) {
            $um_table_sql = null;
            if (function_exists('wp_table')) {
                $um_table_sql = wp_table('usermeta');
            } else {
                $prefix = defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_';
                $um_table_sql = defined('WP_DB_NAME')
                    ? ('`' . WP_DB_NAME . '`.`' . $prefix . 'usermeta`')
                    : ('`' . $prefix . 'usermeta`');
            }
            $select_sql = "SELECT umeta_id FROM $um_table_sql WHERE user_id = ? AND meta_key = ? LIMIT 1";
            $stmt = @mysqli_prepare($conn, $select_sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'is', $wp_user_id, $meta_key);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    mysqli_stmt_bind_result($stmt, $umeta_id);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);
                    $update_sql = "UPDATE $um_table_sql SET meta_value = ? WHERE umeta_id = ?";
                    $upd = @mysqli_prepare($conn, $update_sql);
                    if ($upd) {
                        mysqli_stmt_bind_param($upd, 'si', $meta_value, $umeta_id);
                        mysqli_stmt_execute($upd);
                        mysqli_stmt_close($upd);
                        return true;
                    }
                } else {
                    mysqli_stmt_close($stmt);
                    $insert_sql = "INSERT INTO $um_table_sql (user_id, meta_key, meta_value) VALUES (?, ?, ?)";
                    $ins = @mysqli_prepare($conn, $insert_sql);
                    if ($ins) {
                        mysqli_stmt_bind_param($ins, 'iss', $wp_user_id, $meta_key, $meta_value);
                        mysqli_stmt_execute($ins);
                        mysqli_stmt_close($ins);
                        return true;
                    }
                }
            }
            $esc_val = mysqli_real_escape_string($conn, $meta_value);
            $esc_key = mysqli_real_escape_string($conn, $meta_key);
            $check_raw = "SELECT umeta_id FROM $um_table_sql WHERE user_id = " . intval($wp_user_id) . " AND meta_key = '$esc_key' LIMIT 1";
            $res = @$conn->query($check_raw);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $umeta_id = intval($row['umeta_id']);
                $raw_update = "UPDATE $um_table_sql SET meta_value = '$esc_val' WHERE umeta_id = $umeta_id";
                @$conn->query($raw_update);
                return true;
            } else {
                $raw_insert = "INSERT INTO $um_table_sql (user_id, meta_key, meta_value) VALUES (" . intval($wp_user_id) . ", '$esc_key', '$esc_val')";
                @$conn->query($raw_insert);
                return true;
            }
        }
        return false;
    }
}





//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 5
/* ------------------------------------------------------------------------ 
   STATELESS USER MAPPING: Resolving QuickDate user identity from JWT/WP 
   ------------------------------------------------------------------------ */

if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

$final_qd_user_id = 0;
$orig_session_qd  = isset($_SESSION['qd_user_id']) ? (int)$_SESSION['qd_user_id'] : 0;
$wp_conn = function_exists('get_wp_db_conn') ? get_wp_db_conn() : null;

qd_bridge_log('Mapping start', [
    'claim_qd'  => $claim_qd_user_id,
    'session_qd'=> $orig_session_qd,
    'login'     => $claim_wp_user_login,
    'email'     => $claim_wp_user_email
]);

$has_all_canonical = (
    $claim_qd_user_id && $claim_wp_user_id && $claim_wp_user_login && $claim_wp_user_email
);
if ($has_all_canonical) {
    qd_bridge_log('All canonical SSO values present — performing strict qd_user_id verification', [
        'claim_qd'=>$claim_qd_user_id,
        'wp_user_id'=>$claim_wp_user_id,
        'wp_user_login'=>$claim_wp_user_login,
        'wp_user_email'=>$claim_wp_user_email
    ]);
    $row = qd_get_user_row($claim_qd_user_id);
    if ($row) {
        $db_un = isset($row['username']) ? trim((string)$row['username']) : '';
        $db_em = isset($row['email']) ? trim((string)$row['email']) : '';
        if (
            strcasecmp($db_un, trim($claim_wp_user_login)) === 0 &&
            strcasecmp($db_em, trim($claim_wp_user_email)) === 0
        ) {
            $final_qd_user_id = (int)$claim_qd_user_id;
            qd_bridge_log('Strict verification successful — qd_user_id accepted', ['qd_user_id'=>$final_qd_user_id]);
        } else {
            qd_bridge_log('Strict verification failed — username/email mismatch, clearing session', [
                'qd_user_id'=>$claim_qd_user_id,
                'db_username'=>$db_un,
                'db_email'=>$db_em,
                'session_login'=>trim($claim_wp_user_login),
                'session_email'=>trim($claim_wp_user_email)
            ]);
            unset($_SESSION['qd_user_id']);
            $claim_qd_user_id = 0;
            $orig_session_qd  = 0;
        }
    } else {
        qd_bridge_log('Strict verification failed — qd_user_id not found in DB, clearing session', [
            'qd_user_id'=>$claim_qd_user_id
        ]);
        unset($_SESSION['qd_user_id']);
        $claim_qd_user_id = 0;
        $orig_session_qd  = 0;
    }
}

if (!$final_qd_user_id) {
    if ($claim_qd_user_id && qd_find_user_by_id($claim_qd_user_id)) {
        $final_qd_user_id = $claim_qd_user_id;
        qd_bridge_log('Using qd_user_id from claim/cookie/session (exists in DB)', ['qd_user_id'=>$final_qd_user_id]);
    } else {
        $found = qd_find_user_by_login_email($claim_wp_user_login, $claim_wp_user_email);
        if ($found) {
            $final_qd_user_id = $found;
            qd_bridge_log('Mapped qd_user_id via login+email', ['qd_user_id'=>$final_qd_user_id]);
            if (!empty($claim_wp_user_id) && $wp_conn && function_exists('qd_persist_wp_usermeta')) {
                try {
                    qd_persist_wp_usermeta($claim_wp_user_id, 'qd_user_id', $final_qd_user_id, $wp_conn);
                    qd_bridge_log('Persisted mapped qd_user_id to WordPress usermeta', [
                        'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                    ]);
                } catch (Throwable $e) {
                    qd_bridge_log('Exception persisting qd_user_id to WP usermeta', [
                        'ex'=>$e->getMessage(),'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                    ]);
                }
            }
        } else {
            if (BUZZ_SSO_AUTO_REGISTER && filter_var($claim_wp_user_email, FILTER_VALIDATE_EMAIL)) {
                qd_bridge_log('No mapping found — attempting auto-register', [
                    'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email,'orig_session_qd'=>$orig_session_qd
                ]);
                $created = qd_register_user($claim_wp_user_login, $claim_wp_user_email, $claim_wp_user_id);
                if ($created) {
                    $final_qd_user_id = (int)$created;
                    qd_bridge_log('Auto-register created QuickDate user', ['created_id'=>$created]);
                    $_SESSION['qd_user_id'] = $final_qd_user_id;
                    $claim_qd_user_id = $final_qd_user_id;
                    if (!empty($claim_wp_user_id) && $wp_conn && function_exists('qd_persist_wp_usermeta')) {
                        try {
                            qd_persist_wp_usermeta($claim_wp_user_id, 'qd_user_id', $final_qd_user_id, $wp_conn);
                            qd_bridge_log('Persisted auto-registered qd_user_id to WP usermeta', [
                                'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                            ]);
                        } catch (Throwable $e) {
                            qd_bridge_log('Exception persisting auto-registered qd_user_id to WP usermeta', [
                                'ex'=>$e->getMessage(),'wp_user_id'=>$claim_wp_user_id,'qd_user_id'=>$final_qd_user_id
                            ]);
                        }
                    }
                } else {
                    qd_bridge_log('Auto-register failed: no created id returned', [
                        'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email
                    ]);
                }
            } else {
                qd_bridge_log('Auto-registration disabled or invalid email, mapping not found', [
                    'login'=>$claim_wp_user_login,'email'=>$claim_wp_user_email
                ]);
            }
            if (!$final_qd_user_id && $orig_session_qd && qd_find_user_by_id($orig_session_qd)) {
                $final_qd_user_id = $orig_session_qd;
                qd_bridge_log('Preserving original session qd_user_id', ['qd_user_id'=>$final_qd_user_id]);
            }
        }
    }
}

if (!$final_qd_user_id) {
    qd_bridge_log('Unable to determine QuickDate user id after mapping/registration', [
        'session'=>$_SESSION, 'cookie_payload'=>$cookie_payload ?? null
    ]);
    qd_clear_and_logout('no_qd_user_after_mapping');
}

$_SESSION['wp_user_login'] = $_SESSION['wp_user_login'] ?? trim($claim_wp_user_login);
$_SESSION['wp_user_id']    = (int)$claim_wp_user_id;
$_SESSION['wp_user_email'] = trim($claim_wp_user_email);
$_SESSION['qd_user_id']    = (int)$final_qd_user_id;

try {
    $need_issue = false;
    if (empty($_COOKIE[BUZZ_SSO_COOKIE])) {
        $need_issue = true;
    } else {
        if (!is_array($cookie_payload)) {
            $cookie_payload = qd_sso_verify_token($_COOKIE[BUZZ_SSO_COOKIE], $BUZZ_SSO_SECRET) ?: null;
        }
        if (!is_array($cookie_payload)
            || empty($cookie_payload['qd_user_id'])
            || (int)$cookie_payload['qd_user_id'] !== (int)$final_qd_user_id
            || (!empty($cookie_payload['exp']) && $cookie_payload['exp'] < time())
        ) {
            $need_issue = true;
        }
    }

    if ($need_issue) {
        $new_payload = [
            'wp_user_id'    => (int)$_SESSION['wp_user_id'],
            'wp_user_login' => (string)$_SESSION['wp_user_login'],
            'wp_user_email' => (string)$_SESSION['wp_user_email'],
            'qd_user_id'    => (int)$_SESSION['qd_user_id']
        ];
        qd_issue_buzz_sso_cookie($new_payload);
    }
} catch (Throwable $e) {
    qd_bridge_log('Exception while ensuring long-lived buzz_sso cookie', ['ex'=>$e->getMessage()]);
}

function qd_build_sso_password_token($qd_user_id, $wp_user_id, $wp_user_login, $wp_user_email, $secret) {
    $nonce = bin2hex(random_bytes(8));
    $claims = [
        'ver'          => 1,
        'qd_user_id'   => (int)$qd_user_id,
        'wp_user_id'   => (int)$wp_user_id,
        'wp_user_login'=> (string)$wp_user_login,
        'wp_user_email'=> (string)$wp_user_email,
        'iat'          => time(),
        'exp'          => time() + BUZZ_SSO_TTL,
        'nonce'        => $nonce,
    ];
    $json = json_encode($claims);
    $sig  = hash_hmac('sha256', $json, (string)$secret, true);
    return 'WPSSO.v1.' . _qd_b64url_encode($json) . '.' . _qd_b64url_encode($sig);
}

$sso_username = $_SESSION['wp_user_login'];
$sso_password = qd_build_sso_password_token(
    $_SESSION['qd_user_id'],
    $_SESSION['wp_user_id'],
    $_SESSION['wp_user_login'],
    $_SESSION['wp_user_email'],
    $BUZZ_SSO_SECRET
);

$site_base = defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($config->uri) ? rtrim($config->uri,'/') : '');
$last_url = '/';
if (!empty($_REQUEST['last_url'])) {
    $parsed = parse_url((string)$_REQUEST['last_url']);
    $path = $parsed['path'] ?? '/';
    $last_url = (strpos($path, '/') === 0) ? $path : '/';
}
$ajax_url = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php') . '?sso_action=do_login';

qd_bridge_log('SSO client payload prepared', [
    'sso_username'     => $sso_username,
    'sso_password_len' => strlen($sso_password),
    'ajax_url'         => $ajax_url,
    'last_url'         => $last_url
]);

if (!empty($_GET['sso_action']) && $_GET['sso_action'] === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    QD_SSO_Login();
    exit;
}

//END QuickDate 'social/qd-sso-bridge.php' UPDATED PART 5 - FINAL





//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 6
function QD_SSO_Login() {
    global $BUZZ_SSO_SECRET, $config;
    header('Content-Type: application/json; charset=utf-8');

    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $last_url = isset($_POST['last_url']) ? (string)$_POST['last_url'] : '/';

    if (!$BUZZ_SSO_SECRET) {
        qd_bridge_log('QD_SSO_Login: BUZZ_SSO_SECRET missing');
        http_response_code(500);
        echo json_encode(['status'=>500,'errors'=>['Server misconfiguration']]);
        exit;
    }

    if (strlen($password) < 40 || strlen($password) > 4096) {
        qd_bridge_log('QD_SSO_Login: invalid token length', ['token_len'=>strlen($password)]);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid token format']]);
        exit;
    }

    $claims = qd_parse_sso_password_token($password, $BUZZ_SSO_SECRET);
    if (!$claims) {
        qd_bridge_log('QD_SSO_Login: invalid SSO password token', ['token_preview'=>substr($password,0,40)]);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid or expired SSO token']]);
        exit;
    }

    // Replay protection (if needed)
    if (!empty($claims['jti']) && function_exists('qd_register_jti')) {
        $exp = $claims['exp'] ?? (time()+BUZZ_SSO_TTL);
        if (!qd_register_jti($claims['jti'], $exp)) {
            qd_bridge_log('Replay detected', ['jti'=>$claims['jti']]);
            http_response_code(401);
            echo json_encode(['status'=>401,'errors'=>['Replay detected']]);
            exit;
        }
    }

    $exp_qd    = (int)($claims['qd_user_id'] ?? 0);
    $exp_wp    = (int)($claims['wp_user_id'] ?? 0);
    $exp_login = (string)($claims['wp_user_login'] ?? '');
    $exp_email = (string)($claims['wp_user_email'] ?? '');

    // Enterprise: required anchor (email or wp_user_id)
    if (empty($exp_email) && empty($exp_wp)) {
        qd_bridge_log('Missing strong anchor', []);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid SSO identity']]);
        exit;
    }

    qd_bridge_log('QD_SSO_Login canonical identity', [
        'qd'    => $exp_qd,
        'wp'    => $exp_wp,
        'login' => $exp_login,
        'email' => $exp_email
    ]);
    $identifier_count = 0;
    foreach ([$exp_qd, $exp_wp, $exp_login, $exp_email] as $v) if (!empty($v)) $identifier_count++;
    if ($identifier_count < 3) {
        qd_bridge_log('Insufficient identifiers in token');
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['Invalid SSO token structure']]);
        exit;
    }

    $db = get_qd_db_conn();
    $candidates = [];
    if ($db) {
        $where = [];
        $params = [];
        if ($exp_qd)    { $where[] = 'id=?';           $params[] = $exp_qd; }
        if ($exp_email) { $where[] = 'email=?';        $params[] = $exp_email; }
        if ($exp_login) { $where[] = 'username=?';     $params[] = $exp_login; }
        if ($exp_wp)    { $where[] = 'wp_user_id=?';   $params[] = $exp_wp; }
        if ($where) {
            $sql = 'SELECT * FROM users WHERE ' . implode(' OR ', $where) . ' LIMIT 5';
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $types = '';
                foreach ($params as $p) { $types .= is_int($p) ? 'i' : 's'; }
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $candidates[] = $row;
                $stmt->close();
            }
        }
    }
    qd_bridge_log('QD_SSO_Login candidates count', ['count'=>count($candidates)]);

    $accepted_user = null;
    $accepted_matches = [];
    foreach ($candidates as $row) {
        $db_id  = (int)$row['id'];
        $db_un  = (string)$row['username'];
        $db_em  = (string)$row['email'];
        $db_wpu = (int)($row['wp_user_id'] ?? 0);

        $m_id  = ($exp_qd && $db_id === $exp_qd) ? 1 : 0;
        $m_em  = ($exp_email && strcasecmp($db_em, $exp_email) === 0) ? 1 : 0;
        $m_un  = ($exp_login && strcasecmp($db_un, $exp_login) === 0) ? 1 : 0;
        $m_wpu = ($exp_wp && $db_wpu === $exp_wp) ? 1 : 0;

        $cnt = $m_id + $m_em + $m_un + $m_wpu;
        if ($cnt >= 3) {
            $accepted_user = $row;
            $accepted_matches = ['id'=>$m_id, 'email'=>$m_em, 'username'=>$m_un, 'wp_user_id'=>$m_wpu];
            break;
        }
    }

    if (!$accepted_user) {
        qd_bridge_log('QD_SSO_Login: no accepted candidate (≥3 required)', [
            'expected'   => ['qd'=>$exp_qd,'wp'=>$exp_wp,'login'=>$exp_login,'email'=>$exp_email],
            'candidates' => array_map(function($c){
                return [
                    'id'        => $c['id'],
                    'username'  => $c['username'],
                    'email'     => $c['email'],
                    'wp_user_id'=> $c['wp_user_id'] ?? null
                ];
            }, $candidates)
        ]);
        http_response_code(401);
        echo json_encode(['status'=>401,'errors'=>['No matching QuickDate account for SSO.']]);
        exit;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_start()) {
            qd_bridge_log('Session start failed on login');
            http_response_code(500);
            echo json_encode(['status'=>500,'errors'=>['Session initialization failed']]);
            exit;
        }
    }

    $_SESSION['qd_user_id']    = (int)$accepted_user['id'];
    $_SESSION['user_id']       = $accepted_user['web_token'] ?? (int)$accepted_user['id'];
    $_SESSION['wp_sso_login']  = true;
    $_SESSION['wp_user_id']    = $exp_wp;
    $_SESSION['wp_user_email'] = $exp_email;
    if (!isset($_SESSION['wp_user_login'])) $_SESSION['wp_user_login'] = $exp_login;

    if (function_exists('LoadEndPointResource')) {
        $usersRes = LoadEndPointResource('users');
        if ($usersRes && method_exists($usersRes, 'SetLoginWithSession') && !empty($exp_email)) {
            try {
                $usersRes->SetLoginWithSession($exp_email);
                qd_bridge_log('SetLoginWithSession invoked', ['email'=>$exp_email]);
            } catch (Throwable $e) {
                qd_bridge_log('SetLoginWithSession exception', ['ex'=>$e->getMessage()]);
            }
        }
    }

    // Post-login WP→QD sync logic as previously implemented

    // Hardened redirect
    $default_url = (isset($config->uri) ? rtrim($config->uri, '/') : '') . '/find-matches';
    $url = $default_url;
    if (!empty($accepted_user['start_up']) && $accepted_user['start_up'] == 3 && !empty($accepted_user['verified'])) {
        $url = (isset($config->uri) ? rtrim($config->uri,'/') : '') . '/steps';
    }
    if (!empty($last_url) && $last_url !== '//') {
        $parsed = parse_url($last_url);
        $site_base = isset($config->uri) ? rtrim($config->uri, '/') : '';
        $is_relative = empty($parsed['host']) && substr($last_url, 0, 2) !== '//' && empty($parsed['scheme']);
        $is_same_origin = $site_base && strpos($last_url, $site_base) === 0;
        if ($is_relative || $is_same_origin) {
            $url = $last_url;
        }
    }

    qd_bridge_log('QD_SSO_Login success', [
        'user_id'=>$accepted_user['id'],
        'matches'=>$accepted_matches,
        'redirect'=>$url,
        'session_id'=>session_id()
    ]);

    http_response_code(200);
    echo json_encode(['status'=>200,'location'=>$url]);
    exit;
}





//START QuickDate 'social/qd-sso-bridge.php' CODE - PART 7
// -----------------------------------------------------------------------------
// QD SSO Bridge HTML: stateless, production-grade, debug/diagnostic friendly
// -----------------------------------------------------------------------------

// Generate CSP nonce for inline script
$nonce = bin2hex(random_bytes(16));

// Security headers for production browser-layer defense
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';");
header("Referrer-Policy: no-referrer");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");

// Log bridge page render
qd_bridge_log('Rendering QD SSO bridge page', [
    'sso_username'      => $sso_username,
    'sso_password_len'  => strlen($sso_password),
    'last_url'          => $last_url,
    'final_qd_user_id'  => isset($final_qd_user_id) ? $final_qd_user_id : null,
    'php_session_id'    => session_id(),
    'shadow_session_present' => isset($_COOKIE['PHPSESSID']),
    'session_keys'      => array_keys($_SESSION),
    'cookie_keys'       => array_keys($_COOKIE),
]);
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
.card{max-width:560px;margin:10vh auto;padding:1.5rem 1.75rem;background:#131a33;border-radius:10px;box-shadow:0 4px 32px #0008}
.title{font-size:1.45rem;font-weight:700;margin-bottom:.5em}
.status{font-size:1.05rem;margin-top:1em}
.status.ok{color:#6f6}.status.err{color:#e88}
.dbg{font-size:.9em;margin-top:2em;word-break:break-all}
</style>
</head>
<body>
  <div class="card">
    <div class="title">Signing you in…</div>
    <div id="status" class="status">Preparing secure session…</div>
    <?php if (qd_is_debug()): ?>
      <div class="dbg"><pre><?php 
        echo htmlspecialchars(print_r([
          'ajax_url'=>$ajax_url,
          'post'=>[
            'password'=>'(token:len='.strlen($sso_password).')',
            'last_url'=>$last_url,
            'remember_device'=>'on'
          ],
          'session_keys'=>array_keys($_SESSION),
          'cookie_keys'=>array_keys($_COOKIE)
        ], true)); 
      ?></pre></div>
    <?php endif; ?>
    <noscript>
      <div class="status err">
        JavaScript is required for secure sign-in. Please enable JavaScript.
      </div>
    </noscript>
  </div>

  <script nonce="<?php echo htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8'); ?>">
  (function(){
    if (window.__qd_sso_executed) return;
    window.__qd_sso_executed = true;

    var ajaxUrl = <?php echo json_encode($ajax_url); ?>;
    var payload = {
      password: <?php echo json_encode($sso_password); ?>,
      remember_device: 'on',
      last_url: <?php echo json_encode($last_url); ?>
    };
    var beaconUrl = <?php
      $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/qd-sso-bridge.php';
      echo json_encode($self . '?sso_client_log=1');
    ?>;
    var statusEl = document.getElementById('status');

    function beacon(msg, extra){
      try{
        var dataObj = {msg:msg,extra:extra||{},when:Date.now()};
        var data = JSON.stringify(dataObj);
        if (data.length > 2000) data = data.substring(0,2000);
        if (navigator.sendBeacon) navigator.sendBeacon(beaconUrl, data);
        else { var x = new XMLHttpRequest(); x.open('POST', beaconUrl, true); x.setRequestHeader('Content-Type','text/plain'); x.send(data); }
      }catch(e){}
    }

    statusEl && (statusEl.textContent = 'Contacting server…');
    beacon('bridge:init', {ajaxUrl: ajaxUrl, last: payload.last_url});

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.withCredentials = true;
    xhr.timeout = 20000;

    xhr.onreadystatechange = function(){
      if (xhr.readyState === 4) {
        var ok=false, locationUrl=null, errors=null, res=null;
        try { res = JSON.parse(xhr.responseText); } catch(e) {
          beacon('bridge:parse_error', {http: xhr.status});
        }
        if (res) { ok = !!(res.status===200 || res.status===600) && !!res.location; locationUrl = res.location; errors = res.errors || null; }
        beacon('bridge:response', {status: res && res.status, http: xhr.status});
        if (ok) {
          statusEl && (statusEl.className='status ok', statusEl.textContent='Welcome back! Redirecting…');
          payload.password = null; delete payload.password;
          setTimeout(function(){
            if (locationUrl && locationUrl.charAt(0) === '/' && locationUrl.indexOf('//') !== 0) {
              window.location.href = locationUrl;
            } else {
              statusEl && (statusEl.className='status err', statusEl.textContent='Invalid redirect target.');
            }
            xhr = null;
          }, 400);
        } else {
          var body = xhr.responseText || '';
          var looksLikeHtml = body.indexOf('<!DOCTYPE') !== -1 || body.indexOf('<html') !== -1;
          if (!res && looksLikeHtml && payload.last_url && payload.last_url.charAt(0) === '/' && payload.last_url.indexOf('//') !== 0) {
            beacon('bridge:fallback_html_redirect', {http: xhr.status});
            window.location.href = payload.last_url;
            return;
          }
          statusEl && (statusEl.className='status err', statusEl.textContent=(errors && errors.join ? errors.join(', ') : 'Unexpected response.'));
          beacon('bridge:failed', {http: xhr.status});
          xhr = null;
        }
      }
    };

    xhr.onerror = function(){ beacon('bridge:error', {http: xhr.status}); statusEl && (statusEl.className='status err', statusEl.textContent='Network or server error.'); xhr=null; };
    xhr.ontimeout = function(){ beacon('bridge:timeout', {}); statusEl && (statusEl.className='status err', statusEl.textContent='Request timed out.'); xhr=null; };

    var body = 'password=' + encodeURIComponent(payload.password)
             + '&remember_device=on'
             + '&last_url=' + encodeURIComponent(payload.last_url);
    xhr.send(body);

    // Memory hygiene: wipe password after use
    payload.password = null; delete payload.password;
  })();
  </script>
</body>
</html>