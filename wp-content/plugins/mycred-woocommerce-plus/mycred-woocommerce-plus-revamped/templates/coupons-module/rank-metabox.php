<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_nonce_field( 'mwp_module_coupons_nonce', 'mwp-coupon-reward-nonce' );
?>
<h4><?php esc_html_e( 'You can use these settings to reward users on achieving this rank.', 'mycred-woocommerce-plus' ); ?></h4>
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group">
            <label for="discount-mycred-discount-type"><?php esc_html_e( 'Discount Type', 'mycred-woocommerce-plus' );?></label>
            <?php 
            mycred_create_select_field( 
                array(
                    'fixed'   => __( 'Fixed Discount', 'mycred-woocommerce-plus' ),
                    'percent' => __( 'Percentage Discount', 'mycred-woocommerce-plus' ),
                ), 
                $settings['type'],
                array(
                    'name'  => 'discount[mycred_discount_type]',
                    'id'    => 'discount-mycred-discount-type',
                    'class' => 'form-control'
                )
            );
            ?>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group">
            <label for="discount-mycred-coupon-code-rank"><?php esc_html_e( 'Coupon code', 'mycred-woocommerce-plus' );?></label>
            <?php  
            mycred_create_input_field( array(
                'type'  => 'text',
                'name'  => 'discount[mycred_coupon_code_rank]',
                'id'    => 'discount-mycred-coupon-code-rank',
                'class' => 'form-control',
                'value' => $settings['code']
            ) );
            ?>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group">
            <label for="discount-mycred-discount-bagde-rank"><?php esc_html_e( 'Amount', 'mycred-woocommerce-plus' );?></label>
            <?php  
            mycred_create_input_field( array(
                'type'  => 'number',
                'name'  => 'discount[mycred_discount_bagde_rank]',
                'id'    => 'discount-mycred-discount-bagde-rank',
                'class' => 'form-control',
                'min'   => '0',
                'value' => $settings['amount']
            ) );
            ?>
        </div>
    </div>
</div>
<p><i><?php esc_html_e( 'NOTE: Keep amount 0 in order to disable coupons for this rank.', 'mycred-woocommerce-plus' );?></i></p>
