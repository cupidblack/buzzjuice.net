<?php
/**
 * Gained roles order meta-box.
 *
 * @var array $rules
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AutomaticRoleChanger\Views
 */

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
<p>
	<?php
	echo esc_html(
		_n(
			'Customer gained the following role:',
			'Customer gained the following roles:',
			count( $roles ),
			'yith-automatic-role-changer-for-woocommerce'
		)
	);
	?>
</p>
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
