<?php
/**
 * Main class start.
 *
 * @package : plain
 */

defined( 'ABSPATH' ) || exit;
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
/* translators: %1$s: Order number. %2$s: Customer full name */
echo 'Test plain email';
if ( $additional_content ) {
	echo wp_kses_post( $content );
	echo "\n\n----------------------------------------\n\n";
}
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
