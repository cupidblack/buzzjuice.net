<div class="row">
    <h3><?php esc_html_e( 'Reward Point settings', 'mycred-woocommerce-plus' );?></h3>
    <div class="col-xs-12 col-sm-12 col-lg-12 col-md-12">
        <div class="form-group">
         <?php  
         mycred_create_toggle_field( 
            array(
                'id' => $this->field_id( 'single_product' ),
                'name' => $this->field_name( 'single_product' ),
                'label' => __( 'Single Product Page', 'mycred-woocommerce-plus' ),
                'after' => true
            ), 
            $settings['single_product'],
            $settings['single_product']
        );
        ?>
        <p><i><?php esc_html_e( 'Show earn points on single product page.', 'mycred-woocommerce-plus' );?></i></p>
    </div>
</div>
</div>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-lg-6 col-md-6">
        <div class="form-group">
         <?php  
         mycred_create_toggle_field( 
            array(
                'id' => $this->field_id( 'checkout_product_meta' ),
                'name' => $this->field_name( 'checkout_product_meta' ),
                'label' => __( 'Checkout Product Meta', 'mycred-woocommerce-plus' ),
                'after' => true
            ), 
            $settings['checkout_product_meta'],
            $settings['checkout_product_meta']
        );
        ?>
        <p><i><?php esc_html_e( 'Show earn points on per product on checkout page.', 'mycred-woocommerce-plus' );?></i></p>
    </div>
</div>
<div class="col-xs-12 col-sm-12 col-lg-6 col-md-6">
    <div class="form-group">
     <?php  
     mycred_create_toggle_field( 
        array(
            'id' => $this->field_id( 'checkout_product_total' ),
            'name' => $this->field_name( 'checkout_product_total' ),
            'label' => __( 'Checkout Product Total', 'mycred-woocommerce-plus' ),
            'after' => true
        ), 
        $settings['checkout_product_total'],
        $settings['checkout_product_total']
    );
    ?>
    <p><i><?php esc_html_e( 'Enable this option to show total earn points on top of the checkout page.', 'mycred-woocommerce-plus' );?></i></p>
</div>
</div>
</div>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-lg-6 col-md-6">
        <div class="form-group">
         <?php  
         mycred_create_toggle_field( 
            array(
                'id' => $this->field_id( 'cart_product_meta' ),
                'name' => $this->field_name( 'cart_product_meta' ),
                'label' => __( 'Cart Product Meta', 'mycred-woocommerce-plus' ),
                'after' => true
            ), 
            $settings['cart_product_meta'],
            $settings['cart_product_meta']
        );
        ?>
        <p><i><?php esc_html_e( 'Show earn points on per product on cart page.', 'mycred-woocommerce-plus' );?></i></p>
    </div>
</div>
<div class="col-xs-12 col-sm-12 col-lg-6 col-md-6">
    <div class="form-group">
     <?php  
     mycred_create_toggle_field( 
        array(
            'id' => $this->field_id( 'cart_product_total' ),
            'name' => $this->field_name( 'cart_product_total' ),
            'label' => __( 'Cart Product Total', 'mycred-woocommerce-plus' ),
            'after' => true
        ), 
        $settings['cart_product_total'],
        $settings['cart_product_total']
    );
    ?>
    <p><i><?php esc_html_e( 'Enable this option to show total earn points on top of the cart page.', 'mycred-woocommerce-plus' );?></i></p>
</div>
</div>
</div>



