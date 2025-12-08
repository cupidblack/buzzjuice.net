<?php
/**
 * Helpers for handling and synchronizing media, attachments, and voice messages
 * between WoWonder and WordPress/BuddyBoss/BPBM.
 *
 * Ensures URLs are preserved and correctly referenced cross-platform.
 */

/**
 * Converts media URLs between WoWonder and WordPress conventions, if needed.
 * By default, leaves URLs as absolute when syncing between systems.
 * @param string $messageBody
 * @param string $direction  'ww_to_wp' or 'wp_to_ww'
 * @return string
 */
if (!function_exists('convert_media_links')) {
    function convert_media_links($messageBody, $direction = 'ww_to_wp') {
        // If you ever need to convert relative paths, handle here.
        // For now, just return the messageBody unchanged, as both platforms use absolute URLs.
        return $messageBody;
    }
}

/**
 * Extracts the first media/attachment URL from a message (if present).
 * Used for syncing voice notes, files, images, etc.
 * @param string $messageBody
 * @return string|null
 */
if (!function_exists('get_attachment_url_from_message')) {
    function get_attachment_url_from_message($messageBody) {
        // Simple regex to match URLs (http/https)
        if (preg_match('/https?:\/\/[^\s"\']+/i', $messageBody, $matches)) {
            return $matches[0];
        }
        return null;
    }
}

/**
 * Optionally: Emoji/special character conversion helpers.
 * Extend as needed for platform-specific emoji mapping.
 */
if (!function_exists('convert_emojis')) {
    /**
     * Example stub for handling emoji conversion for cross-platform compatibility.
     * @param string $messageBody
     * @param string $direction  'ww_to_wp' or 'wp_to_ww'
     * @return string
     */
    function convert_emojis($messageBody, $direction = 'ww_to_wp') {
        // For now, pass through. Extend this to map shortcodes or unicode if needed.
        return $messageBody;
    }
}
?>