<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mwp-product-referral-generator">
    <?php if ( empty( $product_id ) ):?>
    <h3 class="mwp-prod-ref-generator-heading"><?php esc_html_e( 'Generate Link', 'mycred-woocommerce-plus' ); ?></h3>
    <div class="mwp-prod-ref-generator-form">
        <?php  
        mycred_create_input_field( array(
            'type'  => 'hidden',
            'class' => 'mwp-prod-ref-generator-uid',
            'value' => $user_id
        ) );
        ?>
        <?php wp_nonce_field( 'mwp_product_referral_nonce', 'mwp-prod-referral-nonce' );?>
        <?php 
        mycred_create_select_field( 
            $products, 
            $product_id,
            array( 
                'class' => 'mwp-prod-ref-generator-products mycred-select2',
                'style' => 'width: 100%' 
            )
        );
        ?>
        <button class="mwp-prod-ref-generator-btn button wp-element-button" type="button">
            <span class="mwp__text"><?php esc_html_e( 'Generate Link', 'mycred-woocommerce-plus' );?></span>
        </button>
    </div>
    <?php endif;?>
    <fieldset class="mwp-prod-ref-generator-links <?php echo esc_attr( ! empty( $product_id ) ? 'show' : '' );?>">
        <?php if ( ! empty( $product_id ) ):?>
        <h3><?php echo esc_html( $product_name );?></h3>
        <?php endif;?>
        <legend><?php esc_html_e( 'Product Referral Link', 'mycred-woocommerce-plus' );?></legend>
        <label>Product URL</label>
        <div>
            <span class="mwp-prod-ref-generator-links-url"><?php echo esc_url( $product_url );?></span>
        </div>
        <div class="mwp-prod-ref-generator-links-ref-url">
            <label for="mwp-prod-ref-generator-ref-url"><?php esc_html_e( 'Product Referral URL', 'mycred-woocommerce-plus' );?></label>
            <div>
                <?php  
                mycred_create_input_field( array(
                    'type'  => 'text',
                    'class' => 'mwp-prod-ref-generator-ref-url',
                    'value' => $referral_url
                ) );
                ?>
                <button type="button" class="button mwp-prod-ref-clipboard" title="Copy">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </div>
        </div>
    </fieldset>
</div>