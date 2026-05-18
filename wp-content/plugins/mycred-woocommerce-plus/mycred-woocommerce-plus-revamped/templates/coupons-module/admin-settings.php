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
                    'id' => $this->field_id( 'module_enable' ),
                    'name' => $this->field_name( 'module_enable' ),
                    'label' => __( 'Enable', 'mycred-woocommerce-plus' ),
                    'after' => true
                ), 
                $settings['module_enable'],
                $settings['module_enable']
            );
            ?>
            <p><i><?php esc_html_e( 'This will enable coupon functionalities.', 'mycred-woocommerce-plus' );?></i></p>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12 col-sm-12 col-lg-12 col-md-12">
        <h3><?php esc_html_e( 'Badges & Ranks Coupon', 'mycred' );?></h3>
        <div class="form-group">
           <?php  
           mycred_create_toggle_field( 
            array(
                'id' => $this->field_id( 'badge' ),
                'name' => $this->field_name( 'badge' ),
                'label' => __( 'Badges', 'mycred-woocommerce-plus' ),
                'after' => true
            ), 
            $settings['badge'],
            $settings['badge']
        );
        ?>
        <p><i><?php esc_html_e( 'Enable this option to reward users on achieving Badges. Settings will appear in Badge edit window once enabled.', 'mycred-woocommerce-plus' );?></i></p>
    </div>
</div>
</div>

<div class="row">
    <div class="col-xs-12 col-sm-12 col-lg-12 col-md-12">
        <div class="form-group">
           <?php  
           mycred_create_toggle_field( 
            array(
                'id' => $this->field_id( 'rank' ),
                'name' => $this->field_name( 'rank' ),
                'label' => __( 'Ranks', 'mycred-woocommerce-plus' ),
                'after' => true
            ), 
            $settings['rank'],
            $settings['rank']
        );
        ?>
        <p><i><?php esc_html_e( 'Enable this option to reward users on achieving ranks. Settings will appear in ranks edit window once enabled.', 'mycred-woocommerce-plus' );?></i></p>
    </div>
</div>
</div>

<div class="row">
    <h3><?php esc_html_e( 'Coupon Generator Settings', 'mycred' );?></h3>

    <div class="row">
      <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="form-group">
            <?php mycred_create_toggle_field( 
                array(
                    'id' => $this->field_id( 'enable' ),
                    'name' => $this->field_name( 'enable' ),
                    'label' => __( 'Enable', 'mycred-woocommerce-plus' ),
                    'after' => true
                ), 
                ! empty( $settings['enable'] ) ? $settings['enable'] : 0 ,
                ! empty( $settings['enable'] ) ? $settings['enable'] : 0
            ); ?> 
            <p>
                <i> <?php esc_html_e( 'This will display coupon generator in my account page of logged in user. You can also use this shortcode [mycred_coupon_code_generator] on your desired page.', 'mycred-woocommerce-plus' );?> </i>
            </p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="form-group">
            <?php mycred_create_toggle_field( 
                array(
                    'id' => $this->field_id( 'email_field' ),
                    'name' => $this->field_name( 'email_field' ),
                    'label' => __( 'Email Field', 'mycred-woocommerce-plus' ),
                    'after' => true
                ),
                ! empty( $settings['email_field'] ) ? $settings['email_field'] : 0 ,
                ! empty( $settings['email_field'] ) ? $settings['email_field'] : 0 
            ); 
            ?> 
            <p>
                <i> <?php esc_html_e( 'This will display email field in my account page.', 'mycred-woocommerce-plus' );?> </i>
            </p>
        </div>
    </div>
</div>

<div class="row" id="required-email-wrapper" style="display: none;">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="form-group">
            <?php mycred_create_toggle_field( 
                array(
                    'id' => $this->field_id( 'required_email' ),
                    'name' => $this->field_name( 'required_email' ),
                    'label' => __( 'Required Email', 'mycred-woocommerce-plus' ),
                    'after' => true
                ), 
                ! empty( $settings['required_email'] ) ? $settings['required_email'] : 0 ,
                ! empty( $settings['required_email'] ) ? $settings['required_email'] : 0
            );    
            ?> 
            <p>
                <i> <?php esc_html_e( 'Enable this option to require the email field.', 'mycred-woocommerce-plus' );?> </i>
            </p>
        </div>
    </div>
</div>

<script>

    document.addEventListener("DOMContentLoaded", function() {
        var emailFieldToggle = document.getElementById("<?php echo $this->field_id( 'email_field' ); ?>");
        var requiredEmailWrapper = document.getElementById("required-email-wrapper");

        function toggleRequiredEmail() {
            if (emailFieldToggle.checked) {
                requiredEmailWrapper.style.display = "block";
            } else {
                requiredEmailWrapper.style.display = "none";
            }
        }

        toggleRequiredEmail();

        emailFieldToggle.addEventListener("change", toggleRequiredEmail);
    });

</script>

<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">

    <div class="form-group">
        <label for="mycred_partial_payments_woo_point_type"> <?php esc_html_e( 'Select Point Type to Display', 'mycred-woocommerce-plus' ); ?> </label> <?php 
        mycred_create_select_field(
            mycred_get_types( true ),
            ! empty( $settings['point_type'] ) ? $settings['point_type'] : 'mycred_default', 
            array( 
                'id'       => $this->field_id( 'point_type' ),
                'class'    => 'mycred-select2',
                'name'     => $this->field_name( 'point_type' ), 
                'multiple' => false 
            )
        );
        ?>
        <p>
            <i><?php esc_html_e( 'Select the point type a user can pay with', 'mycred-woocommerce-plus' );?> </i>
        </p>
    </div>

    <div class="form-group">
        <label for="mycred_partial_payments_woo_min"><?php esc_html_e('Exchange Rate', 'mycred-woocommerce-plus'); ?></label>
        <input type="text" name="<?php echo $this->field_name( 'exchange_rate' ) ; ?>" id="<?php $this->field_id( 'exchange_rate' );  ?>" class="form-control" value="<?php echo isset($settings['exchange_rate']) && $settings['exchange_rate'] !== '' ? $settings['exchange_rate'] : 1; ?>" min="1">
        <p>
            <i>
                <?php esc_html_e('How much is 1 point worth in your store currency?', 'mycred-woocommerce-plus'); ?>

            </i>
        </p>
    </div>
    <div class="form-group">
        <label for="mycred_partial_payments_woo_min"> <?php esc_html_e( 'Minimum', 'mycred-woocommerce-plus' ); ?> </label> <?php 
        mycred_create_input_field( array(
            'type'  => 'number',
            'name'  => $this->field_name( 'min' ),
            'id'    => $this->field_id( 'min' ),
            'class' => 'form-control',
            'value' => $settings['min'],
            'min'   => 1    
        ) );
        ?>
        <p><i><?php esc_html_e( 'The minimum amount of points a user must pay.', 'mycred-woocommerce-plus' );?></i></p>
    </div>
    <div class="form-group">
        <label for="mycred_partial_payments_woo_coupon_max"> <?php esc_html_e( 'Maximum', 'mycred-woocommerce-plus' ); ?> </label> <?php 
        mycred_create_input_field( array(
            'type'  => 'number',
            'name'  => $this->field_name( 'coupon_max' ),
            'id'    => $this->field_id( 'coupon_max' ),
            'class' => 'form-control',
            'value' => $settings['coupon_max'],
            'min'   => 1    
        ) );
        ?>
        <p><i><?php esc_html_e( 'The maximum amount of points a user must pay.', 'mycred-woocommerce-plus' );?></i></p>
    </div>
</div>
<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
    <div class="form-group">
        <label for="mycred_partial_payments_woo_coupon_title"> <?php esc_html_e( 'Coupon Title', 'mycred-woocommerce-plus' ); ?> </label> <?php 
        mycred_create_input_field( array(
            'type'  => 'text',
            'name'  => $this->field_name( 'coupon_title' ),
            'id'    => $this->field_id( 'coupon_title' ),
            'class' => 'form-control',
            'value' => $settings['coupon_title'],
        ) );
        ?>
        <p>
            <i> <?php esc_html_e( 'Coupon title that will show in the coupon fields.', 'mycred-woocommerce-plus' );?> </i>
        </p>
    </div>
    <div class="form-group">
        <label for="mycred_partial_payments_woo_coupon_desc"> <?php esc_html_e( 'Coupon Description', 'mycred-woocommerce-plus' ); ?> </label><?php 
        mycred_create_input_field( array(
            'type'  => 'text',
            'name'  => $this->field_name( 'coupon_desc' ),
            'id'    => $this->field_id( 'coupon_desc' ),
            'class' => 'form-control',
            'value' => $settings['coupon_desc'],  
        ) );
        ?>
        <p>
            <i> <?php esc_html_e( 'Coupon generator short description.', 'mycred-woocommerce-plus' );?> </i>
        </p>
    </div>
    <div class="form-group">
        <label for="mycred_partial_payments_woo_coupon_coupon_button"> <?php esc_html_e( 'Coupon Button Label', 'mycred-woocommerce-plus' ); ?> </label><?php 
        mycred_create_input_field( array(
            'type'  => 'text',
            'name'  => $this->field_name( 'coupon_button' ),
            'id'    => $this->field_id( 'coupon_button' ),
            'class' => 'form-control',
            'value' => $settings['coupon_button'],  
        ) );
        ?>
        <p>
            <i> <?php esc_html_e( 'Coupon button label that will show in the coupon generator button.', 'mycred-woocommerce-plus' );?> </i>
        </p>
    </div>
</div>
</div>
