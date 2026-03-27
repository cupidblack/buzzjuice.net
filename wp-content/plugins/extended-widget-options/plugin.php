<?php
/**
 * Plugin Name: Widget Options - Extended
 * Plugin URI: https://widget-options.com/
 * Description: Your all-in-one plugin to completely manage your blocks & widgets.
 * Version: 5.3.1
 * Author: Widget Options Team
 * Author URI: https://widget-options.com/
 * Text Domain: widget-options
 * Domain Path: languages
 *
 * @category Widgets
 * @author Widget Options Team
 * @version 5.3.1
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;
if (!class_exists('WP_Widget_Options')) :

	/**
	 * Main WP_Widget_Options Class.
	 *
	 * @since 4.1
	 */
	final class WP_Widget_Options
	{
		/**
		 * @var WP_Widget_Options The one true WP_Widget_Options
		 * @since 4.1
		 */
		private static $instance;

		/**
		 * Main WP_Widget_Options Instance.
		 *
		 * Insures that only one instance of WP_Widget_Options exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 4.1
		 * @static
		 * @staticvar array $instance
		 * @uses WP_Widget_Options::setup_constants() Setup the constants needed.
		 * @uses WP_Widget_Options::includes() Include the required files.
		 * @uses WP_Widget_Options::load_textdomain() load the language files.
		 * @see WIDGETOPTS()
		 * @return object|WP_Widget_Options The one true WP_Widget_Options
		 */
		public static function instance()
		{
			if (!isset(self::$instance) && !(self::$instance instanceof WP_Widget_Options)) {
				self::$instance = new WP_Widget_Options;
				self::$instance->setup_constants();

				// add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

				self::$instance->includes();
				// self::$instance->roles         = new WIDGETOPTS_Roles();
				add_filter('use_widgets_block_editor', array(self::$instance, 'widget_options_use_widgets_block_editor'));

				self::$instance->setup_widget_block_editor();
			}
			return self::$instance;
		}

		/**
		 * REVERT classic widgets screen
		 */
		public function widget_options_use_widgets_block_editor($use_widgets_block_editor)
		{
			global $widget_options;
			if (!empty($widget_options['classic_widgets_screen']) && $widget_options['classic_widgets_screen'] == 'activate') {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since 4.1
		 * @return void
		 */
		private function setup_constants()
		{

			// Plugin version.
			if (!defined('WIDGETOPTS_PLUGIN_NAME')) {
				define('WIDGETOPTS_PLUGIN_NAME', 'Extended Widget Options');
			}

			// Plugin version.
			if (!defined('WIDGETOPTS_VERSION')) {
				define('WIDGETOPTS_VERSION', '5.3.1');
			}

			// Plugin Folder Path.
			if (!defined('WIDGETOPTS_PLUGIN_DIR')) {
				define('WIDGETOPTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
			}

			// Plugin Folder URL.
			if (!defined('WIDGETOPTS_PLUGIN_URL')) {
				define('WIDGETOPTS_PLUGIN_URL', plugin_dir_url(__FILE__));
			}

			// Plugin Root File.
			if (!defined('WIDGETOPTS_PLUGIN_FILE')) {
				define('WIDGETOPTS_PLUGIN_FILE', __FILE__);
			}

			// Migration permissions capability (editor level minimum)
			if (!defined('WIDGETOPTS_MIGRATION_PERMISSIONS')) {
				define('WIDGETOPTS_MIGRATION_PERMISSIONS', 'edit_others_posts');
			}
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since 4.1
		 * @return void
		 */
		private function includes()
		{
			global $widget_options, $extended_license, $widgetopts_taxonomies, $widgetopts_pages, $widgetopts_types, $pagenow, $widgetopts_http_headers, $widgetopts_user_agent;

			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/register-settings.php';
			$widget_options = widgetopts_get_settings();

			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/extras.php';
			$widgetopts_http_headers = widgetopts_set_http_headers();
			$widgetopts_user_agent = widgetopts_get_user_agent();

			// Load Display Logic Snippets system
			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/snippets/class-snippets-cpt.php';
			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/snippets/class-snippets-api.php';
			if (is_admin()) {
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/snippets/class-snippets-admin.php';
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/snippets/class-snippets-migration.php';
			}

			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/scripts.php';

			//call admin only resources
			if (is_admin()) {

				//other global variables to prevent duplicate and faster calls
				$widgetopts_pages 		= widgetopts_global_pages();

				//admin settings
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/globals.php';
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/welcome.php';
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/display-settings.php';
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/transient.php';
				if (in_array($pagenow, array('options-general.php'))) {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/classic-widgets-screen.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/visibility.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/devices.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/alignment.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/title.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/classes.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/logic.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/links.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/fixed.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/columns.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/roles.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/dates.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/styling.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/animation.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/taxonomies.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/disable_widgets.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/permission.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/shortcodes.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/cache.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/siteorigin.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/sidebar-purchase_validation.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/sidebar-support_box.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/search.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/move.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/clone.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/elementor.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/sliding.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/widget-area.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/import-export.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/urls.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/beaver_builder.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/acf.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/state.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/page-and-post-block.php';
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/settings/modules/custom-sidebar.php';
				}

				// if( in_array( $pagenow, array( 'widgets.php' ) ) ){
				//widget callbacks
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/widgets.php';
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/disable.php';

				//add columns tab if activated
				if ($widget_options['columns'] == 'activate') {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/columns.php';
				}
				//add alignment tab if activated
				if ($widget_options['alignment'] == 'activate') {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/alignment.php';
				}
				//add roles tab if activated
				if ($widget_options['roles'] == 'activate' || (isset($widget_options['state']) && $widget_options['state'] == 'activate')) {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/roles.php';
				}
				//add visibility tab if activated
				if ($widget_options['visibility'] == 'activate') {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/visibility.php';
				}
				//add devices tab if activated
				if ($widget_options['devices'] == 'activate') {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/devices.php';
				}
				//add days and dates tab if activated
				if ($widget_options['dates'] == 'activate') {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/days-dates.php';
				}
				//add styling tab if activated
				if ($widget_options['styling'] == 'activate') {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/styling.php';
				}
				//add behavior tab if activated
				if (
					'activate' == $widget_options['hide_title'] ||
					'activate' == $widget_options['fixed'] ||
					'activate' == $widget_options['links'] ||
					'activate' == $widget_options['classes']
				) {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/behavior.php';
				}
				//add settings tab if activated
				if (
					'activate' == $widget_options['hide_title'] ||
					'activate' == $widget_options['fixed'] ||
					'activate' == $widget_options['links'] ||
					'activate' == $widget_options['classes'] ||
					'activate' == $widget_options['logic']
				) {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/settings.php';
				}

				if ('activate' == $widget_options['animation']) {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/option-tabs/animation.php';
				}

				if ((isset($widget_options['import_export']) && 'activate' == $widget_options['import_export']) ||
					(isset($widget_options['widget_area']) && 'activate' == $widget_options['widget_area'])
				) {
					require_once WIDGETOPTS_PLUGIN_DIR . 'includes/admin/import-export.php';
				}

				// }

				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/class-edd-license-handler.php';
				// auto updater
				if (class_exists('WIDGETOPTS_License')) {
					$extended_license = new WIDGETOPTS_License(WIDGETOPTS_PLUGIN_FILE, WIDGETOPTS_PLUGIN_NAME, WIDGETOPTS_VERSION, 'Phpbits Creative Studio');
				}
			} //end is_admin condition

			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/extras.php';
			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/display.php';
			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/shortcodes.php';
			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/ajax-functions.php';

			//pagebuilders support
			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/pagebuilders/siteorigin.php';
			if (defined('ELEMENTOR_VERSION') && isset($widget_options['elementor']) && 'activate' == $widget_options['elementor']) {
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/pagebuilders/elementor/elementor.php';
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/pagebuilders/elementor/render.php';
			}

			if (class_exists('FLBuilderLoader') && isset($widget_options['beaver']) && 'activate' == $widget_options['beaver']) {
				require_once WIDGETOPTS_PLUGIN_DIR . 'includes/pagebuilders/beaver/beaver.php';
			}


			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/install.php';
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since 5.0.1
		 * @return void
		 */
		private function setup_widget_block_editor()
		{
			require_once WIDGETOPTS_PLUGIN_DIR . 'includes/widgets/gutenberg/gutenberg-toolbar.php';
		}
	}

endif; // End if class_exists check.


/**
 * The main function for that returns WP_Widget_Options
 *
 * The main function responsible for returning the one true WP_Widget_Options
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $widgetopts = WP_Widget_Options(); ?>
 *
 * @since 4.1
 * @return object|WP_Widget_Options The one true WP_Widget_Options Instance.
 */
if (!function_exists('WIDGETOPTS')) {
	function WIDGETOPTS()
	{
		return WP_Widget_Options::instance();
	}
	// Get Plugin Running.
	if (function_exists('is_multisite') && is_multisite()) {
		//loads on plugins_loaded action to avoid issue on multisite
		add_action('plugins_loaded', 'WIDGETOPTS', apply_filters('widgetopts_priority', 90));
	} else {
		WIDGETOPTS();
	}
}
