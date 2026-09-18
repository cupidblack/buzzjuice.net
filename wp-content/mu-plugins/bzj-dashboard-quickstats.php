<?php
/**
 * Plugin Name: Buzzjuice Dashboard Quick Stats Panel
 * Description: [bz_dashboard_quickstats] — Wallet (WoWonder), Points (mycred), left sidebar status panel.
 */

if (!defined('ABSPATH')) exit;

require_once ABSPATH . 'shared/wwqd_bridge.php'; // for get_wowonder_db()
require_once ABSPATH . 'shared/palmier/palmier-helpers.php';
require_once ABSPATH . 'wp-content/mu-plugins/palmier-lowbalance-modal.php';

add_action('init', function() {
    add_shortcode('bz_dashboard_quickstats', 'bz_dashboard_quickstats_shortcode');
});

function bz_dashboard_quickstats_shortcode() {
    if (!is_user_logged_in()) return '';
    $user      = wp_get_current_user();
    $user_id   = $user->ID;
    $username  = $user->user_login;

    // Fetch wallet from WoWonder
    $wallet = 0;
    $cache_key = 'bzqs_wallet_' . $user_id;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        $wallet = $cached;
    } else {
        $conn = get_wowonder_db();
        if ($conn) {
            $query = mysqli_query($conn, "SELECT wallet FROM Wo_Users WHERE username='" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1");
            if ($query && mysqli_num_rows($query)) {
                $row = mysqli_fetch_assoc($query);
                $wallet = isset($row['wallet']) ? (float)$row['wallet'] : 0;
            }
        }
        set_transient($cache_key, $wallet, 60);
    }

    // Get Palmier points from mycred_default
    $points = bz_get_palmier_balance($user_id);
    
    // Plan map - add/adjust as appropriate for your system & naming
    $roles = (array) $user->roles;
    $plan_roles = [
        'classic_lifestyle'   => 'Classic Lifestye Subscription',
        'silver_lifestyle'    => 'Silver Lifestye Subscription',
        'rockstar_lifestyle'  => 'Rockstar Lifestye Subscription',
        'premium_lifestyle'   => 'Premium Lifestye Subscription',
        'jewel_affiliate'     => 'Jewel Affiliate Subscription'
    ];
    $plan_label = 'No active subscription';
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

    ob_start();
    ?>
    <div class="bzqs">

<!--    <div class="bzqs-title">Quick Stats</div> -->

        <!-- PALMIERS -->
        <a class="bzqs-card" 
            onclick="bzOpenPalmierModal()"  href="#">
                <div class="bzqs-label">₱almiers</div>
                <div class="bzqs-value"><?php echo '₱' . number_format($points); ?></div>
        </a>

        <!-- WALLET
        <a class="bzqs-card"
           onclick="bzOpenPalmierModal()"  href="#">
            <div class="bzqs-label">Wallet</div>
            <div class="bzqs-value">GHS <?php // echo number_format($wallet, 2); ?></div>
        </a>  -->

        <?php if ($points <= 0): ?>
        <div class="bzqs-warning">
            <button type="button" onclick="bzOpenPalmierModal()" tabindex="0">Low balance. Add Points</button>
        </div>
        <?php endif; ?>
        
        <span class="bz-plan-label"><?php echo esc_html($plan_label); ?></span>
        <?php if ($expiry): ?>
            <span class="bz-plan-expiry">(Expiry: <?php echo esc_html($expiry); ?>)</span>
        <?php endif; ?>

    </div>

    <style>
    .bzqs {
        background: #fff;
        margin-bottom: 10px;
        text-align: -webkit-center;
    }
    .bzqs-title{font-size:15px;font-weight:700;margin-bottom:14px;}
    .bzqs-card{display:flex;justify-content:space-between;align-items:center;padding:14px;border-radius:16px;background:#f8f9fc;margin-bottom:10px;text-decoration:none;color:#111;transition:.2s;}
    .bzqs-card:hover{background:#eef2ff;}
    .bzqs-label{font-size:13px;color:#666;}
    .bzqs-value{font-size:16px;font-weight:700;}
    .bzqs-warning{margin-top:-2px;margin-bottom:14px;}
    .bzqs-warning button{border:none;background:none;padding:0;color:#c0392b;font-size:12px;font-weight:600;cursor:pointer;}
    .bzqs-section-title{margin-top:16px;margin-bottom:10px;font-size:12px;font-weight:700;text-transform:uppercase;color:#666;}
    .bzqs-actions{display:grid;grid-template-columns:1fr;gap:8px;}
    .bzqs-actions button, .bzqs-actions a{border:none;background:#385DFF;color:#fff;border-radius:12px;padding:11px 14px;text-decoration:none;cursor:pointer;font-size:13px;text-align:center;}
    @media(max-width:650px){.bzqs{border-radius:14px;}}
    
    .bz-plan-widget {
        margin:5px 0 5px 0;
        border:1px solid #D6D9DD;
        border-radius:10px;
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