<?php
/**
 * Main class start.
 *
 * @package : arc
 */

do_action( 'woocommerce_email_header', $email_heading, $email );
echo wp_kses_post( $content );
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );
