<?php
// No direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	return __( 'You must be logged in to use partial payments/store coupons.', 'mycred-woocommerce-plus
		' );
}

if ( ! WC()->cart->needs_payment() ) {
	return;
}

$mycred_partial_payment = $settings;
$user_id = get_current_user_id();
$all_point_types = mycred_get_types(true);
$enabled_point_types = [];

foreach ( $all_point_types as $key => $label ) {
	$mycred = mycred( $key );
	if ( isset($mycred->core['partial_payment_settings']['enable_pointtype']) && $mycred->core['partial_payment_settings']['enable_pointtype'] == 1 ) {
		$enabled_point_types[$key] = $label;
	}
}

$change_position = $mycred_partial_payment['change_position'];

if ( ( 'both' == $change_position ) || ( 'cart' == $change_position ) || ( 'checkout' == $change_position  ) ) {
	
	$total = mycred_part_woo_get_total( $mycred_partial_payment );
	$selecttype = $mycred_partial_payment['selecttype'];
	$step = isset( $mycred_partial_payment['step'] ) && ! empty( $mycred_partial_payment['step'] ) ? $mycred_partial_payment['step'] : 1;
	$blockOnTop = isset( $mycred_partial_payment['position'] ) && 'before' === $mycred_partial_payment['position'] ? 'order-top' : '';
	$slider_type = ( 'input' == $selecttype ) ? 'number' : 'range';

	/**
	 * Action mycred_woo_partial_before_selector
	 * 
	 * @since 1.0
	**/
	do_action( 'mycred_woo_partial_before_selector' );
	?>

	<style>
		.cart-collaterals #mycred-partial-payment-woo {
			float: left;
			width: 45%;
		}

		#mycred-partial-payment-wrapper {
			margin-bottom: 24px;
		}

		#mycred-partial-payment-total,
		#mycred-range-selector {
			margin-bottom: 12px;
		}
	</style>


	<div id="mycred-partial-payment-woo" class="<?php echo esc_attr( $blockOnTop ); ?>">
		<h3><?php echo esc_html__( wptexturize( ! empty( $mycred_partial_payment['title'] ) ? $mycred_partial_payment['title'] : 'Partial Payment' ), 'mycred-woocommerce-plus' ); ?></h3>

		<p><?php echo esc_html__( wptexturize( $mycred_partial_payment['desc'] ), 'mycred-woocommerce-plus
		' ); ?></p>
		<?php
		$count = count( $enabled_point_types );

		if ( $count === 0 || empty( $enabled_point_types ) ) : ?>
			<p><?php echo esc_html__( 'No Point type available', 'mycred-woocommerce-plus' ); ?></p>
		<?php else : 
			$hidden = ( $count === 1 ) ? 'style="display: none;"' : '';
			?>
			<?php if ( $count !== 1 ) : ?>
				<label for="point-type-select"><?php echo esc_html__( 'Select Point Type:', 'mycred-woocommerce-plus' ); ?></label>
			<?php endif; ?>
			<select id="point-type-select" name="point_type" <?php echo $hidden; ?>>
				<option value="0"><?php echo esc_html__( 'Select Point Type', 'mycred-woocommerce-plus' ); ?></option>
				<?php
				foreach ( $enabled_point_types as $key => $label ) :
					$mycred = mycred( $key );
					if ( isset( $mycred->core['partial_payment_settings']['enable_pointtype'] ) && $mycred->core['partial_payment_settings']['enable_pointtype'] == 1 ) :
						?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php
					endif;
				endforeach;
				?>
			</select>
		<?php endif; ?>


		<div id="mycred-partial-payment-wrapper" class="uses-<?php echo esc_attr( $selecttype ); ?>">
			<div id="mycred-partial-payment-total">
				<p class="point-cost-desc" ></p>
				<p>
					<?php 
					echo wp_kses_post( wc_price( 0 ) ); 
					echo ' ' . esc_html( apply_filters( 'mycred_woocommerce_plus_discount_text', __( 'Discount', 'mycred-woocommerce-plus' ) ) ); 
					?>
				</p>
			</div>

			<form id="mycred_partial_payment_form" action="" method="post">
				<div id="mycred-range-selector">
					<input type="<?php echo esc_attr( $slider_type ); ?>" class="mycred-partial-payment-inpt" name="mycred_partial_payment[amount]" min="<?php echo esc_attr( $mycred->number( 0 ) ); ?>" max="<?php echo esc_attr( 0 ); ?>" 
					<?php
					$increments = sprintf( __( 'Increments of %s', 'mycred-woocommerce-plus
						' ), strip_tags( $mycred->format_creds( $step ) ) );
					if ( 'input' == $selecttype ) {
						echo 'class="input-text"';
						if ( false !== $step ) {
							echo ' step="' . esc_attr( $step ) . '"';
						}
					} else {
						echo 'class="input-range"';
						if ( false !== $step ) {
							echo ' step="' . esc_attr( $step ) . '"';
							echo ' placeholder="' . esc_attr( $increments )  . '"';
						}
					}
				?> value="<?php echo esc_attr( $mycred->number( 0 ) ); ?>" style="width:100%;" />
			</div>
			<div id="mycred-range-action">
				<input type="submit" name="submit" value="<?php echo esc_html__( ! empty( $mycred_partial_payment['button'] ) ? $mycred_partial_payment['button'] : 'Apply discount' , 'mycred-woocommerce-plus
				' ); ?>" />
			</div>
		</form>

	</div>
</div>
<?php
	/**
	 * Action mycred_woo_partial_after_selector
	 * 
	 * @since 1.0
	**/
	do_action( 'mycred_woo_partial_after_selector' );
}
?>
