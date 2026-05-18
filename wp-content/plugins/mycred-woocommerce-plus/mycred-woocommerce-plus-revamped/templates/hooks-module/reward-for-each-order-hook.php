<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hook-instance">
    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $obj->field_id( 'reward_on_each' ) );?>"><?php esc_html_e( 'Reward Type', 'mycred' );?></label> 
                <?php 
                mycred_create_select_field( 
                    array(
                        'fixed_rate'    => __( 'Fixed', 'mycred-woocommerce-plus' ),
                        'percentage'    => __( 'Percentage', 'mycred-woocommerce-plus' ),
                        'exchange_rate' => __( 'Exchange Rate', 'mycred-woocommerce-plus' )
                    ), 
                    $prefs['reward_on_each'],
                    array(
                        'name'  => $obj->field_name( 'reward_on_each' ),
                        'id'    => $obj->field_id( 'reward_on_each' ),
                        'class' => 'form-control mwp-hook-reward-on-each'
                    )
                );
                ?>
                <span class="description"><?php esc_html_e( 'Select the type of reward you want to give', 'mycred' );?></span>
            </div>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
            <div class="form-group">
                <?php 
                $label_each_order = $obj->core->plural();

                if ( $prefs['reward_on_each'] == 'percentage' ) {
                    $label_each_order = 'Percentage (%)';
                }
                elseif ( $prefs['reward_on_each'] == 'exchange_rate' ) {
                    $label_each_order = 'Rate (' . $obj->core->plural() . ')';
                }
                ?>
                <label for="<?php echo esc_attr( $obj->field_id( 'creds' ) ); ?>" class="mwp-hook-each-order-amt-label" data-type="<?php echo esc_attr( $obj->core->plural() ); ?>"><?php echo esc_html( $label_each_order ); ?></label>
                <?php 
                mycred_create_input_field( array(
                    'type'  => 'text',
                    'name'  => $obj->field_name( 'creds' ),
                    'id'    => $obj->field_id( 'creds' ),
                    'class' => 'form-control',
                    'value' => $prefs['creds']
                ) );
                ?>
            </div>
        </div>
        <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $obj->field_id( 'log' ) );?>"><?php esc_html_e( 'Log Template', 'mycred' );?></label>
                <?php 
                mycred_create_input_field( array(
                    'type'        => 'text',
                    'name'        => $obj->field_name( 'log' ),
                    'id'          => $obj->field_id( 'log' ),
                    'class'       => 'form-control',
                    'placeholder' => 'form-control',
                    'value'       => $prefs['log']
                ) );
                ?>
                <span class="description"><?php echo wp_kses_post( $obj->available_template_tags( array( 'general' ) ) );?></span>
            </div>
        </div>
    </div>
</div>