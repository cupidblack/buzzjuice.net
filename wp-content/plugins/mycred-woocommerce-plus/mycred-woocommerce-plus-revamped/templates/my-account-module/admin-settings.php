<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <?php 
        mycred_create_toggle_field( 
            array(
                'id' => $this->field_id( 'enable' ),
                'name' => $this->field_name( 'enable' ),
                'label' => __( 'Enable', 'mycred-woocommerce-plus' ),
                'after' => true
            ), 
            $settings['enable'],
            $settings['enable']
        );
        ?>
    </div>
</div>
<hr class="mb-4">
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
        <div class="form-group">
            <label for="<?php echo esc_attr( $this->field_id( 'tab_label' ) ); ?>"><?php esc_html_e( 'Tab Label', 'mycred-woocommerce-plus' );?></label>
            <?php  
            mycred_create_input_field( array(
                'type'       => 'text',
                'name'       => $this->field_name( 'tab_label' ),
                'id'         => $this->field_id( 'tab_label' ),
                'class'      => 'form-control',
                'value'      => $settings['tab_label']
            ) );
            ?>
        </div>
    </div>
    <div class="col-lg-7 col-md-7 col-sm-12 col-xs-12 offset-md-1">
        <h3>Tab Elements</h3>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <?php  
                    mycred_create_toggle_field( 
                        array(
                            'id'    => $this->field_id( 'badges' ),
                            'name'  => $this->field_name( 'badges' ),
                            'label' => __( 'My Badges', 'mycred-woocommerce-plus' ),
                            'after' => true
                        ), 
                        $settings['badges'],
                        $settings['badges']
                    );
                    ?>
                    <p><i><?php esc_html_e( "Enable this option to display Users' Badges.", 'mycred-woocommerce-plus' ) ?></i></p>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <?php  
                    mycred_create_toggle_field( 
                        array(
                            'id'    => $this->field_id( 'ranks' ),
                            'name'  => $this->field_name( 'ranks' ),
                            'label' => __( 'My Ranks', 'mycred-woocommerce-plus' ),
                            'after' => true
                        ), 
                        $settings['ranks'],
                        $settings['ranks']
                    );
                    ?>
                    <p><i><?php esc_html_e( "Enable this option to display Users' Ranks.", 'mycred-woocommerce-plus' ) ?></i></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <?php  
                    mycred_create_toggle_field( 
                        array(
                            'id'    => $this->field_id( 'balances' ),
                            'name'  => $this->field_name( 'balances' ),
                            'label' => __( 'My Balances', 'mycred-woocommerce-plus' ),
                            'after' => true
                        ), 
                        $settings['balances'],
                        $settings['balances']
                    );
                    ?>
                    <p><i><?php esc_html_e( "Enable this option to display Users' Balances.", 'mycred-woocommerce-plus' ) ?></i></p>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="form-group">
                    <?php  
                    mycred_create_toggle_field( 
                        array(
                            'id'    => $this->field_id( 'level' ),
                            'name'  => $this->field_name( 'level' ),
                            'label' => __( 'My Level', 'mycred-woocommerce-plus' ),
                            'after' => true
                        ), 
                        $settings['level'],
                        $settings['level']
                    );
                    ?>
                    <p><i><?php esc_html_e( "Enable this option to display Users' Level.", 'mycred-woocommerce-plus' ) ?></i></p>
                </div>
            </div>
        </div>
    </div>
</div>