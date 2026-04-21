<?php
/**
 * Login Form Resend Link Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$resend_url = home_url( '?p=reset-verification-email' );
?>
<p class="woocommerce-LostPassword lost_password">
	<a href="<?php echo esc_url( $resend_url ); ?>">
		<?php esc_html_e( 'Resend verification email', 'customer-email-verification' ); ?>
	</a>
</p>

