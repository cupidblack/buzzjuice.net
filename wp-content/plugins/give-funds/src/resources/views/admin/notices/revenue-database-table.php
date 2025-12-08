<?php defined( 'ABSPATH' ) or exit; ?>

<strong>
	<?php esc_html_e( 'Activation Error:', 'give-funds' ); ?>
</strong>
<?php printf( esc_html__( 'There is a problem preventing %1$s from working. The correct version of GiveWP is installed, however the revenue table is missing. Please contact support for assistance.', 'give-funds' ), GIVE_FUNDS_ADDON_NAME ); ?>.
