<?php
/**
 * Plugin Name: Fix WidgetOpts Submenu & Prevent Fatal (Buzzjuice)
 * Description: Prevents invalid submenu registration in WidgetOpts under PHP 8
 */

// The plugin calls at priority 11, so we need to remove its action before that
add_action('admin_menu', function() {
    // Remove the buggy submenu registration BEFORE it fires
    if (class_exists('WidgetOpts_Snippets_Admin')) {
        remove_action(
            'admin_menu',
            array('WidgetOpts_Snippets_Admin', 'add_admin_menu'),
            11
        );
    }
}, 10);

// Then, re-add our safe, redirecting submenu at normal priority 100
add_action('admin_menu', function() {
    add_submenu_page(
        'options-general.php',
        __('Widget Options Snippets', 'widget-options'),
        __('Widget Options Snippets', 'widget-options'),
        'manage_options',
        'widgetopts_snippets_safe',
        function() {
            // Redirect to the intended URL
            $slug = 'edit.php?post_type=widgetopts_snippet';
            $url = admin_url($slug);
            echo '<script>window.location = ' . json_encode($url) . ';</script>';
            echo '<p><a href="' . esc_url($url) . '">Click here if not redirected.</a></p>';
        }
    );
}, 100);