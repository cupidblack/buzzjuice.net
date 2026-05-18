<?php
/**
 * Plugin Name: myCred WooCommerce Plus
 * Description: Allows WooCommerce Plus of a WooCommerce orders using Coupons, Restrict Products, Points History, Partial Payments.
 * Version: 2.1.3
 * Tags: points history, restrict products, coupons, woocommerce
 * Author: myCred
 * Author URI: http://mycred.me
 * Author Email: support@mycred.me
 * Requires at least: WP 4.8
 * Tested up to: WP 6.7.1
 * Text Domain: mycred-woocommerce-plus
 * Domain Path: /lang
 * Requires Plugins: mycred, woocommerce
 * WC requires at least: 8.3
 * WC tested up to: 9.3.2
 */

// Check the current version
$version_check = get_option('mycred_woocommerce_version');
$partial_payments_key = get_option('mycred_partial_payments_woo');
include_once plugin_dir_path(__FILE__) . 'class.mycred-license.php';

if ( $version_check === false ) {
    // New user (no version set yet)
    add_action( 'mycred_after_plugin_loaded', 'mycred_load_revamp_woocommerce_plus' );
} 
else {

    // Old user (version key exists)
    if ( $partial_payments_key && $version_check == '2.0') {
        // Existing user with partial payments key
        add_action( 'mycred_after_plugin_loaded', 'mycred_load_revamp_woocommerce_plus' );
    } 
    else {
        // Old user without the key
        if ($version_check === '1.8') {
                // Old user currently on version 1.8
            include_once plugin_dir_path(__FILE__) . 'mycred-woocommerce-plus-legacy/mycred-woocommerce-plus.php';
        } 
        else {
            add_action( 'mycred_after_plugin_loaded', 'mycred_load_revamp_woocommerce_plus' );
        }
    }

}


function mycred_load_revamp_woocommerce_plus ( ) {
    include_once plugin_dir_path(__FILE__) . 'mycred-woocommerce-plus-revamped/mycred-woocommerce-plus.php';
}

function declare_woocommerce_compatibility() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
}
add_action( 'before_woocommerce_init', 'declare_woocommerce_compatibility' );



function load_mycred_woo_license() {

    if ( class_exists('myCRED_License') ) {

        new myCRED_License( 
            array(
                'version' => '2.1.3',
                'slug'    => 'mycred-woocommerce-plus',
                'base'    => __FILE__
            )
        );

    } 
    else {

        add_action( 'admin_notices', 'mycred_woo_license_admin_notice' );

    }

}
add_action( 'mycred_init', 'load_mycred_woo_license', 5 );

function mycred_woo_license_admin_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <?php esc_html_e( 'myCRED WooCommerce Plus - WooCommerce requires myCred 2.3.1 or greater version to work your license properly.', 'mycredpartwoo' ); ?>
        </p>
    </div>
    <?php
}


function mycred_woo_load_plugin_textdomain() {


    $locale = apply_filters( 'plugin_locale', get_locale(), 'mycred-woocommerce-plus' );

    load_textdomain( 'mycred-woocommerce-plus', WP_LANG_DIR . '/' . 'mycred-woocommerce-plus' . '-' . $locale . '.mo' );
    load_plugin_textdomain( 'mycred-woocommerce-plus', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

}
add_action( 'init', 'mycred_woo_load_plugin_textdomain'  );