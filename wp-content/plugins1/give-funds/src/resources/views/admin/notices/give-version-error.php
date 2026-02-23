<?php defined( 'ABSPATH' ) or exit; ?>

<strong>
	<?php _e( 'Activation Error:', 'give-funds' ); ?>
</strong>
<?php _e( 'You must have', 'give-funds' ); ?> <a href="https://givewp.com" target="_blank">Give</a>
<?php _e( 'version', 'give-funds' ); ?> <?php echo GIVE_FUNDS_ADDON_MIN_GIVE_VERSION; ?>+
<?php printf( esc_html__( 'for the %1$s add-on to activate', 'give-funds' ), GIVE_FUNDS_ADDON_NAME ); ?>.

