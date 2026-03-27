<?php
if (!defined('ABSPATH')) exit;
add_shortcode('bj_sidebar_left', function () {
    ob_start();
    include BJ_DASH_PATH . 'components/sidebar-left.php';
    return ob_get_clean();
});