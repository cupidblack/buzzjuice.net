<?php
/**
 * User Filter Dropdown Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'top' !== $which ) {
	return;
}

// Get selected filter value.
$top_filter    = isset( $_GET['customer_email_verified_top'] ) ? wc_clean( $_GET['customer_email_verified_top'] ) : '';
$bottom_filter = isset( $_GET['customer_email_verified_bottom'] ) ? wc_clean( $_GET['customer_email_verified_bottom'] ) : '';
$selected_value = ! empty( $top_filter ) ? $top_filter : $bottom_filter;

// Determine selected options.
$true_selected  = ( 'true' === $selected_value ) ? 'selected' : '';
$false_selected = ( 'false' === $selected_value ) ? 'selected' : '';
?>
<select name="customer_email_verified_<?php echo esc_attr( $which ); ?>" style="float:none; margin-left:10px;">
	<option value=""><?php esc_html_e( 'User verification', 'customer-email-verification-pro' ); ?></option>
	<option value="true" <?php echo esc_attr( $true_selected ); ?>>
		<?php esc_html_e( 'Verified', 'customer-email-verification-pro' ); ?>
	</option>
	<option value="false" <?php echo esc_attr( $false_selected ); ?>>
		<?php esc_html_e( 'Non verified', 'customer-email-verification-pro' ); ?>
	</option>
</select>
<?php
submit_button( __( 'Filter', 'customer-email-verification' ), '', $which, false );

