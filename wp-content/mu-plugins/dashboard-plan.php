<?php
/**
 * Plugin Name: Buzzjuice Dashboard Plan Widget
 * Description: Shortcode [bz_dashboard_plan] for showing user's current plan and expiry date.
 * Author: Buzzjuice
 * Version: 1.0
 */

// Register the shortcode
add_action('init', function() {
    add_shortcode('bz_dashboard_plan', 'bz_dashboard_plan_shortcode');
});

function bz_dashboard_plan_shortcode() {
    if ( ! is_user_logged_in() ) return '';

    $user = wp_get_current_user();
    $roles = (array) $user->roles;

    // Plan map - add/adjust as appropriate for your system & naming
    $plan_roles = [
        'classic_lifestyle'   => 'Classic Lifestye Subscription',
        'silver_lifestyle'    => 'Silver Lifestye Subscription',
        'rockstar_lifestyle'  => 'Rockstar Lifestye Subscription',
        'premium_lifestyle'   => 'Premium Lifestye Subscription',
        'jewel_affiliate'     => 'Jewel Affiliate Subscription'
    ];
    $plan_label = 'No active subscription plan';
    foreach( $plan_roles as $role => $label ) {
        if ( in_array($role, $roles) ) {
            $plan_label = $label;
            break;
        }
    }

    // Get expiry date from WooCommerce Memberships or custom user meta if needed
    $expiry = '';
    if ( function_exists('wc_memberships_get_user_active_memberships') ) {
        $memberships = wc_memberships_get_user_active_memberships($user->ID);
        if ($memberships && !empty($memberships[0])) {
            $end_date = $memberships[0]->get_end_date('j F, Y');
            if ($end_date && strtolower($end_date) !== 'unlimited') {
                $expiry = $end_date;
            }
        }
    }

    // Output block, including CSS for the widget style
    ob_start(); ?>
    <div class="bz-plan-widget">
        <span class="bz-plan-label"><?php echo esc_html($plan_label); ?></span>
        <?php if ($expiry): ?>
            <span class="bz-plan-expiry">(Expiry: <?php echo esc_html($expiry); ?>)</span>
        <?php endif; ?>
    </div>
    <style>
    .bz-plan-widget {
        margin:8px 0 0 0;
        display:flex;
        align-items:center;
        gap:10px;
    }
    .bz-plan-label {
        font-size:14px;
        font-weight:600;
        color:#2764a4;
        background:#eceaff;
        border-radius:5px;
        padding:2px 14px;
        letter-spacing:0.5px;
        display:inline-block;
        text-align: -webkit-center;
    }
    .bz-plan-expiry {
        font-size:13px;
        color:#725b10;
        font-style:italic;
        background: #fff3ce;
        border-radius:4px;
        padding:2px 8px;
        margin-left:5px;
        display:inline-block;
    }
    </style>
    <?php
    return ob_get_clean();
}