<?php
/**
 * Mu-plugin: Register BuddyBoss/BuddyPress missing JS dependency handles.
 * Prevents "not registered" dependency errors for custom/broken enqueue calls.
 */
add_action('wp_enqueue_scripts', function() {
    $missing_handles = [
        'bp-widget-members',
        'bp-jquery-query',
        'bp-jquery-cookie',
        'bp-jquery-scroll-to',
    ];
    foreach ($missing_handles as $handle) {
        if (!wp_script_is($handle, 'registered')) {
            // Register a dummy, empty JS script so dependency chain is satisfied
            wp_register_script($handle, '');
        }
    }
}, 1); // Priority 1: before most scripts, so handles are ready
