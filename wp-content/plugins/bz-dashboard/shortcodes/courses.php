<?php
if (!defined('ABSPATH')) exit;
add_shortcode('bj_profile_card', function () {
    ob_start();
    include plugin_dir_path(__FILE__) . '../components/courses.php';
    return ob_get_clean();
});