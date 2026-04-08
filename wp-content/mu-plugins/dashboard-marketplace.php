<?php
/**
 * Plugin Name: Buzzjuice Dashboard Marketplace Snapshot
 * Description: [bz_marketplace_snapshot] — WoWonder marketplace quick access, summary, and actions
 */
if (!defined('ABSPATH')) exit;

// load WoWonder DB bridge
$bz_bridge = ABSPATH . 'shared/wwqd_bridge.php';
if (file_exists($bz_bridge)) require_once $bz_bridge;

add_action('init', function() {
    add_shortcode('bz_marketplace_snapshot', 'bz_marketplace_snapshot_shortcode');
});

function bz_marketplace_snapshot_shortcode() {
    if (!is_user_logged_in()) return '';
    $conn = get_wowonder_db();
    if (empty($conn)) return '<div class="bz-market-error">Marketplace unavailable</div>';
    $conn = $conn;
    $wp_user = wp_get_current_user();
    $username = mysqli_real_escape_string($conn, $wp_user->user_login);

    // Get WoWonder user_id for this WP username
    $user_id_ww = 0;
    $q_user = mysqli_query($conn, "SELECT user_id FROM Wo_Users WHERE username = '$username' LIMIT 1");
    if ($q_user && mysqli_num_rows($q_user)) {
        $user_id_ww = (int)mysqli_fetch_assoc($q_user)['user_id'];
    }
    if (!$user_id_ww) return '<div class="bz-market-error">User not synced</div>';

    // -- TOP PRODUCT (by views recency)
    $product = null;
    $q_product = mysqli_query($conn,"SELECT * FROM Wo_Products WHERE user_id = $user_id_ww ORDER BY views DESC, time DESC LIMIT 1");
    if ($q_product && mysqli_num_rows($q_product)) {
        $p = mysqli_fetch_assoc($q_product);
        $img = '';
        if (!empty($p['images'])) {
            $imgs = json_decode($p['images'], true);
            if (is_array($imgs) && !empty($imgs[0]['image_org'])) $img = $imgs[0]['image_org'];
        }
        $product = [
            'id'     => $p['id'],
            'title'  => $p['name'],
            'price'  => $p['price_format'],
            'views'  => (int)$p['views'],
            'img'    => $img ?: 'https://via.placeholder.com/50x50?text=P',
            'url'    => '/streams/products/'.$p['id']
        ];
    }
    // -- LATEST ORDER (SELLER)
    $order = null;
    $q_order = mysqli_query($conn,"SELECT * FROM Wo_UserOrders WHERE product_owner_id = $user_id_ww ORDER BY id DESC LIMIT 1");
    if ($q_order && mysqli_num_rows($q_order)) {
        $o = mysqli_fetch_assoc($q_order);
        $order = [
            'id'     => $o['hash_id'],
            'status' => ucfirst($o['status']),
            'url'    => '/streams/order/' . $o['hash_id']
        ];
    }
    // -- LATEST PURCHASE (BUYER)
    $purchase = null;
    $q_purchase = mysqli_query($conn, "SELECT * FROM Wo_UserOrders WHERE user_id = $user_id_ww ORDER BY id DESC LIMIT 1");
    if ($q_purchase && mysqli_num_rows($q_purchase)) {
        $p = mysqli_fetch_assoc($q_purchase);
        $purchase = [
            'title'  => $p['product_name'],
            'status' => ucfirst($p['status']),
            'url'    => '/streams/order/' . $p['hash_id']
        ];
    }
    ob_start(); ?>
<div class="bz-market">
    <div class="bz-mkt-title">MARKETPLACE SNAPSHOT</div>
    <?php if ($product): ?>
        <div class="bz-mkt-section">
            <div class="bz-mkt-label">Your Top Product</div>
            <div class="bz-mkt-prod">
                <img src="<?php echo esc_url($product['img']); ?>" alt="">
                <div class="bz-mkt-meta">
                    <div class="bz-mkt-prod-title"><?php echo esc_html($product['title']); ?></div>
                    <div class="bz-mkt-prod-meta">
                        <?php echo $product['price']; ?> | Views: <?php echo $product['views']; ?>
                    </div>
                    <a href="<?php echo esc_url($product['url']); ?>">View Product →</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($order): ?>
        <div class="bz-mkt-section">
            <div class="bz-mkt-label">Latest Order</div>
            <div class="bz-mkt-order-row">
                <span>Order #<?php echo esc_html($order['id']); ?></span>
                <span class="bz-mkt-status"><?php echo esc_html($order['status']); ?></span>
            </div>
            <a href="<?php echo esc_url($order['url']); ?>">Manage Order →</a>
        </div>
    <?php endif; ?>
    <?php if ($purchase): ?>
        <div class="bz-mkt-section">
            <div class="bz-mkt-label">Your Latest Purchase</div>
            <div class="bz-mkt-order-row">
                <span><?php echo esc_html($purchase['title']); ?></span>
                <span class="bz-mkt-status"><?php echo esc_html($purchase['status']); ?></span>
            </div>
            <a href="<?php echo esc_url($purchase['url']); ?>">View Details →</a>
        </div>
    <?php endif; ?>
    <div class="bz-mkt-actions">
        <a href="/streams/my-products" class="bz-mkt-btn">Sell Product</a>
        <a href="/streams/orders" class="bz-mkt-btn alt">My Orders</a>
    </div>
    <div class="bz-mkt-footer">
        <a href="/streams/products">Go to Marketplace →</a>
    </div>
</div>
<style>
.bz-market {background:#fff;border:1px solid #D6D9DD;border-radius:10px;padding:12px;margin-top:5px;margin-top:5px;}
.bz-mkt-title {font-size:14px;font-weight:600;color:#3e6cb8;margin-bottom:5px;letter-spacing:1.1px;}
.bz-mkt-section {margin-bottom:11px;}
.bz-mkt-label {font-size:12.2px;color:#414b5f;font-weight:600;margin-bottom:2px;}
.bz-mkt-prod {display:flex;gap:8px;}
.bz-mkt-prod img {width:50px;height:50px;border-radius:6px;object-fit:cover;}
.bz-mkt-meta{flex:1;}
.bz-mkt-prod-title{font-weight:600;font-size:13px;color:#2a405e;}
.bz-mkt-prod-meta{font-size:12px;color:#6d6d6d;}
.bz-mkt-order-row{display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:5px;}
.bz-mkt-status{background:#eef2ff;padding:2px 6px;border-radius:5px;font-size:11px;}
.bz-mkt-actions{display:flex;gap:6px;margin-top:10px;}
.bz-mkt-btn{flex:1;text-align:center;background:#f1f3f7;color:#fff;padding:6px;border-radius:5px;font-size:12px;text-decoration:none;}
.bz-mkt-btn.alt{background:#f1f3f7;color:#333;}
.bz-mkt-footer{margin-top:10px;text-align:center;}
.bz-mkt-footer a{font-size:12px;color:#3e6cb8;text-decoration:underline;}
@media (max-width:650px){.bz-mkt-prod{flex-direction:column;}.bz-mkt-order-row{flex-direction:column;}.bz-mkt-actions{flex-direction:column;}}
</style>
<?php
    return ob_get_clean();
}