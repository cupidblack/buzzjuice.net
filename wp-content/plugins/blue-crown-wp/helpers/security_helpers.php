<?php
/**
 * Security and robustness helpers for syncing between WoWonder and WordPress/BuddyBoss/BPBM.
 * Apply these in your REST endpoints and AJAX handlers.
 */

/**
 * Only allow sync if BuddyBoss/BuddyPress is active.
 */
if (!function_exists('is_bp_platform_active')) {
    function is_bp_platform_active() {
        return function_exists('bp_is_active') && bp_is_active('messages');
    }
}

/**
 * Sanitize and validate all incoming REST fields.
 */
if (!function_exists('sanitize_sync_fields')) {
    function sanitize_sync_fields($data) {
        return [
            'from_id'   => isset($data['from_id']) ? intval($data['from_id']) : 0,
            'to_id'     => isset($data['to_id']) ? intval($data['to_id']) : 0,
            'message'   => isset($data['message']) ? sanitize_text_field($data['message']) : '',
            'media_url' => isset($data['media_url']) ? esc_url_raw($data['media_url']) : null
        ];
    }
}

/**
 * Enforce REST nonces for frontend JS requests (optional, for extra security).
 */
if (!function_exists('verify_sync_nonce')) {
    function verify_sync_nonce($request) {
        if (!isset($_SERVER['HTTP_X_WP_NONCE'])) return false;
        return wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'wp_rest');
    }
}

/**
 * .htaccess file content for /data/sync_messages/ to deny direct web access except for PHP scripts.
 */
if (!function_exists('get_sync_data_htaccess')) {
    function get_sync_data_htaccess() {
        return <<<HTACCESS
# Deny all access except PHP
<FilesMatch "\.php$">
  Order allow,deny
  Allow from all
</FilesMatch>
Deny from all
HTACCESS;
    }
}