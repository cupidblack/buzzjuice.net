<?php

if (!defined('ABSPATH')) exit;

/**
 * Returns the Variable Product ID for Palmier Points packages.
 * Set this to your actual WooCommerce product ID!
 */
function bz_get_palmier_product_id() {
    return 9286; // <-- CHANGE THIS TO YOUR ACTUAL PALMIER PRODUCT ID!
}



/**
 * Get Palmier Points balance from myCRED for a user.
 */
function bz_get_palmier_balance($user_id = 0) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id || !function_exists('mycred_get_users_balance')) return 0;
    return (float) mycred_get_users_balance($user_id, 'mycred_default');
}

/**
 * Generic paywall: true if user has >= X Palmier points.
 * Extendable to trigger modal in any plugin/theme code.
 */
function bz_require_points($min_points = 1, $user_id = 0) {
    return bz_get_palmier_balance($user_id) >= $min_points;
}

