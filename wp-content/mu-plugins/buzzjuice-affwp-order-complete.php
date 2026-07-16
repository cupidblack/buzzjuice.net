<?php
/*
Plugin Name: Buzzjuice AffiliateWP Order Completion Handler
Description: Process Streams orders for AffiliateWP referral creation
Version: 8.0
Author: Buzzjuice
*/

if (!defined('ABSPATH')) {
    exit;
}

// error_log('[AFFWP-MU] MU plugin loaded at ' . gmdate('Y-m-d H:i:s'));

// Load the bridge
$bridge_file = ABSPATH . 'wp-content/plugins/blue-crown-wp/wow-pgb_sync/wow-pgb_sync.php';
if (file_exists($bridge_file)) {
    require_once $bridge_file;
//    error_log('[AFFWP-MU] Bridge file loaded');
} else {
    error_log('[AFFWP-MU] ❌ Bridge file NOT found: ' . $bridge_file);
}

if (!function_exists('bluecrown_affiliatewp_post_checkout_verification')) {
    error_log('[AFFWP-MU] ❌ Bridge function NOT available after loading');
}

/**
 * SINGLE HOOK APPROACH (ChatGPT recommendation)
 * 
 * Only hook on 'completed' status since API orders are explicitly completed
 * Avoids race conditions and duplicate processing
 */
add_action('woocommerce_order_status_completed', function($order_id) {
    error_log("[AFFWP-MU] 🔵 woocommerce_order_status_completed: order_id=$order_id");
    
    if (!function_exists('bluecrown_affiliatewp_post_checkout_verification')) {
        error_log('[AFFWP-MU] ❌ Bridge function not callable');
        return;
    }

    try {
        bluecrown_affiliatewp_post_checkout_verification($order_id);
    } catch (Throwable $e) {
        error_log('[AFFWP-MU] ❌ Exception: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    }
}, 999, 1);

// error_log('[AFFWP-MU] Single hook registered on woocommerce_order_status_completed');