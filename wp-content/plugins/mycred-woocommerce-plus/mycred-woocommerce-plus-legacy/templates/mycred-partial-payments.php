<?php

if ( isset( $multiple_payment_restricted ) && true === $multiple_payment_restricted ) {
	return;
}

$total          = mycred_part_woo_get_total();
$selecttype     = $mycred_partial_payment['selecttype'];
$step           = isset( $mycred_partial_payment['step'] ) && ! empty( $mycred_partial_payment['step'] ) ? $mycred_partial_payment['step'] : 1;
$max            = $mycred->number( $total / $mycred_partial_payment['exchange'] );
$max            = ( ( $mycred_partial_payment['max'] < 100 ) ? ( $max / 100 ) * $mycred_partial_payment['max'] : $max );
$blockOnTop     = isset( $mycred_partial_payment['position'] ) && 'before' === $mycred_partial_payment['position'] ? 'order-top' : '';

if ( $balance < $max ) {
	$max = $balance;
}

if ( $max < $min ) {
	$min = $max;
}

if ( 'input' == $selecttype ) {
	$slider_type = 'number';
} else {
	$slider_type = 'range';
}

/**
 * Action mycred_woo_partial_before_selector
 * 
 * @since 1.0
**/
do_action( 'mycred_woo_partial_before_selector' );

?>
<style> 
.cart-collaterals #mycred-partial-payment-woo { 
	float : left; 
	width: 45%; 
} 
#mycred-partial-payment-wrapper { 
	margin-bottom: 24px; 
}
#mycred-partial-payment-total, #mycred-range-selector { 
	margin-bottom: 12px; 
}
</style>
<div id="mycred-partial-payment-woo" class="<?php echo esc_attr( $blockOnTop ); ?>">
	<h3><?php echo esc_html__( wptexturize( ! empty( $mycred_partial_payment['title'] ) ? $mycred_partial_payment['title'] : 'Partial Payment' ), 'mycredpartwoo' ); ?></h3>
	<p><?php echo esc_html__( wptexturize( $mycred_partial_payment['desc'] ), 'mycredpartwoo' ); ?></p>
	<div id="mycred-partial-payment-wrapper" class="uses-<?php echo esc_attr( $selecttype ); ?>">
		<div id="mycred-partial-payment-total">
			<h2><?php echo esc_attr( $mycred->format_creds( $min ) ); ?></h2>
			<p><?php echo wp_kses_post( mycred_wcp_kses_html( wc_price( $min * $mycred_partial_payment['exchange'] ) ) ); ?> <?php esc_attr_e( 'Discount', 'mycredpartwoo' ); ?></p>
		</div>
		<?php
		if ( $balance > $mycred->zero() ) {
			?>
		
			<form id="mycred_partial_payment_form" action="" method="post">
				<div id="mycred-range-selector">
					<input 
						type="<?php echo esc_attr( $slider_type ); ?>" 
						class="mycred-partial-payment-inpt" 
						name="mycred_partial_payment[amount]"
						min="<?php echo esc_attr($mycred->number( $min )); ?>" 
						max="<?php echo esc_attr( $max ); ?>"
						<?php
						/* translators: %s: Increments */
						$increments = sprintf( __( 'Increments of %s', 'mycredpartwoo' ), strip_tags( $mycred->format_creds( $step ) ) );
						if ( 'input' == $selecttype ) {
							echo 'class="input-text"';
							if ( false !== $step ) {
								echo 'step="' . esc_attr__( $step, 'mycredpartwoo' ) . '"';
							}
						} else {
							echo 'class="input-range"';
							if ( false !== $step ) {
								echo 'step="' . esc_attr__( $step, 'mycredpartwoo' ) . '"';
								echo 'placeholder="' . wp_kses_post( mycred_wcp_kses_html( $increments ) ) . '"';
							}
						}
						?>
						value="<?php echo esc_attr($mycred->number( $mycred_partial_payment['default'] )); ?>" 
						style="width:100%;"
						/>
					</div>
				<div id="mycred-range-action">
					<input type="submit" name="submit" disabled="disabled" value="<?php echo esc_html__( ! empty( $mycred_partial_payment['button'] ) ? $mycred_partial_payment['button'] : 'Apply discount' , 'mycredpartwoo' ); ?>" />
				</div>
			</form>
			<?php
		} else {
			?>
			<p><?php echo esc_html__( 'Not enough points to use partial payment.', 'mycredpartwoo' ); ?></p>
			<?php
		}
		?>
	</div>
</div>
<?php

/**
 * Action mycred_woo_partial_after_selector
 * 
 * @since 1.0
**/
do_action( 'mycred_woo_partial_after_selector' );
