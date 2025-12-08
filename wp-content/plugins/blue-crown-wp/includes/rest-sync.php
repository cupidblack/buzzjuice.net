<?php
/**
 * REST API endpoint for syncing messages from WoWonder or other external sources.
 * This acts as a fallback handler for the Node.js server.
 * Supports media/attachments.
 */

add_action('rest_api_init', function () {
    register_rest_route('buddyboss-sync/v1', '/new-message', [
        'methods'             => 'POST',
        'callback'            => 'handle_new_sync_message',
        // For production, use a more secure check (API key, nonce, IP whitelist).
        'permission_callback' => '__return_true',
    ]);
});

/**
 * Handles the incoming message from the REST API request.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function handle_new_sync_message($request) {
    // Include the necessary helper for message insertion.
    require_once plugin_dir_path(__FILE__) . '../helpers/wp_insert_message.php';

    $params = $request->get_json_params();

    $from_id   = isset($params['from_id']) ? intval($params['from_id']) : 0;
    $to_id     = isset($params['to_id']) ? intval($params['to_id']) : 0;
    $message   = isset($params['message']) ? sanitize_text_field($params['message']) : '';
    $media_url = isset($params['media_url']) ? esc_url_raw($params['media_url']) : null;

    // Validate required params.
    if (empty($from_id) || empty($to_id) || empty($message)) {
        return new WP_REST_Response(['status' => 'error', 'message' => 'Missing required parameters.'], 400);
    }

    // Call the central sync function to handle the message insertion.
    $result = sync_message_to_wordpress($from_id, $to_id, $message, $media_url);

    if ($result) {
        return new WP_REST_Response(['status' => 'ok', 'message' => 'Message synced successfully.'], 200);
    } else {
        return new WP_REST_Response(['status' => 'fail', 'message' => 'Failed to sync message.'], 500);
    }
}
?>