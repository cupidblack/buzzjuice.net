<?php
/**
 * @snippet       Redirect to Checkout Upon Add to Cart - WooCommerce (Specific Products)
 * @how-to        businessbloomer.com/woocommerce-customization
 * @author        Rodolfo Melogli, Business Bloomer
 * @compatible    Woo 3.8+
 * @community     https://businessbloomer.com/club/
 */
/*
add_filter( 'woocommerce_add_to_cart_redirect', 'bbloomer_redirect_checkout_add_cart' );

function bbloomer_redirect_checkout_add_cart( $url ) {
    
    // List of product IDs to redirect
    $redirect_product_ids = array( 694, 1648, 1650, 1649, 1651 );

    // Check if the add-to-cart parameter is set and matches one of the IDs
    if ( isset( $_REQUEST['add-to-cart'] ) && in_array( intval( $_REQUEST['add-to-cart'] ), $redirect_product_ids, true ) ) {
        return wc_get_checkout_url();
    }

    // Otherwise, keep default behavior
    return $url;
}
*/

/**
 * Redirect specific WooCommerce product pages
 * - Product IDs: 694, 1648, 1650, 1649, 1651
 * - If logged in → redirect to /streams/go-pro/
 * - If not logged in → redirect to wp-login.php?redirect_to=/courses/registration-orientation/
 * - Runs after sso-session-sync.php MU plugin has initialized
 */

/**
 * Redirect specific WooCommerce products to Buzzjuice Go Pro or login
 */
add_action( 'template_redirect', 'buzzjuice_redirect_specific_products', 50 );

function buzzjuice_redirect_specific_products() {

    // Bail if WooCommerce is not active or not on a product page
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }

    global $post;

    // List of product IDs to trigger redirect
    $redirect_product_ids = array( 694, 1648, 1650, 1649, 1651 );

    if ( in_array( (int) $post->ID, $redirect_product_ids, true ) ) {

        if ( is_user_logged_in() ) {
            // Logged-in users → redirect to streams/go-pro
            wp_safe_redirect( home_url( '/streams/go-pro/' ), 301 );
            exit;
        } else {
            // Not logged in → redirect to login
            // Redirect_to should be LearnDash course page
            $learndash_url = home_url( '/courses/registration-orientation/' );
            $login_url     = home_url( '/wp-login.php?redirect_to=' . urlencode( $learndash_url ) );

            wp_safe_redirect( $login_url, 302 );
            exit;
        }
    }
}



