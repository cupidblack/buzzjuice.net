<?php
// WoWonder → WordPress BPBM Message Bridge

// Include helpers and plugin detection
require_once '/home/koware/public_html/buzzjuice.net/data/db_helpers.php';
require_once '/home/koware/public_html/buzzjuice.net/data/sync_messages/sync_helpers.php';
require_once '/home/koware/public_html/buzzjuice.net/wp-content/plugins/bluecrown-wp/helpers/snc_msg-bpbm_helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Method not allowed']);
    exit;
}

// Parse input
$input = $_POST;
$ww_from_id = isset($input['from_user_id']) ? (int)$input['from_user_id'] : 0;
$ww_to_id   = isset($input['to_user_id']) ? (int)$input['to_user_id'] : 0;
$message    = isset($input['message']) ? trim($input['message']) : '';
$media_url  = isset($input['media_url']) ? trim($input['media_url']) : null;

// Validate
if (!$ww_from_id || !$ww_to_id || !$message) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Missing required fields']);
    exit;
}

// Map users
$wp_db = get_wp_db_conn();
$wp_from_id = ww_to_wp($ww_from_id, $wp_db);
$wp_to_id   = ww_to_wp($ww_to_id, $wp_db);

if (!$wp_from_id || !$wp_to_id) {
    log_sync_event("User mapping failed: WW($ww_from_id,$ww_to_id) → WP($wp_from_id,$wp_to_id)");
    http_response_code(404);
    echo json_encode(['status' => 'error', 'error' => 'User mapping failed']);
    exit;
}

// Check BPBM active
if (!snc_msg_is_bpbm_active()) {
    log_sync_event("BPBM not active. Cannot sync message from $ww_from_id to $ww_to_id");
    http_response_code(501);
    echo json_encode(['status' => 'error', 'error' => 'BPBM not active']);
    exit;
}

// Prepare REST API call
$wp_api_url = site_url('/wp-json/bp-better-messages/v1/send-message'); // Might need to hardcode site_url if not running inside WP
$body = [
    'sender_id'  => $wp_from_id,
    'recipients' => [$wp_to_id],
    'message'    => $message
];
if ($media_url) $body['attachment'] = $media_url;

// Send message
$response = wp_remote_post($wp_api_url, [
    'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
    'body'    => $body,
    'timeout' => 15
]);

if (is_wp_error($response)) {
    log_sync_event("WP REST error: " . $response->get_error_message());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'WP REST error']);
    exit;
}
$code = wp_remote_retrieve_response_code($response);

if ($code !== 200) {
    log_sync_event("WP REST failed: code=$code; body=" . wp_remote_retrieve_body($response));
    http_response_code($code);
    echo json_encode(['status' => 'error', 'error' => 'WP REST failed']);
    exit;
}

// Log and respond
log_sync_event("Synced WW($ww_from_id → $ww_to_id) to WP($wp_from_id → $wp_to_id): $message");
echo json_encode(['status' => 'success', 'code' => $code]);
exit;
?>