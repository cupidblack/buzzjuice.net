<?php
/* MYCRED */
function mycred_change_badge_post_type_args( $args, $post_type ) {
    if ( 'mycred_badge' === $post_type ) {
        $args['rewrite']['slug'] = 'badge';
        $args['rewrite']['with_front'] = false;
    }
    return $args;
}
add_filter( 'register_post_type_args', 'mycred_change_badge_post_type_args', 10, 2 );

function mycred_flush_rewrite_rules() {
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'mycred_flush_rewrite_rules' );


function enable_comments_on_mycred_badge() {
    add_post_type_support( 'mycred_badge', 'comments' );
}
add_action( 'init', 'enable_comments_on_mycred_badge' );


/* ---------- JS guard to prevent undefined extension crash ---------- */
add_action( 'wp_enqueue_scripts', 'mu_mycred_enqueue_js_guard', 5 );
function mu_mycred_enqueue_js_guard() {
    if ( is_admin() ) {
        return;
    }
    if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
        return;
    }
    if ( ! is_cart() && ! is_checkout() ) {
        return;
    }

    $handle = 'mu-mycred-guard';
    // Register a no-src handle so inline script can be added and enqueued early.
    wp_register_script( $handle, '' );
    // This script ensures wc.blocksCheckout.extensions.mycredwoo/mycredwoogateway exist before the block runs.
    $inline = "(function(){try{if(typeof wc === 'undefined'){return;}wc.blocksCheckout = wc.blocksCheckout || {};wc.blocksCheckout.extensions = wc.blocksCheckout.extensions || {}; if ( typeof wc.blocksCheckout.extensions.mycredwoo === 'undefined' ) { wc.blocksCheckout.extensions.mycredwoo = { mycred_woo_total:null, mycred_woo_total_label:'', mycred_woo_balance:null, mycred_woo_balance_label:'', payment_gateway:'no' }; } else { wc.blocksCheckout.extensions.mycredwoo.mycred_woo_total = (typeof wc.blocksCheckout.extensions.mycredwoo.mycred_woo_total !== 'undefined') ? wc.blocksCheckout.extensions.mycredwoo.mycred_woo_total : null; wc.blocksCheckout.extensions.mycredwoo.mycred_woo_total_label = (typeof wc.blocksCheckout.extensions.mycredwoo.mycred_woo_total_label !== 'undefined') ? wc.blocksCheckout.extensions.mycredwoo.mycred_woo_total_label : ''; wc.blocksCheckout.extensions.mycredwoo.mycred_woo_balance = (typeof wc.blocksCheckout.extensions.mycredwoo.mycred_woo_balance !== 'undefined') ? wc.blocksCheckout.extensions.mycredwoo.mycred_woo_balance : null; wc.blocksCheckout.extensions.mycredwoo.mycred_woo_balance_label = (typeof wc.blocksCheckout.extensions.mycredwoo.mycred_woo_balance_label !== 'undefined') ? wc.blocksCheckout.extensions.mycredwoo.mycred_woo_balance_label : ''; wc.blocksCheckout.extensions.mycredwoo.payment_gateway = (typeof wc.blocksCheckout.extensions.mycredwoo.payment_gateway !== 'undefined') ? wc.blocksCheckout.extensions.mycredwoo.payment_gateway : 'no'; } if ( typeof wc.blocksCheckout.extensions.mycredwoogateway === 'undefined' ) { wc.blocksCheckout.extensions.mycredwoogateway = wc.blocksCheckout.extensions.mycredwoo; } }catch(e){} })();";
    wp_add_inline_script( $handle, $inline );
    wp_enqueue_script( $handle );
}