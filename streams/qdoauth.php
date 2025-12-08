<?php
require_once 'config.php';
require_once 'assets/init.php';

$uri = rtrim($site_url, '/'); // Sanitize the site URL by removing trailing slash

global $db;

if (isset($_GET['code']) && !empty($_GET['code'])) {
    // Use environment variables or configuration for sensitive data
    $app_id = getenv('APP_ID') ?: '73b84a058cfdca19e38e';
    $app_secret = getenv('APP_SECRET') ?: '9415c0836d301c44500d8a149fc127cabf54f82';
    $quickdate_url = 'https://buzzjuice.net/social';
    $code = Secure($_GET['code']);
    $time = time();

    // Construct the authorization URL
    $auth_url = "{$quickdate_url}/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}";

    // Create a secure stream context
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    // Fetch the access token
    $get = file_get_contents($auth_url, false, $context);
    if ($get === false) {
        error_log("Failed to connect to the authorization server: {$auth_url}");
        echo __('Failed to connect to the authorization server.') . "<a href='" . $uri . "'>" . __('Return back') . "</a>";
        exit();
    }

    // Decode the JSON response
    $wo_json_reply = json_decode($get, true);
    if (!is_array($wo_json_reply) || !isset($wo_json_reply['access_token'])) {
        error_log("Invalid response from authorization server: {$get}");
        echo __('Error found, please try again later.') . "<a href='" . $uri . "'>" . __('Return back') . "</a>";
        exit();
    }

    $access_token = $wo_json_reply['access_token'];
    $type = "get_user_data";
    $user_data_url = "{$quickdate_url}/api_request?access_token={$access_token}&type={$type}&cache={$time}";

    // Fetch user data
    $user_data_json = file_get_contents($user_data_url, false, $context);
    if ($user_data_json === false) {
        error_log("Failed to fetch user data: {$user_data_url}");
        echo __('Failed to fetch user data.') . "<a href='" . $uri . "'>" . __('Return back') . "</a>";
        exit();
    }

    // Decode the user data JSON response
    $user_data_array = json_decode($user_data_json, true);
    if (!is_array($user_data_array) || !isset($user_data_array['user_data'])) {
        error_log("Invalid user data received: {$user_data_json}");
        echo __('Invalid user data received.') . "<a href='" . $uri . "'>" . __('Return back') . "</a>";
        exit();
    }

    // Process user data
    $user_data = $user_data_array['user_data'];
    error_log("User data retrieved: " . print_r($user_data, true)); // Log user data for debugging

    // Redirect to the application
    header("Location: {$uri}");
    exit();
} 
?>