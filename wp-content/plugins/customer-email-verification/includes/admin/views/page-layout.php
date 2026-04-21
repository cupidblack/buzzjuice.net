<?php
/**
 * Admin Page Layout Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get plugin instance for path functions.
$plugin_instance = cev_pro();
$plugin_path     = $plugin_instance->get_plugin_path();
?>
<div class="woocommerce cev_admin_layout">
	<div class="cev_admin_content">
		<!-- Include the activity panel -->
		<?php
		$activity_panel_path = $plugin_path . '/includes/admin/views/activity_panel.php';
		if ( file_exists( $activity_panel_path ) ) {
			include $activity_panel_path;
		}
		?>

		<!-- Navigation menu and tabs -->
		<div class="cev_nav_div">
			<?php
			// Render the menu tabs.
			if ( isset( $admin_instance ) ) {
				$admin_instance->render_menu_tabs( $admin_instance->get_cev_tab_settings_data() );
			}
			?>
			<div class="menu_devider"></div>

			<!-- Include admin settings views -->
			<?php
			$admin_settings_path = $plugin_path . '/includes/admin/views/admin_options_settings.php';
			$users_tab_path      = $plugin_path . '/includes/admin/views/cev_users_tab.php';

			if ( file_exists( $admin_settings_path ) ) {
				require_once $admin_settings_path;
			}

			if ( file_exists( $users_tab_path ) ) {
				require_once $users_tab_path;
			}
			?>
		</div>
	</div>
</div>

