<?php
if (!defined('ABSPATH')) exit;

function bj_user_type() {
    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    if (in_array('jewel_affiliate', $roles)) return 'affiliate';
    if (array_intersect(['classic_lifestyle', 'silver_lifestyle', 'rockstar_lifestyle', 'premium_lifestyle'], $roles)) return 'primary';
    return 'general';
}

function bj_gate($type, $callback) {
    $user_type = bj_user_type();
    if ($type === 'affiliate' && $user_type !== 'affiliate') {
        echo '<div class="bj-locked-box">Become an affiliate to view full affiliate stats</div>';
        return;
    }
    if ($type === 'primary' && $user_type === 'general') {
        echo '<div class="bj-locked-box">Activate a subscription to view your lifestyle matches</div>';
        return;
    }
    $callback();
}