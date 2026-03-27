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


/**
 * Adjust myCRED Point Rewards
 * Will move the points payout from when an order is "paid" to when
 * an order is "completed".
 * @version 1.0
 */
// Adjust myCRED Point Rewards

add_action( 'after_setup_theme', 'mycred_pro_adjust_woo_rewards', 110, 2 );
function mycred_pro_adjust_woo_rewards() {

	remove_action( 'woocommerce_payment_complete', 'mycred_woo_payout_rewards' );
	add_action( 'woocommerce_order_status_completed', 'mycred_woo_payout_rewards' );

}

add_filter('mycred_woo_reward_mycred_payment', '__return_true');