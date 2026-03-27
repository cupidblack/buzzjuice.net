<?php
if (!defined('ABSPATH')) exit;

function bj_get_user_meta_safe($user_id, $key, $default = '') {
    $v = get_user_meta($user_id, $key, true);
    return (!empty($v)) ? $v : $default;
}
function bj_currency($amount) {
    return '₵' . number_format((float)$amount, 2);
}