<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<div class="row">
    <div class="col-xs-12 col-sm-12 col-lg-12 col-md-12">
        <div class="form-group">
            <?php  
            mycred_create_toggle_field( 
                array(
                    'id'    => $this->field_id( 'enable' ),
                    'name'  => $this->field_name( 'enable' ),
                    'label' => __( 'Enable Specific Product', 'mycred-woocommerce-plus' ),
                    'after' => true
                ), 
                $settings['enable'],
                $settings['enable']
            );
            ?>
            <p><i><?php esc_html_e( 'With this option you will have new settings in single product edit window to restrict products based on either ranks or badges.', 'mycred-woocommerce-plus' );?></i></p>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12 col-sm-12 col-lg-12 col-md-12">
        <div class="form-group">
            <?php  
            mycred_create_toggle_field( 
                array(
                    'id'    => $this->field_id( 'enable_category' ),
                    'name'  => $this->field_name( 'enable_category' ),
                    'label' => __( 'Enable Specific Category', 'mycred-woocommerce-plus' ),
                    'after' => true
                ), 
                ! empty( $settings['enable_category'] ) ? $settings['enable_category'] : 0 ,
               ! empty( $settings['enable_category'] ) ? $settings['enable_category'] : 0 ,
            );
            ?>
            <p><i><?php esc_html_e( 'With this option you will have new settings in ranks and badges edit window to restrict products by selecting category.', 'mycred-woocommerce-plus' );?></i></p>
        </div>
    </div>
</div>