<?php
/*
Plugin Name: Buzzjuice Dashboard
Description: Unified dashboard for Buzzjuice.net integrating BuddyBoss and Elementor.
Version: 0.1
Author: pearbuzzjuice
*/
if (!defined('ABSPATH')) exit;
define('BJ_DASH_PATH', plugin_dir_path(__FILE__));
define('BJ_DASH_URL', plugin_dir_url(__FILE__));

// Load helpers
require_once BJ_DASH_PATH . 'helpers/gate.php';
require_once BJ_DASH_PATH . 'helpers/utils.php';

// Load components
foreach (glob(BJ_DASH_PATH . 'components/*.php') as $file) {
    require_once $file;
}

// Load shortcodes
foreach (glob(BJ_DASH_PATH . 'shortcodes/*.php') as $file) {
    require_once $file;
}

// Enqueue dashboard assets on dashboard page only
add_action('wp_enqueue_scripts', function () {
    if (is_page('dashboard')) {
        wp_enqueue_style('bj-dashboard', BJ_DASH_URL . 'assets/css/dashboard.css', [], '0.1');
        wp_enqueue_style('bj-sidebar-left', BJ_DASH_URL . 'assets/css/sidebar-left.css', [], '0.1');
        wp_enqueue_style('bj-sidebar-right', BJ_DASH_URL . 'assets/css/sidebar-right.css', [], '0.1');
        wp_enqueue_style('bj-menu-icons', BJ_DASH_URL . 'assets/css/menu-icons.css', [], '0.1');
        wp_enqueue_script('bj-dashboard', BJ_DASH_URL . 'assets/js/dashboard.js', ['jquery'], '0.1', true);
    }
});