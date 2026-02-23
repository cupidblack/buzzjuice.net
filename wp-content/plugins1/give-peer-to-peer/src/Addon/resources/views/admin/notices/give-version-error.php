<?php defined( 'ABSPATH' ) or exit; ?>

<strong>
	<?php _e( 'Activation Error:', 'give-peer-to-peer' ); ?>
</strong>
<?php _e( 'You must have', 'give-peer-to-peer' ); ?> <a href="https://givewp.com" target="_blank">GiveWP</a>
<?php _e( 'version', 'give-peer-to-peer' ); ?> <?php echo GIVE_VERSION; ?>+
<?php printf( esc_html__( 'for the %1$s add-on to activate', 'give-peer-to-peer' ), GIVE_P2P_NAME ); ?>.

