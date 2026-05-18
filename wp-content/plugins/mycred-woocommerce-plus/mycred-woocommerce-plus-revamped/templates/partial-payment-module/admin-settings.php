<div class="row mb-4">
    <h3 class="col-12 mb-3"><?php esc_html_e('Partial Payments', 'mycred-woocommerce-plus'); ?></h3>

    <!-- Enable Toggle -->
    <div class="col-12">
        <div class="form-group mb-3">
            <?php  
            mycred_create_toggle_field( 
                array(
                    'id' => $this->field_id('enable'),
                    'name' => $this->field_name('enable'),
                    'label' => __('Enable', 'mycred-woocommerce-plus'),
                    'after' => true
                ), 
                $settings['enable'],
                $settings['enable']
            );
            ?>
            <p><i><?php esc_html_e('This will display Partial Payment section.', 'mycred-woocommerce-plus'); ?></i></p>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Display Settings Column -->
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group mb-3">
            <label><?php esc_html_e('Display Settings', 'mycred-woocommerce-plus'); ?></label>
            
            <div class="mt-3">
                <label><?php esc_html_e('Select Template to Display', 'mycred-woocommerce-plus'); ?></label>
                <?php 
                $options = array(
                    'cart' => __('In Cart', 'mycred-woocommerce-plus'),
                    'checkout' => __('In Checkout', 'mycred-woocommerce-plus'),
                    'both' => __('Both Cart and Checkout', 'mycred-woocommerce-plus'),
                );
                mycred_create_select_field($options, $settings['change_position'], array(
                    'id' => $this->field_id('change_position'),
                    'class' => 'mycred-select2',
                    'name' => $this->field_name('change_position'),
                    'multiple' => false
                ));
                ?>
            </div>

            <div class="mt-3 partial-payments-position-group">
                <label><?php esc_html_e('Position', 'mycred-woocommerce-plus'); ?></label>
                <?php 
                $options = array(
                    'after' => __('After Order Total', 'mycred-woocommerce-plus'),
                    'before' => __('Before Order Total', 'mycred-woocommerce-plus'),
                );
                foreach ($options as $key => $label) {
                    $checked = ($settings['position'] === $key);
                    mycred_create_radio_field(array(
                        'id' => $this->field_id('position'),
                        'name' => $this->field_name('position'),
                        'value' => $key,
                        'label' => $label,
                        'after' => true
                    ), $key, $checked, true);
                }
                ?>
                <p><i><?php esc_html_e('Position setting for displaying content before or after the order total, currently only for legacy WooCommerce checkout.', 'mycred-woocommerce-plus'); ?></i></p>
            </div>
        </div>
    </div>

    <!-- Payment Options Column -->
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group mb-3">
            <label><?php esc_html_e('Payment Options', 'mycred-woocommerce-plus'); ?></label>

            <div class="mt-3">
                <label><?php esc_html_e('Multiple Payment', 'mycred-woocommerce-plus'); ?></label>
                <?php 
                $options = array(
                    'no' => __('No', 'mycred-woocommerce-plus'),
                    'yes' => __('Yes', 'mycred-woocommerce-plus'),
                );
                mycred_create_select_field($options, $settings['multiple'], array(
                    'id' => $this->field_id('multiple'),
                    'class' => 'mycred-select2',
                    'name' => $this->field_name('multiple'),
                    'multiple' => false
                ));
                ?>
                <p><i><?php esc_html_e('Can users, if they can afford it, make multiple payments?', 'mycred-woocommerce-plus'); ?></i></p>
            </div>

            <div class="mt-3">
                <label><?php esc_html_e('Undo', 'mycred-woocommerce-plus'); ?></label>
                <?php 
                mycred_create_select_field($options, !empty($settings['undo']) ? $settings['undo'] : 'no', array(
                    'id' => $this->field_id('undo'),
                    'class' => 'mycred-select2',
                    'name' => $this->field_name('undo'),
                    'multiple' => false
                ));
                ?>
                <p><i><?php esc_html_e('Can users undo a partial payment? Undoing a partial payment will refund the amount the user paid (For legacy cart).', 'mycred-woocommerce-plus'); ?></i></p>
            </div>
        </div>
    </div>

    <!-- Additional Settings Column -->
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group mb-3">
            <label><?php esc_html_e('Additional Settings', 'mycred-woocommerce-plus'); ?></label>

            <div class="mt-3">
                <label><?php esc_html_e('Store Rewards', 'mycred-woocommerce-plus'); ?></label>
                <?php 
                $options = array(
                    1 => __('I do not use store rewards', 'mycred-woocommerce-plus'),
                    2 => __('Deduct the partial payment amount from the amount the user gets in reward', 'mycred-woocommerce-plus'),
                    3 => __('Ignore partial payments and let rewards payout the appropriate amount', 'mycred-woocommerce-plus'),
                );
                mycred_create_select_field($options, !empty($settings['rewards']) ? $settings['rewards'] : 1, array(
                    'id' => $this->field_id('rewards'),
                    'class' => 'mycred-select2',
                    'name' => $this->field_name('rewards'),
                    'multiple' => false,
                    'css' => 'min-width:300px;',
                ));
                ?>
                <p><i><?php esc_html_e('If you are rewarding your users with points for store purchases, how do you want to handle partial point payments?', 'mycred-woocommerce-plus'); ?></i></p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
  <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="form-group">   <label for="mycred_partial_payments_woo_shipping"> <?php esc_html_e( 'Pay for: Shipping', 'mycred-woocommerce-plus
        ' ); ?> </label><?php 
    $options = array(
        'no'  => __( 'No', 'mycred-woocommerce-plus' ),
        'yes' => __( 'Yes', 'mycred-woocommerce-plus' ),
    );
    mycred_create_select_field( 
        $options, 
        ! empty( $settings['free_shipping'] ) ? $settings['free_shipping'] : 'no' , 
        array( 
            'id'       => $this->field_id( 'free_shipping' ),
            'class'    => 'mycred-select2',
            'name'     => $this->field_name( 'free_shipping' ), 
            'multiple' => false 
        )
    );
?> <p>
    <i> 
        <?php 
        echo sprintf(
            esc_html__('Can points be used to pay for shipping costs? Only applicable for orders that need to be shipped. If you only sell virtual products, this should be set to No.') ); 
            ?> 
        </i>
    </p>

</div>
</div>
<div class="col-lg-6 col-md-6 col-sm-12">
    <div class="form-group">  <label for="mycred_partial_payments_woo_sale_itms"> <?php esc_html_e( 'Pay for: Sale Items', 'mycred-woocommerce-plus
        ' ); ?> </label> <?php 
    $options = array(
        'no'  => __( 'No', 'mycred-woocommerce-plus' ),
        'yes' => __( 'Yes', 'mycred-woocommerce-plus' ),
    );
    mycred_create_select_field( 
        $options, 
        ! empty( $settings['sale_items'] ) ? $settings['sale_items'] : 'no' , 
        array( 
            'id'       => $this->field_id( 'sale_items' ),
            'class'    => 'mycred-select2',
            'name'     => $this->field_name( 'sale_items' ), 
            'multiple' => false 
        )
    );
?> <p>
    <i> <?php esc_html_e( 'Can points be used to pay for items that are "on sale"?', 'mycred-woocommerce-plus' );?> </i>
</p>
</div>
</div>
</div>


<div class="row mb-4">
 <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="form-group">
      <label for="mycred_partial_payments_woo_selecttype"> <?php esc_html_e( 'Selector Type', 'mycred-woocommerce-plus' ); ?> </label>
      <p> <?php esc_html_e( 'This controls how a user indicates the amount they want to pay for the order.', 'mycred-woocommerce-plus' ); ?> </p> <?php 
      $options = array(
        'input'  => __( 'Input Field - The amount needs to be typed in.', 'mycred-woocommerce-plus' ),
        'slider' => __( 'Slider - The amount changes based on the sliders position.', 'mycred-woocommerce-plus' ),
    );
      foreach ( $options as $key => $label ) {
        $selecttype = ! empty( $settings['selecttype'] ) ? $settings['selecttype'] : 'input' ; 

        $checked = ( $selecttype === $key );
        mycred_create_radio_field( 
            array(
                'id'       => $this->field_id( 'selecttype' ),
                'name'     => $this->field_name( 'selecttype' ), 
                'value' => $key,
                'label' =>  $label,
                'after' => true
            ), 
            $key, 
            $checked, 
            true 
        );
    }
    ?>
</div>
</div>
<div class="col-lg-6 col-md-6 col-sm-12">
    <div class="form-group">   <label for="mycred_partial_payments_woo_step"> <?php esc_html_e( 'Steps', 'mycred-woocommerce-plus
        ' ); ?> </label><?php 
    mycred_create_input_field( array(
        'type'  => 'text',
        'name'  => $this->field_name( 'step' ),
        'id'    => $this->field_id( 'step' ),
        'class' => 'form-control',
        'value' =>  ! empty( $settings['step'] ) ? $settings['step'] : 1 , 
    ) );
?> <p>
    <i> <?php esc_html_e( 'The point amount that each step increment.', 'mycred-woocommerce-plus' );?> </i>
</p>
</div>
</div>
</div>


<div class="row mb-4">
  <div class="col-12">
    <div class="form-group">   <label for="mycred_partial_payments_woo_title"> <?php esc_html_e( 'Partial Payment Title', 'mycred-woocommerce-plus
        ' ); ?> </label><?php 

    mycred_create_input_field( array(
        'type'  => 'text',
        'name'  => $this->field_name( 'title' ),
        'id'    => $this->field_id( 'title' ),
        'class' => 'form-control',
        'value' =>  ! empty( $settings['title'] ) ? $settings['title'] : __('Partial Payment', 'mycred-woocommerce-plus') , 
    ) );
    ?> 
</div>
</div>
<div class="col-12">
    <div class="form-group">   <label for="mycred_partial_payments_woo_desc"> <?php esc_html_e( 'Partial Payment Description', 'mycred-woocommerce-plus
        ' ); ?> </label><?php 
    mycred_create_input_field( array(
        'type'  => 'text',
        'name'  => $this->field_name( 'desc' ),
        'id'    => $this->field_id( 'desc' ),
        'class' => 'form-control',
        'value' => ! empty( $settings['desc'] ) ? $settings['desc'] : '', 
    ) );
    ?> 
</div>
</div>
<div class="col-12">
    <div class="form-group">   <label for="mycred_partial_payments_woo_button"> <?php esc_html_e( 'Partial Payment Button Label', 'mycred-woocommerce-plus
        ' ); ?> </label><?php 
    mycred_create_input_field( array(
        'type'  => 'text',
        'name'  => $this->field_name( 'button' ),
        'id'    => $this->field_id( 'button' ),
        'class' => 'form-control',
        'value' => ! empty( $settings['button'] ) ? $settings['button'] : __('Apply discount', 'mycred-woocommerce-plus') , 
    ) );
    ?> 
</div>
</div>
</div>


<div class="row mb-4">
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group">
            <label for="mycred_partial_payments_woo_checkout_total">
                <?php esc_html_e( 'Point Cost', 'mycred-woocommerce-plus' ); ?>
            </label>
            <?php 
            $options = array(
                'no'       => __( 'No', 'mycred-woocommerce-plus' ),
                'cart'     => __( 'In Cart', 'mycred-woocommerce-plus' ),
                'checkout' => __( 'In Checkout', 'mycred-woocommerce-plus' ),
                'both'     => __( 'Both Cart and Checkout', 'mycred-woocommerce-plus' ),
            );
            mycred_create_select_field( 
                $options, 
                ! empty( $settings['checkout_total'] ) ? $settings['checkout_total'] : 'cart' , 
                array( 
                    'id'       => $this->field_id( 'checkout_total' ),
                    'class'    => 'mycred-select2',
                    'name'     => $this->field_name( 'checkout_total' ), 
                    'multiple' => false 
                )
            );
            ?> 
            <p>
                <i>
                    <?php esc_html_e( 'Show the cart cost in points based on our exchange rate.', 'mycred-woocommerce-plus' );?>
                </i>
            </p>
        </div>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="form-group">   <label for="mycred_partial_payments_woo_checkout_total_label"> <?php esc_html_e( 'Total Label', 'mycred-woocommerce-plus
            ' ); ?> </label><?php 
        mycred_create_input_field( array(
            'type'  => 'text',
            'name'  => $this->field_name( 'checkout_total_label' ),
            'id'    => $this->field_id( 'checkout_total_label' ),
            'class' => 'form-control',
            'value' =>  ! empty( $settings['checkout_total_label'] ) ? $settings['checkout_total_label'] : __('Total', 'mycred-woocommerce-plus') ,  
        ) );
        ?> 
        <p>
            <i>
                <?php esc_html_e( 'The label to use for the total cost. Supports ', 'mycred-woocommerce-plus' ); ?>
                <a href="http://codex.mycred.me/category/template-tags/temp-general/" target="_blank">
                    <?php esc_html_e( 'General Template Tags', 'mycred-woocommerce-plus' ); ?>
                </a>
            </i>
        </p>

    </div>
</div>

<div class="col-lg-4 col-md-4 col-sm-12">
    <div class="form-group">
        <label for="mycred_partial_payments_woo_checkout_checkout_balance">
            <?php esc_html_e( 'Users Balance', 'mycred-woocommerce-plus' ); ?>
        </label>
        <?php 
        $options = array(
            'no'       => __( 'No', 'mycred-woocommerce-plus' ),
            'cart'     => __( 'In Cart', 'mycred-woocommerce-plus' ),
            'checkout' => __( 'In Checkout', 'mycred-woocommerce-plus' ),
            'both'     => __( 'Both Cart and Checkout', 'mycred-woocommerce-plus' ),
        );
        mycred_create_select_field( 
            $options, 
            ! empty( $settings['checkout_balance'] ) ? $settings['checkout_balance'] : 'cart' , 
            array( 
                'id'       => $this->field_id( 'checkout_balance' ),
                'class'    => 'mycred-select2',
                'name'     => $this->field_name( 'checkout_balance' ), 
                'multiple' => false 
            )
        );
        ?> 
        <p>
            <i>
                <?php esc_html_e( 'Show the current users balance.', 'mycred-woocommerce-plus' ); ?>
            </i>
        </p>
    </div>
</div>

<div class="col-lg-4 col-md-4 col-sm-12">
    <div class="form-group">   <label for="mycred_partial_payments_woo_checkout_balance_label"> <?php esc_html_e( 'Balance Label', 'mycred-woocommerce-plus
        ' ); ?> </label><?php 
    mycred_create_input_field( array(
        'type'  => 'text',
        'name'  => $this->field_name( 'checkout_balance_label' ),
        'id'    => $this->field_id( 'checkout_balance_label' ),
        'class' => 'form-control',
        'value' => ! empty( $settings['checkout_balance_label'] ) ? $settings['checkout_balance_label'] : __('Balance', 'mycred-woocommerce-plus') ,  
    ) );
    ?> 
    <p>
        <i>
            <?php esc_html_e( 'The label to use for the total cost. Supports ', 'mycred-woocommerce-plus' ); ?>
            <a href="http://codex.mycred.me/category/template-tags/temp-general/" target="_blank">
                <?php esc_html_e( 'General Template Tags', 'mycred-woocommerce-plus' ); ?>
            </a>
        </i>
    </p>

</div>
</div>


</div>
<script>
    jQuery(document).ready(function($) {
        $('#mycredprefwoomwppartialpaymentschangeposition').change(function() {
            if ($(this).val() === 'cart') {
                $('div.partial-payments-position-group').hide();
            } else {
                $('div.partial-payments-position-group').show();
            }
        });
        $('#mycredprefwoomwppartialpaymentschangeposition').trigger('change');
    });
</script>