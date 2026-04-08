<?php
/**
 * Plugin Name: Buzzjuice Dashboard Quick Stats Panel
 * Description: [bz_dashboard_quickstats] — Wallet, points (WoWonder), referral link, earnings/estimate.
 * Author: Buzzjuice
 */

if (!defined('ABSPATH')) exit;

// Load bridge for WoWonder DB
$bz_bridge = ABSPATH . 'shared/wwqd_bridge.php';
if (file_exists($bz_bridge)) require_once $bz_bridge;

add_action('init', function() {
    add_shortcode('bz_dashboard_quickstats', 'bz_dashboard_quickstats_shortcode');
});

function bz_dashboard_quickstats_shortcode() {
    if (!is_user_logged_in()) return '';

    $conn = get_wowonder_db();;
    $user = wp_get_current_user();
    $username = $user->user_login;
    $roles = (array) $user->roles;
    $is_affiliate = in_array('jewel_affiliate', $roles);

    // ----- WOWONDER DATA -----
    $wallet = 0; $points = 0; $referrals = 0;
    if (isset($conn)) {
        $query = mysqli_query($conn, "SELECT wallet, points, referrer FROM Wo_Users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1");
        if ($query && mysqli_num_rows($query)) {
            $row = mysqli_fetch_assoc($query);
            $wallet    = isset($row['wallet']) ? (float) $row['wallet'] : 0;
            $points    = isset($row['points']) ? (int) $row['points'] : 0;
            $referrals = isset($row['referrer']) ? (int) $row['referrer'] : 0;
        }
    }

    // ----- REFERRAL LINK -----
    if ($is_affiliate && shortcode_exists('affiliate_referral_url')) {
        $ref_link = strip_tags(do_shortcode('[affiliate_referral_url format="username"]'));
    } else {
        $ref_link = site_url('/register?ref=' . $username);
    }

    // ----- AFFILIATEWP DATA -----
    $affiliate_earnings = '0';
    $affiliate_referrals = $referrals;
    if ($is_affiliate && function_exists('affwp_get_affiliate_id')) {
        $aff_id = affwp_get_affiliate_id($user->ID);
        if ($aff_id && class_exists('Affiliate_WP_Referrals_DB')) {
            $ref_db = new Affiliate_WP_Referrals_DB();
            $affiliate_referrals = (int)$ref_db->count(['affiliate_id' => $aff_id]);
        }
        if (shortcode_exists('affiliate_earnings')) {
            $earn = strip_tags(do_shortcode('[affiliate_earnings status="unpaid"]'));
            if ($earn !== '') $affiliate_earnings = $earn;
        }
    }

    // ----- NON-AFFILIATE ESTIMATE -----
    $est_ghc = !$is_affiliate ? $referrals * 5 : 0;
    $est_points = !$is_affiliate ? $referrals * 50 : 0;

    ob_start(); ?>
    <div class="bzqs">
        <div class="bzqs-row">
            <span>Wallet</span><strong>GHS <?php echo number_format($wallet,2); ?></strong>
        </div>
        <div class="bzqs-row">
            <span>Points</span><strong><?php echo number_format($points); ?></strong>
        </div>
        
        
        
        
        <?php if ($is_affiliate): ?>
        <div class="bzqs-row">
            <span>Referrals</span><strong><?php echo number_format($referrals); ?></strong>
        </div>
        <div class="bzqs-link">
            <input type="text" id="bzqs-link" value="<?php echo esc_attr($ref_link); ?>" readonly>
            <button onclick="bzqsCopy()">Copy</button>
        </div>
            <div class="bzqs-aff-hz">
                <span>Earnings: <strong><?php echo $affiliate_earnings; ?></strong></span>
                <span>Referrals: <strong><?php echo $affiliate_referrals; ?></strong></span>
            </div>
        <?php endif; ?>
        
        
        
        
        
    </div>
    <style>
    .bzqs { background:#fff; border:1px solid #D6D9DD; border-radius:10px; padding:12px; margin-top: 5px;margin-bottom: 5px}
    .bzqs-row { display:flex; justify-content:space-between; font-size:12px; margin-bottom:5px;}
    .bzqs-row span { color:#888;}
    .bzqs-row strong { color:#23272a; font-weight:600;}
    .bzqs-link { display:flex; align-items:center; gap:6px; margin:8px 0;}
    .bzqs-link input { flex:1; border-radius:4px; padding:3px 7px; font-size:12px;max-width: 72%;height:auto;}
    .bzqs-link button { background:#385DFF; border:none; font-size:12px!important; border-radius:4px; padding:3px 9px; cursor:pointer; }
    .bzqs-est { background:#fff7e0; color:#754600; border-radius:5px; margin-top:8px; padding:6px 10px; font-size:12px; }
    .bzqs-aff-hz { display:flex; justify-content:space-between; border-top:1px solid #fae57a; margin-top:8px; padding-top:8px; font-size:12px;}
    @media (max-width:650px){
        .bzqs-row { flex-direction:row; gap:2px;}
        .bzqs-aff-hz { flex-direction:column; gap:2px;}
        .bzqs-link { flex-direction:row; }
    }
    </style>
    <script>
    function bzqsCopy(){
        var el = document.getElementById("bzqs-link");
        el.select();
        el.setSelectionRange(0,99999);
        document.execCommand('copy');
    }
    function bzqsShare(){
        var link = document.getElementById("bzqs-link").value;
        if (navigator.share) {
            navigator.share({
                title: "Join me on Buzzjuice",
                text: "Sign up using my invite link:",
                url: link
            });
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(link);
            alert("Link copied! You can paste it anywhere.");
        } else {
            var el = document.getElementById("bzqs-link");
            el.select();
            document.execCommand('copy');
            alert("Link copied! You can paste it anywhere.");
        }
    }
    </script>
    <?php
    return ob_get_clean();
}