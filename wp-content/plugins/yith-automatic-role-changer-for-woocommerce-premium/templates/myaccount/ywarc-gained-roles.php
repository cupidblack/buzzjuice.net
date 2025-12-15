<?php
/**
 * Notification when added or replaced a role for user.
 *
 * @var int $order_id
 *
 * @author  YITH <plugins@yithemes.com>
 * @package yith-woocommerce-automatic-role-changer.premium\templates\myaccount
 */

$order = wc_get_order( $order_id );
$rules = $order ? $order->get_meta( '_ywarc_rules_granted' ) : array();
if ( ! $rules ) {
	return;
}

$roles = array();

foreach ( $rules as $rule ) {
	$rule_to_display = yith_wcarc_get_rule_data_to_display( $rule );
	if ( ! empty( $rule_to_display['roles'] ) ) {
		foreach ( $rule_to_display['roles'] as $role_name ) {
			$roles[] = array(
				'name' => $role_name,
				'from' => $rule_to_display['from'] ?? '',
				'to'   => $rule_to_display['to'] ?? '',
			);
		}
	}
}
?>
<section class="yith_wcarc_gained_roles-section">
	<h2 class="yith_wcarc_gained_roles-section__title"><?php esc_html_e( 'Roles gained', 'yith-automatic-role-changer-for-woocommerce' ); ?></h2>

	<div class="yith_wcarc_gained_roles">
		<?php foreach ( $roles as $role ): ?>
			<?php
			$date_message = implode(
				' | ',
				array_filter(
					array(
						// translators: %s is the start date.
						! empty( $role['from'] ) ? sprintf( __( 'From: %s', 'yith-automatic-role-changer-for-woocommerce' ), $role['from'] ) : '',
						// translators: %s is the end date.
						! empty( $role['to'] ) ? sprintf( __( 'To: %s', 'yith-automatic-role-changer-for-woocommerce' ), $role['to'] ) : '',
					)
				)
			);
			?>
			<div class="yith_wcarc_gained_role">
				<div class="yith_wcarc_gained_role__message">
					<?php
					// translators: This message is followed by the role name; i.e.: You're now a Reseller.
					echo esc_html_x( "You're now a", 'Role pre-message', 'yith-automatic-role-changer-for-woocommerce' );
					?>
				</div>
				<div class="yith_wcarc_gained_role__role">
					<?php echo esc_html( $role['name'] ); ?>
				</div>
				<?php if ( $date_message ): ?>
					<div class="yith_wcarc_gained_role__dates">
						<?php echo $date_message; ?>
					</div>
				<?php endif; ?>

			</div>
		<?php endforeach; ?>
	</div>
</section>
