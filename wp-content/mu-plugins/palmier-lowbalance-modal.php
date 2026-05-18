<?php
if (!defined('ABSPATH')) exit;
require_once ABSPATH . 'shared/palmier/palmier-helpers.php';

// Renders the modal in the page footer, globally present for logged-in users.
add_action('wp_footer', 'bz_render_lowbalance_modal');
function bz_render_lowbalance_modal() {
    if (!is_user_logged_in() || !class_exists('WooCommerce')) return;
    $product_id = bz_get_palmier_product_id();
    if (!$product_id) return;
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) return;
    $variations = $product->get_available_variations();
    ?>

    <!-- Palmier Points Modal Overlay & Block -->
    <div id="bz-palmier-overlay" style="display:none;"></div>
    <div id="bz-palmier-modal" style="display:none;">
        <div class="bzpm-header">
            <span>BUY PALMIER POINTS</span>
            <button type="button" onclick="bzClosePalmierModal()" aria-label="Close Modal">&times;</button>
        </div>
        <div class="bzpm-body">
            <?php foreach ($variations as $var): 
                $variation_id = $var['variation_id'];
                $variation = wc_get_product($variation_id);
                if (!$variation) continue;
                // Grab first attribute as label:
                $attributes = $variation->get_attributes();
                $label = reset($attributes);
                $img_id = $variation->get_image_id();
                $img = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : wc_placeholder_img_src();
                $price = $variation->get_price_html();
            ?>
                <a class="bzpm-product" href="<?php echo esc_url(add_query_arg('add-to-cart', $variation_id, wc_get_checkout_url())); ?>">
                    <div class="bzpm-left">
                        <img src="<?php echo esc_url($img); ?>" alt="Palmier">
                        <div class="bzpm-meta">
                            <strong><?php echo esc_html($label); ?></strong>
                            <span><?php echo wp_kses_post($price); ?></span>
                        </div>
                    </div>
                    <div class="bzpm-buy">Buy</div>
                </a>
            <?php endforeach; ?>
            <a class="bzpm-account-link" href="<?php echo esc_url(site_url('/dashboard/palmiers/')); ?>">Tap here to view your points account</a>
        </div>
    </div>
    <style>
    #bz-palmier-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999998;backdrop-filter:blur(2.5px);}
    #bz-palmier-modal{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:97%;max-width:410px;background:#fff;border-radius:18px;overflow:hidden;z-index:999999;box-shadow:0 20px 60px rgba(0,0,0,.21);}
    .bzpm-header{display:flex;justify-content:space-between;align-items:center;padding:0 30px;border-bottom:1px solid #eee;font-weight:700;font-size:15px;}
    .bzpm-header button{border:none;background:none;font-size:28px;cursor:pointer;line-height:1;}
    .bzpm-body{padding:0 20px;max-height:65vh;overflow:auto;}
    .bzpm-product{display:flex;justify-content:space-between;align-items:center;gap:13px;padding:8px 0;border-bottom:1px solid #f0f0f0;text-decoration:none;color:#111;}
    .bzpm-left{display:flex;align-items:center;gap:15px;}
    .bzpm-left img{width:44px;height:44px;border-radius:9px;object-fit:cover;background:#f4f4f4;}
    .bzpm-meta{display:flex;flex-direction:column;gap:2px;}
    .bzpm-meta strong{font-size:14px;}
    .bzpm-meta span{font-size:12px;color:#615f5f;}
    .bzpm-buy{background:#385DFF;color:#fff;border-radius:8px;padding:6px 13px;font-size:12px;font-weight:600;}
    .bzpm-account-link{display:block;text-align:center;margin:10px;font-size:17px;font-weight:600;text-decoration:none;color:#385DFF;}
    </style>
    <script>
    function bzOpenPalmierModal() {
        document.getElementById('bz-palmier-overlay').style.display='block';
        document.getElementById('bz-palmier-modal').style.display='block';
        document.body.style.overflow='hidden';
    }
    function bzClosePalmierModal() {
        document.getElementById('bz-palmier-overlay').style.display='none';
        document.getElementById('bz-palmier-modal').style.display='none';
        document.body.style.overflow='';
    }
    document.addEventListener('click',function(e){
        if(e.target.id==='bz-palmier-overlay') bzClosePalmierModal();
    });
    document.addEventListener('keydown',function(e){
        if(e.key==='Escape') bzClosePalmierModal();
    });
    </script>
    <?php
}