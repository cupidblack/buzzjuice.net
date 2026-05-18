<?php

$mycred = mycred( $mycred_partial_payment['point_type'] );
$user_id = get_current_user_id();
$balance = $mycred->get_users_balance( $user_id );
$min = $mycred_partial_payment['min'];

if ( ! empty( $mycred_partial_payment['min'] ) && ! empty( $mycred_partial_payment['coupon_max'] ) ) {
	if ( $mycred_partial_payment['coupon_max'] < $mycred_partial_payment['min'] ) {
		$min = $mycred_partial_payment['coupon_max'];
	}
}


?>

<div class="mycred-coupon">
	<h3><?php echo esc_html__( wptexturize( ! empty( $mycred_partial_payment['coupon_title'] ) ? $mycred_partial_payment['coupon_title'] : 'Coupon' ), 'mycredpartwoo' ); ?></h3>
	<p><?php echo esc_html__( wptexturize( $mycred_partial_payment['coupon_desc'] ), 'mycredpartwoo' ); ?></p>
	<p class="mycred-user-balance">
		<?php 
		/* translators: %s: user balance */
		printf( esc_html__( 'Your current balance is: %s', 'mycredpartwoo' ), esc_html__( $mycred->format_creds( $balance ), 'mycredpartwoo' ) ); 
		?>
	</p>
	<p>
		<?php 
		if ( !empty ( $mycred_partial_payment['coupon_max'] ) ) {
			/* translators: %1$1s coupon max,  %2$2s: point type */
			printf( esc_html__( 'You can only create %1$1s %2$2s coupon.', 'mycredpartwoo' ), esc_html__( $mycred->format_creds( $mycred_partial_payment['coupon_max'] ), 'mycredpartwoo' ), esc_html__( $mycred->plural() ), 'mycredpartwoo' ); 
		}
		?>
	</p>
	<p class="mycred-coupon-code"></p>
	<?php
	if ( $balance > $mycred->zero() ) {
		?>
		<form id="mycred_coupon_form" action="" method="post">
			<label><?php echo esc_html__( 'Amount: ', 'mycredpartwoo' ); ?></label>
			<input 
			type="number" 
			class="mycred-coupon-amount"
			<?php echo ! empty( $min ) ? 'min="' . esc_attr__( $mycred->number( $min ), 'mycredpartwoo' ) . '"' : 'min="1"'; ?>
			<?php echo ! empty($mycred_partial_payment['coupon_max']) ? 'max="' . esc_attr__( $mycred->number( $mycred_partial_payment['coupon_max'] ), 'mycredpartwoo' ) . '"' : ''; ?>
			size="5"
			name="mycred_to_woo[amount]"
			placeholder="Enter amount here"
			value="<?php echo ! empty( $min ) ? esc_attr( $mycred->number( $min ) ) : '1'; ?>"
			/>

			<div id="mycred-coupon-action">
				<input type="submit" name="submit" value="<?php echo esc_html__( ! empty( $mycred_partial_payment['coupon_button'] ) ? $mycred_partial_payment['coupon_button'] : 'Create Coupon' , 'mycredpartwoo' ); ?>" />
			</div>
		</form>
		<?php
	} else {
		?>
		<p><?php echo esc_html__( 'Not enough points to create coupons.', 'mycredpartwoo' ); ?></p>
		<?php
	}
	?>
</div>
