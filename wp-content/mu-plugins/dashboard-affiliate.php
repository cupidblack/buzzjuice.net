<?php
/**
 * Plugin Name: Buzzjuice Dashboard Affiliate Panel
 * Description: [bz_dashboard_affiliate] â€” Unified Buzzjuice affiliate vision: multi-platform stats, tier projections, CTA, chart.
 */
 
 /* =======================
 ======== PROMPT===========
===========================

Reverting to the procession to BUILD LEFT SIDEBAR (AFFILIATE PANEL), consider the following development:

<<START DEVELOPMENT>>

Referencing the Koware Affiliate Program with the following attributes:              
              
Level 1: 37%              
Level 2: 40% of Level 1              
Level 3: 30% of Level 1              
Level 4: 20% of Level 1               
Level 5: 10% of Level 1              
              
The panel for non-affiliates would simulate the recruitment of 1 affiliate referral every fortnight (2 weeks, half month or 15 days) starting from their date of registration. The simulation assumes that the user receives a commission of 37% of the the annual price of the Jewel Affiliate WooCommerce product (ID 6778) per year for each direct referral at Level 1.

The simulation would be time based adding 1 simulated referral every fortnight. This simulation also assumes that the simulated recruited referrals also recruit 1 simulated referral every fortnight.

The affiliate panel should show how much the user would have earned up to date if they had activated a Jewel Affiliate subscription a fortnight after the date when they became a member.

Carefully consider the assumed simulated registration date for each subsequent affiliate referral from level 1 to level 5 to produce as much of an accurate estimated earnings for non-affiliate.

The affiliate referral link must only be available for active Jewel Affiliates with the jewel_affiliate role.

<<STOP DEVELOPMENT>>

Show the full wireframe structure of the full block section then continue with the required developments and code generations to create the full block section with all necessary widgets and elements.

Note the following:
1. WoWonder and QuickDate have their own affiliate systems that would be consolidated into a single affiliate system for unregistered affiliates where users would be able to see only their referral visits and an estimate of how much they would have earned if they activated an affiliate subscription.

<<WORDPRESS AFFILIATEWP RESOURCES>>
https://github.com/cupidblack/buzzjuice.net/tree/main/wp-content/plugins/affiliate-wp
https://github.com/cupidblack/buzzjuice.net/tree/main/wp-content/plugins/affiliate-wp-lifetime-commissions
https://github.com/cupidblack/buzzjuice.net/tree/main/wp-content/plugins/affiliatewp-affaliate-portal
https://github.com/cupidblack/buzzjuice.net/tree/main/wp-content/plugins/affiliatewp-multi-tier-commissions
https://github.com/cupidblack/buzzjuice.net/tree/main/wp-content/plugins/affiliate-wp-recurring-referrals

<<WOWONDER AFFILIATE AND REFERRAL RESOURCES>>
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/admin-panel/pages/affiliates-settings
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/admin-panel/pages/referrals-list
https://github.com/cupidblack/buzzjuice.net/blob/main/streams/themes/sunshine/layout/setting/affiliates.phtml

<<QUICKDATE AFFILIATE AND REFERRAL RESOURCES>>
https://github.com/cupidblack/buzzjuice.net/blob/main/social/admin-panel/pages/affiliates-settings
https://github.com/cupidblack/buzzjuice.net/blob/main/social/admin-panel/pages/referrals-list
https://github.com/cupidblack/buzzjuice.net/blob/main/social/themes/love/settings-affiliate.php
 
 */

/**
 * Plugin Name: Buzzjuice Dashboard Affiliate Panel (Koware Time Simulation)
 * Description: [bz_dashboard_affiliate] — Recursively simulates 5-level commission up to today, actual stats + referral link for active Jewel Affiliates.
 */

if (!defined('ABSPATH')) exit;

// Include bridge for wowonder etc
$bz_bridge = ABSPATH . 'shared/wwqd_bridge.php';
if (file_exists($bz_bridge)) require_once $bz_bridge;

add_action('init', function() {
    add_shortcode('bz_dashboard_affiliate', 'bz_dashboard_affiliate_shortcode');
});

function bz_dashboard_affiliate_shortcode() {
    if (!is_user_logged_in()) return '';

    global $wpdb, $wo;
    $user = wp_get_current_user();
    $username = $user->user_login;
    $roles = (array)$user->roles;
    $is_aff = in_array('jewel_affiliate', $roles);

    // 1. Get annual Jewel Affiliate price (WooCommerce product 6778)
    $product_price = 230;
    if (function_exists('wc_get_product')) {
        $prod = wc_get_product(6778);
        if ($prod) $product_price = (float)$prod->get_price();
    }

    // 2. Koware Commissions:
    $level_rate = [
      1 => 0.37,
      2 => 0.37 * 0.40,
      3 => 0.37 * 0.30,
      4 => 0.37 * 0.20,
      5 => 0.37 * 0.10,
    ];

    // 3. Simulate recursive referrals (non-affiliates only)
    $panel_html = '';
    if (!$is_aff) {
        $user_join = strtotime($user->user_registered);
        $now = time();
        // Only start simulating after user has been registered +15d
        $sim_start = $user_join + 15*24*3600;
        $fortnights = $now > $sim_start ? floor(($now - $sim_start) / (15*24*3600)) : 0;

        // Recursive tree simulation
        $counts = [1=>0,2=>0,3=>0,4=>0,5=>0];
        $nodes = [['level'=>1,'start'=>1]];
        for($f=1;$f<=$fortnights;$f++){
            $next = [];
            foreach($nodes as $node){
                if($f >= $node['start']){
                    $lvl = min($node['level'],5);
                    $counts[$lvl]++;
                    if($lvl < 5) $next[] = ['level'=>$lvl+1, 'start'=>$f+1];
                }
                $next[] = $node;
            }
            $nodes = $next;
        }
        $total = 0;
        $lines = '';
        foreach(range(1,5) as $lvl){
            $earn = $counts[$lvl] * $level_rate[$lvl] * $product_price;
            $lines .= "<div class='bz-aff-line'>
              <span>Level $lvl ({$counts[$lvl]} referral".($counts[$lvl]!=1?'s':'').")</span>
              <strong>GHS ".number_format($earn,2)."</strong>
            </div>";
            $total += $earn;
        }
        $panel_html = "
          <div class='bz-aff-status'>🟡 Not Activated</div>
          <div class='bz-aff-sim'>
            <div class='bz-aff-label'>🧠 Simulated Earnings</div>
            <div class='bz-aff-desc'>If you activated 15 days after joining (1 every 2 wks, recursive):</div>
            $lines
            <div class='bz-aff-total'>TOTAL Estimate: <strong>GHS ".number_format($total,2)."</strong></div>
          </div>
          <a href='/product/jewel-affiliate/' class='bz-aff-btn cta'>🚀 Activate Jewel Affiliate →</a>
          <div class='bz-aff-note'>
            * Estimates use recursive recruitment every 15 days after join (max 5 levels), per Koware affiliate plan logic.
          </div>
        ";
    } else {
        $earn = shortcode_exists('affiliate_earnings') ? strip_tags(do_shortcode('[affiliate_earnings status="unpaid"]')) : '';
        $refs = shortcode_exists('affiliate_referral_count') ? strip_tags(do_shortcode('[affiliate_referral_count]')) : '';
        $ref_link = shortcode_exists('affiliate_referral_url') ? strip_tags(do_shortcode('[affiliate_referral_url format="username"]')) : '';
        $panel_html = "
        <div class='bz-aff-status'>🟢 Active Jewel Affiliate</div>
        <div class='bz-aff-row'>
            <input type='text' id='bz-aff-link' value='".esc_attr($ref_link)."' readonly>
            <button onclick='bzAffCopy()'>Copy</button>
        </div>
        <div class='bz-aff-stats'>
            <span>Earnings: <strong>".($earn ?: 'GHS 0')."</strong></span>
            <span>Active Referrals: <strong>".($refs ?: '0')."</strong></span>
        </div>
        <a href='/affiliate-area/' class='bz-aff-btn'>View Dashboard →</a>
        ";
    }

    ob_start(); ?>
    <div class="bz-aff-panel">
      <div class="bz-aff-title">AFFILIATE PANEL</div>
      <?php echo $panel_html; ?>
    </div>
    <style>
    .bz-aff-panel {background:#fff;border:1px solid #e7eaf1;border-radius:10px;padding:12px;margin-top:16px;}
    .bz-aff-title {font-size:14px;font-weight:600;color:#3E6CB8;margin-bottom:7px;}
    .bz-aff-status {font-size:12px;margin-bottom:7px;}
    .bz-aff-row {display:flex;gap:6px;padding-top:3px;}
    .bz-aff-row input {flex:1;font-size:12px;}
    .bz-aff-row button {background:#f7d774;border:none;font-size:12px;border-radius:4px;padding:2px 7px;cursor:pointer;}
    .bz-aff-btn {display:inline-block;text-align:center;background:#531bb9;color:#fff;padding:6px 14px;border-radius:6px;font-size:13px;text-decoration:none;margin-top:9px;}
    .bz-aff-btn.cta {background:#f7d774;color:#222;}
    .bz-aff-sim {background:#eef2ff;padding:10px;border-radius:8px;margin-top:10px;}
    .bz-aff-label {font-size:12.5px;font-weight:600;color:#531bb9;}
    .bz-aff-desc {font-size:11.5px;color:#666;margin-bottom:3px;}
    .bz-aff-line {display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;}
    .bz-aff-total {margin-top:7px;font-size:13px;font-weight:600;color:#531bb9;}
    .bz-aff-note {font-size:10px;color:#777;margin-top:9px;}
    .bz-aff-stats {display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px;}
    @media (max-width:650px){.bz-aff-row,.bz-aff-stats{flex-direction:column;}}
    </style>
    <script>
    function bzAffCopy(){
        var el=document.getElementById("bz-aff-link");
        el.select();
        document.execCommand("copy");
    }
    </script>
    <?php
    return ob_get_clean();
}