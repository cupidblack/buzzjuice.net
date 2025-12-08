<?php defined( 'ABSPATH' ) or exit; ?>

<div class="notice notice-error">
	<p>
		<strong><?php esc_html_e( 'Activation Error:', 'give-woocommerce' ); ?></strong>
		<?php esc_html_e( 'You must have', 'give-woocommerce' ); ?> <a href="https://woocommerce.com" target="_blank">Woocommerce</a>
		<?php
		printf(
			esc_html__( 'plugin installed and activated for the %s add-on to activate', 'give-woocommerce' ),
			GIVE_WOOCOMMERCE_ADDON_NAME
		);
		?>
	</p>
</div>
