<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
        <h3><?php esc_html_e( 'Referral Cookie', 'mycred-woocommerce-plus' ) ?></h3>
        <div class="form-group pb-1">
            <?php  
            mycred_create_toggle_field( 
                array(
                    'id'     => $this->field_id( 'can_cookie_expire' ),
                    'name'   => $this->field_name( 'can_cookie_expire' ),
                    'label'  => __( 'Make cookie expire', 'mycred-woocommerce-plus' ),
                    'inline' => false
                ), 
                $settings['can_cookie_expire'],
                $settings['can_cookie_expire']
            );
            ?>
            <p><i><?php esc_html_e( 'Enable this option if you want to make referral cookie expire.', 'mycred-woocommerce-plus' );?></i></p>
        </div>
        <div class="form-group pb-1">
            <label for="<?php echo esc_attr( $this->field_id( 'cookie_expiration_days' ) ); ?>"><?php esc_html_e( 'Cookie Expiration in days', 'mycred-woocommerce-plus' );?></label>
            <?php  
            mycred_create_input_field( array(
                'type'  => 'number',
                'name'  => $this->field_name( 'cookie_expiration_days' ),
                'id'    => $this->field_id( 'cookie_expiration_days' ),
                'class' => 'form-control',
                'value' => $settings['cookie_expiration_days'],
                'min'   => 1    
            ) );
            ?>
            <p><i><?php esc_html_e( 'Referral Cookie expires in x days.', 'mycred-woocommerce-plus' );?></i></p>
        </div>
        <div class="form-group">
            <label for="<?php echo esc_attr( $this->field_id( 'ref_cookie_name' ) ); ?>"><?php esc_html_e( 'Referral Cookie name', 'mycred-woocommerce-plus' );?></label>
            <?php  
            mycred_create_input_field( array(
                'type'  => 'text',
                'name'  => $this->field_name( 'ref_cookie_name' ),
                'id'    => $this->field_id( 'ref_cookie_name' ),
                'class' => 'form-control',
                'value' => $settings['ref_cookie_name']
            ) );
            ?>
            <p><i><?php esc_html_e( 'Product ID will be appended with it.', 'mycred-woocommerce-plus' );?></i></p>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
        <h3><?php esc_html_e( 'Referral Links', 'mycred-woocommerce-plus' );?></h3>
        <div class="form-group">
            <label for="mwp-referral-link-id"><?php esc_html_e( 'Referral ID Type', 'mycred-woocommerce-plus' );?></label>
            <?php
            mycred_create_radio_field( 
                array(
                    'id'     => 'mwp-referral-link-id',
                    'name'   => $this->field_name( 'referral_link_type' ),
                    'label'  => __( 'Assign numeric referral IDs to each user', 'mycred-woocommerce-plus' ),
                    'after'  => true
                ),
                'id',
                ! empty( $settings['referral_link_type'] ) ? $settings['referral_link_type'] === 'id' : true
            );
            ?>
            <p><i><?php echo esc_html( sprintf( __( 'Example: %s', 'mycred-woocommerce-plus' ), home_url('your-product/?productref=1') ) );?></i></p>
            <?php
            mycred_create_radio_field( 
                array(
                    'id'     => 'mwp-referral-link-username',
                    'name'   => $this->field_name( 'referral_link_type' ),
                    'label'  => __( 'Assign usernames as IDs for each user', 'mycred-woocommerce-plus' ),
                    'class'  => 'mt-4',
                    'after'  => true
                ),
                'username',
                 ! empty( $settings['referral_link_type'] ) ? $settings['referral_link_type'] === 'username' : true
            );
            ?>
            <p><i><?php echo esc_html( sprintf( __( 'Example: %s', 'mycred-woocommerce-plus' ), home_url('your-product/?productref=john+doe') ) );?></i></p>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
        <h3><?php esc_html_e( 'Referral Menu', 'mycred-woocommerce-plus' ) ?></h3>
        <div class="form-group pb-1">
            <?php  
            mycred_create_toggle_field( 
                array(
                    'id'     => $this->field_id( 'enable_myaccount_tab' ),
                    'name'   => $this->field_name( 'enable_myaccount_tab' ),
                    'label'  => __( 'Show Tab in My Account', 'mycred-woocommerce-plus' ),
                    'inline' => false
                ), 
                ! empty ( $settings['enable_myaccount_tab'] ) ? $settings['enable_myaccount_tab'] : '',
                ! empty ( $settings['enable_myaccount_tab'] ) ? $settings['enable_myaccount_tab'] : ''
            );
            ?>
            <p><i>
                <?php 
                printf( 
                    esc_html__( 'Enable this option to add Product referral menu in My Account. You can also use this shortcode %s on your desired page.', 'mycred-woocommerce-plus' ), 
                    '<strong>[mycred_woocommerce_product_referral]</strong>' 
                ); 
                ?>
                </i></p>
        </div>
        <div class="form-group">
            <label for="<?php echo esc_attr( $this->field_id( 'tab_label' ) ); ?>"><?php esc_html_e( 'Tab Label', 'mycred-woocommerce-plus' );?></label>
            <?php  
            mycred_create_input_field( array(
                'type'  => 'text',
                'name'  => $this->field_name( 'tab_label' ),
                'id'    => $this->field_id( 'tab_label' ),
                'class' => 'form-control',
                'value' =>  ! empty ( $settings['tab_label'] ) ? $settings['tab_label'] : '',
            ) );
            ?>
            <p><i><?php esc_html_e( 'Label of Tab in My Account.', 'mycred-woocommerce-plus' );?></i></p>
        </div>
    </div>
</div>