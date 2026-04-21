<?php
/**
 * Admin Notice Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="updated notice">
	<p><?php echo esc_html( $message ); ?></p>
</div>

