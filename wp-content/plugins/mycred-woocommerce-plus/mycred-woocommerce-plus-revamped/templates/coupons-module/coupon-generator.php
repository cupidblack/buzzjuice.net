<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$mycred = mycred( $settings['point_type'] );
$user_id = get_current_user_id();
$balance = $mycred->get_users_balance( $user_id );
$min = $settings['min'];

if ( ! empty( $settings['min'] ) && ! empty( $settings['coupon_max'] ) ) {
	if ( $settings['coupon_max'] < $settings['min'] ) {
		$min = $settings['coupon_max'];
	}
}


?>

<div class="mycred-coupon">
	<h3><?php echo esc_html__( wptexturize( ! empty( $settings['coupon_title'] ) ? $settings['coupon_title'] : 'Coupon' ), 'mycred-woocommerce-plus' ); ?></h3>
	<p><?php echo esc_html__( wptexturize( $settings['coupon_desc'] ), 'mycred-woocommerce-plus' ); ?></p>
	<p class="mycred-user-balance">
		<?php 
		/* translators: %s: user balance */
		printf( esc_html__( 'Your current balance is: %s', 'mycred-woocommerce-plus' ), esc_html__( $mycred->format_creds( $balance ), 'mycred-woocommerce-plus' ) ); 
		?>
	</p>
	<p>
		<?php 
		if ( !empty ( $settings['coupon_max'] ) ) {
			/* translators: %1$1s coupon max,  %2$2s: point type */
			printf( esc_html__( 'You can only create %1$1s %2$2s coupon.', 'mycred-woocommerce-plus' ), esc_html__( $mycred->format_creds( $settings['coupon_max'] ), 'mycred-woocommerce-plus' ), esc_html__( $mycred->plural() ), 'mycred-woocommerce-plus' ); 
		}
		?>
	</p>
	<?php
	
		$email_enabled   = ! empty( $settings['email_field'] ) ? $settings['email_field'] : 0;
		$required_email  = ! empty( $settings['required_email'] ) ? $settings['required_email'] : 0;

		if ( $email_enabled ) : ?>
			<div>
				<label>
					<?php echo esc_html__( 'Email: ', 'mycred-woocommerce-plus' ); ?>
					<span id="email-required-asterisk" style="color: red; font-weight: bold; <?php echo $required_email ? '' : 'display: none;'; ?>">
						*
					</span>
				</label>
				<input type="email" class="email_field" id="email-input" <?php echo $required_email ? 'required' : ''; ?> />
					<p id="email-error" style="color: red; display: none; font-size: 14px; margin-top: 5px;">
						<?php echo esc_html__( 'Please enter a valid email address.', 'mycred-woocommerce-plus' ); ?>
					</p>
			</div>

			<script>
			document.addEventListener("DOMContentLoaded", function() {
				var requiredEmailToggle = document.getElementById("<?php echo $this->field_id( 'required_email' ); ?>");
				var emailAsterisk = document.getElementById("email-required-asterisk");
				var emailInput = document.getElementById("email-input");

				if (!requiredEmailToggle) {
					return;
				}

				function toggleAsterisk() {
					if (requiredEmailToggle.checked) {
						emailAsterisk.style.display = "inline";
						emailInput.setAttribute("required", "required");
					} else {
						emailAsterisk.style.display = "none";
						emailInput.removeAttribute("required");
					}
				}

				// Initial check on page load
				toggleAsterisk();

				// Event listener for change
				requiredEmailToggle.addEventListener("change", toggleAsterisk);
			});
			</script>

		<?php endif; ?>
	<p class="mycred-coupon-code"></p>
	<?php
	if ( $balance > $mycred->zero() ) {
		?>
		<form id="mycred_coupon_form" action="" method="post">
			<label><?php echo esc_html__( 'Amount: ', 'mycred-woocommerce-plus' ); ?></label>
			<input 
			type="number" 
			class="mycred-coupon-amount"
			<?php echo ! empty( $min ) ? 'min="' . esc_attr__( $mycred->number( $min ), 'mycred-woocommerce-plus' ) . '"' : 'min="1"'; ?>
			<?php echo !empty($settings['coupon_max']) && $settings['coupon_max'] != 0  ? 'max="' . esc_attr__( $mycred->number($settings['coupon_max']), 'mycred-woocommerce-plus' ) . '"' : 'max="' . esc_attr__( $mycred->get_users_balance($user_id), 'mycred-woocommerce-plus' ) . '"'; ?>
			size="5"
			name="mycred_to_woo[amount]"
			placeholder="Enter amount here"
			value="<?php echo ! empty( $min ) ? esc_attr( $mycred->number( $min ) ) : '1'; ?>"
			/>

			<div id="mycred-coupon-action">
				<input type="submit" name="submit" value="<?php echo esc_html__( ! empty( $settings['coupon_button'] ) ? $settings['coupon_button'] : 'Create Coupon' , 'mycred-woocommerce-plus' ); ?>" />
			</div>
		</form>
		<?php
	} else {
		?>
		<p><?php echo esc_html__( 'Not enough points to create coupons.', 'mycred-woocommerce-plus' ); ?></p>
		<?php
	}
	?>
</div>
