<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mwp-my-account-container">
    <?php if( $settings['balances'] ):?>
    <div class="mwp-my-account-item balance">
        <h4><b><?php esc_html_e( 'My Balance', 'mycred-woocommerce-plus' );?></b></h4>
        <hr>
        <div class="mwp-my-account-item-body">
            <?php foreach( $point_types as $type_key => $type_name ):?>
            <div class="loop <?php echo esc_attr( $type_key ); ?>"> 
                <div class="mycred-points-lable"><?php echo esc_attr( $type_name );?></div>
                <div class="mycred-points">
                    <?php echo do_shortcode( '[mycred_my_balance type="' . esc_attr( $type_key ) . '"]' );?>
                </div>
            </div>
            <?php endforeach;?>
        </div>
    </div>
    <?php endif;?>
    <?php if( $settings['badges'] ):?>
    <div class="mwp-my-account-item badges">
        <h4><b><?php esc_html_e( 'My Badges', 'mycred-woocommerce-plus' );?></b></h4>
        <hr>
        <div class="mwp-my-account-item-body">
            <?php echo do_shortcode( '[mycred_my_badges]' ); ?>
        </div>
    </div>
    <?php endif;?>
    <?php if( $settings['ranks'] ):?>
    <div class="mwp-my-account-item ranks">
        <h4><b><?php esc_html_e( 'My Ranks', 'mycred-woocommerce-plus' );?></b></h4>
        <hr>
        <div class="mwp-my-account-item-body">
             <?php foreach( $point_types as $type_key => $type_name ):?>
            <div class="loop <?php echo esc_attr( $type_key ); ?>"> 
                <div class="mycred-points-lable"><?php echo esc_attr( $type_name );?></div>
                <div class="mycred-points">
                    <?php echo do_shortcode( '[mycred_my_rank ctype="' . esc_attr( $type_key ) . '"]' );?>
                </div>
            </div>
            <?php endforeach;?>
        </div>
    </div>
    <?php endif;?>
    <?php if( $settings['level'] ):?>
    <div class="mwp-my-account-item level">
        <h4><b><?php esc_html_e( 'My Level', 'mycred-woocommerce-plus' ); ?></b></h4>
        <hr>
        <div class="mwp-my-account-item-body">
            <?php echo do_shortcode( '[mycred_leaderboard_position]' );?>
        </div>
    </div>
    <?php endif;?>
</div>