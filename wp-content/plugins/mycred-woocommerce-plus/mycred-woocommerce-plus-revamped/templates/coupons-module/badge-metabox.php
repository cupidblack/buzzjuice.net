<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_nonce_field( 'mwp_module_coupons_nonce', 'mwp-coupon-reward-nonce' );
?>
<h4><?php esc_html_e( 'You can use these settings to reward users on achieving this badge.', 'mycred-woocommerce-plus' );?></h4>
<div class="mwp-badge-coupon-reward-wrapper">
	<?php foreach ( $settings as $key => $level ):?>
	<div class="mwp-badge-coupon-reward-item" data-level="<?php echo esc_attr( $key );?>">
		<div class="mwp-badge-coupon-reward-header">
			<?php echo ( ! empty( $badge_levels[$key]['label'] ) ? esc_html( $badge_levels[$key]['label'] ) : esc_html( sprintf( __( 'Level %s', 'mycred-woocommerce-plus' ), $key + 1 ) ) );?>
		</div>
		<div class="mwp-badge-coupon-reward-body">
			<div class="row">
			    <div class="col-lg-4 col-md-4 col-sm-12">
			        <div class="form-group">
			            <label for="discount-mycred-discount-type"><?php esc_html_e( 'Discount Type', 'mycred-woocommerce-plus' );?></label>
			            <?php 
			            mycred_create_select_field( 
			                array(
			                    'fixed'   => __( 'Fixed Discount', 'mycred-woocommerce-plus' ),
			                    'percent' => __( 'Percentage Discount', 'mycred-woocommerce-plus' )
			                ), 
			                $level['discount_type'],
			                array(
			                    'name'  => 'woo_discount[' . $key . '][discount_type]',
			                    'id'    => 'woo-discount-' . $key . '-discount-type',
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
			                'name'  => 'woo_discount[' . $key . '][mycred_coupon_code_badge]',
			                'id'    => 'woo-discount-' . $key . '-mycred-coupon-code-badge',
			                'class' => 'form-control',
			                'value' => $level['mycred_coupon_code_badge']
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
			                'name'  => 'woo_discount[' . $key . '][discount_amount]',
			                'id'    => 'woo-discount-' . $key . '-discount_amount',
			                'class' => 'form-control',
			                'min'   => '0',
			                'value' => $level['discount_amount']
			            ) );
			            ?>
			        </div>
			    </div>
			</div>
		</div>
	</div>
	<?php endforeach;?>
</div>
<p><i><?php esc_html_e( 'NOTE: Keep amount 0 in order to disable coupons for this rank.', 'mycred-woocommerce-plus' );?></i></p>
