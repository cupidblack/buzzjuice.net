<?php defined( 'ABSPATH' ) or exit; ?>

<strong>
	<?php esc_html_e( 'Activation Error:', 'give-woocommerce' ); ?>
</strong>
<?php esc_html_e( 'You must have', 'give-woocommerce' ); ?> <a href="https://givewp.com" target="_blank">GiveWP</a>
<?php _e( 'version', 'give-woocommerce' ); ?> <?php echo GIVE_VERSION; ?>+
<?php printf( esc_html__( 'for the %1$s add-on to activate', 'give-woocommerce' ), GIVE_WOOCOMMERCE_ADDON_NAME ); ?>.

