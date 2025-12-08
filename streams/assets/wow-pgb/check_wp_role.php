<?php
require_once 'config.php';
require_once 'assets/init.php';

// Start secure session
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'cookie_samesite' => 'Lax'
    ]);

    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// Ensure WoWonder user is authenticated
if (empty($wo) || empty($wo['user']['email']) || empty($wo['user']['user_id']) || empty($wo['user']['username'])) {
    // error_log("❌ WoWonder user session is invalid.");
    exit();
}

// Validate and sanitize WoWonder user data
$wowonder_email = filter_var($wo['user']['email'], FILTER_VALIDATE_EMAIL);
if (!$wowonder_email) {
    error_log("❌ Invalid email format.");
    exit();
}
$wowonder_email = filter_var($wowonder_email, FILTER_SANITIZE_EMAIL);
$wowonder_user_id = intval($wo['user']['user_id']);
$wowonder_username = $wo['user']['username'];

// Securely fetch WordPress API credentials
$wp_username = getenv('WORDPRESS_API_USERNAME') ?: (defined('WORDPRESS_API_USERNAME') ? WORDPRESS_API_USERNAME : '');
$wp_password = getenv('WORDPRESS_API_PASSWORD') ?: (defined('WORDPRESS_API_PASSWORD') ? WORDPRESS_API_PASSWORD : '');
$wp_api_base = getenv('WORDPRESS_API_BASE') ?: rtrim($GLOBALS['wo']['config']['wow_api_url'], '/') . '/wp/v2/users';

if (!$wp_username || !$wp_password) {
    error_log("❌ Missing WordPress API credentials.");
    exit();
}

// API request function with improved error handling
function make_api_request($url, $wp_username, $wp_password, $retry = 2) {
    $auth_header = "Authorization: Basic " . base64_encode("$wp_username:$wp_password");

    while ($retry > 0) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [$auth_header, "Content-Type: application/json"],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Buzzjuice WoWonder-WP Bridge',
            CURLOPT_FAILONERROR => true
        ]);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("❌ cURL Error: " . $error);
        } elseif ($http_code === 200 && $result) {
            $decoded_result = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("❌ JSON Decode Error: " . json_last_error_msg());
                return false;
            }
            return $decoded_result;
        } else {
            error_log("❌ API Request failed. HTTP Code: $http_code | Response: " . print_r($result, true));
        }

        $retry--;
        sleep(1);
    }
    return false;
}

// Function to fetch WP user securely
function fetch_wp_user($username, $email, $wp_username, $wp_password, $api_base) {
    $search_params = [
        ['key' => 'email', 'value' => $email],
        ['key' => 'username', 'value' => $username]
    ];

    foreach ($search_params as $param) {
        $url = sprintf("%s?search=%s&_fields=id,roles", $api_base, urlencode($param['value']));
        $users = make_api_request($url, $wp_username, $wp_password);

        if (!empty($users) && isset($users[0]['id'])) {
            return $users[0];
        }
    }

    return null;
}

// Session-based role check to reduce API calls
$session_timeout = 20; // 4 hours
if (!isset($_SESSION['wp_role_checked']) || (time() - $_SESSION['wp_role_checked']) > $session_timeout || !isset($_SESSION['user_role'])) {
    $wp_user = fetch_wp_user($wowonder_username, $wowonder_email, $wp_username, $wp_password, $wp_api_base);

    // Check for user roles
    $user_roles = !empty($wp_user['roles']) ? (array)$wp_user['roles'] : [];
    $valid_roles = ['classic_lifestyle', 'silver_lifestyle', 'rockstar_lifestyle', 'premium_lifestyle'];
    $user_role = null;

    foreach ($valid_roles as $role) {
        if (in_array($role, $user_roles)) {
            $user_role = $role;
            break;
        }
    }

    $_SESSION['wp_role_checked'] = time();
    $_SESSION['user_role'] = $user_role;
} else {
    $user_role = $_SESSION['user_role'];
}

if (!function_exists('Wo_UpdateUserData')) {
    function Wo_UpdateUserData($user_id, $update_data) {
        global $db;
        return $db->where('user_id', $user_id)->update(T_USERS, $update_data);
    }
}

// Role Synchronization Logic
$role_to_pro_type = [
    'classic_lifestyle' => 1,
    'silver_lifestyle' => 2,
    'rockstar_lifestyle' => 3,
    'premium_lifestyle' => 4,
];

if (isset($role_to_pro_type[$user_role])) {
    $pro_type = $role_to_pro_type[$user_role];
    if ($wo['user']['pro_type'] != $pro_type) {
        Wo_UpdateUserData($wowonder_user_id, ['pro_type' => $pro_type, 'is_pro' => 1, 'pro_time' => time()]);
    }
} elseif ($user_role === null && $wo['user']['pro_type'] != 0) {
    Wo_UpdateUserData($wowonder_user_id, ['pro_type' => 0, 'is_pro' => 0]);
}

// Redirect only if the user does not have a valid role
if ($user_role === null) {
    $redirect_url = sprintf('http://%s/products/default', $_SERVER['HTTP_HOST']);
    echo '<meta http-equiv="refresh" content="0;url=' . $redirect_url . '">';
    return;
}

// If the user has a valid role, allow them to continue browsing

?>