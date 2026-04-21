<?php
/**
 * Admin Page Header Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get plugin instance for URL functions.
$plugin_instance = cev_pro();
$plugin_url      = $plugin_instance->plugin_dir_url();
?>
<div class="zorem-layout-cev__header">
	<!-- Page header with navigation breadcrumbs -->
	<h1 class="page_heading">
		<a href="javascript:void(0)">
			<?php esc_html_e( 'Customer Email Verification', 'customer-email-verification' ); ?>
		</a>
		<span class="dashicons dashicons-arrow-right-alt2"></span>
		<span class="breadcums_page_heading">
			<?php echo esc_html( $breadcrumb_text ); ?>
		</span>
	</h1>
	<!-- Plugin logo -->
	<img class="zorem-layout-cev__header-logo" 
		src="<?php echo esc_url( $plugin_url . 'assets/images/zorem-logo.png' ); ?>" 
		alt="<?php esc_attr_e( 'Zorem Logo', 'customer-email-verification' ); ?>">
</div>

