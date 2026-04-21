<?php
/**
 * MU Plugin: Register BuddyBoss/BuddyPress missing JS dependency handles EARLY.
 * Prevents "not registered" dependency errors due to stricter WP script validation.
 */
add_action('muplugins_loaded', function () {
    if (!class_exists('WP_Scripts')) require_once ABSPATH . WPINC . '/class.wp-scripts.php';
    global $wp_scripts;
    if (empty($wp_scripts) || !($wp_scripts instanceof WP_Scripts)) {
        $wp_scripts = new WP_Scripts();
    }

    $handles = [
        'bp-widget-members',
        'bp-jquery-query',
        'bp-jquery-cookie',
        'bp-jquery-scroll-to',
        'bp-media-dropzone', // <-- Add this!
    ];
    foreach ($handles as $handle) {
        if (!isset($wp_scripts->registered[$handle])) {
            $wp_scripts->add($handle, '', [], null);
        }
    }
}, -10000); // Very high priority to run as early as possible // Priority 1: before most scripts, so handles are ready
