<?php
/**
 * Verification Status Column Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Skip admin users.
if ( $is_admin_user ) {
	echo '-';
	return;
}

$is_verified           = ( 'true' === $verified );
$verified_icon_css     = $is_verified ? '' : 'display:none';
$unverified_icon_css   = $is_verified ? 'display:none' : '';
?>
<span style="<?php echo esc_attr( $verified_icon_css ); ?>" class="dashicons dashicons-yes cev_5 cev_verified_admin_user_action" title="<?php esc_attr_e( 'Verified', 'customer-email-verification' ); ?>"></span>
<span style="<?php echo esc_attr( $unverified_icon_css ); ?>" class="dashicons dashicons-no no-border cev_unverified_admin_user_action cev_5" title="<?php esc_attr_e( 'Unverify', 'customer-email-verification' ); ?>"></span>
