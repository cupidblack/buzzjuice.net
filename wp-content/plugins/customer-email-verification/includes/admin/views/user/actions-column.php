<?php
/**
 * Actions Column Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Skip admin users.
if ( $is_admin_user ) {
	return;
}

$is_verified         = ( 'true' === $verified );
$verify_btn_css      = $is_verified ? 'display:none' : '';
$unverify_btn_css    = $is_verified ? '' : 'display:none';
$nonce               = wp_create_nonce( $nonce_action_email );
?>
<span style="<?php echo esc_attr( $unverify_btn_css ); ?>" class="dashicons dashicons-no cev_dashicons_icon_unverify_user" id="<?php echo esc_attr( $user_id ); ?>" wp_nonce="<?php echo esc_attr( $nonce ); ?>"></span>
<span style="<?php echo esc_attr( $verify_btn_css ); ?>" class="dashicons dashicons-yes small-yes cev_dashicons_icon_verify_user cev_10" id="<?php echo esc_attr( $user_id ); ?>" wp_nonce="<?php echo esc_attr( $nonce ); ?>"></span>
