<?php defined( 'ABSPATH' ) or exit; ?>

<strong>
	<?php _e( 'Activation Error:', 'give-currency-switcher' ); ?>
</strong>
<?php _e( 'You must have', 'give-currency-switcher' ); ?> <a href="https://givewp.com" target="_blank">Give</a>
<?php _e( 'version', 'give-currency-switcher' ); ?> <?php echo GIVE_VERSION; ?>+
<?php printf( esc_html__( 'for the %1$s add-on to activate', 'give-currency-switcher' ), GIVE_CURRENCY_SWITCHER_ADDON_NAME ); ?>.

