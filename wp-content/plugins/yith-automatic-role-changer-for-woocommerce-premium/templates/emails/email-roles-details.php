<?php
/**
 * Details for changed roles in emails.
 *
 * @var array  $roles
 * @var string $username
 * @var bool   $sent_to_admin
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AutomaticRoleChanger\Templates
 */

defined( 'ABSPATH' ) || exit;
?>

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
			if ( $sent_to_admin ) {
				// translators: The placeholder is the username; This message is followed by the role name; i.e.: John Doe is now a Reseller.
				echo esc_html( sprintf( _x( "%s is now a", 'Role pre-message', 'yith-automatic-role-changer-for-woocommerce' ), $username ) );
			} else {
				// translators: This message is followed by the role name; i.e.: You're now a Reseller.
				echo esc_html_x( "You're now a", 'Role pre-message', 'yith-automatic-role-changer-for-woocommerce' );
			}
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