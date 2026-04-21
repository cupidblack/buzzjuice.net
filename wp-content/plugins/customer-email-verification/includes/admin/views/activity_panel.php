<?php
/**
 * Activity Panel Template
 *
 * Displays the support and documentation menu in the admin area.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get plugin instance for URL and version.
$plugin_instance = cev_pro();
$plugin_url      = $plugin_instance->plugin_dir_url();
$plugin_version  = isset( $plugin_instance->version ) ? $plugin_instance->version : '1.0.0';

// Define support and documentation links.
$support_link      = 'https://www.zorem.com/?support=1';
$documentation_link = 'https://docs.zorem.com/docs/customer-email-verification/';
?>
<div class="menu-container">
	<button class="menu-button" type="button" aria-label="<?php esc_attr_e( 'Open support menu', 'customer-email-verification' ); ?>">
		<span class="menu-icon">
			<span class="dashicons dashicons-menu-alt" aria-hidden="true"></span>
		</span>
	</button>
	<div class="popup-menu">
		<a href="<?php echo esc_url( $support_link ); ?>" class="menu-item" target="_blank" rel="noopener noreferrer">
			<span class="menu-icon">
				<img 
					src="<?php echo esc_url( $plugin_url . 'assets/images/get-support-icon-20.svg' ); ?>?v=<?php echo esc_attr( $plugin_version ); ?>" 
					alt="<?php esc_attr_e( 'Get Support', 'customer-email-verification' ); ?>"
					width="20"
					height="20"
				>
			</span>
			<?php esc_html_e( 'Get Support', 'customer-email-verification' ); ?>
		</a>
		<a href="<?php echo esc_url( $documentation_link ); ?>" class="menu-item" target="_blank" rel="noopener noreferrer">
			<span class="menu-icon">
				<img 
					src="<?php echo esc_url( $plugin_url . 'assets/images/documentation-icon-20.svg' ); ?>?v=<?php echo esc_attr( $plugin_version ); ?>" 
					alt="<?php esc_attr_e( 'Documentation', 'customer-email-verification' ); ?>"
					width="20"
					height="20"
				>
			</span>
			<?php esc_html_e( 'Documentation', 'customer-email-verification' ); ?>
		</a>
	</div>
</div>
