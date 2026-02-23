<?php defined( 'ABSPATH' ) or exit; ?>

<div class="notice notice-error">
	<p>
		<strong><?php _e( 'Activation Error:', 'give-currency-switcher' ); ?></strong>
		<?php esc_html_e( 'You must have', 'give-currency-switcher' ); ?> <a href="https://givewp.com" target="_blank">Give</a>
		<?php printf( __( 'plugin installed and activated for the %s add-on to activate', 'give-currency-switcher' ), GIVE_CURRENCY_SWITCHER_ADDON_NAME ); ?>
	</p>
</div>
