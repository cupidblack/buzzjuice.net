<?php
/**
 * User Profile Fields Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$user_id  = absint( $user->ID );
$verified = get_user_meta( $user_id, $verification_meta_key, true );
$nonce    = wp_create_nonce( $nonce_action_email );

// Skip admin users.
$can_verify = ! $is_admin_user;
$is_verified = ( 'true' === $verified );
?>
<table class="form-table cev-admin-menu">
	<tr>
		<th colspan="2">
			<h4 class="cev_admin_user">
				<?php esc_html_e( 'Customer verification', 'customer-email-verification' ); ?>
			</h4>
		</th>
	</tr>
	<tr>
		<th class="cev-admin-padding">
			<label><?php esc_html_e( 'Email verification status:', 'customer-email-verification' ); ?></label>
		</th>
		<td>
			<?php if ( $can_verify ) : ?>
				<?php
				$verified_icon_css   = $is_verified ? '' : 'display:none';
				$unverified_icon_css = $is_verified ? 'display:none' : '';
				?>
				<span style="<?php echo esc_attr( $verified_icon_css ); ?>" 
					  class="dashicons dashicons-yes cev_5 cev_verified_admin_user_action_single" 
					  title="<?php esc_attr_e( 'Verified', 'customer-email-verification' ); ?>">
				</span>
				<span style="<?php echo esc_attr( $unverified_icon_css ); ?>" 
					  class="dashicons dashicons-no no-border cev_unverified_admin_user_action_single cev_5" 
					  title="<?php esc_attr_e( 'Unverify', 'customer-email-verification' ); ?>">
				</span>
			<?php else : ?>
				<?php esc_html_e( 'Admin', 'customer-email-verification' ); ?>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<?php if ( $can_verify ) : ?>
				<?php
				$verify_btn_css   = $is_verified ? 'display:none' : '';
				$unverify_btn_css = $is_verified ? '' : 'display:none';
				?>
				<a style="<?php echo esc_attr( $verify_btn_css ); ?>" 
				   class="button-primary cev-admin-verify-button cev_dashicons_icon_verify_user" 
				   id="<?php echo esc_attr( $user_id ); ?>" 
				   wp_nonce="<?php echo esc_attr( $nonce ); ?>">
					<span class="dashicons dashicons-yes cev-admin-dashicons" style="color:#ffffff; margin-right: 2px;"></span>
					<span><?php esc_html_e( 'Verify email manually', 'customer-email-verification' ); ?></span>
				</a>
				<a style="<?php echo esc_attr( $unverify_btn_css ); ?>" 
				   class="button-primary cev-admin-unverify-button cev_dashicons_icon_unverify_user" 
				   id="<?php echo esc_attr( $user_id ); ?>" 
				   wp_nonce="<?php echo esc_attr( $nonce ); ?>">
					<span class="dashicons dashicons-no cev-admin-dashicons"></span>
					<span><?php esc_html_e( 'Un-verify email', 'customer-email-verification' ); ?></span>
				</a>
			<?php endif; ?>
		</td>
	</tr>
</table>

